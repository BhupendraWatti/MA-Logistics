<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container-fluid px-4 py-5">
    
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark mb-2" style="font-size: 2rem;">Workspace Access</h2>
        <p class="text-secondary" style="font-size: 1.1rem;">Select operational company to continue session</p>
    </div>

    <div class="card shadow-sm border-0 rounded-3 mb-4 mx-auto" style="max-width: 1000px;">
        <div class="card-body p-0">
            <!-- Header section of the card -->
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h6 class="text-uppercase fw-bold text-secondary mb-0" style="font-size: 0.85rem; letter-spacing: 1px;">Company Directory</h6>
                <div class="d-flex">
                    <div class="input-group input-group-sm me-3" style="width: 250px;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 shadow-none" id="companySearch" placeholder="Search entity ID or name...">
                    </div>
                    <?php if (session()->get('role') === 'admin'): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-none" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                            <i class="fas fa-plus me-1"></i> Add Company
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table section -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="companyTable">
                    <thead class="text-muted" style="font-size: 0.85rem;">
                        <tr>
                            <th class="ps-4 fw-normal border-0 pt-3 pb-3">Company Name</th>
                            <th class="text-end pe-4 fw-normal border-0 pt-3 pb-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach($companies ?? [] as $company): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-dark company-name"><?= esc($company['name']) ?></td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <?= form_open('logistics/setCompany', ['class' => 'm-0']) ?>
                                            <input type="hidden" name="company_id" value="<?= $company['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary px-3 shadow-none">Enter</button>
                                        <?= form_close() ?>
                                        
                                        <?php if (session()->get('role') === 'admin'): ?>
                                        <?= form_open('logistics/deleteCompany/' . $company['id'], ['class' => 'm-0', 'onsubmit' => 'event.preventDefault(); ERPUtils.confirmAction("Delete Company", "Are you sure? This will delete ALL bookings related to this company!", "Yes, delete", "error").then(res => { if(res.isConfirmed) this.submit(); });']) ?>
                                            <button type="submit" class="btn btn-sm btn-light text-danger shadow-none" title="Delete Company">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?= form_close() ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (session()->get('role') === 'admin'): ?>
<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" aria-labelledby="addCompanyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="addCompanyModalLabel"><i class="fas fa-building text-primary me-2"></i> Add New Company</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <?= form_open('logistics/createCompany') ?>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label text-muted small fw-semibold">Company Name</label>
            <input type="text" name="name" class="form-control shadow-none" placeholder="Enter full legal entity name" required>
        </div>
      </div>
      <div class="modal-footer border-top-0 bg-light">
        <button type="button" class="btn btn-light shadow-none border" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary shadow-none"><i class="fas fa-plus me-1"></i> Create Workspace</button>
      </div>
      <?= form_close() ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Simple JS for table filtering -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('companySearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#companyTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
<?= $this->endSection() ?>