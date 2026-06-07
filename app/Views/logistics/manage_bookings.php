<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <style>
        #bookingsTable th, #bookingsTable td {
            white-space: nowrap;
        }
        #bookingsTable .consignee-cell {
            white-space: normal !important;
            word-break: break-word;
            min-width: 250px !important;
            width: 250px !important;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
             Manage <?= esc($company_name ?? '') ?> Bookings
        </h2>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-dark bg-white shadow-sm fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#screen-options-collapse" aria-expanded="false" aria-controls="screen-options-collapse">
                <i class="fas fa-cog me-1"></i> Screen Options
            </button>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary">
                Dashboard
            </a>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-success">
                New Booking
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Screen Options Panel (WordPress Style) -->
    <div class="collapse mb-4" id="screen-options-collapse">
        <div class="card card-body shadow-sm border-0 rounded-3 p-4 bg-white">
            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-sliders-h me-2 text-primary"></i> Screen Options: Customize Table Columns</h6>
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-awb" value="1" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-awb">AWB No.</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-docket" value="2" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-docket">Docket No</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-date" value="3" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-date">Date</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-route" value="4" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-route">Origin &rarr; Destination</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-customer" value="5" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-customer">Customer</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-consignee" value="6" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-consignee">Consignee</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-status" value="7" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-status">Status</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-pieces" value="8" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-pieces">Pieces</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-weight" value="9" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-weight">Total Wt</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input col-toggle-chk" type="checkbox" id="chk-col-amount" value="10" checked>
                    <label class="form-check-label fw-semibold text-dark fs-7" for="chk-col-amount">Amount</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="exportSelected()">
                    <i class="fas fa-file-excel me-1"></i> Export Selected
                </button>
            </div>
            <div class="table-responsive">
                <table id="bookingsTable" class="table table-hover table-bordered w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="3%"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                            <th>AWB No.</th>
                            <th>Docket No</th>
                            <th>Date</th>
                            <th>Origin → Dest</th>
                            <th>Customer</th>
                            <th class="consignee-cell">Consignee</th>
                            <th>Status</th>
                            <th>Pieces</th>
                            <th>Total Wt</th>
                            <th>Amount</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->include('logistics/pod_tracking_drawer') ?>


<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- JavaScript -->
<script>
let dataTable;

