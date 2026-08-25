<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> All Invoices (Consolidated Billing)</h4>
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

    <ul class="nav nav-tabs invoice-workspace-tabs mb-3" id="invoiceWorkspaceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="generateInvoiceTab" data-bs-toggle="tab" data-bs-target="#generateInvoicePane" type="button" role="tab" aria-controls="generateInvoicePane" aria-selected="true">
                <i class="fas fa-file-invoice me-1"></i> Generate Invoice
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="downloadHistoryTab" data-bs-toggle="tab" data-bs-target="#downloadHistoryPane" type="button" role="tab" aria-controls="downloadHistoryPane" aria-selected="false">
                <i class="fas fa-download me-1"></i> All Downloads
                <span class="badge bg-light text-secondary border ms-1" id="downloadHistoryCount"><?= count($downloads ?? []) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="generateInvoicePane" role="tabpanel" aria-labelledby="generateInvoiceTab">
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
                                        <option value="<?= esc($b['id']) ?>" <?= (int) ($b['is_default'] ?? 0) === 1 ? 'selected' : '' ?>>
                                            <?= esc($b['bank_name']) ?> (<?= esc($b['account_number']) ?>) <?= $b['is_default'] == 1 ? '[Default]' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Billing Mode</label>
                                <div class="btn-group w-100 invoice-segment" role="group" aria-label="Billing mode">
                                    <input type="radio" class="btn-check" name="billing_mode" id="billingModeAwb" value="awb" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary btn-sm fw-semibold" for="billingModeAwb">AWB Wise</label>
                                    <input type="radio" class="btn-check" name="billing_mode" id="billingModeDocket" value="docket" autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm fw-semibold" for="billingModeDocket">Docket Wise</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">PDF Layout</label>
                                <div class="btn-group w-100 invoice-segment" role="group" aria-label="PDF layout">
                                    <input type="radio" class="btn-check" name="layout_orientation" id="layoutLandscape" value="landscape" autocomplete="off" checked>
                                    <label class="btn btn-outline-secondary btn-sm fw-semibold" for="layoutLandscape">Landscape</label>
                                    <input type="radio" class="btn-check" name="layout_orientation" id="layoutPortrait" value="portrait" autocomplete="off">
                                    <label class="btn btn-outline-secondary btn-sm fw-semibold" for="layoutPortrait">Portrait</label>
                                </div>
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
                                <label class="form-label text-muted fs-7 fw-semibold">Invoice Number</label>
                                <input type="text" name="invoice_no" id="fInvoiceNo" class="form-control form-control-sm shadow-none fw-bold text-uppercase" placeholder="Optional">
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

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="club_by_lr" id="clubByLr" value="1">
                                    <label class="form-check-label fw-semibold text-muted fs-7" for="clubByLr">Club same LR / Docket into one invoice line</label>
                                </div>
                            </div>

                            <!-- GST Options removed in favor of automated settings per booking -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Shipment Records Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm invoice-records-card">
                    <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-truck-loading me-2"></i> Shipment Records</h6>
                        <span id="selectedCount" class="badge bg-primary rounded-pill">0 Selected</span>
                    </div>
                    <div class="card-body p-0 position-relative">
                        <!-- Loading Overlay -->
                        <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-none align-items-center justify-content-center" style="z-index: 10;">
                            <div class="text-center">
                                <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                                <div class="text-muted fs-7">Loading shipment records...</div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="d-flex flex-column align-items-center justify-content-center p-5 text-center">
                            <i class="fas fa-folder-open text-muted mb-3 fs-1"></i>
                            <h6 class="fw-bold text-muted mb-1">No Records Loaded</h6>
                            <p class="text-secondary fs-7 mb-0">Select a customer and date range on the left to filter shipment records.</p>
                        </div>

                        <!-- Shipment Table -->
                        <div id="tableContainer" class="d-none">
                            <div class="table-responsive invoice-records-scroll">
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
                            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fs-7 text-secondary fw-semibold">
                                    Total Weight: <span id="sumWeight" class="text-dark fw-bold">0.00</span> KG | 
                                    Total Boxes: <span id="sumBoxes" class="text-dark fw-bold">0</span>
                                </div>
                                <div>
                                    <input type="hidden" name="export_type" id="export_type" value="pdf">
                                    <button type="submit" value="excel" class="btn btn-outline-success fw-bold shadow-sm px-4 me-2">
                                        <i class="fas fa-file-excel me-2"></i> Export Excel
                                    </button>
                                    <button type="submit" value="pdf" class="btn btn-success fw-bold shadow-sm px-4">
                                        <i class="fas fa-file-pdf me-2"></i> Generate Consolidated Invoice
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

        </div>
        <div class="tab-pane fade" id="downloadHistoryPane" role="tabpanel" aria-labelledby="downloadHistoryTab">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-download me-2"></i> All Downloads</h6>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="month" id="downloadHistoryMonth" class="form-control form-control-sm shadow-none" value="<?= date('Y-m') ?>" style="width: 150px;">
                <input type="search" id="downloadHistorySearch" class="form-control form-control-sm shadow-none" placeholder="Search downloads..." style="width: min(220px, 70vw);">
                <span class="badge bg-light text-secondary border" id="downloadHistoryCardCount"><?= count($downloads ?? []) ?> Saved</span>
            </div>
        </div>
        <div class="px-3 py-3 border-bottom bg-light">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted fw-semibold">Month Billing</div>
                    <div class="fs-5 fw-bold text-success">₹<span id="downloadMonthAmount"><?= esc(number_format(array_sum(array_map(static fn ($d) => (float) ($d['total_amount'] ?? 0), $downloads ?? [])), 2)) ?></span></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted fw-semibold">Invoices Saved</div>
                    <div class="fs-5 fw-bold text-dark" id="downloadMonthCount"><?= count($downloads ?? []) ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted fw-semibold">Selected Month</div>
                    <div class="fs-5 fw-bold text-primary" id="downloadMonthLabel"><?= esc(date('M Y')) ?></div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Downloaded At</th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Bill To</th>
                            <th>Period</th>
                            <th class="text-end">Amount</th>
                            <th>Layout</th>
                            <th>User</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="downloadHistoryTableBody">
                        <?php if (!empty($downloads ?? [])): ?>
                            <?php foreach ($downloads as $download): ?>
                                <tr class="download-history-row">
                                    <td class="ps-3"><?= esc(date('d-M-Y H:i', strtotime($download['downloaded_at']))) ?></td>
                                    <td class="fw-semibold text-primary"><?= esc($download['invoice_no']) ?></td>
                                    <td><?= esc($download['customer_name']) ?></td>
                                    <td><?= esc($download['bill_to'] ?: '-') ?></td>
                                    <td>
                                        <?= !empty($download['from_date']) ? esc(date('d-M-Y', strtotime($download['from_date']))) : '-' ?>
                                        to
                                        <?= !empty($download['to_date']) ? esc(date('d-M-Y', strtotime($download['to_date']))) : '-' ?>
                                    </td>
                                    <td class="text-end fw-semibold">₹<?= esc(number_format((float) ($download['total_amount'] ?? 0), 2)) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= esc(ucfirst($download['layout_orientation'])) ?></span></td>
                                    <td><?= esc($download['downloaded_by'] ?: 'Unknown') ?></td>
                                    <td class="text-end pe-3">
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= base_url('logistics/all-invoices/downloads/' . $download['id']) ?>">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <?php if ((session()->get('permissions')['can_delete'] ?? 0) == 1): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1 delete-invoice-download-btn" data-id="<?= (int) $download['id'] ?>" data-invoice="<?= esc($download['invoice_no']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No consolidated invoice downloads saved yet.</td>
                            </tr>
                        <?php endif; ?>
                        <tr id="downloadHistoryNoMatches" class="d-none">
                            <td colspan="9" class="text-center py-4 text-muted">No matching downloads found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
