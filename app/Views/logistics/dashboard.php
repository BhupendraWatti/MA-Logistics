<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <style>
        .table-responsive table th, .table-responsive table td {
            white-space: nowrap;
        }
        .table-responsive table .consignee-cell {
            white-space: normal !important;
            word-break: break-word;
            min-width: 250px !important;
            width: 250px !important;
        }
    </style>
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
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0 flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-dark">Recent Bookings</h6>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="search" id="recentBookingSearch" class="form-control form-control-sm shadow-none" placeholder="Search records..." style="width: min(220px, 70vw);">
                <a href="<?= base_url('logistics/manage') ?>" class="text-decoration-none fw-medium text-primary" style="font-size:0.9rem;">View All</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border-top">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>AWB Number</th>
                            <th>Docket No</th>
                            <th>Origin &rarr; Dest</th>
                            <th>Customer</th>
                            <th class="consignee-cell">Consignee</th>
                            <th>Created</th>
                            <th>Created By</th>
                            <th>Pieces</th>
                            <th>Weight</th>
                            <th class="pe-4">Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($recent_bookings) && !empty($recent_bookings)): ?>
                        <?php $i = 1; foreach($recent_bookings as $booking): ?>
                        <tr class="recent-booking-row">
                            <td class="ps-4 text-muted"><?= $i++ ?></td>
                            <td><a href="<?= base_url('logistics/view/' . $booking['id']) ?>" class="fw-medium text-decoration-none"><?= esc($booking['awb_no']) ?></a></td>
                            <td>
                                <?php
                                    $docketStr = $booking['docket_no'] ?? '-';
                                    $docketsArray = array_filter(array_map('trim', explode(',', $docketStr)));
                                    if (count($docketsArray) > 1) {
                                        $docketDisplay = esc($docketsArray[0]) . '...';
                                        $docketTitle = esc($docketStr);
                                    } else {
                                        $docketDisplay = esc($docketStr);
                                        $docketTitle = '';
                                    }
                                ?>
                                <span class="text-muted fw-semibold" style="font-size: 0.85rem;" title="<?= $docketTitle ?>"><?= $docketDisplay ?></span>
                            </td>
                            <td><span class="fw-medium text-dark"><?= esc($booking['origin']) ?></span> <span class="text-muted mx-1">&rarr;</span> <span class="fw-medium text-dark"><?= esc($booking['destination']) ?></span></td>
                            <td>
                                <?= esc($booking['customer_name'] ?? 'Unknown') ?>
                            </td>
                            <td class="consignee-cell">
                                <span class="text-muted" style="font-size: 0.85rem;"><?= esc($booking['consignee'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= !empty($booking['created_at']) ? esc(date('d-M-Y H:i', strtotime($booking['created_at']))) : '-' ?></span>
                            </td>
                            <td><?= esc($booking['created_by_name'] ?? 'Unknown') ?></td>
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
                            <td colspan="12" class="text-center py-5 text-muted">No recent bookings found.</td>
                        </tr>
                        <?php endif; ?>
                        <tr id="recentBookingNoMatches" class="d-none">
                            <td colspan="12" class="text-center py-4 text-muted">No matching recent bookings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->include('logistics/pod_tracking_drawer') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).on('input', '#recentBookingSearch', function() {
    const needle = ($(this).val() || '').toLowerCase().trim();
    let visibleRows = 0;
    $('.recent-booking-row').each(function() {
        const matches = $(this).text().toLowerCase().includes(needle);
        $(this).toggle(matches);
        if (matches) visibleRows++;
    });
    $('#recentBookingNoMatches').toggleClass('d-none', visibleRows > 0);
});
</script>
<?= $this->endSection() ?>
