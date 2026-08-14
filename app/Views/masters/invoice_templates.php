<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Invoice Master</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Invoice Type
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3">
            <table id="invoiceTemplatesTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>GST Type</th>
                        <th>Prefix</th>
                        <th>Active</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end erp-drawer erp-drawer-sm" tabindex="-1" id="addModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 id="mTitle" class="offcanvas-title fw-bold text-primary"><i class="fas fa-file-invoice-dollar me-2"></i> Add Invoice Type</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form id="mForm" action="<?= base_url('masters/invoice-templates/create') ?>" method="POST" class="d-flex flex-column h-100 mb-0">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="fName" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">GST / Non-GST <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="Invoice GST type">
                            <input type="radio" class="btn-check" name="gst_type" id="gstTypeGst" value="gst" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm fw-semibold" for="gstTypeGst">GST</label>
                            <input type="radio" class="btn-check" name="gst_type" id="gstTypeNonGst" value="non_gst" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm fw-semibold" for="gstTypeNonGst">Non-GST</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Prefix <span class="text-danger">*</span></label>
                        <input type="text" name="prefix" id="fPrefix" class="form-control form-control-sm shadow-none text-uppercase fw-bold" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input shadow-none" type="checkbox" name="is_active" id="fIsActive" value="1" checked>
                            <label class="form-check-label fw-semibold text-secondary" for="fIsActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="fas fa-check me-2"></i> Save</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let dataTable;

$(document).ready(function() {
    dataTable = ERPUtils.initDataTable('#invoiceTemplatesTable', BASE_URL + 'masters/ajax-datatable/invoice-templates', [
        { data: 'id' },
        { data: 'name' },
        {
            data: 'gst_type',
            render: data => data === 'non_gst'
                ? '<span class="badge bg-secondary">Non-GST</span>'
                : '<span class="badge bg-success">GST</span>'
        },
        { data: 'prefix', render: data => `<span class="fw-bold text-primary">${data || '-'}</span>` },
        {
            data: 'is_active',
            render: data => data == 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-light text-muted border">Inactive</span>'
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
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
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-file-invoice-dollar me-2"></i> Edit Invoice Type';
    document.getElementById('fName').value = r.name || '';
    document.getElementById('fPrefix').value = r.prefix || '';
    document.getElementById('gstTypeGst').checked = r.gst_type !== 'non_gst';
    document.getElementById('gstTypeNonGst').checked = r.gst_type === 'non_gst';
    document.getElementById('fIsActive').checked = (r.is_active == 1);
    document.getElementById('mForm').action = BASE_URL + 'masters/invoice-templates/update/' + r.id;
    new bootstrap.Offcanvas(document.getElementById('addModal')).show();
}

function delRecord(id) {
    ERPUtils.confirmDelete(BASE_URL + 'masters/invoice-templates/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}

document.getElementById('addModal').addEventListener('hidden.bs.offcanvas', () => {
    document.getElementById('mForm').reset();
    document.getElementById('gstTypeGst').checked = true;
    document.getElementById('fIsActive').checked = true;
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-file-invoice-dollar me-2"></i> Add Invoice Type';
    document.getElementById('mForm').action = BASE_URL + 'masters/invoice-templates/create';
});
</script>
<?= $this->endSection() ?>
