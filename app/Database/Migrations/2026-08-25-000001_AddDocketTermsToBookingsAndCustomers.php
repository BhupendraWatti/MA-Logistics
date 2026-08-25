<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDocketTermsToBookingsAndCustomers extends Migration
{
    public function up()
    {
        $bookingFields = [
            'docket_terms' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'remarks',
            ],
        ];
        if ($this->db->tableExists('bookings') && !$this->db->fieldExists('docket_terms', 'bookings')) {
            $this->forge->addColumn('bookings', $bookingFields);
        }

        $customerFields = [
            'default_terms' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'address',
            ],
        ];
        if ($this->db->tableExists('customers') && !$this->db->fieldExists('default_terms', 'customers')) {
            $this->forge->addColumn('customers', $customerFields);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bookings') && $this->db->fieldExists('docket_terms', 'bookings')) {
            $this->forge->dropColumn('bookings', 'docket_terms');
        }
        if ($this->db->tableExists('customers') && $this->db->fieldExists('default_terms', 'customers')) {
            $this->forge->dropColumn('customers', 'default_terms');
        }
    }
}
