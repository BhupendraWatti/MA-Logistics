<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMiscChargesToShipmentItems extends Migration
{
    public function up()
    {
        $fields = [
            'misc_charges' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'after'      => 'service_charges'
            ],
            'misc_charges_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'default'    => 'Misc Charges',
                'after'      => 'misc_charges'
            ]
        ];
        $this->forge->addColumn('shipment_items', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('shipment_items', ['misc_charges', 'misc_charges_name']);
    }
}
