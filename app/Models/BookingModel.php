<?php
namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'awb_no', 'company_id', 'branch_id', 'booking_date', 'expected_delivery_date', 'expected_delivery_time', 'origin', 'destination', 
        'mode_transport', 'material_type', 'material_details', 
        'material_category', 'status', 'transporter_name', 'transporter_mobile', 
        'driver_name', 'driver_mobile', 'driver_license_no', 
        'vehicle_no', 'total_pieces', 'total_weight', 'flight_number', 'airlines', 
        'volumetric_formula', 'gst_applied', 'payment_type', 'narration', 'created_by',
        'gstin', 'pan', 'sac_code', 'cgst_rate', 'sgst_rate', 'igst_rate', 'signature_path'
    ];
    protected $useTimestamps = true;

    public function searchByCompany($companyId, $searchValue)
    {
        $query = $this->select('bookings.*, companies.name as company_name, 
           COALESCE(SUM(shipment_items.final_chargeable_weight), 0) as total_chargeable_weight')
        ->join('companies', 'companies.id = bookings.company_id')
        ->join('shipment_items', 'shipment_items.booking_id = bookings.id', 'left')
        ->where('bookings.company_id', $companyId);

        $userRole = session()->get('role');
        if ($userRole !== 'admin') {
            $branchId = session()->get('branch_id') ?? 1;
            $query = $query->where('bookings.branch_id', $branchId);
        }

        return $query->groupStart()
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
            'total_charges' => '₹' . number_format((float)($totalCharges ?: 0), 0),  // ✅ Shows real ₹ amount!
            'status' => 'Active'
        ];
    }

   public function getCompanyBookings($companyId, $limit = 5)
   {
       $query = $this->where('company_id', $companyId);

       $userRole = session()->get('role');
       if ($userRole !== 'admin') {
           $branchId = session()->get('branch_id') ?? 1;
           $query = $query->where('branch_id', $branchId);
       }

       return $query->orderBy('id', 'DESC')
                    ->limit($limit)
                    ->findAll();
   }

   public function getLatestAuditActions(array $bookingIds): array
   {
       if (empty($bookingIds)) {
           return [];
       }

       $db = \Config\Database::connect();
       $logs = $db->table('audit_logs al')
                  ->select('al.record_id, al.new_value as action, al.created_at, u.username, u.id as user_id')
                  ->join('users u', 'u.id = al.changed_by', 'left')
                  ->where('al.table_name', 'bookings')
                  ->where('al.field_name', 'action')
                  ->whereIn('al.record_id', $bookingIds)
                  ->orderBy('al.id', 'DESC')
                  ->get()->getResultArray();

       $bookingLogs = [];
       foreach ($logs as $log) {
           $bid = $log['record_id'];
           if (!isset($bookingLogs[$bid])) {
               $bookingLogs[$bid] = [
                   'action'     => $log['action'],
                   'username'   => $log['username'] ?: 'Unknown',
                   'user_id'    => $log['user_id'] ?: '-',
                   'created_at' => date('d-M-Y H:i', strtotime($log['created_at']))
               ];
           }
       }

       return $bookingLogs;
   }
}