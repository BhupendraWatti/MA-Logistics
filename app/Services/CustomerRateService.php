<?php

namespace App\Services;

use App\Models\CustomerModel;
use CodeIgniter\Database\BaseConnection;

class CustomerRateService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public static function scopeKey(int $customerId, string $origin, string $destination, ?string $category): string
    {
        return hash('sha256', implode('|', [
            $customerId,
            self::normalize($origin),
            self::normalize($destination),
            self::normalize($category),
        ]));
    }

    public function createCustomer(int $companyId, array $post): int
    {
        $this->db->transBegin();
        try {
            $post['company_id'] = $companyId;
            $model = new CustomerModel($this->db);
            if (!$model->insert($post)) {
                throw new \InvalidArgumentException(implode(', ', $model->errors()));
            }

            $customerId = (int) $model->getInsertID();
            $this->lockCustomer($companyId, $customerId);
            $this->syncCustomerRates($companyId, $customerId, trim((string) ($post['name'] ?? '')), $post);
            $this->commitOrFail();

            return $customerId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function updateCustomer(int $companyId, int $customerId, array $post): void
    {
        $this->db->transBegin();
        try {
            $customer = $this->lockCustomer($companyId, $customerId);
            if (!$customer) {
                throw new \RuntimeException('Customer not found.', 404);
            }

            $post['company_id'] = $companyId;
            $model = new CustomerModel($this->db);
            if (!$model->where('id', $customerId)->where('company_id', $companyId)->set($post)->update()) {
                throw new \InvalidArgumentException(implode(', ', $model->errors()));
            }

            $customerName = trim((string) ($post['name'] ?? $customer['name']));
            $this->db->table('customer_rates')
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->update(['customer_name' => $customerName]);
            $this->syncCustomerRates($companyId, $customerId, $customerName, $post);
            $this->commitOrFail();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function saveRuntimeRate(
        int $companyId,
        string $customerName,
        string $origin,
        string $destination,
        ?string $category,
        float $rate,
        int $expectedRateId = 0,
        ?string $effectiveFrom = null
    ): array {
        $origin = trim($origin);
        $destination = trim($destination);
        $category = trim((string) $category);
        $effectiveFrom = $effectiveFrom ?: date('Y-m-d');
        if ($companyId <= 0 || trim($customerName) === '' || $origin === '' || $destination === '' || $rate <= 0) {
            throw new \InvalidArgumentException('Customer, origin, destination, and item rate are required.');
        }

        $customerId = 0;
        $scopeKey = '';
        $this->db->transBegin();
        try {
            $customer = $this->lockCustomerByName($companyId, $customerName);
            if (!$customer) {
                throw new \RuntimeException('Customer not found in Customer Master.', 404);
            }

            $customerId = (int) $customer['id'];
            $scopeKey = self::scopeKey($customerId, $origin, $destination, $category);
            $active = $this->findActiveByScope($companyId, $customerId, $scopeKey);

            if ($active && $this->sameRate($active, $rate)) {
                $this->commitOrFail();
                return $active;
            }

            if ($active && (int) $active['id'] !== $expectedRateId) {
                throw new \RuntimeException('This customer rate was changed by another user. Reload the rate and try again.', 409);
            }
            if (!$active && $expectedRateId > 0) {
                throw new \RuntimeException('This customer rate was removed by another user. Reload the rate and try again.', 409);
            }

            $row = $this->replaceActiveRate(
                $companyId,
                $customer,
                $origin,
                $destination,
                $category,
                $rate,
                $effectiveFrom,
                $active
            );
            $this->commitOrFail();

            return $row;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if ($customerId > 0 && $scopeKey !== '' && $this->isUniqueConflict($e)) {
                $winner = $this->findActiveByScope($companyId, $customerId, $scopeKey);
                if ($winner && $this->sameRate($winner, $rate)) {
                    return $winner;
                }
                throw new \RuntimeException('This customer rate was changed by another user. Reload the rate and try again.', 409, $e);
            }
            throw $e;
        }
    }

    private function syncCustomerRates(int $companyId, int $customerId, string $customerName, array $post): void
    {
        if ($customerName === '') {
            throw new \InvalidArgumentException('Customer name is required.');
        }

        $activeRows = $this->db->table('customer_rates')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->get()->getResultArray();
        $activeByScope = [];
        foreach ($activeRows as $row) {
            $key = (string) ($row['active_scope_key'] ?: self::scopeKey(
                $customerId,
                (string) ($row['origin'] ?? ''),
                (string) ($row['destination'] ?? ''),
                $row['material_category'] ?? null
            ));
            $activeByScope[$key] = $row;
        }

        $submitted = $this->submittedRates($post, $customerId);
        foreach ($submitted as $scopeKey => $rateData) {
            $existing = $activeByScope[$scopeKey] ?? null;
            if ($existing
                && $this->sameRate($existing, $rateData['rate'])
                && (string) $existing['effective_from'] === $rateData['effective_from']) {
                unset($activeByScope[$scopeKey]);
                continue;
            }

            $this->replaceActiveRate(
                $companyId,
                ['id' => $customerId, 'name' => $customerName],
                $rateData['origin'],
                $rateData['destination'],
                $rateData['category'],
                $rateData['rate'],
                $rateData['effective_from'],
                $existing
            );
            unset($activeByScope[$scopeKey]);
        }

        foreach ($activeByScope as $removed) {
            $this->closeRate($removed, date('Y-m-d'));
        }
    }

    private function submittedRates(array $post, int $customerId): array
    {
        $origins = $post['rate_origin'] ?? [];
        $destinations = $post['rate_destination'] ?? [];
        $rates = $post['rate_value'] ?? [];
        $categories = $post['rate_material_category'] ?? [];
        $effectiveDates = $post['rate_effective_from'] ?? [];
        $submitted = [];

        foreach ($rates as $index => $value) {
            $rate = (float) $value;
            $origin = trim((string) ($origins[$index] ?? ''));
            $destination = trim((string) ($destinations[$index] ?? ''));
            if ($rate <= 0 || $origin === '' || $destination === '') {
                continue;
            }
            $category = trim((string) ($categories[$index] ?? ''));
            $effectiveFrom = trim((string) ($effectiveDates[$index] ?? '')) ?: date('Y-m-d');
            if (!$this->validDate($effectiveFrom)) {
                throw new \InvalidArgumentException('Each customer rate must have a valid effective date.');
            }
            $scopeKey = self::scopeKey($customerId, $origin, $destination, $category);
            if (isset($submitted[$scopeKey])) {
                throw new \InvalidArgumentException('Only one active rate may be submitted for the same route and category.');
            }
            $submitted[$scopeKey] = compact('origin', 'destination', 'category', 'rate', 'effectiveFrom');
            $submitted[$scopeKey]['effective_from'] = $submitted[$scopeKey]['effectiveFrom'];
            unset($submitted[$scopeKey]['effectiveFrom']);
        }

        return $submitted;
    }

    private function replaceActiveRate(
        int $companyId,
        array $customer,
        string $origin,
        string $destination,
        string $category,
        float $rate,
        string $effectiveFrom,
        ?array $active
    ): array {
        if (!$this->validDate($effectiveFrom)) {
            throw new \InvalidArgumentException('The customer rate effective date is invalid.');
        }
        if ($active && $effectiveFrom < (string) $active['effective_from']) {
            throw new \InvalidArgumentException('A new rate cannot take effect before the current version.');
        }
        if ($active) {
            $this->closeRate($active, $effectiveFrom);
        }

        $scopeKey = self::scopeKey((int) $customer['id'], $origin, $destination, $category);
        $data = [
            'company_id'       => $companyId,
            'customer_id'      => (int) $customer['id'],
            'customer_name'    => (string) $customer['name'],
            'origin'           => trim($origin),
            'destination'      => trim($destination),
            'material_category'=> $category !== '' ? trim($category) : null,
            'effective_from'   => $effectiveFrom,
            'effective_to'     => null,
            'rate'             => $rate,
            'is_active'        => 1,
            'active_scope_key' => $scopeKey,
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        if (!$this->db->table('customer_rates')->insert($data)) {
            throw new \RuntimeException('Unable to save the customer rate.');
        }
        $data['id'] = (int) $this->db->insertID();

        return $data;
    }

    private function closeRate(array $row, string $replacementDate): void
    {
        $currentFrom = (string) ($row['effective_from'] ?? $replacementDate);
        $effectiveTo = $currentFrom < $replacementDate
            ? date('Y-m-d', strtotime($replacementDate . ' -1 day'))
            : $currentFrom;
        $this->db->table('customer_rates')->where('id', (int) $row['id'])->update([
            'is_active'        => 0,
            'active_scope_key' => null,
            'effective_to'     => $effectiveTo,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    private function lockCustomer(int $companyId, int $customerId): ?array
    {
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND id = ?';
        if (stripos($this->db->DBDriver, 'SQLite') === false) {
            $sql .= ' FOR UPDATE';
        }
        return $this->db->query($sql, [$companyId, $customerId])->getRowArray() ?: null;
    }

    private function lockCustomerByName(int $companyId, string $customerName): ?array
    {
        $sql = 'SELECT * FROM customers WHERE company_id = ? AND LOWER(name) = ?';
        if (stripos($this->db->DBDriver, 'SQLite') === false) {
            $sql .= ' FOR UPDATE';
        }
        return $this->db->query($sql, [$companyId, self::normalize($customerName)])->getRowArray() ?: null;
    }

    private function findActiveByScope(int $companyId, int $customerId, string $scopeKey): ?array
    {
        return $this->db->table('customer_rates')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('active_scope_key', $scopeKey)
            ->where('is_active', 1)
            ->get()->getRowArray() ?: null;
    }

    private function commitOrFail(): void
    {
        if ($this->db->transStatus() === false || !$this->db->transCommit()) {
            throw new \RuntimeException('Customer rate transaction failed.');
        }
    }

    private function sameRate(array $row, float $rate): bool
    {
        return abs((float) ($row['rate'] ?? 0) - $rate) < 0.00001;
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isUniqueConflict(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'duplicate') || str_contains($message, 'unique constraint');
    }

    private static function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
