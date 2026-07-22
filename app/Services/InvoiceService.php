<?php

namespace App\Services;

use App\Models\BankAccountModel;
use App\Models\CompanyModel;
use App\Models\CustomerModel;
use InvalidArgumentException;

/**
 * InvoiceService
 *
 * Encapsulates all invoice-related business logic that was previously scattered
 * inside the fat Logistics controller methods (exportPdf, generateConsolidatedInvoice).
 *
 * Responsibilities:
 *  - Shipment charge aggregation and active-column resolution
 *  - Per-row shipment data building for the PDF template
 *  - GST calculation with mutual-exclusion enforcement (CGST/SGST vs IGST)
 *  - Customer GSTIN / PAN lookup (tenant-scoped)
 *  - Company and bank details resolution with full fallback chain
 *  - Amount-in-words conversion (Indian Numbering System)
 *  - Final $viewData assembly for pdfs/invoice.php
 *
 * The controller's only job is:
 *   1. Parse and validate HTTP input
 *   2. Query the database for raw data
 *   3. Call this service to assemble clean data
 *   4. Hand the result to PdfInvoiceGenerator
 */
class InvoiceService
{
    protected CompanyModel     $companyModel;
    protected CustomerModel    $customerModel;
    protected BankAccountModel $bankModel;

