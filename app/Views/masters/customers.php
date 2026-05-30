<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">👥 Customers / Shippers</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Customer
        </button>
    </div>



    <div class="card shadow-sm">
        <div class="card-body p-3">
            <table id="customersTable" class="table table-hover table-bordered w-100">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>City</th>
                        <th>Payment Type</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Add Customer Drawer -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-wide" tabindex="-1" id="addModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary"><i class="fas fa-building me-2"></i> Add New Customer</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form action="<?= base_url('masters/customers/create') ?>" method="POST" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
        <?= csrf_field() ?>
        <div class="offcanvas-body bg-light p-0">
            <div class="erp-drawer-content pb-5 p-4">
                <?= view('masters/_customer_form_fields', ['customer' => [], 'lookups' => $lookups]) ?>
            </div>
        </div>
        <div class="sticky-footer bg-white">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm"><i class="fas fa-check me-2"></i> Save Customer</button>
        </div>
    </form>
</div>

<style>
.fs-7 { font-size: 0.85rem; }
.fs-8 { font-size: 0.75rem; }
.accordion-button:not(.collapsed) {
    background-color: #e9ecef !important;
    color: #212529;
    box-shadow: none;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}
</style>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
let dataTable;

$(document).ready(function() {
    dataTable = ERPUtils.initDataTable('#customersTable', BASE_URL + 'masters/ajax-datatable/customers', [
        { data: 'id' },
        { data: 'name' },
        { data: 'code', render: data => data ? data : '-' },
        { data: 'city', render: data => data ? data : '-' },
        { data: 'payment_type', render: data => data ? data : '-' },
        { 
            data: null, 
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return `
                    <div class="btn-group">
                        <a href="${BASE_URL}masters/customers/edit/${row.id}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                        <button class="btn btn-sm btn-danger" onclick="delRecord(${row.id})"><i class="fas fa-trash"></i></button>
                    </div>
                `;
            }
        }
    ]);
});

function delRecord(id) {
    ERPUtils.confirmDelete(BASE_URL + 'masters/customers/delete/' + id, function() {
        dataTable.ajax.reload(null, false);
    });
}
</script>
<?= $this->endSection() ?>
