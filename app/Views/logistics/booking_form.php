<?= $this->extend('layout') ?>
<?php 
$booking = $booking ?? [];
$company = $company ?? [];
$sales = $sales ?? [];
$permissions = session()->get('permissions') ?? [];
// Merge with old input if redirecting back after a validation error
foreach (['awb_no', 'booking_date', 'origin', 'destination', 'mode_transport', 'payment_type', 'material_type', 'material_category', 'gst_applied', 'material_details', 'transporter_name', 'transporter_mobile', 'driver_name', 'driver_mobile', 'driver_license_no', 'vehicle_no', 'total_pieces', 'total_weight', 'volumetric_formula', 'narration', 'airlines', 'flight_number', 'cgst_rate', 'sgst_rate', 'igst_rate'] as $field) {
    if (old($field) !== null) {
        $booking[$field] = old($field);
    }
}
foreach (['rate', 'weight', 'awb_fees_agent', 'awb_fees_carrier', 'admin_charges', 'delivery_order_charges', 'inbound_handling', 'inbound_storage', 'outbound_storage', 'ddc', 'ssc', 'btc', 'flc', 'doc', 'tcp', 'inbound_tsp', 'outbound_tsp', 'utility_charges', 'xray_charges', 'ado', 'misc_charges'] as $field) {
    if (old($field) !== null) {
        $sales[$field] = old($field);
    }
}
$shipments_raw = old('items_json');
if ($shipments_raw !== null) {
    $decoded = json_decode($shipments_raw, true);
    if (is_array($decoded)) {
        $shipments = [];
        foreach ($decoded as $item) {
            $shipments[] = [
                'id' => $item['id'] ?? '',
                'customer_name' => $item['customer'] ?? '',
                'bill_to' => $item['bill_to'] ?? '',
                'consignee' => $item['consignee'] ?? '',
                'docket_no' => $item['docket'] ?? '',
                'invoice_no' => $item['invoice_no'] ?? '',
                'part_no' => $item['part_no'] ?? '',
                'part_qty' => $item['part_qty'] ?? 0,
                'invoice_date' => $item['invoice_date'] ?? '',
                'pieces' => $item['pcs'] ?? 1,
                'actual_weight' => $item['act_wt'] ?? 0,
                'length' => $item['l'] ?? 0,
                'width' => $item['w'] ?? 0,
                'height' => $item['h'] ?? 0,
                'volumetric_weight' => $item['vol_wt'] ?? 0,
                'final_chargeable_weight' => $item['chg_wt'] ?? 0,
                'eway_no' => $item['eway_no'] ?? '',
                'eway_date' => $item['eway_date'] ?? '',
                'rate' => $item['rate'] ?? '',
                'delivery_charges' => $item['delivery_charges'] ?? '',
                'docket_charges' => $item['docket_charges'] ?? '',
                'pickup_charges' => $item['pickup_charges'] ?? '',
                'fuel_surcharge' => $item['fuel_surcharge'] ?? '',
                'fov_charges' => $item['fov_charges'] ?? '',
                'handling_charges' => $item['handling_charges'] ?? '',
                'service_charges' => $item['service_charges'] ?? '',
                'misc_charges' => $item['misc_charges'] ?? '',
                'misc_charges_name' => $item['misc_charges_name'] ?? 'Misc Charges'
            ];
        }
    }
}
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
    <?= form_open_multipart('logistics/update/' . $bookingId, ['id' => 'bookingForm']) ?>
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
    <input type="hidden" name="redirect_to_booking_id" id="redirect_to_booking_id" value="">

    <!-- Quick Switch AWB Dropdown at top -->
    <div class="card p-3 mb-3 border-0 bg-light rounded shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white fw-bold text-muted border-secondary"><i class="fas fa-search me-1 text-primary"></i> Quick Switch AWB:</span>
                    <select id="awb_select_top" class="form-select fw-bold border-secondary shadow-none no-track">
                        <option value="">-- Select AWB to Edit --</option>
                        <?php foreach ($previous_bookings ?? [] as $pb): ?>
                            <option value="<?= $pb['id'] ?>" <?= (isset($booking['id']) && $booking['id'] == $pb['id']) ? 'selected' : '' ?>>
                                <?= esc($pb['awb_no']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php if (empty($booking['origin'])): ?>
                            <option value="" selected disabled>--Select Origin--</option>
                        <?php else: ?>
                            <option value="<?= esc($booking['origin']) ?>" selected><?= esc($booking['origin']) ?></option>
                        <?php endif; ?>
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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php if (empty($booking['destination'])): ?>
                            <option value="" selected disabled>--Select Destination--</option>
                        <?php else: ?>
                            <option value="<?= esc($booking['destination']) ?>" selected><?= esc($booking['destination']) ?></option>
                        <?php endif; ?>
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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
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
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php foreach($lookups['material_category'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['material_category'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                    <input type="text" id="material_category_other" class="form-control form-control-sm shadow-none mt-1 d-none" placeholder="Enter Material Category">
                </div>
                <div class="col-md-3 d-flex flex-column justify-content-end d-none" id="gst_applied_container">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="gst_applied" id="gst_applied" value="1" <?= (!isset($booking['id']) || !empty($booking['gst_applied'])) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold text-dark" for="gst_applied">GST Applied</label>
                    </div>
                </div>

                <div class="col-md-12">
                    <small id="gst_warning_msg" class="text-warning fw-bold d-none" style="font-size: 0.8rem;">
                        <i class="fas fa-exclamation-triangle me-1"></i> Selected customer has no GST number — GST will not be applied.
                    </small>
                </div>

                <div class="col-md-12">
                    <label class="form-label text-muted fs-7 fw-semibold">MATERIAL DETAILS</label>
                    <textarea name="material_details" class="form-control form-control-sm shadow-none" rows="2" placeholder="Enter material details..."><?= esc($booking['material_details'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- GLOBAL PARTIES (Applies to all items implicitly, but can be overridden) -->
            <div class="row g-4 mb-4 d-none">
                <div class="col-md-12"><h6 class="fw-bold text-primary mb-0 border-bottom pb-2">B. Primary Parties (Global)</h6></div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">CONSIGNOR (SHIPPER)</label>
                    <select id="global_shipper" class="form-select form-select-sm shadow-none fw-bold" onchange="autoFillShipperGlobal()">
                        <option value="">Select Shipper</option>
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php foreach($customers ?? [] as $c): ?>
                            <option value="<?= esc($c['name']) ?>"><?= esc($c['name']) ?> (<?= esc($c['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">BILL TO</label>
                    <textarea id="global_bill_to" class="form-control form-control-sm shadow-none" rows="2" maxlength="250"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted fs-7 fw-semibold">CONSIGNEE</label>
                    <textarea id="global_consignee" class="form-control form-control-sm shadow-none" rows="2" maxlength="250"></textarea>
                </div>
            </div>

            <div class="row g-4 mb-3">
                <div class="col-md-12"><h6 class="fw-bold text-primary mb-0 border-bottom pb-2">C. Transport & Driver Details</h6></div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">TRANSPORTER</label>
                    <select name="transporter_name" id="transporter_name" class="form-select form-select-sm shadow-none" onchange="autoFillTransporter()">
                        <option value="">--Select Transporter--</option>
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php if (!empty($booking['transporter_name'])): ?>
                            <option value="<?= esc($booking['transporter_name']) ?>" selected><?= esc($booking['transporter_name']) ?></option>
                        <?php endif; ?>
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
                        <option value="">--Select Driver--</option>
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php if (!empty($booking['driver_name'])): ?>
                            <option value="<?= esc($booking['driver_name']) ?>" selected><?= esc($booking['driver_name']) ?></option>
                        <?php endif; ?>
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
                            <th>AWB No.</th>
                            <th>Docket No</th>
                            <th>Customer</th>
                            <th>Invoice No</th>
                            <th>Part NO.</th>
                            <th>Part Qty</th>
                            <th>Pcs/Boxes</th>
                            <th>Act Wt</th>
                            <th>Vol Wt</th>
                            <th>Chg Wt</th>
                            <th>Item Rate</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
            
            <input type="hidden" name="items_json" id="items_json">
            <div id="hiddenInputsContainer"></div>
            
            <div class="text-end mt-4">
                <button type="button" class="btn btn-primary" onclick="new bootstrap.Tab(document.getElementById('tab3-tab')).show()">Proceed to Charges <i class="fas fa-arrow-right ms-1"></i></button>
            </div>
        </div>

        <!-- TAB 3: FINANCIALS & CHARGES -->
        <div class="tab-pane fade" id="tab3" role="tabpanel">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Financials & Global Charges</h6>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Airlines</label>
                    <select name="airlines" id="airlines_select" class="form-select form-select-sm shadow-none">
                        <option value="">--Select--</option>
                        <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                        <?php foreach($airlines ?? [] as $a): ?>
                            <option value="<?= esc($a['name']) ?>" data-code="<?= esc($a['code'] ?? '') ?>" <?= ($booking['airlines'] ?? '') == $a['name'] ? 'selected' : '' ?>><?= esc($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Flight Number</label>
                    <input type="text" name="flight_number" class="form-control form-control-sm shadow-none" value="<?= $booking['flight_number'] ?? '' ?>">
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

            <!-- Tax & Digital Signature overrides per Booking -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6" id="gst_config_card_container">
                    <div class="card border-0 shadow-sm h-100" style="background-color: #fcfcfc; border: 1px solid #eaeaea !important;">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-percent me-1"></i> Tax &amp; GST Configuration (Customizable)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12 mb-2">
                                    <label class="form-label text-muted fs-8 fw-semibold mb-1">GST Billing Type</label>
                                    <select id="gst_type" class="form-select form-select-sm shadow-none fw-bold">
                                        <option value="intra">Intra-State (CGST + SGST)</option>
                                        <option value="inter">Inter-State (IGST)</option>
                                    </select>
                                </div>
                                <div class="col-12 border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label text-muted fs-7 fw-semibold mb-0">Booking Tax Rates (%)</label>
                                        <span class="badge bg-primary fs-8" id="totalGstBadge">Total GST: 18.00%</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-4" id="cgst_col">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted fs-8">CGST</span>
                                                <input type="number" step="0.01" min="0" max="50" name="cgst_rate" id="cgst_rate" class="form-control shadow-none tabular-nums fw-bold rate-input" value="<?= isset($booking['id']) ? esc($booking['cgst_rate']) : '9.00' ?>">
                                            </div>
                                        </div>
                                        <div class="col-4" id="sgst_col">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted fs-8">SGST</span>
                                                <input type="number" step="0.01" min="0" max="50" name="sgst_rate" id="sgst_rate" class="form-control shadow-none tabular-nums fw-bold rate-input" value="<?= isset($booking['id']) ? esc($booking['sgst_rate']) : '9.00' ?>">
                                            </div>
                                        </div>
                                        <div class="col-4" id="igst_col">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-muted fs-8">IGST</span>
                                                <input type="number" step="0.01" min="0" max="50" name="igst_rate" id="igst_rate" class="form-control shadow-none tabular-nums fw-bold rate-input" value="<?= isset($booking['id']) ? esc($booking['igst_rate']) : '0.00' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100" style="background-color: #fcfcfc; border: 1px solid #eaeaea !important;">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-invoice me-1"></i> Digital Signature</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label text-muted fs-7 fw-semibold">Upload Signature Image <small class="text-muted">(PNG/JPG/JPEG)</small></label>
                                    <input type="file" name="signature_image" class="form-control form-control-sm shadow-none" accept="image/png,image/jpeg,image/jpg">
                                    <?php if (!empty($booking['signature_path'])): ?>
                                        <div class="mt-2 bg-light p-2 rounded d-inline-block position-relative w-100">
                                            <small class="text-muted d-block mb-1">Current Signature for this Booking:</small>
                                            <img src="<?= base_url(esc($booking['signature_path'])) ?>" style="max-height:60px; mix-blend-mode: multiply;">
                                            <div class="mt-2">
                                                <a href="<?= base_url('logistics/deleteSignature/' . $booking['id']) ?>" class="btn btn-sm btn-danger shadow-sm py-1 px-2 fw-bold" onclick="return confirm('Are you sure you want to delete this booking\'s signature?');">
                                                    <i class="fas fa-trash"></i> Delete Signature
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif (!empty($company['signature_path'])): ?>
                                        <div class="mt-2 bg-light p-2 rounded d-inline-block position-relative w-100 opacity-75">
                                            <small class="text-muted d-block mb-1">Using Company Default Signature:</small>
                                            <img src="<?= base_url(esc($company['signature_path'])) ?>" style="max-height:60px; mix-blend-mode: multiply;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <label class="form-label text-muted fs-7 fw-semibold">Or Draw Your Signature</label>
                                    <div class="border rounded bg-light p-2 text-center">
                                        <div class="bg-white border rounded mb-2 overflow-hidden" style="height: 120px;">
                                            <canvas id="signatureCanvas" style="width: 100%; height: 120px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" id="clearCanvas" class="btn btn-xs btn-outline-warning text-dark fw-bold shadow-sm py-1 px-2 fs-8">
                                                <i class="fas fa-eraser"></i> Clear Drawing
                                            </button>
                                        </div>
                                        <input type="hidden" name="signature_base64" id="signatureBase64">
                                    </div>
                                </div>
                            </div>
                        </div>
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
            <?php if (isset($isEdit) && $isEdit && isset($bookingId)): ?>
                <a href="<?= base_url('logistics/exportPdf/' . $bookingId) ?>" class="btn btn-outline-danger bg-white fw-bold shadow-sm" target="_blank">
                    <i class="fas fa-file-pdf text-danger me-1"></i> Export PDF
                </a>
            <?php endif; ?>
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
                <select id="entry_customer" class="form-select form-select-sm shadow-none">
                    <option value="">Select Shipper</option>
                    <option value="NEW_ENTRY" style="color: #20c997; font-weight: bold;">+ NEW ENTRY</option>
                    <?php foreach($customers ?? [] as $c): ?>
                        <option value="<?= esc($c['name']) ?>"><?= esc($c['name']) ?> (<?= esc($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Bill To</label>
                <input type="text" id="entry_bill_to" class="form-control form-control-sm" maxlength="250">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Consignee</label>
                <input type="text" id="entry_consignee" class="form-control form-control-sm" maxlength="250">
            </div>
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="fs-8 text-muted fw-semibold">Docket No / Ref</label>
                    <div class="form-check mb-0">
                        <?php
                        $autoGenChecked = true;
                        if (isset($booking['id']) && !empty($shipments)) {
                            foreach ($shipments as $s) {
                                $docketNo = $s['docket_no'] ?? '';
                                if ($docketNo !== '' && strpos($docketNo, 'DCK-') !== 0) {
                                    $autoGenChecked = false;
                                    break;
                                }
                            }
                        }
                        ?>
                        <input class="form-check-input" type="checkbox" id="modal_auto_generate_docket" value="1" <?= $autoGenChecked ? 'checked' : '' ?> style="transform: scale(0.85); margin-top: 0.15rem;">
                        <label class="form-check-label fs-8 text-muted fw-semibold" for="modal_auto_generate_docket">Auto</label>
                    </div>
                </div>
                <input type="text" id="entry_docket" class="form-control form-control-sm shadow-none" placeholder="Enter Docket No manually">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Invoice No</label>
                <input type="text" id="entry_invoice" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Part NO.</label>
                <input type="text" id="entry_part_no" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Invoice Date</label>
                <input type="date" id="entry_invoice_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="fs-8 text-muted fw-semibold">Total Part Quantity</label>
                <input type="number" id="entry_part_qty" class="form-control form-control-sm" value="0" min="0">
            </div>
        </div>

        <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary">Dimensions & Weight</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-2 d-none">
                <label class="fs-8 text-muted fw-semibold">Contents Description <span class="text-danger">*</span></label>
                <input type="text" id="entry_contents" class="form-control form-control-sm" value="Goods" required>
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
            <div class="col-md-2">
                <label class="fs-8 text-muted fw-semibold">Chargeable Wt (kg)</label>
                <input type="number" step="0.01" id="entry_chg_wt" class="form-control form-control-sm tabular-nums">
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
            <div class="col-md-2">
                <input type="text" id="entry_misc_name" class="form-control form-control-sm border-0 bg-transparent fw-semibold text-muted p-0 fs-8 shadow-none text-truncate" value="Misc Charges" style="cursor: text;" title="Click to rename this charge field">
                <input type="number" step="0.01" id="entry_misc" class="form-control form-control-sm tabular-nums">
            </div>
        </div>
      </div>
    </div>
    <div class="sticky-footer">
      <button type="button" class="btn btn-outline-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
      <button type="button" class="btn btn-primary px-5 fw-bold shadow-sm" onclick="saveItemToGrid()"><i class="fas fa-check me-2"></i> Save Item</button>
    </div>
  </div>
<!-- QUICK MASTER CREATION MODAL -->
<div class="modal fade" id="quickMasterModal" tabindex="-1" aria-labelledby="quickMasterModalLabel" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white py-3">
        <h6 class="modal-title fw-bold" id="quickMasterModalLabel"><i class="fas fa-plus-circle me-2"></i> Add New Master Entry</h6>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <form id="quickMasterForm" autocomplete="off">
            <?= csrf_field() ?>
            <!-- Dynamic Fields Container -->
            <div id="quickMasterFields"></div>
        </form>
      </div>
      <div class="modal-footer bg-white border-top-0 pt-0">
        <button type="button" class="btn btn-outline-secondary fw-bold px-3 py-1.5 fs-7" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary fw-bold px-4 py-1.5 fs-7 shadow-sm" id="btnSaveQuickMaster"><i class="fas fa-check me-2"></i> Save Entry</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
</style>

<script>
    let items = [];
    window.isDirty = false;

    function updateDocketInputState() {
        const isAutoGen = $('#modal_auto_generate_docket').is(':checked');
        const editIndex = $('#entry_edit_index').val();
        
        // Only apply auto-gen preview/manual carryover if we are adding a NEW item
        if (editIndex === '-1') {
            if (isAutoGen) {
                // Show "DCK-XXXXX" instead of empty/fetching message while calling the preview endpoint
                $('#entry_docket').val('DCK-XXXXX').prop('readonly', true).css('color', '#888888');
                
                let previewDockets = items.map(i => i.docket).filter(d => d);
                $.ajax({
                    url: BASE_URL + 'masters/dockets/preview',
                    type: 'POST',
                    data: { exclude_dockets: previewDockets },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success' && $('#entry_edit_index').val() === '-1' && $('#modal_auto_generate_docket').is(':checked')) {
                            $('#entry_docket').val(res.docket_no).css('color', '#888888');
                        } else if (res.status !== 'success') {
                            console.error("Docket preview error status returned:", res.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Docket preview AJAX call failed details:", {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        $('#entry_docket').val('DCK-XXXXX').css('color', '#888888');
                    }
                });
            } else {
                let nextDocket = '';
                if (items.length > 0) {
                    // Carry over the exact manual docket number from the last added item without incrementing
                    nextDocket = items[items.length - 1].docket || '';
                }
                $('#entry_docket').val(nextDocket).prop('readonly', false).css('color', '').attr('placeholder', 'Enter Docket No manually');
            }
        }
    }
    const _customers = <?= json_encode($customers ?? []) ?>;
    const _companyGst = { 
        cgst: <?= isset($company['cgst_rate']) && $company['cgst_rate'] !== '' ? $company['cgst_rate'] : 9 ?>, 
        sgst: <?= isset($company['sgst_rate']) && $company['sgst_rate'] !== '' ? $company['sgst_rate'] : 9 ?>, 
        igst: <?= isset($company['igst_rate']) && $company['igst_rate'] !== '' ? $company['igst_rate'] : 0 ?> 
    };
    const initialShipments = <?= json_encode($shipments ?? []) ?>;
    
    let manifestDataTable;

    $(document).ready(function() {
        $('#awb_select_top').on('change', function() {
            let targetId = $(this).val();
            if (targetId) {
                const isNewBooking = <?= !isset($booking['id']) ? 'true' : 'false' ?>;
                const hasItems = items.length > 0;
                const dirty = typeof window.checkDirty === 'function' ? window.checkDirty() : (!!window.isDirty);
                
                if (isNewBooking && !hasItems) {
                    window.isDirty = false;
                    window.allowLeave = true;
                    window.location.href = BASE_URL + 'logistics/edit/' + targetId;
                    return;
                }
                
                if (!dirty) {
                    window.isDirty = false;
                    window.allowLeave = true;
                    window.location.href = BASE_URL + 'logistics/edit/' + targetId;
                    return;
                }
                
                Swal.fire({
                    title: 'Switching Booking',
                    text: 'Your current changes will be saved as a Draft, and you will be redirected to edit the selected booking.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.allowLeave = true;
                        $('#redirect_to_booking_id').val(targetId);
                        saveAsDraft();
                    } else {
                        $(this).val('<?= $booking['id'] ?? '' ?>');
                    }
                });
            }
        });

        $('#airlines_select').on('change', function() {
            const code = $(this).find('option:selected').data('code') || '';
            $('input[name="flight_number"]').val(code);
        });

        $('input[name="awb_no"]').on('input change', function() {
            if (manifestDataTable) {
                manifestDataTable.rows().invalidate().draw(false);
            }
        });

        // Handle auto-generate docket checkbox change
        $('#modal_auto_generate_docket').on('change', function() {
            updateDocketInputState();
        });

        // Dynamic visual character counters for fields with maxlength attribute
        $('[maxlength]').each(function() {
            const $field = $(this);
            const maxLength = parseInt($field.attr('maxlength'));
            
            // Create counter element
            const $counter = $('<div class="char-counter text-muted text-end mt-1" style="font-size: 0.75rem; font-weight: 600; min-height: 18px;"></div>');
            $field.after($counter);
            
            function updateCounter() {
                const currentLength = $field.val().length;
                const remaining = maxLength - currentLength;
                
                $counter.text(`${remaining} characters remaining / limit: ${maxLength}`);
                
                if (remaining <= 20) {
                    $counter.removeClass('text-muted text-warning').addClass('text-danger');
                } else if (remaining <= 50) {
                    $counter.removeClass('text-muted text-danger').addClass('text-warning');
                } else {
                    $counter.removeClass('text-danger text-warning').addClass('text-muted');
                }
            }
            
            $field.on('input propertychange change keyup paste', updateCounter);
            // Re-sync on modal openings or value bindings
            $field.on('sync-counter', updateCounter);
            updateCounter();
        });

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
            {
                data: null,
                render: function() {
                    return $('input[name="awb_no"]').val() || '';
                }
            },
            { data: 'docket' },
            { data: 'customer' },
            { data: 'invoice_no' },
            { data: 'part_no' },
            { data: 'part_qty', className: 'tabular-nums' },
            { data: 'pcs', className: 'tabular-nums' },
            { data: 'act_wt', className: 'tabular-nums', render: data => parseFloat(data || 0).toFixed(2) },
            { data: 'vol_wt', className: 'tabular-nums', render: data => parseFloat(data || 0).toFixed(2) },
            { data: 'chg_wt', className: 'tabular-nums fw-bold text-primary', render: data => parseFloat(data || 0).toFixed(2) },
            { data: 'rate', className: 'tabular-nums', render: data => data ? parseFloat(data).toFixed(2) : '0.00' },
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
            // Load global parties from first item - match exactly the stored customer_name as the option value
            const storedCustName = initialShipments[0].customer_name || '';
            // The select option values are the plain customer name (not name+code)
            // Try exact match first, then partial match
            let matched = false;
            $('#global_shipper option').each(function() {
                if ($(this).val() === storedCustName) {
                    $('#global_shipper').val(storedCustName);
                    matched = true;
                    return false; // break
                }
            });
            if (!matched && storedCustName) {
                // Try to find option whose value starts with the stored name
                $('#global_shipper option').each(function() {
                    if ($(this).val().startsWith(storedCustName)) {
                        $('#global_shipper').val($(this).val());
                        return false;
                    }
                });
            }
            
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
                    part_no: s.part_no || '',
                    part_qty: parseInt(s.part_qty) || 0,
                    invoice_date: s.invoice_date || '',
                    contents: s.part_no || 'Goods',
                    pcs: parseInt(s.pieces) || 1,
                    act_wt: parseFloat(s.actual_weight) || 0,
                    l: parseFloat(s.length) || 0,
                    w: parseFloat(s.width) || 0,
                    h: parseFloat(s.height) || 0,
                    vol_wt: parseFloat(s.volumetric_weight) || 0,
                    chg_wt: parseFloat(s.final_chargeable_weight) || 0,
                    eway_no: s.eway_bill_no || s.eway_no || '',
                    eway_date: s.eway_bill_date || s.eway_date || '',
                    rate: s.rate || '',
                    delivery_charges: s.delivery_charges || '',
                    docket_charges: s.docket_charges || '',
                    pickup_charges: s.pickup_charges || '',
                    fuel_surcharge: s.fuel_surcharge || '',
                    fov_charges: s.fov_charges || '',
                    handling_charges: s.handling_charges || '',
                    service_charges: s.service_charges || '',
                    misc_charges: s.misc_charges || '',
                    misc_charges_name: s.misc_charges_name || 'Misc Charges'
                });
            });
            renderGrid();
        }
        

        
        // Global docket generation listeners removed as auto-generation is now handled automatically

        calcTotals();
    });

    function saveAsDraft() {
        $('#booking_status').val('Draft');
        $('#bookingForm').submit();
    }

    function autoFillShipperGlobal() {
        let name = $('#global_shipper').val();
        if(name) {
            const c = _customers.find(x => (x.name || '').trim().toLowerCase() === (name || '').trim().toLowerCase());
            const newBillTo    = c ? (c.bill_to   || '') : '';
            const newConsignee = c ? (c.consignee || '') : '';
            
            $('#global_bill_to').val(newBillTo);
            $('#global_consignee').val(newConsignee);
            if (c && c.payment_type) $('#payment_type').val(c.payment_type);
            
            // CRITICAL: Update entry_customer so new items pick up the correct name
            $('#entry_customer').val(name);
            $('#entry_bill_to').val(newBillTo);
            $('#entry_consignee').val(newConsignee);
            
            // Also update ALL existing items: customer, bill_to, and consignee
            // This ensures the invoice is generated with the NEW customer's details
            if (items.length > 0) {
                items.forEach(s => {
                    s.customer  = name;
                    s.bill_to   = newBillTo;
                    s.consignee = newConsignee;
                });
                renderGrid();
            }
        }
        syncGstOptionsVisibility();
    }

    $(document).on('input change', '#global_bill_to, #global_consignee', function() {
        const billTo = $('#global_bill_to').val() || '';
        const consignee = $('#global_consignee').val() || '';
        if (items.length > 0) {
            items.forEach(s => {
                s.bill_to = billTo;
                s.consignee = consignee;
            });
            renderGrid();
        }
    });
    
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
        const act = parseFloat($('#entry_act_wt').val()) || 0;
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        let vol = 0;
        if(l>0 && w>0 && h>0) {
            vol = (l * w * h) / formula;
            $('#entry_vol_wt').val(vol.toFixed(2));
        } else {
            $('#entry_vol_wt').val('');
        }
        const chg = Math.max(act, vol);
        if (chg > 0) {
            $('#entry_chg_wt').val(chg.toFixed(2));
        } else {
            $('#entry_chg_wt').val('');
        }
    }

    function fetchNextDocket(activeDockets) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: BASE_URL + 'masters/dockets/generate',
                type: 'POST',
                data: { exclude_dockets: activeDockets },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        resolve(response.docket_no);
                    } else {
                        reject(response.message || "Failed to generate docket.");
                    }
                },
                error: function() {
                    reject("Server error during docket generation.");
                }
            });
        });
    }

    function openItemModal(index) {
        $('#entry_edit_index').val(index);
        
        // Always use the currently selected global shipper
        const globalShipper = $('#global_shipper').val() || '';
        const globalBillTo  = $('#global_bill_to').val() || '';
        const globalConsignee = $('#global_consignee').val() || '';
        
        const awbVal = $('input[name="awb_no"]').val() || '';
        
        if (index === -1) {
            // New Item
            $('#itemModalLabel').html('<i class="fas fa-box-open me-2"></i> Add Shipment Item' + (awbVal ? ` - AWB: <b>${awbVal}</b>` : ''));
            
            let prevPartNo = '';
            let prevInvoiceDate = '';
            if (items.length > 0) {
                const prev = items[items.length - 1];
                prevPartNo = prev.part_no || '';
                prevInvoiceDate = prev.invoice_date || '';
            }
            
            // Use global shipper if set, else fall back to last item
            if (globalShipper) {
                $('#entry_customer').val(globalShipper);
                $('#entry_bill_to').val(globalBillTo);
                $('#entry_consignee').val(globalConsignee);
            } else if (items.length > 0) {
                const prev = items[items.length - 1];
                $('#entry_customer').val(prev.customer);
                $('#entry_bill_to').val(prev.bill_to);
                $('#entry_consignee').val(prev.consignee);
            }
            
            // Set input state & fetch docket if needed
            updateDocketInputState();
            
            $('#entry_invoice').val('');
            $('#entry_part_no').val(prevPartNo);
            $('#entry_invoice_date').val(prevInvoiceDate);
            $('#entry_part_qty').val('0');
            
            $('#entry_contents').val('Goods');
            $('#entry_pcs').val('1');
            $('#entry_act_wt').val('');
            $('#entry_l, #entry_w, #entry_h, #entry_vol_wt').val('');
            $('#entry_chg_wt').val('');
            
            // Clear charges
            $('#entry_eway_no, #entry_eway_date, #entry_rate, #entry_delivery, #entry_docket_chg, #entry_pickup, #entry_fuel, #entry_fov, #entry_handling, #entry_service').val('');
            $('#entry_misc').val('');
            $('#entry_misc_name').val('Misc Charges');
            
        } else {
            // Edit Item
            $('#itemModalLabel').html('<i class="fas fa-box-open me-2"></i> Edit Shipment Item' + (awbVal ? ` - AWB: <b>${awbVal}</b>` : ''));
            const item = items[index];
            
            $('#entry_customer').val(item.customer);
            $('#entry_bill_to').val(item.bill_to);
            $('#entry_consignee').val(item.consignee);
            $('#entry_docket').val(item.docket || '').prop('readonly', false).css('color', '');
            
            $('#entry_invoice').val(item.invoice_no);
            $('#entry_part_no').val(item.part_no || '');
            $('#entry_invoice_date').val(item.invoice_date || '');
            $('#entry_part_qty').val(item.part_qty || 0);
            
            $('#entry_contents').val(item.contents || 'Goods');
            $('#entry_pcs').val(item.pcs);
            $('#entry_act_wt').val(item.act_wt);
            $('#entry_l').val(item.l || '');
            $('#entry_w').val(item.w || '');
            $('#entry_h').val(item.h || '');
            
            // Render volumetric weight without overriding existing custom chargeable weight
            const l = parseFloat(item.l) || 0;
            const w = parseFloat(item.w) || 0;
            const h = parseFloat(item.h) || 0;
            const formula = parseFloat($('#volumetric_formula').val()) || 6000;
            if(l>0 && w>0 && h>0) {
                const vol = (l * w * h) / formula;
                $('#entry_vol_wt').val(vol.toFixed(2));
            } else {
                $('#entry_vol_wt').val('');
            }
            $('#entry_chg_wt').val(item.chg_wt ? parseFloat(item.chg_wt).toFixed(2) : '');
            
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
            $('#entry_misc').val(item.misc_charges || '');
            $('#entry_misc_name').val(item.misc_charges_name || 'Misc Charges');
        }
        $('[maxlength]').trigger('sync-counter');
        
        var modal = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('itemModal'));
        modal.show();
    }

    async function saveItemToGrid() {
        const customer = $('#entry_customer').val();
        const contents = $('#entry_contents').val() || 'Goods';
        const act_wt = parseFloat($('#entry_act_wt').val()) || 0;
        
        // BUG FIX #2 & #8: Guard against NEW_ENTRY sentinel and enforce a valid selection
        if (!customer || customer === 'NEW_ENTRY') {
            ERPUtils.showWarning("Missing Data", "Please select a valid Shipper/Customer from the list.");
            return;
        }
        if (act_wt <= 0) {
            ERPUtils.showWarning("Missing Data", "Actual Weight is required.");
            return;
        }
        // BUG FIX #6: Block save if docket is flagged as duplicate
        if ($('#entry_docket').hasClass('is-invalid')) {
            ERPUtils.showWarning("Duplicate Docket", "The docket number entered already exists in another AWB. Please use a unique docket number.");
            return;
        }

        let docket = $('#entry_docket').val() || '';
        const editIndex = parseInt($('#entry_edit_index').val());
        
        // Auto generate if checked and it's a new item
        const isAutoGen = $('#modal_auto_generate_docket').is(':checked');
        if (isAutoGen && editIndex === -1) {
            Swal.fire({
                title: 'Generating Docket...',
                text: 'Please wait while we generate a unique docket number.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                let activeDockets = [];
                items.forEach((item, idx) => {
                    if (idx !== editIndex && item.docket) {
                        activeDockets.push(item.docket.trim());
                    }
                });
                docket = await fetchNextDocket(activeDockets);
                $('#entry_docket').val(docket);
                Swal.close();
            } catch (err) {
                Swal.close();
                ERPUtils.showError("Generation Failed", err);
                return;
            }
        }

        const l = parseFloat($('#entry_l').val()) || 0;
        const w = parseFloat($('#entry_w').val()) || 0;
        const h = parseFloat($('#entry_h').val()) || 0;
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        const vol_wt = (l*w*h)/formula;
        const chg_wt = parseFloat($('#entry_chg_wt').val()) || Math.max(act_wt, vol_wt); 

        const itemObj = {
            id: editIndex >= 0 ? items[editIndex].id : '',
            customer: $('#entry_customer').val(),
            bill_to: $('#entry_bill_to').val(),
            consignee: $('#entry_consignee').val(),
            docket: docket,
            invoice_no: $('#entry_invoice').val(),
            part_no: $('#entry_part_no').val(),
            part_qty: parseInt($('#entry_part_qty').val()) || 0,
            invoice_date: $('#entry_invoice_date').val(),
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
            misc_charges: $('#entry_misc').val(),
            misc_charges_name: $('#entry_misc_name').val() || 'Misc Charges',
        };

        if(editIndex >= 0) {
            items[editIndex] = itemObj;
        } else {
            items.push(itemObj);
        }
        
        window.isDirty = true;
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
        Swal.fire({
            title: 'Are you sure?',
            text: "This will remove the shipment item from the grid.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                items.splice(index, 1);
                window.isDirty = true;
                renderGrid();
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Item has been removed from the grid.',
                    icon: 'success',
                    timer: 1000,
                    showConfirmButton: false
                });
            }
        });
    }

    function renderGrid() {
        $('#hiddenInputsContainer').empty();
        
        let sumPcs = 0, sumAct = 0, sumVol = 0, sumChg = 0;

        // Update DataTable
        manifestDataTable.clear().rows.add(items).draw(false);
        $('#itemCountBadge').text(items.length);

        items.forEach((item, idx) => {
            sumPcs += item.pcs; sumAct += item.act_wt; sumVol += item.vol_wt; sumChg += item.chg_wt;
        });

        $('#items_json').val(JSON.stringify(items));

        // Update Footers
        $('#sumPcs').text(sumPcs);
        $('#sumActWt').text(sumAct.toFixed(2));
        $('#sumVolWt').text(sumVol.toFixed(2));
        $('#sumChgWt').text(sumChg.toFixed(2));
        
        $('#input_total_pieces').val(sumPcs);
        $('#input_total_weight').val(sumChg.toFixed(2));
        $('#salesWeight').val(sumChg.toFixed(2));
        
        // Auto-sync first item details to hidden global fields for GST checks
        if (items.length > 0) {
            $('#global_shipper').val(items[0].customer);
            $('#global_bill_to').val(items[0].bill_to);
            $('#global_consignee').val(items[0].consignee);
        } else {
            $('#global_shipper').val('');
            $('#global_bill_to').val('');
            $('#global_consignee').val('');
        }
        syncGstOptionsVisibility();
        
        calcTotals();
    }

    function recalcAllItems() {
        const formula = parseFloat($('#volumetric_formula').val()) || 6000;
        items.forEach(item => {
            item.vol_wt = (item.l * item.w * item.h) / formula;
            item.chg_wt = Math.max(item.act_wt, item.vol_wt);
        });
        window.isDirty = true;
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
        const gstType = $('#gst_type').val();
        
        let cgstRate = 0;
        let sgstRate = 0;
        let igstRate = 0;

        if (gstType === 'intra') {
            cgstRate = parseFloat($('#cgst_rate').val()) || 0;
            sgstRate = parseFloat($('#sgst_rate').val()) || 0;
            $('#igst_rate').val('0.00');
        } else {
            igstRate = parseFloat($('#igst_rate').val()) || 0;
            $('#cgst_rate').val('0.00');
            $('#sgst_rate').val('0.00');
        }
        
        // Show live total percentage calculations applied to the booking
        const totalGstPercent = cgstRate + sgstRate + igstRate;
        $('#totalGstBadge').text('Total GST: ' + totalGstPercent.toFixed(2) + '%');

        let cgst = isGstApplied ? Math.round(taxable * (cgstRate / 100)) : 0;
        let sgst = isGstApplied ? Math.round(taxable * (sgstRate / 100)) : 0;
        let igst = isGstApplied ? Math.round(taxable * (igstRate / 100)) : 0;
        let netPayable = taxable + cgst + sgst + igst;

        $('#totalTaxableAmount').text('₹' + taxable.toFixed(2));
        $('#netPayableAmount').text('₹' + netPayable.toFixed(2));
    }

    $(document).on('input', '#salesRate, .calc-surcharge, .rate-input', calcTotals);
    $(document).on('change', '#gst_applied', function() {
        const rawName = $('#global_shipper').val();
        const name = (rawName || '').trim().toLowerCase();
        
        if ($(this).is(':checked')) {
            if (!name) {
                $(this).prop('checked', false);
                ERPUtils.showWarning("Can't Apply GST", "Please select a customer first.");
            }
        }
        syncGstOptionsVisibility();
    });

    $(document).on('change', '#entry_customer', function() {
        const name = $(this).val();
        if (name && name !== 'NEW_ENTRY') {
            const c = _customers.find(x => (x.name || '').trim().toLowerCase() === (name || '').trim().toLowerCase());
            if (c) {
                $('#entry_bill_to').val(c.bill_to || '');
                $('#entry_consignee').val(c.consignee || '');
                
                if (items.length === 0) {
                    $('#global_shipper').val(name);
                    $('#global_bill_to').val(c.bill_to || '');
                    $('#global_consignee').val(c.consignee || '');
                    if (c.payment_type) {
                        $('#payment_type').val(c.payment_type);
                    }
                    syncGstOptionsVisibility();
                }
            }
        } else if (!name) {
            $('#entry_bill_to').val('');
            $('#entry_consignee').val('');
            if (items.length === 0) {
                $('#global_shipper').val('');
                $('#global_bill_to').val('');
                $('#global_consignee').val('');
                syncGstOptionsVisibility();
            }
        }
    });

    $(document).on('change', '#gst_type', function() {
        handleGstTypeChange(false);
    });

    function syncGstOptionsVisibility() {
        const rawName = $('#global_shipper').val();
        const name = (rawName || '').trim().toLowerCase();
        let hasGst = false;
        if (name && typeof _customers !== 'undefined' && Array.isArray(_customers)) {
            const c = _customers.find(x => (x.name || '').trim().toLowerCase() === name);
            if (c && c.gst_number && c.gst_number.trim() !== '') {
                hasGst = true;
            }
        }
        
        // Auto-check/uncheck GST Applied checkbox programmatically
        $('#gst_applied').prop('checked', hasGst);
        
        // Show/hide red validation warning text (no GST number in customer master)
        if (name && !hasGst) {
            $('#gst_warning_msg').removeClass('d-none');
        } else {
            $('#gst_warning_msg').addClass('d-none');
        }
        
        // Keep the GST Applied checkbox container hidden
        $('#gst_applied_container').addClass('d-none');
        
        // Show/hide GST config card based on customer GST status
        if (hasGst) {
            $('#gst_config_card_container').removeClass('d-none');
        } else {
            $('#gst_config_card_container').addClass('d-none');
        }
        calcTotals();
    }

    function handleGstTypeChange(isInit) {
        const mode = $('#gst_type').val();
        if (mode === 'intra') {
            $('#cgst_col').removeClass('d-none');
            $('#sgst_col').removeClass('d-none');
            $('#igst_col').addClass('d-none');
            
            if (!isInit) {
                $('#cgst_rate').val('9.00');
                $('#sgst_rate').val('9.00');
                $('#igst_rate').val('0.00');
            }
        } else {
            $('#cgst_col').addClass('d-none');
            $('#sgst_col').addClass('d-none');
            $('#igst_col').removeClass('d-none');
            
            if (!isInit) {
                $('#cgst_rate').val('0.00');
                $('#sgst_rate').val('0.00');
                $('#igst_rate').val('18.00');
            }
        }
        calcTotals();
    }

    let signaturePad;
    document.addEventListener("DOMContentLoaded", function() {
        var canvas = document.getElementById('signatureCanvas');
        if (canvas && typeof SignaturePad !== 'undefined') {
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)', // Solid white prevents TCPDF alpha channel errors
                penColor: 'rgb(0, 0, 0)'
            });

            function resizeCanvas() {
                // Guard: if canvas is inside a hidden tab, offsetWidth is 0 - skip until visible
                if (canvas.offsetWidth === 0) return;
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                // Save existing signature data before resize
                var existingData = signaturePad.toData();
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
                // Restore signature data after resize (preserves drawing on window resize)
                if (existingData && existingData.length > 0) {
                    signaturePad.fromData(existingData);
                }
            }

            window.addEventListener("resize", resizeCanvas);
            // DO NOT call resizeCanvas() here - canvas is inside a hidden tab (offsetWidth = 0)

            // FIX: Resize canvas when Tab 3 (Financials) becomes visible
            var tab3Btn = document.getElementById('tab3-tab');
            if (tab3Btn) {
                tab3Btn.addEventListener('shown.bs.tab', function() {
                    // Small timeout ensures Bootstrap has fully shown the tab pane
                    setTimeout(resizeCanvas, 50);
                });
            }

            document.getElementById('clearCanvas').addEventListener('click', function () {
                signaturePad.clear();
                document.getElementById('signatureBase64').value = "";
            });
        }
        
        // Initialize GST mode dropdown state based on loaded values
        const initialIgst = parseFloat($('#igst_rate').val()) || 0;
        if (initialIgst > 0) {
            $('#gst_type').val('inter');
        } else {
            $('#gst_type').val('intra');
        }
        handleGstTypeChange(true);
        syncGstOptionsVisibility();
        
        // Initial GST calculations update
        calcTotals();
    });

    $('#bookingForm').on('submit', function() {
        if(items.length === 0) {
            ERPUtils.showWarning("Missing Data", "Please add at least one shipment item.");
            return false;
        }
        
        // Save drawn signature base64 if present
        if (signaturePad && !signaturePad.isEmpty()) {
            document.getElementById('signatureBase64').value = signaturePad.toDataURL('image/jpeg');
        }
        
        // Re-render to ensure hidden inputs are fresh/current
        renderGrid();
        
        window.isDirty = false; // allow navigation without prompt
        $('#mainSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    });

    // --- QUICK INLINE MASTER CREATION ---
    
    // Set up global CSRF handler for AJAX calls
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // BUG FIX #3: Restrict NEW_ENTRY handler to only known master dropdowns (prevents nested modal chaos)
    const MASTER_QUICK_DROPDOWNS = ['origin','destination','mode_transport','payment_type','material_type','material_category','global_shipper','entry_customer','transporter_name','driver_name','airlines_select'];
    $(document).on('change', 'select', function() {
        if ($(this).val() === 'NEW_ENTRY' && MASTER_QUICK_DROPDOWNS.includes($(this).attr('id'))) {
            const dropdownId = $(this).attr('id');
            openQuickMasterModal(dropdownId, $(this));
        }
    });

    function openQuickMasterModal(dropdownId, $selectEl) {
        let title = "Add New Entry";
        let fieldsHtml = "";
        let submitUrl = "";
        
        // Store reference to the dropdown that triggered the modal
        $('#quickMasterModal').data('target-select-id', dropdownId);
        
        if (dropdownId === 'origin' || dropdownId === 'destination') {
            const type = dropdownId;
            title = `Add New ${type === 'origin' ? 'Origin' : 'Destination'}`;
            submitUrl = BASE_URL + 'masters/lookups/' + type + '/create';
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">City <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">State <span class="text-danger">*</span></label>
                        <input type="text" name="state" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Pincode</label>
                        <input type="text" name="pincode" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">District</label>
                        <input type="text" name="district" class="form-control form-control-sm shadow-none">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        } else if (dropdownId === 'mode_transport' || dropdownId === 'payment_type' || dropdownId === 'material_type' || dropdownId === 'material_category') {
            let type = dropdownId;
            if (type === 'mode_transport') type = 'mode';
            let displayType = type.replace('_', ' ');
            displayType = displayType.charAt(0).toUpperCase() + displayType.slice(1);
            title = `Add New ${displayType}`;
            submitUrl = BASE_URL + 'masters/lookups/' + type + '/create';
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Value <span class="text-danger">*</span></label>
                        <input type="text" name="value" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        } else if (dropdownId === 'global_shipper' || dropdownId === 'entry_customer') {
            title = "Add New Customer / Shipper";
            submitUrl = BASE_URL + 'masters/customers/create';
            
            let paymentOptions = '<option value="">--Select--</option>';
            <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                paymentOptions += `<option value="<?= esc($l['value']) ?>"><?= esc($l['value']) ?></option>`;
            <?php endforeach; ?>
            
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Address</label>
                        <textarea name="address" class="form-control form-control-sm shadow-none" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">GST Number</label>
                        <input type="text" name="gst_number" class="form-control form-control-sm shadow-none text-uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">GST State</label>
                        <input type="text" name="gst_state" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Payment Type</label>
                        <select name="payment_type" class="form-select form-select-sm shadow-none">
                            ${paymentOptions}
                        </select>
                    </div>
                    <div class="col-md-12 border-top pt-2 mt-3">
                        <h6 class="fs-8 fw-bold text-muted mb-2">Default Invoice Addresses</h6>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Default Bill To</label>
                        <textarea name="bill_to" class="form-control form-control-sm shadow-none" rows="2" maxlength="250"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Default Consignee</label>
                        <textarea name="consignee" class="form-control form-control-sm shadow-none" rows="2" maxlength="250"></textarea>
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        } else if (dropdownId === 'transporter_name') {
            title = "Add New Transporter";
            submitUrl = BASE_URL + 'masters/transporters/create';
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Transporter Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control form-control-sm shadow-none">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        } else if (dropdownId === 'driver_name') {
            title = "Add New Driver";
            submitUrl = BASE_URL + 'masters/drivers/create';
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Driver Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Vehicle No</label>
                        <input type="text" name="vehicle_no" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">License No</label>
                        <input type="text" name="license_no" class="form-control form-control-sm shadow-none">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        } else if (dropdownId === 'airlines_select') {
            title = "Add New Airline";
            submitUrl = BASE_URL + 'masters/airlines/create';
            fieldsHtml = `
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Airline Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Carrier Code</label>
                        <input type="text" name="code" class="form-control form-control-sm shadow-none">
                    </div>
                    <input type="hidden" name="is_active" value="1">
                </div>
            `;
        }

        $('#quickMasterModalLabel').html('<i class="fas fa-plus-circle me-2"></i> ' + title);
        $('#quickMasterFields').html(fieldsHtml);
        $('#quickMasterModal').data('submit-url', submitUrl);
        
        // Show Modal
        const modal = new bootstrap.Modal(document.getElementById('quickMasterModal'));
        modal.show();

        // When modal is dismissed, if we didn't save, reset target select to empty/default
        // BUG FIX #7: Use data attribute (not closure) so dismiss always resets the correct select
        $('#quickMasterModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
            const targetId = $(this).data('target-select-id');
            const $target = $('#' + targetId);
            if ($target.val() === 'NEW_ENTRY') {
                $target.val('');
            }
        });
    }

    $('#btnSaveQuickMaster').on('click', function() {
        const $form = $('#quickMasterForm');
        
        // Check form validity
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        const submitUrl = $('#quickMasterModal').data('submit-url');
        const targetSelectId = $('#quickMasterModal').data('target-select-id');
        const formData = $form.serialize();
        
        $('#btnSaveQuickMaster').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');
        
        $.ajax({
            url: submitUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                $('#btnSaveQuickMaster').prop('disabled', false).html('<i class="fas fa-check me-2"></i> Save Entry');
                
                // BUG FIX #4: Refresh CSRF token from response to prevent 403 on second save
                if (res.csrf_hash) {
                    $('meta[name="csrf-token"]').attr('content', res.csrf_hash);
                    $('input[name="csrf_token_name"]').val(res.csrf_hash);
                }
                
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Saved!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    let newId = res.id;
                    let newValue = res.value || res.name;
                    
                    if (targetSelectId === 'global_shipper' || targetSelectId === 'entry_customer') {
                        _customers.push({
                            id: newId,
                            name: res.name,
                            code: res.code,
                            bill_to: res.bill_to,
                            consignee: res.consignee,
                            payment_type: res.payment_type,
                            gst_number: res.gst_number,
                            gst_state: res.gst_state
                        });
                        
                        const $globalShipper = $('#global_shipper');
                        const $entryCustomer = $('#entry_customer');
                        
                        const newOptionGlobal = `<option value="${res.name}">${res.name} (${res.code || 'N/A'})</option>`;
                        const newOptionEntry = `<option value="${res.name}">${res.name} (${res.code || 'N/A'})</option>`;
                        
                        $globalShipper.append(newOptionGlobal);
                        $entryCustomer.append(newOptionEntry);
                        
                        if (targetSelectId === 'global_shipper') {
                            $globalShipper.val(res.name).trigger('change');
                            setTimeout(() => $('#global_bill_to').focus(), 100);
                        } else {
                            $entryCustomer.val(res.name).trigger('change');
                            setTimeout(() => $('#entry_bill_to').focus(), 100);
                        }
                    } else {
                        const $select = $('#' + targetSelectId);
                        let newOption = "";
                        
                        if (targetSelectId === 'transporter_name') {
                            newOption = `<option value="${res.name}" data-mobile="${res.mobile || ''}">${res.name}</option>`;
                            $select.append(newOption);
                            $select.val(res.name).trigger('change');
                            setTimeout(() => $('#transporter_mobile').focus(), 100);
                            
                        } else if (targetSelectId === 'driver_name') {
                            newOption = `<option value="${res.name}" data-mobile="${res.mobile || ''}" data-vehicle="${res.vehicle_no || ''}">${res.name}</option>`;
                            $select.append(newOption);
                            $select.val(res.name).trigger('change');
                            setTimeout(() => $('#driver_mobile').focus(), 100);
                            
                        } else if (targetSelectId === 'airlines_select') {
                            newOption = `<option value="${res.name}" data-code="${res.code || ''}">${res.name}</option>`;
                            $select.append(newOption);
                            $select.val(res.name).trigger('change');
                            setTimeout(() => $('input[name="flight_number"]').focus(), 100);
                            
                        } else {
                            newOption = `<option value="${res.value}">${res.value}</option>`;
                            $select.append(newOption);
                            $select.val(res.value).trigger('change');
                            
                            if (targetSelectId === 'origin') {
                                setTimeout(() => $('#destination').focus(), 100);
                            } else if (targetSelectId === 'destination') {
                                setTimeout(() => $('#mode_transport').focus(), 100);
                            } else if (targetSelectId === 'mode_transport') {
                                setTimeout(() => $('#payment_type').focus(), 100);
                            } else if (targetSelectId === 'payment_type') {
                                setTimeout(() => $('#material_type').focus(), 100);
                            } else if (targetSelectId === 'material_type') {
                                setTimeout(() => $('#material_category').focus(), 100);
                            } else if (targetSelectId === 'material_category') {
                                setTimeout(() => $('#gst_applied').focus(), 100);
                            }
                        }
                    }
                    
                    bootstrap.Modal.getInstance(document.getElementById('quickMasterModal')).hide();
                } else {
                    ERPUtils.showError("Save Failed", res.message || "An error occurred.");
                }
            },
            error: function(xhr, status, error) {
                $('#btnSaveQuickMaster').prop('disabled', false).html('<i class="fas fa-check me-2"></i> Save Entry');
                console.error("AJAX Master Save error detail:", {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                ERPUtils.showError("Network Error", "Failed to communicate with server. Technical logs have been printed to console.");
            }
        });
    });

    // --- REAL-TIME DUPLICATE DOCKET CHECK ---
    $('#entry_docket').on('change', function() {
        const docketNo = $(this).val().trim();
        const editIndex = $('#entry_edit_index').val();
        const bookingId = <?= isset($bookingId) ? $bookingId : 0 ?>;
        
        if (docketNo) {
            // Check if it is duplicate within the local items grid
            let localDup = false;
            items.forEach((item, idx) => {
                if (idx !== parseInt(editIndex) && item.docket && item.docket.trim().toLowerCase() === docketNo.toLowerCase()) {
                    localDup = true;
                }
            });
            
            if (localDup) {
                return;
            }
            
            // Check against database for other bookings
            $.ajax({
                url: BASE_URL + 'masters/dockets/check-unique',
                type: 'POST',
                data: { docket_no: docketNo, booking_id: bookingId },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success' && !res.unique) {
                        Swal.fire({
                            title: 'Duplicate Docket Warning',
                            html: res.message,
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        $('#entry_docket').addClass('is-invalid');
                    } else {
                        $('#entry_docket').removeClass('is-invalid');
                    }
                }
            });
        }
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