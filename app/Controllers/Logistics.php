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
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Permission denied',
                ]);
            }
            return redirect()->to('/logistics')->with('error', 'Permission denied!');
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
    unset($b);
    
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
    $data['previous_bookings'] = (new BookingModel())->select('id, awb_no')->where('company_id', $companyId)->orderBy('id', 'DESC')->findAll();
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
    $perm = $this->checkPermission('can_create');
    if ($perm !== true) {
        return $perm;
    }
    
    $bookingService = new \App\Services\BookingService();
    
    try {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) return redirect()->to('/company-selection');
        $bookingId = $bookingService->createBooking($this->request->getPost(), session()->get('user_id'), $companyId);
        $awb_no = $this->request->getPost('awb_no');
        
        session()->setFlashdata('export_pdf_booking_id', $bookingId);
        session()->setFlashdata('export_pdf_action', 'create');

        $redirectTo = $this->request->getPost('redirect_to_booking_id');
        if (!empty($redirectTo)) {
            return redirect()->to('/logistics/edit/' . $redirectTo)->with('success', 'Booking saved as Draft successfully.');
        }
        
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
        return redirect()->back()->withInput()->with('error', $userMessage);
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
    $trackingHistory = (new \App\Models\TrackingHistoryModel())
        ->where('booking_id', $id)
        ->orderBy('event_date', 'DESC')
        ->orderBy('event_time', 'DESC')
        ->findAll();

    $companyData = (new \App\Models\CompanyModel())->find(session()->get('selected_company_id'));

    $data = [
        'booking' => $booking,
        'shipments' => $shipments,
        'sales' => $sales,
        'trackingHistory' => $trackingHistory,
        'company' => $companyData,
        'user' => session()->get()
    ];
    
    return view('logistics/view_booking', $data);
  }


