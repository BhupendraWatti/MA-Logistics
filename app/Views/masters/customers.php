<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">👥 Customers / Shippers</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            + Add Customer
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>City</th>
                        <th>Payment Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $i => $c): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($c['name']) ?></td>
                        <td><?= esc($c['code'] ?? '-') ?></td>
                        <td><?= esc($c['city'] ?? '-') ?></td>
                        <td><?= esc($c['payment_type'] ?? '-') ?></td>
                        <td>
                            <a href="<?= base_url('masters/customers/edit/' . $c['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <button class="btn btn-sm btn-danger"
                                    onclick="delRecord('/masters/customers/delete/<?= $c['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No customers yet. Click <strong>+ Add Customer</strong> to get started.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="/masters/customers/create" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Add Customer / Shipper</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">PAN</label>
                    <input type="text" name="pan" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Type</label>
                    <input type="text" name="payment_type" class="form-control" placeholder="To Pay / Cash / Credit...">
                </div>
                <div class="col-12">
                    <label class="form-label">Bill To <small class="text-muted">(billing address)</small></label>
                    <textarea name="bill_to" class="form-control" rows="2"
                              placeholder="Office billing address"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Consignee <small class="text-muted">(default delivery address)</small></label>
                    <textarea name="consignee" class="form-control" rows="2"
                              placeholder="Default consignee / delivery address"></textarea>
                </div>
                <div class="col-12"><hr><strong>Contact Persons</strong></div>
                <?php foreach ([1, 2, 3] as $p): ?>
                <div class="col-md-4">
                    <label class="form-label">Person <?= $p ?> Name</label>
                    <input type="text" name="person<?= $p ?>_name" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Person <?= $p ?> Phone</label>
                    <input type="text" name="person<?= $p ?>_phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Person <?= $p ?> Email</label>
                    <input type="email" name="person<?= $p ?>_email" class="form-control">
                </div>
                <?php endforeach; ?>
                <div class="col-12">
                    <label class="form-label">Narration / Notes</label>
                    <textarea name="narration" class="form-control" rows="1"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function delRecord(url) {
    if (!confirm('Delete this customer? This cannot be undone.')) return;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
<?= $this->endSection() ?>
