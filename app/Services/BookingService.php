<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShipmentItemModel;
use App\Models\SalesChargeModel;
use Exception;

class BookingService
{
    protected $bookingModel;
    protected $shipmentModel;
    protected $salesModel;
    protected $db;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->shipmentModel = new ShipmentItemModel();
        $this->salesModel = new SalesChargeModel();
        $this->db = \Config\Database::connect();
        $this->db->transException(true); // Automatically rollback transactions on Exception
    }

    public function createBooking(array $postData, int $userId, int $companyId)
    {
        $this->validateBasicData($postData);

        $this->db->transStart();

        $bookingData = [
            'awb_no' => $postData['awb_no'] ?? '',
            'company_id' => $companyId,
            'booking_date' => $postData['booking_date'] ?? null,
            'origin' => $postData['origin'] ?? '',
            'destination' => $postData['destination'] ?? '',
            'mode_transport' => $postData['mode_transport'] ?? '',
            'material_type' => $postData['material_type'] ?? '',
            'material_details' => $postData['material_details'] ?? '',
            'material_category' => $postData['material_category'] ?? '',
            'status' => $postData['status'] ?? 'Draft',
            'transporter_name' => $postData['transporter_name'] ?? '',
            'transporter_mobile' => $postData['transporter_mobile'] ?? '',
            'driver_name' => $postData['driver_name'] ?? '',
            'driver_mobile' => $postData['driver_mobile'] ?? '',
            'driver_license_no' => $postData['driver_license_no'] ?? '',
            'vehicle_no' => $postData['vehicle_no'] ?? '',
            'total_pieces' => $postData['total_pieces'] ?? 1,
            'total_weight' => $postData['total_weight'] ?? 0,
            'volumetric_formula' => $postData['volumetric_formula'] ?? 6000,
            'gst_applied' => isset($postData['gst_applied']) ? 1 : 0,
            'payment_type' => $postData['payment_type'] ?? '',
            'narration' => $postData['narration'] ?? '',
            'flight_number' => $postData['flight_number'] ?? '',
            'airlines' => $postData['airlines'] ?? '',
            'created_by' => $userId
        ];

        if (!$this->bookingModel->insert($bookingData)) {
            throw new Exception('Failed to create booking: ' . implode(', ', $this->bookingModel->errors()));
        }

        $bookingId = $this->bookingModel->getInsertID();

        $this->processShipments($bookingId, $postData['items'] ?? []);
        $this->processSales($bookingId, $postData);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new Exception('Transaction failed to save booking data.');
        }

        return $bookingId;
    }

    public function updateBooking(int $id, array $postData, int $userId, int $companyId)
    {
        $this->validateBasicData($postData);


        $this->db->transStart();

        $bookingData = [
            'awb_no' => $postData['awb_no'] ?? '',
            'company_id' => $companyId,
            'booking_date' => $postData['booking_date'] ?? null,
            'origin' => $postData['origin'] ?? '',
            'destination' => $postData['destination'] ?? '',
            'mode_transport' => $postData['mode_transport'] ?? '',
            'material_type' => $postData['material_type'] ?? '',
            'material_details' => $postData['material_details'] ?? '',
            'material_category' => $postData['material_category'] ?? '',
            'status' => $postData['status'] ?? 'Draft',
            'transporter_name' => $postData['transporter_name'] ?? '',
            'transporter_mobile' => $postData['transporter_mobile'] ?? '',
            'driver_name' => $postData['driver_name'] ?? '',
            'driver_mobile' => $postData['driver_mobile'] ?? '',
            'driver_license_no' => $postData['driver_license_no'] ?? '',
            'vehicle_no' => $postData['vehicle_no'] ?? '',
            'total_pieces' => $postData['total_pieces'] ?? 1,
            'total_weight' => $postData['total_weight'] ?? 0,
            'volumetric_formula' => $postData['volumetric_formula'] ?? 6000,
            'gst_applied' => isset($postData['gst_applied']) ? 1 : 0,
            'payment_type' => $postData['payment_type'] ?? '',
            'narration' => $postData['narration'] ?? '',
            'flight_number' => $postData['flight_number'] ?? '',
            'airlines' => $postData['airlines'] ?? '',
            'created_by' => $userId
        ];

        $this->bookingModel->update($id, $bookingData);

        $existingShipments = $this->shipmentModel->where('booking_id', $id)->findAll();
        $existingIds = array_column($existingShipments, 'id');

        // --- NEW JSON MIGRATION BLOCK START ---
        if (!empty($postData['items_json'])) {
            $decoded = json_decode($postData['items_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format in shipment items data: ' . json_last_error_msg());
            }
            if (!is_array($decoded)) {
                throw new \Exception('Shipment items must be a valid list.');
            }
            $items = [];
            foreach ($decoded as $jsItem) {
                $items[] = [
                    'id' => $jsItem['id'] ?? '',
                    'customer_name' => $jsItem['customer'] ?? '',
                    'bill_to' => $jsItem['bill_to'] ?? '',
                    'consignee' => $jsItem['consignee'] ?? '',
                    'docket_no' => $jsItem['docket'] ?? '',
                    'invoice_no' => $jsItem['invoice_no'] ?? '',
                    'part_no' => $jsItem['contents'] ?? '',
                    'pieces' => $jsItem['pcs'] ?? 1,
                    'actual_weight' => $jsItem['act_wt'] ?? 0,
                    'length' => $jsItem['l'] ?? 0,
                    'width' => $jsItem['w'] ?? 0,
                    'height' => $jsItem['h'] ?? 0,
                    'volumetric_weight' => $jsItem['vol_wt'] ?? 0,
                    'calculated_chargeable_weight' => $jsItem['chg_wt'] ?? 0,
                    'chargeable_weight' => $jsItem['chg_wt'] ?? 0,
                    'eway_bill_no' => $jsItem['eway_no'] ?? '',
                    'eway_bill_date' => !empty($jsItem['eway_date']) ? $jsItem['eway_date'] : null,
                    'rate' => $jsItem['rate'] ?? 0,
                    'delivery_charges' => $jsItem['delivery_charges'] ?? 0,
                    'docket_charges' => $jsItem['docket_charges'] ?? 0,
                    'pickup_charges' => $jsItem['pickup_charges'] ?? 0,
                    'fuel_surcharge' => $jsItem['fuel_surcharge'] ?? 0,
                    'fov_charges' => $jsItem['fov_charges'] ?? 0,
                    'handling_charges' => $jsItem['handling_charges'] ?? 0,
                    'service_charges' => $jsItem['service_charges'] ?? 0
                ];
            }
        } else {
            $items = $postData['items'] ?? [];
        }
        // --- NEW JSON MIGRATION BLOCK END ---
        
        $submittedIds = [];

        foreach ($items as $item) {
            if (empty($item['customer_name'])) {
                throw new \Exception('Customer Name is required for all shipment items.');
            }
                $shipmentData = [
                    'booking_id' => $id,
                    'customer_name' => $item['customer_name'],
                    'bill_to' => $item['bill_to'] ?? '',
                    'consignee' => $item['consignee'] ?? '',
                    'docket_no' => $item['docket_no'] ?? '',
                    'part_no' => $item['part_no'] ?? '',
                    'invoice_no' => $item['invoice_no'] ?? '',
                    'invoice_date' => $item['invoice_date'] ?? null,
                    'actual_weight' => $this->validateNumeric($item['actual_weight'] ?? 0),
                    'length' => $this->validateNumeric($item['length'] ?? 0),
                    'width' => $this->validateNumeric($item['width'] ?? 0),
                    'height' => $this->validateNumeric($item['height'] ?? 0),
                    'volumetric_weight' => $this->validateNumeric($item['volumetric_weight'] ?? 0),
                    'calculated_chargeable_weight' => $this->validateNumeric($item['calculated_chargeable_weight'] ?? 0),
                    'final_chargeable_weight' => $this->validateNumeric($item['chargeable_weight'] ?? 0),
                    'pieces' => intval($item['pieces'] ?? 1),
                    'eway_bill_no' => $item['eway_bill_no'] ?? '',
                    'eway_bill_date' => $item['eway_bill_date'] ?? null,
                    'rate' => $this->validateNumeric($item['rate'] ?? 0),
                    'delivery_charges' => $this->validateNumeric($item['delivery_charges'] ?? 0),
                    'docket_charges' => $this->validateNumeric($item['docket_charges'] ?? 0),
                    'pickup_charges' => $this->validateNumeric($item['pickup_charges'] ?? 0),
                    'fuel_surcharge' => $this->validateNumeric($item['fuel_surcharge'] ?? 0),
                    'fov_charges' => $this->validateNumeric($item['fov_charges'] ?? 0),
                    'handling_charges' => $this->validateNumeric($item['handling_charges'] ?? 0),
                    'service_charges' => $this->validateNumeric($item['service_charges'] ?? 0)
                ];

                if (!empty($item['id']) && in_array((int)$item['id'], $existingIds)) {
                    $itemId = (int)$item['id'];
                    if (!$this->shipmentModel->update($itemId, $shipmentData)) {
                        throw new \Exception('Failed to update shipment item: ' . implode(', ', $this->shipmentModel->errors()));
                    }
                    $submittedIds[] = $itemId;   // int, matches $existingIds type
                    $recordId = $itemId;
                } else {
                    if (!$this->shipmentModel->insert($shipmentData)) {
                        throw new \Exception('Failed to insert shipment item: ' . implode(', ', $this->shipmentModel->errors()));
                    }
                    $recordId = $this->shipmentModel->getInsertID();
                    $submittedIds[] = $recordId; // also track newly inserted IDs
                }

                if ($shipmentData['final_chargeable_weight'] != $shipmentData['calculated_chargeable_weight']) {
                    $this->logAudit('shipment_items', $recordId, 'chargeable_weight_override', $shipmentData['calculated_chargeable_weight'], $shipmentData['final_chargeable_weight']);
                }
        }

        // array_diff now compares int vs int — safe
        $idsToDelete = array_diff($existingIds, $submittedIds);
        if (!empty($idsToDelete)) {
            $this->shipmentModel->whereIn('id', array_values($idsToDelete))->delete();
        }

        $salesData = [
            'booking_id' => $id,
            'flight_number' => $postData['flight_number'] ?? '',
            'airlines' => $postData['airlines'] ?? '',
            'rate' => $this->validateNumeric($postData['rate'] ?? 0),
            'weight' => $this->validateNumeric($postData['weight'] ?? 0),
            'ddc' => $this->validateNumeric($postData['ddc'] ?? 0),
            'ssc' => $this->validateNumeric($postData['ssc'] ?? 0),
            'btc' => $this->validateNumeric($postData['btc'] ?? 0),
            'flc' => $this->validateNumeric($postData['flc'] ?? 0),
            'doc' => $this->validateNumeric($postData['doc'] ?? 0),
            'inbound_tsp' => $this->validateNumeric($postData['inbound_tsp'] ?? 0),
            'outbound_tsp' => $this->validateNumeric($postData['outbound_tsp'] ?? 0),
            'tcp' => $this->validateNumeric($postData['tcp'] ?? 0),
            'utility_charges' => $this->validateNumeric($postData['utility_charges'] ?? 0),
            'xray_charges' => $this->validateNumeric($postData['xray_charges'] ?? 0),
            'ado' => $this->validateNumeric($postData['ado'] ?? 0),
            'awb_fees_agent' => $this->validateNumeric($postData['awb_fees_agent'] ?? 0),
            'awb_fees_carrier' => $this->validateNumeric($postData['awb_fees_carrier'] ?? 0),
            'admin_charges' => $this->validateNumeric($postData['admin_charges'] ?? 0),
            'delivery_order_charges' => $this->validateNumeric($postData['delivery_order_charges'] ?? 0),
            'inbound_handling' => $this->validateNumeric($postData['inbound_handling'] ?? 0),
            'inbound_storage' => $this->validateNumeric($postData['inbound_storage'] ?? 0),
            'outbound_storage' => $this->validateNumeric($postData['outbound_storage'] ?? 0),
            'misc_charges' => $this->validateNumeric($postData['misc_charges'] ?? 0)
        ];

        $salesData['total_amount'] = $this->calculateTotalAmount($salesData);

        $existingSales = $this->salesModel->where('booking_id', $id)->first();
        if ($existingSales) {
            $this->salesModel->update($existingSales['id'], $salesData);
        } else {
            $this->salesModel->insert($salesData);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new Exception('Transaction failed to update booking data.');
        }

        return true;
    }

    private function processShipments($bookingId, array $items)
    {
        // --- NEW JSON MIGRATION BLOCK START ---
        $postData = service('request')->getPost();
        if (!empty($postData['items_json'])) {
            $decoded = json_decode($postData['items_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format in shipment items data: ' . json_last_error_msg());
            }
            if (!is_array($decoded)) {
                throw new \Exception('Shipment items must be a valid list.');
            }
            $items = []; // Overwrite the old array format
            foreach ($decoded as $jsItem) {
                $items[] = [
                    'id' => $jsItem['id'] ?? '',
                    'customer_name' => $jsItem['customer'] ?? '',
                    'bill_to' => $jsItem['bill_to'] ?? '',
                    'consignee' => $jsItem['consignee'] ?? '',
                    'docket_no' => $jsItem['docket'] ?? '',
                    'invoice_no' => $jsItem['invoice_no'] ?? '',
                    'part_no' => $jsItem['contents'] ?? '',
                    'pieces' => $jsItem['pcs'] ?? 1,
                    'actual_weight' => $jsItem['act_wt'] ?? 0,
                    'length' => $jsItem['l'] ?? 0,
                    'width' => $jsItem['w'] ?? 0,
                    'height' => $jsItem['h'] ?? 0,
                    'volumetric_weight' => $jsItem['vol_wt'] ?? 0,
                    'calculated_chargeable_weight' => $jsItem['chg_wt'] ?? 0,
                    'chargeable_weight' => $jsItem['chg_wt'] ?? 0,
                    'eway_bill_no' => $jsItem['eway_no'] ?? '',
                    'eway_bill_date' => !empty($jsItem['eway_date']) ? $jsItem['eway_date'] : null,
                    'rate' => $jsItem['rate'] ?? 0,
                    'delivery_charges' => $jsItem['delivery_charges'] ?? 0,
                    'docket_charges' => $jsItem['docket_charges'] ?? 0,
                    'pickup_charges' => $jsItem['pickup_charges'] ?? 0,
                    'fuel_surcharge' => $jsItem['fuel_surcharge'] ?? 0,
                    'fov_charges' => $jsItem['fov_charges'] ?? 0,
                    'handling_charges' => $jsItem['handling_charges'] ?? 0,
                    'service_charges' => $jsItem['service_charges'] ?? 0
                ];
            }
        }
        // --- NEW JSON MIGRATION BLOCK END ---

        if (!is_array($items)) {
            throw new Exception("Items must be an array");
        }

        foreach ($items as $item) {
            if (empty($item['customer_name'])) {
                throw new \Exception('Customer Name is required for all shipment items.');
            }
                $shipmentData = [
                    'booking_id' => $bookingId,
                    'customer_name' => $item['customer_name'],
                    'bill_to' => $item['bill_to'] ?? '',
                    'consignee' => $item['consignee'] ?? '',
                    'docket_no' => $item['docket_no'] ?? '',
                    'part_no' => $item['part_no'] ?? '',
                    'invoice_no' => $item['invoice_no'] ?? '',
                    'invoice_date' => $item['invoice_date'] ?? null,
                    'actual_weight' => $this->validateNumeric($item['actual_weight'] ?? 0),
                    'length' => $this->validateNumeric($item['length'] ?? 0),
                    'width' => $this->validateNumeric($item['width'] ?? 0),
                    'height' => $this->validateNumeric($item['height'] ?? 0),
                    'volumetric_weight' => $this->validateNumeric($item['volumetric_weight'] ?? 0),
                    'calculated_chargeable_weight' => $this->validateNumeric($item['calculated_chargeable_weight'] ?? 0),
                    'final_chargeable_weight' => $this->validateNumeric($item['chargeable_weight'] ?? 0),
                    'pieces' => intval($item['pieces'] ?? 1),
                    'eway_bill_no' => $item['eway_bill_no'] ?? '',
                    'eway_bill_date' => $item['eway_bill_date'] ?? null,
                    'rate' => $this->validateNumeric($item['rate'] ?? 0),
                    'delivery_charges' => $this->validateNumeric($item['delivery_charges'] ?? 0),
                    'docket_charges' => $this->validateNumeric($item['docket_charges'] ?? 0),
                    'pickup_charges' => $this->validateNumeric($item['pickup_charges'] ?? 0),
                    'fuel_surcharge' => $this->validateNumeric($item['fuel_surcharge'] ?? 0),
                    'fov_charges' => $this->validateNumeric($item['fov_charges'] ?? 0),
                    'handling_charges' => $this->validateNumeric($item['handling_charges'] ?? 0),
                    'service_charges' => $this->validateNumeric($item['service_charges'] ?? 0)
                ];
                
                if (!$this->shipmentModel->insert($shipmentData)) {
                    throw new \Exception('Failed to insert shipment item: ' . implode(', ', $this->shipmentModel->errors()));
                }
                $recordId = $this->shipmentModel->getInsertID();

                if ($shipmentData['final_chargeable_weight'] != $shipmentData['calculated_chargeable_weight']) {
                    $this->logAudit('shipment_items', $recordId, 'chargeable_weight_override', $shipmentData['calculated_chargeable_weight'], $shipmentData['final_chargeable_weight']);
                }
        }
    }

    private function processSales($bookingId, array $postData)
    {
        $salesData = [
            'booking_id' => $bookingId,
            'flight_number' => $postData['flight_number'] ?? '',
            'airlines' => $postData['airlines'] ?? '',
            'rate' => $this->validateNumeric($postData['rate'] ?? 0),
            'weight' => $this->validateNumeric($postData['weight'] ?? 0),
            'ddc' => $this->validateNumeric($postData['ddc'] ?? 0),
            'ssc' => $this->validateNumeric($postData['ssc'] ?? 0),
            'btc' => $this->validateNumeric($postData['btc'] ?? 0),
            'flc' => $this->validateNumeric($postData['flc'] ?? 0),
            'doc' => $this->validateNumeric($postData['doc'] ?? 0),
            'inbound_tsp' => $this->validateNumeric($postData['inbound_tsp'] ?? 0),
            'outbound_tsp' => $this->validateNumeric($postData['outbound_tsp'] ?? 0),
            'tcp' => $this->validateNumeric($postData['tcp'] ?? 0),
            'utility_charges' => $this->validateNumeric($postData['utility_charges'] ?? 0),
            'xray_charges' => $this->validateNumeric($postData['xray_charges'] ?? 0),
            'ado' => $this->validateNumeric($postData['ado'] ?? 0),
            'awb_fees_agent' => $this->validateNumeric($postData['awb_fees_agent'] ?? 0),
            'awb_fees_carrier' => $this->validateNumeric($postData['awb_fees_carrier'] ?? 0),
            'admin_charges' => $this->validateNumeric($postData['admin_charges'] ?? 0),
            'delivery_order_charges' => $this->validateNumeric($postData['delivery_order_charges'] ?? 0),
            'inbound_handling' => $this->validateNumeric($postData['inbound_handling'] ?? 0),
            'inbound_storage' => $this->validateNumeric($postData['inbound_storage'] ?? 0),
            'outbound_storage' => $this->validateNumeric($postData['outbound_storage'] ?? 0),
            'misc_charges' => $this->validateNumeric($postData['misc_charges'] ?? 0)
        ];

        $salesData['total_amount'] = $this->calculateTotalAmount($salesData);
        $this->salesModel->insert($salesData);
    }

    private function validateBasicData($data)
    {
        if (empty($data['awb_no'])) {
            throw new Exception("AWB Number is required.");
        }
    }

    private function validateNumeric($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        return 0.00;
    }

    private function logAudit($tableName, $recordId, $fieldName, $oldValue, $newValue)
    {
        if ((string)$oldValue !== (string)$newValue) {
            $this->db->table('audit_logs')->insert([
                'table_name' => $tableName,
                'record_id'  => $recordId,
                'field_name' => $fieldName,
                'old_value'  => $oldValue,
                'new_value'  => $newValue,
                'changed_by' => session()->get('user_id'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function calculateTotalAmount(array $salesData)
    {
        return (floatval($salesData['rate'] ?? 0) * floatval($salesData['weight'] ?? 0))
            + floatval($salesData['ddc'] ?? 0)
            + floatval($salesData['ssc'] ?? 0)
            + floatval($salesData['btc'] ?? 0)
            + floatval($salesData['flc'] ?? 0)
            + floatval($salesData['doc'] ?? 0)
            + floatval($salesData['inbound_tsp'] ?? 0)
            + floatval($salesData['outbound_tsp'] ?? 0)
            + floatval($salesData['tcp'] ?? 0)
            + floatval($salesData['utility_charges'] ?? 0)
            + floatval($salesData['xray_charges'] ?? 0)
            + floatval($salesData['ado'] ?? 0)
            + floatval($salesData['awb_fees_agent'] ?? 0)
            + floatval($salesData['awb_fees_carrier'] ?? 0)
            + floatval($salesData['admin_charges'] ?? 0)
            + floatval($salesData['delivery_order_charges'] ?? 0)
            + floatval($salesData['inbound_handling'] ?? 0)
            + floatval($salesData['inbound_storage'] ?? 0)
            + floatval($salesData['outbound_storage'] ?? 0)
            + floatval($salesData['misc_charges'] ?? 0);
    }
}
