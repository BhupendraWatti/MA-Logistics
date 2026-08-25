<?php
namespace App\Models;

use CodeIgniter\Model;

class ShipmentItemModel extends Model
{
    protected $table = 'shipment_items';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    
    protected $allowedFields = [
        'booking_id', 'customer_name', 'bill_to', 'consignee', 'payment_type', 'material_category', 'docket_no',
        'part_no', 'contents', 'part_qty', 'invoice_no', 'invoice_date', 'actual_weight', 'length',
        'width', 'height', 'volumetric_weight', 'calculated_chargeable_weight', 'final_chargeable_weight', 'pieces', 
        'eway_bill_no', 'eway_bill_date', 'rate', 'delivery_charges',
        'docket_charges', 'pickup_charges', 'fuel_surcharge', 'fov_charges',
        'handling_charges', 'service_charges', 'misc_charges', 'misc_charges_name', 'custom_charges', 'created_at', 'updated_at'
    ];

    protected $validationRules = [
        'customer_name' => 'required|min_length[2]',
        'bill_to' => 'required|min_length[2]',
        'consignee' => 'required|min_length[2]',
        'actual_weight' => 'required|greater_than_equal_to[0]',
        'length' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'width' => 'permit_empty|numeric|greater_than_equal_to[0]',
        'height' => 'permit_empty|numeric|greater_than_equal_to[0]',
    ];

    protected $chargeableWeightRules = [
        'final_chargeable_weight' => 'required|greater_than_equal_to[45]'
    ];

    public function calculateChargeableWeight($actual_weight, $length, $width, $height)
    {
        $volumetric = ($length * $width * $height) / 6000;
        $chargeable = max($actual_weight, $volumetric);
        return $chargeable;
    }
}