$(document).ready(function() {
    dataTable = ERPUtils.initDataTable('#bookingsTable', BASE_URL + 'logistics/ajax-datatable', [
        { 
            data: null, 
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return `<input type="checkbox" class="booking-check" value="${row.id}">`;
            }
        },
        { 
            data: 'awb_no',
            render: function(data, type, row) {
                return `<strong>${data}</strong>`;
            }
        },
        { 
            data: 'docket_no',
            render: function(data) {
                if (data && data.indexOf(',') !== -1) {
                    let parts = data.split(',');
                    return `<span class="text-muted fw-semibold" style="font-size:0.85rem;" title="${data}">${parts[0].trim()}...</span>`;
                }
                return `<span class="text-muted fw-semibold" style="font-size:0.85rem;">${data || '-'}</span>`;
            }
        },
        { 
            data: 'booking_date',
            render: function(data, type, row) {
                let html = `<div class="fw-bold text-dark" style="font-size: 0.85rem;">${data}</div>`;
                let logsHtml = '<div class="mt-1" style="font-size: 0.68rem; line-height: 1.35; color: #475569;">';
                
                if (row.last_action) {
                    let actionText = '';
                    let badgeClass = '';
                    if (row.last_action.action === 'created') {
                        actionText = 'Created';
                        badgeClass = 'text-success';
                    } else if (row.last_action.action === 'updated') {
                        actionText = 'Updated';
                        badgeClass = 'text-primary';
                    } else if (row.last_action.action === 'viewed') {
                        actionText = 'Viewed';
                        badgeClass = 'text-info';
                    } else {
                        actionText = row.last_action.action.charAt(0).toUpperCase() + row.last_action.action.slice(1);
                        badgeClass = 'text-secondary';
                    }
                    logsHtml += `<div><span class="${badgeClass} fw-bold">${actionText}:</span> ${row.last_action.username} (ID: ${row.last_action.user_id})</div>`;
                } else if (row.creator_name) {
                    logsHtml += `<div><span class="text-success fw-bold">Created:</span> ${row.creator_name} (ID: ${row.creator_id})</div>`;
                } else {
                    logsHtml += `<div><span class="text-muted italic">No logs available</span></div>`;
                }
                
                logsHtml += '</div>';
                return html + logsHtml;
            }
        },
        {
            data: null,
            render: function(data, type, row) {
                return `<span class="fw-medium text-dark">${row.origin || '-'}</span> <span class="text-muted mx-1">&rarr;</span> <span class="fw-medium text-dark">${row.destination || '-'}</span>`;
            }
        },
        {
            data: 'customer_name',
            render: function(data) {
                return `<span class="fw-semibold text-dark" style="font-size:0.85rem;">${data}</span>`;
            }
        },
        {
            data: 'consignee',
            className: 'consignee-cell',
            render: function(data) {
                return `<span class="text-muted" style="font-size:0.85rem;">${data}</span>`;
            }
        },
        { 
            data: 'status',
            render: function(data) {
                let badgeClass = 'bg-secondary';
                if (data === 'Delivered') badgeClass = 'bg-success';
                else if (data === 'In-Transit') badgeClass = 'bg-warning';
                return `<span class="badge ${badgeClass}">${data}</span>`;
            }
        },
        { data: 'total_pieces' },
        { 
            data: 'total_weight',
            render: data => `${data}kg`
        },
        { 
            data: 'total_amount',
            render: function(data) {
                return `<strong class="text-success">₹${data}</strong>`;
            }
        },
        { 
            data: null, 
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                let actions = `
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-info" title="Tracking / POD" onclick="openTrackingDrawer('${row.id}', '${row.awb_no}', '${(row.customer_name || '').replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-location-dot"></i>
                        </button>
                        <a href="${BASE_URL}logistics/view/${row.id}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                `;
                
                if (row.can_edit == 1) {
                    actions += `<a href="${BASE_URL}logistics/edit/${row.id}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>`;
                }
                
                if (row.can_delete == 1) {
                    actions += `<button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteBooking(${row.id}, '${row.awb_no}')"><i class="fas fa-trash"></i></button>`;
                }
                
                actions += `</div>`;
                return actions;
            }
        }
    ]);

    // Apply Saved Settings or Default
    const storageKey = 'malogistics_booking_columns';

    function loadColumnSettings() {
        let savedSettings = localStorage.getItem(storageKey);
        if (savedSettings) {
            try {
                let columns = JSON.parse(savedSettings);
                $.each(columns, function(index, isVisible) {
                    let colIdx = parseInt(index);
                    // Update checkbox state
                    $(`.col-toggle-chk[value="${colIdx}"]`).prop('checked', isVisible);
                    // Apply visibility to DataTable column index
                    dataTable.column(colIdx).visible(isVisible, false);
                });
                // Recalculate column layout once and redraw to apply changes
                dataTable.columns.adjust().draw(false);
                if (dataTable.responsive) {
                    dataTable.responsive.recalc();
                }
            } catch(e) {
                console.error("Error loading column settings:", e);
            }
        }
    }

    function saveColumnSettings() {
        let settings = {};
        $('.col-toggle-chk').each(function() {
            let colIdx = $(this).val();
            let isChecked = $(this).is(':checked');
            settings[colIdx] = isChecked;
        });
        localStorage.setItem(storageKey, JSON.stringify(settings));
    }

    // Handle Checkbox Changes
    $('.col-toggle-chk').on('change', function() {
        let colIdx = parseInt($(this).val());
        let isVisible = $(this).is(':checked');
        
        dataTable.column(colIdx).visible(isVisible);
        dataTable.columns.adjust();
        if (dataTable.responsive) {
            dataTable.responsive.recalc();
        }
        
        saveColumnSettings();
    });

    // Run column visibility logic right after DataTable initialization
    loadColumnSettings();
});

function deleteBooking(id, awb) {
    ERPUtils.confirmDelete(BASE_URL + 'logistics/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}

function toggleAll() {
    const isChecked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.booking-check').forEach(cb => cb.checked = isChecked);
}

function exportSelected() {
    const checked = document.querySelectorAll('.booking-check:checked');
    if (checked.length === 0) {
        ERPUtils.showError('Selection Empty', 'Please select at least one booking to export.');
        return;
    }
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    window.location.href = BASE_URL + 'logistics/exportExcel?ids=' + ids;
}
</script>
<?= $this->endSection() ?>