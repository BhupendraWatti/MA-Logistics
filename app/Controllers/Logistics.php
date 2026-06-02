<?php
namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\BookingModel;
use App\Models\ShipmentItemModel;
use App\Models\SalesChargeModel;
use App\Models\CustomerModel;
use App\Models\TransporterModel;
use App\Models\DriverModel;
use App\Models\AirlineModel;
use App\Models\LookupValueModel;
use App\Models\SystemSettingsModel;

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
                return redirect()->to('/logistics')->with('error', 'Create permission denied!');
            }
            break;
        case 'edit':
            if (!($permissions['can_edit'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Edit permission denied!');
            }
            break;
        case 'delete':
            if (!($permissions['can_delete'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Delete permission denied!');
            }
            break;
    }
    return true;
}


public function index()
{
    $companyId = session()->get('selected_company_id');
    
    // Redirect to company selection if no company is selected
    if (!$companyId) {
        return redirect()->to('/company-selection');
    }

    $data = [
        'user' => session()->get(),
        'permissions' => session()->get('permissions') ?? [],
        'company_name' => session()->get('selected_company_name'),
        'company_id' => $companyId
    ];

    $bookingModel = new BookingModel();
    $data['stats'] = $bookingModel->getCompanyStats($companyId);
    
    // Fetch recent bookings and append customer name + weight from shipment_items
    $recent_bookings = $bookingModel->getCompanyBookings($companyId, 10);
    
    foreach ($recent_bookings as &$b) {
        $db = \Config\Database::connect();
        
        // Fetch aggregated shipment details for docket, customer, and consignee
        $shipDetails = $db->table('shipment_items')
                          ->select('GROUP_CONCAT(DISTINCT docket_no SEPARATOR ", ") AS dockets,
                                    GROUP_CONCAT(DISTINCT customer_name SEPARATOR ", ") AS customers,
                                    GROUP_CONCAT(DISTINCT consignee SEPARATOR ", ") AS consignees')
                          ->where('booking_id', $b['id'])
                          ->get()->getRowArray();

        $b['docket_no'] = !empty($shipDetails['dockets']) ? $shipDetails['dockets'] : '-';
        $b['customer_name'] = !empty($shipDetails['customers']) ? $shipDetails['customers'] : 'Unknown';
        $b['consignee'] = !empty($shipDetails['consignees']) ? $shipDetails['consignees'] : '-';
        
        // Sum chargeable weight
        $wgtRow = $db->table('shipment_items')
                     ->selectSum('final_chargeable_weight', 'total_weight')
                     ->where('booking_id', $b['id'])
                     ->get()->getRowArray();

        $b['total_weight'] = $wgtRow['total_weight'] ?? 0;
    }
    
    $data['recent_bookings'] = $recent_bookings;

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
            ->with('error', 'You do not have permission to create bookings!');
    }
    
    $companyId = (int) session()->get('selected_company_id');
    $data['user'] = session()->get();
    $data['selected_company_id']   = $companyId;
    $data['selected_company_name'] = session()->get('selected_company_name');
    
    if (!$companyId) {
        return redirect()->to('/company-selection')->with('error', 'Please select company first!');
    }
    
    // Master data for dropdowns
    $data['customers']    = (new CustomerModel())->getByCompany($companyId);
    $data['transporters'] = (new TransporterModel())->getByCompany($companyId);
    $data['drivers']      = (new DriverModel())->getByCompany($companyId);
    $data['airlines']     = (new AirlineModel())->getByCompany($companyId);
    $data['lookups']      = [
        'origin'            => (new LookupValueModel())->getByType($companyId, 'origin'),
        'destination'       => (new LookupValueModel())->getByType($companyId, 'destination'),
        'mode'              => (new LookupValueModel())->getByType($companyId, 'mode'),
        'material_type'     => (new LookupValueModel())->getByType($companyId, 'material_type'),
        'material_category' => (new LookupValueModel())->getByType($companyId, 'material_category'),
        'payment_type'      => (new LookupValueModel())->getByType($companyId, 'payment_type'),
    ];
    $data['volumetric_formula'] = (new SystemSettingsModel())->getSetting($companyId, 'volumetric_divider', 6000);
    $data['company'] = (new CompanyModel())->find($companyId);
    
    return view('logistics/booking_form', $data);
}

  public function store()
  {
    $this->checkPermission('can_create');
    
    $bookingService = new \App\Services\BookingService();
    
    try {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) return redirect()->to('/company-selection');
        $bookingService->createBooking($this->request->getPost(), session()->get('user_id'), $companyId);
        $awb_no = $this->request->getPost('awb_no');
        return redirect()->to('/logistics')->with('success', 'Booking created successfully! AWB: ' . $awb_no);
    } catch (\Throwable $e) {
        log_message('error', '[Logistics Store Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        $msg = $e->getMessage();
        $isSystemError = ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) ||
                         ($e instanceof \mysqli_sql_exception) ||
                         (strpos($msg, 'SQL') !== false) ||
                         (strpos($msg, 'database') !== false) ||
                         (strpos($msg, 'query') !== false) ||
                         (strpos($msg, 'Access denied') !== false) ||
                         (strpos($msg, 'Connection') !== false);
                         
        $userMessage = $isSystemError ? 'A secure database or system error occurred. Technical logs have been updated safely.' : $msg;
        return redirect()->back()->with('error', $userMessage);
    }
  }


  public function view($id)
  {
    $bookingModel = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel = new SalesChargeModel();
    
    $booking = $bookingModel->getFullBooking($id);
    if (!$booking || $booking['company_id'] != session()->get('selected_company_id')) {
        return redirect()->back()->with('error', 'Booking not found or access denied!');
    }
    
    $shipments = $shipmentModel->where('booking_id', $id)->findAll();
    $sales = $salesModel->where('booking_id', $id)->first();
    
    $companyData = (new \App\Models\CompanyModel())->find(session()->get('selected_company_id'));
    
    $data = [
        'booking' => $booking,
        'shipments' => $shipments,
        'sales' => $sales,
        'company' => $companyData,
        'user' => session()->get()
    ];
    
    return view('logistics/view_booking', $data);
  }


public function edit($id)
{
    $this->checkPermission('can_edit');
    
    $bookingModel  = new BookingModel();
    $shipmentModel = new ShipmentItemModel();
    $salesModel    = new SalesChargeModel();
    $companyId     = (int) session()->get('selected_company_id');
    
    $booking = $bookingModel->getFullBooking($id);
    if (!$booking || $booking['company_id'] != $companyId) {
        return redirect()->back()->with('error', 'Booking not found or access denied!');
    }
    
    $data = [
        'booking'              => $booking,
        'shipments'            => $shipmentModel->where('booking_id', $id)->findAll(),
        'sales'                => $salesModel->where('booking_id', $id)->first(),
        'isEdit'               => true,
        'bookingId'            => $id,
        'selected_company_id'  => $companyId,
        'selected_company_name'=> session()->get('selected_company_name'),
        'user'                 => session()->get(),
        // Master data for dropdowns
        'customers'    => (new CustomerModel())->getByCompany($companyId),
        'transporters' => (new TransporterModel())->getByCompany($companyId),
        'drivers'      => (new DriverModel())->getByCompany($companyId),
        'airlines'     => (new AirlineModel())->getByCompany($companyId),
        'lookups'      => [
            'origin'            => (new LookupValueModel())->getByType($companyId, 'origin'),
            'destination'       => (new LookupValueModel())->getByType($companyId, 'destination'),
            'mode'              => (new LookupValueModel())->getByType($companyId, 'mode'),
            'material_type'     => (new LookupValueModel())->getByType($companyId, 'material_type'),
            'material_category' => (new LookupValueModel())->getByType($companyId, 'material_category'),
            'payment_type'      => (new LookupValueModel())->getByType($companyId, 'payment_type'),
        ],
        'volumetric_formula' => (new SystemSettingsModel())->getSetting($companyId, 'volumetric_divider', 6000),
        'company' => (new CompanyModel())->find($companyId),
    ];
    
    return view('logistics/booking_form', $data);
}


  public function update($id)
  {
    $this->checkPermission('can_edit');
    
    $bookingService = new \App\Services\BookingService();
    
    try {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) return redirect()->to('/company-selection');
        
        // SECURITY FIX: Prevent IDOR / Booking hijacking
        $existing = (new BookingModel())->find($id);
        if (!$existing || $existing['company_id'] != $companyId) {
            return redirect()->back()->with('error', 'Booking not found or access denied!');
        }
        
        $bookingService->updateBooking($id, $this->request->getPost(), session()->get('user_id'), $companyId);
        $awb_no = $this->request->getPost('awb_no');
        return redirect()->to('/logistics')->with('success', 'Booking updated successfully! AWB: ' . $awb_no);
    } catch (\Throwable $e) {
        log_message('error', '[Logistics Update Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        $msg = $e->getMessage();
        $isSystemError = ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) ||
                         ($e instanceof \mysqli_sql_exception) ||
                         (strpos($msg, 'SQL') !== false) ||
                         (strpos($msg, 'database') !== false) ||
                         (strpos($msg, 'query') !== false) ||
                         (strpos($msg, 'Access denied') !== false) ||
                         (strpos($msg, 'Connection') !== false);
                         
        $userMessage = $isSystemError ? 'A secure database or system error occurred. Technical logs have been updated safely.' : $msg;
        return redirect()->back()->with('error', $userMessage);
    }
  }


  public function delete($id)
  {
    $this->checkPermission('can_delete');
    
    $bookingModel = new BookingModel();
    $booking = $bookingModel->find($id);
    
    if (!$booking || $booking['company_id'] != session()->get('selected_company_id')) {
        session_write_close();
        return $this->response->setJSON([
            'success' => false, 
            'message' => 'Booking not found or access denied. ID: ' . $id . ', Found: ' . ($booking ? 'YES' : 'NO') . ', Booking Co: ' . ($booking ? $booking['company_id'] : 'N/A') . ', Session Co: ' . (session()->get('selected_company_id') ?? 'NULL')
        ]);
    }
    
    // Cascade delete shipments and sales
    (new \App\Models\ShipmentItemModel())->where('booking_id', $id)->delete();
    (new \App\Models\SalesChargeModel())->where('booking_id', $id)->delete();
    
    $bookingModel->delete($id);
    
    session_write_close();
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
                ->with('success', 'Welcome to ' . $company['name'] . ' Dashboard!');
        }
    }
    
    return redirect()->back()
        ->with('error', 'Invalid company selection!');
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
        return redirect()->to('/logistics')->with('error', 'Admin access required!');
    }

      $name = $this->request->getPost('name');
      if (empty($name)) {
          return redirect()->back()->with('error', 'Company name is required!');
      }

      $companyModel = new CompanyModel();
      // Check if already exists
      if ($companyModel->where('name', $name)->first()) {
          return redirect()->back()->with('error', 'Company already exists!');
      }

      $companyModel->insert(['name' => $name]);
      return redirect()->back()->with('success', 'Company "' . esc($name) . '" created successfully!');
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
        return redirect()->to('/logistics')->with('error', 'Admin access required!');
    }
      $companyModel = new CompanyModel();
      $company = $companyModel->find($id);

      if (!$company) {
          return redirect()->back()->with('error', 'Company not found!');
      }

      // Delete company (MySQL will cascade delete related bookings)
      $companyModel->delete($id);

      // If the currently selected company is deleted, clear session
      if (session()->get('selected_company_id') == $id) {
          session()->remove(['selected_company_id', 'selected_company_name']);
      }

      return redirect()->back()->with('success', 'Company "' . esc($company['name']) . '" and all its associated records deleted successfully!');
  }


 public function manageBookings()
 {
    $companyId = session()->get('selected_company_id');
    if (!$companyId) {
        return redirect()->to('/company-selection');
    }
    
    $data = [
        'company_name' => session()->get('selected_company_name'),
        'company_id' => $companyId,
        'user' => session()->get(),
        'permissions' => session()->get('permissions') ?? []
    ];
    
    return view('logistics/manage_bookings', $data);
 }

 public function ajaxDatatable()
 {
    $companyId = session()->get('selected_company_id');
    if (!$companyId) {
        return $this->response->setJSON(['error' => 'No company selected']);
    }

    $post = $this->request->getPost();
    $draw = (int) ($post['draw'] ?? 1);
    $start = (int) ($post['start'] ?? 0);
    $length = (int) ($post['length'] ?? 10);
    $searchValue = $post['search']['value'] ?? '';

    $db = \Config\Database::connect();
    $builder = $db->table('bookings b');

    // Select core columns + aggregated columns
    // Select core columns + optimized aggregated joins
    $builder->select("
        b.id,
        b.awb_no,
        b.booking_date,
        b.created_at,
        b.origin,
        b.destination,
        b.status,
        b.total_pieces,
        COALESCE(si.total_weight, 0) AS total_weight,
        COALESCE(sc.total_amount, 0) AS total_amount
    ");
    
    // Aggregated Subqueries via Hash Joins
    $builder->join("(SELECT booking_id, SUM(final_chargeable_weight) AS total_weight FROM shipment_items GROUP BY booking_id) si", "si.booking_id = b.id", "left");
    $builder->join("(SELECT booking_id, ((COALESCE(rate,0) * COALESCE(weight,0)) + COALESCE(ddc,0) + COALESCE(ssc,0) + COALESCE(btc,0) + COALESCE(flc,0) + COALESCE(doc,0) + COALESCE(inbound_tsp,0) + COALESCE(outbound_tsp,0) + COALESCE(tcp,0) + COALESCE(utility_charges,0) + COALESCE(xray_charges,0) + COALESCE(ado,0) + COALESCE(awb_fees_agent,0) + COALESCE(awb_fees_carrier,0) + COALESCE(admin_charges,0) + COALESCE(delivery_order_charges,0) + COALESCE(inbound_handling,0) + COALESCE(inbound_storage,0) + COALESCE(outbound_storage,0) + COALESCE(misc_charges,0)) AS total_amount FROM sales_charges) sc", "sc.booking_id = b.id", "left");
    
    $builder->where('b.company_id', $companyId);

    $userRole = session()->get('role');
    /* Branch filter temporarily suspended
    if ($userRole !== 'admin') {
        $branchId = session()->get('branch_id') ?? 1;
        $builder->where('b.branch_id', $branchId);
    }
    */

    // Total records
    $totalRecords = $builder->countAllResults(false);

    // Search
    if (!empty($searchValue)) {
        $builder->groupStart()
                ->like('b.awb_no', $searchValue)
                ->orLike('b.origin', $searchValue)
                ->orLike('b.destination', $searchValue)
                ->orLike('b.status', $searchValue)
                ->groupEnd();
    }
    $filteredRecords = $builder->countAllResults(false);

    // Pagination
    if ($length != -1) {
        $builder->limit($length, $start);
    }

    $builder->orderBy('b.id', 'desc');

    $data = $builder->get()->getResultArray();

    // Permissions
    $permissions = session()->get('permissions') ?? [];
    $canEdit = $permissions['can_edit'] ?? 0;
    $canDelete = $permissions['can_delete'] ?? 0;

    // Formatting
    foreach ($data as &$row) {
        $row['total_weight'] = number_format((float)$row['total_weight'], 1);
        $row['total_amount'] = number_format((float)$row['total_amount'], 0);
        $time = !empty($row['created_at']) ? date('H:i', strtotime($row['created_at'])) : '00:00';
        $row['booking_date'] = date('d-M-Y', strtotime($row['booking_date'])) . ' ' . $time;
        $row['can_edit'] = $canEdit;
        $row['can_delete'] = $canDelete;

        // Fetch aggregated shipment details for docket, customer, and consignee
        $shipDetails = $db->table('shipment_items')
                          ->select('GROUP_CONCAT(DISTINCT docket_no SEPARATOR ", ") AS dockets,
                                    GROUP_CONCAT(DISTINCT customer_name SEPARATOR ", ") AS customers,
                                    GROUP_CONCAT(DISTINCT consignee SEPARATOR ", ") AS consignees')
                          ->where('booking_id', $row['id'])
                          ->get()->getRowArray();

        $row['docket_no'] = !empty($shipDetails['dockets']) ? $shipDetails['dockets'] : '-';
        $row['customer_name'] = !empty($shipDetails['customers']) ? $shipDetails['customers'] : 'Unknown';
        $row['consignee'] = !empty($shipDetails['consignees']) ? $shipDetails['consignees'] : '-';
    }

    session_write_close(); // Prevent database session write shutdown errors overriding 200 OK status
    return $this->response->setJSON([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
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
    
    if (!$booking || $booking['company_id'] != session()->get('selected_company_id')) {
        return redirect()->back()->with('error', 'Booking not found or access denied!');
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



    try {
        $pdf = new \TCPDF('L', 'mm', 'A4');
        $pdf->SetCreator('Malogistics');
        $pdf->SetAuthor('Malogistics');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(8, 8, 8);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);

    $serial = 1;
    $totalBoxes = 0;
    $totalWt = 0;
    $totalTaxable = 0;
    $shipmentRows = [];

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

        $shipmentRows[] = [
            'serial' => $serial,
            'date' => $date,
            'lrNo' => $lrNo,
            'invoiceNumber' => $invoiceNumber,
            'origin' => $origin,
            'destination' => $destination,
            'boxes' => $boxes,
            'wt' => $wt,
            'rate' => $rate,
            'fuelSur' => $fuelSur,
            'freight' => $freight,
            'fuelAmt' => $fuelAmt,
            'docket' => $docket,
            'pickup' => $pickup,
            'delivery' => $delivery,
            'taxable' => $taxable
        ];
        $serial++;
    }

    // Load GST rates from company master (not hardcoded)
    $companyData = (new CompanyModel())->select('name, address, email, mobile, gstin, pan, sac_code, cgst_rate, sgst_rate, igst_rate, terms_conditions, signature_path')
                                       ->find($booking['company_id']);

    // Dynamic settings from booking with fallbacks to company settings
    $gstin = !empty($booking['gstin']) ? $booking['gstin'] : ($companyData['gstin'] ?? '');
    $pan = !empty($booking['pan']) ? $booking['pan'] : ($companyData['pan'] ?? '');
    $sacCode = !empty($booking['sac_code']) ? $booking['sac_code'] : ($companyData['sac_code'] ?? '');
    
    $cgstRate = isset($booking['cgst_rate']) ? (float)$booking['cgst_rate'] : (float)($companyData['cgst_rate'] ?? 9);
    $sgstRate = isset($booking['sgst_rate']) ? (float)$booking['sgst_rate'] : (float)($companyData['sgst_rate'] ?? 9);
    $igstRate = isset($booking['igst_rate']) ? (float)$booking['igst_rate'] : (float)($companyData['igst_rate'] ?? 9);
    
    $signaturePath = !empty($booking['signature_path']) ? $booking['signature_path'] : ($companyData['signature_path'] ?? '');
                                       
    // BUG FIX: Only apply GST if the booking has gst_applied checked
    if (isset($booking['gst_applied']) && $booking['gst_applied'] == 1) {
        $cgst = round($totalTaxable * $cgstRate / 100);
        $sgst = round($totalTaxable * $sgstRate / 100);
        $igst = round($totalTaxable * $igstRate / 100);
    } else {
        $cgst = 0;
        $sgst = 0;
        $igst = 0;
    }
    
    $netPayable = round($totalTaxable + $cgst + $sgst + $igst);

    $viewData = [
        'company' => $companyData,
        'recipientName' => $recipientName,
        'recipientAddress' => $recipientAddress,
        'invoiceNo' => $invoiceNo,
        'invoicePeriod' => $invoicePeriod,
        'invoiceDate' => $invoiceDate,
        'billingBranch' => $billingBranch,
        'modeTransport' => $modeTransport,
        'shipmentRows' => $shipmentRows,
        'totalBoxes' => $totalBoxes,
        'totalWt' => $totalWt,
        'totalTaxable' => $totalTaxable,
        'cgst' => $cgst,
        'sgst' => $sgst,
        'igst' => $igst,
        'cgstRate' => $cgstRate,
        'sgstRate' => $sgstRate,
        'igstRate' => $igstRate,
        'netPayable' => $netPayable,
        'amountInWords' => $this->formatAmountInWords($netPayable),
        // Dynamic overrides per booking
        'booking' => $booking,
        'bookingGstin' => $gstin,
        'bookingPan' => $pan,
        'bookingSacCode' => $sacCode,
        'bookingSignaturePath' => $signaturePath
    ];

    $html = view('pdfs/invoice', $viewData);

        $pdf->writeHTML($html, true, false, true, false, '');
        
        // BUG FIX: Prevent CI4 from corrupting PDF headers/output
        if (ob_get_length()) {
            ob_end_clean();
        }
        $this->response->setContentType('application/pdf'); // Force header just in case
        $pdfFileName = 'AWB_' . ($booking['awb_no'] ?: $invoiceNo) . '.pdf';
        $pdf->Output($pdfFileName, 'D');
        exit;
    } catch (\Exception $e) {
        log_message('error', '[PDF Export Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        if (strpos($e->getMessage(), 'alpha channel') !== false || strpos($e->getMessage(), 'Imagick or GD') !== false) {
            return redirect()->back()->with('error', 'Your server does not support transparent PNG signatures. Please go to Company Settings and upload a JPG image or draw your signature manually.');
        }
        
        $msg = $e->getMessage();
        $isSystemError = ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException) ||
                         ($e instanceof \mysqli_sql_exception) ||
                         (strpos($msg, 'SQL') !== false) ||
                         (strpos($msg, 'database') !== false) ||
                         (strpos($msg, 'query') !== false) ||
                         (strpos($msg, 'Connection') !== false);
                         
        $userMessage = $isSystemError ? 'A secure database or system error occurred. Technical logs have been updated safely.' : 'PDF Generation failed: ' . $msg;
        return redirect()->back()->with('error', $userMessage);
    }
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

public function export()
{
    $db = \Config\Database::connect();
    $companyId = session()->get('selected_company_id');
    
    if (!$companyId) {
        return redirect()->back()->with('error', 'Please select a company first.');
    }
    
    $builder = $db->table('shipment_items s');
    $builder->select('
        b.awb_no, b.booking_date, b.origin, b.destination, b.mode_transport,
        s.docket_no, s.customer_name as shipper, s.consignee, 
        s.actual_weight, s.calculated_chargeable_weight, s.final_chargeable_weight, s.pieces,
        c.total_amount as parent_sales_charges
    ');
    $builder->join('bookings b', 'b.id = s.booking_id', 'left');
    $builder->join('sales_charges c', 'c.booking_id = b.id', 'left');
    $builder->where('b.company_id', $companyId);
    $builder->orderBy('b.id', 'DESC');
    
    $query = $builder->get();
    
    $filename = 'Malogistics_Export_' . date('Y-m-d_H-i') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, [
        'AWB No', 'Booking Date', 'Origin', 'Destination', 'Mode',
        'Docket No', 'Shipper', 'Consignee', 
        'Actual Weight', 'Calculated Chargeable Wt', 'Final Chargeable Wt', 'Pieces',
        'Parent Sales Charges'
    ]);
    
    foreach ($query->getResultArray() as $row) {
        fputcsv($output, [
            $row['awb_no'],
            $row['booking_date'],
            $row['origin'],
            $row['destination'],
            $row['mode_transport'],
            $row['docket_no'],
            $row['shipper'],
            $row['consignee'],
            $row['actual_weight'],
            $row['calculated_chargeable_weight'],
            $row['final_chargeable_weight'],
            $row['pieces'],
            $row['parent_sales_charges']
        ]);
    }
    
    fclose($output);
    exit;
}

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
    $headers = ['SR NO', 'AWB NO', 'DOCKET NO', 'DATE', 'COMPANY', 'ORIGIN', 'DESTINATION', 'STATUS', 'PIECES', 
               'INVOICE NO', 'CUSTOMER', 'BILL TO', 'CONSIGNEE', 'WEIGHT', 'RATE', 
               'FREIGHT', 'FUEL SUR', 'FUEL AMT', 'DOCKET CHARGES', 'PICKUP', 'DELIVERY', 'TAXABLE'];
    
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
                $item['docket_no'] ?? '',  // ← Actual alphanumeric Docket Number
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
                $dock,         // ← Aligned with "DOCKET CHARGES" column
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


    public function deleteSignature($bookingId)
    {
        $this->checkPermission('can_edit');
        
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        
        if (!$booking || $booking['company_id'] != session()->get('selected_company_id')) {
            return redirect()->back()->with('error', 'Booking not found or access denied!');
        }
        
        if (!empty($booking['signature_path']) && file_exists(FCPATH . $booking['signature_path'])) {
            unlink(FCPATH . $booking['signature_path']);
        }
        
        $bookingModel->update($bookingId, ['signature_path' => null]);
        
        return redirect()->to('/logistics/edit/' . $bookingId)->with('success', 'Booking signature deleted successfully!');
    }


// =========== Export Excel End ============





}