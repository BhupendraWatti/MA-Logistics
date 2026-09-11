<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="fas fa-users-cog text-primary"></i> User Management</h2>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
            <i class="fas fa-shield-alt me-1"></i> Centralized MA Logistic Root Administration
        </span>
    </div>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#createUserModal">
        <i class="fas fa-user-plus me-1"></i> Create User
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-3">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover table-bordered w-100 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>System Role</th>
                        <th>Company Access</th>
                        <th class="text-center text-nowrap" style="font-size: 0.78rem; letter-spacing: 0.5px;">MASTER ENTRY</th>
                        <th class="text-center text-nowrap" style="font-size: 0.78rem; letter-spacing: 0.5px;">TRACKING &amp; POD</th>
                        <th class="text-center text-nowrap" style="font-size: 0.78rem; letter-spacing: 0.5px;">USER MANAGEMENT</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                        <th class="text-center">Password</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Create User Drawer -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-sm" tabindex="-1" id="createUserModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary"><i class="fas fa-user-plus me-2"></i> Create New User</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form action="<?= base_url('admin/createUser') ?>" method="post" class="d-flex flex-column h-100 mb-0 no-track">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control form-control-sm shadow-none" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control form-control-sm shadow-none" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control form-control-sm shadow-none" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Default System Role</label>
                    <select name="role" class="form-select form-select-sm shadow-none">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="tracking">Tracking</option>
                    </select>
                </div>
                <hr class="my-3">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold d-block">
                        <i class="fas fa-building text-primary me-1"></i> Initial Company Assignments
                    </label>
                    <div class="border rounded p-2 bg-light" style="max-height: 160px; overflow-y: auto;">
                        <?php foreach (($companies ?? []) as $company): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="company_ids[]" value="<?= $company['id'] ?>" id="comp_check_<?= $company['id'] ?>" <?= ((int)$company['id'] === 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fs-7" for="comp_check_<?= $company['id'] ?>">
                                    <?= esc($company['name']) ?>
                                    <?php if ((int)$company['id'] === 1 || !empty($company['is_root'])): ?>
                                        <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Root</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted fs-8">Per-company roles & permissions can be fine-tuned after creation.</small>
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white border-top p-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="fas fa-check me-2"></i> Create</button>
        </div>
    </form>
</div>

<!-- Password Drawer -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-sm" tabindex="-1" id="passwordModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-warning"><i class="fas fa-key me-2"></i> Change Password</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form action="<?= base_url('admin/changePassword') ?>" method="post" class="d-flex flex-column h-100 mb-0 no-track">
        <?= csrf_field() ?>
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <input type="hidden" name="user_id" id="passwordUserId">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control form-control-sm shadow-none" required minlength="6">
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white border-top p-3 d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm"><i class="fas fa-save me-2"></i> Update</button>
        </div>
    </form>
</div>

<!-- Company Access Drawer -->
<div class="offcanvas offcanvas-end erp-drawer" tabindex="-1" id="companyAccessDrawer" style="width: 550px;" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <div>
            <h5 class="offcanvas-title fw-bold text-primary mb-0">
                <i class="fas fa-building me-2"></i> Company Access Management
            </h5>
            <small class="text-muted">User: <strong id="companyAccessUsername" class="text-dark"></strong> (ID: <span id="companyAccessUserId"></span>)</small>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <!-- Assign / Update Form Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-2 fw-semibold fs-7 text-secondary">
                <i class="fas fa-user-tag text-primary me-1"></i> Assign or Update Company Access
            </div>
            <div class="card-body p-3">
                <input type="hidden" id="assignUserId">
                <div class="row g-2 mb-2">
                    <div class="col-sm-7">
                        <label class="form-label fs-8 text-muted fw-semibold mb-1">Company</label>
                        <select id="assignCompanyId" class="form-select form-select-sm shadow-none" data-no-track="true">
                            <?php foreach (($companies ?? []) as $comp): ?>
                                <option value="<?= $comp['id'] ?>">
                                    <?= esc($comp['name']) ?><?= ((int)$comp['id'] === 1 || !empty($comp['is_root'])) ? ' (Root)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label fs-8 text-muted fw-semibold mb-1">Role in Company</label>
                        <input id="assignRole" type="text" class="form-control form-control-sm shadow-none"
                               list="companyRoleSuggestions" maxlength="50" placeholder="e.g. User" value="user">
                        <datalist id="companyRoleSuggestions">
                            <option value="user">
                            <option value="admin">
                            <option value="tracking">
                        </datalist>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fs-8 text-muted fw-semibold mb-1">Permissions in Company</label>
                    <div class="d-flex flex-wrap gap-3 bg-light p-2 rounded border">
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="assignCanCreate" checked>
                            <label class="form-check-label fs-8 fw-semibold" for="assignCanCreate">Master Entry</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="assignCanEdit">
                            <label class="form-check-label fs-8 fw-semibold" for="assignCanEdit">Tracking &amp; POD</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="checkbox" id="assignCanDelete">
                            <label class="form-check-label fs-8 fw-semibold" for="assignCanDelete">User Management</label>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-primary px-3 fw-semibold shadow-sm" onclick="saveCompanyAssignment()">
                        <i class="fas fa-save me-1"></i> Save Access
                    </button>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <h6 class="fw-bold text-secondary mb-2 fs-7 text-uppercase" style="letter-spacing: 0.5px;">
            <i class="fas fa-list me-1"></i> Active Company Assignments
        </h6>
        <div id="companyAssignmentsLoading" class="text-center py-4 d-none">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="ms-2 fs-7 text-muted">Loading access rights...</span>
        </div>
        <div class="table-responsive border rounded bg-white">
            <table class="table table-sm table-hover align-middle mb-0" id="assignmentsTable">
                <thead class="table-light fs-8">
                    <tr>
                        <th>Company</th>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="assignmentsListBody" class="fs-7">
                    <!-- Populated dynamically via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let usersTable;
    let activeDrawerUserId = null;
    let activeCompanyAssignments = [];

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    
    $(document).ready(function() {
        usersTable = ERPUtils.initDataTable('#usersTable', BASE_URL + 'admin/ajax-datatable', [
            { data: 'id' },
            { data: 'username', render: function(d) { return `<strong>${escapeHtml(d)}</strong>`; } },
            { data: 'email' },
            { 
                data: 'role', 
                render: function(data) {
                    let badgeClass = 'bg-info text-dark';
                    if (data === 'admin') badgeClass = 'bg-danger';
                    else if (data === 'tracking') badgeClass = 'bg-warning text-dark';
                    return `<span class="badge ${badgeClass}">${escapeHtml(data.charAt(0).toUpperCase() + data.slice(1))}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const count = row.company_count || 0;
                    const btnClass = count > 0 ? 'btn-outline-primary' : 'btn-outline-secondary';
                    const badgeClass = count > 0 ? 'bg-primary' : 'bg-secondary';
                    return `<button class="btn btn-sm ${btnClass} fw-bold shadow-sm" onclick="openCompanyAccessDrawer(${parseInt(row.id, 10)}, decodeURIComponent('${encodeURIComponent(String(row.username || ''))}'))">
                                <i class="fas fa-building me-1"></i> <span class="badge ${badgeClass}">${count}</span> Company(ies)
                            </button>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const checked = row.can_create == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch d-inline-block"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_create" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const checked = row.can_edit == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch d-inline-block"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_edit" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const checked = row.can_delete == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch d-inline-block"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_delete" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: 'is_active',
                className: 'text-center',
                render: function(data) {
                    return `<span class="badge ${data == 1 ? 'bg-success' : 'bg-secondary'}">${data == 1 ? 'Active' : 'Inactive'}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const btnClass = row.is_active == 1 ? 'btn-outline-danger' : 'btn-outline-success';
                    const btnText = row.is_active == 1 ? '<i class="fas fa-ban"></i> Deactivate' : '<i class="fas fa-check"></i> Activate';
                    return `<button class="btn btn-sm ${btnClass} me-1 shadow-sm" onclick="toggleActive(${row.id}, ${row.is_active})">${btnText}</button>` +
                           `<button class="btn btn-sm btn-outline-danger shadow-sm" onclick="deleteUser(${row.id})"><i class="fas fa-trash"></i></button>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-warning fw-bold shadow-sm" data-bs-toggle="offcanvas" data-bs-target="#passwordModal" onclick="setUserId(${row.id})"><i class="fas fa-key"></i></button>`;
                }
            }
        ]);

        // Delegated event binding for permissions
        $('#usersTable').on('change', '.toggle-permission', function() {
            const userId = $(this).attr('data-user-id');
            const permission = $(this).attr('data-permission');
            const value = $(this).is(':checked') ? 1 : 0;

            $.post(BASE_URL + 'admin/togglePermission', {
                user_id: userId,
                permission: permission,
                value: value
            }, function(response) {
                if (response.success) {
                    if (userId == <?= session()->get('user_id') ?? 0 ?>) {
                        location.reload();
                    } else {
                        ERPUtils.showSuccess('Permission Updated', 'Root default permission updated.');
                    }
                } else {
                    ERPUtils.showError('Permission Denied', response.message || 'Action restricted.');
                    usersTable.ajax.reload(null, false);
                }
            }).fail(function() {
                ERPUtils.showError('Error', 'Server error occurred.');
                usersTable.ajax.reload(null, false);
            });
        });

        $('#assignCompanyId').on('change', loadSelectedCompanyAssignment);
    });

    function openCompanyAccessDrawer(userId, username) {
        activeDrawerUserId = userId;
        activeCompanyAssignments = [];
        $('#assignUserId').val(userId);
        $('#companyAccessUserId').text(userId);
        $('#companyAccessUsername').text(username);

        loadUserCompanyAssignments(userId);

        const drawerEl = document.getElementById('companyAccessDrawer');
        const bsDrawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
        bsDrawer.show();
    }

    function loadUserCompanyAssignments(userId) {
        $('#companyAssignmentsLoading').removeClass('d-none');
        $('#assignmentsListBody').empty();

        $.get(BASE_URL + 'admin/getUserAssignments', { user_id: userId }, function(response) {
            $('#companyAssignmentsLoading').addClass('d-none');
            if (response.success && response.data) {
                activeCompanyAssignments = response.data;
                renderAssignmentsList(activeCompanyAssignments);
                loadSelectedCompanyAssignment();
            } else {
                $('#assignmentsListBody').html('<tr><td colspan="4" class="text-center text-muted py-3">No companies assigned yet.</td></tr>');
            }
        }).fail(function() {
            $('#companyAssignmentsLoading').addClass('d-none');
            $('#assignmentsListBody').html('<tr><td colspan="4" class="text-center text-danger py-3">Failed to load company assignments.</td></tr>');
        });
    }

    function loadSelectedCompanyAssignment() {
        const companyId = parseInt($('#assignCompanyId').val(), 10);
        const assignment = activeCompanyAssignments.find(item => parseInt(item.company_id, 10) === companyId);

        $('#assignRole').val(assignment ? assignment.role : 'user');
        $('#assignCanCreate').prop('checked', assignment ? parseInt(assignment.can_create, 10) === 1 : false);
        $('#assignCanEdit').prop('checked', assignment ? parseInt(assignment.can_edit, 10) === 1 : false);
        $('#assignCanDelete').prop('checked', assignment ? parseInt(assignment.can_delete, 10) === 1 : false);
    }

    function renderAssignmentsList(assignments) {
        if (!assignments || assignments.length === 0) {
            $('#assignmentsListBody').html('<tr><td colspan="4" class="text-center text-muted py-3">No companies assigned yet.</td></tr>');
            return;
        }

        let html = '';
        assignments.forEach(item => {
            const isRoot = (parseInt(item.company_id) === 1 || parseInt(item.is_root) === 1);
            const rootBadge = isRoot ? '<span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Root</span>' : '';

            let roleBadge = 'bg-info text-dark';
            if (item.role === 'admin') roleBadge = 'bg-danger';
            else if (item.role === 'tracking') roleBadge = 'bg-warning text-dark';

            let perms = [];
            if (parseInt(item.can_create)) perms.push('<span class="badge bg-primary" title="Master Entry">Master Entry</span>');
            if (parseInt(item.can_edit)) perms.push('<span class="badge bg-info text-dark" title="Tracking & POD">Tracking & POD</span>');
            if (parseInt(item.can_delete)) perms.push('<span class="badge bg-danger" title="User Management">User Mgmt</span>');
            if (perms.length === 0) perms.push('<span class="badge bg-light text-muted border">None</span>');

            html += `<tr>
                <td class="fw-semibold text-dark">
                    <i class="fas fa-building text-secondary me-1"></i> ${escapeHtml(item.company_name)} ${rootBadge}
                </td>
                <td>
                    <span class="badge ${roleBadge}">${escapeHtml(item.role.charAt(0).toUpperCase() + item.role.slice(1))}</span>
                </td>
                <td>
                    <div class="d-flex gap-1">${perms.join('')}</div>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger py-0 px-2 shadow-none" title="Revoke Access" onclick="revokeCompanyAccess(${parseInt(item.company_id, 10)}, decodeURIComponent('${encodeURIComponent(String(item.company_name || ''))}'))">
                        <i class="fas fa-trash-alt fs-8"></i>
                    </button>
                </td>
            </tr>`;
        });
        $('#assignmentsListBody').html(html);
    }

    function saveCompanyAssignment() {
        const userId = $('#assignUserId').val();
        const companyId = $('#assignCompanyId').val();
        const role = $('#assignRole').val().trim();
        const canCreate = $('#assignCanCreate').is(':checked') ? 1 : 0;
        const canEdit = $('#assignCanEdit').is(':checked') ? 1 : 0;
        const canDelete = $('#assignCanDelete').is(':checked') ? 1 : 0;

        if (!userId || !companyId || !role) {
            ERPUtils.showWarning('Required', 'Please select a company and enter a role.');
            return;
        }

        $.post(BASE_URL + 'admin/saveAssignment', {
            user_id: userId,
            company_id: companyId,
            role: role,
            can_create: canCreate,
            can_edit: canEdit,
            can_delete: canDelete,
            is_active: 1
        }, function(response) {
            if (response.success) {
                window.resetDirty?.();
                ERPUtils.showSuccess('Access Saved', response.message || 'Company access updated.');
                loadUserCompanyAssignments(userId);
                usersTable.ajax.reload(null, false);
            } else {
                ERPUtils.showError('Error', response.message || 'Could not save company access.');
            }
        }).fail(function() {
            ERPUtils.showError('Server Error', 'Failed to communicate with server.');
        });
    }

    function revokeCompanyAccess(companyId, companyName) {
        const userId = activeDrawerUserId;
        ERPUtils.confirmAction('Revoke Access?', `Remove access to "${companyName}" for this user?`, 'Yes, revoke').then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/revokeAssignment', {
                    user_id: userId,
                    company_id: companyId
                }, function(response) {
                    if (response.success) {
                        ERPUtils.showSuccess('Access Revoked', response.message);
                        loadUserCompanyAssignments(userId);
                        usersTable.ajax.reload(null, false);
                    } else {
                        ERPUtils.showError('Action Denied', response.message || 'Could not revoke access.');
                    }
                }).fail(function() {
                    ERPUtils.showError('Server Error', 'Failed to communicate with server.');
                });
            }
        });
    }

    function toggleActive(userId, currentStatus) {
        const action = currentStatus == 1 ? 'deactivate' : 'activate';
        const confirmMsg = currentStatus == 1
            ? 'Deactivate this user? They will not be able to log in!'
            : 'Activate this user?';

        ERPUtils.confirmAction('Are you sure?', confirmMsg, 'Yes, ' + action).then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/toggleStatus', {
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        if (response.logout) {
                            window.location.href = BASE_URL + 'login';
                        } else {
                            ERPUtils.showSuccess('Success', response.message);
                            usersTable.ajax.reload(null, false);
                        }
                    } else {
                        ERPUtils.showError('Failed', response.message || 'Action could not be performed.');
                    }
                }).fail(function() {
                    ERPUtils.showError('Error', 'Server error occurred.');
                });
            }
        });
    }

    function deleteUser(userId) {
        if (userId == <?= session()->get('user_id') ?? 0 ?>) {
            ERPUtils.showWarning('Action Denied', 'You cannot delete yourself.');
            return;
        }

        ERPUtils.confirmAction('Delete User?', 'This action will delete the user and all their company access records!', 'Yes, delete').then((result) => {
            if (result.isConfirmed) {
                $.post(BASE_URL + 'admin/deleteUser', {
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        ERPUtils.showSuccess('Deleted!', response.message);
                        usersTable.ajax.reload(null, false);
                    } else {
                        ERPUtils.showError('Error', response.message);
                    }
                }).fail(function() {
                    ERPUtils.showError('Error', 'Server error occurred while deleting user.');
                });
            }
        });
    }

    function setUserId(userId) {
        document.getElementById('passwordUserId').value = userId;
    }
</script>
<?= $this->endSection() ?>