public function edit($id)
{
    $perm = $this->checkPermission('can_edit');
    if ($perm !== true) {
        return $perm;
    }
    
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
        'previous_bookings' => $bookingModel->select('id, awb_no')->where('company_id', $companyId)->orderBy('id', 'DESC')->findAll(),
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
        
        session()->setFlashdata('export_pdf_booking_id', $id);
        session()->setFlashdata('export_pdf_action', 'update');

        $redirectTo = $this->request->getPost('redirect_to_booking_id');
        if (!empty($redirectTo)) {
            return redirect()->to('/logistics/edit/' . $redirectTo)->with('success', 'Booking updated as Draft successfully.');
        }
        
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
        return redirect()->back()->withInput()->with('error', $userMessage);
    }
  }


  public function delete($id = null)
  {
    $perm = $this->checkPermission('can_delete');
    if ($perm !== true) {
        return $perm;
    }

    $id = (int) $id;
    if ($id < 1) {
        session_write_close();
        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'status'  => 'error',
            'message' => 'Invalid booking id',
        ]);
    }

    $bookingModel = new BookingModel();
    $booking = $bookingModel->find($id);

    if (!$booking || (int) $booking['company_id'] !== (int) session()->get('selected_company_id')) {
        session_write_close();
        return $this->response->setStatusCode(404)->setJSON([
            'success' => false,
            'status'  => 'error',
            'message' => 'Booking not found or access denied',
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
    // ONLY Admin can create companies (ignores can_create permission)
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
    // ONLY Admin can delete companies (ignores can_delete permission)
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

    // Fast counts on bookings only (avoid double COUNT with heavy joins)
    $totalRecords = $db->table('bookings')->where('company_id', $companyId)->countAllResults();
    if ($searchValue !== '') {
        $filteredRecords = $db->table('bookings')
            ->where('company_id', $companyId)
            ->groupStart()
            ->like('awb_no', $searchValue)
            ->orLike('origin', $searchValue)
            ->orLike('destination', $searchValue)
            ->orLike('status', $searchValue)
            ->groupEnd()
            ->countAllResults();
    } else {
        $filteredRecords = $totalRecords;
    }

    $builder = $db->table('bookings b');
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
    $builder->join("(SELECT booking_id, SUM(final_chargeable_weight) AS total_weight FROM shipment_items GROUP BY booking_id) si", "si.booking_id = b.id", "left");
    $builder->join("(SELECT booking_id, ((COALESCE(rate,0) * COALESCE(weight,0)) + COALESCE(ddc,0) + COALESCE(ssc,0) + COALESCE(btc,0) + COALESCE(flc,0) + COALESCE(doc,0) + COALESCE(inbound_tsp,0) + COALESCE(outbound_tsp,0) + COALESCE(tcp,0) + COALESCE(utility_charges,0) + COALESCE(xray_charges,0) + COALESCE(ado,0) + COALESCE(awb_fees_agent,0) + COALESCE(awb_fees_carrier,0) + COALESCE(admin_charges,0) + COALESCE(delivery_order_charges,0) + COALESCE(inbound_handling,0) + COALESCE(inbound_storage,0) + COALESCE(outbound_storage,0) + COALESCE(misc_charges,0)) AS total_amount FROM sales_charges) sc", "sc.booking_id = b.id", "left");
    $builder->where('b.company_id', $companyId);

    if ($searchValue !== '') {
        $builder->groupStart()
            ->like('b.awb_no', $searchValue)
            ->orLike('b.origin', $searchValue)
            ->orLike('b.destination', $searchValue)
            ->orLike('b.status', $searchValue)
            ->groupEnd();
    }

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

    // Fetch audit logs for loaded bookings in a single query to preserve speed and optimization
    $bookingIds = array_column($data, 'id');
    $bookingLogs = [];
    if (!empty($bookingIds)) {
        $bookingModel = new BookingModel();
        $bookingLogs = $bookingModel->getLatestAuditActions($bookingIds);
    }

    // Batch-load shipment labels (one query per page, not per row)
    $shipByBooking = [];
    if (!empty($bookingIds)) {
        $shipRows = $db->table('shipment_items')
            ->select("booking_id,
                GROUP_CONCAT(DISTINCT docket_no SEPARATOR ', ') AS dockets,
                GROUP_CONCAT(DISTINCT customer_name SEPARATOR ', ') AS customers,
                GROUP_CONCAT(DISTINCT consignee SEPARATOR ', ') AS consignees")
            ->whereIn('booking_id', $bookingIds)
            ->groupBy('booking_id')
            ->get()
            ->getResultArray();
        foreach ($shipRows as $sr) {
            $shipByBooking[(int) $sr['booking_id']] = $sr;
        }
    }

    foreach ($data as &$row) {
        $row['total_weight'] = number_format((float) $row['total_weight'], 1);
        $row['total_amount'] = number_format((float) $row['total_amount'], 0);
        $time = !empty($row['created_at']) ? date('H:i', strtotime($row['created_at'])) : '00:00';
        $row['booking_date'] = date('d-M-Y', strtotime($row['booking_date'])) . ' ' . $time;
        $row['can_edit'] = $canEdit;
        $row['can_delete'] = $canDelete;
        $row['last_action'] = $bookingLogs[$row['id']] ?? null;

        $shipDetails = $shipByBooking[(int) $row['id']] ?? [];
        $row['docket_no'] = !empty($shipDetails['dockets']) ? $shipDetails['dockets'] : '-';
        $row['customer_name'] = !empty($shipDetails['customers']) ? $shipDetails['customers'] : 'Unknown';
        $row['consignee'] = !empty($shipDetails['consignees']) ? $shipDetails['consignees'] : '-';
    }
    unset($row);

    session_write_close(); // Prevent database session write shutdown errors overriding 200 OK status
    return $this->response->setJSON([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
 }





// Export PDF — thin controller; all business logic delegated to InvoiceService + PdfInvoiceGenerator
public function exportPdf($id)
{
    $bookingModel   = new BookingModel();
    $shipmentModel  = new ShipmentItemModel();
    $invoiceService = new \App\Services\InvoiceService();
    $pdfGenerator   = new \App\Services\PdfInvoiceGenerator();

    $booking   = $bookingModel->getFullBooking($id);
    $shipments = $shipmentModel->where('booking_id', $id)->findAll();

    // ── Authorization checks ─────────────────────────────────────────────────
    if (!$booking || $booking['company_id'] != session()->get('selected_company_id')) {
        return redirect()->back()->with('error', 'Booking not found or access denied!');
    }
    if (empty($shipments)) {
        return redirect()->back()->with('error', 'No shipment items found!');
    }

    try {
        $companyId = (int) $booking['company_id'];

        // ── Master data lookups ──────────────────────────────────────────────
        $companyData   = $invoiceService->loadCompany($companyId);
        $customerName  = trim($shipments[0]['customer_name'] ?? '');
        $customerInfo  = $invoiceService->resolveCustomerGstDetails($customerName, $companyId);
        $bankDetails   = $invoiceService->resolveBankDetails($companyId, $companyData);

        // ── Invoice header metadata ──────────────────────────────────────────
        $invoiceNo    = $shipments[0]['invoice_no'] ?? 'AWB-' . $booking['awb_no'];
        $invoiceDates = array_filter(array_column($shipments, 'invoice_date'));
        sort($invoiceDates);
        $invoiceStart  = !empty($invoiceDates) ? date('d.m.Y', strtotime(reset($invoiceDates))) : date('d.m.Y', strtotime($booking['booking_date']));
        $invoiceEnd    = !empty($invoiceDates) ? date('d.m.Y', strtotime(end($invoiceDates)))   : date('d.m.Y', strtotime($booking['booking_date']));
        $invoicePeriod = $invoiceStart . ' TO ' . $invoiceEnd;
        $invoiceDate   = !empty($invoiceDates) ? date('d.m.Y', strtotime(end($invoiceDates)))   : date('d.m.Y');

        $billingBranch = trim(explode(',', $booking['origin'] ?: 'Pune')[0]);
        $modeTransport = strtoupper($booking['mode_transport'] ?: 'AIR');

        $recipientName    = $customerName ?: 'NA';
        $recipientAddress = $shipments[0]['bill_to'] ?? $shipments[0]['consignee'] ?? 'Address not available';

        // ── Business logic — delegated to InvoiceService ────────────────────
        $chargeAgg     = $invoiceService->aggregateCharges($shipments);
        $activeCharges = $invoiceService->resolveActiveCharges($chargeAgg['totals'], $chargeAgg['miscLabel'], $chargeAgg['customTotals'] ?? []);

        $rowData = $invoiceService->buildShipmentRows(
            $shipments,
            $booking['origin']      ?? 'Pune',
            $booking['destination'] ?? ''
        );

        $gstApplied = (bool) ($booking['gst_applied'] ?? false);
        // Per-booking rate overrides; fallback to company-level rates
        $rateSource = [
            'cgst_rate' => (float) ($booking['cgst_rate'] ?? $companyData['cgst_rate'] ?? 9),
            'sgst_rate' => (float) ($booking['sgst_rate'] ?? $companyData['sgst_rate'] ?? 9),
            'igst_rate' => (float) ($booking['igst_rate'] ?? $companyData['igst_rate'] ?? 18),
        ];
        $isIgst  = ($gstApplied && $rateSource['igst_rate'] > 0);
        $gstData = $invoiceService->calculateGst($rowData['totalTaxable'], $gstApplied, $isIgst, $rateSource);

        $signaturePath = !empty($booking['signature_path']) ? $booking['signature_path'] : ($companyData['signature_path'] ?? '');

        $viewData = $invoiceService->assembleViewData([
            'company'              => $companyData,
            'recipientName'        => $recipientName,
            'recipientAddress'     => $recipientAddress,
            'customerGst'          => $customerInfo['gst'],
            'customerPan'          => $customerInfo['pan'],
            'invoiceNo'            => $invoiceNo,
            'invoicePeriod'        => $invoicePeriod,
            'invoiceDate'          => $invoiceDate,
            'billingBranch'        => $billingBranch,
            'modeTransport'        => $modeTransport,
            'shipmentRows'         => $rowData['rows'],
            'totalBoxes'           => $rowData['totalBoxes'],
            'totalWt'              => $rowData['totalWt'],
            'totalTaxable'         => $rowData['totalTaxable'],
            'gstData'              => $gstData,
            'bankDetails'          => $bankDetails,
            'bookingGstin'         => !empty($booking['gstin'])    ? $booking['gstin']    : ($companyData['gstin']    ?? ''),
            'bookingPan'           => !empty($booking['pan'])      ? $booking['pan']      : ($companyData['pan']      ?? ''),
            'bookingSacCode'       => !empty($booking['sac_code']) ? $booking['sac_code'] : ($companyData['sac_code'] ?? ''),
            'bookingSignaturePath' => $signaturePath,
            'activeCharges'        => $activeCharges,
            'dueDate'              => '',
            'booking'              => $booking,
        ]);

        $pdfFileName = 'AWB_' . ($booking['awb_no'] ?: $invoiceNo) . '.pdf';
        $pdfGenerator->stream($viewData, $pdfFileName);

    } catch (\Exception $e) {
        log_message('error', '[PDF Export Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

        if (strpos($e->getMessage(), 'alpha channel') !== false || strpos($e->getMessage(), 'Imagick or GD') !== false) {
            return redirect()->back()->with('error', 'Your server does not support transparent PNG signatures. Please go to Company Settings and upload a JPG image or draw your signature manually.');
        }

        $msg           = $e->getMessage();
        $isSystemError = ($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException)
                      || ($e instanceof \mysqli_sql_exception)
                      || str_contains($msg, 'SQL')
                      || str_contains($msg, 'database')
                      || str_contains($msg, 'query')
                      || str_contains($msg, 'Connection');

        $userMessage = $isSystemError
            ? 'A secure database or system error occurred. Technical logs have been updated safely.'
            : 'PDF Generation failed: ' . $msg;

        return redirect()->back()->with('error', $userMessage);
    }
}





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
    $salesModel = new \App\Models\SalesChargeModel();
    
    $companyId = session()->get('selected_company_id');
    if (!$companyId) {
        return redirect()->to('/company-selection');
    }
    
    $ids = $this->request->getGet('ids');
    
    if (empty($ids)) {
        return redirect()->back()->with('error', 'Please select at least one booking!');
    }
    
    $idArray = explode(',', $ids);
    
    $allItemCustomLabels = [];
    $allGlobalCustomLabels = [];
    
    foreach ($idArray as $bookingId) {
        $booking = $bookingModel->find($bookingId);
        if (!$booking || $booking['company_id'] != $companyId) continue;
        
        $sales = $salesModel->where('booking_id', $bookingId)->first() ?: [];
        if (!empty($sales['custom_charges'])) {
            $gList = is_string($sales['custom_charges']) ? json_decode($sales['custom_charges'], true) : $sales['custom_charges'];
            if (is_array($gList)) {
                foreach ($gList as $gc) {
                    $lbl = strtoupper(trim($gc['label'] ?? 'SURCHARGE'));
                    if ($lbl !== '' && !in_array($lbl, $allGlobalCustomLabels)) {
                        $allGlobalCustomLabels[] = $lbl;
                    }
                }
            }
        }
        
        $shipments = $shipmentModel->where('booking_id', $bookingId)->findAll();
        foreach ($shipments as $item) {
            if (!empty($item['custom_charges'])) {
                $iList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                if (is_array($iList)) {
                    foreach ($iList as $ic) {
                        $lbl = strtoupper(trim($ic['label'] ?? 'CHARGE'));
                        if ($lbl !== '' && !in_array($lbl, $allItemCustomLabels)) {
                            $allItemCustomLabels[] = $lbl;
                        }
                    }
                }
            }
        }
    }
    
    $headers = [
        'SR NO', 'AWB NO', 'DOCKET NO', 'COMPANY', 'CUSTOMER NAME', 'CONSIGNEE', 'INVOICE NO', 'PART NO',
        'BOOKING DATE', 'ORIGIN', 'DESTINATION', 'MODE OF TRANSPORT', 'STATUS',
        'EXPECTED DELIVERY DATE', 'EXPECTED DELIVERY TIME', 'INVOICE DATE', 'BILL TO',
        'ACTUAL WEIGHT', 'LENGTH', 'WIDTH', 'HEIGHT', 'VOLUMETRIC WEIGHT', 'CALCULATED WEIGHT', 'FINAL CHARGEABLE WEIGHT', 'ITEM PIECES',
        'E-WAY BILL NO', 'E-WAY BILL DATE',
        'ITEM RATE', 'ITEM FREIGHT', 'ITEM FUEL SURCHARGE RATE', 'ITEM FUEL SURCHARGE AMOUNT', 'ITEM DOCKET CHARGES', 'ITEM PICKUP CHARGES', 'ITEM DELIVERY CHARGES', 'ITEM FOV CHARGES', 'ITEM HANDLING CHARGES', 'ITEM SERVICE CHARGES', 'ITEM MISC CHARGES'
    ];

    foreach ($allItemCustomLabels as $lbl) {
        $headers[] = 'ITEM: ' . $lbl;
    }
    $headers[] = 'ITEM TAXABLE AMOUNT';

    array_push($headers, 
        'AIRLINES / CARRIER', 'FLIGHT NUMBER', 'VEHICLE NO', 'DRIVER NAME', 'DRIVER MOBILE', 'DRIVER LICENSE', 'TRANSPORTER NAME', 'TRANSPORTER MOBILE',
        'PAYMENT TYPE', 'GST APPLIED', 'GSTIN', 'PAN', 'SAC CODE', 'CGST RATE (%)', 'SGST RATE (%)', 'IGST RATE (%)', 'NARRATION',
        'SALES RATE', 'SALES WEIGHT', 'SALES DDC', 'SALES SSC', 'SALES BTC', 'SALES FLC', 'SALES DOC', 'SALES INBOUND TSP', 'SALES OUTBOUND TSP', 'SALES TCP', 'SALES UTILITY CHARGES', 'SALES XRAY CHARGES', 'SALES ADO', 'SALES AWB FEES AGENT', 'SALES AWB FEES CARRIER', 'SALES ADMIN CHARGES', 'SALES DELIVERY ORDER CHARGES', 'SALES INBOUND HANDLING', 'SALES INBOUND STORAGE', 'SALES OUTBOUND STORAGE', 'SALES MISC CHARGES'
    );

    foreach ($allGlobalCustomLabels as $lbl) {
        $headers[] = 'GLOBAL: ' . $lbl;
    }
    $headers[] = 'TOTAL BILLING AMOUNT';
    
    $rows = [];
    $srNo = 1;
    
    foreach ($idArray as $bookingId) {
        $booking = $bookingModel->find($bookingId);
        if (!$booking || $booking['company_id'] != $companyId) continue;
        
        $company = $companyModel->find($booking['company_id']);
        $companyName = $company['name'] ?? 'N/A';
        
        $sales = $salesModel->where('booking_id', $bookingId)->first() ?: [];
        $salesCustomMap = [];
        if (!empty($sales['custom_charges'])) {
            $gList = is_string($sales['custom_charges']) ? json_decode($sales['custom_charges'], true) : $sales['custom_charges'];
            if (is_array($gList)) {
                foreach ($gList as $gc) {
                    $lbl = strtoupper(trim($gc['label'] ?? 'SURCHARGE'));
                    $val = floatval($gc['value'] ?? 0);
                    $salesCustomMap[$lbl] = $val;
                }
            }
        }

        $shipments = $shipmentModel->where('booking_id', $bookingId)->findAll();
        
        foreach ($shipments as $item) {
            $actualWt = floatval($item['actual_weight'] ?? 0);
            $wt = floatval($item['final_chargeable_weight'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
            $dock = floatval($item['docket_charges'] ?? 0);
            $pickup = floatval($item['pickup_charges'] ?? 0);
            $delivery = floatval($item['delivery_charges'] ?? 0);
            $fov = floatval($item['fov_charges'] ?? 0);
            $handling = floatval($item['handling_charges'] ?? 0);
            $service = floatval($item['service_charges'] ?? 0);
            $misc = floatval($item['misc_charges'] ?? 0);
            
            $freight = $wt * $rate;
            $fuelAmt = $fuelSur;
            
            $itemCustomMap = [];
            $itemCustomSum = 0;
            if (!empty($item['custom_charges'])) {
                $iList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                if (is_array($iList)) {
                    foreach ($iList as $ic) {
                        $lbl = strtoupper(trim($ic['label'] ?? 'CHARGE'));
                        $val = floatval($ic['value'] ?? 0);
                        $itemCustomMap[$lbl] = $val;
                        $itemCustomSum += $val;
                    }
                }
            }

            $taxable = $freight + $fuelAmt + $dock + $pickup + $delivery + $fov + $handling + $service + $misc + $itemCustomSum;
            
            $row = [
                $srNo,
                $booking['awb_no'] ?? '',
                $item['docket_no'] ?? '',
                $companyName,
                $item['customer_name'] ?? '',
                $item['consignee'] ?? '',
                $item['invoice_no'] ?? '',
                $item['part_no'] ?? '',
                
                !empty($booking['booking_date']) ? date('d-m-Y', strtotime($booking['booking_date'])) : '',
                $booking['origin'] ?? '',
                $booking['destination'] ?? '',
                $booking['mode_transport'] ?? '',
                $booking['status'] ?? '',
                
                !empty($booking['expected_delivery_date']) ? date('d-m-Y', strtotime($booking['expected_delivery_date'])) : '',
                !empty($booking['expected_delivery_time']) ? substr($booking['expected_delivery_time'], 0, 5) : '',
                !empty($item['invoice_date']) ? date('d-m-Y', strtotime($item['invoice_date'])) : '',
                $item['bill_to'] ?? '',
                
                $actualWt,
                floatval($item['length'] ?? 0),
                floatval($item['width'] ?? 0),
                floatval($item['height'] ?? 0),
                floatval($item['volumetric_weight'] ?? 0),
                floatval($item['calculated_chargeable_weight'] ?? 0),
                floatval($item['final_chargeable_weight'] ?? 0),
                intval($item['pieces'] ?? 0),
                $item['eway_bill_no'] ?? '',
                !empty($item['eway_bill_date']) ? date('d-m-Y', strtotime($item['eway_bill_date'])) : '',
                
                $rate,
                $freight,
                $fuelSur,
                $fuelAmt,
                $dock,
                $pickup,
                $delivery,
                $fov,
                $handling,
                $service,
                $misc
            ];

            foreach ($allItemCustomLabels as $lbl) {
                $row[] = floatval($itemCustomMap[$lbl] ?? 0);
            }

            $row[] = $taxable;

            array_push($row,
                $booking['airlines'] ?? '',
                $booking['flight_number'] ?? '',
                $booking['vehicle_no'] ?? '',
                $booking['driver_name'] ?? '',
                $booking['driver_mobile'] ?? '',
                $booking['driver_license_no'] ?? '',
                $booking['transporter_name'] ?? '',
                $booking['transporter_mobile'] ?? '',
                
                $booking['payment_type'] ?? '',
                ($booking['gst_applied'] ?? 0) == 1 ? 'Yes' : 'No',
                $booking['gstin'] ?? '',
                $booking['pan'] ?? '',
                $booking['sac_code'] ?? '',
                $booking['cgst_rate'] ?? 0,
                $booking['sgst_rate'] ?? 0,
                $booking['igst_rate'] ?? 0,
                $booking['narration'] ?? '',
                
                floatval($sales['rate'] ?? 0),
                floatval($sales['weight'] ?? 0),
                floatval($sales['ddc'] ?? 0),
                floatval($sales['ssc'] ?? 0),
                floatval($sales['btc'] ?? 0),
                floatval($sales['flc'] ?? 0),
                floatval($sales['doc'] ?? 0),
                floatval($sales['inbound_tsp'] ?? 0),
                floatval($sales['outbound_tsp'] ?? 0),
                floatval($sales['tcp'] ?? 0),
                floatval($sales['utility_charges'] ?? 0),
                floatval($sales['xray_charges'] ?? 0),
                floatval($sales['ado'] ?? 0),
                floatval($sales['awb_fees_agent'] ?? 0),
                floatval($sales['awb_fees_carrier'] ?? 0),
                floatval($sales['admin_charges'] ?? 0),
                floatval($sales['delivery_order_charges'] ?? 0),
                floatval($sales['inbound_handling'] ?? 0),
                floatval($sales['inbound_storage'] ?? 0),
                floatval($sales['outbound_storage'] ?? 0),
                floatval($sales['misc_charges'] ?? 0)
            );

            foreach ($allGlobalCustomLabels as $lbl) {
                $row[] = floatval($salesCustomMap[$lbl] ?? 0);
            }

            $row[] = floatval($sales['total_amount'] ?? 0);

            $rows[] = $row;
            $srNo++;
        }
    }
    
    // Save to temp JSON
    $tempJson = WRITEPATH . 'export_' . uniqid() . '.json';
    $tempXlsx = WRITEPATH . 'export_' . uniqid() . '.xlsx';
    
    file_put_contents($tempJson, json_encode([
        'headers' => $headers,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE));
    
    // Execute python script to generate XLSX
    $scriptPath = ROOTPATH . 'scripts/generate_xlsx.py';
    $cmd = 'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($tempJson) . ' ' . escapeshellarg($tempXlsx);
    
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0 || !file_exists($tempXlsx)) {
        @unlink($tempJson);
        
        // Log detailed error for debugging
        log_message('error', '[Excel Generation Error] Python command failed. Cmd: ' . $cmd . ' | Return Code: ' . $returnVar . ' | Output: ' . implode("\n", $output));
        
        // Fallback to streaming raw CSV
        $filename = 'AWB_Export_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM to support special/Unicode characters in Excel
        fwrite($out, "\xEF\xBB\xBF");
        
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
    
    // Download XLSX
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="AWB_Export_' . date('Y-m-d') . '.xlsx"');
    header('Content-Length: ' . filesize($tempXlsx));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($tempXlsx);
    
    // Clean up
    @unlink($tempJson);
    @unlink($tempXlsx);
    exit;
}


    public function deleteSignature($bookingId)
    {
        $perm = $this->checkPermission('can_edit');
        if ($perm !== true) {
            return $perm;
        }
        
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

    public function allInvoices()
    {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) {
            return redirect()->to('/company-selection');
        }

        $customers = (new CustomerModel())->getByCompany($companyId);
        $banks = (new \App\Models\BankAccountModel())->getByCompany($companyId);

        return view('logistics/all_invoices', [
            'user'         => session()->get(),
            'company_name' => session()->get('selected_company_name'),
            'customers'    => $customers,
            'banks'        => $banks,
        ]);
    }

    public function ajaxSearchShipmentRecords()
    {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $customerName = $this->request->getPost('customer_name');
        $fromDate = $this->request->getPost('from_date');
        $toDate = $this->request->getPost('to_date');

        if (empty($customerName) || empty($fromDate) || empty($toDate)) {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('shipment_items si')
                      ->select('si.id, si.docket_no, si.invoice_no, si.invoice_date, si.actual_weight, si.final_chargeable_weight, si.pieces, si.rate, b.booking_date, b.origin as booking_origin, b.destination as booking_destination, b.gst_applied, b.cgst_rate, b.sgst_rate, b.igst_rate, b.awb_no')
                      ->join('bookings b', 'b.id = si.booking_id')
                      ->where('b.company_id', $companyId)
                      ->where('si.customer_name', $customerName);

        $builder->groupStart()
                    ->groupStart()
                        ->where("si.invoice_date >= ", $fromDate)
                        ->where("si.invoice_date <= ", $toDate)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where("si.invoice_date IS NULL")
                        ->where("b.booking_date >= ", $fromDate)
                        ->where("b.booking_date <= ", $toDate)
                    ->groupEnd()
                ->groupEnd();

        $records = $builder->orderBy('si.invoice_date', 'ASC')
                           ->orderBy('b.booking_date', 'ASC')
                           ->get()
                           ->getResultArray();

        // Format dates for UI
        foreach ($records as &$r) {
            $dateVal = !empty($r['invoice_date']) ? $r['invoice_date'] : $r['booking_date'];
            $r['display_date'] = date('d-M-Y', strtotime($dateVal));
        }

        return $this->response->setJSON($records);
    }

    // generateConsolidatedInvoice — thin controller; all business logic delegated to InvoiceService + PdfInvoiceGenerator
    public function generateConsolidatedInvoice()
    {
        $companyId = session()->get('selected_company_id');
        if (!$companyId) {
            return redirect()->to('/company-selection');
        }

        $post         = $this->request->getPost();
        $customerName = trim($post['customer_name'] ?? '');
        $bankId       = (int) ($post['bank_id'] ?? 0);
        $fromDate     = $post['from_date']     ?? '';
        $toDate       = $post['to_date']       ?? '';
        $invoiceNo    = $post['invoice_no']    ?? '';
        $invoiceDate  = $post['invoice_date']  ?? date('Y-m-d');
        $dueDate      = $post['due_date']      ?? '';
        $remark       = $post['remark']        ?? '';
        $itemIds      = $post['item_ids']      ?? [];

        if (empty($customerName) || empty($itemIds)) {
            return redirect()->back()->with('error', 'Please select a customer and at least one shipment record!');
        }

        $invoiceService = new \App\Services\InvoiceService();
        $pdfGenerator   = new \App\Services\PdfInvoiceGenerator();

        try {
            // ── Master data lookups ──────────────────────────────────────────
            $companyData  = $invoiceService->loadCompany((int) $companyId);
            $customerInfo = $invoiceService->resolveCustomerGstDetails($customerName, (int) $companyId);
            $bankDetails  = $invoiceService->resolveBankDetails((int) $companyId, $companyData, $bankId);

            // ── Fetch selected, tenant-scoped shipment items ────────────────
            $db        = \Config\Database::connect();
            $shipments = $db->table('shipment_items si')
                ->select('si.*, b.booking_date, b.origin as booking_origin, b.destination as booking_destination, b.mode_transport as booking_mode')
                ->join('bookings b', 'b.id = si.booking_id')
                ->whereIn('si.id', $itemIds)
                ->where('b.company_id', (int) $companyId)
                ->where('si.customer_name', $customerName)
                ->orderBy('si.invoice_date', 'ASC')
                ->orderBy('b.booking_date', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($shipments)) {
                return redirect()->back()->with('error', 'Selected shipment items not found or access denied!');
            }

            // ── Recipient ───────────────────────────────────────────────────
            $recipientName    = $customerName;
            $recipientAddress = $shipments[0]['bill_to'] ?? $shipments[0]['consignee'] ?? 'Address not available';

            // ── Business logic — delegated to InvoiceService ────────────────
            $chargeAgg     = $invoiceService->aggregateCharges($shipments);
            $activeCharges = $invoiceService->resolveActiveCharges($chargeAgg['totals'], $chargeAgg['miscLabel'], $chargeAgg['customTotals'] ?? []);

            $rowData = $invoiceService->buildShipmentRows($shipments);

            // Determine GST application and IGST status from selected shipments' bookings (automated settings per booking)
            $gstApplied = false;
            $igstCount  = 0;
            $cgstCount  = 0;
            
            $bookingIds = array_unique(array_column($shipments, 'booking_id'));
            if (!empty($bookingIds)) {
                $bookingsData = $db->table('bookings')
                    ->whereIn('id', $bookingIds)
                    ->get()
                    ->getResultArray();
                foreach ($bookingsData as $b) {
                    if ((int)($b['gst_applied'] ?? 0) === 1) {
                        $gstApplied = true;
                        if ((float)($b['igst_rate'] ?? 0) > 0) {
                            $igstCount++;
                        } else if ((float)($b['cgst_rate'] ?? 0) > 0 || (float)($b['sgst_rate'] ?? 0) > 0) {
                            $cgstCount++;
                        }
                    }
                }
            }
            $isIgst = ($igstCount >= $cgstCount) && ($igstCount > 0);

            // ── Excel Export option ──────────────────────────────────────────
            $exportType = $post['export_type'] ?? 'pdf';
            if ($exportType === 'excel') {
                $bookingModel = new \App\Models\BookingModel();
                $companyModel = new \App\Models\CompanyModel();
                $salesModel   = new \App\Models\SalesChargeModel();
                
                $headers = [
                    'SR NO', 'AWB NO', 'DOCKET NO', 'COMPANY', 'CUSTOMER NAME', 'CONSIGNEE', 'INVOICE NO', 'PART NO',
                    'BOOKING DATE', 'ORIGIN', 'DESTINATION', 'MODE OF TRANSPORT', 'STATUS',
                    'EXPECTED DELIVERY DATE', 'EXPECTED DELIVERY TIME', 'INVOICE DATE', 'BILL TO',
                    'ACTUAL WEIGHT', 'LENGTH', 'WIDTH', 'HEIGHT', 'VOLUMETRIC WEIGHT', 'CALCULATED WEIGHT', 'FINAL CHARGEABLE WEIGHT', 'ITEM PIECES',
                    'E-WAY BILL NO', 'E-WAY BILL DATE',
                    'ITEM RATE', 'ITEM FREIGHT', 'ITEM FUEL SURCHARGE RATE', 'ITEM FUEL SURCHARGE AMOUNT', 'ITEM DOCKET CHARGES', 'ITEM PICKUP CHARGES', 'ITEM DELIVERY CHARGES', 'ITEM FOV CHARGES', 'ITEM HANDLING CHARGES', 'ITEM SERVICE CHARGES', 'ITEM MISC CHARGES', 'ITEM TAXABLE AMOUNT',
                    'AIRLINES / CARRIER', 'FLIGHT NUMBER', 'VEHICLE NO', 'DRIVER NAME', 'DRIVER MOBILE', 'DRIVER LICENSE', 'TRANSPORTER NAME', 'TRANSPORTER MOBILE',
                    'PAYMENT TYPE', 'GST APPLIED', 'GSTIN', 'PAN', 'SAC CODE', 'CGST RATE (%)', 'SGST RATE (%)', 'IGST RATE (%)', 'NARRATION',
                    'SALES RATE', 'SALES WEIGHT', 'SALES DDC', 'SALES SSC', 'SALES BTC', 'SALES FLC', 'SALES DOC', 'SALES INBOUND TSP', 'SALES OUTBOUND TSP', 'SALES TCP', 'SALES UTILITY CHARGES', 'SALES XRAY CHARGES', 'SALES ADO', 'SALES AWB FEES AGENT', 'SALES AWB FEES CARRIER', 'SALES ADMIN CHARGES', 'SALES DELIVERY ORDER CHARGES', 'SALES INBOUND HANDLING', 'SALES INBOUND STORAGE', 'SALES OUTBOUND STORAGE', 'SALES MISC CHARGES', 'TOTAL BILLING AMOUNT'
                ];
                
                $rows = [];
                $srNo = 1;
                
                $bookingCache = [];
                $companyCache = [];
                $salesCache   = [];
                
                foreach ($shipments as $item) {
                    $bId = $item['booking_id'];
                    
                    if (!isset($bookingCache[$bId])) {
                        $bookingCache[$bId] = $bookingModel->find($bId);
                    }
                    $booking = $bookingCache[$bId];
                    if (!$booking || $booking['company_id'] != $companyId) continue;
                    
                    if (!isset($companyCache[$booking['company_id']])) {
                        $comp = $companyModel->find($booking['company_id']);
                        $companyCache[$booking['company_id']] = $comp['name'] ?? 'N/A';
                    }
                    $companyName = $companyCache[$booking['company_id']];
                    
                    if (!isset($salesCache[$bId])) {
                        $salesCache[$bId] = $salesModel->where('booking_id', $bId)->first() ?: [];
                    }
                    $sales = $salesCache[$bId];
                    
                    $actualWt = floatval($item['actual_weight'] ?? 0);
                    $wt = floatval($item['final_chargeable_weight'] ?? 0);
                    $rate = floatval($item['rate'] ?? 0);
                    $fuelSur = floatval($item['fuel_surcharge'] ?? 0);
                    $dock = floatval($item['docket_charges'] ?? 0);
                    $pickup = floatval($item['pickup_charges'] ?? 0);
                    $delivery = floatval($item['delivery_charges'] ?? 0);
                    $fov = floatval($item['fov_charges'] ?? 0);
                    $handling = floatval($item['handling_charges'] ?? 0);
                    $service = floatval($item['service_charges'] ?? 0);
                    $misc = floatval($item['misc_charges'] ?? 0);
                    
                    $freight = $wt * $rate;
                    $fuelAmt = $fuelSur;
                    $taxable = $freight + $fuelAmt + $dock + $pickup + $delivery + $fov + $handling + $service + $misc;
                    
                    $rows[] = [
                        $srNo,
                        $booking['awb_no'] ?? '',
                        $item['docket_no'] ?? '',
                        $companyName,
                        $item['customer_name'] ?? '',
                        $item['consignee'] ?? '',
                        $item['invoice_no'] ?? '',
                        $item['part_no'] ?? '',
                        
                        !empty($booking['booking_date']) ? date('d-m-Y', strtotime($booking['booking_date'])) : '',
                        $booking['origin'] ?? '',
                        $booking['destination'] ?? '',
                        $booking['mode_transport'] ?? '',
                        $booking['status'] ?? '',
                        
                        !empty($booking['expected_delivery_date']) ? date('d-m-Y', strtotime($booking['expected_delivery_date'])) : '',
                        !empty($booking['expected_delivery_time']) ? substr($booking['expected_delivery_time'], 0, 5) : '',
                        !empty($item['invoice_date']) ? date('d-m-Y', strtotime($item['invoice_date'])) : '',
                        $item['bill_to'] ?? '',
                        
                        $actualWt,
                        floatval($item['length'] ?? 0),
                        floatval($item['width'] ?? 0),
                        floatval($item['height'] ?? 0),
                        floatval($item['volumetric_weight'] ?? 0),
                        floatval($item['calculated_chargeable_weight'] ?? 0),
                        floatval($item['final_chargeable_weight'] ?? 0),
                        intval($item['pieces'] ?? 0),
                        $item['eway_bill_no'] ?? '',
                        !empty($item['eway_bill_date']) ? date('d-m-Y', strtotime($item['eway_bill_date'])) : '',
                        
                        $rate,
                        $freight,
                        $fuelSur,
                        $fuelAmt,
                        $dock,
                        $pickup,
                        $delivery,
                        $fov,
                        $handling,
                        $service,
                        $misc,
                        $taxable,
                        
                        $booking['airlines'] ?? '',
                        $booking['flight_number'] ?? '',
                        $booking['vehicle_no'] ?? '',
                        $booking['driver_name'] ?? '',
                        $booking['driver_mobile'] ?? '',
                        $booking['driver_license_no'] ?? '',
                        $booking['transporter_name'] ?? '',
                        $booking['transporter_mobile'] ?? '',
                        
                        $booking['payment_type'] ?? '',
                        ($booking['gst_applied'] ?? 0) == 1 ? 'Yes' : 'No',
                        $booking['gstin'] ?? '',
                        $booking['pan'] ?? '',
                        $booking['sac_code'] ?? '',
                        $booking['cgst_rate'] ?? 0,
                        $booking['sgst_rate'] ?? 0,
                        $booking['igst_rate'] ?? 0,
                        $booking['narration'] ?? '',
                        
                        floatval($sales['rate'] ?? 0),
                        floatval($sales['weight'] ?? 0),
                        floatval($sales['ddc'] ?? 0),
                        floatval($sales['ssc'] ?? 0),
                        floatval($sales['btc'] ?? 0),
                        floatval($sales['flc'] ?? 0),
                        floatval($sales['doc'] ?? 0),
                        floatval($sales['inbound_tsp'] ?? 0),
                        floatval($sales['outbound_tsp'] ?? 0),
                        floatval($sales['tcp'] ?? 0),
                        floatval($sales['utility_charges'] ?? 0),
                        floatval($sales['xray_charges'] ?? 0),
                        floatval($sales['ado'] ?? 0),
                        floatval($sales['awb_fees_agent'] ?? 0),
                        floatval($sales['awb_fees_carrier'] ?? 0),
                        floatval($sales['admin_charges'] ?? 0),
                        floatval($sales['delivery_order_charges'] ?? 0),
                        floatval($sales['inbound_handling'] ?? 0),
                        floatval($sales['inbound_storage'] ?? 0),
                        floatval($sales['outbound_storage'] ?? 0),
                        floatval($sales['misc_charges'] ?? 0),
                        floatval($sales['total_amount'] ?? 0)
                    ];
                    $srNo++;
                }
                
                $tempJson = WRITEPATH . 'export_' . uniqid() . '.json';
                $tempXlsx = WRITEPATH . 'export_' . uniqid() . '.xlsx';
                
                file_put_contents($tempJson, json_encode([
                    'headers' => $headers,
                    'rows' => $rows
                ], JSON_UNESCAPED_UNICODE));
                
                $scriptPath = ROOTPATH . 'scripts/generate_xlsx.py';
                $cmd = 'python ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($tempJson) . ' ' . escapeshellarg($tempXlsx);
                
                exec($cmd, $outputCmd, $returnVar);
                
                if ($returnVar !== 0 || !file_exists($tempXlsx)) {
                    @unlink($tempJson);
                    log_message('error', '[Consolidated Excel Error] Python cmd failed: ' . $cmd . ' | Code: ' . $returnVar);
                    
                    // Fallback to CSV
                    $filename = 'Purchase_Record_Export_' . date('Y-m-d') . '.csv';
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, $headers);
                    foreach ($rows as $row) {
                        fputcsv($out, $row);
                    }
                    fclose($out);
                    exit;
                }
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="Purchase_Record_Export_' . date('Y-m-d') . '.xlsx"');
                header('Content-Length: ' . filesize($tempXlsx));
                header('Pragma: no-cache');
                header('Expires: 0');
                
                readfile($tempXlsx);
                
                @unlink($tempJson);
                @unlink($tempXlsx);
                exit;
            }

            $gstData    = $invoiceService->calculateGst(
                $rowData['totalTaxable'],
                $gstApplied,
                $isIgst,
                $companyData
            );

            // ── Period ──────────────────────────────────────────────────────
            $invoicePeriod = date('d/m/Y', strtotime($fromDate)) . ' TO ' . date('d/m/Y', strtotime($toDate));

            $viewData = $invoiceService->assembleViewData([
                'company'              => $companyData,
                'recipientName'        => $recipientName,
                'recipientAddress'     => $recipientAddress,
                'customerGst'          => $customerInfo['gst'],
                'customerPan'          => $customerInfo['pan'],
                'invoiceNo'            => $invoiceNo,
                'invoicePeriod'        => $invoicePeriod,
                'invoiceDate'          => date('d/m/Y', strtotime($invoiceDate)),
                'billingBranch'        => trim(explode(',', $shipments[0]['booking_origin'] ?? 'PUNE')[0]),
                'modeTransport'        => strtoupper($shipments[0]['booking_mode'] ?? 'AIR'),
                'shipmentRows'         => $rowData['rows'],
                'totalBoxes'           => $rowData['totalBoxes'],
                'totalWt'              => $rowData['totalWt'],
                'totalTaxable'         => $rowData['totalTaxable'],
                'gstData'              => $gstData,
                'bankDetails'          => $bankDetails,
                'bookingGstin'         => $companyData['gstin']          ?? '',
                'bookingPan'           => $companyData['pan']            ?? '',
                'bookingSacCode'       => $companyData['sac_code']       ?? '',
                'bookingSignaturePath' => $companyData['signature_path'] ?? '',
                'activeCharges'        => $activeCharges,
                'dueDate'              => !empty($dueDate) ? date('d/m/Y', strtotime($dueDate)) : '',
                'booking'              => [
                    'gst_applied' => $gstApplied ? 1 : 0,
                    'narration'   => $remark,
                ],
            ]);

            $pdfGenerator->stream($viewData, $invoiceNo . '.pdf');

        } catch (\Throwable $e) {
            log_message('error', '[Consolidated PDF Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'PDF Generation failed: ' . $e->getMessage());
        }
    }
}
