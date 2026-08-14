<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceTemplateModel extends Model
{
    protected $table = 'invoice_templates';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id',
        'name',
        'gst_type',
        'prefix',
        'is_active',
    ];

    protected $validationRules = [
        'company_id' => 'required|is_natural_no_zero',
        'name'       => 'required|min_length[2]|max_length[100]',
        'gst_type'   => 'required|in_list[gst,non_gst]',
        'prefix'     => 'required|min_length[1]|max_length[30]',
    ];

    public function getByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
            ->orderBy('gst_type', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function getActiveByCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('gst_type', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
