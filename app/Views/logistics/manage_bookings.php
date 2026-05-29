<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
             Manage <?= esc($company_name ?? '') ?> Bookings
        </h2>
        <div>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary me-2">
                Dashboard
            </a>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-success">
                New Booking
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="exportSelected()">
                    <i class="fas fa-file-excel me-1"></i> Export Selected
                </button>
            </div>
            <table id="bookingsTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th width="3%"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                        <th>AWB No.</th>
                        <th>Date</th>
                        <th>Origin → Dest</th>
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
        { data: 'booking_date' },
        { 
            data: null,
            render: function(data, type, row) {
                return `${row.origin} → ${row.destination}`;
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