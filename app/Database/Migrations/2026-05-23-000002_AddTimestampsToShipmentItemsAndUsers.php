<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimestampsToShipmentItemsAndUsers extends Migration
{
    public function up()
    {
        // 1. Alter shipment_items table
        $shipmentFields = [
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'service_charges'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_at'
            ],
        ];
        $this->forge->addColumn('shipment_items', $shipmentFields);

        // 2. Alter users table
        $userFields = [
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'can_delete'
            ],
        ];
        $this->forge->addColumn('users', $userFields);
    }

    public function down()
    {
        $this->forge->dropColumn('shipment_items', ['created_at', 'updated_at']);
        $this->forge->dropColumn('users', ['created_at']);
    }
}
