<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        // Disable foreign key checks for truncation
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate tables to ensure a fresh start
        $db->table('shipment_items')->truncate();
        $db->table('sales_charges')->truncate();
        $db->table('bookings')->truncate();
        $db->table('customers')->truncate();
        $db->table('transporters')->truncate();
        $db->table('drivers')->truncate();
        $db->table('airlines')->truncate();
        $db->table('lookup_values')->truncate();
        $db->table('companies')->truncate();
        
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 1. Insert Companies
        $companies = [
            [
                'id' => 1,
                'name' => 'MA Logistics Pvt Ltd',
                'address' => '123 Logistics Park, Andheri East, Mumbai, 400069',
                'email' => 'contact@malogistics.com',
                'mobile' => '9876543210',
                'gstin' => '27AADCM1234E1Z5',
                'pan' => 'AADCM1234E',
                'sac_code' => '996791',
                'cgst_rate' => 9.00,
                'sgst_rate' => 9.00,
                'igst_rate' => 0.00,
                'terms_conditions' => '1. All payments must be made within 30 days. 2. Goods are transported at owner\'s risk.'
            ],
            [
                'id' => 2,
                'name' => 'MRL Express Delivery',
                'address' => '45 Transport Hub, Vashi, Navi Mumbai, 400703',
                'email' => 'info@mrlexpress.com',
                'mobile' => '9988776655',
                'gstin' => '27AAECM5678R1Z2',
                'pan' => 'AAECM5678R',
                'sac_code' => '996791',
                'cgst_rate' => 9.00,
                'sgst_rate' => 9.00,
                'igst_rate' => 0.00,
                'terms_conditions' => '1. Standard terms apply. 2. Demurrage charges after 48 hours.'
            ]
        ];
        $db->table('companies')->insertBatch($companies);

        // 2. Insert Customers (Shippers)
        $customers = [
            [
                'company_id' => 1,
                'name' => 'Acme Pharmaceuticals',
                'code' => 'CUST001',
                'email' => 'logistics@acmepharma.com',
                'pan' => 'ABCDE1234F',
                'pincode' => '400001',
                'city' => 'Mumbai',
                'bill_to' => 'Acme Pharma HQ, Nariman Point, Mumbai',
                'consignee' => 'Acme Warehouse, Bhiwandi',
                'payment_type' => 'Credit',
                'narration' => 'Monthly billing cycle, PO required',
                'person1_name' => 'Rajesh Kumar',
                'person1_phone' => '9123456789',
                'person1_email' => 'rajesh@acmepharma.com',
                'is_active' => 1
            ],
            [
                'company_id' => 1,
                'name' => 'Global Tech Supplies',
                'code' => 'CUST002',
                'email' => 'dispatch@globaltech.com',
                'pan' => 'ZYXWV9876G',
                'pincode' => '411001',
                'city' => 'Pune',
                'bill_to' => 'Global Tech HQ, Hinjewadi, Pune',
                'consignee' => 'Regional Office, Delhi',
                'payment_type' => 'To Pay',
                'narration' => 'Payment strictly on delivery',
                'person1_name' => 'Sita Ram',
                'person1_phone' => '9876512345',
                'person1_email' => 'sita@globaltech.com',
                'is_active' => 1
            ],
            [
                'company_id' => 2,
                'name' => 'Prime Retailers',
                'code' => 'PRIME01',
                'email' => 'supply@primeretail.com',
                'pan' => 'PRIME1234R',
                'pincode' => '110001',
                'city' => 'Delhi',
                'bill_to' => 'Prime Mall, Connaught Place, Delhi',
                'consignee' => 'Prime Store, Gurgaon',
                'payment_type' => 'Cash',
                'narration' => '',
                'person1_name' => 'Amit Singh',
                'person1_phone' => '9998887776',
                'person1_email' => 'amit@primeretail.com',
                'is_active' => 1
            ]
        ];
        $db->table('customers')->insertBatch($customers);

        // 3. Insert Transporters
        $transporters = [
            ['company_id' => 1, 'name' => 'FastRoad Carriers', 'mobile' => '9876543211', 'is_active' => 1],
            ['company_id' => 1, 'name' => 'Blue Sky Transport', 'mobile' => '9876543212', 'is_active' => 1],
            ['company_id' => 2, 'name' => 'Reliable Movers', 'mobile' => '9876543213', 'is_active' => 1],
        ];
        $db->table('transporters')->insertBatch($transporters);

        // 4. Insert Drivers
        $drivers = [
            ['company_id' => 1, 'name' => 'Ramesh Driver', 'mobile' => '8887776665', 'vehicle_no' => 'MH-04-AB-1234', 'license_no' => 'MH0420101234567', 'is_active' => 1],
            ['company_id' => 1, 'name' => 'Suresh Driver', 'mobile' => '8887776666', 'vehicle_no' => 'MH-43-CD-5678', 'license_no' => 'MH4320159876543', 'is_active' => 1],
            ['company_id' => 2, 'name' => 'Mahesh Driver', 'mobile' => '8887776667', 'vehicle_no' => 'DL-01-XY-9999', 'license_no' => 'DL0120201122334', 'is_active' => 1],
        ];
        $db->table('drivers')->insertBatch($drivers);

        // 5. Insert Airlines
        $airlines = [
            ['company_id' => 1, 'name' => 'Air India', 'code' => 'AI', 'is_active' => 1],
            ['company_id' => 1, 'name' => 'IndiGo', 'code' => '6E', 'is_active' => 1],
            ['company_id' => 2, 'name' => 'SpiceJet', 'code' => 'SG', 'is_active' => 1],
        ];
        $db->table('airlines')->insertBatch($airlines);

        // 6. Insert Lookup Values
        $lookups = [
            ['company_id' => 1, 'type' => 'origin', 'value' => 'BOM (Mumbai)', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'origin', 'value' => 'DEL (Delhi)', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'destination', 'value' => 'BLR (Bangalore)', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'destination', 'value' => 'CCU (Kolkata)', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'mode', 'value' => 'Air', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'mode', 'value' => 'Surface', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'material_category', 'value' => 'Pharma', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'material_category', 'value' => 'Electronics', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'material_type', 'value' => 'Cold Chain', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'material_type', 'value' => 'General Cargo', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'payment_type', 'value' => 'To Pay', 'sort_order' => 1],
            ['company_id' => 1, 'type' => 'payment_type', 'value' => 'Credit', 'sort_order' => 2],
            ['company_id' => 1, 'type' => 'payment_type', 'value' => 'Cash', 'sort_order' => 3],
        ];
        $db->table('lookup_values')->insertBatch($lookups);

        // 7. Insert Bookings
        // Assuming user ID 1 is the admin
        $userId = 1; 

        $bookings = [
            [
                'id' => 1,
                'awb_no' => 'MA-10001',
                'company_id' => 1,
                'booking_date' => date('Y-m-d H:i:s'),
                'origin' => 'BOM (Mumbai)',
                'destination' => 'DEL (Delhi)',
                'mode_transport' => 'Air',
                'material_type' => 'Cold Chain',
                'material_category' => 'Pharma',
                'material_details' => 'Vaccines',
                'status' => 'Booked',
                'transporter_name' => 'FastRoad Carriers',
                'transporter_mobile' => '9876543211',
                'driver_name' => 'Ramesh Driver',
                'driver_mobile' => '8887776665',
                'driver_license_no' => 'MH0420101234567',
                'vehicle_no' => 'MH-04-AB-1234',
                'total_pieces' => 2,
                'total_weight' => 50.00,
                'flight_number' => 'AI-863',
                'airlines' => 'Air India',
                'volumetric_formula' => 6000,
                'gst_applied' => 1,
                'payment_type' => 'Credit',
                'narration' => 'Handle with care',
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'awb_no' => 'MA-10002',
                'company_id' => 1,
                'booking_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'origin' => 'DEL (Delhi)',
                'destination' => 'BLR (Bangalore)',
                'mode_transport' => 'Surface',
                'material_type' => 'General Cargo',
                'material_category' => 'Electronics',
                'material_details' => 'Laptops and Accessories',
                'status' => 'In-Transit',
                'transporter_name' => 'Blue Sky Transport',
                'transporter_mobile' => '9876543212',
                'driver_name' => 'Suresh Driver',
                'driver_mobile' => '8887776666',
                'driver_license_no' => 'MH4320159876543',
                'vehicle_no' => 'MH-43-CD-5678',
                'total_pieces' => 5,
                'total_weight' => 120.50,
                'flight_number' => '',
                'airlines' => '',
                'volumetric_formula' => 6000,
                'gst_applied' => 1,
                'payment_type' => 'To Pay',
                'narration' => '',
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
        $db->table('bookings')->insertBatch($bookings);

        // 8. Insert Shipment Items
        $shipments = [
            [
                'booking_id' => 1,
                'customer_name' => 'Acme Pharmaceuticals',
                'bill_to' => 'Acme Pharma HQ, Nariman Point, Mumbai',
                'consignee' => 'Apollo Hospital, Delhi',
                'docket_no' => 'DOC1001',
                'part_no' => 'P-123',
                'invoice_no' => 'INV-2026-001',
                'invoice_date' => date('Y-m-d'),
                'actual_weight' => 20.00,
                'length' => 50,
                'width' => 40,
                'height' => 30,
                'volumetric_weight' => 10.00, // 50*40*30 / 6000 = 10
                'calculated_chargeable_weight' => 20.00,
                'final_chargeable_weight' => 20.00,
                'pieces' => 1,
                'eway_bill_no' => 'EWB1234567890',
                'eway_bill_date' => date('Y-m-d'),
                'rate' => 50.00,
                'delivery_charges' => 500.00,
                'docket_charges' => 100.00,
                'pickup_charges' => 200.00,
                'fuel_surcharge' => 150.00,
                'fov_charges' => 0.00,
                'handling_charges' => 50.00,
                'service_charges' => 0.00,
            ],
            [
                'booking_id' => 1,
                'customer_name' => 'Acme Pharmaceuticals',
                'bill_to' => 'Acme Pharma HQ, Nariman Point, Mumbai',
                'consignee' => 'Max Hospital, Delhi',
                'docket_no' => 'DOC1002',
                'part_no' => 'P-124',
                'invoice_no' => 'INV-2026-002',
                'invoice_date' => date('Y-m-d'),
                'actual_weight' => 15.00,
                'length' => 60,
                'width' => 60,
                'height' => 50,
                'volumetric_weight' => 30.00, // 60*60*50 / 6000 = 30
                'calculated_chargeable_weight' => 30.00,
                'final_chargeable_weight' => 30.00,
                'pieces' => 1,
                'eway_bill_no' => 'EWB1234567891',
                'eway_bill_date' => date('Y-m-d'),
                'rate' => 50.00,
                'delivery_charges' => 500.00,
                'docket_charges' => 100.00,
                'pickup_charges' => 200.00,
                'fuel_surcharge' => 150.00,
                'fov_charges' => 0.00,
                'handling_charges' => 50.00,
                'service_charges' => 0.00,
            ],
            [
                'booking_id' => 2,
                'customer_name' => 'Global Tech Supplies',
                'bill_to' => 'Global Tech HQ, Pune',
                'consignee' => 'Tech Hub, Bangalore',
                'docket_no' => 'DOC1003',
                'part_no' => 'LT-900',
                'invoice_no' => 'INV-2026-099',
                'invoice_date' => date('Y-m-d', strtotime('-1 day')),
                'actual_weight' => 120.50,
                'length' => 100,
                'width' => 100,
                'height' => 50,
                'volumetric_weight' => 83.33, // 100*100*50 / 6000 = 83.33
                'calculated_chargeable_weight' => 120.50,
                'final_chargeable_weight' => 120.50,
                'pieces' => 5,
                'eway_bill_no' => 'EWB9876543210',
                'eway_bill_date' => date('Y-m-d', strtotime('-1 day')),
                'rate' => 25.00,
                'delivery_charges' => 1500.00,
                'docket_charges' => 200.00,
                'pickup_charges' => 500.00,
                'fuel_surcharge' => 300.00,
                'fov_charges' => 100.00,
                'handling_charges' => 250.00,
                'service_charges' => 0.00,
            ]
        ];
        $db->table('shipment_items')->insertBatch($shipments);

        // 9. Insert Sales Charges
        $sales = [
            [
                'booking_id' => 1,
                'flight_number' => 'AI-863',
                'airlines' => 'Air India',
                'rate' => 45.00,
                'weight' => 50.00, // Total chargeable weight of booking 1
                'ddc' => 100.00,
                'ssc' => 50.00,
                'btc' => 0.00,
                'flc' => 200.00,
                'doc' => 50.00,
                'inbound_tsp' => 0.00,
                'outbound_tsp' => 100.00,
                'tcp' => 0.00,
                'utility_charges' => 25.00,
                'xray_charges' => 150.00,
                'ado' => 0.00,
                'awb_fees_agent' => 100.00,
                'awb_fees_carrier' => 50.00,
                'admin_charges' => 20.00,
                'delivery_order_charges' => 0.00,
                'inbound_handling' => 0.00,
                'inbound_storage' => 0.00,
                'outbound_storage' => 0.00,
                'misc_charges' => 0.00,
                'total_amount' => (45.00 * 50.00) + 100 + 50 + 200 + 50 + 100 + 25 + 150 + 100 + 50 + 20,
            ],
            [
                'booking_id' => 2,
                'flight_number' => '',
                'airlines' => '',
                'rate' => 20.00,
                'weight' => 120.50,
                'ddc' => 200.00,
                'ssc' => 0.00,
                'btc' => 0.00,
                'flc' => 0.00,
                'doc' => 100.00,
                'inbound_tsp' => 0.00,
                'outbound_tsp' => 0.00,
                'tcp' => 0.00,
                'utility_charges' => 50.00,
                'xray_charges' => 0.00,
                'ado' => 0.00,
                'awb_fees_agent' => 0.00,
                'awb_fees_carrier' => 0.00,
                'admin_charges' => 50.00,
                'delivery_order_charges' => 200.00,
                'inbound_handling' => 0.00,
                'inbound_storage' => 0.00,
                'outbound_storage' => 0.00,
                'misc_charges' => 100.00,
                'total_amount' => (20.00 * 120.50) + 200 + 100 + 50 + 50 + 200 + 100,
            ]
        ];
        $db->table('sales_charges')->insertBatch($sales);
    }
}
