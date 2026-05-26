<?php

namespace App\Models;

use CodeIgniter\Model;

class TransporterModel extends Model
{
    protected $table      = 'transporters';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'name', 'mobile', 'is_active'];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->findAll();
    }
}
