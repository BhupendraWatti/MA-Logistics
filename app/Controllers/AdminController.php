<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CompanyModel;
use App\Models\UserCompanyAccessModel;

class AdminController extends BaseController
{
    /**
     * Enforce MA Logistic root administrative guard
     */
    private function checkRootAdmin(): bool
    {
        $isRootCompany = (int) session()->get('is_root_company');
        $permissions = session()->get('permissions') ?? [];
        $userRole = session()->get('role') ?? 'user';

        $hasUserMgmt = ($userRole === 'admin' || !empty($permissions['can_delete']));

        if (!$hasUserMgmt || !$isRootCompany) {
            return false;
        }
        return true;
    }

    private function isSoleActiveRootAdmin(int $userId): bool
    {
        $rootComp = (new \App\Models\CompanyModel())->getRootCompany();
        $rootId = (int) ($rootComp['id'] ?? 2);

        $rootAdmins = \Config\Database::connect()->table('user_company_access uca')
            ->select('uca.user_id')
            ->join('users u', 'u.id = uca.user_id')
            ->where('uca.company_id', $rootId)
            ->where('uca.role', 'admin')
            ->where('uca.is_active', 1)
            ->where('u.is_active', 1)
            ->get()
            ->getResultArray();

        return count($rootAdmins) === 1 && (int) $rootAdmins[0]['user_id'] === $userId;
    }

    public function index()
    {
        if (! $this->checkRootAdmin()) {
            return redirect()->to('/logistics')->with('error', 'User Management is centralized under MA Logistic root administration.');
        }

        $userModel = new UserModel();
        $companyModel = new CompanyModel();
        $data['users'] = $userModel->findAll();
        $data['companies'] = $companyModel->orderBy('id', 'ASC')->findAll();
        return view('admin/users', $data);
    }

