<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4 class="mb-4">🏢 Company Settings: <?= esc($company['name']) ?></h4>



    <form action="<?= base_url('masters/company/update') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-4">
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-1"></i> Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" value="<?= esc($company['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm shadow-none" value="<?= esc($company['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-7 fw-semibold">Mobile</label>
                                <input type="text" name="mobile" class="form-control form-control-sm shadow-none" value="<?= esc($company['mobile'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Address</label>
                                <textarea name="address" class="form-control form-control-sm shadow-none" rows="2"><?= esc($company['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-invoice me-1"></i> Invoice Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Terms &amp; Conditions <small class="text-muted">(printed on all invoices)</small></label>
                                <textarea name="terms_conditions" class="form-control form-control-sm shadow-none" rows="5"><?= esc($company['terms_conditions'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                            <i class="fas fa-save me-2"></i> Save Settings
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
</style>
<?= $this->endSection() ?>

