<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">🏛️ Bank Accounts</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i> Add Bank Account</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3">
            <table id="bankAccountsTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Account Name</th>
                        <th>Bank Name</th>
                        <th>Account Number</th>
                        <th>IFSC Code</th>
                        <th>Branch</th>
                        <th>Default</th>
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
        <h5 id="mTitle" class="offcanvas-title fw-bold text-primary"><i class="fas fa-university me-2"></i> Add Bank Account</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form id="mForm" action="<?= base_url('masters/bank-accounts/create') ?>" method="POST" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Account Holder Name</label>
                        <input type="text" name="account_name" id="fAccountName" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" id="fBankName" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Account Number <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" id="fAccountNumber" class="form-control form-control-sm shadow-none" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">IFSC Code</label>
                        <input type="text" name="ifsc_code" id="fIfscCode" class="form-control form-control-sm shadow-none text-uppercase">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Branch Name</label>
                        <input type="text" name="branch_name" id="fBranchName" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Branch Address</label>
                        <textarea name="branch_address" id="fBranchAddress" class="form-control form-control-sm shadow-none" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Misc Code (MICR/SWIFT)</label>
                        <input type="text" name="misc_code" id="fMiscCode" class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input shadow-none" type="checkbox" name="is_default" id="fIsDefault" value="1">
                            <label class="form-check-label fw-semibold text-secondary" for="fIsDefault">Set as default bank account</label>
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
    dataTable = ERPUtils.initDataTable('#bankAccountsTable', BASE_URL + 'masters/ajax-datatable/bank-accounts', [
        { data: 'id' },
        { data: 'account_name', render: data => data ? data : '-' },
        { data: 'bank_name' },
        { data: 'account_number' },
        { data: 'ifsc_code', render: data => data ? data : '-' },
        { data: 'branch_name', render: data => data ? data : '-' },
        { 
            data: 'is_default',
            render: function(data) {
                return data == 1 
                    ? '<span class="badge bg-success shadow-sm">Default</span>' 
                    : '<span class="badge bg-light text-muted border">No</span>';
            }
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
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-university me-2"></i> Edit Bank Account';
    document.getElementById('fAccountName').value = r.account_name || '';
    document.getElementById('fBankName').value = r.bank_name;
    document.getElementById('fAccountNumber').value = r.account_number;
    document.getElementById('fIfscCode').value = r.ifsc_code || '';
    document.getElementById('fBranchName').value = r.branch_name || '';
    document.getElementById('fBranchAddress').value = r.branch_address || '';
    document.getElementById('fMiscCode').value = r.misc_code || '';
    document.getElementById('fIsDefault').checked = (r.is_default == 1);
    document.getElementById('mForm').action = BASE_URL + 'masters/bank-accounts/update/' + r.id;
    new bootstrap.Offcanvas(document.getElementById('addModal')).show();
}

function delRecord(id) {
    ERPUtils.confirmDelete(BASE_URL + 'masters/bank-accounts/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}

document.getElementById('addModal').addEventListener('hidden.bs.offcanvas', () => {
    document.getElementById('mForm').reset();
    document.getElementById('fIsDefault').checked = false;
    document.getElementById('mTitle').innerHTML = '<i class="fas fa-university me-2"></i> Add Bank Account';
    document.getElementById('mForm').action = BASE_URL + 'masters/bank-accounts/create';
});
</script>
<?= $this->endSection() ?>
