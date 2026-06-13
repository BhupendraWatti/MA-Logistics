<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">🧾 All Invoices (Consolidated Billing)</h4>
        <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold"><i class="fas fa-building me-1"></i> <?= esc($company_name) ?></span>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="invoiceForm" action="<?= base_url('logistics/all-invoices/generate') ?>" method="POST" target="_blank">
        <?= csrf_field() ?>
        <div class="row g-4">
            
            <!-- Left Side: Form Details -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom pt-4 pb-3">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-invoice me-2"></i> Invoice Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                <select name="customer_name" id="fCustomer" class="form-select form-select-sm shadow-none fw-bold" required>
                                    <option value="">-- Choose Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= esc($c['name']) ?>"><?= esc($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="fGstWarning" class="text-danger fw-semibold fs-7 mt-1 d-none">
                                    <i class="fas fa-exclamation-triangle me-1"></i> GSTIN not entered in Customer Master. GST options disabled.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Select Bank Account <span class="text-danger">*</span></label>
                                <select name="bank_id" id="fBank" class="form-select form-select-sm shadow-none" required>
                                    <option value="">-- Choose Bank --</option>
                                    <?php foreach ($banks as $b): ?>
                                        <option value="<?= esc($b['id']) ?>">
                                            <?= esc($b['bank_name']) ?> (<?= esc($b['account_number']) ?>) <?= $b['is_default'] == 1 ? '[Default]' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label text-muted fs-7 fw-semibold">From Date <span class="text-danger">*</span></label>
                                <input type="date" name="from_date" id="fFromDate" class="form-control form-control-sm shadow-none" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label text-muted fs-7 fw-semibold">To Date <span class="text-danger">*</span></label>
                                <input type="date" name="to_date" id="fToDate" class="form-control form-control-sm shadow-none" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Invoice Number <span class="text-danger">*</span></label>
                                <input type="text" name="invoice_no" id="fInvoiceNo" class="form-control form-control-sm shadow-none fw-bold text-uppercase" placeholder="e.g. MAL/25-26/185" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" name="invoice_date" id="fInvoiceDate" class="form-control form-control-sm shadow-none fw-semibold" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Due Date</label>
                                <input type="date" name="due_date" id="fDueDate" class="form-control form-control-sm shadow-none">
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Remarks / Narration</label>
                                <textarea name="remark" id="fRemark" class="form-control form-control-sm shadow-none" rows="3" placeholder="Any internal reference or notes..."></textarea>
                            </div>

                            <div class="col-12" id="gstOptionsContainer">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input shadow-none" type="checkbox" name="gst_applied" id="fGstApplied" value="1" checked>
                                    <label class="form-check-label fw-bold text-secondary fs-7" for="fGstApplied">Apply GST</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input shadow-none" type="checkbox" name="is_igst" id="fIsIgst" value="1">
                                    <label class="form-check-label fw-bold text-secondary fs-7" for="fIsIgst">IGST (Inter-state 18%)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Shipment Records Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100 d-flex flex-column">
                    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-truck-loading me-2"></i> Shipment Records</h6>
                        <span id="selectedCount" class="badge bg-primary rounded-pill">0 Selected</span>
                    </div>
                    <div class="card-body p-0 flex-grow-1 position-relative" style="min-height: 350px;">
                        <!-- Loading Overlay -->
                        <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none align-items-center justify-content-center" style="z-index: 10;">
                            <div class="text-center">
                                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                <div class="text-muted fs-7">Loading shipment records...</div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="d-flex flex-column align-items-center justify-content-center h-100 p-5 text-center">
                            <i class="fas fa-folder-open text-muted mb-3 fs-1"></i>
                            <h6 class="fw-bold text-muted mb-1">No Records Loaded</h6>
                            <p class="text-secondary fs-7 mb-0">Select a customer and date range on the left to filter shipment records.</p>
                        </div>

                        <!-- Shipment Table -->
                        <div id="tableContainer" class="d-none h-100 d-flex flex-column">
                            <div class="table-responsive flex-grow-1" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0 text-nowrap">
                                    <thead class="table-light sticky-top" style="z-index: 5;">
                                        <tr>
                                            <th class="ps-3" style="width: 40px;">
                                                <input class="form-check-input shadow-none" type="checkbox" id="selectAllCheckbox" checked>
                                            </th>
                                            <th>Date</th>
                                            <th>Docket No</th>
                                            <th>Invoice No</th>
                                            <th>Origin &rarr; Dest</th>
                                            <th class="text-center">Boxes</th>
                                            <th class="text-center">Chargeable Weight</th>
                                            <th class="text-end pe-3">Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="shipmentsTableBody"></tbody>
                                </table>
                            </div>

                            <!-- Footer CTA -->
                            <div class="p-3 bg-light border-top mt-auto d-flex justify-content-between align-items-center">
                                <div class="fs-7 text-secondary fw-semibold">
                                    Total Weight: <span id="sumWeight" class="text-dark fw-bold">0.00</span> KG | 
                                    Total Boxes: <span id="sumBoxes" class="text-dark fw-bold">0</span>
                                </div>
                                <button type="submit" class="btn btn-success fw-bold shadow-sm px-4">
                                    <i class="fas fa-file-pdf me-2"></i> Generate Consolidated Invoice
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<style>
.fs-7 { font-size: 0.85rem; }
.text-nowrap { white-space: nowrap; }
.sticky-top { top: 0; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const _customers = <?= json_encode($customers) ?>;

$(document).ready(function() {
    // Sync GST visibility when customer changes
    $('#fCustomer').on('change', function() {
        const customerName = $(this).val();
        let hasGst = false;
        
        if (customerName) {
            const customer = _customers.find(c => c.name === customerName);
            if (customer && customer.gst_number && customer.gst_number.trim() !== '') {
                hasGst = true;
            }
        }
        
        if (customerName) {
            if (hasGst) {
                $('#fGstWarning').addClass('d-none');
                $('#gstOptionsContainer').removeClass('d-none');
                $('#fGstApplied').prop('checked', true).prop('disabled', false);
                $('#fIsIgst').prop('disabled', false);
            } else {
                $('#fGstWarning').removeClass('d-none');
                $('#fGstApplied').prop('checked', false).prop('disabled', true);
                $('#fIsIgst').prop('checked', false).prop('disabled', true);
                $('#gstOptionsContainer').addClass('d-none');
            }
        } else {
            $('#fGstWarning').addClass('d-none');
            $('#gstOptionsContainer').removeClass('d-none');
            $('#fGstApplied').prop('checked', true).prop('disabled', false);
            $('#fIsIgst').prop('disabled', false);
        }
    });

    // Event listeners to fetch shipments
    $('#fCustomer, #fFromDate, #fToDate').on('change', function() {
        loadShipments();
    });

    // Select all checkboxes helper
    $('#selectAllCheckbox').on('change', function() {
        const checked = $(this).is(':checked');
        $('.shipment-checkbox').prop('checked', checked);
        updateTotals();
    });

    // Update totals when individual checkbox changes
    $(document).on('change', '.shipment-checkbox', function() {
        updateTotals();
        
        // Update master check status
        const allChecked = $('.shipment-checkbox:checked').length === $('.shipment-checkbox').length;
        $('#selectAllCheckbox').prop('checked', allChecked);
    });

    // Form submit validation
    $('#invoiceForm').on('submit', function(e) {
        if ($('.shipment-checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one shipment record to generate the invoice.');
            return false;
        }
    });
});

function loadShipments() {
    const customer = $('#fCustomer').val();
    const fromDate = $('#fFromDate').val();
    const toDate = $('#fToDate').val();

    if (!customer || !fromDate || !toDate) {
        $('#tableContainer').addClass('d-none');
        $('#emptyState').removeClass('d-none');
        return;
    }

    $('#loadingOverlay').removeClass('d-none').addClass('d-flex');

    $.ajax({
        url: BASE_URL + 'logistics/all-invoices/search',
        type: 'POST',
        data: {
            customer_name: customer,
            from_date: fromDate,
            to_date: toDate,
            <?= csrf_token() ?>: '<?= csrf_hash() ?>'
        },
        dataType: 'json',
        success: function(data) {
            $('#loadingOverlay').removeClass('d-flex').addClass('d-none');
            const tbody = $('#shipmentsTableBody');
            tbody.empty();

            if (data && data.length > 0) {
                $('#emptyState').addClass('d-none');
                $('#tableContainer').removeClass('d-none');

                data.forEach(function(row) {
                    const origin = row.booking_origin ? row.booking_origin.split(',')[0].trim() : '-';
                    const dest = row.booking_destination ? row.booking_destination.split(',')[0].trim() : '-';
                    const tr = `
                        <tr>
                            <td class="ps-3">
                                <input class="form-check-input shadow-none shipment-checkbox" type="checkbox" name="item_ids[]" value="${row.id}" data-weight="${parseFloat(row.final_chargeable_weight || 0)}" data-boxes="${parseInt(row.pieces || 0)}" checked>
                            </td>
                            <td>${row.display_date}</td>
                            <td class="fw-semibold text-primary">${row.docket_no || '-'}</td>
                            <td>${row.invoice_no || '-'}</td>
                            <td>${origin} &rarr; ${dest}</td>
                            <td class="text-center">${row.pieces || 0}</td>
                            <td class="text-center fw-bold">${parseFloat(row.final_chargeable_weight || 0).toFixed(2)}</td>
                            <td class="text-end pe-3">₹${parseFloat(row.rate || 0).toFixed(2)}</td>
                        </tr>
                    `;
                    tbody.append(tr);
                });

                $('#selectAllCheckbox').prop('checked', true);
                updateTotals();
            } else {
                $('#tableContainer').addClass('d-none');
                $('#emptyState').removeClass('d-none');
                // Show a mini info inside empty state
                $('#emptyState').find('p').text('No shipment records found for this customer in the selected date range.');
            }
        },
        error: function(xhr, status, error) {
            $('#loadingOverlay').removeClass('d-flex').addClass('d-none');
            alert('An error occurred while fetching shipments. Please try again.');
        }
    });
}

function updateTotals() {
    let totalWeight = 0;
    let totalBoxes = 0;
    let checkedCount = 0;

    $('.shipment-checkbox:checked').each(function() {
        totalWeight += parseFloat($(this).data('weight') || 0);
        totalBoxes += parseInt($(this).data('boxes') || 0);
        checkedCount++;
    });

    $('#selectedCount').text(checkedCount + ' Selected');
    $('#sumWeight').text(totalWeight.toFixed(2));
    $('#sumBoxes').text(totalBoxes);
}
</script>
<?= $this->endSection() ?>
