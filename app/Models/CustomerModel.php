<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table      = 'customers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id', 'name', 'code', 'email', 'pan', 'gst_state', 'gst_number', 'pincode', 'city', 'state', 'country', 'address', 'default_terms',
        'bill_to', 'consignee', 'payment_type', 'currency', 'tds_percentage', 'narration',
        'person1_name', 'person1_phone', 'person1_email',
        'person2_name', 'person2_phone', 'person2_email',
        'person3_name', 'person3_phone', 'person3_email',
        'operation_contact_name', 'operation_contact_number', 'operation_contact_email',
        'purchase_contact_name', 'purchase_contact_number', 'purchase_contact_email',
        'sales_contact_name', 'sales_contact_number', 'sales_contact_email',
        'plant_head_contact_name', 'plant_head_contact_number', 'plant_head_contact_email',
        'billing_contact_name', 'billing_contact_number', 'billing_contact_email',
        'mis_email_ids', 'mis_cc_email_ids',
        'other_1', 'other_2', 'other_3', 'other_4',
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
