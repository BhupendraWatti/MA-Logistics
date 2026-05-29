<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrackingHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'awb_no' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'current_location' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'event_date' => [
                'type'       => 'DATE',
            ],
            'event_time' => [
                'type'       => 'TIME',
            ],
            'remarks' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'proof_image' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
            'deleted_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tracking_history');
    }

    public function down()
    {
        $this->forge->dropTable('tracking_history');
    }
}
