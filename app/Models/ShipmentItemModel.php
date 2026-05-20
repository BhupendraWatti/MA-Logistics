<?php
namespace App\Models;

use CodeIgniter\Model;

class ShipmentItemModel extends Model
{
    protected $table = 'shipment_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = false;
    
    protected $allowedFields = [
        'booking_id', 'customer_name', 'bill_to', 'consignee', 'docket_no',
        'part_no', 'invoice_no', 'invoice_date', 'actual_weight', 'length',
        'width', 'height', 'volumetric_weight', 'chargeable_weight', 'pieces', 
        'eway_bill_no', 'eway_bill_date', 'rate', 'delivery_charges',
        'docket_charges', 'pickup_charges', 'fuel_surcharge', 'fov_charges',
        'handling_charges', 'service_charges'
    ];

    protected $validationRules = [
        'customer_name' => 'required|min_length[2]',
        'bill_to' => 'required|min_length[2]',
        'consignee' => 'required|min_length[2]',
        'actual_weight' => 'required|greater_than[0]',
        'length' => 'permit_empty|numeric|greater_than[0]',
        'width' => 'permit_empty|numeric|greater_than[0]',
        'height' => 'permit_empty|numeric|greater_than[0]',
    ];

    protected $chargeableWeightRules = [
        'chargeable_weight' => 'required|greater_than_equal_to[45]'
    ];

    public function calculateChargeableWeight($actual_weight, $length, $width, $height)
    {
        $volumetric = ($length * $width * $height) / 5000;
        $chargeable = max($actual_weight, $volumetric);
        return max($chargeable, 45); // Minimum 45 KG
    }
}