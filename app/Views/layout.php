<!DOCTYPE html>
<html>
<head>
    <title>Malogistics Management System</title>
    <!-- ANTI-CACHE HEADERS -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- SIMPLE ANTI-CACHE -->
    <script>
        // Force reload on back/forward
        if (performance.navigation.type === 2) {
            window.location.reload(true);
        }
    </script>
    <!-- CUSTOM CSS -->
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= base_url('logistics') ?>">
                <i class="fas fa-truck"></i> Malogistics
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (session()->get('user_id')): ?>
                    <?php if (session()->get('role') === 'admin'): ?>
                        <a class="nav-link top-nav-link" href="<?= base_url('admin') ?>"><i class="fas fa-users-cog"></i> Admin</a>
                    <?php endif; ?>
                    <!-- <a class="nav-link top-nav-link" href="#"><i class="fas fa-search"></i> Search</a> -->
                    <?php if ((session()->get('permissions')['can_create'] ?? 0) == 1): ?>
                        <a class="nav-link top-nav-link" href="<?= base_url('logistics/create') ?>"><i class="fas fa-plus"></i> New Booking</a>
                    <?php endif; ?>
                    <span class="navbar-text me-3 top-nav-link">
                        <i class="fas fa-user"></i> <?= session()->get('username') ?> 
                        (<?= ucfirst(session()->get('role')) ?>)
                    </span>
                    <a class="nav-link top-nav-link" href="<?= base_url('auth/logout') ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

<!-- <?php //if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?//= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php //endif; ?>

<?php //if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?//= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php //endif; ?>

<?php //if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?//= session()->getFlashdata('info') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php //endif; ?> -->

<?php $success = session()->getFlashdata('success'); ?>
<?php $error = session()->getFlashdata('error'); ?>
<?php $info = session()->getFlashdata('info'); ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= esc($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($info): ?>
    <div class="alert alert-info alert-dismissible fade show">
        <?= esc($info) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>


        <?= $this->renderSection('content') ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>