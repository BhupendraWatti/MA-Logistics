<?php

namespace App\Models;

use CodeIgniter\Model;

class LookupValueModel extends Model
{
    protected $table      = 'lookup_values';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'type', 'value', 'is_active'];

    /**
     * All valid lookup types.
     */
    public const TYPES = [
        'origin'            => 'Origin',
        'destination'       => 'Destination',
        'mode'              => 'Mode of Transport',
        'material_type'     => 'Material Type',
        'material_category' => 'Material Category',
        'payment_type'      => 'Payment Type',
    ];

    public function getByType(int $companyId, string $type): array
    {
        return $this->where('company_id', $companyId)
                    ->where('type', $type)
                    ->orderBy('value')
                    ->orderBy('value')
                    ->findAll();
    }
}
