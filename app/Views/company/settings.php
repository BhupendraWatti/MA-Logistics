<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Company Settings: <?= esc($company_name ?? 'MA Logistics') ?></h2>
        <a href="<?= base_url('/logistics') ?>" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm border-dark">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">⚙️ PDF & Taxation Settings</h5>
        </div>
        <div class="card-body">
            <form action="<?= base_url('company/settings/update') ?>" method="POST" enctype="multipart/form-data">
                
                <h5 class="mb-3">General Information</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="4"><?= esc($company['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control mb-2" value="<?= esc($company['email'] ?? '') ?>">
                        
                        <label>Mobile</label>
                        <input type="text" name="mobile" class="form-control" value="<?= esc($company['mobile'] ?? '') ?>">
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Taxation Details</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>PAN Number</label>
                        <input type="text" name="pan" class="form-control" value="<?= esc($company['pan'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>GSTIN</label>
                        <input type="text" name="gstin" class="form-control" value="<?= esc($company['gstin'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>SAC Code</label>
                        <input type="text" name="sac_code" class="form-control" value="<?= esc($company['sac_code'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>CGST Rate (%)</label>
                        <input type="number" step="0.01" name="cgst_rate" class="form-control" value="<?= esc($company['cgst_rate'] ?? 9) ?>">
                    </div>
                    <div class="col-md-4">
                        <label>SGST Rate (%)</label>
                        <input type="number" step="0.01" name="sgst_rate" class="form-control" value="<?= esc($company['sgst_rate'] ?? 9) ?>">
                    </div>
                    <div class="col-md-4">
                        <label>IGST Rate (%)</label>
                        <input type="number" step="0.01" name="igst_rate" class="form-control" value="<?= esc($company['igst_rate'] ?? 18) ?>">
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Invoice PDF Settings</h5>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Terms & Conditions (Printed on PDF)</label>
                        <textarea name="terms_conditions" class="form-control" rows="6"><?= esc($company['terms_conditions'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Digital Signature (Upload PNG)</label>
                        <input type="file" name="signature_image" class="form-control" accept="image/png">
                        <?php if(!empty($company['signature_path']) && file_exists(FCPATH . $company['signature_path'])): ?>
                            <div class="mt-3 p-3 bg-light border rounded text-center">
                                <strong>Current Signature:</strong><br>
                                <img src="<?= base_url($company['signature_path']) ?>" style="max-height: 80px; max-width: 200px; margin-top:10px; mix-blend-mode: multiply;">
                                <div class="mt-3">
                                    <a href="<?= base_url('company/settings/deleteSignature') ?>" class="btn btn-sm btn-danger shadow-sm" onclick="return confirm('Are you sure you want to delete the current signature?');">
                                        <i class="fas fa-trash"></i> Delete Signature
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label>Or Draw Your Signature</label>
                        <div class="border rounded bg-light p-2 text-center">
                            <canvas id="signatureCanvas" class="bg-white border shadow-sm rounded" width="300" height="150" style="touch-action: none; cursor: crosshair;"></canvas>
                            <div class="mt-2">
                                <button type="button" id="clearCanvas" class="btn btn-sm btn-outline-warning text-dark fw-bold shadow-sm">
                                    <i class="fas fa-eraser"></i> Clear Drawing
                                </button>
                            </div>
                            <input type="hidden" name="signature_base64" id="signatureBase64">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3 px-4">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var canvas = document.getElementById('signatureCanvas');
        if (canvas) {
            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)'
            });

            document.getElementById('clearCanvas').addEventListener('click', function () {
                signaturePad.clear();
                document.getElementById('signatureBase64').value = "";
            });

            document.querySelector('form').addEventListener('submit', function(e) {
                if (!signaturePad.isEmpty()) {
                    document.getElementById('signatureBase64').value = signaturePad.toDataURL('image/png');
                }
            });
        }
    });
</script>
