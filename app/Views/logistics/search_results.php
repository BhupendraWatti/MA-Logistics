<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Search Results</h2>
        <a href="<?= base_url('logistics/search') ?>" class="btn btn-secondary">
            New Search
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            No results found for your search.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>AWB No.</th>
                        <th>Company</th>
                        <th>Date</th>
                        <th>Origin → Dest</th>
                        <th>Status</th>
                        <th>Total Pieces</th>
                        <th>Total Chargeable Wt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr>
                            <td><strong><?= esc($booking['awb_no']) ?></strong></td>
                            <td><?= esc($booking['company_name']) ?></td>
                            <td><?= date('d-M-Y H:i', strtotime($booking['booking_date'])) ?></td>
                            <td>
                                <small class="text-muted"><?= esc($booking['origin']) ?> → <?= esc($booking['destination']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $booking['status'] == 'Delivered' ? 'bg-success' : 
                                ($booking['status'] == 'In-Transit' ? 'bg-warning' : 'bg-secondary') ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td><?= $booking['total_pieces'] ?></td>
                        <td><?= number_format((float)$booking['total_chargeable_weight'], 2) ?> KG</td>
                        <td>
                            <?php if ((session()->get('permissions')['can_edit'] ?? 0) == 1): ?>
                            <a href="<?= base_url('logistics/view/' . $booking['id']) ?>" class="btn btn-sm btn-outline-primary me-1" title="View">
                                View</a>
                            <a href="<?= base_url('logistics/edit/' . $booking['id']) ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                Edit</a>
                            <?php endif; ?>
                            <?php if ((session()->get('permissions')['can_delete'] ?? 0) == 1): ?>
                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $booking['id'] ?>" data-awb="<?= esc($booking['awb_no']) ?>" title="Delete">
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
<script>
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        const awb = $(this).data('awb');
        ERPUtils.confirmAction('Delete AWB ' + awb + '?', 'This cannot be undone!', 'Yes, delete', 'error')
            .then((result) => {
                if (result.isConfirmed) {
                    $.post('<?= base_url('logistics/delete/') ?>' + id, function(response) {
                        if (response.success) {
                            ERPUtils.showSuccess('Deleted', response.message).then(() => location.reload());
                        } else {
                            ERPUtils.showError('Error', response.message);
                        }
                    });
                }
            });
    });
</script>
<?= $this->endSection() ?>