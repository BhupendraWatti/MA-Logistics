<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">🚛 Transporters</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i> Add Transporter</button>
    </div>



    <div class="card shadow-sm">
        <div class="card-body p-3">
            <table id="transportersTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Drawer -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-sm" tabindex="-1" id="addModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 id="mTitle" class="offcanvas-title fw-bold text-primary"><i class="fas fa-truck me-2"></i> Add Transporter</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form id="mForm" action="/masters/transporters/create" method="POST" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Transporter Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="fName" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Mobile No.</label>
                        <input type="text" name="mobile" id="fMobile" class="form-control form-control-sm shadow-none">
                    </div>
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="fas fa-check me-2"></i> Save</button>
        </div>
    </form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
let dataTable;

$(document).ready(function() {
    dataTable = ERPUtils.initDataTable('#transportersTable', BASE_URL + 'masters/ajax-datatable/transporters', [
        { data: 'id' },
        { data: 'name' },
        { data: 'mobile', render: data => data ? data : '-' },
        { 
            data: null, 
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                // Escape quotes for json
                const rowJson = JSON.stringify(row).replace(/"/g, '&quot;');
                return `
                    <div class="btn-group">
                        <button class="btn btn-sm btn-warning" onclick="editRow(${rowJson})"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="delRecord(${row.id})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
            }
        }
    ]);
});

function editRow(r) {
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-truck me-2"></i> Edit Transporter';
    document.getElementById('fName').value   = r.name;
    document.getElementById('fMobile').value = r.mobile || '';
    document.getElementById('mForm').action  = '/masters/transporters/update/' + r.id;
    new bootstrap.Offcanvas(document.getElementById('addModal')).show();
}

function delRecord(id) {
    ERPUtils.confirmDelete(BASE_URL + 'masters/transporters/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}

document.getElementById('addModal').addEventListener('hidden.bs.offcanvas', () => {
    document.getElementById('mForm').reset();
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-truck me-2"></i> Add Transporter';
    document.getElementById('mForm').action = '/masters/transporters/create';
});
</script>
<?= $this->endSection() ?>
