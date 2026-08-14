<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerRateModel extends Model
{
    protected $table = 'customer_rates';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id', 'customer_id', 'customer_name', 'origin', 'destination', 'material_category',
        'effective_from', 'effective_to', 'rate', 'is_active', 'active_scope_key', 'created_at', 'updated_at',
    ];

    protected $validationRules = [
        'company_id'      => 'required|is_natural_no_zero',
        'customer_name'   => 'required|min_length[2]|max_length[150]',
        'effective_from'  => 'required|valid_date',
        'effective_to'    => 'permit_empty|valid_date',
        'rate'            => 'required|numeric|greater_than_equal_to[0]',
    ];

    public function findRate(
        int $companyId,
        string $customerName,
        ?string $category,
        string $bookingDate,
        ?string $origin = null,
        ?string $destination = null
    ): ?array
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
        } else {
            $builder = $builder->groupStart()
                ->where('material_category', null)
                ->orWhere('material_category', '')
            ->groupEnd();
        }

        $origin = trim((string) $origin);
        $destination = trim((string) $destination);
        if (($origin === '') xor ($destination === '')) {
            return null;
        }

        if ($origin !== '' && $destination !== '') {
            $builder = $builder
                ->where('LOWER(origin)', strtolower($origin))
                ->where('LOWER(destination)', strtolower($destination));
        } else {
            $builder = $builder
                ->groupStart()->where('origin', null)->orWhere('origin', '')->groupEnd()
                ->groupStart()->where('destination', null)->orWhere('destination', '')->groupEnd();
        }

        return $builder
            ->orderBy("CASE WHEN material_category IS NULL OR material_category = '' THEN 1 ELSE 0 END", 'ASC', false)
            ->orderBy('is_active', 'DESC')
            ->orderBy('effective_from', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