.text-nowrap { white-space: nowrap; }
.sticky-top { top: 0; }
.invoice-segment .btn { min-height: 34px; }
.invoice-segment .btn-check:checked + .btn {
    box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.15);
}
.invoice-workspace-tabs .nav-link {
    font-weight: 700;
}
.invoice-records-scroll {
    max-height: min(56vh, 520px);
    overflow-y: auto;
}
#emptyState {
    min-height: 350px;
}
</style>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
const _customers = <?= json_encode($customers) ?>;
let isSubmitting = false;

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
            } else {
                $('#fGstWarning').removeClass('d-none');
            }
        } else {
            $('#fGstWarning').addClass('d-none');
        }
    });

    // Track which submit button was clicked
    $('button[type="submit"]').on('click', function() {
        $('#export_type').val($(this).val());
    });

    // Event listeners to fetch shipments
    $('#fCustomer, #fFromDate, #fToDate').on('change', function() {
        loadShipments();
    });

    $('input[name="billing_mode"]').on('change', function() {
        if ($(this).val() === 'docket' && $(this).is(':checked')) {
            $('#clubByLr').prop('checked', true);
        }
    });

    $('#downloadHistorySearch').on('input', function() {
        const needle = ($(this).val() || '').toLowerCase().trim();
        let visibleRows = 0;
        $('.download-history-row').each(function() {
            const matches = $(this).text().toLowerCase().includes(needle);
            $(this).toggle(matches);
            if (matches) visibleRows++;
        });
        $('#downloadHistoryNoMatches').toggleClass('d-none', visibleRows > 0);
    });

    $('#downloadHistoryMonth').on('change', function() {
        refreshDownloadHistory();
    });

    $(document).on('click', '.delete-invoice-download-btn', function() {
        deleteInvoiceDownload(parseInt($(this).data('id') || 0), $(this).data('invoice') || '-');
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
        if (isSubmitting) {
            return true;
        }

        const checkedBoxes = $('.shipment-checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one shipment record to generate the invoice.');
            return false;
        }

        // Check for GST mismatches among selected records
        let igstRecords = [];
        let cgstSgstRecords = [];
        let noGstRecords = [];
        let invoicePrefixMismatchRecords = [];

        checkedBoxes.each(function() {
            const checkbox = $(this);
            const gstApplied = parseInt(checkbox.data('gst-applied') || 0) === 1;
            const cgstRate = parseFloat(checkbox.data('cgst-rate') || 0);
            const sgstRate = parseFloat(checkbox.data('sgst-rate') || 0);
            const igstRate = parseFloat(checkbox.data('igst-rate') || 0);
            const awbNo = checkbox.data('awb-no') || '';
            const docketNo = checkbox.data('docket-no') || '';
            const invoiceNo = checkbox.data('invoice-no') || '';
            const templateGstType = checkbox.data('invoice-template-gst-type') || '';
            const templateName = checkbox.data('invoice-template-name') || '';
            const templatePrefix = checkbox.data('invoice-template-prefix') || '';
            
            const recordInfo = { awb: awbNo, docket: docketNo };

            if (!gstApplied) {
                noGstRecords.push(recordInfo);
            } else if (igstRate > 0) {
                igstRecords.push(recordInfo);
            } else if (cgstRate > 0 || sgstRate > 0) {
                cgstSgstRecords.push(recordInfo);
            } else {
                noGstRecords.push(recordInfo);
            }

            if (templateGstType) {
                const templateIsGst = templateGstType !== 'non_gst';
                const rowIsGst = gstApplied && (igstRate > 0 || cgstRate > 0 || sgstRate > 0);
                if (templateIsGst !== rowIsGst) {
                    invoicePrefixMismatchRecords.push({
                        awb: awbNo,
                        docket: docketNo,
                        invoiceNo: invoiceNo,
                        templateName: templateName,
                        templatePrefix: templatePrefix,
                        templateType: templateIsGst ? 'GST' : 'Non-GST',
                        recordType: rowIsGst ? 'GST' : 'Non-GST'
                    });
                }
            }
        });

        // Determine if we have a mismatch
        const activeTypes = [];
        if (igstRecords.length > 0) activeTypes.push('IGST');
        if (cgstSgstRecords.length > 0) activeTypes.push('CGST/SGST');
        if (noGstRecords.length > 0) activeTypes.push('No GST');

        if (activeTypes.length > 1) {
            e.preventDefault();

            // Find majority type
            let majorityType = 'IGST';
            const counts = {
                'IGST': igstRecords.length,
                'CGST/SGST': cgstSgstRecords.length,
                'No GST': noGstRecords.length
            };

            let maxCount = -1;
            for (const type in counts) {
                if (counts[type] > maxCount) {
                    maxCount = counts[type];
                    majorityType = type;
                }
            }

            // Prepare list of mismatched records (those not matching the majorityType)
            let mismatchDetails = '<div class="mt-2" style="max-height: 200px; overflow-y: auto;">';
            if (majorityType !== 'IGST' && igstRecords.length > 0) {
                mismatchDetails += '<strong class="text-primary">IGST Records:</strong><br>';
                igstRecords.forEach(r => {
                    mismatchDetails += `- AWB: ${r.awb} (Docket: ${r.docket})<br>`;
                });
            }
            if (majorityType !== 'CGST/SGST' && cgstSgstRecords.length > 0) {
                mismatchDetails += '<strong class="text-primary">CGST/SGST Records:</strong><br>';
                cgstSgstRecords.forEach(r => {
                    mismatchDetails += `- AWB: ${r.awb} (Docket: ${r.docket})<br>`;
                });
            }
            if (majorityType !== 'No GST' && noGstRecords.length > 0) {
                mismatchDetails += '<strong class="text-primary">No GST Records:</strong><br>';
                noGstRecords.forEach(r => {
                    mismatchDetails += `- AWB: ${r.awb} (Docket: ${r.docket})<br>`;
                });
            }
            mismatchDetails += '</div>';

            Swal.fire({
                title: 'GST Mismatch Warning',
                html: `<div class="text-start fs-7">
                        <p class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Mismatched GST configurations detected among selected records!</p>
                        <p>Majority configuration is: <span class="badge bg-secondary">${majorityType}</span></p>
                        ${mismatchDetails}
                        <p class="mt-3">Would you like to proceed with generating the consolidated report under the majority's settings?</p>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    proceedInvoiceSubmit();
                }
            });
            return false;
        }

        if (invoicePrefixMismatchRecords.length > 0) {
            e.preventDefault();
            const mismatchRows = invoicePrefixMismatchRecords.slice(0, 12).map(r => {
                return `- AWB: ${escapeHistoryHtml(r.awb)} (Docket: ${escapeHistoryHtml(r.docket)}, Invoice: ${escapeHistoryHtml(r.invoiceNo || '-')}, ${escapeHistoryHtml(r.templateName || r.templatePrefix)} is ${escapeHistoryHtml(r.templateType)}, record is ${escapeHistoryHtml(r.recordType)})`;
            }).join('<br>');
            const extraCount = invoicePrefixMismatchRecords.length > 12 ? `<br>...and ${invoicePrefixMismatchRecords.length - 12} more` : '';

            Swal.fire({
                title: 'Invoice Prefix Mismatch',
                html: `<div class="text-start fs-7">
                    <p class="text-danger fw-bold mb-2"><i class="fas fa-exclamation-triangle"></i> Some selected shipment rows use an invoice prefix whose master type does not match the row GST status.</p>
                    <div class="mt-2" style="max-height: 220px; overflow-y: auto;">${mismatchRows}${extraCount}</div>
                    <p class="mb-0 mt-3">You can still proceed if this is intentional.</p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Proceed Anyway',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    proceedInvoiceSubmit();
                }
            });
            return false;
        }

        if ($('#export_type').val() === 'pdf') {
            e.preventDefault();
            proceedInvoiceSubmit();
            return false;
        }

        return true;
    });

    $(window).on('focus', function() {
        refreshDownloadHistory();
    });
});

