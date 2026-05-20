<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog"></i> User Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-plus"></i> Add User
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
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
        <tbody>
            <?php foreach($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= esc($user['username']) ?></td>
                    <td><?= esc($user['email']) ?></td>
                    <td><span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-info' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-permission" 
                            data-user-id="<?= $user['id'] ?>" 
                            data-permission="can_create" 
                            type="checkbox" 
                            <?= $user['can_create'] ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-permission" 
                            data-user-id="<?= $user['id'] ?>" 
                            data-permission="can_edit" 
                            type="checkbox" 
                            <?= $user['can_edit'] ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input toggle-permission" 
                            data-user-id="<?= $user['id'] ?>" 
                            data-permission="can_delete" 
                            type="checkbox" 
                            <?= $user['can_delete'] ? 'checked' : '' ?>>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <!-- UPDATED button with current status -->
                        <button class="btn btn-sm <?= $user['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                            onclick="toggleActive(<?= $user['id'] ?>, <?= $user['is_active'] ?>)">
                            <?= $user['is_active'] ? '<i class="fas fa-ban"></i> Deactivate' : '<i class="fas fa-check"></i> Activate' ?>
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#passwordModal" onclick="setUserId(<?= $user['id'] ?>)">
                            <i class="fas fa-key"></i> Change
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/createUser') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Create New User</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Model -->
<div class="modal fade" id="passwordModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('admin/changePassword') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="passwordUserId">
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?> <!-- close content -->
<?= $this->section('scripts') ?>
<script>
    $('.toggle-permission').change(function() {
        const userId = $(this).data('user-id');
        const permission = $(this).data('permission');
        const value = $(this).is(':checked') ? 1 : 0;

        $.post('<?= base_url('admin/togglePermission') ?>', {
            user_id: userId,
            permission: permission,
            value: value
        }, function(response) {
            if (response.success) {
            // ✅ RELOAD PAGE if current user affected
                if (userId == <?= session()->get('user_id') ?>) {
                    location.reload();
                }
            // Show success
            // $(this).closest('tr').addClass('table-success').fadeOut(1000, function() {
            //     $(this).fadeIn().removeClass('table-success');
            // });
            } else {
                alert(response.message || 'Permission denied!');
                location.reload();
            }
        }).fail(function() {
            alert('Server error!');
            location.reload();
        });
    });

    // ✅ ADDED: Toggle Active/Deactivate function
    function toggleActive(userId, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        const confirmMsg = currentStatus 
        ? 'Deactivate this user? They cannot login!' 
        : 'Activate this user?';

        if (!confirm(confirmMsg)) return;

        $.post('<?= base_url('admin/toggleStatus') ?>', {
            user_id: userId
        }, function(response) {
            if (response.success) {
                alert(response.message);
                if (response.logout) {
                    window.location.href = '/login';
                } else {
                    location.reload();
                }
            } else {
                alert(response.message || 'Failed!');
            }
        }).fail(function() {
            alert('Server error!');
        });
    }
</script>
<!-- PSD JS -->
<script>
    function setUserId(userId) {
        document.getElementById('passwordUserId').value = userId;
    }
</script>

<?= $this->endSection() ?>