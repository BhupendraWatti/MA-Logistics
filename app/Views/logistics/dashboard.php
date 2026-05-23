<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="jumbotron bg-primary text-white p-5 rounded">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 mb-2">Welcome to Malogistics! ️</h1>
                    <p class="lead mb-0">
                        <?php if (isset($company_name)): ?>
                            <strong><?= esc($company_name) ?></strong> Dashboard
                        <?php else: ?>
                            Advanced Logistics Management System
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle px-4" type="button" data-bs-toggle="dropdown">
                            <?= esc($user['username'] ?? session()->get('username')) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><?= ucfirst($user['role'] ?? 'Guest') ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (isset($company_name)): ?>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('logistics/clearCompany') ?>">
                                    Switch Company
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= base_url('auth/logout') ?>">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            <p>
                <strong> Permissions:</strong> 
                <?= ($permissions['can_create'] ?? 0) ? '<span class="badge bg-success ms-2">Create</span>' : '' ?>
                <?= ($permissions['can_edit'] ?? 0) ? '<span class="badge bg-info ms-2">️Edit</span>' : '' ?>
                <?= ($permissions['can_delete'] ?? 0) ? '<span class="badge bg-warning ms-2">️Delete</span>' : '<span class="badge bg-secondary ms-2">View Only</span>' ?>
            </p>
            
            <!-- <?php //if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?//= session()->getFlashdata('success') ?></div>
            <?php //endif; ?>
            
            <?php //if (session()->getFlashdata('info')): ?>
            <div class="alert alert-info"><?//= session()->getFlashdata('info') ?></div>
            <?php //endif; ?> -->
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- <div class="col-md-3 mb-4">
        <div class="card text-center h-100 shadow-sm border-primary">
            <div class="card-body">
                <h5> Quick Search</h5>
                <p class="text-muted small mb-3">AWB or Docket Number</p>
                <a href="<?//= base_url('logistics/search') ?>" class="btn btn-outline-primary w-100">
                    Search
                </a>
            </div>
        </div>
    </div> -->
    
    <?php if (($permissions['can_create'] ?? 0) == 1): ?>
    <div class="col-md-4 mb-4">
        <div class="card text-center h-100 shadow-sm border-success">
            <div class="card-body">
                <h5> New Booking</h5>
                <p class="text-muted small mb-3">Two-Tab AWB Form</p>
                <a href="<?= base_url('logistics/create') ?>" class="btn btn-success w-100">
                    Create AWB
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (($permissions['can_edit'] ?? 0) == 1): ?>
    <!-- <div class="col-md-3 mb-4">
        <div class="card text-center h-100 shadow-sm border-info">
            <div class="card-body">
                <h5> Manage Bookings</h5>
                <p class="text-muted small mb-3">View/Edit AWBs</p>
                <a href="<?//= base_url('logistics/search') ?>" class="btn btn-info text-white w-100">
                    View All
                </a>
            </div>
        </div>
    </div> -->

<div class="col-md-4 mb-4">
    <div class="card text-center h-100 shadow-sm border-info">
        <div class="card-body">
            <h5> Manage Bookings</h5>
            <p class="text-muted small mb-3"><?= isset($stats['total_bookings']) ? $stats['total_bookings'] : 0 ?> AWBs</p>
            <a href="<?= base_url('logistics/manage') ?>" class="btn btn-info text-white w-100">
                View All Bookings
            </a>
        </div>
    </div>
</div>

    <?php endif; ?>
    
    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
    <div class="col-md-4 mb-4">
        <div class="card text-center h-100 shadow-sm border-warning">
            <div class="card-body">
                <h5>️ Admin Panel</h5>
                <p class="text-muted small mb-3">User Management</p>
                <a href="<?= base_url('admin') ?>" class="btn btn-warning w-100">
                    Admin
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- STATS SECTION (Safe - checks if exists) -->
<?php if (isset($stats)): ?>
<div class="row mt-5">
    <div class="col-md-12">
        <h4 class="mb-3">
             <?= esc($company_name ?? 'Company') ?> Stats
        </h4>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body text-center">
                        <h3 class="text-primary"><?= $stats['total_bookings'] ?? 0 ?></h3>
                        <p class="mb-0">Total AWBs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body text-center">
                        <h3 class="text-success"><?= $stats['total_shipments'] ?? 0 ?></h3>
                        <p class="mb-0">Shipments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body text-center">
                        <h3 class="text-info"><?= $stats['total_charges'] ?? 0 ?></h3>
                        <p class="mb-0">Sales Charges</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body text-center">
                        <span class="badge bg-warning fs-5"><?= $stats['status'] ?? 'Draft' ?></span>
                        <p class="mb-0 mt-2">Status</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!--  NEW: Full Company Bookings Table -->
