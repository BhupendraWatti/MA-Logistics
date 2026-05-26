<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table      = 'customers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id', 'name', 'code', 'email', 'pan', 'pincode', 'city',
        'bill_to', 'consignee', 'payment_type', 'narration',
        'person1_name', 'person1_phone', 'person1_email',
        'person2_name', 'person2_phone', 'person2_email',
        'person3_name', 'person3_phone', 'person3_email',
        'is_active',
    ];

    protected $validationRules = [
        'company_id' => 'required|is_natural_no_zero',
        'name'       => 'required|min_length[3]|max_length[150]',
        'email'      => 'permit_empty|valid_email'
    ];
    
    protected $validationMessages = [
        'company_id' => ['required' => 'Company ID is required.'],
        'name'       => ['required' => 'Customer name is required.']
    ];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->orderBy('name')
                    ->findAll();
    }
}
