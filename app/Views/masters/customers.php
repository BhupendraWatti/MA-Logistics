<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">👥 Customers / Shippers</h4>
        <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Customer
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

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
    <form action="/masters/customers/create" method="POST" class="d-flex flex-column h-100 mb-0">
        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
        <?= csrf_field() ?>
        <div class="offcanvas-body bg-light p-0">
            <div class="erp-drawer-content pb-5">
                <div class="row g-4">
                    
                    <!-- LEFT COLUMN: COMPANY DETAILS -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0">Company Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fs-7 fw-semibold">Company Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fs-7 fw-semibold">Customer Code</label>
                                        <input type="text" name="code" class="form-control form-control-sm shadow-none">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fs-7 fw-semibold">Email Address</label>
                                        <input type="email" name="email" class="form-control form-control-sm shadow-none">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted fs-7 fw-semibold">PAN Number</label>
                                        <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted fs-7 fw-semibold">City</label>
                                        <input type="text" name="city" class="form-control form-control-sm shadow-none">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted fs-7 fw-semibold">Pincode</label>
                                        <input type="text" name="pincode" class="form-control form-control-sm shadow-none">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted fs-7 fw-semibold">Payment Type</label>
                                        <select name="payment_type" class="form-select form-select-sm shadow-none">
                                            <option value="">--Select--</option>
                                            <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                                                <option value="<?= esc($l['value']) ?>"><?= esc($l['value']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label text-muted fs-7 fw-semibold">Bill To <small>(Billing Address)</small></label>
                                        <textarea name="bill_to" class="form-control form-control-sm shadow-none" rows="2"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-muted fs-7 fw-semibold">Consignee <small>(Default Delivery Address)</small></label>
                                        <textarea name="consignee" class="form-control form-control-sm shadow-none" rows="2"></textarea>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label text-muted fs-7 fw-semibold">Narration / Notes</label>
                                        <textarea name="narration" class="form-control form-control-sm shadow-none" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: CONTACT PERSONS -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h6 class="fw-bold text-primary mb-0">Contact Roles (From Master)</h6>
                            </div>
                            <div class="card-body">
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fs-8 fw-semibold mb-1">CS Person (Customer Service)</label>
                                    <select name="cs_person" class="form-select form-select-sm shadow-none">
                                        <option value="">--Select--</option>
                                        <?php foreach($contacts ?? [] as $c): ?>
                                            <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fs-8 fw-semibold mb-1">Contact Person (General)</label>
                                    <select name="contact_person" class="form-select form-select-sm shadow-none">
                                        <option value="">--Select--</option>
                                        <?php foreach($contacts ?? [] as $c): ?>
                                            <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted fs-8 fw-semibold mb-1">Billing Person</label>
                                    <select name="billing_person" class="form-select form-select-sm shadow-none">
                                        <option value="">--Select--</option>
                                        <?php foreach($contacts ?? [] as $c): ?>
                                            <option value="<?= esc($c['id']) ?>"><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <h6 class="fw-bold text-primary mt-4 mb-2">Other Contacts</h6>
                                <div class="accordion" id="addContactsAccordion">
                                    <?php foreach ([1, 2, 3] as $index => $p): ?>
                                        <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                                            <h2 class="accordion-header" id="addHeading<?= $p ?>">
                                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?> fw-semibold bg-light text-dark rounded" type="button" data-bs-toggle="collapse" data-bs-target="#addCollapse<?= $p ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="addCollapse<?= $p ?>">
                                                    <i class="fas fa-user-circle text-muted me-2"></i> Contact Person <?= $p ?>
                                                </button>
                                            </h2>
                                            <div id="addCollapse<?= $p ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="addHeading<?= $p ?>" data-bs-parent="#addContactsAccordion">
                                                <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Select from Master</label>
                                                        <select name="person<?= $p ?>_name" class="form-select form-select-sm shadow-none">
                                                            <option value="">--Select--</option>
                                                            <?php foreach($contacts ?? [] as $c): ?>
                                                                <option value="<?= esc($c['name']) ?>"><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Phone</label>
                                                        <input type="text" name="person<?= $p ?>_phone" class="form-control form-control-sm shadow-none">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Email</label>
                                                        <input type="email" name="person<?= $p ?>_email" class="form-control form-control-sm shadow-none">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
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
