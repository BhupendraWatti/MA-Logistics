<?php

namespace App\Models;

use CodeIgniter\Model;

class DocketSeriesModel extends Model
{
    protected $table = 'docket_series';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id',
        'name',
        'prefix',
        'entry_mode',
        'current_number',
        'is_active',
    ];

    protected $validationRules = [
        'company_id'  => 'required|is_natural_no_zero',
        'name'        => 'required|min_length[2]|max_length[100]',
        'prefix'      => 'required|min_length[1]|max_length[30]',
        'entry_mode'  => 'required|in_list[auto,manual]',
    ];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
            ->orderBy('entry_mode', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function getActiveByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('entry_mode', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