    public function ajaxDatatable()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'User Management is centralized under MA Logistic root administration.']);
        }

        $post = $this->request->getPost();
        $draw = (int) ($post['draw'] ?? 1);
        $start = (int) ($post['start'] ?? 0);
        $length = (int) ($post['length'] ?? 10);
        $searchValue = $post['search']['value'] ?? '';

        $userModel = new UserModel();
        $builder = $userModel->builder();

        // Total records
        $totalRecords = $builder->countAllResults(false);

        // Search
        if (!empty($searchValue)) {
            $builder->groupStart()
                    ->like('username', $searchValue)
                    ->orLike('email', $searchValue)
                    ->orLike('role', $searchValue)
                    ->groupEnd();
        }
        $filteredRecords = $builder->countAllResults(false);

        // Pagination
        if ($length != -1) {
            $builder->limit($length, $start);
        }

        // Order by ID desc
        $builder->orderBy('id', 'desc');

        $data = $builder->get()->getResultArray();

        $companyCounts = [];
        if ($data) {
            $countRows = \Config\Database::connect()->table('user_company_access')
                ->select('user_id, COUNT(*) AS company_count')
                ->whereIn('user_id', array_column($data, 'id'))
                ->groupBy('user_id')
                ->get()
                ->getResultArray();
            $companyCounts = array_column($countRows, 'company_count', 'user_id');
        }

        foreach ($data as &$row) {
            $row['company_count'] = (int) ($companyCounts[$row['id']] ?? 0);
        }
        unset($row);

        session_write_close();
        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function getUserAssignments()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = (int) $this->request->getVar('user_id');
        $accessModel = new UserCompanyAccessModel();
        $assignments = $accessModel->getUserAssignmentsWithCompanies($userId);

        session_write_close();
        return $this->response->setJSON([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function saveAssignment()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = (int) $this->request->getPost('user_id');
        $companyId = (int) $this->request->getPost('company_id');
        $role = strtolower(trim((string) ($this->request->getPost('role') ?? 'user')));
        $canCreate = !empty($this->request->getPost('can_create') ?? ($role === 'tracking' ? 0 : 1)) ? 1 : 0;
        $canEdit = !empty($this->request->getPost('can_edit') ?? ($role === 'tracking' || $role === 'admin' ? 1 : 0)) ? 1 : 0;
        $canDelete = !empty($this->request->getPost('can_delete') ?? ($role === 'admin' ? 1 : 0)) ? 1 : 0;
        $isActive = !empty($this->request->getPost('is_active') ?? 1) ? 1 : 0;

        if (!$userId || !$companyId || $role === '' || strlen($role) > 50) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'User, Company and a role of up to 50 characters are required.']);
        }

        $userModel = new UserModel();
        $companyModel = new CompanyModel();
        if (!$userModel->find($userId) || !$companyModel->find($companyId)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => 'The selected user or company does not exist.']);
        }

        $accessModel = new UserCompanyAccessModel();
        $existingAccess = $accessModel->getUserAccess($userId, $companyId);
        if ($companyId === 1
            && ($existingAccess['role'] ?? null) === 'admin'
            && (int) ($existingAccess['is_active'] ?? 0) === 1
            && ($role !== 'admin' || $isActive !== 1)
            && $this->isSoleActiveRootAdmin($userId)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Cannot remove or deactivate the sole active Root Administrator.',
            ]);
        }

        $saved = $accessModel->assignCompany($userId, $companyId, [
            'role'       => $role,
            'can_create' => $canCreate,
            'can_edit'   => $canEdit,
            'can_delete' => $canDelete,
            'is_active'  => $isActive,
        ]);

        if ($saved && $companyId === 1) {
            $userModel->update($userId, [
                'can_create' => $canCreate,
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
            ]);
        }

        // If the updated user is currently logged in, update their active session if this is their active company
        if ($saved && (int) session()->get('user_id') === $userId && (int) session()->get('selected_company_id') === $companyId) {
            session()->set([
                'role' => $role,
                'permissions' => [
                    'can_create' => $canCreate,
                    'can_edit'   => $canEdit,
                    'can_delete' => $canDelete,
                ]
            ]);
        }

        session_write_close();
        return $this->response->setJSON([
            'success' => (bool) $saved,
            'message' => $saved ? 'Company access updated successfully.' : 'Failed to update company access.'
        ]);
    }

    public function revokeAssignment()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $userId = (int) $this->request->getPost('user_id');
        $companyId = (int) $this->request->getPost('company_id');

        // Prevent revoking root company access of the sole active root admin
        if ($companyId === 1) {
            if ($this->isSoleActiveRootAdmin($userId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Cannot revoke root company access for the sole active Root Administrator.'
                ]);
            }
        }

        $accessModel = new UserCompanyAccessModel();
        $revoked = $accessModel->revokeCompany($userId, $companyId);

        session_write_close();
        return $this->response->setJSON([
            'success' => (bool) $revoked,
            'message' => $revoked ? 'Company access revoked successfully.' : 'Failed to revoke company access.'
        ]);
    }

    public function togglePermission()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin only!']);
        }

        $userModel = new UserModel();
        $userId = (int) $this->request->getPost('user_id');
        $companyId = (int) ($this->request->getPost('company_id') ?: session()->get('selected_company_id') ?: 1);
        $permission = $this->request->getPost('permission');
        $value = $this->request->getPost('value') ? 1 : 0;

        if (!in_array($permission, ['can_create', 'can_edit', 'can_delete'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid permission']);
        }

        // Update in user_company_access
        $accessModel = new UserCompanyAccessModel();
        $access = $accessModel->getUserAccess($userId, $companyId);
        if ($access) {
            $accessModel->update($access['id'], [$permission => $value]);
        } else {
            $user = $userModel->find($userId);
            $accessModel->assignCompany($userId, $companyId, [
                'role' => $user['role'] ?? 'user',
                $permission => $value
            ]);
        }

        // Also update users table if root company for backward compatibility
        if ($companyId === 1) {
            $userModel->update($userId, [$permission => $value]);
        }

        // Update session if current user
        if ((int) session()->get('user_id') === $userId && (int) session()->get('selected_company_id') === $companyId) {
            $perms = session()->get('permissions') ?? [];
            $perms[$permission] = $value;
            session()->set('permissions', $perms);
        }

        session_write_close();
        return $this->response->setJSON(['success' => true]);
    }

    public function createUser()
    {
        if (! $this->checkRootAdmin()) {
            return redirect()->to('/logistics')->with('error', 'User Management is centralized under MA Logistic root administration.');
        }

        $userModel = new UserModel();
        $data = [
            'username'   => trim($this->request->getPost('username') ?? ''),
            'email'      => trim($this->request->getPost('email') ?? ''),
            'password'   => $this->request->getPost('password') ?? '',
            'role'       => $this->request->getPost('role') ?? 'user',
            'is_active'  => 1,
            'can_create' => $this->request->getPost('role') === 'tracking' ? 0 : 1,
            'can_edit'   => ($this->request->getPost('role') === 'tracking' || $this->request->getPost('role') === 'admin') ? 1 : 0,
            'can_delete' => $this->request->getPost('role') === 'admin' ? 1 : 0
        ];

        // Validations
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return redirect()->back()->withInput()->with('error', 'All fields are required.');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
        }

        if (strlen($data['password']) < 6) {
            return redirect()->back()->withInput()->with('error', 'Password must be at least 6 characters long.');
        }

        // Check for duplicate username
        $existingUser = $userModel->where('username', $data['username'])->first();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'Username is already taken.');
        }

        // Check for duplicate email
        $existingEmail = $userModel->where('email', $data['email'])->first();
        if ($existingEmail) {
            return redirect()->back()->withInput()->with('error', 'Email address is already registered.');
        }

        $newUserId = $userModel->insert($data);
        if ($newUserId) {
            $accessModel = new UserCompanyAccessModel();
            $companyIds = $this->request->getPost('company_ids');
            if (empty($companyIds)) {
                $companyIds = [1];
            }
            if (is_array($companyIds)) {
                foreach ($companyIds as $cId) {
                    $cId = (int) $cId;
                    if ($cId > 0) {
                        $accessModel->assignCompany($newUserId, $cId, [
                            'role'       => $data['role'],
                            'can_create' => $data['can_create'],
                            'can_edit'   => $data['can_edit'],
                            'can_delete' => $data['can_delete'],
                            'is_active'  => 1
                        ]);
                    }
                }
            }
        }

        return redirect()->to('/admin')->with('success', 'User created successfully!');
    }

    public function updateUser()
    {
        if (! $this->checkRootAdmin()) {
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'User Management is centralized under MA Logistic root administration.'
                ]);
            }
            return redirect()->to('/logistics')->with('error', 'User Management is centralized under MA Logistic root administration.');
        }

        $userId = (int) $this->request->getPost('user_id');
        if ($userId <= 0) {
            $msg = 'Invalid user ID.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        $userModel = new UserModel();
        $existing = $userModel->find($userId);
        if (!$existing) {
            $msg = 'User not found.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        $username = trim((string) $this->request->getPost('username'));
        $email    = trim((string) $this->request->getPost('email'));
        $role     = strtolower(trim((string) $this->request->getPost('role')));

        if (empty($username) || empty($email)) {
            $msg = 'Username and Email are required.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Please enter a valid email address.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Check duplicate username (excluding current user)
        $duplicateUser = $userModel->where('username', $username)->where('id !=', $userId)->first();
        if ($duplicateUser) {
            $msg = 'Username is already taken by another user.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        // Check duplicate email (excluding current user)
        $duplicateEmail = $userModel->where('email', $email)->where('id !=', $userId)->first();
        if ($duplicateEmail) {
            $msg = 'Email address is already registered to another user.';
            if ($this->request->isAJAX()) {
                session_write_close();
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $updateData = [
            'username' => $username,
            'email'    => $email,
        ];

        // If role provided, update role
        if (!empty($role)) {
            // Guard: don't demote sole active root admin
            if ($existing['role'] === 'admin' && $role !== 'admin' && $this->isSoleActiveRootAdmin($userId)) {
                $msg = 'Cannot demote the sole active Root Administrator.';
                if ($this->request->isAJAX()) {
                    session_write_close();
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }
            $updateData['role'] = $role;
        }

        // Optional password update if provided
        $password = (string) $this->request->getPost('password');
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $msg = 'Password must be at least 6 characters long.';
                if ($this->request->isAJAX()) {
                    session_write_close();
                    return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => $msg]);
                }
                return redirect()->back()->with('error', $msg);
            }
            $updateData['password'] = $password;
        }

        $userModel->update($userId, $updateData);

        // If current logged-in user updated their own username/role, refresh session
        if ($userId === (int) session()->get('user_id')) {
            session()->set('username', $username);
            session()->set('email', $email);
            if (isset($updateData['role'])) {
                session()->set('role', $updateData['role']);
            }
        }

        if ($this->request->isAJAX()) {
            session_write_close();
            return $this->response->setJSON([
                'success' => true,
                'message' => 'User updated successfully!',
                'csrf_hash' => csrf_hash()
            ]);
        }

        return redirect()->to('/admin')->with('success', 'User updated successfully!');
    }

    public function toggleStatus()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin only!']);
        }

        $userModel = new UserModel();
        $userId = $this->request->getPost('user_id');
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        if (!$newStatus && $this->isSoleActiveRootAdmin((int) $userId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Cannot deactivate the sole active Root Administrator.',
            ]);
        }
        $userModel->update($userId, ['is_active' => $newStatus]);

        if ($userId == session()->get('user_id') && !$newStatus) {
            session()->destroy();
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Your account was deactivated!',
                'logout' => true
            ]);
        }

        return $this->response->setJSON([
            'success' => true, 
            'message' => $newStatus ? 'User activated!' : 'User deactivated!'
        ]);
    }

    public function deleteUser()
    {
        if (! $this->checkRootAdmin()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin only!']);
        }
        
        $userId = $this->request->getPost('user_id');
        if ($userId == session()->get('user_id')) {
            return $this->response->setJSON(['success' => false, 'message' => 'You cannot delete yourself!']);
        }
        if ($this->isSoleActiveRootAdmin((int) $userId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Cannot delete the sole active Root Administrator.',
            ]);
        }
        
        (new UserModel())->delete($userId);
        return $this->response->setJSON(['success' => true, 'message' => 'User deleted successfully!']);
    }

    public function changePassword()
    {
        if (! $this->checkRootAdmin()) {
            return redirect()->to('/logistics');
        }

        $userId = $this->request->getPost('user_id');
        $newPassword = $this->request->getPost('new_password');

        if (!$userId || !$newPassword) {
            return redirect()->back()->with('error', 'Missing fields!');
        }

        if (strlen($newPassword) < 6) {
            return redirect()->back()->with('error', 'Password too short!');
        }

        // Direct update - bypass UserModel's auto-hash
        $db = \Config\Database::connect();
        $db->table('users')
           ->where('id', $userId)
           ->update(['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        return redirect()->to('/admin')->with('success', '✅ Password changed!');
    }
}
