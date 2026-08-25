<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContentsToShipmentItems extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('contents', 'shipment_items')) {
            $this->forge->addColumn('shipment_items', [
                'contents' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'part_no',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('contents', 'shipment_items')) {
            $this->forge->dropColumn('shipment_items', 'contents');
        }
    }
}
