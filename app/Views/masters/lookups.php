<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4 class="mb-3">⚙️ Lookup Values: <span class="text-primary"><?= esc($type_label) ?></span></h4>

    <!-- Type tabs -->
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <?php foreach ($all_types as $key => $label): ?>
            <a href="<?= base_url('masters/lookups/' . $key) ?>"
               class="btn btn-sm <?= $key === $type ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Add New <?= esc($type_label) ?></div>
                <div class="card-body">
                    <form action="<?= base_url('masters/lookups/' . $type . '/create') ?>" method="POST">
                        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Value *</label>
                            <input type="text" name="value" class="form-control"
                                   placeholder="e.g. <?= $type === 'origin' ? 'Pune' : ($type === 'mode' ? 'Air' : 'Enter value') ?>"
                                   required>
                        </div>
                        <button class="btn btn-primary w-100">+ Add</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Values list -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr><th>#</th><th>Value</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($values as $i => $v): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($v['value']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger"
                                            onclick="delRecord('<?= base_url('masters/lookups/delete/' . $v['id']) ?>')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($values)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No <?= strtolower($type_label) ?> values yet. Add one using the form.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function delRecord(url) {
    if (!confirm('Delete this value? Existing bookings with this value will keep their text.')) return;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}
</script>
<?= $this->endSection() ?>
