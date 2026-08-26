<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4 class="mb-4">🏢 Company Settings: <?= esc($company['name'] ?? 'MA Logistics') ?></h4>

    <form action="<?= base_url('masters/company/update') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-4">
            
            <div class="col-lg-8">
                <!-- Card 1: Basic Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-1"></i> Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Company Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" value="<?= esc($company['name'] ?? '') ?>" required>
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
                                <textarea name="address" class="form-control form-control-sm shadow-none" rows="3"><?= esc($company['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Taxation Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-invoice-dollar me-1"></i> Taxation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">PAN Number</label>
                                <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($company['pan'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">GSTIN</label>
                                <input type="text" name="gstin" class="form-control form-control-sm shadow-none text-uppercase fw-bold" value="<?= esc($company['gstin'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">SAC Code</label>
                                <input type="text" name="sac_code" class="form-control form-control-sm shadow-none" value="<?= esc($company['sac_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">CGST Rate (%)</label>
                                <input type="number" step="0.01" name="cgst_rate" class="form-control form-control-sm shadow-none" value="<?= esc($company['cgst_rate'] ?? 9) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">SGST Rate (%)</label>
                                <input type="number" step="0.01" name="sgst_rate" class="form-control form-control-sm shadow-none" value="<?= esc($company['sgst_rate'] ?? 9) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-7 fw-semibold">IGST Rate (%)</label>
                                <input type="number" step="0.01" name="igst_rate" class="form-control form-control-sm shadow-none" value="<?= esc($company['igst_rate'] ?? 18) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Bank Details Settings -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-university me-1"></i> Bank Details Settings</h6>
                    </div>
                    <div class="card-body">
                        <!-- Legacy field hidden inputs to prevent overwriting during company update -->
                        <input type="hidden" name="bank_name" value="<?= esc($company['bank_name'] ?? '') ?>">
                        <input type="hidden" name="branch_name" value="<?= esc($company['branch_name'] ?? '') ?>">
                        <input type="hidden" name="branch_address" value="<?= esc($company['branch_address'] ?? '') ?>">
                        <input type="hidden" name="ifsc_code" value="<?= esc($company['ifsc_code'] ?? '') ?>">
                        <input type="hidden" name="account_number" value="<?= esc($company['account_number'] ?? '') ?>">
                        <input type="hidden" name="misc_code" value="<?= esc($company['misc_code'] ?? '') ?>">

                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0">
                            <div class="me-3 fs-4">
                                <i class="fas fa-info-circle text-info"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-info">Dynamic Bank Accounts Management</h6>
                                <p class="mb-2 fs-7 text-secondary">
                                    Since your company can have multiple bank accounts, bank details are now managed dynamically under the Bank Accounts Master.
                                </p>
                                <a href="<?= base_url('masters/bank-accounts') ?>" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">
                                    <i class="fas fa-external-link-alt me-1"></i> Go to Bank Accounts Master
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Company Logo & Branding -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-image me-1"></i> Company Logo & Branding</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label text-muted fs-7 fw-semibold">Upload Company Logo <small class="text-muted">(PNG, JPG, WEBP - max 2MB)</small></label>
                                <input type="file" name="logo_image" class="form-control form-control-sm shadow-none" accept="image/png, image/jpeg, image/jpg, image/webp, image/gif">
                                <small class="text-muted d-block mt-1">This logo will be displayed on top-left of all PDF Invoices and Dockets.</small>
                            </div>
                            <div class="col-md-5 text-center">
                                <?php $logo = $company['logo_path'] ?? $company['logo_image'] ?? ''; ?>
                                <?php if (!empty($logo) && file_exists(FCPATH . $logo)): ?>
                                    <div class="p-2 border rounded bg-white d-inline-block shadow-sm">
                                        <img src="<?= base_url(esc($logo)) ?>" alt="Company Logo" style="max-height: 60px; max-width: 160px; object-fit: contain;">
                                    </div>
                                    <div class="mt-2">
                                        <a href="<?= base_url('company/settings/deleteLogo') ?>" class="btn btn-outline-danger btn-xs fw-bold px-2 py-1 fs-8" onclick="return confirm('Are you sure you want to delete the company logo?');">
                                            <i class="fas fa-trash me-1"></i> Delete Logo
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 border rounded bg-light text-muted fs-8 text-center">
                                        <i class="fas fa-image fs-3 mb-1 d-block text-secondary"></i>
                                        No logo uploaded yet
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Invoice PDF Settings -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-signature me-1"></i> Invoice PDF Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="signature_image" class="form-label text-muted fs-7 fw-semibold">Invoice Footer Signature <small class="text-muted">(PNG, JPG, or GIF)</small></label>
                                <input type="file" id="signature_image" name="signature_image" class="form-control form-control-sm shadow-none" accept="image/png, image/jpeg, image/jpg, image/gif">
                                <small class="text-muted d-block mt-1">This signature is printed in the authorized-signatory area of invoice PDFs.</small>
                                <?php $signature = $company['signature_path'] ?? $company['signature_image'] ?? ''; ?>
                                <?php if (!empty($signature) && file_exists(FCPATH . $signature)): ?>
                                    <div class="d-flex align-items-center gap-3 mt-3">
                                        <img src="<?= base_url(esc($signature)) ?>" alt="Current invoice signature" class="border rounded bg-white p-1" style="max-height: 70px; max-width: 180px; object-fit: contain;">
                                        <a href="<?= base_url('company/settings/deleteSignature') ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete the invoice signature?');">
                                            <i class="fas fa-trash me-1"></i> Delete Signature
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-muted fs-8 mt-2">No invoice signature uploaded yet.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted fs-7 fw-semibold">Terms &amp; Conditions <small class="text-muted">(printed on all invoices - supports multi-line numbered points)</small></label>
                                <textarea name="terms_conditions" class="form-control form-control-sm shadow-none" rows="6" placeholder="1. Difference if any may be notified within 7 days of receipt of bills.&#10;2. Subject to Pune Jurisdiction.&#10;3. E & O.E.&#10;4. Draw Cheque in favour of &quot;MA LOGISTICS&quot;"><?= esc($company['terms_conditions'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm bg-light mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                            <i class="fas fa-save me-2"></i> Save Settings
                        </button>
                        <a href="<?= base_url('/logistics') ?>" class="btn btn-outline-secondary w-100 mt-2 fw-bold shadow-sm py-2">
                            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                        </a>
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
