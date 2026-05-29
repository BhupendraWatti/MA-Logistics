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
        
        return $this->response->setJSON([
            'status' => 'success',
            'data' => $history,
            'booking' => $booking
        ]);
    }

    public function saveUpdate()
    {
        $postData = $this->request->getPost();
        
        // Handle file upload
        $proofImage = null;
        $file = $this->request->getFile('proof_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/pod', $newName);
            $proofImage = 'uploads/pod/' . $newName;
        }

        $data = [
            'booking_id'       => $postData['booking_id'],
            'awb_no'           => $postData['awb_no'],
            'current_location' => $postData['current_location'],
            'status'           => $postData['status'],
            'event_date'       => $postData['event_date'],
            'event_time'       => $postData['event_time'],
            'remarks'          => $postData['remarks'] ?? null,
        ];

        if ($proofImage) {
            $data['proof_image'] = $proofImage;
        }

        if (!empty($postData['id'])) {
            // Update
            $this->trackingModel->update($postData['id'], $data);
            
            // Sync latest status to main booking
            if (!empty($postData['status'])) {
               $this->bookingModel->update($postData['booking_id'], ['status' => $postData['status']]);
            }
            
            return $this->response->setJSON(['status' => 'success', 'message' => 'Tracking updated successfully']);
        } else {
            // Insert
            $this->trackingModel->insert($data);
            
            // Sync latest status to main booking
            if (!empty($postData['status'])) {
               $this->bookingModel->update($postData['booking_id'], ['status' => $postData['status']]);
            }
            
            return $this->response->setJSON(['status' => 'success', 'message' => 'Tracking added successfully']);
        }
    }

    public function deleteUpdate($id)
    {
        if ($this->trackingModel->delete($id)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Record deleted successfully']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to delete record']);
    }
}
