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
        if (!($permissions[$permission] ?? 0)) {
            return redirect()->to('/logistics')->with('error', 'Permission denied!');
        }

        return true;
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

    // Calculate total amount
    $totalAmount = (floatval($salesData['rate']) * floatval($salesData['weight']))
        + floatval($salesData['ddc'])
        + floatval($salesData['ssc'])
        + floatval($salesData['btc'])
        + floatval($salesData['flc'])
        + floatval($salesData['doc'])
        + floatval($salesData['inbound_tsp'])
        + floatval($salesData['outbound_tsp'])
        + floatval($salesData['tcp'])
        + floatval($salesData['utility_charges'])
        + floatval($salesData['xray_charges'])
        + floatval($salesData['ado'])
        + floatval($salesData['awb_fees_agent'])
        + floatval($salesData['awb_fees_carrier'])
        + floatval($salesData['admin_charges'])
        + floatval($salesData['delivery_order_charges'])
        + floatval($salesData['inbound_handling'])
        + floatval($salesData['inbound_storage'])
        + floatval($salesData['outbound_storage'])
        + floatval($salesData['misc_charges']);
    $salesData['total_amount'] = $totalAmount;

    $salesModel->insert($salesData);

    return redirect()->to('/logistics')->with('success', '✅ Booking created successfully! AWB: ' . $bookingData['awb_no']);
  }


  public function view($id)
  {
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

    // Get all existing shipments for this booking
    $existingShipments = $shipmentModel->where('booking_id', $id)->findAll();
    $existingIds = array_column($existingShipments, 'id');

    // Process shipments
    $items = $this->request->getPost('items') ?? [];
    $submittedIds = [];

    foreach ($items as $item) {
        if (!empty($item['customer_name'])) {
            $shipmentData = [
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
            ];

            if (!empty($item['id']) && in_array($item['id'], $existingIds)) {
                // Update existing item
                $shipmentModel->update($item['id'], $shipmentData);
                $submittedIds[] = $item['id'];
            } else {
                // Insert new item
                $shipmentModel->insert($shipmentData);
            }
        }
    }

    // Delete shipments that were removed by the user
    $idsToDelete = array_diff($existingIds, $submittedIds);
    if (!empty($idsToDelete)) {
        $shipmentModel->whereIn('id', $idsToDelete)->delete();
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

    // Calculate total amount
    $totalAmount = (floatval($salesData['rate']) * floatval($salesData['weight']))
        + floatval($salesData['ddc'])
        + floatval($salesData['ssc'])
        + floatval($salesData['btc'])
        + floatval($salesData['flc'])
        + floatval($salesData['doc'])
        + floatval($salesData['inbound_tsp'])
        + floatval($salesData['outbound_tsp'])
        + floatval($salesData['tcp'])
        + floatval($salesData['utility_charges'])
        + floatval($salesData['xray_charges'])
        + floatval($salesData['ado'])
        + floatval($salesData['awb_fees_agent'])
        + floatval($salesData['awb_fees_carrier'])
        + floatval($salesData['admin_charges'])
        + floatval($salesData['delivery_order_charges'])
        + floatval($salesData['inbound_handling'])
        + floatval($salesData['inbound_storage'])
        + floatval($salesData['outbound_storage'])
        + floatval($salesData['misc_charges']);
    $salesData['total_amount'] = $totalAmount;

    // Check if sales charge record already exists
    $existingSales = $salesModel->where('booking_id', $id)->first();
    if ($existingSales) {
        $salesModel->update($existingSales['id'], $salesData);
    } else {
        $salesModel->insert($salesData);
    }

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

  public function createCompany()
  {
    //   $permissions = session()->get('permissions') ?? [];
    //   $role = session()->get('role');
    //   if ($role !== 'admin' && !($permissions['can_create'] ?? 0)) {
    //       return redirect()->back()->with('error', '❌ You do not have permission to create companies!');
    //   }

    // ✅ FIXED: ONLY Admin can create companies (ignores can_create permission)
    if (session()->get('role') !== 'admin') {
        return redirect()->to('/logistics')->with('error', '❌ Admin access required!');
    }

      $name = $this->request->getPost('name');
      if (empty($name)) {
          return redirect()->back()->with('error', '❌ Company name is required!');
      }

      $companyModel = new CompanyModel();
      // Check if already exists
      if ($companyModel->where('name', $name)->first()) {
          return redirect()->back()->with('error', '❌ Company already exists!');
      }

      $companyModel->insert(['name' => $name]);
      return redirect()->back()->with('success', '✅ Company "' . esc($name) . '" created successfully!');
  }

  public function deleteCompany($id)
  {
    //   $permissions = session()->get('permissions') ?? [];
    //   $role = session()->get('role');
    //   if ($role !== 'admin' && !($permissions['can_delete'] ?? 0)) {
    //       return redirect()->back()->with('error', '❌ You do not have permission to delete companies!');
    //   }

    // ✅ FIXED: ONLY Admin can delete companies (ignores can_delete permission)
    if (session()->get('role') !== 'admin') {
        return redirect()->to('/logistics')->with('error', '❌ Admin access required!');
    }
      $companyModel = new CompanyModel();
      $company = $companyModel->find($id);

      if (!$company) {
          return redirect()->back()->with('error', '❌ Company not found!');
      }

      // Delete company (MySQL will cascade delete related bookings)
      $companyModel->delete($id);

      // If the currently selected company is deleted, clear session
      if (session()->get('selected_company_id') == $id) {
          session()->remove(['selected_company_id', 'selected_company_name']);
      }

      return redirect()->back()->with('success', '✅ Company "' . esc($company['name']) . '" and all its associated records deleted successfully!');
  }


 public function manageBookings()
 {

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

    $invoiceNo = $shipments[0]['invoice_no'] ?? 'AWB-' . $booking['awb_no'];
    $invoiceDates = array_filter(array_column($shipments, 'invoice_date'));
    sort($invoiceDates);
    $invoiceStart = !empty($invoiceDates) ? date('d.m.Y', strtotime(reset($invoiceDates))) : date('d.m.Y', strtotime($booking['booking_date']));
    $invoiceEnd = !empty($invoiceDates) ? date('d.m.Y', strtotime(end($invoiceDates))) : date('d.m.Y', strtotime($booking['booking_date']));
    $invoicePeriod = $invoiceStart . ' TO ' . $invoiceEnd;
    $invoiceDate = !empty($invoiceDates) ? date('d.m.Y', strtotime(end($invoiceDates))) : date('d.m.Y');
    $billingBranch = $booking['origin'] ?: 'Pune';
    $modeTransport = strtoupper($booking['mode_transport'] ?: 'AIR');
    $recipientName = $booking['company_name'] ?: 'NA';
    $recipientAddress = $shipments[0]['bill_to'] ?? $shipments[0]['consignee'] ?? '';
    $recipientAddress = $recipientAddress ?: 'Address not available';

    $pdf = new \TCPDF('L', 'mm', 'A4');
    $pdf->SetCreator('Malogistics');
    $pdf->SetAuthor('Malogistics');
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(8, 8, 8);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; font-size:9px; border-collapse:collapse; font-family:helvetica;">';
    
    // Header block
    $html .= '<tr><td colspan="16" style="text-align:center;">';
    $html .= '<span style="font-size:24px; font-weight:bold; font-family:times;">M.A.LOGISTICS</span><br>';
    $html .= '<span style="font-size:10px;">Sr.No.34/2, plot No. -69, Rajkamal Bldg, Lane No.10(10A) Vidya Nagar, Tingre Nagar, Pune 411 032</span><br>';
    $html .= '<span style="font-size:10px;">Office Ph.7719868468, Mob.7620829619. Email ID : malogistics.pune@gmail.com</span>';
    $html .= '</td></tr>';
    
    // GSTIN, SAC, PAN
    $html .= '<tr>';
    $html .= '<td colspan="5" style="font-size:10px; font-weight:bold;">GSTIN : 27AICPD8922A1ZQ</td>';
    $html .= '<td colspan="5" style="font-size:10px; font-weight:bold; text-align:center;">SAC CODE : 996531</td>';
    $html .= '<td colspan="6" style="font-size:10px; font-weight:bold; text-align:center;">PAN : AICPD8922A</td>';
    $html .= '</tr>';
    
    // INVOICE title
    $html .= '<tr><td colspan="16" style="text-align:center; font-size:14px; font-weight:bold; letter-spacing:2px;">INVOICE</td></tr>';
    
    // TO Details
    $html .= '<tr>';
    $html .= '<td colspan="8" style="vertical-align:top;">';
    $html .= '<strong>TO : ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '</strong><br>';
    $html .= htmlspecialchars($recipientAddress, ENT_QUOTES, 'UTF-8');
    $html .= '</td>';
    $html .= '<td colspan="8" style="vertical-align:top;">';
    $html .= '<table cellpadding="1" cellspacing="0" style="width:100%;">';
    $html .= '<tr><td style="width:40%;"><strong>Invoice No</strong></td><td>: ' . htmlspecialchars($invoiceNo, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $html .= '<tr><td><strong>Invoice Period Date</strong></td><td>: ' . htmlspecialchars($invoicePeriod, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $html .= '<tr><td><strong>Invoice Date</strong></td><td>: ' . htmlspecialchars($invoiceDate, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $html .= '<tr><td><strong>Billing Branch</strong></td><td>: ' . htmlspecialchars($billingBranch, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $html .= '<tr><td><strong>MODE</strong></td><td>: ' . htmlspecialchars($modeTransport, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '</tr>';
    
    // Table Headers
    $html .= '<tr style="text-align:center; font-weight:bold; font-size:8px;">';
    $html .= '<td style="width:3%;">SR<br>NO</td>';
    $html .= '<td style="width:6%;">DATE</td>';
    $html .= '<td style="width:8%;">LR NO.</td>';
    $html .= '<td style="width:11%;">INVOICE NUMBER</td>';
    $html .= '<td style="width:6%;">ORIGIN</td>';
    $html .= '<td style="width:6%;">DEST</td>';
    $html .= '<td style="width:5%;">NO. OF<br>BOX</td>';
    $html .= '<td style="width:5%;">WT</td>';
    $html .= '<td style="width:5%;">RATE</td>';
    $html .= '<td style="width:5%;">Fuel<br>Surcharge</td>';
    $html .= '<td style="width:6%;">FREIGHT</td>';
    $html .= '<td style="width:7%;">Fuel<br>surcharge<br>Amount</td>';
    $html .= '<td style="width:7%;">DOCKET</td>';
    $html .= '<td style="width:6%;">PICK UP<br>CHARGE</td>';
    $html .= '<td style="width:6%;">DELIVER<br>CHARGE</td>';
    $html .= '<td style="width:8%;">TAXABLE<br>AMOUNT</td>';
    $html .= '</tr>';

    $serial = 1;
    $totalBoxes = 0;
    $totalWt = 0;
    $totalTaxable = 0;

    foreach ($shipments as $item) {
        $date = !empty($item['invoice_date']) ? date('d.m.y', strtotime($item['invoice_date'])) : '-';
        $lrNo = $item['docket_no'] ?: '-';
        $invoiceNumber = $item['invoice_no'] ?: '-';
        $origin = $booking['origin'];
        $destination = $booking['destination'];
        $boxes = intval($item['pieces'] ?? 1);
        $wt = floatval($item['actual_weight'] ?? 0);
        $rate = floatval($item['rate'] ?? 0);
        $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
        $freight = $wt * $rate;
        $fuelAmt = $wt * $fuelSur;
        $docket = floatval($item['docket_charges'] ?? 0);
        $pickup = floatval($item['pickup_charges'] ?? 0);
        $delivery = floatval($item['delivery_charges'] ?? 0);
        $taxable = $freight + $fuelAmt + $docket + $pickup + $delivery;

        $totalBoxes += $boxes;
        $totalWt += $wt;
        $totalTaxable += $taxable;

        $html .= '<tr style="font-size:8px;">';
        $html .= '<td style="text-align:center;">' . $serial . '</td>';
        $html .= '<td style="text-align:center;">' . $date . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($lrNo, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($invoiceNumber, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($origin, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:center;">' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td style="text-align:center;">' . $boxes . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$wt . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$rate . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$fuelSur . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$freight . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$fuelAmt . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$docket . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$pickup . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$delivery . '</td>';
        $html .= '<td style="text-align:center;">' . (float)$taxable . '</td>';
        $html .= '</tr>';

        $serial++;
    }

    $html .= '<tr style="font-size:8px;">';
    $html .= '<td colspan="6"></td>';
    $html .= '<td style="text-align:center; font-weight:bold;">' . $totalBoxes . '</td>';
    $html .= '<td style="text-align:center; font-weight:bold;">' . (float)$totalWt . '</td>';
    $html .= '<td></td>';
    $html .= '<td></td>';
    $html .= '<td colspan="5" style="text-align:center; font-weight:bold;">TAXABLE AMOUNT</td>';
    $html .= '<td style="text-align:center; font-weight:bold;">' . round($totalTaxable) . '</td>';
    $html .= '</tr>';

    $cgst = round($totalTaxable * 0.09);
    $sgst = round($totalTaxable * 0.09);
    $igst = 0;
    $netPayable = round($totalTaxable + $cgst + $sgst + $igst);

    $html .= '<tr style="font-size:8px; font-weight:bold;">';
    $html .= '<td colspan="10" rowspan="4"></td>';
    $html .= '<td colspan="5" style="text-align:left;">C.GST - 9%</td>';
    $html .= '<td style="text-align:center;">' . $cgst . '</td>';
    $html .= '</tr>';

    $html .= '<tr style="font-size:8px; font-weight:bold;">';
    $html .= '<td colspan="5" style="text-align:left;">S.GST - 9%</td>';
    $html .= '<td style="text-align:center;">' . $sgst . '</td>';
    $html .= '</tr>';

    $html .= '<tr style="font-size:8px; font-weight:bold;">';
    $html .= '<td colspan="5" style="text-align:left;">I.GST - 18%</td>';
    $html .= '<td style="text-align:center;">' . $igst . '</td>';
    $html .= '</tr>';

    $html .= '<tr style="font-size:8px; font-weight:bold;">';
    $html .= '<td colspan="5" style="text-align:left;">NET PAYABLE AMOUNT</td>';
    $html .= '<td style="text-align:center;">' . $netPayable . '</td>';
    $html .= '</tr>';

    $html .= '<tr><td colspan="16" style="font-size:10px; font-weight:bold;">';
    $html .= 'Rs. (In Word) ' . strtoupper($this->formatAmountInWords($netPayable)) . ' RUPEES ONLY ./-';
    $html .= '</td></tr>';

    $html .= '<tr>';
    $html .= '<td colspan="11" style="vertical-align:top; font-size:9px;">';
    $html .= '<b>Service Catagory : Courier & Cargo</b><br><br>';
    $html .= '<b>Terms & Conditions :</b><br>';
    $html .= 'i. Difference if any may be notified within 7 days of receipt of bills.<br>';
    $html .= 'ii. Subject to Pune Jurisdiction<br>';
    $html .= 'iii. E & O.E.<br>';
    $html .= 'iv. Draw Cheque in favour of "MA LOGISTICS"<br>';
    $html .= 'v. For NEFT/RTGS: Bank Details are as follows :-<br>';
    $html .= '&nbsp;&nbsp;&nbsp;Bank Name : AXIS BANK, &nbsp;&nbsp; Branch: Bund Garden,Pune<br>';
    $html .= '&nbsp;&nbsp;&nbsp;Current Account No : 914020014273896<br>';
    $html .= '&nbsp;&nbsp;&nbsp;IFSC : UTIB0000073';
    $html .= '</td>';
    $html .= '<td colspan="5" style="vertical-align:top; text-align:center; font-size:10px; font-weight:bold;">';
    $html .= 'For M.A LOGISTICS<br><br><br><br><br><br><br><br>Authorised signatory';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('AWB-' . $booking['awb_no'] . '.pdf', 'D');
}

private function formatAmountInWords($amount)
{
    $whole = floor($amount);
    $fraction = round(($amount - $whole) * 100);
    $words = $this->numberToWords($whole) . ' Rupees';
    if ($fraction > 0) {
        $words .= ' and ' . $this->numberToWords($fraction) . ' Paise';
    }
    return $words;
}

private function numberToWords($number)
{
    $words = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy',
        80 => 'eighty', 90 => 'ninety'
    ];

    if ($number < 21) {
        return $words[$number];
    }
    if ($number < 100) {
        $tens = intval($number / 10) * 10;
        $units = $number % 10;
        return $words[$tens] . ($units ? ' ' . $words[$units] : '');
    }
    if ($number < 1000) {
        $hundreds = intval($number / 100);
        $remainder = $number % 100;
        return $words[$hundreds] . ' hundred' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
    }
    if ($number < 100000) {
        $thousands = intval($number / 1000);
        $remainder = $number % 1000;
        return $this->numberToWords($thousands) . ' thousand' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
    }
    if ($number < 10000000) {
        $lakhs = intval($number / 100000);
        $remainder = $number % 100000;
        return $this->numberToWords($lakhs) . ' lakh' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
    }
    $crores = intval($number / 10000000);
    $remainder = $number % 10000000;
    return $this->numberToWords($crores) . ' crore' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
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