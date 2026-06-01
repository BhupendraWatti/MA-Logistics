<?php

namespace App\Models;

use CodeIgniter\Model;

class DocketMasterModel extends Model
{
    protected $table            = 'docket_master';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'docket_no', 'company_id', 'booking_id', 'shipment_item_id'
    ];
    protected $useTimestamps    = true;
}
