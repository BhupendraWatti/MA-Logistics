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
    }

    public function createBooking(array $postData, int $userId)
    {
        $this->validateBasicData($postData);

        $this->db->transStart();

        $bookingData = [
            'awb_no' => $postData['awb_no'] ?? '',
            'company_id' => $postData['company_id'] ?? null,
            'booking_date' => $postData['booking_date'] ?? null,
            'origin' => $postData['origin'] ?? '',
            'destination' => $postData['destination'] ?? '',
            'mode_transport' => $postData['mode_transport'] ?? '',
            'material_type' => $postData['material_type'] ?? '',
            'material_details' => $postData['material_details'] ?? '',
            'material_category' => $postData['material_category'] ?? '',
            'status' => $postData['status'] ?? 'Draft',
            'driver_name' => $postData['driver_name'] ?? '',
            'driver_mobile' => $postData['driver_mobile'] ?? '',
            'vehicle_no' => $postData['vehicle_no'] ?? '',
            'total_pieces' => $postData['total_pieces'] ?? 1,
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

    public function updateBooking(int $id, array $postData, int $userId)
    {
        $this->validateBasicData($postData);

        $this->db->transStart();

        $bookingData = [
            'awb_no' => $postData['awb_no'] ?? '',
            'company_id' => $postData['company_id'] ?? null,
            'booking_date' => $postData['booking_date'] ?? null,
            'origin' => $postData['origin'] ?? '',
            'destination' => $postData['destination'] ?? '',
            'mode_transport' => $postData['mode_transport'] ?? '',
            'material_type' => $postData['material_type'] ?? '',
            'material_details' => $postData['material_details'] ?? '',
            'material_category' => $postData['material_category'] ?? '',
            'status' => $postData['status'] ?? 'Draft',
            'driver_name' => $postData['driver_name'] ?? '',
            'driver_mobile' => $postData['driver_mobile'] ?? '',
            'vehicle_no' => $postData['vehicle_no'] ?? '',
            'total_pieces' => $postData['total_pieces'] ?? 1,
            'flight_number' => $postData['flight_number'] ?? '',
            'airlines' => $postData['airlines'] ?? '',
            'created_by' => $userId
        ];

        $this->bookingModel->update($id, $bookingData);

        $existingShipments = $this->shipmentModel->where('booking_id', $id)->findAll();
        $existingIds = array_column($existingShipments, 'id');

        $items = $postData['items'] ?? [];
        $submittedIds = [];

        foreach ($items as $item) {
            if (!empty($item['customer_name'])) {
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
                    'chargeable_weight' => $this->validateNumeric($item['chargeable_weight'] ?? 0),
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

                if (!empty($item['id']) && in_array($item['id'], $existingIds)) {
                    $this->shipmentModel->update($item['id'], $shipmentData);
                    $submittedIds[] = $item['id'];
                } else {
                    $this->shipmentModel->insert($shipmentData);
                }
            }
        }

        $idsToDelete = array_diff($existingIds, $submittedIds);
        if (!empty($idsToDelete)) {
            $this->shipmentModel->whereIn('id', $idsToDelete)->delete();
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
        if (!is_array($items)) {
            throw new Exception("Items must be an array");
        }

        foreach ($items as $item) {
            if (!empty($item['customer_name'])) {
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
                    'chargeable_weight' => $this->validateNumeric($item['chargeable_weight'] ?? 0),
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
                
                $this->shipmentModel->insert($shipmentData);
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
        if (empty($data['company_id'])) {
            throw new Exception("Company ID is required.");
        }
        if (empty($data['awb_no'])) {
            throw new Exception("AWB Number is required.");
        }
    }

    private function validateNumeric($val)
    {
        if (!is_numeric($val) && !empty($val)) {
            throw new Exception("Invalid numeric format detected in financial fields.");
        }
        return floatval($val);
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
