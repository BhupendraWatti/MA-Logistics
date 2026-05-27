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
            <!-- 
            <button class="btn btn-outline-dark bg-white shadow-sm fw-bold">
                <i class="fas fa-print"></i> Print AWB
            </button>
            <button class="btn btn-primary shadow-sm fw-bold">
                <i class="fas fa-satellite-dish"></i> Track Shipment
            </button>
            -->
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
                                
                                $itemChgs = ($item['rate'] * $item['final_chargeable_weight']) + 
                                            ($item['delivery_charges'] ?? 0) + 
                                            ($item['docket_charges'] ?? 0) + 
                                            ($item['pickup_charges'] ?? 0) + 
                                            ($item['fuel_surcharge'] ?? 0) + 
                                            ($item['fov_charges'] ?? 0) + 
                                            ($item['handling_charges'] ?? 0) + 
                                            ($item['service_charges'] ?? 0);
                                $sumChgs += $itemChgs;
                            ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-7"><?= esc($item['customer_name']) ?></div>
                                    <div class="text-muted fs-8"><?= esc($item['consignee']) ?></div>
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

                        $subtotal = $baseFreight;
                        foreach($surcharges as $amt) $subtotal += $amt;

                        // GST Calculation
                        $cgst = 0; $sgst = 0; $igst = 0;
                        $cgstRate = (float)($company['cgst_rate'] ?? 0);
                        $sgstRate = (float)($company['sgst_rate'] ?? 0);
                        $igstRate = (float)($company['igst_rate'] ?? 0);

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
                            <span class="text-muted fw-semibold"><?= $label ?></span>
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

</div>

<style>
    .fs-7 { font-size: 0.875rem; }
    .fs-8 { font-size: 0.75rem; }
</style>
<?= $this->endSection() ?>