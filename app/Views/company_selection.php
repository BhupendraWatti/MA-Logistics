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
                
                <?php 
                $permissions = session()->get('permissions') ?? [];
                $role = session()->get('role');
                if ($role === 'admin' || !empty($permissions['can_create']) || !empty($permissions['can_delete'])): 
                ?>
                <hr class="my-4">
                
                <h5 class="text-center mb-3">⚙️ Manage Companies</h5>
                
                <?php if ($role === 'admin' || !empty($permissions['can_create'])): ?>
                <?= form_open('logistics/createCompany', ['class' => 'mb-3 d-flex']) ?>
                    <input type="text" name="name" class="form-control me-2" placeholder="New Company Name" required>
                    <button type="submit" class="btn btn-success text-nowrap">
                        <i class="fas fa-plus"></i> Add
                    </button>
                <?= form_close() ?>
                <?php endif; ?>

                <?php if ($role === 'admin' || !empty($permissions['can_delete'])): ?>
                <div class="list-group">
                    <?php foreach($companies ?? [] as $company): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><?= esc($company['name']) ?></span>
                            <?= form_open('logistics/deleteCompany/' . $company['id'], ['class' => 'm-0', 'onsubmit' => 'return confirm("Are you sure? This will delete ALL bookings related to this company!");']) ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Company">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?= form_close() ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

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