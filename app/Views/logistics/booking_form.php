<?= $this->extend('layout') ?>
<?php 
$permissions = session()->get('permissions') ?? [];
if (!$permissions['can_create'] && !isset($isEdit)) {
    echo '<div class="alert alert-danger text-center"><h3>Access Denied</h3><p>Create permission required!</p><a href="' . base_url('logistics') . '" class="btn btn-primary">Go to Dashboard</a></div>';
    return;
}
?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <?php if (isset($isEdit) && $isEdit): ?>
        <div class="alert alert-warning">
            <h5>Edit Mode - AWB: <?= esc($booking['awb_no']) ?></h5>
            <small>All changes will be saved to existing booking #<?= $bookingId ?></small>
        </div>
    <?= form_open('logistics/update/' . $bookingId, ['id' => 'bookingForm']) ?>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>New AWB Booking (Two-Tab System)</h2>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
        <?= form_open_multipart('logistics/store', ['id' => 'bookingForm']) ?>
    <?php endif; ?>

    <!-- COMPANY DROPDOWN -->
    <!--  AUTO COMPANY (NO DROPDOWN) -->
    <div class="row mt-3 mb-4 p-3 border rounded bg-light">
        <div class="col-md-12">
            <div class="d-flex align-items-center">
                <div>
                    <label class="form-label fw-bold fs-5 mb-1 d-block">Selected Company</label>
                    <h5 class="mb-0 text-success"><?= esc($selected_company_name ?? 'No Company') ?></h5>
                    <small class="text-muted">ID: <?= esc($selected_company_id ?? 'N/A') ?></small>
                    <?php if (isset($isEdit) && $isEdit): ?>
                        <small class="badge bg-info ms-2">
                            Original: <?= esc($booking['company_name'] ?? 'N/A') ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="company_id" value="<?= esc($selected_company_id ?? '') ?>" required>
        </div>
    </div>

    <!-- TWO-TAB SYSTEM -->
    <ul class="nav nav-tabs" id="bookingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">
                Tab 1: AWB & Shipment (<?= isset($shipments) ? count($shipments) : 0 ?> items)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" disabled>
                Tab 2: Sales Charges
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="bookingTabsContent">
        <!-- TAB 1: AWB & SHIPMENT DETAILS -->
        <div class="tab-pane fade show active" id="tab1" role="tabpanel">
            <!-- A. BASIC BOOKING DETAILS -->
            <div class="row mb-4 p-3 border rounded bg-light">
                <h5>A. Basic Booking Details</h5>
                <div class="col-md-3">
                    <label class="form-label fw-bold">AWB No. <span class="text-danger">*</span></label>
                    <input type="text" name="awb_no" class="form-control" 
                    value="<?= isset($booking['awb_no']) ? esc($booking['awb_no']) : '' ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Booking Date/Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="booking_date" class="form-control" 
                    value="<?= isset($booking['booking_date']) ? date('Y-m-d\TH:i', strtotime($booking['booking_date'])) : '' ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Origin <span class="text-danger">*</span></label>
                    <input type="text" name="origin" list="originList" class="form-control" 
                    value="<?= $booking['origin'] ?? '' ?>" required>
                    <datalist id="originList">
                        <?php foreach($lookups['origin'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Destination <span class="text-danger">*</span></label>
                    <input type="text" name="destination" list="destList" class="form-control" 
                    value="<?= $booking['destination'] ?? '' ?>" required>
                    <datalist id="destList">
                        <?php foreach($lookups['destination'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label fw-bold">Mode</label>
                    <select name="mode_transport" class="form-select">
                        <option value="">--Select Mode--</option>
                        <?php foreach($lookups['mode'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['mode_transport'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Material Type</label>
                    <select name="material_type" class="form-select">
                        <option value="">--Select Type--</option>
                        <?php foreach($lookups['material_type'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['material_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Material Category</label>
                    <select name="material_category" class="form-select">
                        <option value="">--Select Category--</option>
                        <?php foreach($lookups['material_category'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['material_category'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Material Details</label>
                    <textarea name="material_details" class="form-control" rows="1"><?= $booking['material_details'] ?? '' ?></textarea>
                </div>
                <!-- <div class="col-md-3 mt-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Draft" <?//= ($booking['status'] ?? 'Draft') == 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="Booked" <?//= ($booking['status'] ?? '') == 'Booked' ? 'selected' : '' ?>>Booked</option>
                        <option value="In-Transit" <?//= ($booking['status'] ?? '') == 'In-Transit' ? 'selected' : '' ?>>In-Transit</option>
                        <option value="Delivered" <?//= ($booking['status'] ?? '') == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    </select>
                </div> -->
                <div class="col-md-3 mt-2">
                    <label class="form-label">Payment Type</label>
                    <select name="payment_type" id="payment_type" class="form-select">
                        <option value="">--Select Payment Type--</option>
                        <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                            <option value="<?= esc($l['value']) ?>" <?= ($booking['payment_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" id="narration" class="form-control" value="<?= esc($booking['narration'] ?? '') ?>">
                </div>
                <div class="col-md-3 mt-2 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="gst_applied" id="gst_applied" value="1" <?= (!isset($booking['id']) || !empty($booking['gst_applied'])) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="gst_applied">
                            GST Applied
                        </label>
                    </div>
                </div>
                <div class="col-md-3 mt-2">
                    <label class="form-label fw-bold">Volumetric Formula</label>
                    <input type="number" name="volumetric_formula" id="volumetric_formula" class="form-control" value="<?= esc($booking['volumetric_formula'] ?? 6000) ?>" onchange="recalcAllItems()">
                </div>
            </div>

            <!-- B. TRANSPORT DETAILS -->
            <div class="row mb-4 p-3 border rounded bg-light">
                <h5>B. Transport & Driver Details</h5>
                <div class="col-md-3">
                    <label>Transporter Name</label>
                    <input type="text" name="transporter_name" id="transporter_name" list="transporterList" class="form-control" value="<?= $booking['transporter_name'] ?? '' ?>" onchange="autoFillTransporter()">
                    <datalist id="transporterList">
                        <?php foreach($transporters ?? [] as $t): ?>
                            <option value="<?= esc($t['name']) ?>" data-mobile="<?= esc($t['mobile']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label>Transporter Mobile</label>
                    <input type="text" name="transporter_mobile" id="transporter_mobile" class="form-control" value="<?= $booking['transporter_mobile'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label>Driver Name</label>
                    <input type="text" name="driver_name" id="driver_name" list="driverList" class="form-control" value="<?= $booking['driver_name'] ?? '' ?>" onchange="autoFillDriver()">
                    <datalist id="driverList">
                        <?php foreach($drivers ?? [] as $d): ?>
                            <option value="<?= esc($d['name']) ?>" data-mobile="<?= esc($d['mobile']) ?>" data-vehicle="<?= esc($d['vehicle_no']) ?>" data-license="<?= esc($d['license_no']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label>Driver Mobile</label>
                    <input type="text" name="driver_mobile" id="driver_mobile" class="form-control" value="<?= $booking['driver_mobile'] ?? '' ?>">
                </div>
                <div class="col-md-3 mt-3">
                    <label>Vehicle No.</label>
                    <input type="text" name="vehicle_no" id="vehicle_no" class="form-control" value="<?= $booking['vehicle_no'] ?? '' ?>">
                </div>
                <div class="col-md-3 mt-3">
                    <label>Driver License</label>
                    <input type="text" name="driver_license_no" id="driver_license_no" class="form-control" value="<?= $booking['driver_license_no'] ?? '' ?>">
                </div>
                <div class="col-md-3 mt-3">
                    <label>Total Pieces</label>
                    <input type="number" name="total_pieces" id="total_pieces" class="form-control" value="<?= $booking['total_pieces'] ?? '' ?>" readonly>
                </div>
                <div class="col-md-3 mt-3">
                    <label>Total Weight</label>
                    <input type="number" step="0.01" name="total_weight" id="total_weight" class="form-control" value="<?= $booking['total_weight'] ?? '' ?>" readonly>
                </div>
            </div>

            <!-- C. SHIPMENT ITEMS (ALL FIELDS RESTORED) -->
            <div class="mb-4 p-3 border rounded bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>C. Shipment Items (Add Multiple)</h5>
                    <button type="button" class="btn btn-outline-primary" onclick="addShipmentItem()">
                        Add Item
                    </button>
                </div>
                <div id="shipmentItems">
                    <!-- DYNAMIC ITEMS -->
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-success btn-lg" onclick="nextTab()">
                        Next: Sales Charges </button>
                </div>
            </div>
        </div>

        <!-- TAB 2: SALES CHARGES (UNCHANGED - ALL FIELDS) -->
        <div class="tab-pane fade" id="tab2" role="tabpanel">
            <h5 class="mb-4">Sales Charges</h5>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Flight Number</label>
                    <input type="text" name="flight_number" class="form-control" value="<?= $booking['flight_number'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label>Airlines</label>
                    <input type="text" name="airlines" class="form-control" value="<?= $booking['airlines'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label>Rate (₹/KG)</label>
                    <input type="number" step="0.01" id="salesRate" name="rate" class="form-control" value="<?= $sales['rate'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label>Total Weight (KG)</label>
                    <input type="number" step="0.01" name="weight" class="form-control" value="<?= $sales['weight'] ?? '' ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2"><label>DDC</label><input type="number" step="0.01" name="ddc" class="form-control calc-total" value="<?= $sales['ddc'] ?? '' ?>"></div>
                <div class="col-md-2"><label>SSC</label><input type="number" step="0.01" name="ssc" class="form-control calc-total" value="<?= $sales['ssc'] ?? '' ?>"></div>
                <div class="col-md-2"><label>BTC</label><input type="number" step="0.01" name="btc" class="form-control calc-total" value="<?= $sales['btc'] ?? '' ?>"></div>
                <div class="col-md-2"><label>FLC</label><input type="number" step="0.01" name="flc" class="form-control calc-total" value="<?= $sales['flc'] ?? '' ?>"></div>
                <div class="col-md-2"><label>DOC</label><input type="number" step="0.01" name="doc" class="form-control calc-total" value="<?= $sales['doc'] ?? '' ?>"></div>
                <div class="col-md-2"><label>TCP</label><input type="number" step="0.01" name="tcp" class="form-control calc-total" value="<?= $sales['tcp'] ?? '' ?>"></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2"><label>Inbound TSP</label><input type="number" step="0.01" name="inbound_tsp" class="form-control calc-total" value="<?= $sales['inbound_tsp'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Outbound TSP</label><input type="number" step="0.01" name="outbound_tsp" class="form-control calc-total" value="<?= $sales['outbound_tsp'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Utility</label><input type="number" step="0.01" name="utility_charges" class="form-control calc-total" value="<?= $sales['utility_charges'] ?? '' ?>"></div>
                <div class="col-md-2"><label>X-Ray</label><input type="number" step="0.01" name="xray_charges" class="form-control calc-total" value="<?= $sales['xray_charges'] ?? '' ?>"></div>
                <div class="col-md-2"><label>ADO</label><input type="number" step="0.01" name="ado" class="form-control calc-total" value="<?= $sales['ado'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Misc</label><input type="number" step="0.01" name="misc_charges" class="form-control calc-total" value="<?= $sales['misc_charges'] ?? '' ?>"></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-2"><label>AWB Fees Agent</label><input type="number" step="0.01" name="awb_fees_agent" class="form-control calc-total" value="<?= $sales['awb_fees_agent'] ?? '' ?>"></div>
                <div class="col-md-2"><label>AWB Fees Carrier</label><input type="number" step="0.01" name="awb_fees_carrier" class="form-control calc-total" value="<?= $sales['awb_fees_carrier'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Admin Charges</label><input type="number" step="0.01" name="admin_charges" class="form-control calc-total" value="<?= $sales['admin_charges'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Delivery Order Charges</label><input type="number" step="0.01" name="delivery_order_charges" class="form-control calc-total" value="<?= $sales['delivery_order_charges'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Inbound Handling</label><input type="number" step="0.01" name="inbound_handling" class="form-control calc-total" value="<?= $sales['inbound_handling'] ?? '' ?>"></div>
                <div class="col-md-2"><label>Inbound Storage</label><input type="number" step="0.01" name="inbound_storage" class="form-control calc-total" value="<?= $sales['inbound_storage'] ?? '' ?>"></div>
            </div>

            <div class="row mb-4">
                <div class="col-md-2"><label>Outbound Storage</label><input type="number" step="0.01" name="outbound_storage" class="form-control calc-total" value="<?= $sales['outbound_storage'] ?? '' ?>"></div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 offset-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5>Total Taxable Amount: <span id="totalTaxableAmount" class="text-primary">₹0.00</span></h5>
                            <h6 class="text-muted">Net Payable (Incl. GST): <span id="netPayableAmount" class="text-success fw-bold">₹0.00</span></h6>
                            <button type="submit" id="mainSubmitBtn" class="btn btn-success btn-lg w-100 mt-3">
                                <?php if (isset($isEdit) && $isEdit): ?>
                                    Update Booking
                                <?php else: ?>
                                    Save Complete Booking
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= form_close() ?>

    <!-- Master Datalists -->
    <datalist id="customerList">
        <?php foreach($customers ?? [] as $c): ?>
            <option value="<?= esc($c['name']) ?>">
        <?php endforeach; ?>
    </datalist>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let itemCounter = 0;
    let shipmentsData = <?= json_encode($shipments ?? []) ?>;
    let isEditMode = <?= isset($isEdit) && $isEdit ? 'true' : 'false' ?>;

    // Grid Memory & Master Data
    let _lastRow = { shipper: '', bill_to: '', consignee: '', docket_no: '' };
    const _customers = <?= json_encode($customers ?? []) ?>;
    const _companyGst = {
        cgst: <?= $company['cgst_rate'] ?? 9 ?>,
        sgst: <?= $company['sgst_rate'] ?? 9 ?>,
        igst: <?= $company['igst_rate'] ?? 0 ?>
    };

    //  FIXED COMPLETE INITIALIZATION
    $(document).ready(function() {
        if (isEditMode && shipmentsData.length > 0) {
        // EDIT MODE: Load existing data FIRST
            shipmentsData.forEach(function(item, index) {
            addShipmentItem(); // Add row for each existing item
            setTimeout(function() {
                loadShipmentData(item, index);
            }, index * 50);
        });
        } else {
        // NEW MODE: Add 1 empty row
            addShipmentItem();
        }

        syncSalesRateFromFirstItem();
        // Trigger calculations
        $('.calc-weight, .calc-dim').trigger('input');
        $('[name="rate"], [name="weight"], [name="ddc"]').trigger('input');
    });

    function loadShipmentData(item, index) {
        const row = $('.shipment-item').eq(index);

        // ALL FIELDS LOADED 
        row.find('[name*="[id]"]').val(item.id || '');
        row.find('[name*="customer_name"]').val(item.customer_name || '');
        row.find('[name*="bill_to"]').val(item.bill_to || '');
        row.find('[name*="consignee"]').val(item.consignee || '');
        row.find('[name*="docket_no"]').val(item.docket_no || '');
        row.find('[name*="part_no"]').val(item.part_no || '');
        row.find('[name*="invoice_no"]').val(item.invoice_no || '');
        row.find('[name*="invoice_date"]').val(item.invoice_date || '');
        row.find('[name*="actual_weight"]').val(item.actual_weight || 0);
        row.find('[name*="length"]').val(item.length || 0);
        row.find('[name*="width"]').val(item.width || 0);
        row.find('[name*="height"]').val(item.height || 0);
        row.find('[name*="volumetric_weight"]').val(item.volumetric_weight || 0);
        // Populate calculated chargeable weight if available
        row.find('[name*="calculated_chargeable_weight"]').val(item.calculated_chargeable_weight || 0);
        row.find('[name*="chargeable_weight"]').val(item.final_chargeable_weight || 0);
        row.find('[name*="pieces"]').val(item.pieces || 1);
        row.find('[name*="eway_bill_no"]').val(item.eway_bill_no || '');
        row.find('[name*="eway_bill_date"]').val(item.eway_bill_date || '');
        row.find('[name*="rate"]').val(item.rate || 0);

        // ALL CHARGES 
        row.find('[name*="delivery_charges"]').val(item.delivery_charges || 0);
        row.find('[name*="docket_charges"]').val(item.docket_charges || 0);
        row.find('[name*="pickup_charges"]').val(item.pickup_charges || 0);
        row.find('[name*="fuel_surcharge"]').val(item.fuel_surcharge || 0);
        row.find('[name*="fov_charges"]').val(item.fov_charges || 0);
        row.find('[name*="handling_charges"]').val(item.handling_charges || 0);
        row.find('[name*="service_charges"]').val(item.service_charges || 0);
    }

    // PERFECT SHIPMENT TEMPLATE WITH ACCESSIBILITY
    function addShipmentItem() {
        itemCounter++;
        const template = `
    <div class="shipment-item row border p-3 mb-3 rounded bg-light position-relative" data-item="${itemCounter}">
        <input type="hidden" name="items[${itemCounter}][id]" value="">
        <!-- 1-3. PARTIES (REQUIRED) -->
        <div class="col-md-4">
            <label class="fw-bold">Customer (Shipper) <span class="text-danger">*</span></label>
            <input type="text" name="items[${itemCounter}][customer_name]" id="shipper_${itemCounter}" list="customerList" class="form-control" aria-label="Customer Name for Item ${itemCounter}" onchange="autoFillShipper(${itemCounter})" required>
        </div>
        <div class="col-md-4">
            <label class="fw-bold">Bill To <span class="text-danger">*</span></label>
            <textarea name="items[${itemCounter}][bill_to]" id="bill_to_${itemCounter}" class="form-control" rows="2" aria-label="Bill To for Item ${itemCounter}" required></textarea>
        </div>
        <div class="col-md-4">
            <label class="fw-bold">Consignee <span class="text-danger">*</span></label>
            <textarea name="items[${itemCounter}][consignee]" id="consignee_${itemCounter}" class="form-control" rows="2" aria-label="Consignee for Item ${itemCounter}" required></textarea>
        </div>

        <!-- 4-7. DOC NUMBERS -->
        <div class="col-md-3 mt-3">
            <label>Docket No.</label>
            <input type="text" name="items[${itemCounter}][docket_no]" class="form-control" aria-label="Docket Number for Item ${itemCounter}">
        </div>
        <div class="col-md-3 mt-3">
            <label>Part No.</label>
            <input type="text" name="items[${itemCounter}][part_no]" class="form-control" aria-label="Part Number for Item ${itemCounter}">
        </div>
        <div class="col-md-3 mt-3">
            <label>Invoice No.</label>
            <input type="text" name="items[${itemCounter}][invoice_no]" class="form-control" aria-label="Invoice Number for Item ${itemCounter}">
        </div>
        <div class="col-md-3 mt-3">
            <label>Inv. Date</label>
            <input type="date" name="items[${itemCounter}][invoice_date]" class="form-control" aria-label="Invoice Date for Item ${itemCounter}">
        </div>

        <!-- 8-13. WEIGHT & DIMENSIONS -->
        <div class="col-md-3 mt-3">
            <label><strong>Actual Wt (KG) <span class="text-danger">*</span></strong></label>
            <input type="number" step="0.01" name="items[${itemCounter}][actual_weight]" class="form-control calc-weight" aria-label="Actual Weight for Item ${itemCounter}" required>
        </div>
        <div class="col-md-2 mt-3">
            <label>L (CM)</label>
            <input type="number" name="items[${itemCounter}][length]" class="form-control calc-dim" aria-label="Length for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>W (CM)</label>
            <input type="number" name="items[${itemCounter}][width]" class="form-control calc-dim" aria-label="Width for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>H (CM)</label>
            <input type="number" name="items[${itemCounter}][height]" class="form-control calc-dim" aria-label="Height for Item ${itemCounter}">
        </div>
        <div class="col-md-3 mt-3">
            <label><strong>Vol. Wt <small>(Auto)</small></strong></label>
            <input type="number" step="0.01" name="items[${itemCounter}][volumetric_weight]" class="form-control bg-light" aria-label="Volumetric Weight for Item ${itemCounter}" readonly>
        </div>
        <div class="col-md-3 mt-3">
            <label><strong>Chg. Wt <small>(Min 45kg)</small></strong></label>
            <input type="hidden" name="items[${itemCounter}][calculated_chargeable_weight]">
            <input type="number" step="0.01" name="items[${itemCounter}][chargeable_weight]" class="form-control bg-success text-white fw-bold" aria-label="Chargeable Weight for Item ${itemCounter}">
        </div>

        <!-- 14. PIECES -->
        <div class="col-md-2 mt-3">
            <label>Pieces</label>
            <input type="number" name="items[${itemCounter}][pieces]" class="form-control" value="1" min="1" aria-label="Number of Pieces for Item ${itemCounter}">
        </div>

        <!-- 15-16. E-WAY BILL -->
        <div class="col-md-2 mt-3">
            <label>E-Way No.</label>
            <input type="text" name="items[${itemCounter}][eway_bill_no]" class="form-control" aria-label="E-Way Bill Number for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>E-Way Date</label>
            <input type="date" name="items[${itemCounter}][eway_bill_date]" class="form-control" aria-label="E-Way Bill Date for Item ${itemCounter}">
        </div>

        <!-- 17. RATE -->
        <div class="col-md-3 mt-3">
            <label>Rate (₹/KG)</label>
            <input type="number" step="0.01" name="items[${itemCounter}][rate]" class="form-control" aria-label="Rate for Item ${itemCounter}">
        </div>

        <!-- 18-23. ALL CHARGES -->
        <div class="col-md-2 mt-3">
            <label>Delivery Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][delivery_charges]" class="form-control" aria-label="Delivery Charges for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>Docket Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][docket_charges]" class="form-control" aria-label="Docket Charges for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>Pickup Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][pickup_charges]" class="form-control" aria-label="Pickup Charges for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>Fuel Surcharge</label>
            <input type="number" step="0.01" name="items[${itemCounter}][fuel_surcharge]" class="form-control" aria-label="Fuel Surcharge for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>FOV Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][fov_charges]" class="form-control" aria-label="FOV Charges for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>Handling Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][handling_charges]" class="form-control" aria-label="Handling Charges for Item ${itemCounter}">
        </div>
        <div class="col-md-2 mt-3">
            <label>Service Charges</label>
            <input type="number" step="0.01" name="items[${itemCounter}][service_charges]" class="form-control" aria-label="Service Charges for Item ${itemCounter}">
        </div>

        <!-- Remove Button -->
        <div class="col-12">
            <button type="button" class="btn btn-sm btn-danger position-absolute" 
                    style="top:10px; right:10px;" onclick="removeItem(${itemCounter})" aria-label="Remove Item ${itemCounter}">
                Remove
            </button>
        </div>

        </div>`;

        $('#shipmentItems').append(template);

        // Apply grid memory for new rows
        if (_lastRow.shipper) {
            $(`#shipper_${itemCounter}`).val(_lastRow.shipper);
            $(`#bill_to_${itemCounter}`).val(_lastRow.bill_to);
            $(`#consignee_${itemCounter}`).val(_lastRow.consignee);
            $(`[name="items[${itemCounter}][docket_no]"]`).val(_lastRow.docket_no);
        }
    }

    function removeItem(itemId) {
        $(`.shipment-item[data-item="${itemId}"]`).remove();
        recalcAllItems();
    }

    // Capture changes to update grid memory
    $(document).on('change', '[name*="[customer_name]"], [name*="[bill_to]"], [name*="[consignee]"], [name*="[docket_no]"]', function() {
        const row = $(this).closest('.shipment-item');
        _lastRow.shipper = row.find('[name*="[customer_name]"]').val();
        _lastRow.bill_to = row.find('[name*="[bill_to]"]').val();
        _lastRow.consignee = row.find('[name*="[consignee]"]').val();
        _lastRow.docket_no = row.find('[name*="[docket_no]"]').val();
    });

    // Auto-fill Functions for Masters
    function autoFillShipper(id) {
        const name = $(`#shipper_${id}`).val();
        const c = _customers.find(x => x.name === name);
        if (c) {
            if (c.bill_to) $(`#bill_to_${id}`).val(c.bill_to);
            if (c.consignee) $(`#consignee_${id}`).val(c.consignee);
            if (c.payment_type) $('#payment_type').val(c.payment_type);
            if (c.narration) $('#narration').val(c.narration);
            $(`#shipper_${id}`).trigger('change'); // update memory
        }
    }

    function autoFillTransporter() {
        const val = $('#transporter_name').val();
        const opt = $('#transporterList option').filter(function() { return this.value === val; });
        if (opt.length) {
            $('#transporter_mobile').val(opt.data('mobile'));
        }
    }

    function autoFillDriver() {
        const val = $('#driver_name').val();
        const opt = $('#driverList option').filter(function() { return this.value === val; });
        if (opt.length) {
            $('#driver_mobile').val(opt.data('mobile'));
            $('#vehicle_no').val(opt.data('vehicle'));
            $('#driver_license_no').val(opt.data('license'));
        }
    }


    // WEIGHT CALCULATIONS
    // RECALCULATE ALL
    function recalcAllItems() {
        $('.calc-weight').trigger('input');
    }

    // WEIGHT CALCULATIONS & TOTALS
    $(document).on('input', '.calc-weight, .calc-dim, [name*="[pieces]"]', function() {
        let totalWt = 0;
        let totalPc = 0;
        const volFormula = parseFloat($('#volumetric_formula').val()) || 6000;

        $('.shipment-item').each(function() {
            const row = $(this);
            const actual = parseFloat(row.find('.calc-weight').val()) || 0;
            const l = parseFloat(row.find('[name*="length"]').val()) || 0;
            const w = parseFloat(row.find('[name*="width"]').val()) || 0;
            const h = parseFloat(row.find('[name*="height"]').val()) || 0;
            const pieces = parseInt(row.find('[name*="[pieces]"]').val()) || 1;

            const volumetric = (l * w * h) / volFormula;
            const chargeable = Math.max(actual, volumetric);

            row.find('[name*="volumetric_weight"]').val(volumetric.toFixed(2));
            row.find('[name*="calculated_chargeable_weight"]').val(chargeable.toFixed(2));
            
            // Only update chargeable_weight if it hasn't been manually overridden
            // We can assume if they are editing weight/dims, we recalculate
            row.find('[name*="chargeable_weight"]').val(chargeable.toFixed(2));
            
            totalWt += chargeable;
            totalPc += pieces;
        });
        
        $('#total_weight').val(totalWt.toFixed(2));
        $('#total_pieces').val(totalPc);
    });

    // TAB NAVIGATION
    function nextTab() {
        const form = document.getElementById('bookingForm');
        const firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth' });
            firstInvalid.focus();
            return;
        }
        syncSalesRateFromFirstItem();
        document.getElementById('tab2-tab').disabled = false;
        new bootstrap.Tab(document.getElementById('tab2-tab')).show();
    }

    function syncSalesRateFromFirstItem() {
        const firstItemRateInput = $('input[name*="[rate]"]').filter(function() {
            return $(this).val().toString().trim() !== '';
        }).first();
        const firstItemRate = parseFloat(firstItemRateInput.val()) || 0;
        if (firstItemRate > 0) {
            $('#salesRate').val(firstItemRate).trigger('input');
        }
    }

    // JavaScript
    $(document).on('input', 'input[name*="[rate]"]', function() {
        syncSalesRateFromFirstItem();
    });

    $(document).on('input', '[name="rate"], [name="weight"], [name="ddc"], [name="ssc"], [name="btc"], [name="flc"], [name="doc"], [name="inbound_tsp"], [name="outbound_tsp"], [name="tcp"], [name="utility_charges"], [name="xray_charges"], [name="ado"], [name="awb_fees_agent"], [name="awb_fees_carrier"], [name="admin_charges"], [name="delivery_order_charges"], [name="inbound_handling"], [name="inbound_storage"], [name="outbound_storage"], [name="misc_charges"]', function() {
        let taxable = 0;

        // Freight: Rate × Weight (MAIN REVENUE)
        const rate = parseFloat($('[name="rate"]').val()) || 0;
        const weight = parseFloat($('[name="weight"]').val()) || 0;
        taxable += rate * weight;

        // All Individual Charges
        const charges = ['ddc','ssc','btc','flc','doc','inbound_tsp','outbound_tsp','tcp','utility_charges','xray_charges','ado','awb_fees_agent','awb_fees_carrier','admin_charges','delivery_order_charges','inbound_handling','inbound_storage','outbound_storage','misc_charges'];
        charges.forEach(field => taxable += parseFloat($(`[name="${field}"]`).val()) || 0);

        // GST Calculation
        let cgst = Math.round(taxable * (_companyGst.cgst / 100));
        let sgst = Math.round(taxable * (_companyGst.sgst / 100));
        let igst = Math.round(taxable * (_companyGst.igst / 100));
        let netPayable = taxable + cgst + sgst + igst;

        $('#totalTaxableAmount').text('₹' + taxable.toFixed(2));
        $('#netPayableAmount').text('₹' + netPayable.toFixed(2));
    });

    // Async Form Submit
    $('#bookingForm').on('submit', function() {
        const btn = $('#mainSubmitBtn');
        btn.prop('disabled', true);
        btn.html('Saving...');
    });

</script>
<?= $this->endSection() ?>