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
        <div>
            <?php if (session()->get('role') !== 'tracking'): ?>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary me-2">
                Dashboard
            </a>
            <?php endif; ?>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-success">
                New Booking
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <?php if (session()->get('role') !== 'tracking'): ?>
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="exportSelected()">
                    <i class="fas fa-file-excel me-1"></i> Export Selected
                </button>
            </div>
            <?php endif; ?>
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
                            <th>Remark</th>
                            <th>Status</th>
                            <th>Boxes</th>
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
const USER_ROLE = '<?= session()->get('role') ?>';
let dataTable;

$(document).ready(function() {
    dataTable = ERPUtils.initDataTable('#bookingsTable', BASE_URL + 'logistics/ajax-datatable', [
        { 
            data: null, 
            orderable: false,
            searchable: false,
            visible: USER_ROLE !== 'tracking',
            render: function(data, type, row) {
                return `<input type="checkbox" class="booking-check" value="${row.id}">`;
            }
        },
        { 
            data: 'awb_no',
            render: function(data, type, row) {
                if (row.can_edit == 1) {
                    return `<a href="${BASE_URL}logistics/edit/${row.id}"><strong>${data}</strong></a>`;
                }
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
            data: null,
            render: function(data, type, row) {
                const remark = row.remarks || row.narration || '-';
                return `<span class="text-muted" style="font-size:0.82rem;">${remark}</span>`;
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
            visible: USER_ROLE !== 'tracking',
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
                `;
                
                if (USER_ROLE !== 'tracking') {
                    actions += `<a href="${BASE_URL}logistics/view/${row.id}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>`;
                }
                
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
