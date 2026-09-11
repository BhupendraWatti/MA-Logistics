<?php

namespace App\Models;

use CodeIgniter\Model;

class UserCompanyAccessModel extends Model
{
    protected $table         = 'user_company_access';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'company_id',
        'role',
        'can_create',
        'can_edit',
        'can_delete',
        'is_active',
        'created_at',
        'updated_at',
    ];

    /**
     * Get all companies assigned to a user.
     */
    public function getUserCompanies(int $userId, bool $activeOnly = true): array
    {
        $builder = $this->db->table($this->table . ' uca')
            ->select('c.id, c.name, c.is_root, c.email, c.mobile, c.gstin, uca.role, uca.can_create, uca.can_edit, uca.can_delete, uca.is_active as access_active')
            ->join('companies c', 'c.id = uca.company_id')
            ->where('uca.user_id', $userId);

        if ($activeOnly) {
            $builder->where('uca.is_active', 1);
        }

        $companies = $builder->orderBy('c.is_root', 'DESC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($companies as &$c) {
            if (!isset($c['name']) && isset($c['company_name'])) {
                $c['name'] = $c['company_name'];
            }
        }
        unset($c);

        return $companies;
    }

    /**
     * Get specific access record for user in a company.
     */
    public function getUserAccess(int $userId, int $companyId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Assign or update company access for a user.
     */
    public function assignCompany(int $userId, int $companyId, array $data): bool
    {
        $existing = $this->getUserAccess($userId, $companyId);
        $payload = [
            'user_id'    => $userId,
            'company_id' => $companyId,
            'role'       => $data['role'] ?? 'user',
            'can_create' => isset($data['can_create']) ? (int) $data['can_create'] : 0,
            'can_edit'   => isset($data['can_edit']) ? (int) $data['can_edit'] : 0,
            'can_delete' => isset($data['can_delete']) ? (int) $data['can_delete'] : 0,
            'is_active'  => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ];

        if ($existing) {
            return (bool) $this->update($existing['id'], $payload);
        }

        return (bool) $this->insert($payload);
    }

    /**
     * Revoke company access for a user.
     */
    public function revokeCompany(int $userId, int $companyId): bool
    {
        return (bool) $this->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->delete();
    }

    /**
     * Get all company assignments for a user, including company names.
     */
    public function getUserAssignmentsWithCompanies(int $userId): array
    {
        return $this->db->table($this->table . ' uca')
            ->select('uca.*, c.name as company_name, c.is_root')
            ->join('companies c', 'c.id = uca.company_id')
            ->where('uca.user_id', $userId)
            ->orderBy('c.is_root', 'DESC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all users assigned to a specific company.
     */
    public function getCompanyUsers(int $companyId): array
    {
        return $this->db->table($this->table . ' uca')
            ->select('u.id as user_id, u.username, u.email, u.is_active as user_active, uca.role, uca.can_create, uca.can_edit, uca.can_delete, uca.is_active as access_active')
            ->join('users u', 'u.id = uca.user_id')
            ->where('uca.company_id', $companyId)
            ->orderBy('u.username', 'ASC')
            ->get()
            ->getResultArray();
    }
}
