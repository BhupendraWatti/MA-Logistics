<?php
namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        // TEMPORARY FIX: Hash any plain-text passwords in the DB
        $db = \Config\Database::connect();
        $users = $db->table('users')->get()->getResultArray();
        foreach ($users as $u) {
            if (substr($u['password'], 0, 4) !== '$2y$') {
                $db->table('users')->where('id', $u['id'])->update([
                    'password' => password_hash($u['password'], PASSWORD_DEFAULT)
                ]);
            }
        }

        return view('auth/login');
    }

    public function attemptLogin()
    {
        $userModel = new UserModel();
        $credentials = [
            'username' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password')
        ];

        // --- FIRST-TIME ADMIN BOOTSTRAP ONLY ---
        $db = \Config\Database::connect();
        $userCount = $db->table('users')->countAllResults();
        
        if ($userCount === 0) {
            // No users exist in the system yet. Auto-create the initial admin user.
            $db->table('users')->insert([
                'username' => $credentials['username'],
                'email' => $credentials['username'] . '@example.com',
                'password' => password_hash($credentials['password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'is_active' => 1,
                'can_create' => 1,
                'can_edit' => 1,
                'can_delete' => 1
            ]);
            return redirect()->back()->with('error', 'Database was empty. Automatically created initial admin user "' . esc($credentials['username']) . '". Please login with the password you just typed.');
        }

        $user = $userModel->attemptLogin($credentials);

        if ($user && $user['is_active']) {
            session()->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'permissions' => [
                    'can_create' => $user['can_create'],
                    'can_edit' => $user['can_edit'],
                    'can_delete' => $user['can_delete']
                ]
            ]);
            //return redirect()->to('/logistics');
            return redirect()->to('/company-selection');
        }

        return redirect()->back()->with('error', 'Invalid credentials!');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    public function dashboard()
    {
        $data['user'] = session()->get();
        return view('dashboard', $data);
    }
}