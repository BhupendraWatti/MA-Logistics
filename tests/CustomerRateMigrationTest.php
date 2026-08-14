<?php

namespace Tests;

require_once APPPATH . 'Database/Migrations/2026-08-14-000004_VersionCustomerRates.php';

use App\Database\Migrations\VersionCustomerRates;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use PHPUnit\Framework\TestCase;

final class CustomerRateMigrationTest extends TestCase
{
    private BaseConnection $adminDb;
    private BaseConnection $db;
    private string $databaseName;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database')->default;
        $config['database'] = 'mysql';
        $config['DBPrefix'] = '';
        $config['DBDebug'] = true;
        $this->adminDb = Database::connect($config, false);
        $this->databaseName = 'malogistics_migration_test_' . bin2hex(random_bytes(5));
        $this->adminDb->query('CREATE DATABASE `' . $this->databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        $config['database'] = $this->databaseName;
        $this->db = Database::connect($config, false);
    }

    protected function tearDown(): void
    {
        $this->db->close();
        $this->adminDb->query('DROP DATABASE `' . $this->databaseName . '`');
        $this->adminDb->close();
        parent::tearDown();
    }

    public function testMigrationBackfillsOneNewestActiveVersionWithoutDeletingHistory(): void
    {
        $this->db->query('CREATE TABLE customer_rates (
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
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB');

        foreach ([
            ['effective_from' => '2026-07-01', 'rate' => 10],
            ['effective_from' => '2026-08-01', 'rate' => 15],
            ['effective_from' => '2026-08-01', 'rate' => 20],
        ] as $row) {
            $this->db->table('customer_rates')->insert($row + [
                'company_id' => 1,
                'customer_id' => 7,
                'customer_name' => 'Test Customer',
                'origin' => 'Delhi',
                'destination' => 'Mumbai',
                'material_category' => 'Steel',
            ]);
        }

        (new VersionCustomerRates(Database::forge($this->db)))->up();

        $rows = $this->db->table('customer_rates')->orderBy('id', 'ASC')->get()->getResultArray();
        self::assertCount(3, $rows);
        self::assertSame(1, (int) $rows[2]['is_active']);
        self::assertNotEmpty($rows[2]['active_scope_key']);
        self::assertSame(0, (int) $rows[0]['is_active']);
        self::assertSame(0, (int) $rows[1]['is_active']);
        self::assertNull($rows[0]['active_scope_key']);
        self::assertNotEmpty($rows[0]['effective_to']);

        $duplicate = $rows[2];
        unset($duplicate['id']);
        $this->expectException(\Throwable::class);
        $this->db->table('customer_rates')->insert($duplicate);
    }
}
