<?php

namespace App\Models;

use CodeIgniter\Model;

class AirlineModel extends Model
{
    protected $table      = 'airlines';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'name', 'code', 'is_active'];

    protected $validationRules = [
        'company_id' => 'required|is_natural_no_zero',
        'name'       => 'required|min_length[3]|max_length[150]'
    ];
    
    protected $validationMessages = [
        'company_id' => ['required' => 'Company ID is required.'],
        'name'       => ['required' => 'Airline name is required.']
    ];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->orderBy('name')
                    ->findAll();
    }
}
