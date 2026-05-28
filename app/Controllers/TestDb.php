<?php
namespace App\Controllers;
use CodeIgniter\Controller;
class TestDb extends Controller
{
    public function index()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('bookings b');
        $builder->select("
            b.id, b.awb_no, b.booking_date, b.origin, b.destination, b.status, b.total_pieces,
            COALESCE(si.total_weight, 0) AS total_weight,
            COALESCE(sc.total_amount, 0) AS total_amount
        ");
        $builder->join("(SELECT booking_id, SUM(final_chargeable_weight) AS total_weight FROM shipment_items GROUP BY booking_id) si", "si.booking_id = b.id", "left");
        $builder->join("(SELECT booking_id, ((COALESCE(rate,0) * COALESCE(weight,0)) + COALESCE(ddc,0) + COALESCE(ssc,0) + COALESCE(btc,0) + COALESCE(flc,0) + COALESCE(doc,0) + COALESCE(inbound_tsp,0) + COALESCE(outbound_tsp,0) + COALESCE(tcp,0) + COALESCE(utility_charges,0) + COALESCE(xray_charges,0) + COALESCE(ado,0) + COALESCE(awb_fees_agent,0) + COALESCE(awb_fees_carrier,0) + COALESCE(admin_charges,0) + COALESCE(delivery_order_charges,0) + COALESCE(inbound_handling,0) + COALESCE(inbound_storage,0) + COALESCE(outbound_storage,0) + COALESCE(misc_charges,0)) AS total_amount FROM sales_charges) sc", "sc.booking_id = b.id", "left");
        
        $builder->where('b.company_id', 1);

        $totalRecords = $builder->countAllResults(false);
        echo "Total: $totalRecords\n";

        $searchValue = 'search me bhi';
        if (!empty($searchValue)) {
            $builder->groupStart()
                    ->like('b.awb_no', $searchValue)
                    ->orLike('b.origin', $searchValue)
                    ->orLike('b.destination', $searchValue)
                    ->orLike('b.status', $searchValue)
                    ->groupEnd();
        }

        $filteredRecords = $builder->countAllResults(false);
        echo "Filtered: $filteredRecords\n";

        $builder->limit(10, 0);
        $builder->orderBy('b.id', 'desc');

        try {
            $data = $builder->get()->getResultArray();
            echo "Data count: " . count($data) . "\n";
            if (count($data) > 0) {
                echo "First row keys: " . implode(", ", array_keys($data[0])) . "\n";
                // Let's check if total_weight exists
                echo "First row total_weight: " . ($data[0]['total_weight'] ?? 'MISSING') . "\n";
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }
    }
}
