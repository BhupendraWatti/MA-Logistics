<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3> Preliminary Search & Security Gateway</h3>
            </div>
            <div class="card-body">
                <?php //= form_open('logistics/searchResult') ?>
                <?= form_open('logistics/searchResult?token=' . session()->get('security_token')) ?>
                <div class="row">
                    <div class="col-md-4">
                        <label>Company <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            <?php foreach($companies as $company): ?>
                                <option value="<?= $company['id'] ?>"><?= esc($company['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Search By</label>
                        <select name="search_type" class="form-select">
                            <option value="awb">AWB Number</option>
                            <option value="docket">Docket Number</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Search Value <span class="text-danger">*</span></label>
                        <input type="text" name="search_value" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3 w-100">
                    Search
                </button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>