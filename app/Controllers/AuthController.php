<?php
namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        return view('auth/login');
    }

    public function attemptLogin()
    {
        $userModel = new UserModel();
        $credentials = [
            'username' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password')
        ];

        $user = $userModel->attemptLogin($credentials);

        if ($user && $user['is_active']) {
            session()->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'branch_id' => $user['branch_id'] ?? 1,
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