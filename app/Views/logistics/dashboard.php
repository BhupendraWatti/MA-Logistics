<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-dark fw-bold">Dashboard</h4>
            <p class="text-muted mb-0">Welcome to MA Logistics System</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('masters/customers') ?>" class="btn btn-outline-secondary bg-white"><i class="fas fa-user-plus me-2"></i> Add Customer</a>
            <a href="<?= base_url('logistics/manage') ?>" class="btn btn-outline-secondary bg-white"><i class="fas fa-crosshairs me-2"></i> Track Shipment</a>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Booking</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Bookings Table -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0">
            <h6 class="mb-0 fw-bold text-dark">Recent Bookings</h6>
            <a href="<?= base_url('logistics/manage') ?>" class="text-decoration-none fw-medium text-primary" style="font-size:0.9rem;">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border-top">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>AWB Number</th>
                            <th>Origin &rarr; Dest</th>
                            <th>Customer</th>
                            <th>Pieces</th>
                            <th>Weight</th>
                            <th class="pe-4">Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($recent_bookings) && !empty($recent_bookings)): ?>
                        <?php $i = 1; foreach($recent_bookings as $booking): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?= $i++ ?></td>
                            <td><a href="<?= base_url('logistics/view/' . $booking['id']) ?>" class="fw-medium text-decoration-none"><?= esc($booking['awb_no']) ?></a></td>
                            <td><span class="fw-medium text-dark"><?= esc($booking['origin']) ?></span> <span class="text-muted mx-1">&rarr;</span> <span class="fw-medium text-dark"><?= esc($booking['destination']) ?></span></td>
                            <td>
                                <?= esc($booking['customer_name'] ?? 'Unknown') ?>
                            </td>
                            <td><span class="text-dark fw-medium"><?= $booking['total_pieces'] ?></span></td>
                            <td>
                                <?= number_format((float)($booking['total_weight'] ?? 0), 1) ?> kg
                            </td>
                            <td class="pe-4">
                                <?php 
                                    $bg = 'bg-secondary';
                                    if ($booking['status'] == 'Delivered') { $bg = 'bg-success'; }
                                    elseif ($booking['status'] == 'In-Transit') { $bg = 'bg-warning text-dark'; }
                                ?>
                                <span class="badge <?= $bg ?> text-white" style="font-size:0.75rem; letter-spacing:0.5px;">
                                    <?= esc($booking['status']) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-circle" title="Update Tracking / POD" onclick="openTrackingDrawer('<?= $booking['id'] ?>', '<?= esc($booking['awb_no']) ?>', '<?= esc(addslashes($booking['customer_name'] ?? '')) ?>')">
                                    <i class="fa-solid fa-location-dot"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No recent bookings found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->include('logistics/pod_tracking_drawer') ?>

<?= $this->endSection() ?>