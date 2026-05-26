<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-building text-primary me-2"></i> Edit Customer: <?= esc($customer['name']) ?></h4>
        <a href="<?= base_url('masters/customers') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form action="/masters/customers/update/<?= $customer['id'] ?>" method="POST">
        <?= csrf_field() ?>
        <div class="row g-4">
            
            <!-- LEFT COLUMN: COMPANY DETAILS -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0">Company Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" value="<?= esc($customer['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Customer Code</label>
                                <input type="text" name="code" class="form-control form-control-sm shadow-none" value="<?= esc($customer['code'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control form-control-sm shadow-none" value="<?= esc($customer['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">PAN Number</label>
                                <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($customer['pan'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">City</label>
                                <input type="text" name="city" class="form-control form-control-sm shadow-none" value="<?= esc($customer['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">Pincode</label>
                                <input type="text" name="pincode" class="form-control form-control-sm shadow-none" value="<?= esc($customer['pincode'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">Payment Type</label>
                                <select name="payment_type" class="form-select form-select-sm shadow-none">
                                    <option value="">--Select--</option>
                                    <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                                        <option value="<?= esc($l['value']) ?>" <?= ($customer['payment_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mt-4">
                                <label class="form-label text-muted fs-7 fw-semibold">Bill To <small>(Billing Address)</small></label>
                                <textarea name="bill_to" class="form-control form-control-sm shadow-none" rows="3"><?= esc($customer['bill_to'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Consignee <small>(Default Delivery Address)</small></label>
                                <textarea name="consignee" class="form-control form-control-sm shadow-none" rows="3"><?= esc($customer['consignee'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <label class="form-label text-muted fs-7 fw-semibold">Narration / Notes</label>
                                <textarea name="narration" class="form-control form-control-sm shadow-none" rows="2"><?= esc($customer['narration'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: CONTACT PERSONS -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0">Contact Roles (From Master)</h6>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted fs-8 fw-semibold mb-1">CS Person (Customer Service)</label>
                            <select name="cs_person" class="form-select form-select-sm shadow-none">
                                <option value="">--Select--</option>
                                <?php foreach($contacts ?? [] as $c): ?>
                                    <option value="<?= esc($c['id']) ?>" <?= ($customer['cs_person'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted fs-8 fw-semibold mb-1">Contact Person (General)</label>
                            <select name="contact_person" class="form-select form-select-sm shadow-none">
                                <option value="">--Select--</option>
                                <?php foreach($contacts ?? [] as $c): ?>
                                    <option value="<?= esc($c['id']) ?>" <?= ($customer['contact_person'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted fs-8 fw-semibold mb-1">Billing Person</label>
                            <select name="billing_person" class="form-select form-select-sm shadow-none">
                                <option value="">--Select--</option>
                                <?php foreach($contacts ?? [] as $c): ?>
                                    <option value="<?= esc($c['id']) ?>" <?= ($customer['billing_person'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Legacy Generic Contacts -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0">Other Contacts</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="contactsAccordion">
                            <?php foreach ([1, 2, 3] as $index => $p): ?>
                                <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                                    <h2 class="accordion-header" id="heading<?= $p ?>">
                                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?> fw-semibold bg-light text-dark rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $p ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $p ?>">
                                            <i class="fas fa-user-circle text-muted me-2"></i> Contact Person <?= $p ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $p ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $p ?>" data-bs-parent="#contactsAccordion">
                                        <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                            <div class="mb-3">
                                                <label class="form-label text-muted fs-8 fw-semibold mb-1">Select from Master</label>
                                                <!-- We map the name field to a select list that submits the name. This maintains compatibility with old schema for person1_name -->
                                                <select name="person<?= $p ?>_name" class="form-select form-select-sm shadow-none">
                                                    <option value="">--Select--</option>
                                                    <?php foreach($contacts ?? [] as $c): ?>
                                                        <option value="<?= esc($c['name']) ?>" <?= ($customer["person{$p}_name"] ?? '') == $c['name'] ? 'selected' : '' ?>><?= esc($c['name']) ?> (<?= esc($c['phone']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted fs-8 fw-semibold mb-1">Phone</label>
                                                <input type="text" name="person<?= $p ?>_phone" class="form-control form-control-sm shadow-none" value="<?= esc($customer["person{$p}_phone"] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label text-muted fs-8 fw-semibold mb-1">Email</label>
                                                <input type="email" name="person<?= $p ?>_email" class="form-control form-control-sm shadow-none" value="<?= esc($customer["person{$p}_email"] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                            <i class="fas fa-save me-2"></i> Update Customer
                        </button>
                    </div>
                </div>
            </div>
            
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
