<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table         = 'companies';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'name', 'address', 'email', 'mobile', 'gstin', 'pan', 'sac_code',
        'cgst_rate', 'sgst_rate', 'igst_rate', 'terms_conditions', 'signature_path',
    ];
}