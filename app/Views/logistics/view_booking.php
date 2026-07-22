<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid py-4 bg-light min-vh-100">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex flex-column gap-1">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= base_url('logistics') ?>" class="btn btn-outline-secondary bg-white shadow-sm fw-bold">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h4 class="mb-0 fw-bold text-dark">Booking Details: <span class="text-primary"><?= esc($booking['awb_no']) ?></span></h4>
            </div>
            <div class="text-muted fs-8 ms-5 ps-4">
                <i class="fas fa-clock me-1"></i> Last modified: <?= date('Y-m-d h:i A', strtotime($booking['updated_at'] ?? $booking['booking_date'])) ?> by <span class="fw-bold"><?= esc($booking['created_by_name']) ?></span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ((session()->get('permissions')['can_edit'] ?? 0) == 1): ?>
                <a href="<?= base_url('logistics/edit/' . $booking['id']) ?>" class="btn btn-outline-dark bg-white shadow-sm fw-bold">
                    <i class="fas fa-pen"></i> Edit Booking
                </a>
            <?php endif; ?>
            <a href="<?= base_url('logistics/exportPdf/' . $booking['id']) ?>" class="btn btn-outline-dark bg-white shadow-sm fw-bold">
                <i class="fas fa-file-pdf text-danger"></i> Export PDF
            </a>
            <button type="button" class="btn btn-primary shadow-sm fw-bold" title="Update Tracking / POD"
                onclick="openTrackingDrawer('<?= $booking['id'] ?>', '<?= esc($booking['awb_no']) ?>', '<?= esc(addslashes($booking['customer_name'] ?? '')) ?>')">
                <i class="fas fa-location-dot"></i> Update Tracking / POD
            </button>
        </div>
    </div>

    <!-- Basic Info Grid -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">AWB NUMBER</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['awb_no']) ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">BOOKING DATE</div>
                    <div class="fw-bold fs-6 text-dark"><?= date('Y-m-d', strtotime($booking['booking_date'])) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">COMPANY</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['company_name']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">ORIGIN / DEST</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['origin']) ?> <i class="fas fa-arrow-right text-muted mx-1"></i> <?= esc($booking['destination']) ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">MODE</div>
                    <div class="fw-bold fs-6 text-dark"><i class="fas <?= stripos($booking['mode_transport'], 'air') !== false ? 'fa-plane' : (stripos($booking['mode_transport'], 'train') !== false ? 'fa-train' : 'fa-truck') ?> text-primary"></i> <?= esc($booking['mode_transport']) ?></div>
                </div>

                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">CREATED BY</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['created_by_name']) ?></div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">MATERIAL TYPE</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['material_type']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">FLIGHT NO</div>
                    <div class="fw-bold fs-6 text-dark"><?= esc($booking['flight_number']) ?: '-' ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">TOTAL PIECES</div>
                    <div class="fw-bold fs-6 text-dark"><?= $booking['total_pieces'] ?> Pcs</div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="text-uppercase text-muted fs-8 fw-bold mb-1">STATUS</div>
                    <div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold border border-success px-2 py-1">
                            <?= strtoupper($booking['status'] ?? 'CONFIRMED') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Split -->
    <div class="row g-4">
        <!-- Left Column: Shipment Items -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-end mb-2 px-1">
                <h6 class="fw-bold text-dark mb-0 fs-5">Shipment Items</h6>
                <span class="text-muted fw-bold"><?= count($shipments) ?> Items Total</span>
            </div>
            <div class="card shadow-sm border-0 rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted fs-7">
                            <tr>
                                <th class="ps-4 fw-bold">#</th>
                                <th class="fw-bold">Customer / Consignee</th>
                                <th class="fw-bold">Docket / Invoice</th>
                                <th class="text-center fw-bold">Pcs</th>
                                <th class="text-end fw-bold">Act. Wt</th>
                                <th class="text-end fw-bold">Chg. Wt</th>
                                <th class="text-end pe-4 fw-bold">Total Chgs</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php 
                            $sumPcs = 0; $sumActWt = 0; $sumChgWt = 0; $sumChgs = 0;
                            foreach($shipments as $index => $item): 
                                $sumPcs += $item['pieces'] ?? 1;
                                $sumActWt += $item['actual_weight'];
                                $sumChgWt += $item['final_chargeable_weight'];
                                
                                $itemCustomSum = 0;
                                $itemCustomList = [];
                                if (!empty($item['custom_charges'])) {
                                    $itemCustomList = is_string($item['custom_charges']) ? json_decode($item['custom_charges'], true) : $item['custom_charges'];
                                    if (is_array($itemCustomList)) {
                                        foreach ($itemCustomList as $cc) {
                                            $itemCustomSum += (float)($cc['value'] ?? 0);
                                        }
                                    } else {
                                        $itemCustomList = [];
                                    }
                                }

                                $itemChgs = ($item['rate'] * $item['final_chargeable_weight']) + 
                                            ($item['delivery_charges'] ?? 0) + 
                                            ($item['docket_charges'] ?? 0) + 
                                            ($item['pickup_charges'] ?? 0) + 
                                            ($item['fuel_surcharge'] ?? 0) + 
                                            ($item['fov_charges'] ?? 0) + 
                                            ($item['handling_charges'] ?? 0) + 
                                            ($item['service_charges'] ?? 0) +
                                            ($item['misc_charges'] ?? 0) +
                                            $itemCustomSum;
                                $sumChgs += $itemChgs;
                            ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-7"><?= esc($item['customer_name']) ?></div>
                                    <div class="text-muted fs-8"><?= esc($item['consignee']) ?></div>
                                    <?php if (!empty($itemCustomList)): ?>
                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                            <?php foreach ($itemCustomList as $cc): ?>
                                                <?php if (!empty($cc['label']) || (float)($cc['value'] ?? 0) > 0): ?>
                                                    <span class="badge bg-light text-primary border me-1 fw-semibold" style="font-size:0.7rem;">
                                                        <i class="fas fa-tag me-1"></i><?= esc($cc['label'] ?: 'Extra Charge') ?>: ₹<?= number_format((float)($cc['value'] ?? 0), 2) ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-7"><?= esc($item['docket_no']) ?></div>
                                    <div class="text-muted fs-8"><?= esc($item['invoice_no'] ?? '-') ?></div>
                                </td>
                                <td class="text-center fw-semibold text-dark"><?= $item['pieces'] ?? 1 ?></td>
                                <td class="text-end fw-semibold text-dark"><?= number_format((float)$item['actual_weight'], 1) ?></td>
                                <td class="text-end fw-semibold text-dark"><?= number_format((float)$item['final_chargeable_weight'], 1) ?></td>
                                <td class="text-end pe-4 fw-bold text-dark">₹<?= number_format((float)$itemChgs, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light border-top">
                            <tr>
                                <td colspan="3" class="text-end fw-bold text-dark py-3">GRAND TOTALS:</td>
                                <td class="text-center fw-bold text-dark"><?= $sumPcs ?></td>
                                <td class="text-end fw-bold text-dark"><?= number_format((float)$sumActWt, 1) ?> kg</td>
                                <td class="text-end fw-bold text-dark"><?= number_format((float)$sumChgWt, 1) ?> kg</td>
                                <td class="text-end pe-4 fw-bold text-primary fs-6">₹<?= number_format((float)$sumChgs, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Custom Tax & GST / Signature Details for this Booking -->
            <div class="card shadow-sm border-0 rounded-3 mt-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-signature me-1"></i> Custom Tax, GST &amp; Signature Settings for this Booking</h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6 border-end">
                            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-3"><i class="fas fa-percent"></i> Tax &amp; GST Configuration</h6>
                            <table class="table table-sm table-borderless fs-7 mb-0">
                                <tr>
                                    <td class="text-muted fw-medium py-1" style="width: 35%;">GSTIN:</td>
                                    <td class="fw-bold text-dark py-1"><?= esc(($booking['gstin'] ?? '') ?: ($company['gstin'] ?? '') ?: 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium py-1">PAN:</td>
                                    <td class="fw-bold text-dark py-1"><?= esc(($booking['pan'] ?? '') ?: ($company['pan'] ?? '') ?: 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium py-1">SAC Code:</td>
                                    <td class="fw-bold text-dark py-1"><?= esc(($booking['sac_code'] ?? '') ?: ($company['sac_code'] ?? '') ?: 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-medium py-1">GST Rates:</td>
                                    <td class="fw-bold text-dark py-1">
                                        CGST: <span class="badge bg-light text-dark fw-bold border py-1 px-2 me-1"><?= number_format((float)($booking['cgst_rate'] ?? $company['cgst_rate'] ?? 9.00), 2) ?>%</span>
                                        SGST: <span class="badge bg-light text-dark fw-bold border py-1 px-2 me-1"><?= number_format((float)($booking['sgst_rate'] ?? $company['sgst_rate'] ?? 9.00), 2) ?>%</span>
                                        IGST: <span class="badge bg-light text-dark fw-bold border py-1 px-2"><?= number_format((float)($booking['igst_rate'] ?? $company['igst_rate'] ?? 9.00), 2) ?>%</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-3"><i class="fas fa-signature"></i> Signature Preview</h6>
                            <?php 
                            $sigPath = ($booking['signature_path'] ?? '') ?: ($company['signature_path'] ?? '');
                            if (!empty($sigPath)): 
                            ?>
                                <div class="bg-light p-3 rounded text-center border d-inline-block" style="min-width: 200px;">
                                    <img src="<?= base_url($sigPath) ?>" style="max-height: 70px; max-width: 100%; mix-blend-mode: multiply;">
                                    <div class="mt-2 text-muted fs-9 fw-semibold">
                                        <?= !empty($booking['signature_path']) ? '<i class="fas fa-check-circle text-success me-1"></i> Custom Booking Signature' : '<i class="fas fa-info-circle text-primary me-1"></i> Company Default Signature' ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted fs-7 py-3">No digital signature uploaded or drawn.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Financial Summary -->
        <div class="col-lg-4">
            <h6 class="fw-bold text-dark mb-2 px-1 fs-5">Financial Summary</h6>
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <?php if ($sales): 
                        $baseFreight = ($sales['rate'] * $sales['weight']);
                        
                        $surcharges = [
                            'Fuel Surcharge (FLC)' => $sales['flc'] ?? 0,
                            'Docket Charges (DOC)' => $sales['doc'] ?? 0,
                            'DDC' => $sales['ddc'] ?? 0,
                            'SSC' => $sales['ssc'] ?? 0,
                            'BTC' => $sales['btc'] ?? 0,
                            'TCP' => $sales['tcp'] ?? 0,
                            'Inbound TSP' => $sales['inbound_tsp'] ?? 0,
                            'Outbound TSP' => $sales['outbound_tsp'] ?? 0,
                            'Utility' => $sales['utility_charges'] ?? 0,
                            'X-Ray' => $sales['xray_charges'] ?? 0,
                            'ADO' => $sales['ado'] ?? 0,
                            'AWB Agent' => $sales['awb_fees_agent'] ?? 0,
                            'AWB Carrier' => $sales['awb_fees_carrier'] ?? 0,
                            'Admin' => $sales['admin_charges'] ?? 0,
                            'Delivery Order' => $sales['delivery_order_charges'] ?? 0,
                            'Inbound Handling' => $sales['inbound_handling'] ?? 0,
                            'Inbound Storage' => $sales['inbound_storage'] ?? 0,
                            'Outbound Storage' => $sales['outbound_storage'] ?? 0,
                            'Misc Charges' => $sales['misc_charges'] ?? 0,
                        ];

                        $customGlobalList = [];
                        if (!empty($sales['custom_charges'])) {
                            $customGlobalList = is_string($sales['custom_charges']) ? json_decode($sales['custom_charges'], true) : $sales['custom_charges'];
                            if (is_array($customGlobalList)) {
                                foreach ($customGlobalList as $gc) {
                                    $gLabel = !empty($gc['label']) ? $gc['label'] : 'Custom Surcharge';
                                    $gVal = (float)($gc['value'] ?? 0);
                                    if (!empty($gc['label']) || $gVal > 0) {
                                        $surcharges[$gLabel] = $gVal;
                                    }
                                }
                            } else {
                                $customGlobalList = [];
                            }
                        }

                        $subtotal = $baseFreight;
                        foreach($surcharges as $amt) $subtotal += $amt;

                        // GST Calculation
                        $cgst = 0; $sgst = 0; $igst = 0;
                        $cgstRate = isset($booking['cgst_rate']) ? (float)$booking['cgst_rate'] : (float)($company['cgst_rate'] ?? 0);
                        $sgstRate = isset($booking['sgst_rate']) ? (float)$booking['sgst_rate'] : (float)($company['sgst_rate'] ?? 0);
                        $igstRate = isset($booking['igst_rate']) ? (float)$booking['igst_rate'] : (float)($company['igst_rate'] ?? 0);

                        if (!empty($booking['gst_applied'])) {
                            $cgst = $subtotal * ($cgstRate / 100);
                            $sgst = $subtotal * ($sgstRate / 100);
                            $igst = $subtotal * ($igstRate / 100);
                        }
                        
                        $grandTotal = $subtotal + $cgst + $sgst + $igst;
                    ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">Base Freight</span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$baseFreight, 2) ?></span>
                        </div>
                        
                        <?php foreach($surcharges as $label => $amt): if($amt > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold"><?= esc($label) ?></span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$amt, 2) ?></span>
                        </div>
                        <?php endif; endforeach; ?>

                        <hr class="text-muted opacity-25 my-3">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">Subtotal</span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$subtotal, 2) ?></span>
                        </div>

                        <?php if($cgst > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">CGST (<?= $cgstRate ?>%)</span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$cgst, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($sgst > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">SGST (<?= $sgstRate ?>%)</span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$sgst, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if($igst > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted fw-semibold">IGST (<?= $igstRate ?>%)</span>
                            <span class="fw-bold text-dark">₹<?= number_format((float)$igst, 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Full Charges Breakdown (Collapsible or small text) -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="text-muted fw-bold fs-7 mb-2">Charges Breakdown:</div>
                            <div class="row g-1 text-muted fs-8">
                                <div class="col-6">DDC: ₹<?= number_format((float)($sales['ddc'] ?? 0), 2) ?></div>
                                <div class="col-6">SSC: ₹<?= number_format((float)($sales['ssc'] ?? 0), 2) ?></div>
                                <div class="col-6">BTC: ₹<?= number_format((float)($sales['btc'] ?? 0), 2) ?></div>
                                <div class="col-6">FLC: ₹<?= number_format((float)($sales['flc'] ?? 0), 2) ?></div>
                                <div class="col-6">DOC: ₹<?= number_format((float)($sales['doc'] ?? 0), 2) ?></div>
                                <div class="col-6">TCP: ₹<?= number_format((float)($sales['tcp'] ?? 0), 2) ?></div>
                                <div class="col-6">Inbound TSP: ₹<?= number_format((float)($sales['inbound_tsp'] ?? 0), 2) ?></div>
                                <div class="col-6">Outbound TSP: ₹<?= number_format((float)($sales['outbound_tsp'] ?? 0), 2) ?></div>
                                <div class="col-6">Utility: ₹<?= number_format((float)($sales['utility_charges'] ?? 0), 2) ?></div>
                                <div class="col-6">X-Ray: ₹<?= number_format((float)($sales['xray_charges'] ?? 0), 2) ?></div>
                                <div class="col-6">ADO: ₹<?= number_format((float)($sales['ado'] ?? 0), 2) ?></div>
                                <div class="col-6">AWB Agent: ₹<?= number_format((float)($sales['awb_fees_agent'] ?? 0), 2) ?></div>
                                <div class="col-6">AWB Carrier: ₹<?= number_format((float)($sales['awb_fees_carrier'] ?? 0), 2) ?></div>
                                <div class="col-6">Admin: ₹<?= number_format((float)($sales['admin_charges'] ?? 0), 2) ?></div>
                                <div class="col-6">Delivery Order: ₹<?= number_format((float)($sales['delivery_order_charges'] ?? 0), 2) ?></div>
                                <div class="col-6">Inbound Handling: ₹<?= number_format((float)($sales['inbound_handling'] ?? 0), 2) ?></div>
                                <div class="col-6">Inbound Storage: ₹<?= number_format((float)($sales['inbound_storage'] ?? 0), 2) ?></div>
                                <div class="col-6">Outbound Storage: ₹<?= number_format((float)($sales['outbound_storage'] ?? 0), 2) ?></div>
                                <div class="col-6">Misc: ₹<?= number_format((float)($sales['misc_charges'] ?? 0), 2) ?></div>
                                <?php foreach ($customGlobalList as $gc): ?>
                                    <?php if (!empty($gc['label']) || (float)($gc['value'] ?? 0) > 0): ?>
                                        <div class="col-6 text-primary fw-semibold"><?= esc($gc['label'] ?: 'Custom Surcharge') ?>: ₹<?= number_format((float)($gc['value'] ?? 0), 2) ?></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-muted text-center py-4">No financial data available.</div>
                        <?php $grandTotal = 0; ?>
                    <?php endif; ?>
                </div>

                <div class="bg-dark text-white p-4">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="text-uppercase text-white-50 fs-8 fw-bold mb-1">GRAND TOTAL (INR)</div>
                            <div class="fs-3 fw-bold lh-1">₹<?= number_format((float)($grandTotal ?? 0), 2) ?></div>
                        </div>
                        <button class="btn btn-primary fw-bold px-4 shadow-sm">
                            Finalize Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking History -->
    <div class="card shadow-sm border-0 rounded-3 mt-4" id="booking-tracking-history">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Tracking History
            </h6>
            <span class="badge bg-light text-dark border px-3 py-2 fw-bold">AWB: <?= esc($booking['awb_no']) ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="viewBookingTrackingHistory">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 5%">#</th>
                            <th style="width: 15%">Date</th>
                            <th style="width: 10%">Time</th>
                            <th style="width: 20%">Location</th>
                            <th style="width: 15%">Status</th>
                            <th class="pe-4" style="width: 35%">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($trackingHistory)): ?>
                            <?php foreach ($trackingHistory as $index => $entry): ?>
                                <?php
                                $statusClass = match ($entry['status']) {
                                    'Delivered' => 'bg-success',
                                    'In Transit' => 'bg-warning text-dark',
                                    'Picked Up' => 'bg-info text-dark',
                                    'Booked' => 'bg-primary',
                                    default => 'bg-secondary',
                                };
                                $displayTime = $entry['event_time'];
                                if ($displayTime && strlen($displayTime) > 5) {
                                    $displayTime = substr($displayTime, 0, 5);
                                }
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                    <td class="fw-medium"><?= esc($entry['event_date']) ?></td>
                                    <td><?= esc($displayTime) ?></td>
                                    <td class="fw-bold text-dark">
                                        <i class="fa-solid fa-location-dot me-1 text-primary"></i><?= esc($entry['current_location']) ?>
                                    </td>
                                    <td><span class="badge <?= $statusClass ?>"><?= esc($entry['status']) ?></span></td>
                                    <td class="pe-4">
                                        <?= esc($entry['remarks'] ?: '-') ?>
                                        <?php if (!empty($entry['proof_image'])): ?>
                                            <a href="<?= base_url($entry['proof_image']) ?>" target="_blank" class="ms-2 text-primary" title="View Proof">
                                                <i class="fa-solid fa-image"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No tracking history available for this shipment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?= $this->include('logistics/pod_tracking_drawer') ?>

<style>
    .fs-7 { font-size: 0.875rem; }
    .fs-8 { font-size: 0.75rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.url.indexOf('tracking/save') !== -1 || settings.url.indexOf('tracking/delete') !== -1) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            } catch (e) {
                // Ignore parsing errors
            }
        }
    });
});
</script>
<?= $this->endSection() ?>