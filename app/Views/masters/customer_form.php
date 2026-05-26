<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h4>Edit Customer: <?= esc($customer['name']) ?></h4>

    <form action="/masters/customers/update/<?= $customer['id'] ?>" method="POST" class="card card-body shadow-sm mt-3">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="<?= esc($customer['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="<?= esc($customer['code'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= esc($customer['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">PAN</label>
                <input type="text" name="pan" class="form-control" value="<?= esc($customer['pan'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pincode</label>
                <input type="text" name="pincode" class="form-control" value="<?= esc($customer['pincode'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= esc($customer['city'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Payment Type</label>
                <input type="text" name="payment_type" class="form-control" value="<?= esc($customer['payment_type'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Bill To (billing address)</label>
                <textarea name="bill_to" class="form-control" rows="2"><?= esc($customer['bill_to'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Consignee (default delivery address)</label>
                <textarea name="consignee" class="form-control" rows="2"><?= esc($customer['consignee'] ?? '') ?></textarea>
            </div>

            <div class="col-12"><hr><strong>Contact Persons</strong></div>
            <?php foreach ([1, 2, 3] as $p): ?>
            <div class="col-md-4">
                <label class="form-label">Person <?= $p ?> Name</label>
                <input type="text" name="person<?= $p ?>_name" class="form-control"
                       value="<?= esc($customer["person{$p}_name"] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Person <?= $p ?> Phone</label>
                <input type="text" name="person<?= $p ?>_phone" class="form-control"
                       value="<?= esc($customer["person{$p}_phone"] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Person <?= $p ?> Email</label>
                <input type="email" name="person<?= $p ?>_email" class="form-control"
                       value="<?= esc($customer["person{$p}_email"] ?? '') ?>">
            </div>
            <?php endforeach; ?>

            <div class="col-12">
                <label class="form-label">Narration / Notes</label>
                <textarea name="narration" class="form-control" rows="1"><?= esc($customer['narration'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Update Customer</button>
            <a href="<?= base_url('masters/customers') ?>" class="btn btn-secondary ms-2">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
