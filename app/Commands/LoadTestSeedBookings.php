<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Seed synthetic bookings for pagination / load testing.
 *
 *   php spark loadtest:seed --count 10000 --company 1
 *   php spark loadtest:purge --company 1
 */
class LoadTestSeedBookings extends BaseCommand
{
    protected $group       = 'LoadTest';
    protected $name        = 'loadtest:seed';
    protected $description = 'Insert LOADTEST-* bookings for performance testing (batch insert).';
    protected $usage       = 'loadtest:seed [--count 10000] [--company 1] [--batch 500]';
    protected $options     = [
        'count'   => 'Number of bookings to insert (default: 10000)',
        'company' => 'company_id to assign (default: 1)',
        'batch'   => 'Rows per insertBatch call (default: 500)',
    ];

    public function run(array $params)
    {
        $count   = (int) (CLI::getOption('count') ?? ($params[0] ?? 10000));
        $company = (int) (CLI::getOption('company') ?? 1);
        $batch   = (int) (CLI::getOption('batch') ?? 500);

        if ($count < 1 || $count > 500000) {
            CLI::error('count must be between 1 and 500000');
            return EXIT_ERROR;
        }

        $db = \Config\Database::connect();

        $userRow = $db->table('users')->select('id')->limit(1)->get()->getRowArray();
        if (!$userRow) {
            CLI::error('No users table row found. Run migrations/seeds first.');
            return EXIT_ERROR;
        }
        $userId = (int) $userRow['id'];

        $existing = $db->table('bookings')
            ->where('company_id', $company)
            ->like('awb_no', 'LOADTEST-', 'after')
            ->countAllResults();

        CLI::write("Company {$company}, existing LOADTEST rows: {$existing}", 'yellow');
        CLI::write("Inserting {$count} bookings in batches of {$batch}...", 'green');

        $t0 = microtime(true);
        $inserted = 0;
        $baseSeq  = (int) (microtime(true) * 1000) % 1000000;

        for ($offset = 0; $offset < $count; $offset += $batch) {
            $chunk = min($batch, $count - $offset);
            $rows  = [];
            $now   = date('Y-m-d H:i:s');

            for ($i = 0; $i < $chunk; $i++) {
                $n = $baseSeq + $offset + $i;
                $rows[] = [
                    'awb_no'           => 'LOADTEST-' . $n,
                    'company_id'       => $company,
                    'branch_id'        => 1,
                    'booking_date'     => date('Y-m-d', strtotime("-{$n} days")),
                    'origin'           => 'BOM (Mumbai)',
                    'destination'      => 'DEL (Delhi)',
                    'mode_transport'   => 'Surface',
                    'material_type'    => 'General Cargo',
                    'material_category'=> 'LoadTest',
                    'material_details' => 'Synthetic load-test row',
                    'status'           => 'Booked',
                    'total_pieces'     => 1,
                    'created_by'       => $userId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }

            $db->table('bookings')->insertBatch($rows);
            $inserted += $chunk;

            if ($inserted % 5000 === 0 || $inserted === $count) {
                CLI::write("  … {$inserted} / {$count}", 'cyan');
            }
        }

        $elapsed = round(microtime(true) - $t0, 2);
        $total   = $db->table('bookings')->where('company_id', $company)->countAllResults();

        CLI::write("Done: inserted {$inserted} rows in {$elapsed}s. Company {$company} total bookings: {$total}", 'green');
        return EXIT_SUCCESS;
    }
}
