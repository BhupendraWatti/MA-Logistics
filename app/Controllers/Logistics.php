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
            ->with('error', 'You do not have permission to create bookings!');
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
    
    $bookingService = new \App\Services\BookingService();
    
    try {
        $bookingService->createBooking($this->request->getPost(), session()->get('user_id'));
        $awb_no = $this->request->getPost('awb_no');
        return redirect()->to('/logistics')->with('success', 'Booking created successfully! AWB: ' . $awb_no);
    } catch (\Throwable $e) {
        return redirect()->back()->with('error', 'SYSTEM ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }
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
    
    $bookingService = new \App\Services\BookingService();
    
    try {
        $bookingService->updateBooking($id, $this->request->getPost(), session()->get('user_id'));
        $awb_no = $this->request->getPost('awb_no');
        return redirect()->to('/logistics')->with('success', 'Booking updated successfully! AWB: ' . $awb_no);
    } catch (\Throwable $e) {
        return redirect()->back()->with('error', 'SYSTEM ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    }
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

    $cgst = round($totalTaxable * 0.09);
    $sgst = round($totalTaxable * 0.09);
    $igst = 0;
    $netPayable = round($totalTaxable + $cgst + $sgst + $igst);

    $viewData = [
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
        'netPayable' => $netPayable,
        'amountInWords' => $this->formatAmountInWords($netPayable)
    ];

    $html = view('pdfs/invoice', $viewData);

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