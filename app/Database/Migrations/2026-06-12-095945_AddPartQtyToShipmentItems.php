<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPartQtyToShipmentItems extends Migration
{
    public function up()
    {
        $fields = [
            'part_qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => 0,
                'after'      => 'part_no'
            ]
        ];
        $this->forge->addColumn('shipment_items', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('shipment_items', 'part_qty');
    }
}
