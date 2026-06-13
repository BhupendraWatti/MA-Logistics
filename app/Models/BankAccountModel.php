<?php

namespace App\Models;

use CodeIgniter\Model;

class BankAccountModel extends Model
{
    protected $table      = 'bank_accounts';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id', 'account_name', 'bank_name', 'branch_name', 
        'branch_address', 'ifsc_code', 'account_number', 'misc_code', 'is_default'
    ];

    protected $validationRules = [
        'company_id'     => 'required|is_natural_no_zero',
        'bank_name'      => 'required|min_length[2]|max_length[100]',
        'account_number' => 'required|min_length[5]|max_length[50]',
    ];
    
    protected $validationMessages = [
        'company_id'     => ['required' => 'Company is required.'],
        'bank_name'      => ['required' => 'Bank name is required.'],
        'account_number' => ['required' => 'Account number is required.'],
    ];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
                    ->orderBy('is_default', 'DESC')
                    ->orderBy('bank_name', 'ASC')
                    ->findAll();
    }
}
