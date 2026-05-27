<?= $this->extend('layout') ?>
<?php 
$permissions = session()->get('permissions') ?? [];
if (!$permissions['can_create'] && !isset($isEdit)) {
    echo '<div class="alert alert-danger text-center"><h3>Access Denied</h3><p>Create permission required!</p><a href="' . base_url('logistics') . '" class="btn btn-primary">Go to Dashboard</a></div>';
    return;
}
?>
<?= $this->section('content') ?>

<div class="container-fluid mt-2">
    <?php if (isset($isEdit) && $isEdit): ?>
        <div class="alert alert-warning py-2 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="fas fa-edit"></i> Edit Mode - AWB: <?= esc($booking['awb_no']) ?></span>
                <span class="badge bg-dark">Booking ID: <?= $bookingId ?></span>
            </div>
        </div>
    <?= form_open('logistics/update/' . $bookingId, ['id' => 'bookingForm']) ?>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 text-dark fw-bold">New Consignment</h4>
            <a href="<?= base_url('logistics') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        <?= form_open_multipart('logistics/store', ['id' => 'bookingForm']) ?>
    <?php endif; ?>

    <input type="hidden" name="company_id" value="<?= esc($selected_company_id ?? '') ?>" required>
    <input type="hidden" name="status" id="booking_status" value="Active">
    
    <!-- ERP TABS -->
    <ul class="nav nav-tabs fw-medium" id="bookingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">
                1. Consignment Details
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">
                2. Shipment Items <span class="badge bg-primary rounded-pill ms-1" id="itemCountBadge">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab">
                3. Financials & Charges
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white p-4 mb-5 shadow-sm" id="bookingTabsContent">
        
        <!-- TAB 1: CONSIGNMENT DETAILS -->
        <div class="tab-pane fade show active" id="tab1" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-12"><h6 class="fw-bold text-primary mb-0 border-bottom pb-2">A. Routing & Documentation</h6></div>
                
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">AWB NUMBER <span class="text-danger">*</span></label>
                    <input type="text" name="awb_no" class="form-control form-control-sm border-secondary shadow-none fw-bold" 
                    value="<?= isset($booking['awb_no']) ? esc($booking['awb_no']) : '' ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">BOOKING DATE <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="booking_date" class="form-control form-control-sm shadow-none" 
                    value="<?= isset($booking['booking_date']) ? date('Y-m-d\TH:i', strtotime($booking['booking_date'])) : date('Y-m-d\TH:i') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">ORIGIN <span class="text-danger">*</span></label>
                    <select name="origin" id="origin" class="form-select form-select-sm shadow-none fw-bold" onchange="handleOtherOption(this)" required>
                        <option value="<?= esc($booking['origin'] ?? '') ?>" selected><?= esc($booking['origin'] ?? '') ?></option>
                        <?php foreach($lookups['origin'] ?? [] as $l): ?>
                            <?php if (($booking['origin'] ?? '') != $l['value']): ?>
                                <option value="<?= esc($l['value']) ?>"><?= esc($l['value']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="origin_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Origin">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">DESTINATION <span class="text-danger">*</span></label>
                    <select name="destination" id="destination" class="form-select form-select-sm shadow-none fw-bold" onchange="handleOtherOption(this)" required>
                        <option value="<?= esc($booking['destination'] ?? '') ?>" selected><?= esc($booking['destination'] ?? '') ?></option>
                        <?php foreach($lookups['destination'] ?? [] as $l): ?>
                            <?php if (($booking['destination'] ?? '') != $l['value']): ?>
                                <option value="<?= esc($l['value']) ?>"><?= esc($l['value']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="destination_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Destination">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">MODE OF TRANSPORT</label>
                    <select name="mode_transport" id="mode_transport" class="form-select form-select-sm shadow-none" onchange="handleOtherOption(this)">
                        <option value="">--Select--</option>
                        <?php foreach($lookups['mode'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['mode_transport'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="mode_transport_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Mode">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">PAYMENT TYPE</label>
                    <select name="payment_type" id="payment_type" class="form-select form-select-sm shadow-none" onchange="handleOtherOption(this)">
                        <option value="">--Select--</option>
                        <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['payment_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="payment_type_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Payment Type">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">MATERIAL TYPE</label>
                    <select name="material_type" id="material_type" class="form-select form-select-sm shadow-none" onchange="handleOtherOption(this)">
                        <option value="">--Select--</option>
                        <?php foreach($lookups['material_type'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['material_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="material_type_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Material Type">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">MATERIAL CATEGORY</label>
                    <select name="material_category" id="material_category" class="form-select form-select-sm shadow-none" onchange="handleOtherOption(this)">
                        <option value="">--Select--</option>
                        <?php foreach($lookups['material_category'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['material_category'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="material_category_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Material Category">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="gst_applied" id="gst_applied" value="1" <?= (!isset($booking['id']) || !empty($booking['gst_applied'])) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-dark" for="gst_applied">GST Applied</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label text-muted fs-7 fw-semibold">MATERIAL DETAILS</label>
                    <textarea name="material_details" class="form-control form-control-sm shadow-none" rows="2" placeholder="Enter material details..."><?= esc($booking['material_details'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- GLOBAL PARTIES (Applies to all items implicitly, but can be overridden) -->
            <div class="row g-4 mb-4">
                <div class="col-md-12"><h6 class="fw-bold text-primary mb-0 border-bottom pb-2">B. Primary Parties (Global)</h6></div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">CONSIGNOR (SHIPPER) <span class="text-danger">*</span></label>
                    <select id="global_shipper" class="form-select form-select-sm shadow-none fw-bold" onchange="autoFillShipperGlobal()" required>
                        <option value="">Select Shipper</option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= esc($c['name']) ?>"><?= esc($c['name']) ?> (<?= esc($c['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">BILL TO <span class="text-danger">*</span></label>
                    <textarea id="global_bill_to" class="form-control form-control-sm shadow-none" rows="2" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">CONSIGNEE <span class="text-danger">*</span></label>
                    <textarea id="global_consignee" class="form-control form-control-sm shadow-none" rows="2" required></textarea>
                </div>
            </div>

            <div class="row g-4 mb-3">
                <div class="col-md-12"><h6 class="fw-bold text-primary mb-0 border-bottom pb-2">C. Transport & Driver Details</h6></div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">TRANSPORTER</label>
                    <select name="transporter_name" id="transporter_name" class="form-select form-select-sm shadow-none" onchange="autoFillTransporter()">
                        <option value="<?= esc($booking['transporter_name'] ?? '') ?>" selected><?= esc($booking['transporter_name'] ?? '') ?></option>
                        <?php foreach($transporters ?? [] as $t): ?>
                            <?php if (($booking['transporter_name'] ?? '') != $t['name']): ?>
                                <option value="<?= esc($t['name']) ?>" data-mobile="<?= esc($t['mobile']) ?>"><?= esc($t['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">TRANSPORTER MOBILE</label>
                    <input type="text" name="transporter_mobile" id="transporter_mobile" class="form-control form-control-sm shadow-none" value="<?= $booking['transporter_mobile'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">DRIVER NAME</label>
                    <select name="driver_name" id="driver_name" class="form-select form-select-sm shadow-none" onchange="autoFillDriver()">
                        <option value="<?= esc($booking['driver_name'] ?? '') ?>" selected><?= esc($booking['driver_name'] ?? '') ?></option>
                        <?php foreach($drivers ?? [] as $d): ?>
                            <?php if (($booking['driver_name'] ?? '') != $d['name']): ?>
                                <option value="<?= esc($d['name']) ?>" data-mobile="<?= esc($d['mobile']) ?>" data-vehicle="<?= esc($d['vehicle_no']) ?>"><?= esc($d['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">DRIVER MOBILE</label>
                    <input type="text" name="driver_mobile" id="driver_mobile" class="form-control form-control-sm shadow-none" value="<?= $booking['driver_mobile'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">VEHICLE NO</label>
                    <input type="text" name="vehicle_no" id="vehicle_no" class="form-control form-control-sm shadow-none" value="<?= $booking['vehicle_no'] ?? '' ?>">
                </div>
            </div>
            
            <div class="text-end mt-4">
                <button type="button" class="btn btn-primary" onclick="new bootstrap.Tab(document.getElementById('tab2-tab')).show()">Proceed to Shipment Items <i class="fas fa-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- TAB 2: SHIPMENT ITEMS & GRID -->
        <div class="tab-pane fade" id="tab2" role="tabpanel">
            
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="col-md-2">
                    <label class="form-label text-muted fs-7 fw-semibold mb-1">Global Volumetric Formula</label>
                    <div class="input-group input-group-sm shadow-none">
                        <span class="input-group-text">÷</span>
                        <input type="number" name="volumetric_formula" id="volumetric_formula" class="form-control fw-bold" value="<?= esc($booking['volumetric_formula'] ?? $volumetric_formula ?? 6000) ?>" onchange="recalcAllItems()">
                    </div>
                    <small class="text-muted" style="font-size:0.65rem;">Modifying this recalculates all items below.</small>
                </div>
                <div>
                    <button type="button" class="btn btn-primary shadow-sm" onclick="openItemModal(-1)"><i class="fas fa-plus"></i> Add Item</button>
                </div>
            </div>

            <!-- Item Manifest Table -->
            <div class="table-responsive border rounded bg-white">
                <table class="table table-hover align-middle mb-0 w-100" id="manifestTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th>Sub-Shipper</th>
                            <th>Docket No</th>
                            <th>Contents</th>
                            <th>Pcs</th>
                            <th>Act Wt</th>
                            <th>Dims</th>
                            <th>Vol Wt</th>
                            <th>Chg Wt</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
            
            <div id="hiddenInputsContainer"></div> <!-- Form inputs will be generated here -->
            
            <div class="text-end mt-4">
                <button type="button" class="btn btn-primary" onclick="new bootstrap.Tab(document.getElementById('tab3-tab')).show()">Proceed to Charges <i class="fas fa-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- TAB 3: FINANCIALS & CHARGES -->
        <div class="tab-pane fade" id="tab3" role="tabpanel">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Financials & Global Charges</h6>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Flight Number</label>
                    <input type="text" name="flight_number" class="form-control form-control-sm shadow-none" value="<?= $booking['flight_number'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Airlines</label>
                    <select name="airlines" class="form-select form-select-sm shadow-none">
                        <option value="">--Select--</option>
                        <?php foreach($airlines ?? [] as $a): ?>
                            <option value="<?= esc($a['name']) ?>" <?= ($booking['airlines'] ?? '') == $a['name'] ? 'selected' : '' ?>><?= esc($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Global Rate (₹/KG)</label>
                    <input type="number" step="0.01" id="salesRate" name="rate" class="form-control form-control-sm shadow-none bg-light fw-bold" value="<?= $sales['rate'] ?? '' ?>" oninput="calcTotals()">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Total Chg. Weight (KG)</label>
                    <input type="number" step="0.01" id="salesWeight" name="weight" class="form-control form-control-sm shadow-none bg-light fw-bold tabular-nums" value="<?= $sales['weight'] ?? '' ?>" readonly>
                </div>
            </div>

            <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Additional Global Surcharges</h6>
                    <div class="row g-3">
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">AWB Agent</label><input type="number" step="0.01" name="awb_fees_agent" class="form-control form-control-sm calc-surcharge" value="<?= $sales['awb_fees_agent'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">AWB Carrier</label><input type="number" step="0.01" name="awb_fees_carrier" class="form-control form-control-sm calc-surcharge" value="<?= $sales['awb_fees_carrier'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Admin</label><input type="number" step="0.01" name="admin_charges" class="form-control form-control-sm calc-surcharge" value="<?= $sales['admin_charges'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Del Order</label><input type="number" step="0.01" name="delivery_order_charges" class="form-control form-control-sm calc-surcharge" value="<?= $sales['delivery_order_charges'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Inb Handling</label><input type="number" step="0.01" name="inbound_handling" class="form-control form-control-sm calc-surcharge" value="<?= $sales['inbound_handling'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Inb Storage</label><input type="number" step="0.01" name="inbound_storage" class="form-control form-control-sm calc-surcharge" value="<?= $sales['inbound_storage'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Outb Storage</label><input type="number" step="0.01" name="outbound_storage" class="form-control form-control-sm calc-surcharge" value="<?= $sales['outbound_storage'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">DDC</label><input type="number" step="0.01" name="ddc" class="form-control form-control-sm calc-surcharge" value="<?= $sales['ddc'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">SSC</label><input type="number" step="0.01" name="ssc" class="form-control form-control-sm calc-surcharge" value="<?= $sales['ssc'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">BTC</label><input type="number" step="0.01" name="btc" class="form-control form-control-sm calc-surcharge" value="<?= $sales['btc'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">FLC</label><input type="number" step="0.01" name="flc" class="form-control form-control-sm calc-surcharge" value="<?= $sales['flc'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">DOC</label><input type="number" step="0.01" name="doc" class="form-control form-control-sm calc-surcharge" value="<?= $sales['doc'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">TCP</label><input type="number" step="0.01" name="tcp" class="form-control form-control-sm calc-surcharge" value="<?= $sales['tcp'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Inbound TSP</label><input type="number" step="0.01" name="inbound_tsp" class="form-control form-control-sm calc-surcharge" value="<?= $sales['inbound_tsp'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Outbound TSP</label><input type="number" step="0.01" name="outbound_tsp" class="form-control form-control-sm calc-surcharge" value="<?= $sales['outbound_tsp'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Utility</label><input type="number" step="0.01" name="utility_charges" class="form-control form-control-sm calc-surcharge" value="<?= $sales['utility_charges'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">X-Ray</label><input type="number" step="0.01" name="xray_charges" class="form-control form-control-sm calc-surcharge" value="<?= $sales['xray_charges'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">ADO</label><input type="number" step="0.01" name="ado" class="form-control form-control-sm calc-surcharge" value="<?= $sales['ado'] ?? '' ?>"></div>
                        <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Misc</label><input type="number" step="0.01" name="misc_charges" class="form-control form-control-sm calc-surcharge" value="<?= $sales['misc_charges'] ?? '' ?>"></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- STICKY FOOTER -->
    <div class="sticky-footer d-flex justify-content-between align-items-center">
        <div class="d-flex gap-4 align-items-center">
            <div>
                <div class="text-muted fs-8 fw-semibold text-uppercase">Total Pieces</div>
                <div class="fs-5 fw-bold tabular-nums text-dark"><span id="sumPcs">0</span></div>
                <input type="hidden" name="total_pieces" id="input_total_pieces">
            </div>
            <div>
                <div class="text-muted fs-8 fw-semibold text-uppercase">Total Act Wt</div>
                <div class="fs-5 fw-bold tabular-nums text-dark"><span id="sumActWt">0.00</span> kg</div>
            </div>
            <div>
                <div class="text-muted fs-8 fw-semibold text-uppercase">Total Vol Wt</div>
                <div class="fs-5 fw-bold tabular-nums text-dark"><span id="sumVolWt">0.00</span> kg</div>
            </div>
            <div class="px-3 border-start border-end">
                <div class="text-primary fs-8 fw-bold text-uppercase">Chargeable Wt</div>
                <div class="fs-4 fw-bold tabular-nums text-primary"><span id="sumChgWt">0.00</span> kg</div>
                <input type="hidden" name="total_weight" id="input_total_weight">
            </div>
            <div class="ps-2">
                <div class="text-success fs-8 fw-bold text-uppercase">Net Payable (Inc. GST)</div>
                <div class="fs-4 fw-bold tabular-nums text-success" id="netPayableAmount">₹0.00</div>
                <div class="fs-8 text-muted">Taxable: <span id="totalTaxableAmount">₹0.00</span></div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary bg-white" onclick="saveAsDraft()">Save Draft</button>
            <button type="submit" id="mainSubmitBtn" class="btn btn-success fw-bold px-4 shadow-sm">
                <?php if (isset($isEdit) && $isEdit): ?>Update Booking<?php else: ?>Create Booking<?php endif; ?>
            </button>
        </div>
    </div>

    <?= form_close() ?>
</div>

<!-- ITEM DRAWER -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-wide" tabindex="-1" id="itemModal" data-bs-backdrop="true">
  <div class="offcanvas-header bg-light">
    <h5 class="offcanvas-title fw-bold text-primary" id="itemModalLabel"><i class="fas fa-box-open me-2"></i> Shipment Item Details</h5>
    <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body position-relative p-0">
    <div class="erp-drawer-content pb-5">
        <input type="hidden" id="entry_edit_index" value="-1">
        
        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Item Parties & References</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Customer / Sub-Shipper</label>
                <input type="text" id="entry_customer" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Bill To</label>
                <input type="text" id="entry_bill_to" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Consignee</label>
                <input type="text" id="entry_consignee" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Docket No / Ref</label>
                <input type="text" id="entry_docket" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Invoice No</label>
                <input type="text" id="entry_invoice" class="form-control form-control-sm">
            </div>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Dimensions & Weight</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Contents Description <span class="text-danger">*</span></label>
                <input type="text" id="entry_contents" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-1">
                <label class="fs-8 text-muted fw-semibold">Pcs <span class="text-danger">*</span></label>
                <input type="number" id="entry_pcs" class="form-control form-control-sm tabular-nums" value="1" min="1">
            </div>
            <div class="col-md-2">
                <label class="fs-8 text-muted fw-semibold">Actual Wt (kg) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" id="entry_act_wt" class="form-control form-control-sm tabular-nums" oninput="calcEntryVol()">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Dims (L × W × H) cm</label>
                <div class="d-flex gap-1">
                    <input type="number" id="entry_l" class="form-control form-control-sm tabular-nums" placeholder="L" oninput="calcEntryVol()">
                    <input type="number" id="entry_w" class="form-control form-control-sm tabular-nums" placeholder="W" oninput="calcEntryVol()">
                    <input type="number" id="entry_h" class="form-control form-control-sm tabular-nums" placeholder="H" oninput="calcEntryVol()">
                </div>
            </div>
            <div class="col-md-2">
                <label class="fs-8 text-muted fw-semibold">Volumetric Wt (kg)</label>
                <input type="text" id="entry_vol_wt" class="form-control form-control-sm bg-light tabular-nums" readonly>
            </div>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Item Specific Charges</h6>
        <div class="row g-3">
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">E-Way No</label><input type="text" id="entry_eway_no" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">E-Way Date</label><input type="date" id="entry_eway_date" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Item Rate</label><input type="number" step="0.01" id="entry_rate" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Delivery Charge</label><input type="number" step="0.01" id="entry_delivery" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Docket Charge</label><input type="number" step="0.01" id="entry_docket_chg" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Pickup Charge</label><input type="number" step="0.01" id="entry_pickup" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Fuel Surcharge</label><input type="number" step="0.01" id="entry_fuel" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">FOV Charge</label><input type="number" step="0.01" id="entry_fov" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Handling Charge</label><input type="number" step="0.01" id="entry_handling" class="form-control form-control-sm tabular-nums"></div>
            <div class="col-md-2"><label class="fs-8 text-muted fw-semibold">Service Charge</label><input type="number" step="0.01" id="entry_service" class="form-control form-control-sm tabular-nums"></div>
        </div>
      </div>
    </div>
    <div class="sticky-footer">
      <button type="button" class="btn btn-outline-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
      <button type="button" class="btn btn-primary px-5 fw-bold shadow-sm" onclick="saveItemToGrid()"><i class="fas fa-check me-2"></i> Save Item</button>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
</style>

<script>
    let items = [];
    const _customers = <?= json_encode($customers ?? []) ?>;
    const _companyGst = { 
        cgst: <?= isset($company['cgst_rate']) && $company['cgst_rate'] !== '' ? $company['cgst_rate'] : 9 ?>, 
        sgst: <?= isset($company['sgst_rate']) && $company['sgst_rate'] !== '' ? $company['sgst_rate'] : 9 ?>, 
        igst: <?= isset($company['igst_rate']) && $company['igst_rate'] !== '' ? $company['igst_rate'] : 0 ?> 
    };
    const initialShipments = <?= json_encode($shipments ?? []) ?>;
    
    let manifestDataTable;

    $(document).ready(function() {
        // Initialize DataTable for Grid
        manifestDataTable = ERPUtils.initDataTable('#manifestTable', null, [
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { data: 'customer' },
            { data: 'docket' },
            { data: 'contents' },
            { data: 'pcs', className: 'tabular-nums' },
            { data: 'act_wt', className: 'tabular-nums', render: data => data.toFixed(2) },
            { 
                data: null, 
                className: 'tabular-nums text-muted',
                render: function(data, type, row) {
                    return row.l ? `${row.l}x${row.w}x${row.h}` : '-';
                }
            },
            { data: 'vol_wt', className: 'tabular-nums', render: data => data.toFixed(2) },
            { data: 'chg_wt', className: 'tabular-nums fw-bold text-primary', render: data => data.toFixed(2) },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function(data, type, row, meta) {
                    return `
                        <button type="button" class="btn btn-sm btn-light text-primary border me-1 shadow-none" onclick="openItemModal(${meta.row})"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-light text-danger border shadow-none" onclick="deleteItem(${meta.row})"><i class="fas fa-trash"></i></button>
                    `;
                }
            }
        ]);
        
        // Remove length/search controls to save space
        $('#manifestTable_wrapper .row:first').hide();
        $('#manifestTable_wrapper .row:last').hide();
        
        if(initialShipments.length > 0) {
            // Load global parties from first item
            $('#global_shipper').val(initialShipments[0].customer_name || '');
            $('#global_bill_to').val(initialShipments[0].bill_to || '');
            $('#global_consignee').val(initialShipments[0].consignee || '');
            
            initialShipments.forEach(s => {
                items.push({
                    id: s.id || '',
                    customer: s.customer_name || '',
                    bill_to: s.bill_to || '',
                    consignee: s.consignee || '',
                    docket: s.docket_no || '',
                    invoice_no: s.invoice_no || '',
                    contents: s.part_no || 'Goods',
                    pcs: parseInt(s.pieces) || 1,
                    act_wt: parseFloat(s.actual_weight) || 0,
                    l: parseFloat(s.length) || 0,
                    w: parseFloat(s.width) || 0,
                    h: parseFloat(s.height) || 0,
                    vol_wt: parseFloat(s.volumetric_weight) || 0,
                    chg_wt: parseFloat(s.final_chargeable_weight) || 0,
                    // New charges
                    eway_no: s.eway_no || '',
                    eway_date: s.eway_date || '',
                    rate: s.rate || '',
                    delivery_charges: s.delivery_charges || '',
                    docket_charges: s.docket_charges || '',
                    pickup_charges: s.pickup_charges || '',
                    fuel_surcharge: s.fuel_surcharge || '',
                    fov_charges: s.fov_charges || '',
                    handling_charges: s.handling_charges || '',
                    service_charges: s.service_charges || ''
                });
            });
            renderGrid();
        }
        calcTotals();
    });

    function saveAsDraft() {
        $('#booking_status').val('Draft');
        $('#bookingForm').submit();
    }

    function autoFillShipperGlobal() {
        const name = $('#global_shipper').val();
        const c = _customers.find(x => x.name === name);
        if (c) {
            if (c.bill_to) $('#global_bill_to').val(c.bill_to);
            if (c.consignee) $('#global_consignee').val(c.consignee);
            if (c.payment_type) $('#payment_type').val(c.payment_type);
        }
    }
    
    function autoFillTransporter() {
        const opt = $('#transporter_name option:selected');
        if (opt.length) $('#transporter_mobile').val(opt.data('mobile') || '');
    }

    function autoFillDriver() {
        const selected = $('#driver_name option:selected');
        $('#vehicle_no').val(selected.data('vehicle') || '');
        $('#driver_mobile').val(selected.data('mobile') || '');
    }

    function calcEntryVol() {
        const l = parseFloat($('#entry_l').val()) || 0;
        const w = parseFloat($('#entry_w').val()) || 0;
        const h = parseFloat($('#entry_h').val()) || 0;
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        if(l>0 && w>0 && h>0) {
            const vol = (l * w * h) / formula;
            $('#entry_vol_wt').val(vol.toFixed(2));
        } else {
            $('#entry_vol_wt').val('');
        }
    }

    function openItemModal(index) {
        $('#entry_edit_index').val(index);
        
        if (index === -1) {
            // New Item
            $('#itemModalLabel').text('Add Shipment Item');
            
            // Context Retention from previous item or globals
            if (items.length > 0) {
                const prev = items[items.length - 1];
                $('#entry_customer').val(prev.customer);
                $('#entry_bill_to').val(prev.bill_to);
                $('#entry_consignee').val(prev.consignee);
                $('#entry_docket').val(prev.docket);
                $('#entry_invoice').val(prev.invoice_no);
            } else {
                $('#entry_customer').val($('#global_shipper').val());
                $('#entry_bill_to').val($('#global_bill_to').val());
                $('#entry_consignee').val($('#global_consignee').val());
                $('#entry_docket').val('');
                $('#entry_invoice').val('');
            }
            
            $('#entry_contents').val('');
            $('#entry_pcs').val('1');
            $('#entry_act_wt').val('');
            $('#entry_l, #entry_w, #entry_h, #entry_vol_wt').val('');
            
            // Clear charges
            $('#entry_eway_no, #entry_eway_date, #entry_rate, #entry_delivery, #entry_docket_chg, #entry_pickup, #entry_fuel, #entry_fov, #entry_handling, #entry_service').val('');
            
        } else {
            // Edit Item
            $('#itemModalLabel').text('Edit Shipment Item');
            const item = items[index];
            
            $('#entry_customer').val(item.customer);
            $('#entry_bill_to').val(item.bill_to);
            $('#entry_consignee').val(item.consignee);
            $('#entry_docket').val(item.docket);
            $('#entry_invoice').val(item.invoice_no);
            
            $('#entry_contents').val(item.contents);
            $('#entry_pcs').val(item.pcs);
            $('#entry_act_wt').val(item.act_wt);
            $('#entry_l').val(item.l || '');
            $('#entry_w').val(item.w || '');
            $('#entry_h').val(item.h || '');
            calcEntryVol();
            
            $('#entry_eway_no').val(item.eway_no);
            $('#entry_eway_date').val(item.eway_date);
            $('#entry_rate').val(item.rate);
            $('#entry_delivery').val(item.delivery_charges);
            $('#entry_docket_chg').val(item.docket_charges);
            $('#entry_pickup').val(item.pickup_charges);
            $('#entry_fuel').val(item.fuel_surcharge);
            $('#entry_fov').val(item.fov_charges);
            $('#entry_handling').val(item.handling_charges);
            $('#entry_service').val(item.service_charges);
        }
        
        var modal = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('itemModal'));
        modal.show();
    }

    function saveItemToGrid() {
        const contents = $('#entry_contents').val();
        const act_wt = parseFloat($('#entry_act_wt').val()) || 0;
        
        if(!contents || act_wt <= 0) {
            ERPUtils.showWarning("Missing Data", "Contents Description and Actual Weight are required.");
            return;
        }

        const l = parseFloat($('#entry_l').val()) || 0;
        const w = parseFloat($('#entry_w').val()) || 0;
        const h = parseFloat($('#entry_h').val()) || 0;
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        const vol_wt = (l*w*h)/formula;
        const chg_wt = Math.max(act_wt, vol_wt); // User requested NO MIN 45KG. Just max of actual or vol.

        const editIndex = parseInt($('#entry_edit_index').val());
        
        const itemObj = {
            id: editIndex >= 0 ? items[editIndex].id : '',
            customer: $('#entry_customer').val(),
            bill_to: $('#entry_bill_to').val(),
            consignee: $('#entry_consignee').val(),
            docket: $('#entry_docket').val(),
            invoice_no: $('#entry_invoice').val(),
            contents: contents,
            pcs: parseInt($('#entry_pcs').val()) || 1,
            act_wt: act_wt,
            l: l, w: w, h: h,
            vol_wt: vol_wt,
            chg_wt: chg_wt,
            
            eway_no: $('#entry_eway_no').val(),
            eway_date: $('#entry_eway_date').val(),
            rate: $('#entry_rate').val(),
            delivery_charges: $('#entry_delivery').val(),
            docket_charges: $('#entry_docket_chg').val(),
            pickup_charges: $('#entry_pickup').val(),
            fuel_surcharge: $('#entry_fuel').val(),
            fov_charges: $('#entry_fov').val(),
            handling_charges: $('#entry_handling').val(),
            service_charges: $('#entry_service').val(),
        };

        if(editIndex >= 0) {
            items[editIndex] = itemObj;
        } else {
            items.push(itemObj);
        }
        
        isDirty = true;
        renderGrid();
        
        // Hide modal
        var modalEl = document.getElementById('itemModal');
        var modal = bootstrap.Offcanvas.getInstance(modalEl);
        if(modal) modal.hide();

        Swal.fire({
            title: "Success",
            text: editIndex >= 0 ? "Item updated successfully!" : "Item added to grid!",
            icon: "success",
            timer: 1500,
            showConfirmButton: false
        });
    }

    function deleteItem(index) {
        items.splice(index, 1);
        isDirty = true;
        renderGrid();
    }

    function renderGrid() {
        $('#hiddenInputsContainer').empty();
        
        let sumPcs = 0, sumAct = 0, sumVol = 0, sumChg = 0;

        // Update DataTable
        manifestDataTable.clear().rows.add(items).draw(false);
        $('#itemCountBadge').text(items.length);

        items.forEach((item, idx) => {
            sumPcs += item.pcs; sumAct += item.act_wt; sumVol += item.vol_wt; sumChg += item.chg_wt;
            
            // Generate Hidden Inputs for Form Submission
            const hiddens = `
                <input type="hidden" name="items[${idx}][id]" value="${item.id}">
                <input type="hidden" name="items[${idx}][customer_name]" value="${item.customer}">
                <input type="hidden" name="items[${idx}][bill_to]" value="${item.bill_to}">
                <input type="hidden" name="items[${idx}][consignee]" value="${item.consignee}">
                <input type="hidden" name="items[${idx}][docket_no]" value="${item.docket}">
                <input type="hidden" name="items[${idx}][invoice_no]" value="${item.invoice_no}">
                <input type="hidden" name="items[${idx}][part_no]" value="${item.contents}">
                <input type="hidden" name="items[${idx}][pieces]" value="${item.pcs}">
                <input type="hidden" name="items[${idx}][actual_weight]" value="${item.act_wt}">
                <input type="hidden" name="items[${idx}][length]" value="${item.l}">
                <input type="hidden" name="items[${idx}][width]" value="${item.w}">
                <input type="hidden" name="items[${idx}][height]" value="${item.h}">
                <input type="hidden" name="items[${idx}][volumetric_weight]" value="${item.vol_wt}">
                <input type="hidden" name="items[${idx}][calculated_chargeable_weight]" value="${item.chg_wt}">
                <input type="hidden" name="items[${idx}][chargeable_weight]" value="${item.chg_wt}">
                
                <input type="hidden" name="items[${idx}][eway_no]" value="${item.eway_no}">
                <input type="hidden" name="items[${idx}][eway_date]" value="${item.eway_date}">
                <input type="hidden" name="items[${idx}][rate]" value="${item.rate}">
                <input type="hidden" name="items[${idx}][delivery_charges]" value="${item.delivery_charges}">
                <input type="hidden" name="items[${idx}][docket_charges]" value="${item.docket_charges}">
                <input type="hidden" name="items[${idx}][pickup_charges]" value="${item.pickup_charges}">
                <input type="hidden" name="items[${idx}][fuel_surcharge]" value="${item.fuel_surcharge}">
                <input type="hidden" name="items[${idx}][fov_charges]" value="${item.fov_charges}">
                <input type="hidden" name="items[${idx}][handling_charges]" value="${item.handling_charges}">
                <input type="hidden" name="items[${idx}][service_charges]" value="${item.service_charges}">
            `;
            $('#hiddenInputsContainer').append(hiddens);
        });

        // Update Footers
        $('#sumPcs').text(sumPcs);
        $('#sumActWt').text(sumAct.toFixed(2));
        $('#sumVolWt').text(sumVol.toFixed(2));
        $('#sumChgWt').text(sumChg.toFixed(2));
        
        $('#input_total_pieces').val(sumPcs);
        $('#input_total_weight').val(sumChg.toFixed(2));
        $('#salesWeight').val(sumChg.toFixed(2));
        
        calcTotals();
    }

    function recalcAllItems() {
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        items.forEach(item => {
            item.vol_wt = (item.l * item.w * item.h) / formula;
            item.chg_wt = Math.max(item.act_wt, item.vol_wt);
        });
        isDirty = true;
        renderGrid();
    }

    function calcTotals() {
        let taxable = 0;
        const rate = parseFloat($('#salesRate').val()) || 0;
        const weight = parseFloat($('#salesWeight').val()) || 0;
        taxable += rate * weight;

        $('.calc-surcharge').each(function() {
            taxable += parseFloat($(this).val()) || 0;
        });

        const isGstApplied = $('#gst_applied').is(':checked');
        let cgst = isGstApplied ? Math.round(taxable * (_companyGst.cgst / 100)) : 0;
        let sgst = isGstApplied ? Math.round(taxable * (_companyGst.sgst / 100)) : 0;
        let igst = isGstApplied ? Math.round(taxable * (_companyGst.igst / 100)) : 0;
        let netPayable = taxable + cgst + sgst + igst;

        $('#totalTaxableAmount').text('₹' + taxable.toFixed(2));
        $('#netPayableAmount').text('₹' + netPayable.toFixed(2));
    }

    $(document).on('input', '#salesRate, .calc-surcharge', calcTotals);
    $(document).on('change', '#gst_applied', calcTotals);

    $('#bookingForm').on('submit', function() {
        if(items.length === 0) {
            ERPUtils.showWarning("Missing Data", "Please add at least one shipment item.");
            return false;
        }
        isDirty = false; // allow navigation without prompt
        $('#mainSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    });

    function handleOtherOption(selectEl) {
        const inputId = selectEl.id + '_other';
        const inputEl = document.getElementById(inputId);
        if (!inputEl) return;
        
        if (selectEl.value === 'Other') {
            inputEl.classList.remove('d-none');
            inputEl.setAttribute('name', selectEl.getAttribute('name'));
            inputEl.setAttribute('required', 'required');
            selectEl.removeAttribute('name');
        } else {
            inputEl.classList.add('d-none');
            selectEl.setAttribute('name', inputEl.getAttribute('name') || selectEl.id);
            inputEl.removeAttribute('name');
            inputEl.removeAttribute('required');
        }
    }
</script>
<?= $this->endSection() ?>