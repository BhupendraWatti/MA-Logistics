<?php

namespace App\Models;

use CodeIgniter\Model;

class AirlineModel extends Model
{
    protected $table      = 'airlines';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'name', 'code', 'is_active'];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->findAll();
    }
}
