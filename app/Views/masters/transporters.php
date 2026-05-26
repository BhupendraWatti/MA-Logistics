<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">🚛 Transporters</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Transporter</button>
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
                    <tr><th>#</th><th>Name</th><th>Mobile</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transporters as $i => $t): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($t['name']) ?></td>
                        <td><?= esc($t['mobile'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                    onclick='editRow(<?= json_encode($t) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger"
                                    onclick="delRecord('/masters/transporters/delete/<?= $t['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transporters)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No transporters yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="mForm" action="/masters/transporters/create" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 id="mTitle" class="modal-title">Add Transporter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Transporter Name *</label>
                    <input type="text" name="name" id="fName" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mobile No.</label>
                    <input type="text" name="mobile" id="fMobile" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRow(r) {
    document.getElementById('mTitle').innerText = 'Edit Transporter';
    document.getElementById('fName').value   = r.name;
    document.getElementById('fMobile').value = r.mobile || '';
    document.getElementById('mForm').action  = '/masters/transporters/update/' + r.id;
    new bootstrap.Modal(document.getElementById('addModal')).show();
}
function delRecord(url) {
    if (!confirm('Delete this transporter?')) return;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
// Reset modal on close
document.getElementById('addModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('mForm').reset();
    document.getElementById('mTitle').innerText = 'Add Transporter';
    document.getElementById('mForm').action = '/masters/transporters/create';
});
</script>
<?= $this->endSection() ?>
