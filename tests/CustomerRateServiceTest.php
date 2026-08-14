<?php

namespace Tests;

use App\Models\CustomerRateModel;
use App\Services\CustomerRateService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use PHPUnit\Framework\TestCase;

final class CustomerRateServiceTest extends TestCase
{
    private BaseConnection $db;
    private BaseConnection $adminDb;
    private string $databaseName;

    protected function setUp(): void
    {
        parent::setUp();
        $databaseConfig = config('Database')->default;
        $databaseConfig['database'] = 'mysql';
        $databaseConfig['DBPrefix'] = '';
        $databaseConfig['DBDebug'] = true;
        $this->adminDb = Database::connect($databaseConfig, false);
        $this->databaseName = 'malogistics_test_' . bin2hex(random_bytes(5));
        $this->adminDb->query('CREATE DATABASE `' . $this->databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        $this->db = $this->newDatabase($this->databaseName);
        $this->createSchema($this->db);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        $this->adminDb->query('DROP DATABASE `' . $this->databaseName . '`');
        $this->adminDb->close();
        parent::tearDown();
    }

    public function testCustomerMasterSyncPreservesChangedAndRemovedVersions(): void
    {
        $service = new CustomerRateService($this->db);
        $customerId = $service->createCustomer(1, $this->customerPost([
            ['Delhi', 'Mumbai', 'Steel', 10, '2026-08-01'],
            ['Delhi', 'Pune', '', 12, '2026-08-01'],
        ]));

        $service->updateCustomer(1, $customerId, $this->customerPost([
            ['Delhi', 'Mumbai', 'Steel', 15, '2026-08-14'],
            ['Chennai', 'Pune', '', 20, '2026-08-14'],
        ]));

        $active = $this->db->table('customer_rates')->where('is_active', 1)->get()->getResultArray();
        $history = $this->db->table('customer_rates')->where('is_active', 0)->get()->getResultArray();
        self::assertCount(2, $active);
        self::assertCount(2, $history);
        $activeRates = array_map('floatval', array_column($active, 'rate'));
        sort($activeRates);
        self::assertSame([15.0, 20.0], $activeRates);
        self::assertSame([null, null], array_column($history, 'active_scope_key'));
    }

    public function testRepeatingTheSameSubmissionIsIdempotent(): void
    {
        $service = new CustomerRateService($this->db);
        $post = $this->customerPost([['Delhi', 'Mumbai', '', 10, '2026-08-14']]);
        $customerId = $service->createCustomer(1, $post);
        $service->updateCustomer(1, $customerId, $post);

        self::assertSame(1, $this->db->table('customer_rates')->countAllResults());

        $first = $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 10, 0);
        $second = $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 10, (int) $first['id']);
        self::assertSame((int) $first['id'], (int) $second['id']);
        self::assertSame(1, $this->db->table('customer_rates')->where('is_active', 1)->countAllResults());
    }

    public function testDatabaseRejectsDuplicateActiveScope(): void
    {
        $customerId = $this->insertCustomer(1, 'Test Customer');
        $key = CustomerRateService::scopeKey($customerId, 'Delhi', 'Mumbai', 'Steel');
        $row = $this->rateRow(1, $customerId, 'Test Customer', 'Delhi', 'Mumbai', 'Steel', 10, $key);
        $this->db->table('customer_rates')->insert($row);

        $this->expectException(\Throwable::class);
        $this->db->table('customer_rates')->insert($row);
    }

    public function testExactRouteNeverFallsBackToGenericAndCategoryPrecedenceIsPreserved(): void
    {
        $customerId = $this->insertCustomer(1, 'Test Customer');
        $this->db->table('customer_rates')->insert($this->rateRow(1, $customerId, 'Test Customer', '', '', '', 5,
            CustomerRateService::scopeKey($customerId, '', '', '')));
        $this->db->table('customer_rates')->insert($this->rateRow(1, $customerId, 'Test Customer', 'Delhi', 'Mumbai', '', 10,
            CustomerRateService::scopeKey($customerId, 'Delhi', 'Mumbai', '')));
        $this->db->table('customer_rates')->insert($this->rateRow(1, $customerId, 'Test Customer', 'Delhi', 'Mumbai', 'Steel', 15,
            CustomerRateService::scopeKey($customerId, 'Delhi', 'Mumbai', 'Steel')));

        $model = new CustomerRateModel($this->db);
        self::assertNull($model->findRate(1, 'Test Customer', '', '2026-08-14', 'Delhi', 'Pune'));
        self::assertSame(5.0, (float) $model->findRate(1, 'Test Customer', '', '2026-08-14')['rate']);
        self::assertSame(15.0, (float) $model->findRate(1, 'Test Customer', 'Steel', '2026-08-14', 'DELHI', 'mumbai')['rate']);
        self::assertSame(10.0, (float) $model->findRate(1, 'Test Customer', 'Plastic', '2026-08-14', 'Delhi', 'Mumbai')['rate']);
    }

    public function testTenantBoundariesPreventCrossCompanyLookupAndMutation(): void
    {
        $service = new CustomerRateService($this->db);
        $service->createCustomer(1, $this->customerPost([]));
        $service->createCustomer(2, $this->customerPost([]));
        $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 10);

        $model = new CustomerRateModel($this->db);
        self::assertNull($model->findRate(2, 'Test Customer', '', '2026-08-14', 'Delhi', 'Mumbai'));
        $companyTwo = $service->saveRuntimeRate(2, 'Test Customer', 'Delhi', 'Mumbai', '', 20);
        self::assertSame(2, (int) $companyTwo['company_id']);
        self::assertSame(1, $this->db->table('customer_rates')->where('company_id', 1)->where('is_active', 1)->countAllResults());
        self::assertSame(1, $this->db->table('customer_rates')->where('company_id', 2)->where('is_active', 1)->countAllResults());
    }

    public function testSameDayReplacementWinsAndPastDateUsesClosedVersion(): void
    {
        $service = new CustomerRateService($this->db);
        $service->createCustomer(1, $this->customerPost([]));
        $old = $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 10, 0, '2026-08-01');
        $current = $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 15, (int) $old['id'], '2026-08-14');
        $newest = $service->saveRuntimeRate(1, 'Test Customer', 'Delhi', 'Mumbai', '', 20, (int) $current['id'], '2026-08-14');

        $model = new CustomerRateModel($this->db);
        self::assertSame(10.0, (float) $model->findRate(1, 'Test Customer', '', '2026-08-10', 'Delhi', 'Mumbai')['rate']);
        $today = $model->findRate(1, 'Test Customer', '', '2026-08-14', 'Delhi', 'Mumbai');
        self::assertSame((int) $newest['id'], (int) $today['id']);
        self::assertSame(1, $this->db->table('customer_rates')->where('is_active', 1)->countAllResults());
    }

    public function testStaleIndependentConnectionReceivesConflictAndLeavesOneActiveRow(): void
    {
        $firstDb = $this->newDatabase($this->databaseName);
        $secondDb = $this->newDatabase($this->databaseName);

        try {
            $firstService = new CustomerRateService($firstDb);
            $secondService = new CustomerRateService($secondDb);
            $customerName = 'Connection Customer';
            $firstService->createCustomer(1, ['name' => $customerName]);
            $winner = $firstService->saveRuntimeRate(1, $customerName, 'Delhi', 'Mumbai', '', 10);

            try {
                $secondService->saveRuntimeRate(1, $customerName, 'Delhi', 'Mumbai', '', 20, 0);
                self::fail('A stale save should return a conflict.');
            } catch (\RuntimeException $e) {
                self::assertSame(409, $e->getCode());
            }

            self::assertGreaterThan(0, (int) $winner['id']);
            self::assertSame(1, $firstDb->table('customer_rates')->where('is_active', 1)->countAllResults());
        } finally {
            $secondDb->close();
            $firstDb->close();
        }
    }

    private function newDatabase(string $database): BaseConnection
    {
        $config = config('Database')->default;
        $config['database'] = $database;
        $config['DBPrefix'] = '';
        $config['DBDebug'] = true;
        return Database::connect($config, false);
    }

    private function createSchema(BaseConnection $db): void
    {
        $db->query('CREATE TABLE customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB');
        $db->query('CREATE TABLE customer_rates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            customer_id INT UNSIGNED NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            origin VARCHAR(150) NULL,
            destination VARCHAR(150) NULL,
            material_category VARCHAR(100) NULL,
            effective_from DATE NOT NULL,
            effective_to DATE NULL,
            rate DECIMAL(10,2) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            active_scope_key CHAR(64) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB');
        $db->query('CREATE UNIQUE INDEX uq_customer_rates_active_scope ON customer_rates (company_id, customer_id, active_scope_key)');
    }

    private function customerPost(array $rates): array
    {
        $post = ['name' => 'Test Customer'];
        foreach ($rates as [$origin, $destination, $category, $rate, $effectiveFrom]) {
            $post['rate_origin'][] = $origin;
            $post['rate_destination'][] = $destination;
            $post['rate_material_category'][] = $category;
            $post['rate_value'][] = $rate;
            $post['rate_effective_from'][] = $effectiveFrom;
        }
        return $post;
    }

    private function insertCustomer(int $companyId, string $name): int
    {
        $this->db->table('customers')->insert(['company_id' => $companyId, 'name' => $name]);
        return (int) $this->db->insertID();
    }

    private function rateRow(
        int $companyId,
        int $customerId,
        string $customerName,
        string $origin,
        string $destination,
        string $category,
        float $rate,
        string $scopeKey
    ): array {
        return [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'origin' => $origin !== '' ? $origin : null,
            'destination' => $destination !== '' ? $destination : null,
            'material_category' => $category !== '' ? $category : null,
            'effective_from' => '2026-08-01',
            'effective_to' => null,
            'rate' => $rate,
            'is_active' => 1,
            'active_scope_key' => $scopeKey,
        ];
    }
}
