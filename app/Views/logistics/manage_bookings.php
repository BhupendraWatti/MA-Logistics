<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
             Manage <?= esc($company_name ?? '') ?> Bookings
        </h2>
        <div>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary me-2">
                Dashboard
            </a>
            <?php if (($permissions['can_create'] ?? 0) == 1): ?>
            <a href="<?= base_url('logistics/create') ?>" class="btn btn-success">
                New Booking
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="alert alert-info text-center">
        <h5>No bookings found</h5>
        <p class="mb-0">Create your first booking to get started!</p>
        <?php if (($permissions['can_create'] ?? 0) == 1): ?>
        <a href="<?= base_url('logistics/create') ?>" class="btn btn-primary mt-3">Create First Booking</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <!-- Add Export button -->
<button type="button" class="btn btn-success mb-3" onclick="exportSelected()">
    Export Excel
</button>
        <table class="table table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th width="5%"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
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
                <?php foreach($bookings as $booking): 
                    $shipmentModel = new \App\Models\ShipmentItemModel();
                    $totalWeight = $shipmentModel->selectSum('chargeable_weight')
                                               ->where('booking_id', $booking['id'])
                                               ->first()['chargeable_weight'] ?? 0;
                    $salesModel = new \App\Models\SalesChargeModel();
                    $totalAmount = $salesModel->select('(rate * weight) + ddc + ssc + btc + flc + doc + inbound_tsp + outbound_tsp + tcp + utility_charges + xray_charges + ado + awb_fees_agent + awb_fees_carrier + admin_charges + delivery_order_charges + inbound_handling + inbound_storage + outbound_storage + misc_charges AS total_amount', false)->where('booking_id', $booking['id'])->first()['total_amount'] ?? 0;
                ?>
                <tr>
                    <td><input type="checkbox" class="booking-check" value="<?= $booking['id'] ?>"></td>
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
                        <button class="btn btn-sm btn-outline-danger delete-btn" 
                                data-id="<?= $booking['id'] ?>" data-awb="<?= esc($booking['awb_no']) ?>">
                            Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<!-- JavaScript -->
<script>
$('.delete-btn').click(function() {
    if (confirm('Delete AWB ' + $(this).data('awb') + '?')) {
        const id = $(this).data('id');
        $.post('<?= base_url('logistics/delete/') ?>' + id, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Delete failed'));
            }
        });
    }
});

function toggleAll() {
    const master = document.getElementById('selectAll');
    document.querySelectorAll('.booking-check').forEach(cb => cb.checked = master.checked);
}

function exportSelected() {
    const checked = document.querySelectorAll('.booking-check:checked');
    if (checked.length === 0) {
        alert('Please select at least one booking!');
        return;
    }
    const ids = Array.from(checked).map(cb => cb.value).join(',');
    window.location.href = '<?= base_url('logistics/exportExcel') ?>?ids=' + ids;
}
</script>
<?= $this->endSection() ?>