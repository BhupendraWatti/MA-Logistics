<?php

namespace App\Controllers;

use App\Models\TrackingHistoryModel;
use App\Models\BookingModel;
use CodeIgniter\API\ResponseTrait;

class TrackingController extends BaseController
{
    use ResponseTrait;

    protected $trackingModel;
    protected $bookingModel;

    public function __construct()
    {
        $this->trackingModel = new TrackingHistoryModel();
        $this->bookingModel = new BookingModel();
    }

    public function getHistory($booking_id)
    {
        $history = $this->trackingModel->where('booking_id', $booking_id)->orderBy('event_date', 'DESC')->orderBy('event_time', 'DESC')->findAll();
        
        $booking = $this->bookingModel->find($booking_id);
        
        session_write_close(); // Prevent database session write shutdown errors overriding 200 OK status
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $history,
            'booking' => $booking
        ]);
    }

    public function saveUpdate()
    {
        try {
            $postData = $this->request->getPost();
            
            // Handle file upload
            $proofImage = null;
            $file = $this->request->getFile('proof_image');
            
            // Create directory if it doesn't exist
            $uploadPath = FCPATH . 'uploads/pod';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $proofImage = 'uploads/pod/' . $newName;
            }

            $data = [
                'booking_id'       => $postData['booking_id'] ?? null,
                'awb_no'           => $postData['awb_no'] ?? null,
                'current_location' => $postData['current_location'] ?? null,
                'status'           => $postData['status'] ?? null,
                'event_date'       => $postData['event_date'] ?? null,
                'event_time'       => $postData['event_time'] ?? null,
                'remarks'          => $postData['remarks'] ?? null,
                'receiver_name'    => $postData['receiver_name'] ?? null,
                'receiver_phone'   => $postData['receiver_phone'] ?? null,
                'receiver_company' => $postData['receiver_company'] ?? null,
            ];

            if ($proofImage) {
                $data['proof_image'] = $proofImage;
            }

            $isUpdate = !empty($postData['id']);
            if ($isUpdate) {
                // Update
                if (!$this->trackingModel->update($postData['id'], $data)) {
                    throw new \Exception(implode(', ', $this->trackingModel->errors()));
                }
            } else {
                // Insert
                if (!$this->trackingModel->insert($data)) {
                    throw new \Exception(implode(', ', $this->trackingModel->errors()));
                }
            }
            
            // Sync status and expected delivery details to main booking
            $bookingUpdates = [];
            if (!empty($postData['status'])) {
                $bookingUpdates['status'] = $postData['status'];
            }
            if (isset($postData['expected_delivery_date'])) {
                $bookingUpdates['expected_delivery_date'] = empty($postData['expected_delivery_date']) ? null : $postData['expected_delivery_date'];
            }
            if (isset($postData['expected_delivery_time'])) {
                $bookingUpdates['expected_delivery_time'] = empty($postData['expected_delivery_time']) ? null : $postData['expected_delivery_time'];
            }
            
            if (!empty($bookingUpdates) && !empty($postData['booking_id'])) {
                $this->bookingModel->update($postData['booking_id'], $bookingUpdates);
            }
            
            session_write_close();
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => $isUpdate ? 'Tracking updated successfully' : 'Tracking added successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Tracking saveUpdate error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            $msg = $e->getMessage();
            $isSystemError = ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) ||
                             ($e instanceof \mysqli_sql_exception) ||
                             (strpos($msg, 'SQL') !== false) ||
                             (strpos($msg, 'database') !== false) ||
                             (strpos($msg, 'query') !== false) ||
                             (strpos($msg, 'Connection') !== false);
                             
            $userMessage = $isSystemError ? 'A secure database or system error occurred. Technical logs have been updated safely.' : $msg;
            return $this->response->setJSON(['status' => 'error', 'message' => $userMessage]);
        }
    }

    public function deleteUpdate($id)
    {
        // Find the record first to get the booking_id
        $trackingRecord = $this->trackingModel->find($id);
        if (!$trackingRecord) {
            session_write_close();
            return $this->response->setJSON(['status' => 'error', 'message' => 'Record not found']);
        }

        $bookingId = $trackingRecord['booking_id'] ?? null;

        // Pass 'true' as the second parameter to force a hard delete from the database
        // instead of a soft delete (which only updates the deleted_at column).
        if ($this->trackingModel->delete($id, true)) {
            if ($bookingId) {
                // Find the latest active tracking event remaining for this booking
                $latestEvent = $this->trackingModel->where('booking_id', $bookingId)
                                                   ->orderBy('event_date', 'DESC')
                                                   ->orderBy('event_time', 'DESC')
                                                   ->orderBy('id', 'DESC') // Serial ID serial check
                                                   ->first();
                
                if ($latestEvent && !empty($latestEvent['status'])) {
                    // Update main booking status to match the latest remaining tracking event
                    $this->bookingModel->update($bookingId, ['status' => $latestEvent['status']]);
                } else {
                    // If no tracking history events are left, revert status back to 'Billed'
                    $this->bookingModel->update($bookingId, ['status' => 'Billed']);
                }
            }

            session_write_close();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Record deleted successfully']);
        }
        session_write_close();
        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete record']);
    }

    public function trackByAwb($searchVal)
    {
        // Public read-only tracking endpoint: allow cross-origin GET only.
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }

        try {
            $searchVal = trim(urldecode($searchVal));
            if (empty($searchVal)) {
                session_write_close();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Please enter a valid search value.'
                ]);
            }

            $db = \Config\Database::connect();
            
            // 1. Search by AWB no in bookings table
            $booking = $db->table('bookings')
                          ->select('bookings.*')
                          ->where('bookings.awb_no', $searchVal)
                          ->get()->getRowArray();

            if (!$booking) {
                // 2. Search by Docket no in shipment_items
                $shipItem = $db->table('shipment_items')
                               ->where('docket_no', $searchVal)
                               ->get()->getRowArray();
                if ($shipItem) {
                    $booking = $db->table('bookings')
                                  ->where('id', $shipItem['booking_id'])
                                  ->get()->getRowArray();
                }
            }

            if (!$booking) {
                session_write_close();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No tracking records found for AWB/Docket: ' . esc($searchVal)
                ]);
            }

            // Fetch shipment items details (consignee and eway bill / forwarding no)
            $shipDetails = $db->table('shipment_items')
                              ->select('GROUP_CONCAT(DISTINCT docket_no SEPARATOR ", ") AS dockets,
                                        GROUP_CONCAT(DISTINCT customer_name SEPARATOR ", ") AS customers,
                                        GROUP_CONCAT(DISTINCT consignee SEPARATOR ", ") AS consignees,
                                        GROUP_CONCAT(DISTINCT eway_bill_no SEPARATOR ", ") AS eway_bills')
                              ->where('booking_id', $booking['id'])
                              ->get()->getRowArray();

            // Fetch tracking history
            $history = $db->table('tracking_history')
                          ->where('booking_id', $booking['id'])
                          ->orderBy('event_date', 'DESC')
                          ->orderBy('event_time', 'DESC')
                          ->get()->getResultArray();

            $deliveryDate = '-';
            $deliveryTime = '-';
            $receiverName = '-';

            foreach ($history as $event) {
                if (isset($event['status']) && stripos($event['status'], 'Delivered') !== false) {
                    $deliveryDate = $event['event_date'] ?? '-';
                    $deliveryTime = $event['event_time'] ?? '-';
                    break;
                }
            }

            foreach ($history as $event) {
                if (!empty($event['receiver_name']) && trim($event['receiver_name']) !== '-') {
                    $receiverName = trim($event['receiver_name']);
                    break;
                }
                if (isset($event['status']) && stripos($event['status'], 'Delivered') !== false && !empty($event['remarks']) && trim($event['remarks']) !== '-') {
                    $receiverName = trim($event['remarks']);
                    break;
                }
            }

            $formattedHistory = [];
            foreach ($history as $event) {
                $formattedHistory[] = [
                    'date'          => $event['event_date'] ?? '-',
                    'time'          => $event['event_time'] ?? '-',
                    'location'      => $event['current_location'] ?? '-',
                    'activity'      => $event['status'] ?? '-',
                    'remarks'       => $event['remarks'] ?? '-',
                    'receiver_name' => $event['receiver_name'] ?? '',
                ];
            }

            $latestRemark = '-';
            if (!empty($history)) {
                $latestRemark = !empty($history[0]['remarks']) ? $history[0]['remarks'] : ($booking['status'] ?? 'Billed');
            } else {
                $latestRemark = $booking['status'] ?? 'Billed';
            }

            $formattedBooking = [
                'awb_no'         => $booking['awb_no'] ?? '-',
                'current_status' => $booking['status'] ?? 'Billed',
                'latest_remark'  => $latestRemark,
                'booking_date'   => $booking['booking_date'] ?? '-',
                'consignor_name' => !empty($shipDetails['customers']) ? $shipDetails['customers'] : '-',
                'consignee_name' => !empty($shipDetails['consignees']) ? $shipDetails['consignees'] : '-',
                'origin'         => $booking['origin'] ?? '-',
                'destination'    => $booking['destination'] ?? '-',
                'total_pieces'   => $booking['total_pieces'] ?? '0',
                'delivery_date'  => $deliveryDate,
                'delivery_time'  => $deliveryTime,
                'receiver_name'  => $receiverName,
                'forwarding_no'  => !empty($shipDetails['dockets']) ? $shipDetails['dockets'] : '-',
                'expected_delivery_date' => $booking['expected_delivery_date'] ?? '-',
                'expected_delivery_time' => $booking['expected_delivery_time'] ? substr($booking['expected_delivery_time'], 0, 5) : '-',
            ];

            session_write_close();
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => [
                    'booking' => $formattedBooking,
                    'history' => $formattedHistory
                ]
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[trackByAwb Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            session_write_close();
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'An unexpected server error occurred. Please try again later.'
            ]);
        }
    }
}
