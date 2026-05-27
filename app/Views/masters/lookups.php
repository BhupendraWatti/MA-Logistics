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



    <div class="row">
        <!-- Add form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Add New <?= esc($type_label) ?></div>
                <div class="card-body">
                    <form action="<?= base_url('masters/lookups/' . $type . '/create') ?>" method="POST">
                        <input type="hidden" name="company_id" value="<?= session()->get('selected_company_id') ?>">
                        <?= csrf_field() ?>
                        <?php if ($type === 'origin' || $type === 'destination'): ?>
                            <div class="mb-3">
                                <label class="form-label">City Name *</label>
                                <input type="text" name="city" class="form-control" placeholder="e.g. Pune" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">State *</label>
                                <input type="text" name="state" class="form-control" placeholder="e.g. Maharashtra" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-control" placeholder="e.g. Pune">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">PIN Code</label>
                                <input type="text" name="pincode" class="form-control" placeholder="e.g. 411001">
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Value *</label>
                                <input type="text" name="value" class="form-control"
                                       placeholder="e.g. <?= $type === 'mode' ? 'Air' : 'Enter value' ?>"
                                       required>
                            </div>
                        <?php endif; ?>
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
                            <tr>
                                <th>#</th>
                                <th>Value</th>
                                <?php if ($type === 'origin' || $type === 'destination'): ?>
                                    <th>Pin Code</th>
                                    <th>District</th>
                                <?php endif; ?>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($values as $i => $v): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($v['value']) ?></td>
                                <?php if ($type === 'origin' || $type === 'destination'): ?>
                                    <td><?= esc($v['pincode'] ?? '-') ?></td>
                                    <td><?= esc($v['district'] ?? '-') ?></td>
                                <?php endif; ?>
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
                                <td colspan="<?= ($type === 'origin' || $type === 'destination') ? 5 : 3 ?>" class="text-center text-muted py-4">
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
    ERPUtils.confirmDelete(url, () => location.reload());
}
</script>
<?= $this->endSection() ?>
