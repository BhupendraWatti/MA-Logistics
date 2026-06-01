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
            ];

            if ($proofImage) {
                $data['proof_image'] = $proofImage;
            }

            if (!empty($postData['id'])) {
                // Update
                if (!$this->trackingModel->update($postData['id'], $data)) {
                    throw new \Exception(implode(', ', $this->trackingModel->errors()));
                }
                
                // Sync latest status to main booking
                if (!empty($postData['status'])) {
                   $this->bookingModel->update($postData['booking_id'], ['status' => $postData['status']]);
                }
                
                // does not attempt a session DB write and cause a 500 status override.
                session_write_close();
                return $this->response->setJSON(['status' => 'success', 'message' => 'Tracking updated successfully']);
            } else {
                // Insert
                if (!$this->trackingModel->insert($data)) {
                    throw new \Exception(implode(', ', $this->trackingModel->errors()));
                }
                
                // Sync latest status to main booking
                if (!empty($postData['status'])) {
                   $this->bookingModel->update($postData['booking_id'], ['status' => $postData['status']]);
                }
                
                // CRITICAL: close session before returning JSON
                session_write_close();
                return $this->response->setJSON(['status' => 'success', 'message' => 'Tracking added successfully']);
            }
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
}
