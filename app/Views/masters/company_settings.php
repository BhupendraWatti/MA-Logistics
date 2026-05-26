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

    <form action="/masters/company/update" method="POST" enctype="multipart/form-data" class="card card-body shadow-sm">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Company Name *</label>
                <input type="text" name="name" class="form-control" value="<?= esc($company['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">GSTIN</label>
                <input type="text" name="gstin" class="form-control" value="<?= esc($company['gstin'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">PAN</label>
                <input type="text" name="pan" class="form-control" value="<?= esc($company['pan'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">SAC Code</label>
                <input type="text" name="sac_code" class="form-control" value="<?= esc($company['sac_code'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= esc($company['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="<?= esc($company['mobile'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Address</label>
                <textarea name="address" class="form-control" rows="2"><?= esc($company['address'] ?? '') ?></textarea>
            </div>

            <div class="col-12"><hr><h6 class="text-muted fw-semibold mb-0">GST Configuration</h6>
                <small class="text-muted">Set CGST + SGST for intra-state, OR IGST for inter-state. Do not set both.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">CGST Rate (%)</label>
                <input type="number" step="0.01" min="0" max="50" name="cgst_rate" class="form-control"
                       value="<?= esc($company['cgst_rate'] ?? '0.00') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">SGST Rate (%)</label>
                <input type="number" step="0.01" min="0" max="50" name="sgst_rate" class="form-control"
                       value="<?= esc($company['sgst_rate'] ?? '0.00') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">IGST Rate (%)</label>
                <input type="number" step="0.01" min="0" max="50" name="igst_rate" class="form-control"
                       value="<?= esc($company['igst_rate'] ?? '0.00') ?>">
            </div>

            <div class="col-12"><hr><h6 class="text-muted fw-semibold">Invoice Settings</h6></div>
            <div class="col-12">
                <label class="form-label fw-semibold">Terms &amp; Conditions <small class="text-muted">(printed on all invoices)</small></label>
                <textarea name="terms_conditions" class="form-control" rows="5"><?= esc($company['terms_conditions'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Digital Signature <small class="text-muted">(PNG with transparent background recommended)</small></label>
                <input type="file" name="signature" class="form-control" accept="image/png,image/jpeg,image/gif">
                <?php if (!empty($company['signature_path'])): ?>
                    <div class="mt-2">
                        <small class="text-muted">Current signature:</small><br>
                        <img src="/<?= esc($company['signature_path']) ?>"
                             style="max-height:80px;border:1px solid #ddd;padding:6px;margin-top:4px;border-radius:4px;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary px-4">💾 Save Settings</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
