<?php
namespace App\Controllers;

use App\Models\UserModel;

class AdminController extends BaseController
{
    public function index()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics');
        }

        $userModel = new UserModel();
        $data['users'] = $userModel->findAll();
        return view('admin/users', $data);
    }

    public function ajaxDatatable()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setJSON(['error' => 'Admin only!']);
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

        // We'll just order by ID desc
        $builder->orderBy('id', 'desc');

        $data = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    public function togglePermission()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin only!']);
        }

        $userModel = new UserModel();
        $userId = $this->request->getPost('user_id');
        $permission = $this->request->getPost('permission');
        $value = $this->request->getPost('value') ? 1 : 0;

        // UPDATE DB
        $userModel->update($userId, [$permission => $value]);

        // ✅ RELOAD SESSION for that user IMMEDIATELY
        $user = $userModel->find($userId);
        if ($user) {
        // Update session if current user
            if (session()->get('user_id') == $userId) {
                session()->set('permissions', [
                    'can_create' => $user['can_create'],
                    'can_edit' => $user['can_edit'],
                    'can_delete' => $user['can_delete']
                ]);
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function createUser()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics');
        }

        $userModel = new UserModel();
        $data = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'role' => $this->request->getPost('role'),
            'can_create' => 1,
            'can_edit' => 1,
            'can_delete' => $this->request->getPost('role') === 'admin' ? 1 : 0
        ];

        $userModel->insert($data);
        return redirect()->to('/admin')->with('success', 'User created successfully!');
    }


    // Deactivate Button Enable
    public function toggleStatus()
    {
        if (session()->get('role') !== 'admin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Admin only!']);
        }

        $userModel = new UserModel();
        $userId = $this->request->getPost('user_id');
        $user = $userModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
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


    // Password Hashing method
    public function changePassword()
    {
        if (session()->get('role') !== 'admin') {
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

        // ✅ DIRECT UPDATE - Bypass UserModel's auto-hash
        $db = \Config\Database::connect();
        $db->table('users')
        ->where('id', $userId)
        ->update(['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        return redirect()->to('/admin')->with('success', '✅ Password changed!');
    }


}