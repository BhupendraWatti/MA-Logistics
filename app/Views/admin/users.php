<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog"></i> User Management</h2>
    <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="offcanvas" data-bs-target="#createUserModal">
        <i class="fas fa-user-plus me-1"></i> Create User
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-3">
        <table id="usersTable" class="table table-hover table-bordered w-100">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Create</th>
                    <th>Edit</th>
                    <th>Delete</th>
                    <th>Status</th>
                    <th>Actions</th>
                    <th>Password</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Create User Drawer -->
<div class="offcanvas offcanvas-end erp-drawer erp-drawer-sm" tabindex="-1" id="createUserModal" data-bs-backdrop="true">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary"><i class="fas fa-user-plus me-2"></i> Create New User</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <form action="<?= base_url('admin/createUser') ?>" method="post" class="d-flex flex-column h-100 mb-0">
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
                    <input type="password" name="password" class="form-control form-control-sm shadow-none" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">Role</label>
                    <select name="role" class="form-select form-select-sm shadow-none">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white">
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
    <form action="<?= base_url('admin/changePassword') ?>" method="post" class="d-flex flex-column h-100 mb-0">
        <div class="offcanvas-body position-relative p-0">
            <div class="erp-drawer-content pb-5">
                <input type="hidden" name="user_id" id="passwordUserId">
                <div class="mb-3">
                    <label class="form-label text-muted fs-7 fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="new_password" class="form-control form-control-sm shadow-none" required minlength="6">
                </div>
            </div>
        </div>
        <div class="sticky-footer bg-white">
            <button type="button" class="btn btn-outline-secondary fw-bold px-4 shadow-sm" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="submit" class="btn btn-warning fw-bold px-4 shadow-sm"><i class="fas fa-save me-2"></i> Update</button>
        </div>
    </form>
</div>

<?= $this->endSection() ?> <!-- close content -->
<?= $this->section('scripts') ?>
<script>
    let usersTable;
    
    $(document).ready(function() {
        usersTable = ERPUtils.initDataTable('#usersTable', BASE_URL + 'admin/ajax-datatable', [
            { data: 'id' },
            { data: 'username' },
            { data: 'email' },
            { 
                data: 'role', 
                render: function(data) {
                    return `<span class="badge ${data === 'admin' ? 'bg-danger' : 'bg-info'}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const checked = row.can_create == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_create" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const checked = row.can_edit == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_edit" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const checked = row.can_delete == 1 ? 'checked' : '';
                    return `<div class="form-check form-switch"><input class="form-check-input toggle-permission" data-user-id="${row.id}" data-permission="can_delete" type="checkbox" ${checked}></div>`;
                }
            },
            {
                data: 'is_active',
                render: function(data) {
                    return `<span class="badge ${data == 1 ? 'bg-success' : 'bg-secondary'}">${data == 1 ? 'Active' : 'Inactive'}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    const btnClass = row.is_active == 1 ? 'btn-outline-danger' : 'btn-outline-success';
                    const btnText = row.is_active == 1 ? '<i class="fas fa-ban"></i> Deactivate' : '<i class="fas fa-check"></i> Activate';
                    return `<button class="btn btn-sm ${btnClass} me-1" onclick="toggleActive(${row.id}, ${row.is_active})">${btnText}</button>` +
                           `<button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${row.id})"><i class="fas fa-trash"></i> Delete</button>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-outline-warning fw-bold shadow-sm" data-bs-toggle="offcanvas" data-bs-target="#passwordModal" onclick="setUserId(${row.id})"><i class="fas fa-key"></i> Change</button>`;
                }
            }
        ]);

        // Delegated event binding for permissions
        $('#usersTable').on('change', '.toggle-permission', function() {
            const userId = $(this).data('user-id');
            const permission = $(this).data('permission');
            const value = $(this).is(':checked') ? 1 : 0;

            $.post('<?= base_url('admin/togglePermission') ?>', {
                user_id: userId,
                permission: permission,
                value: value
            }, function(response) {
                if (response.success) {
                    if (userId == <?= session()->get('user_id') ?? 0 ?>) {
                        location.reload();
                    } else {
                        ERPUtils.showSuccess('Permission Updated', 'Role access updated successfully.');
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
    });

    function toggleActive(userId, currentStatus) {
        const action = currentStatus == 1 ? 'deactivate' : 'activate';
        const confirmMsg = currentStatus == 1 
            ? 'Deactivate this user? They cannot login!' 
            : 'Activate this user?';

        ERPUtils.confirmAction('Are you sure?', confirmMsg, 'Yes, ' + action).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('admin/toggleStatus') ?>', {
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        if (response.logout) {
                            window.location.href = '/login';
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

        ERPUtils.confirmAction('Delete User?', 'This action cannot be undone!', 'Yes, delete').then((result) => {
            if (result.isConfirmed) {
                $.post('<?= base_url('admin/deleteUser') ?>', {
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