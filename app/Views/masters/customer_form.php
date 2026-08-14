<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark fw-bold"><i class="fas fa-building text-primary me-2"></i> Edit Customer: <?= esc($customer['name']) ?></h4>
        <a href="<?= base_url('masters/customers') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form action="<?= base_url('masters/customers/update/' . $customer['id']) ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
        
        <?= view('masters/_customer_form_fields', [
            'customer' => $customer,
            'lookups' => $lookups,
            'customer_rates' => $customer_rates ?? [],
            'customer_rate_history' => $customer_rate_history ?? [],
        ]) ?>
        
        <div class="row mt-4">
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary fw-bold px-5 shadow-sm">
                    <i class="fas fa-save me-2"></i> Update Customer
                </button>
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
