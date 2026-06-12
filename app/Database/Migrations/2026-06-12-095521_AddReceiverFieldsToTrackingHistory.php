<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReceiverFieldsToTrackingHistory extends Migration
{
    public function up()
    {
        $fields = [
            'receiver_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'remarks'
            ],
            'receiver_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'receiver_name'
            ],
            'receiver_company' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'receiver_phone'
            ],
        ];
        $this->forge->addColumn('tracking_history', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('tracking_history', 'receiver_name');
        $this->forge->dropColumn('tracking_history', 'receiver_phone');
        $this->forge->dropColumn('tracking_history', 'receiver_company');
    }
}
