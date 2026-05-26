<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">✈️ Airlines</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Airline</button>
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
                    <tr><th>#</th><th>Name</th><th>Code</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($airlines as $i => $a): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($a['name']) ?></td>
                        <td><?= esc($a['code'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                    onclick='editRow(<?= json_encode($a) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger"
                                    onclick="delRecord('/masters/airlines/delete/<?= $a['id'] ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($airlines)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No airlines yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="mForm" action="/masters/airlines/create" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 id="mTitle" class="modal-title">Add Airline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Airline Name *</label>
                    <input type="text" name="name" id="fName" class="form-control"
                           placeholder="e.g. IndiGo, Air India" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Code <small class="text-muted">(e.g. 6E, AI)</small></label>
                    <input type="text" name="code" id="fCode" class="form-control" maxlength="10">
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
    document.getElementById('mTitle').innerText = 'Edit Airline';
    document.getElementById('fName').value  = r.name;
    document.getElementById('fCode').value  = r.code || '';
    document.getElementById('mForm').action = '/masters/airlines/update/' + r.id;
    new bootstrap.Modal(document.getElementById('addModal')).show();
}
function delRecord(url) {
    if (!confirm('Delete this airline?')) return;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
document.getElementById('addModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('mForm').reset();
    document.getElementById('mTitle').innerText = 'Add Airline';
    document.getElementById('mForm').action = '/masters/airlines/create';
});
</script>
<?= $this->endSection() ?>
