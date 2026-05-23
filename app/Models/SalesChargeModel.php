<?php
namespace App\Models;

use CodeIgniter\Model;

class SalesChargeModel extends Model
{
    protected $table = 'sales_charges';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    
    protected $allowedFields = [
        'booking_id', 'flight_number', 'airlines', 'rate', 'weight', 'ddc',
        'ssc', 'btc', 'flc', 'doc', 'inbound_tsp', 'outbound_tsp', 'tcp',
        'utility_charges', 'xray_charges', 'ado', 'awb_fees_agent',
        'awb_fees_carrier', 'admin_charges', 'delivery_order_charges',
        'inbound_handling', 'inbound_storage', 'outbound_storage', 'misc_charges',
        'total_amount', 'created_at', 'updated_at'
    ];
}