<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class VersionCustomerRates extends Migration
{
    private const INDEX_NAME = 'uq_customer_rates_active_scope';

    public function up()
    {
        if (!$this->db->tableExists('customer_rates')) {
            return;
        }

        $fields = $this->db->getFieldNames('customer_rates');
        $add = [];

        if (!in_array('is_active', $fields, true)) {
            $add['is_active'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'rate',
            ];
        }

        if (!in_array('active_scope_key', $fields, true)) {
            $add['active_scope_key'] = [
                'type'       => 'CHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'is_active',
            ];
        }

        if ($add !== []) {
            $this->forge->addColumn('customer_rates', $add);
        }

        $this->backfillVersions();

        if (!$this->indexExists(self::INDEX_NAME)) {
            $this->db->query(
                'CREATE UNIQUE INDEX ' . self::INDEX_NAME
                . ' ON customer_rates (company_id, customer_id, active_scope_key)'
            );
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('customer_rates')) {
            return;
        }

        if ($this->indexExists(self::INDEX_NAME)) {
            if (stripos($this->db->DBDriver, 'MySQL') !== false) {
                $this->db->query('DROP INDEX ' . self::INDEX_NAME . ' ON customer_rates');
            } else {
                $this->db->query('DROP INDEX ' . self::INDEX_NAME);
            }
        }

        $fields = $this->db->getFieldNames('customer_rates');
        if (in_array('active_scope_key', $fields, true)) {
            $this->forge->dropColumn('customer_rates', 'active_scope_key');
        }
        if (in_array('is_active', $fields, true)) {
            $this->forge->dropColumn('customer_rates', 'is_active');
        }
    }

    private function backfillVersions(): void
    {
        $rows = $this->db->table('customer_rates')
            ->orderBy('effective_from', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $activeScopes = [];
        foreach ($rows as $row) {
            $customerId = (int) ($row['customer_id'] ?? 0);
            if ($customerId <= 0) {
                $customer = $this->db->table('customers')
                    ->select('id')
                    ->where('company_id', (int) $row['company_id'])
                    ->where('LOWER(name)', strtolower(trim((string) $row['customer_name'])))
                    ->get()->getRowArray();
                $customerId = (int) ($customer['id'] ?? 0);
                if ($customerId <= 0) {
                    $this->db->table('customer_rates')->where('id', $row['id'])->update([
                        'is_active'        => 0,
                        'active_scope_key' => null,
                        'effective_to'     => $row['effective_to'] ?: date('Y-m-d'),
                    ]);
                    continue;
                }
                $this->db->table('customer_rates')->where('id', $row['id'])->update(['customer_id' => $customerId]);
                $row['customer_id'] = $customerId;
            }

            $scopeKey = hash('sha256', implode('|', [
                $customerId,
                $this->normalize($row['origin'] ?? ''),
                $this->normalize($row['destination'] ?? ''),
                $this->normalize($row['material_category'] ?? ''),
            ]));
            $tenantScope = (int) $row['company_id'] . '|' . $customerId . '|' . $scopeKey;
            $alreadyClosed = !empty($row['effective_to']);

            if (!$alreadyClosed && !isset($activeScopes[$tenantScope])) {
                $activeScopes[$tenantScope] = $row;
                $this->db->table('customer_rates')->where('id', $row['id'])->update([
                    'is_active'       => 1,
                    'active_scope_key' => $scopeKey,
                ]);
                continue;
            }

            $effectiveTo = $row['effective_to'] ?? null;
            if (!$alreadyClosed) {
                $winnerFrom = (string) ($activeScopes[$tenantScope]['effective_from'] ?? date('Y-m-d'));
                $rowFrom = (string) ($row['effective_from'] ?? $winnerFrom);
                $effectiveTo = $rowFrom < $winnerFrom
                    ? date('Y-m-d', strtotime($winnerFrom . ' -1 day'))
                    : $winnerFrom;
            }

            $this->db->table('customer_rates')->where('id', $row['id'])->update([
                'is_active'        => 0,
                'active_scope_key' => null,
                'effective_to'     => $effectiveTo,
            ]);
        }
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function indexExists(string $name): bool
    {
        foreach ($this->db->getIndexData('customer_rates') as $indexName => $index) {
            if (strcasecmp((string) $indexName, $name) === 0) {
                return true;
            }
            if (isset($index->name) && strcasecmp((string) $index->name, $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