function escapeHistoryHtml(value) {
    if (typeof ERPUtils !== 'undefined' && typeof ERPUtils.escapeHtml === 'function') {
        return ERPUtils.escapeHtml(value);
    }
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function scheduleDownloadHistoryRefresh() {
    if ($('#export_type').val() !== 'pdf') return;
    setTimeout(refreshDownloadHistory, 1500);
    setTimeout(refreshDownloadHistory, 4000);
}

function proceedInvoiceSubmit() {
    if ($('#export_type').val() !== 'pdf') {
        isSubmitting = true;
        $('#invoiceForm')[0].submit();
        isSubmitting = false;
        return;
    }

    generatePdfWithSavePicker();
}

async function generatePdfWithSavePicker() {
    if (isSubmitting) return;
    isSubmitting = true;

    const form = document.getElementById('invoiceForm');
    const formData = new FormData(form);
    formData.set('export_type', 'pdf');

    Swal.fire({
        title: 'Generating Invoice...',
        text: 'Please wait while the PDF is prepared.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            credentials: 'same-origin'
        });

        const result = await response.json();
        if (result.csrf_hash) {
            $('meta[name="csrf-token"]').attr('content', result.csrf_hash);
            $('input[name="<?= csrf_token() ?>"]').val(result.csrf_hash);
        }
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Invoice generation failed.');
        }

        Swal.close();

        // Direct open/download without exhausting multi-step alerts
        if (result.download_url) {
            window.open(result.download_url, '_blank');
        }
        refreshDownloadHistory();
    } catch (error) {
        Swal.fire('Download Failed', error.message || 'Unable to generate invoice PDF.', 'error');
    } finally {
        isSubmitting = false;
    }
}

