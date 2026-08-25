<?php

namespace App\Services;

use App\Models\BankAccountModel;
use App\Models\CompanyModel;
use App\Models\CustomerModel;
use App\Models\InvoiceTemplateModel;
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
    protected InvoiceTemplateModel $invoiceTemplateModel;

    public function __construct()
    {
        $this->companyModel  = new CompanyModel();
        $this->customerModel = new CustomerModel();
        $this->bankModel     = new BankAccountModel();
        $this->invoiceTemplateModel = new InvoiceTemplateModel();
    }

    public function finalizeConsolidatedInvoiceNumber(
        int $companyId,
        array $shipments,
        array $companyData,
        string $invoiceDate,
        ?string $requestedInvoiceNo = null,
        int $invoiceTemplateId = 0
    ): string {
        $itemIds = array_values(array_filter(array_map(static fn ($item) => (int) ($item['id'] ?? 0), $shipments)));
        if (empty($itemIds)) {
            throw new InvalidArgumentException('No shipment items were selected for invoice finalization.');
        }

        $existingNumbers = [];
        foreach ($shipments as $item) {
            $invoiceNo = trim((string) ($item['invoice_no'] ?? ''));
            if ($invoiceNo !== '') {
                $existingNumbers[$invoiceNo] = true;
            }
        }

        $db = \Config\Database::connect();

        if (count($existingNumbers) === 1) {
            $invoiceNo = array_key_first($existingNumbers);
            $persistedInvoiceDate = $invoiceDate;
            foreach ($shipments as $item) {
                if (trim((string) ($item['invoice_no'] ?? '')) === $invoiceNo && !empty($item['invoice_date'])) {
                    $persistedInvoiceDate = $item['invoice_date'];
                    break;
                }
            }

            $db->table('shipment_items')
                ->whereIn('id', $itemIds)
                ->update([
                    'invoice_no'   => $invoiceNo,
                    'invoice_date' => $persistedInvoiceDate,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            return $invoiceNo;
        }

        $db->transStart();

        $prefix = $this->resolveInvoicePrefix($companyData, $requestedInvoiceNo, $companyId, $invoiceTemplateId);
        $financialYear = $this->financialYearLabel($invoiceDate);
        $now = date('Y-m-d H:i:s');

        $db->query(
            'INSERT INTO invoice_sequences (company_id, financial_year, prefix, last_number, created_at, updated_at)
             VALUES (?, ?, ?, 0, ?, ?)
             ON DUPLICATE KEY UPDATE updated_at = updated_at',
            [$companyId, $financialYear, $prefix, $now, $now]
        );

        $sequence = $db->query(
            'SELECT * FROM invoice_sequences WHERE company_id = ? AND financial_year = ? AND prefix = ? FOR UPDATE',
            [$companyId, $financialYear, $prefix]
        )->getRowArray();

        if (!$sequence) {
            throw new \RuntimeException('Invoice sequence row could not be locked.');
        }

        $nextNumber = ((int) ($sequence['last_number'] ?? 0)) + 1;
        $invoiceNo = sprintf('%s-%s/%03d', $prefix, $financialYear, $nextNumber);

        $db->table('invoice_sequences')
            ->where('id', (int) $sequence['id'])
            ->update([
                'last_number' => $nextNumber,
                'updated_at'  => $now,
            ]);

        $db->table('shipment_items')
            ->whereIn('id', $itemIds)
            ->update([
                'invoice_no'   => $invoiceNo,
                'invoice_date' => $invoiceDate,
                'updated_at'   => $now,
            ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to finalize the invoice number.');
        }

        return $invoiceNo;
    }

    public function applyInvoiceNumberToShipments(array $shipments, string $invoiceNo, string $invoiceDate): array
    {
        foreach ($shipments as &$item) {
            $item['invoice_no'] = $invoiceNo;
            $item['invoice_date'] = $invoiceDate;
        }

        return $shipments;
    }

    public function financialYearLabel(string $date): string
    {
        $timestamp = strtotime($date) ?: time();
        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);
        $startYear = $month >= 4 ? $year : $year - 1;
        $endYear = $startYear + 1;

        return sprintf('%02d-%02d', $startYear % 100, $endYear % 100);
    }

    private function resolveInvoicePrefix(array $companyData, ?string $requestedInvoiceNo = null, int $companyId = 0, int $invoiceTemplateId = 0): string
    {
        if ($invoiceTemplateId > 0 && $companyId > 0) {
            $template = $this->invoiceTemplateModel
                ->where('id', $invoiceTemplateId)
                ->where('company_id', $companyId)
                ->where('is_active', 1)
                ->first();
            if ($template && trim((string) ($template['prefix'] ?? '')) !== '') {
                return $this->normalizeInvoicePrefix((string) $template['prefix']);
            }
        }

        $configured = trim((string) ($companyData['invoice_prefix'] ?? ''));
        if ($configured !== '') {
            return $this->normalizeInvoicePrefix($configured);
        }

        $requestedPrefix = $this->extractRequestedInvoicePrefix((string) $requestedInvoiceNo);
        if ($requestedPrefix !== '') {
            return $requestedPrefix;
        }

        $companyName = trim((string) ($companyData['name'] ?? 'MA Logistics'));
        preg_match_all('/[A-Za-z0-9]+/', $companyName, $parts);
        $tokens = $parts[0] ?? [];

        if (count($tokens) > 1) {
            $initials = '';
            foreach ($tokens as $token) {
                $initials .= strtoupper($token[0]);
            }
            return substr($initials, 0, 8) ?: 'MA';
        }

        return substr($this->normalizeInvoicePrefix($companyName), 0, 8) ?: 'MA';
    }

    private function extractRequestedInvoicePrefix(string $requestedInvoiceNo): string
    {
        if (preg_match('/^([A-Za-z0-9]+)/', trim($requestedInvoiceNo), $matches)) {
            return $this->normalizeInvoicePrefix($matches[1]);
        }

        return '';
    }

    private function normalizeInvoicePrefix(string $prefix): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix));
        return substr($normalized ?: 'MA', 0, 20);
    }

    private function clubShipmentsByDocket(array $shipments): array
    {
        $grouped = [];

        foreach ($shipments as $item) {
            $docketNo = trim((string) ($item['docket_no'] ?? ''));
            $key = $docketNo !== '' ? $docketNo : '__row_' . count($grouped);

            if (!isset($grouped[$key])) {
                $item['_freight_override'] = 0.0;
                $item['_custom_charges_grouped'] = [];
                $grouped[$key] = $item;
                $grouped[$key]['pieces'] = 0;
                $grouped[$key]['final_chargeable_weight'] = 0.0;

                foreach (['fuel_surcharge', 'docket_charges', 'pickup_charges', 'delivery_charges', 'fov_charges', 'handling_charges', 'service_charges', 'misc_charges'] as $field) {
                    $grouped[$key][$field] = 0.0;
                }
            }

            $wt = (float) ($item['final_chargeable_weight'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $grouped[$key]['pieces'] += (int) ($item['pieces'] ?? 0);
            $grouped[$key]['final_chargeable_weight'] += $wt;
            $grouped[$key]['_freight_override'] += $wt * $rate;

            foreach (['fuel_surcharge', 'docket_charges', 'pickup_charges', 'delivery_charges', 'fov_charges', 'handling_charges', 'service_charges', 'misc_charges'] as $field) {
                $grouped[$key][$field] += (float) ($item[$field] ?? 0);
            }

            if (!empty($item['custom_charges'])) {
                $customList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                if (is_array($customList)) {
                    foreach ($customList as $charge) {
                        $label = strtoupper(trim($charge['label'] ?? 'EXTRA CHARGE'));
                        $grouped[$key]['_custom_charges_grouped'][$label] = ($grouped[$key]['_custom_charges_grouped'][$label] ?? 0) + (float) ($charge['value'] ?? 0);
                    }
                }
            }
        }

        foreach ($grouped as &$item) {
            $weight = (float) ($item['final_chargeable_weight'] ?? 0);
            $freight = (float) ($item['_freight_override'] ?? 0);
            $item['rate'] = $weight > 0 ? round($freight / $weight, 2) : 0;

            if (!empty($item['_custom_charges_grouped'])) {
                $customCharges = [];
                foreach ($item['_custom_charges_grouped'] as $label => $value) {
                    $customCharges[] = ['label' => $label, 'value' => $value];
                }
                $item['custom_charges'] = json_encode($customCharges);
            }

            unset($item['_custom_charges_grouped']);
        }

        return array_values($grouped);
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

        return array_filter($all, fn ($c) => $c['sum'] > 0);
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
        string $fallbackDest   = '',
        bool   $clubByDocket   = false
    ): array {
        if ($clubByDocket) {
            $shipments = $this->clubShipmentsByDocket($shipments);
        }

        $serial       = 1;
        $totalBoxes   = 0;
        $totalWt      = 0.0;
        $totalTaxable = 0.0;
        $rows         = [];

        foreach ($shipments as $item) {
            // Client invoice grid date must reflect the booking date.
            $rawDate = $item['booking_date'] ?? $item['invoice_date'] ?? null;
            $date    = $rawDate ? date('d.m.y', strtotime($rawDate)) : '-';

            // Origin / Destination: use JOIN'd booking fields if present
            $originRaw = $item['booking_origin']      ?? $fallbackOrigin;
            $destRaw   = $item['booking_destination'] ?? $fallbackDest;
            $origin    = trim(explode(',', $originRaw ?: 'Pune')[0]);
            $dest      = trim(explode(',', $destRaw   ?: '')[0]);

            $wt       = (float) ($item['final_chargeable_weight']  ?? 0);
            $rate     = (float) ($item['rate']           ?? 0);
            $fuelSur  = (float) ($item['fuel_surcharge'] ?? 0);
            $freight  = isset($item['_freight_override']) ? (float) $item['_freight_override'] : ($wt * $rate);
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
                'dateRaw'       => $rawDate,
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
     * @return array  ['gst' => string, 'pan' => string, 'address' => string, 'state' => string]
     */
    public function resolveCustomerGstDetails(string $customerName, int $companyId): array
    {
        if (empty(trim($customerName))) {
            return ['gst' => '', 'pan' => '', 'address' => '', 'state' => ''];
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
            'address' => $customer['address'] ?? '',
            'state' => $customer['gst_state'] ?? ($customer['state'] ?? ''),
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
