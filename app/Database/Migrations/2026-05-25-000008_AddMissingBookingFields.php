<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingBookingFields extends Migration
{
    public function up()
    {
        $fields = [
            'total_weight' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'after'      => 'total_pieces',
            ],
            'volumetric_formula' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 6000,
                'after'      => 'total_weight',
            ],
            'gst_applied' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'volumetric_formula',
            ],
            'payment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'gst_applied',
            ],
            'narration' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'payment_type',
            ],
        ];

        $this->forge->addColumn('bookings', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['total_weight', 'volumetric_formula', 'gst_applied', 'payment_type', 'narration']);
    }
}
