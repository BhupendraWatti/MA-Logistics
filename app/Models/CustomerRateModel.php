<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerRateModel extends Model
{
    protected $table = 'customer_rates';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id', 'customer_id', 'customer_name', 'material_category',
        'effective_from', 'effective_to', 'rate', 'created_at', 'updated_at',
    ];

    protected $validationRules = [
        'company_id'      => 'required|is_natural_no_zero',
        'customer_name'   => 'required|min_length[2]|max_length[150]',
        'effective_from'  => 'required|valid_date',
        'effective_to'    => 'permit_empty|valid_date',
        'rate'            => 'required|numeric|greater_than_equal_to[0]',
    ];

    public function findRate(int $companyId, string $customerName, ?string $category, string $bookingDate): ?array
    {
        $customerName = trim($customerName);
        if ($customerName === '' || $bookingDate === '') {
            return null;
        }

        $builder = $this->where('company_id', $companyId)
            ->where('LOWER(customer_name)', strtolower($customerName))
            ->where('effective_from <=', $bookingDate)
            ->groupStart()
                ->where('effective_to >=', $bookingDate)
                ->orWhere('effective_to', null)
            ->groupEnd();

        if (!empty($category)) {
            $builder = $builder->groupStart()
                ->where('LOWER(material_category)', strtolower(trim($category)))
                ->orWhere('material_category', null)
                ->orWhere('material_category', '')
            ->groupEnd();
        }

        return $builder->orderBy('material_category IS NULL', 'ASC', false)
            ->orderBy('effective_from', 'DESC')
            ->first();
    }
}
