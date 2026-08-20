<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table         = 'companies';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'name', 'company_name', 'address', 'email', 'mobile', 'gstin', 'gst_no', 'pan', 'pan_no', 'sac_code',
        'cgst_rate', 'sgst_rate', 'igst_rate', 'terms_conditions', 'logo_path', 'logo_image', 'signature_path', 'signature_image',
        'bank_name', 'branch_name', 'branch_address', 'ifsc_code', 'account_number', 'misc_code',
    ];
}