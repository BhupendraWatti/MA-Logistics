<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstStateToCustomers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('customers', [
            'gst_state' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'pan', // placing it reasonably
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('customers', 'gst_state');
    }
}
