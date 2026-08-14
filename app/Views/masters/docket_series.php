<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-barcode text-primary me-2"></i> Docket Master</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Docket Prefix
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3">
            <table id="docketSeriesTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Prefix</th>
                        <th>Mode</th>
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
        <h5 id="mTitle" class="offcanvas-title fw-bold text-primary"><i class="fas fa-barcode me-2"></i> Add Docket Prefix</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form id="mForm" action="<?= base_url('masters/docket-series/create') ?>" method="POST" class="d-flex flex-column h-100 mb-0">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="fName" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Prefix <span class="text-danger">*</span></label>
                        <input type="text" name="prefix" id="fPrefix" class="form-control form-control-sm shadow-none text-uppercase fw-bold" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Docket Mode <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group" aria-label="Docket mode">
                            <input type="radio" class="btn-check" name="entry_mode" id="modeAuto" value="auto" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm fw-semibold" for="modeAuto">Auto Increment</label>
                            <input type="radio" class="btn-check" name="entry_mode" id="modeManual" value="manual" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm fw-semibold" for="modeManual">Manual</label>
                        </div>
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
    dataTable = ERPUtils.initDataTable('#docketSeriesTable', BASE_URL + 'masters/ajax-datatable/docket-series', [
        { data: 'id' },
        { data: 'name' },
        { data: 'prefix', render: data => `<span class="fw-bold text-primary">${data || '-'}</span>` },
        {
            data: 'entry_mode',
            render: data => data === 'manual'
                ? '<span class="badge bg-secondary">Manual</span>'
                : '<span class="badge bg-success">Auto Increment</span>'
        },
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
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-barcode me-2"></i> Edit Docket Prefix';
    document.getElementById('fName').value = r.name || '';
    document.getElementById('fPrefix').value = r.prefix || '';
    document.getElementById('modeAuto').checked = r.entry_mode !== 'manual';
    document.getElementById('modeManual').checked = r.entry_mode === 'manual';
    document.getElementById('fIsActive').checked = (r.is_active == 1);
    document.getElementById('mForm').action = BASE_URL + 'masters/docket-series/update/' + r.id;
    new bootstrap.Offcanvas(document.getElementById('addModal')).show();
}

function delRecord(id) {
    ERPUtils.confirmDelete(BASE_URL + 'masters/docket-series/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}

document.getElementById('addModal').addEventListener('hidden.bs.offcanvas', () => {
    document.getElementById('mForm').reset();
    document.getElementById('modeAuto').checked = true;
    document.getElementById('fIsActive').checked = true;
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-barcode me-2"></i> Add Docket Prefix';
    document.getElementById('mForm').action = BASE_URL + 'masters/docket-series/create';
});
</script>
<?= $this->endSection() ?>
