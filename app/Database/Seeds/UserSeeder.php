<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;

class UserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        // Clean up existing user if any
        $userModel->where('username', 'admin')->delete();

        $data = [
            'username'   => 'admin',
            'email'      => 'admin@malogistics.com',
            'password'   => 'password', // UserModel hashes this automatically in beforeInsert!
            'role'       => 'admin',
            'is_active'  => 1,
            'can_create' => 1,
            'can_edit'   => 1,
            'can_delete' => 1,
        ];

        $userModel->insert($data);
    }
}
