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
            // Clean slate: set identity, company-scoped keys populated on selection
            session()->set([
                'user_id'   => (int) $user['id'],
                'username'  => $user['username'],
                'branch_id' => $user['branch_id'] ?? 1,
            ]);
            session()->remove([
                'selected_company_id',
                'selected_company_name',
                'is_root_company',
                'role',
                'permissions',
            ]);
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