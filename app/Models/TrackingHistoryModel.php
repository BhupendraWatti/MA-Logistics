<?php

namespace App\Models;

use CodeIgniter\Model;

class TrackingHistoryModel extends Model
{
    protected $table            = 'tracking_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'booking_id',
        'awb_no',
        'current_location',
        'status',
        'event_date',
        'event_time',
        'remarks',
        'proof_image'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'booking_id'       => 'required|numeric',
        'awb_no'           => 'required|max_length[100]',
        'current_location' => 'required|max_length[255]',
        'status'           => 'required|max_length[100]',
        'event_date'       => 'required|valid_date',
        'event_time'       => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
