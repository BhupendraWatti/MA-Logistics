<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-gradient text-black text-center py-4">
                <i class="fas fa-building fa-3x mb-3"></i>
                <h3>🏢 Select Your Company</h3>
                <p class="mb-0 opacity-75">Choose company to access Malogistics Dashboard</p>
            </div>
            <div class="card-body p-5">
                <?= form_open('logistics/setCompany') ?>
                <div class="mb-4">
                    <label class="form-label fw-bold fs-5 mb-3 d-block text-center">
                        📋 Available Companies
                    </label>
                    <select name="company_id" class="form-select form-select-lg" required>
                        <option value="">🔍 Select Company First...</option>
                        <?php foreach($companies ?? [] as $company): ?>
                            <option value="<?= $company['id'] ?>">
                                <?= esc($company['name']) ?> (ID: <?= $company['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">
                    <i class="fas fa-rocket me-2"></i>Enter Dashboard
                </button>
                <?= form_close() ?>
                
                <div class="text-center mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        👤 Welcome, <strong><?= esc(session()->get('username')) ?></strong> | 
                        <?= ucfirst(session()->get('role')) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>