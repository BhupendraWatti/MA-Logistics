<?php
namespace App\Controllers;
use CodeIgniter\Controller;

class TestDb2 extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        $companyId = 1;
        
        try {
            $recent_bookings = $db->table('bookings b')
                ->select("b.id, b.awb_no, b.origin, b.destination, b.status, b.total_pieces, 
                          (SELECT customer_name FROM shipment_items WHERE booking_id = b.id LIMIT 1) as customer_name,
                          (SELECT SUM(final_chargeable_weight) FROM shipment_items WHERE booking_id = b.id) as total_weight")
                ->where('b.company_id', $companyId)
                ->orderBy('b.id', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
                
            echo "Query SUCCESS, rows: " . count($recent_bookings) . "\n";
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}
