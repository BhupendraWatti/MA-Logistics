<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4 class="mb-4">🏢 Company Settings: <?= esc($company['name']) ?></h4>



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
                            <div class="col-md-6 mt-3">
                                <label class="form-label text-muted fs-7 fw-semibold">Digital Signature <small class="text-muted">(Upload Image)</small></label>
                                <input type="file" name="signature" class="form-control form-control-sm shadow-none" accept="image/png,image/jpeg,image/jpg,image/gif">
                                <?php if (!empty($company['signature_path'])): ?>
                                    <div class="mt-2 bg-light p-2 rounded d-inline-block position-relative w-100">
                                        <small class="text-muted d-block mb-1">Current signature:</small>
                                        <img src="/<?= esc($company['signature_path']) ?>" style="max-height:60px; mix-blend-mode: multiply;">
                                        <div class="mt-2">
                                            <a href="/masters/company/deleteSignature" class="btn btn-sm btn-danger shadow-sm" onclick="return confirm('Are you sure you want to delete the current signature?');">
                                                <i class="fas fa-trash"></i> Delete Signature
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label text-muted fs-7 fw-semibold">Or Draw Your Signature</label>
                                <div class="border rounded bg-light p-2 text-center" style="display: flex; flex-direction: column; height: 100%;">
                                    <div style="flex-grow: 1; border: 1px solid #ccc; background-color: #fff; border-radius: 4px; overflow: hidden;">
                                        <canvas id="signatureCanvas" style="width: 100%; height: 150px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <button type="button" id="clearCanvas" class="btn btn-sm btn-outline-warning text-dark fw-bold shadow-sm">
                                            <i class="fas fa-eraser"></i> Clear Drawing
                                        </button>
                                    </div>
                                    <input type="hidden" name="signature_base64" id="signatureBase64">
                                </div>
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

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var canvas = document.getElementById('signatureCanvas');
        if (canvas && typeof SignaturePad !== 'undefined') {
            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)', // Solid white prevents TCPDF alpha channel errors
                penColor: 'rgb(0, 0, 0)'
            });

            function resizeCanvas() {
                var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
            }

            window.addEventListener("resize", resizeCanvas);
            resizeCanvas();

            document.getElementById('clearCanvas').addEventListener('click', function () {
                signaturePad.clear();
                document.getElementById('signatureBase64').value = "";
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                if (!signaturePad.isEmpty()) {
                    document.getElementById('signatureBase64').value = signaturePad.toDataURL('image/jpeg');
                }
            });
        } else {
            console.error("SignaturePad library failed to load.");
        }
    });
</script>
<?= $this->endSection() ?>