function refreshDownloadHistory() {
    $.ajax({
        url: BASE_URL + 'logistics/all-invoices/downloads',
        type: 'GET',
        data: {
            month: $('#downloadHistoryMonth').val()
        },
        dataType: 'json',
        success: function(res) {
            if (!res || res.status !== 'success') return;
            updateDownloadMonthSummary(res);
            renderDownloadHistory(res.downloads || []);
        }
    });
}

function updateDownloadMonthSummary(res) {
    const monthValue = res.month || $('#downloadHistoryMonth').val();
    const monthDate = monthValue ? new Date(monthValue + '-01T00:00:00') : new Date();
    const label = monthDate.toLocaleString(undefined, { month: 'short', year: 'numeric' });
    $('#downloadMonthLabel').text(label);
    $('#downloadMonthCount').text((res.downloads || []).length);
    $('#downloadMonthAmount').text(res.month_total_display || '0.00');
}

function renderDownloadHistory(downloads) {
    const tbody = $('#downloadHistoryTableBody');
    tbody.empty();
    $('#downloadHistoryCount').text(downloads.length);
    $('#downloadHistoryCardCount').text(downloads.length + ' Saved');

    if (!downloads.length) {
        tbody.append('<tr><td colspan="9" class="text-center py-4 text-muted">No consolidated invoice downloads saved yet.</td></tr>');
        tbody.append('<tr id="downloadHistoryNoMatches" class="d-none"><td colspan="9" class="text-center py-4 text-muted">No matching downloads found.</td></tr>');
        return;
    }

    downloads.forEach(function(download) {
        const deleteButton = <?= (session()->get('permissions')['can_delete'] ?? 0) == 1 ? 'true' : 'false' ?>
            ? `<button type="button" class="btn btn-sm btn-outline-danger ms-1 delete-invoice-download-btn" data-id="${parseInt(download.id || 0)}" data-invoice="${escapeHistoryHtml(download.invoice_no || '-')}"><i class="fas fa-trash"></i></button>`
            : '';
        tbody.append(`
            <tr class="download-history-row">
                <td class="ps-3">${escapeHistoryHtml(download.downloaded_at_display || '-')}</td>
                <td class="fw-semibold text-primary">${escapeHistoryHtml(download.invoice_no || '-')}</td>
                <td>${escapeHistoryHtml(download.customer_name || '-')}</td>
                <td>${escapeHistoryHtml(download.bill_to || '-')}</td>
                <td>${escapeHistoryHtml(download.from_date_display || '-')} to ${escapeHistoryHtml(download.to_date_display || '-')}</td>
                <td class="text-end fw-semibold">₹${escapeHistoryHtml(download.total_amount_display || '0.00')}</td>
                <td><span class="badge bg-light text-dark border">${escapeHistoryHtml((download.layout_orientation || '-').replace(/^./, c => c.toUpperCase()))}</span></td>
                <td>${escapeHistoryHtml(download.downloaded_by || 'Unknown')}</td>
                <td class="text-end pe-3">
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="${escapeHistoryHtml(download.view_url || '#')}">
                        <i class="fas fa-eye me-1"></i> View
                    </a>
                    ${deleteButton}
                </td>
            </tr>
        `);
    });
    tbody.append('<tr id="downloadHistoryNoMatches" class="d-none"><td colspan="9" class="text-center py-4 text-muted">No matching downloads found.</td></tr>');
    $('#downloadHistorySearch').trigger('input');
}

