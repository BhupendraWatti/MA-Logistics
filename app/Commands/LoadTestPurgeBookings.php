<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Remove LOADTEST-* bookings created by loadtest:seed.
 *
 *   php spark loadtest:purge --company 1
 */
class LoadTestPurgeBookings extends BaseCommand
{
    protected $group       = 'LoadTest';
    protected $name        = 'loadtest:purge';
    protected $description = 'Delete all bookings with awb_no LIKE LOADTEST-% for a company.';
    protected $usage       = 'loadtest:purge [--company 1]';
    protected $options     = [
        'company' => 'company_id scope (default: 1)',
    ];

    public function run(array $params)
    {
        $company = (int) (CLI::getOption('company') ?? 1);
        $db      = \Config\Database::connect();

        $ids = $db->table('bookings')
            ->select('id')
            ->where('company_id', $company)
            ->like('awb_no', 'LOADTEST-', 'after')
            ->get()
            ->getResultArray();

        if (empty($ids)) {
            CLI::write('No LOADTEST bookings to purge.', 'yellow');
            return EXIT_SUCCESS;
        }

        $idList = array_column($ids, 'id');
        CLI::write('Purging ' . count($idList) . ' LOADTEST bookings…', 'yellow');

        $db->transStart();
        foreach (array_chunk($idList, 500) as $chunk) {
            $db->table('tracking_history')->whereIn('booking_id', $chunk)->delete();
            $db->table('shipment_items')->whereIn('booking_id', $chunk)->delete();
            $db->table('sales_charges')->whereIn('booking_id', $chunk)->delete();
            $db->table('bookings')->whereIn('id', $chunk)->delete();
        }
        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Purge transaction failed.');
            return EXIT_ERROR;
        }

        CLI::write('Purge complete.', 'green');
        return EXIT_SUCCESS;
    }
}
