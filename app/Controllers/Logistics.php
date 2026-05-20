<?php
namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\BookingModel;
use App\Models\ShipmentItemModel;
use App\Models\SalesChargeModel;

class Logistics extends BaseController
{
    private function checkPermission($permission)
    {
        $permissions = session()->get('permissions') ?? [];
        if (!$permissions[$permission]) {
            return redirect()->to('/logistics')->with('error', 'Permission denied!');
        }
    }



private function enforcePermissions($action)
{
    $permissions = session()->get('permissions') ?? [];
    
    switch($action) {
        case 'create':
            if (!($permissions['can_create'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', '❌ Create permission denied!');
            }
            break;
        case 'edit':
            if (!($permissions['can_edit'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', '❌ Edit permission denied!');
            }
            break;
        case 'delete':
            if (!($permissions['can_delete'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', '❌ Delete permission denied!');
            }
            break;
    }
    return true;
}


public function index()
{
    $data = [
        'user' => session()->get(),
        'permissions' => session()->get('permissions') ?? [],
        'company_name' => session()->get('selected_company_name'),
        'company_id' => session()->get('selected_company_id')
    ];

    $companyId = session()->get('selected_company_id');
    if ($companyId) {
        $bookingModel = new BookingModel();
        $data['stats'] = $bookingModel->getCompanyStats($companyId);
        $data['recent_bookings'] = $bookingModel->getCompanyBookings($companyId, 10); // Show 10 bookings
        $data['all_bookings'] = $bookingModel->where('company_id', $companyId)
                                           ->orderBy('booking_date', 'DESC')
                                           ->findAll(50); // All recent 50
    }

    return view('logistics/dashboard', $data);
}

    public function search()
    {
        $companyModel = new CompanyModel();
        $data['companies'] = $companyModel->findAll();
        $data['user'] = session()->get();
        return view('logistics/search_form', $data);
    }

    public function searchResult()
    {
        $bookingModel = new BookingModel();
        $companyId = $this->request->getPost('company_id');
        $searchValue = $this->request->getPost('search_value');

        $bookings = $bookingModel->searchByCompany($companyId, $searchValue);
        
        $data['bookings'] = $bookings;
        $data['user'] = session()->get();
        return view('logistics/search_results', $data);
    }


public function create()
{
    $permissions = session()->get('permissions') ?? [];
    
    // ✅ TRIPLE CHECK
    if (!($permissions['can_create'] ?? 0)) {
        return redirect()->to('/logistics')
            ->with('error', '❌ You do not have permission to create bookings!');
    }
    
    $data['user'] = session()->get();
    $data['selected_company_id'] = session()->get('selected_company_id');
    $data['selected_company_name'] = session()->get('selected_company_name');
    
    if (!$data['selected_company_id']) {
        return redirect()->to('/company-selection')->with('error', 'Please select company first!');
    }
    
    return view('logistics/booking_form', $data);
}

  public function store()
  {
    $this->checkPermission('can_create');
    
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();

    // 1. Insert Booking (Tab 1 Basic)
    $bookingData = [
        'awb_no' => $this->request->getPost('awb_no'),
        'company_id' => $this->request->getPost('company_id'),
        'booking_date' => $this->request->getPost('booking_date'),
        'origin' => $this->request->getPost('origin'),
        'destination' => $this->request->getPost('destination'),
        'mode_transport' => $this->request->getPost('mode_transport'),
        'material_type' => $this->request->getPost('material_type'),
        'material_details' => $this->request->getPost('material_details'),
        'material_category' => $this->request->getPost('material_category'),
        'status' => $this->request->getPost('status') ?? 'Draft',
        'driver_name' => $this->request->getPost('driver_name'),
        'driver_mobile' => $this->request->getPost('driver_mobile'),
        'vehicle_no' => $this->request->getPost('vehicle_no'),
        'total_pieces' => $this->request->getPost('total_pieces'),
        'flight_number' => $this->request->getPost('flight_number'),
        'airlines' => $this->request->getPost('airlines'),
        'created_by' => session()->get('user_id')
    ];

    if (!$bookingModel->insert($bookingData)) {
        return redirect()->back()->with('error', 'Failed to create booking: ' . implode(', ', $bookingModel->errors()));
    }

    $bookingId = $bookingModel->getInsertID();

    // 2. Insert Shipment Items (Multiple)
    $items = $this->request->getPost('items') ?? [];
    foreach ($items as $item) {
        if (!empty($item['customer_name'])) {
            $shipmentData = [
                'booking_id' => $bookingId,
                'customer_name' => $item['customer_name'],
                'bill_to' => $item['bill_to'],
                'consignee' => $item['consignee'],
                'docket_no' => $item['docket_no'] ?? '',
                'part_no' => $item['part_no'] ?? '',
                'invoice_no' => $item['invoice_no'] ?? '',
                'invoice_date' => $item['invoice_date'] ?? null,
                'actual_weight' => $item['actual_weight'] ?? 0,
                'length' => $item['length'] ?? 0,
                'width' => $item['width'] ?? 0,
                'height' => $item['height'] ?? 0,
                'volumetric_weight' => $item['volumetric_weight'] ?? 0,
                'chargeable_weight' => $item['chargeable_weight'] ?? 0,
                'pieces' => $item['pieces'] ?? 1,
                'eway_bill_no' => $item['eway_bill_no'] ?? '',
                'eway_bill_date' => $item['eway_bill_date'] ?? null,
                'rate' => $item['rate'] ?? 0,
                'delivery_charges' => $item['delivery_charges'] ?? 0,
                'docket_charges' => $item['docket_charges'] ?? 0,
                'pickup_charges' => $item['pickup_charges'] ?? 0,
                'fuel_surcharge' => $item['fuel_surcharge'] ?? 0,
                'fov_charges' => $item['fov_charges'] ?? 0,
                'handling_charges' => $item['handling_charges'] ?? 0,
                'service_charges' => $item['service_charges'] ?? 0
            ];
            
            $shipmentModel->insert($shipmentData);
        }
    }

    // 3. Insert Sales Charges (Tab 2)
    $salesData = [
        'booking_id' => $bookingId,
        'flight_number' => $this->request->getPost('flight_number'),
        'airlines' => $this->request->getPost('airlines'),
        'rate' => $this->request->getPost('rate'),
        'weight' => $this->request->getPost('weight'),
        'ddc' => $this->request->getPost('ddc') ?? 0,
        'ssc' => $this->request->getPost('ssc') ?? 0,
        'btc' => $this->request->getPost('btc') ?? 0,
        'flc' => $this->request->getPost('flc') ?? 0,
        'doc' => $this->request->getPost('doc') ?? 0,
        'inbound_tsp' => $this->request->getPost('inbound_tsp') ?? 0,
        'outbound_tsp' => $this->request->getPost('outbound_tsp') ?? 0,
        'tcp' => $this->request->getPost('tcp') ?? 0,
        'utility_charges' => $this->request->getPost('utility_charges') ?? 0,
        'xray_charges' => $this->request->getPost('xray_charges') ?? 0,
        'ado' => $this->request->getPost('ado') ?? 0,
        'awb_fees_agent' => $this->request->getPost('awb_fees_agent') ?? 0,
        'awb_fees_carrier' => $this->request->getPost('awb_fees_carrier') ?? 0,
        'admin_charges' => $this->request->getPost('admin_charges') ?? 0,
        'delivery_order_charges' => $this->request->getPost('delivery_order_charges') ?? 0,
        'inbound_handling' => $this->request->getPost('inbound_handling') ?? 0,
        'inbound_storage' => $this->request->getPost('inbound_storage') ?? 0,
        'outbound_storage' => $this->request->getPost('outbound_storage') ?? 0,
        'misc_charges' => $this->request->getPost('misc_charges') ?? 0
    ];

    $salesModel->insert($salesData);

    return redirect()->to('/logistics')->with('success', '✅ Booking created successfully! AWB: ' . $bookingData['awb_no']);
  }


  public function view($id)
  {
    $this->checkPermission('can_edit');
    
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();
    
    $booking = $bookingModel->getFullBooking($id);
    if (!$booking) {
        return redirect()->back()->with('error', 'Booking not found!');
    }
    
    $shipments = $shipmentModel->where('booking_id', $id)->findAll();
    $sales = $salesModel->where('booking_id', $id)->first();
    
    $data = [
        'booking' => $booking,
        'shipments' => $shipments,
        'sales' => $sales,
        'user' => session()->get()
    ];
    
    return view('logistics/view_booking', $data);
  }


public function edit($id)
{
    $this->checkPermission('can_edit');
    
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();
    //$companyModel = new CompanyModel();
    
    $booking = $bookingModel->getFullBooking($id);
    if (!$booking) {
        return redirect()->back()->with('error', 'Booking not found!');
    }
    
    $data = [
        'booking' => $booking,
        'shipments' => $shipmentModel->where('booking_id', $id)->findAll(),
        'sales' => $salesModel->where('booking_id', $id)->first(),
        //'companies' => $companyModel->findAll(),  // ← ADD THIS
        'isEdit' => true,
        'bookingId' => $id,
        'selected_company_id' => session()->get('selected_company_id'), // ✅ Auto company
        'selected_company_name' => session()->get('selected_company_name'), // Add this for Auto
        'user' => session()->get()
    ];
    
    return view('logistics/booking_form', $data);
}


  public function update($id)
  {
    $this->checkPermission('can_edit');
    
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();

    // Update Booking
    $bookingData = [
        'awb_no' => $this->request->getPost('awb_no'),
        'company_id' => $this->request->getPost('company_id'),
        'booking_date' => $this->request->getPost('booking_date'),
        'origin' => $this->request->getPost('origin'),
        'destination' => $this->request->getPost('destination'),
        'mode_transport' => $this->request->getPost('mode_transport'),
        'material_type' => $this->request->getPost('material_type'),
        'material_details' => $this->request->getPost('material_details'),
        'material_category' => $this->request->getPost('material_category'),
        'status' => $this->request->getPost('status'),
        'driver_name' => $this->request->getPost('driver_name'),
        'driver_mobile' => $this->request->getPost('driver_mobile'),
        'vehicle_no' => $this->request->getPost('vehicle_no'),
        'total_pieces' => $this->request->getPost('total_pieces'),
        'flight_number' => $this->request->getPost('flight_number'),
        'airlines' => $this->request->getPost('airlines'),
        'created_by' => session()->get('user_id')
    ];

    $bookingModel->update($id, $bookingData);

    // Delete old shipments and sales, insert new ones
    $shipmentModel->where('booking_id', $id)->delete();
    $salesModel->where('booking_id', $id)->delete();

    // Insert new shipments
    $items = $this->request->getPost('items') ?? [];
    foreach ($items as $item) {
        if (!empty($item['customer_name'])) {
            $shipmentModel->insert([
                'booking_id' => $id,
                'customer_name' => $item['customer_name'],
                'bill_to' => $item['bill_to'],
                'consignee' => $item['consignee'],
                'docket_no' => $item['docket_no'] ?? '',
                'part_no' => $item['part_no'] ?? '',
                'invoice_no' => $item['invoice_no'] ?? '',
                'invoice_date' => $item['invoice_date'] ?? null,
                'actual_weight' => $item['actual_weight'] ?? 0,
                'length' => $item['length'] ?? 0,
                'width' => $item['width'] ?? 0,
                'height' => $item['height'] ?? 0,
                'volumetric_weight' => $item['volumetric_weight'] ?? 0,
                'chargeable_weight' => $item['chargeable_weight'] ?? 0,
                'pieces' => $item['pieces'] ?? 1,
                'eway_bill_no' => $item['eway_bill_no'] ?? '',
                'eway_bill_date' => $item['eway_bill_date'] ?? null,
                'rate' => $item['rate'] ?? 0,
                'delivery_charges' => $item['delivery_charges'] ?? 0,
                'docket_charges' => $item['docket_charges'] ?? 0,
                'pickup_charges' => $item['pickup_charges'] ?? 0,
                'fuel_surcharge' => $item['fuel_surcharge'] ?? 0,
                'fov_charges' => $item['fov_charges'] ?? 0,
                'handling_charges' => $item['handling_charges'] ?? 0,
                'service_charges' => $item['service_charges'] ?? 0
            ]);
        }
    }

    // Insert new sales charges
    $salesData = [
        'booking_id' => $id,
        'flight_number' => $this->request->getPost('flight_number'),
        'airlines' => $this->request->getPost('airlines'),
        'rate' => $this->request->getPost('rate'),
        'weight' => $this->request->getPost('weight'),
        'ddc' => $this->request->getPost('ddc') ?? 0,
        'ssc' => $this->request->getPost('ssc') ?? 0,
        'btc' => $this->request->getPost('btc') ?? 0,
        'flc' => $this->request->getPost('flc') ?? 0,
        'doc' => $this->request->getPost('doc') ?? 0,
        'inbound_tsp' => $this->request->getPost('inbound_tsp') ?? 0,
        'outbound_tsp' => $this->request->getPost('outbound_tsp') ?? 0,
        'tcp' => $this->request->getPost('tcp') ?? 0,
        'utility_charges' => $this->request->getPost('utility_charges') ?? 0,
        'xray_charges' => $this->request->getPost('xray_charges') ?? 0,
        'ado' => $this->request->getPost('ado') ?? 0,
        'awb_fees_agent' => $this->request->getPost('awb_fees_agent') ?? 0,
        'awb_fees_carrier' => $this->request->getPost('awb_fees_carrier') ?? 0,
        'admin_charges' => $this->request->getPost('admin_charges') ?? 0,
        'delivery_order_charges' => $this->request->getPost('delivery_order_charges') ?? 0,
        'inbound_handling' => $this->request->getPost('inbound_handling') ?? 0,
        'inbound_storage' => $this->request->getPost('inbound_storage') ?? 0,
        'outbound_storage' => $this->request->getPost('outbound_storage') ?? 0,
        'misc_charges' => $this->request->getPost('misc_charges') ?? 0
    ];

    $salesModel->insert($salesData);

    return redirect()->to('/logistics')->with('success', '✅ Booking updated successfully! AWB: ' . $bookingData['awb_no']);
  }


  public function delete($id)
  {
    $this->checkPermission('can_delete');
    
    $bookingModel = new BookingModel();
    $booking = $bookingModel->find($id);
    
    if (!$booking) {
        return $this->response->setJSON(['success' => false, 'message' => 'Booking not found']);
    }
    
    // Cascade delete shipments and sales
    (new \App\Models\ShipmentItemModel())->where('booking_id', $id)->delete();
    (new \App\Models\SalesChargeModel())->where('booking_id', $id)->delete();
    
    $bookingModel->delete($id);
    
    return $this->response->setJSON([
        'success' => true, 
        'message' => 'Booking ' . $booking['awb_no'] . ' deleted successfully'
    ]);
  }

  // Add Consol/Consolidation Mode
  public function consolidation()
  {
    $this->checkPermission('can_create');
    $data['companies'] = (new CompanyModel())->findAll();
    $data['user'] = session()->get();
    return view('logistics/consolidation_form', $data);
  }


public function companySelection()
{
    // Only redirect if NOT logged in
    if (!session()->get('user_id')) {
        return redirect()->to('/login');
    }
    
    // If already selected company, go to dashboard
    if (session()->get('selected_company_id')) {
        return redirect()->to('/logistics');
    }
    
    $companyModel = new CompanyModel();
    $data = [
        'user' => session()->get(),
        'companies' => $companyModel->findAll()
    ];
    
    return view('company_selection', $data);
}

  public function setCompany()
  {
    $companyId = $this->request->getPost('company_id');
    
    if ($companyId) {
        // Verify company exists
        $companyModel = new CompanyModel();
        $company = $companyModel->find($companyId);
        
        if ($company) {
            session()->set([
                'selected_company_id' => $companyId,
                'selected_company_name' => $company['name']
            ]);
            return redirect()->to('/logistics')
                ->with('success', '✅ Welcome to ' . $company['name'] . ' Dashboard!');
        }
    }
    
    return redirect()->back()
        ->with('error', '❌ Invalid company selection!');
  }


  public function clearCompany()
  {
    // COMPLETE SESSION CLEANUP
    session()->remove([
        'selected_company_id', 
        'selected_company_name'
    ]);
    
    // Browser cache bust
    return redirect()->to('/company-selection')
        ->with('info', '🔄 Company selection cleared. Please choose again.')
        ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
  }


 public function manageBookings()
 {

    // ✅ SECURITY FIRST - Block unauthorized users
    $this->checkPermission('can_edit');

    $companyId = session()->get('selected_company_id');
    if (!$companyId) {
        return redirect()->to('/company-selection');
    }
    
    $bookingModel = new BookingModel();
    $data = [
        'bookings' => $bookingModel->where('company_id', $companyId)
                                  ->orderBy('booking_date', 'DESC')
                                  ->findAll(),
        'company_name' => session()->get('selected_company_name'),
        'company_id' => $companyId,
        'user' => session()->get(),
        'permissions' => session()->get('permissions') ?? []
    ];
    
    return view('logistics/manage_bookings', $data);
 }



// Export PDF
public function exportPdf($id)
{
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();
    
    $booking = $bookingModel->getFullBooking($id);
    $shipments = $shipmentModel->where('booking_id', $id)->findAll();
    $sales = $salesModel->where('booking_id', $id)->first();
    
    if (!$booking) {
        return redirect()->back()->with('error', 'Booking not found!');
    }
    
    if (empty($shipments)) {
        return redirect()->back()->with('error', 'No shipment items found!');
    }
    
    // Generate PDF - Landscape for wider table
    $pdf = new \TCPDF();
    $pdf->SetCreator('Malogistics');
    $pdf->SetAuthor('Malogistics');
    $pdf->AddPage('L'); // Landscape mode
    $pdf->SetFont('helvetica', '', 7); // Smaller font
    
    // ========== HEADER ==========
    $html = '<h2 style="color:#0d6efd;">AWB: ' . $booking['awb_no'] . '</h2>';
    $html .= '<p style="font-size:9px;">';
    $html .= '<b>Company:</b> ' . $booking['company_name'] . ' | ';
    $html .= '<b>Origin:</b> ' . $booking['origin'] . ' → ' . $booking['destination'] . ' | ';
    $html .= '<b>Status:</b> ' . $booking['status'] . ' | ';
    $html .= '<b>Pieces:</b> ' . $booking['total_pieces'];
    $html .= '</p><hr>';
    
    // ========== MAIN TABLE ==========
    $html .= '<table border="1" cellpadding="2" style="border-collapse:collapse; width:100%;">';
    $html .= '<tr style="background-color:#0d6efd; color:white; font-size:8px;">
        <th width="3%">SR</th>
        <th width="6%">DATE</th>
        <th width="10%">INVOICE NO</th>
        <th width="10%">ORIGIN</th>
        <th width="10%">DESTINATION</th>
        <th width="5%">BOX</th>
        <th width="6%">WEIGHT</th>
        <th width="6%">RATE</th>
        <th width="6%">FUEL SUR</th>
        <th width="7%">FREIGHT</th>
        <th width="7%">FUEL AMT</th>
        <th width="6%">DOCKET</th>
        <th width="7%">PICKUP</th>
        <th width="7%">DELIVERY</th>
        <th width="8%">TAXABLE</th>
    </tr>';
    
    // ========== TABLE ROWS ==========
    $serial = 1;
    $totalFreight = 0;
    $totalFuelAmt = 0;
    $totalDock = 0;
    $totalPickup = 0;
    $totalDelivery = 0;
    $totalTaxable = 0;
    
    foreach ($shipments as $item) {
        // Get values safely
        $wt = floatval($item['actual_weight'] ?? 0);
        $rate = floatval($item['rate'] ?? 0);
        $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
        $dock = floatval($item['docket_charges'] ?? 0);
        $pickup = floatval($item['pickup_charges'] ?? 0);
        $delivery = floatval($item['delivery_charges'] ?? 0);
        
        // Calculations
        $freight = $wt * $rate;
        $fuelAmt = $wt * $fuelSur;
        $taxable = $freight + $fuelAmt + $dock + $pickup + $delivery;
        
        // Sum totals
        $totalFreight += $freight;
        $totalFuelAmt += $fuelAmt;
        $totalDock += $dock;
        $totalPickup += $pickup;
        $totalDelivery += $delivery;
        $totalTaxable += $taxable;
        
        // Invoice date
        $invDate = !empty($item['invoice_date']) ? date('d-m-y', strtotime($item['invoice_date'])) : '-';
        
        $html .= '<tr style="font-size:7px;">
            <td style="text-align:center;">' . $serial . '</td>
            <td style="text-align:center;">' . $invDate . '</td>
            <td>' . ($item['invoice_no'] ?? '-') . '</td>
            <td>' . $booking['origin'] . '</td>
            <td>' . $booking['destination'] . '</td>
            <td style="text-align:center;">' . ($item['pieces'] ?? 1) . '</td>
            <td style="text-align:right;">' . number_format($wt, 2) . '</td>
            <td style="text-align:right;">' . number_format($rate, 2) . '</td>
            <td style="text-align:right;">' . number_format($fuelSur, 2) . '</td>
            <td style="text-align:right;">' . number_format($freight, 2) . '</td>
            <td style="text-align:right;">' . number_format($fuelAmt, 2) . '</td>
            <td style="text-align:right;">' . number_format($dock, 2) . '</td>
            <td style="text-align:right;">' . number_format($pickup, 2) . '</td>
            <td style="text-align:right;">' . number_format($delivery, 2) . '</td>
            <td style="text-align:right; font-weight:bold;">' . number_format($taxable, 2) . '</td>
        </tr>';
        
        $serial++;
    }
    
    $html .= '</table>';
    
    // ========== TOTALS TABLE ==========
    // $html .= '<br><table border="1" cellpadding="3" style="width:40%; border-collapse:collapse;">';
    // $html .= '<tr style="background-color:#f8f9fa; font-size:9px;">
    //     <th style="text-align:left;">FREIGHT</th>
    //     <th style="text-align:right;">' . number_format($totalFreight, 2) . '</th>
    // </tr>';
    // $html .= '<tr style="font-size:9px;">
    //     <th style="text-align:left;">FUEL AMOUNT</th>
    //     <th style="text-align:right;">' . number_format($totalFuelAmt, 2) . '</th>
    // </tr>';
    // $html .= '<tr style="font-size:9px;">
    //     <th style="text-align:left;">DOCKET CHARGE</th>
    //     <th style="text-align:right;">' . number_format($totalDock, 2) . '</th>
    // </tr>';
    // $html .= '<tr style="font-size:9px;">
    //     <th style="text-align:left;">PICK UP CHARGE</th>
    //     <th style="text-align:right;">' . number_format($totalPickup, 2) . '</th>
    // </tr>';
    // $html .= '<tr style="font-size:9px;">
    //     <th style="text-align:left;">DELIVERY CHARGE</th>
    //     <th style="text-align:right;">' . number_format($totalDelivery, 2) . '</th>
    // </tr>';
    // $html .= '<tr style="background-color:#198754; color:white; font-size:10px;">
    //     <th style="text-align:left;">TOTAL TAXABLE AMOUNT</th>
    //     <th style="text-align:right;">₹' . number_format($totalTaxable, 2) . '</th>
    // </tr>';
    // $html .= '</table>';
    
    // ========== SALES CHARGES (FIXED) ==========
    if ($sales) {
        $html .= '<hr><table border="0" cellpadding="2" style="width:30%; font-size:8px;">';
        $html .= '<tr><th colspan="2" style="background-color:#ffc107;">Sales Charges</th></tr>';
        $html .= '<tr><td>Flight:</td><td>' . ($sales['flight_number'] ?? '-') . '</td></tr>';
        $html .= '<tr><td>Airlines:</td><td>' . ($sales['airlines'] ?? '-') . '</td></tr>';
        $html .= '<tr><td>Weight:</td><td>' . number_format($sales['weight'] ?? 0, 2) . ' KG</td></tr>';
        $html .= '<tr><td>Rate:</td><td>Rs ' . number_format($sales['rate'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>DDC:</td><td>Rs ' . number_format($sales['ddc'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>SSC:</td><td>Rs ' . number_format($sales['ssc'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td><strong>Total:</strong></td><td><strong>Rs ' . number_format($sales['total_amount'] ?? 0, 2) . '</strong></td></tr>';
        $html .= '</table>';
    }
    
    // Output PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('AWB-' . $booking['awb_no'] . '.pdf', 'D');
}




// ========== Export Excel Start ===========
// public function exportExcel()
// {
//     $bookingModel = new BookingModel();
//     $shipmentModel = new ShipmentItemModel();
    
//     $ids = $this->request->getGet('ids');
    
//     if (empty($ids)) {
//         return redirect()->back()->with('error', 'Please select at least one booking!');
//     }
    
//     $idArray = explode(',', $ids);
    
//     // CSV Headers
//     $headers = ['SR NO', 'AWB NO', 'DATE', 'COMPANY', 'ORIGIN', 'DESTINATION', 'STATUS', 'PIECES', 
//                'INVOICE NO', 'CUSTOMER', 'BILL TO', 'CONSIGNEE', 'WEIGHT', 'RATE', 
//                'FREIGHT', 'FUEL SUR', 'FUEL AMT', 'DOCKET', 'PICKUP', 'DELIVERY', 'TAXABLE'];
    
//     // Output buffer
//     $output = implode(',', $headers) . "\n";
    
//     $srNo = 1;
    
//     foreach ($idArray as $bookingId) {
//         $booking = $bookingModel->find($bookingId);
//         if (!$booking) continue;
        
//         $shipments = $shipmentModel->where('booking_id', $bookingId)->findAll();
        
//         foreach ($shipments as $item) {
//             $wt = floatval($item['actual_weight'] ?? 0);
//             $rate = floatval($item['rate'] ?? 0);
//             $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
//             $dock = floatval($item['docket_charges'] ?? 0);
//             $pickup = floatval($item['pickup_charges'] ?? 0);
//             $delivery = floatval($item['delivery_charges'] ?? 0);
            
//             $freight = $wt * $rate;
//             $fuelAmt = $wt * $fuelSur;
//             $taxable = $freight + $fuelAmt + $dock + $pickup + $delivery;
            
//             $row = [
//                 $srNo,
//                 $booking['awb_no'],
//                 date('d-m-Y', strtotime($booking['booking_date'])),
//                 $booking['company_id'],
//                 $booking['origin'],
//                 $booking['destination'],
//                 $booking['status'],
//                 $booking['total_pieces'],
//                 $item['invoice_no'] ?? '',
//                 $item['customer_name'] ?? '',
//                 $item['bill_to'] ?? '',
//                 $item['consignee'] ?? '',
//                 $wt,
//                 $rate,
//                 $freight,
//                 $fuelSur,
//                 $fuelAmt,
//                 $dock,
//                 $pickup,
//                 $delivery,
//                 $taxable
//             ];
            
//             // Escape CSV values
//             $output .= '"' . implode('","', $row) . '"' . "\n";
//             $srNo++;
//         }
//     }
    
//     // Download CSV
//     header('Content-Type: application/csv');
//     header('Content-Disposition: attachment; filename="AWB_Export_' . date('Y-m-d') . '.csv"');
//     echo $output;
//     exit;
// }

public function exportExcel()
{
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $companyModel = new CompanyModel();
    
    $ids = $this->request->getGet('ids');
    
    if (empty($ids)) {
        return redirect()->back()->with('error', 'Please select at least one booking!');
    }
    
    $idArray = explode(',', $ids);
    
    // CSV Headers
    $headers = ['SR NO', 'AWB NO', 'DATE', 'COMPANY', 'ORIGIN', 'DESTINATION', 'STATUS', 'PIECES', 
               'INVOICE NO', 'CUSTOMER', 'BILL TO', 'CONSIGNEE', 'WEIGHT', 'RATE', 
               'FREIGHT', 'FUEL SUR', 'FUEL AMT', 'DOCKET', 'PICKUP', 'DELIVERY', 'TAXABLE'];
    
    // Output buffer
    $output = implode(',', $headers) . "\n";
    
    $srNo = 1;
    
    foreach ($idArray as $bookingId) {
        $booking = $bookingModel->find($bookingId);
        if (!$booking) continue;
        
        // Get company name
        $company = $companyModel->find($booking['company_id']);
        $companyName = $company['name'] ?? 'N/A';
        
        $shipments = $shipmentModel->where('booking_id', $bookingId)->findAll();
        
        foreach ($shipments as $item) {
            $wt = floatval($item['actual_weight'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
            $dock = floatval($item['docket_charges'] ?? 0);
            $pickup = floatval($item['pickup_charges'] ?? 0);
            $delivery = floatval($item['delivery_charges'] ?? 0);
            
            $freight = $wt * $rate;
            $fuelAmt = $wt * $fuelSur;
            $taxable = $freight + $fuelAmt + $dock + $pickup + $delivery;
            
            $row = [
                $srNo,
                $booking['awb_no'],
                date('d-m-Y', strtotime($booking['booking_date'])),
                $companyName,  // ← Now shows company NAME
                $booking['origin'],
                $booking['destination'],
                $booking['status'],
                $booking['total_pieces'],
                $item['invoice_no'] ?? '',
                $item['customer_name'] ?? '',
                $item['bill_to'] ?? '',
                $item['consignee'] ?? '',
                $wt,
                $rate,
                $freight,
                $fuelSur,
                $fuelAmt,
                $dock,
                $pickup,
                $delivery,
                $taxable
            ];
            
            // Escape CSV values
            $output .= '"' . implode('","', $row) . '"' . "\n";
            $srNo++;
        }
    }
    
    // Download CSV
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="AWB_Export_' . date('Y-m-d') . '.csv"');
    echo $output;
    exit;
}


// =========== Export Excel End ============




}