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

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->findAll();
    }
}
