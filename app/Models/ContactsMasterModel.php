<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactsMasterModel extends Model
{
    protected $table            = 'contacts_master';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['company_id', 'customer_id', 'name', 'phone', 'email', 'contact_type'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByCompany($companyId)
    {
        return $this->where('company_id', $companyId)->orderBy('name', 'ASC')->findAll();
    }
}
