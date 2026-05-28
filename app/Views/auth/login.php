<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-center bg-white border-bottom-0 pt-4 pb-2">
                <img src="<?= base_url('images/logo.png') ?>" alt="MA Logistic" class="img-fluid mb-3" style="max-height: 80px; width: auto;">
                <h4 class="text-secondary mb-0"><i class="fas fa-sign-in-alt text-primary me-2"></i> Login</h4>
            </div>
            <div class="card-body">
                <?= form_open('/auth/attemptLogin') ?>
                <div class="mb-3">
                    <label>Username or Email</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>