function deleteInvoiceDownload(id, invoiceNo) {
    if (!id) return;

    Swal.fire({
        title: 'Delete invoice download?',
        text: 'This removes saved invoice ' + invoiceNo + ' from All Downloads.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (!result.isConfirmed) return;

        $.ajax({
            url: BASE_URL + 'logistics/all-invoices/downloads/delete/' + id,
            type: 'POST',
            dataType: 'json',
            data: {
                <?= csrf_token() ?>: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (res && res.csrf_hash) {
                    $('meta[name="csrf-token"]').attr('content', res.csrf_hash);
                }
                if (!res || res.status !== 'success') {
                    ERPUtils.showError('Delete Failed', res && res.message ? res.message : 'Unable to delete this invoice download.');
                    return;
                }
                refreshDownloadHistory();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to delete this invoice download.';
                ERPUtils.showError('Delete Failed', msg);
            }
        });
    });
}

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
                                <input class="form-check-input shadow-none shipment-checkbox" type="checkbox" name="item_ids[]" value="${row.id}" 
                                    data-weight="${parseFloat(row.final_chargeable_weight || 0)}" 
                                    data-boxes="${parseInt(row.pieces || 0)}"
                                    data-gst-applied="${row.gst_applied}"
                                    data-cgst-rate="${parseFloat(row.cgst_rate || 0)}"
                                    data-sgst-rate="${parseFloat(row.sgst_rate || 0)}"
                                    data-igst-rate="${parseFloat(row.igst_rate || 0)}"
                                    data-awb-no="${row.awb_no || ''}"
                                    data-docket-no="${row.docket_no || ''}"
                                    data-invoice-no="${escapeHistoryHtml(row.invoice_no || '')}"
                                    data-invoice-template-name="${escapeHistoryHtml(row.invoice_template_name || '')}"
                                    data-invoice-template-gst-type="${escapeHistoryHtml(row.invoice_template_gst_type || '')}"
                                    data-invoice-template-prefix="${escapeHistoryHtml(row.invoice_template_prefix || '')}"
                                    checked>
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