<?php if (isset($all_bookings) && !empty($all_bookings)): ?>
<div class="row mt-5">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>
                 All <?= esc($company_name ?? '') ?> Bookings 
                <span class="badge bg-light text-dark"><?= count($all_bookings) ?> found</span>
            </h4>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-success">
                New Booking
            </a>
            <?php endif; ?>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>AWB No.</th>
                        <th>Date</th>
                        <th>Origin → Dest</th>
                        <th>Status</th>
                        <th>Pieces</th>
                        <th>Total Wt</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_bookings as $booking): 
                        $shipmentModel = new \App\Models\ShipmentItemModel();
                        $totalWeight = $shipmentModel->selectSum('chargeable_weight')
                                                   ->where('booking_id', $booking['id'])
                                                   ->first()['chargeable_weight'] ?? 0;
                        $salesModel = new \App\Models\SalesChargeModel();
                        $totalAmount = $salesModel->select('(rate * weight) + ddc + ssc + btc + flc + doc + inbound_tsp + outbound_tsp + tcp + utility_charges + xray_charges + ado + awb_fees_agent + awb_fees_carrier + admin_charges + delivery_order_charges + inbound_handling + inbound_storage + outbound_storage + misc_charges AS total_amount', false)->where('booking_id', $booking['id'])->first()['total_amount'] ?? 0;
                    ?>
                    <tr>
                        <td><strong><?= esc($booking['awb_no']) ?></strong></td>
                        <td><?= date('d-M-Y H:i', strtotime($booking['booking_date'])) ?></td>
                        <td><?= esc($booking['origin']) ?> → <?= esc($booking['destination']) ?></td>
                        <td>
                            <span class="badge <?= $booking['status'] == 'Delivered' ? 'bg-success' : 
                                ($booking['status'] == 'In-Transit' ? 'bg-warning' : 'bg-secondary') ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td><?= $booking['total_pieces'] ?></td>
                        <td><?= number_format($totalWeight, 1) ?>kg</td>
                        <td><strong class="text-success">₹<?= number_format($totalAmount, 0) ?></strong></td>
                        <td>
                            <a href="<?= base_url('logistics/view/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary me-1">
                                View</a>
                            <a href="<?= base_url('logistics/edit/' . $booking['id']) ?>" class="btn btn-sm btn-outline-warning me-1">
                                Edit</a>
                            <?php if (($permissions['can_delete'] ?? 0) == 1): ?>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $booking['id'] ?>" data-awb="<?= esc($booking['awb_no']) ?>">
                                Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Search Card (Keep this) -->
<!-- <div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-body text-center p-4">
                <h5> Quick Search</h5>
                <p class="text-muted">AWB or Docket Number</p>
                <a href="<?//= base_url('logistics/search') ?>" class="btn btn-outline-info btn-lg">
                    Advanced Search
                </a>
            </div>
        </div>
    </div>
</div> -->

<!-- Recent Bookings (Safe) -->
<?php //if (isset($recent_bookings) && !empty($recent_bookings)): ?>
<!-- <div class="row mt-4">
    <div class="col-md-12">
        <h5 class="mb-3"> Recent Bookings</h5>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>AWB</th>
                        <th>Date</th>
                        <th>Origin → Dest</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php //foreach($recent_bookings as $booking): ?>
                    <tr>
                        <td><strong><?//= esc($booking['awb_no']) ?></strong></td>
                        <td><?//= date('d-M-Y', strtotime($booking['booking_date'])) ?></td>
                        <td><?//= esc($booking['origin']) ?> → <?//= esc($booking['destination']) ?></td>
                        <td>
                            <span class="badge <?//= $booking['status'] == 'Delivered' ? 'bg-success' : 'bg-warning' ?>">
                                <?//= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?//= base_url('logistics/view/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary">
                                </a>
                        </td>
                    </tr>
                    <?php //endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> -->
<?php //endif; ?>
<?= $this->endSection() ?>