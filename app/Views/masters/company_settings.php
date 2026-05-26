<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4 class="mb-4">🏢 Company Settings: <?= esc($company['name']) ?></h4>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="/masters/company/update" method="POST" enctype="multipart/form-data">
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
                            <div class="col-md-12 mt-3">
                                <label class="form-label text-muted fs-7 fw-semibold">Digital Signature <small class="text-muted">(PNG with transparent background recommended)</small></label>
                                <input type="file" name="signature" class="form-control form-control-sm shadow-none" accept="image/png,image/jpeg,image/gif">
                                <?php if (!empty($company['signature_path'])): ?>
                                    <div class="mt-2 bg-light p-2 rounded d-inline-block">
                                        <small class="text-muted d-block mb-1">Current signature:</small>
                                        <img src="/<?= esc($company['signature_path']) ?>" style="max-height:60px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-percent me-1"></i> Tax &amp; GST Configuration</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label text-muted fs-7 fw-semibold">GSTIN</label>
                                <input type="text" name="gstin" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($company['gstin'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted fs-7 fw-semibold">PAN</label>
                                <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($company['pan'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted fs-7 fw-semibold">SAC Code</label>
                                <input type="text" name="sac_code" class="form-control form-control-sm shadow-none" value="<?= esc($company['sac_code'] ?? '') ?>">
                            </div>
                            <div class="col-12 mt-3 border-top pt-3">
                                <label class="form-label text-muted fs-7 fw-semibold d-block">Default Tax Rates</label>
                                <small class="text-muted fs-8 d-block mb-2">Set CGST + SGST for intra-state, OR IGST for inter-state.</small>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted fs-8">CGST</span>
                                            <input type="number" step="0.01" min="0" max="50" name="cgst_rate" class="form-control shadow-none tabular-nums" value="<?= esc($company['cgst_rate'] ?? '0.00') ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted fs-8">SGST</span>
                                            <input type="number" step="0.01" min="0" max="50" name="sgst_rate" class="form-control shadow-none tabular-nums" value="<?= esc($company['sgst_rate'] ?? '0.00') ?>">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted fs-8">IGST</span>
                                            <input type="number" step="0.01" min="0" max="50" name="igst_rate" class="form-control shadow-none tabular-nums" value="<?= esc($company['igst_rate'] ?? '0.00') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
