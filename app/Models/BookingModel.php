<?php
namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'awb_no', 'company_id', 'booking_date', 'origin', 'destination', 
        'mode_transport', 'material_type', 'material_details', 
        'material_category', 'status', 'transporter_name', 'transporter_mobile', 
        'driver_name', 'driver_mobile', 'driver_license_no', 
        'vehicle_no', 'total_pieces', 'total_weight', 'flight_number', 'airlines', 
        'volumetric_formula', 'gst_applied', 'payment_type', 'narration', 'created_by'
    ];
    protected $useTimestamps = true;

    public function searchByCompany($companyId, $searchValue)
    {
        return $this->select('bookings.*, companies.name as company_name, 
           COALESCE(SUM(shipment_items.final_chargeable_weight), 0) as total_chargeable_weight')
        ->join('companies', 'companies.id = bookings.company_id')
        ->join('shipment_items', 'shipment_items.booking_id = bookings.id', 'left')
        ->where('bookings.company_id', $companyId)
        ->groupStart()
        ->like('bookings.awb_no', $searchValue)
        ->orLike('shipment_items.docket_no', $searchValue)
        ->groupEnd()
        ->groupBy('bookings.id')
        ->orderBy('bookings.booking_date', 'DESC')
        ->findAll();
    }

    public function getFullBooking($id)
    {
        return $this->select('bookings.*, companies.name as company_name, users.username as created_by_name')
        ->join('companies', 'companies.id = bookings.company_id')
        ->join('users', 'users.id = bookings.created_by')
        ->find($id);
    }


    public function getCompanyStats($companyId)
    {
        $totalBookings = $this->where('company_id', $companyId)->countAllResults();

        $db = \Config\Database::connect();
        $totalShipments = $db->table('shipment_items si')
        ->join('bookings b', 'b.id = si.booking_id')
        ->where('b.company_id', $companyId)
        ->countAllResults();

        $totalCharges = $db->table('sales_charges sc')
        ->join('bookings b', 'b.id = sc.booking_id')
        ->where('b.company_id', $companyId)
        ->select('SUM((rate * weight) + ddc + ssc + btc + flc + doc + inbound_tsp + outbound_tsp + tcp + utility_charges + xray_charges + ado + awb_fees_agent + awb_fees_carrier + admin_charges + delivery_order_charges + inbound_handling + inbound_storage + outbound_storage + misc_charges) AS total_amount', false)
        ->get()
        ->getRowArray()['total_amount'] ?? 0;

        return [
            'total_bookings' => $totalBookings,
            'total_shipments' => $totalShipments,
            'total_charges' => '₹' . number_format($totalCharges ?: 0, 0),  // ✅ Shows real ₹ amount!
            'status' => 'Active'
        ];
    }

   public function getCompanyBookings($companyId, $limit = 5)
   {
    return $this->where('company_id', $companyId)
    ->orderBy('id', 'DESC')
    ->limit($limit)
    ->findAll();
   }


}