    public function __construct()
    {
        $this->companyModel  = new CompanyModel();
        $this->customerModel = new CustomerModel();
        $this->bankModel     = new BankAccountModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 1 — Charge Aggregation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sum all charge types across an array of shipment item rows.
     *
     * @param  array[] $shipments  Raw DB rows from shipment_items
     * @return array   ['totals' => [...], 'miscLabel' => '...']
     */
    public function aggregateCharges(array $shipments): array
    {
        $totals = [
            'freight' => 0.0, 'fuel'     => 0.0, 'docket'   => 0.0,
            'pickup'  => 0.0, 'delivery' => 0.0, 'fov'      => 0.0,
            'handling'=> 0.0, 'service'  => 0.0, 'misc'     => 0.0,
        ];
        $miscLabel = 'Misc Charges';
        $customTotals = [];

        foreach ($shipments as $item) {
            $wt      = (float) ($item['final_chargeable_weight']  ?? 0);
            $fuelSur = (float) ($item['fuel_surcharge'] ?? 0);
            $rate    = (float) ($item['rate']           ?? 0);

            $totals['freight']  += $wt * $rate;
            $totals['fuel']     += $fuelSur;
            $totals['docket']   += (float) ($item['docket_charges']   ?? 0);
            $totals['pickup']   += (float) ($item['pickup_charges']   ?? 0);
            $totals['delivery'] += (float) ($item['delivery_charges'] ?? 0);
            $totals['fov']      += (float) ($item['fov_charges']      ?? 0);
            $totals['handling'] += (float) ($item['handling_charges'] ?? 0);
            $totals['service']  += (float) ($item['service_charges']  ?? 0);
            $totals['misc']     += (float) ($item['misc_charges']     ?? 0);

            if (!empty($item['misc_charges_name'])) {
                $miscLabel = $item['misc_charges_name'];
            }

            if (!empty($item['custom_charges'])) {
                $customList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                if (is_array($customList)) {
                    foreach ($customList as $cc) {
                        $lbl = strtoupper(trim($cc['label'] ?? 'EXTRA CHARGE'));
                        $val = (float) ($cc['value'] ?? 0);
                        if ($val > 0) {
                            if (!isset($customTotals[$lbl])) {
                                $customTotals[$lbl] = 0.0;
                            }
                            $customTotals[$lbl] += $val;
                        }
                    }
                }
            }
        }

        return ['totals' => $totals, 'miscLabel' => $miscLabel, 'customTotals' => $customTotals];
    }

    /**
     * Build the map of active (non-zero) charge columns for dynamic PDF column display.
     * Returns a default minimal set if all charges are zero.
     *
     * @param  array  $totals        Keyed totals from aggregateCharges()['totals']
     * @param  string $miscLabel     Label from aggregateCharges()['miscLabel']
     * @param  array  $customTotals  Keyed totals from aggregateCharges()['customTotals']
     * @return array  e.g. ['docket' => ['label' => 'DOCKET', 'field' => 'docket'], ...]
     */
    public function resolveActiveCharges(array $totals, string $miscLabel, array $customTotals = []): array
    {
        $all = [];

        if ($totals['fuel'] > 0) {
            $all['fuel_rate'] = ['label' => 'FUEL SUR %', 'sum' => $totals['fuel'], 'field' => 'fuelSur'];
            $all['fuel_amt']  = ['label' => 'FUEL AMT',   'sum' => $totals['fuel'], 'field' => 'fuelAmt'];
        }
        $all['docket']   = ['label' => 'DOCKET',              'sum' => $totals['docket'],   'field' => 'docket'];
        $all['pickup']   = ['label' => 'PICKUP',              'sum' => $totals['pickup'],   'field' => 'pickup'];
        $all['delivery'] = ['label' => 'DELIVERY',            'sum' => $totals['delivery'], 'field' => 'delivery'];
        $all['fov']      = ['label' => 'FOV',                 'sum' => $totals['fov'],      'field' => 'fov'];
        $all['handling'] = ['label' => 'HANDLING',            'sum' => $totals['handling'], 'field' => 'handling'];
        $all['service']  = ['label' => 'SERVICE',             'sum' => $totals['service'],  'field' => 'service'];
        $all['misc']     = ['label' => strtoupper($miscLabel),'sum' => $totals['misc'],     'field' => 'misc'];

        foreach ($customTotals as $lbl => $val) {
            $key = 'custom_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $lbl));
            $all[$key] = ['label' => $lbl, 'sum' => $val, 'custom' => true, 'custom_label' => $lbl];
        }

        $active = array_filter($all, fn ($c) => $c['sum'] > 0);

        return $active ?: [
            'docket'   => ['label' => 'DOCKET',   'field' => 'docket'],
            'pickup'   => ['label' => 'PICKUP',   'field' => 'pickup'],
            'delivery' => ['label' => 'DELIVERY', 'field' => 'delivery'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 2 — Shipment Row Building
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Transform raw DB shipment rows into the display-ready format expected by the PDF template.
     *
     * @param  array[] $shipments       DB rows; booking_origin / booking_destination should be
     *                                  present from a JOIN, otherwise $fallbackOrigin/$fallbackDest are used.
     * @param  string  $fallbackOrigin  Booking-level origin (used for single-booking PDFs)
     * @param  string  $fallbackDest    Booking-level destination
     * @return array   [
     *                   'rows'         => array,
     *                   'totalBoxes'   => int,
     *                   'totalWt'      => float,
     *                   'totalTaxable' => float,
     *                 ]
     */
    public function buildShipmentRows(
        array  $shipments,
        string $fallbackOrigin = 'Pune',
        string $fallbackDest   = ''
    ): array {
        $serial       = 1;
        $totalBoxes   = 0;
        $totalWt      = 0.0;
        $totalTaxable = 0.0;
        $rows         = [];

        foreach ($shipments as $item) {
            // Prefer invoice_date; fallback to booking_date
            $rawDate = $item['invoice_date'] ?? $item['booking_date'] ?? null;
            $date    = $rawDate ? date('d.m.y', strtotime($rawDate)) : '-';

            // Origin / Destination: use JOIN'd booking fields if present
            $originRaw = $item['booking_origin']      ?? $fallbackOrigin;
            $destRaw   = $item['booking_destination'] ?? $fallbackDest;
            $origin    = trim(explode(',', $originRaw ?: 'Pune')[0]);
            $dest      = trim(explode(',', $destRaw   ?: '')[0]);

            $wt       = (float) ($item['final_chargeable_weight']  ?? 0);
            $rate     = (float) ($item['rate']           ?? 0);
            $fuelSur  = (float) ($item['fuel_surcharge'] ?? 0);
            $freight  = $wt * $rate;
            $fuelAmt  = $fuelSur;

            $docket   = (float) ($item['docket_charges']   ?? 0);
            $pickup   = (float) ($item['pickup_charges']   ?? 0);
            $delivery = (float) ($item['delivery_charges'] ?? 0);
            $fov      = (float) ($item['fov_charges']      ?? 0);
            $handling = (float) ($item['handling_charges'] ?? 0);
            $service  = (float) ($item['service_charges']  ?? 0);
            $misc     = (float) ($item['misc_charges']     ?? 0);
            
            $customChargesSum = 0.0;
            $itemCustomMap    = [];
            if (!empty($item['custom_charges'])) {
                $customList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                if (is_array($customList)) {
                    foreach ($customList as $cc) {
                        $lbl = strtoupper(trim($cc['label'] ?? 'EXTRA CHARGE'));
                        $val = (float) ($cc['value'] ?? 0);
                        $customChargesSum += $val;
                        $itemCustomMap[$lbl] = $val;
                    }
                }
            }

            $taxable  = $freight + $fuelAmt + $docket + $pickup + $delivery + $fov + $handling + $service + $misc + $customChargesSum;

            $totalBoxes   += (int)   ($item['pieces'] ?? 1);
            $totalWt      += $wt;
            $totalTaxable += $taxable;

            $rows[] = [
                'serial'        => $serial,
                'date'          => $date,
                'lrNo'          => $item['docket_no']  ?: '-',
                'invoiceNumber' => $item['invoice_no'] ?: '-',
                'origin'        => $origin,
                'destination'   => $dest,
                'boxes'         => (int) ($item['pieces'] ?? 1),
                'wt'            => $wt,
                'rate'          => $rate,
                'fuelSur'       => $fuelSur,
                'freight'       => $freight,
                'fuelAmt'       => $fuelAmt,
                'docket'        => $docket,
                'pickup'        => $pickup,
                'delivery'      => $delivery,
                'fov'           => $fov,
                'handling'      => $handling,
                'service'       => $service,
                'misc'          => $misc,
                'itemCustomMap' => $itemCustomMap,
                'taxable'       => $taxable,
            ];

            $serial++;
        }

        return [
            'rows'         => $rows,
            'totalBoxes'   => $totalBoxes,
            'totalWt'      => $totalWt,
            'totalTaxable' => $totalTaxable,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 3 — GST Calculation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calculate GST amounts. Enforces mutual exclusion: IGST zeroes CGST/SGST and vice-versa.
     *
     * @param  float  $taxable          Pre-GST total
     * @param  bool   $gstApplied       Whether GST should be calculated at all
     * @param  bool   $isIgst           True = inter-state (IGST only), False = intra-state (CGST + SGST)
     * @param  array  $ratesFromCompany e.g. ['cgst_rate' => 9, 'sgst_rate' => 9, 'igst_rate' => 18]
     * @return array  [cgstRate, sgstRate, igstRate, cgst, sgst, igst, netPayable]
     */
    public function calculateGst(
        float $taxable,
        bool  $gstApplied,
        bool  $isIgst,
        array $ratesFromCompany
    ): array {
        if (!$gstApplied) {
            return [
                'cgstRate'   => 0, 'sgstRate' => 0, 'igstRate' => 0,
                'cgst'       => 0, 'sgst'     => 0, 'igst'     => 0,
                'netPayable' => round($taxable),
            ];
        }

        $cgstRate = $isIgst ? 0 : (float) ($ratesFromCompany['cgst_rate'] ?? 9);
        $sgstRate = $isIgst ? 0 : (float) ($ratesFromCompany['sgst_rate'] ?? 9);
        $igstRate = $isIgst ? (float) ($ratesFromCompany['igst_rate'] ?? 18) : 0;

        $cgst = $isIgst ? 0.0 : round($taxable * $cgstRate / 100, 2);
        $sgst = $isIgst ? 0.0 : round($taxable * $sgstRate / 100, 2);
        $igst = $isIgst ? round($taxable * $igstRate / 100, 2) : 0.0;

        return [
            'cgstRate'   => $cgstRate,
            'sgstRate'   => $sgstRate,
            'igstRate'   => $igstRate,
            'cgst'       => $cgst,
            'sgst'       => $sgst,
            'igst'       => $igst,
            'netPayable' => round($taxable + $cgst + $sgst + $igst),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 4 — Master Lookups
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the customer's GST and PAN from the Customer Master,
     * scoped strictly to the tenant's company ID to prevent cross-tenant leaks.
     *
     * @param  string $customerName
     * @param  int    $companyId
     * @return array  ['gst' => string, 'pan' => string]
     */
    public function resolveCustomerGstDetails(string $customerName, int $companyId): array
    {
        if (empty(trim($customerName))) {
            return ['gst' => '', 'pan' => ''];
        }

        $customer = $this->customerModel
            ->where('company_id', $companyId)
            ->where('name', $customerName)
            ->first();

        // Case-insensitive fallback for name mismatch
        if (!$customer) {
            $customer = $this->customerModel
                ->where('company_id', $companyId)
                ->where('LOWER(name)', strtolower($customerName))
                ->first();
        }

        return [
            'gst' => $customer['gst_number'] ?? '',
            'pan' => $customer['pan']         ?? '',
        ];
    }

    /**
     * Load a company row, enforcing that it exists.
     *
     * @param  int   $companyId
     * @return array Company row
     * @throws InvalidArgumentException  If company not found
     */
    public function loadCompany(int $companyId): array
    {
        $company = $this->companyModel->find($companyId);
        if (!$company) {
            throw new InvalidArgumentException("Company ID {$companyId} not found.");
        }
        return $company;
    }

    /**
     * Resolve invoice bank details using a three-tier fallback:
     *   1. Specific bank selected by the user ($bankId > 0)
     *   2. Company's default bank account (is_default = 1)
     *   3. First bank account found for this company
     *   4. Legacy columns in the companies table
     *
     * @param  int    $companyId
     * @param  array  $companyData  Full company row from loadCompany()
     * @param  int    $bankId       0 = auto-select; any positive value = explicit selection
     * @return array  ['name', 'bank_name', 'ac_no', 'ifsc', 'branch', 'misc_code']
     */
    public function resolveBankDetails(int $companyId, array $companyData, int $bankId = 0): array
    {
        $bank = null;

        if ($bankId > 0) {
            $bank = $this->bankModel
                ->where('id', $bankId)
                ->where('company_id', $companyId)
                ->first();
        }

        if (!$bank) {
            $bank = $this->bankModel
                ->where('company_id', $companyId)
                ->where('is_default', 1)
                ->first();
        }

        if (!$bank) {
            $bank = $this->bankModel
                ->where('company_id', $companyId)
                ->first();
        }

        if ($bank) {
            return [
                'name'      => !empty($bank['account_name']) ? $bank['account_name'] : ($companyData['name'] ?? ''),
                'bank_name' => $bank['bank_name']     ?? '',
                'ac_no'     => $bank['account_number'] ?? '',
                'ifsc'      => $bank['ifsc_code']     ?? '',
                'branch'    => ($bank['branch_name']  ?? '') . (!empty($bank['branch_address']) ? ', ' . $bank['branch_address'] : ''),
                'misc_code' => $bank['misc_code']     ?? '',
            ];
        }

        // Tier 4: legacy company table columns
        return [
            'name'      => $companyData['name']           ?? '',
            'bank_name' => $companyData['bank_name']      ?? '',
            'ac_no'     => $companyData['account_number'] ?? '',
            'ifsc'      => $companyData['ifsc_code']      ?? '',
            'branch'    => ($companyData['branch_name']   ?? '') . (!empty($companyData['branch_address']) ? ', ' . $companyData['branch_address'] : ''),
            'misc_code' => $companyData['misc_code']      ?? '',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 5 — View Data Assembly
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Assemble the complete $viewData array required by pdfs/invoice.php.
     * This is the single authoritative point for what data goes into the PDF.
     *
     * Required keys in $params:
     *   company, recipientName, recipientAddress, customerGst, customerPan,
     *   invoiceNo, invoicePeriod, invoiceDate, billingBranch, modeTransport,
     *   shipmentRows, totalBoxes, totalWt, totalTaxable,
     *   gstData (from calculateGst()),
     *   bankDetails, bookingGstin, bookingPan, bookingSacCode,
     *   bookingSignaturePath, activeCharges, dueDate,
     *   booking (['gst_applied' => int, 'narration' => string, ...])
     *
     * @param  array $params
     * @return array $viewData ready to pass to view('pdfs/invoice', $viewData)
     */
    public function assembleViewData(array $params): array
    {
        $gst = $params['gstData'];

        return [
            'company'              => $params['company'],
            'recipientName'        => $params['recipientName'],
            'recipientAddress'     => $params['recipientAddress'],
            'customerGst'          => $params['customerGst'],
            'customerPan'          => $params['customerPan'],
            'invoiceNo'            => $params['invoiceNo'],
            'invoicePeriod'        => $params['invoicePeriod'],
            'invoiceDate'          => $params['invoiceDate'],
            'billingBranch'        => $params['billingBranch'],
            'modeTransport'        => $params['modeTransport'],
            'shipmentRows'         => $params['shipmentRows'],
            'totalBoxes'           => $params['totalBoxes'],
            'totalWt'              => $params['totalWt'],
            'totalTaxable'         => $params['totalTaxable'],
            'cgst'                 => $gst['cgst'],
            'sgst'                 => $gst['sgst'],
            'igst'                 => $gst['igst'],
            'cgstRate'             => $gst['cgstRate'],
            'sgstRate'             => $gst['sgstRate'],
            'igstRate'             => $gst['igstRate'],
            'netPayable'           => $gst['netPayable'],
            'amountInWords'        => $this->formatAmountInWords($gst['netPayable']),
            'booking'              => $params['booking'],
            'bookingGstin'         => $params['bookingGstin'],
            'bookingPan'           => $params['bookingPan'],
            'bookingSacCode'       => $params['bookingSacCode'],
            'bookingSignaturePath' => $params['bookingSignaturePath'],
            'activeCharges'        => $params['activeCharges'],
            'bankDetails'          => $params['bankDetails'],
            'dueDate'              => $params['dueDate'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 6 — Amount In Words (Indian Numbering System)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert a numeric INR amount into English words using Indian numbering.
     * e.g. 12345.50 → "Twelve Thousand Three Hundred Forty Five Rupees and Fifty Paise"
     */
    public function formatAmountInWords(float $amount): string
    {
        $whole    = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);

        $words = ucfirst($this->numberToWords($whole)) . ' Rupees';
        if ($fraction > 0) {
            $words .= ' and ' . ucfirst($this->numberToWords($fraction)) . ' Paise';
        }
        return $words;
    }

    /**
     * Recursive number-to-words conversion with Indian lakh/crore grouping.
     */
    private function numberToWords(int $n): string
    {
        if ($n === 0) return 'zero';

        $ones = [
            '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen',
        ];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($n < 20)        return $ones[$n];
        if ($n < 100)       return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1_000)     return $ones[(int)($n / 100)] . ' hundred' . ($n % 100 ? ' ' . $this->numberToWords($n % 100) : '');
        if ($n < 100_000)   return $this->numberToWords((int)($n / 1_000))    . ' thousand' . ($n % 1_000    ? ' ' . $this->numberToWords($n % 1_000)    : '');
        if ($n < 10_000_000)return $this->numberToWords((int)($n / 100_000))  . ' lakh'    . ($n % 100_000  ? ' ' . $this->numberToWords($n % 100_000)  : '');
        return               $this->numberToWords((int)($n / 10_000_000))     . ' crore'   . ($n % 10_000_000 ? ' ' . $this->numberToWords($n % 10_000_000) : '');
    }
}