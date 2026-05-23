<?php
namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'username', 'email', 'password', 'role', 'is_active', 
        'can_create', 'can_edit', 'can_delete', 'created_at'
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (array_key_exists('password', $data['data'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function attemptLogin($credentials)
    {
        $user = $this->where('username', $credentials['username'])
                     ->orWhere('email', $credentials['username'])
                     ->first();

        if ($user && password_verify($credentials['password'], $user['password'])) {
            return $user;
        }
        return false;
    }
}