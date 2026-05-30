<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstAndSignatureToBookings extends Migration
{
    public function up()
    {
        $fields = [
            'gstin'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'payment_type'],
            'pan'              => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true, 'after' => 'gstin'],
            'sac_code'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'pan'],
            'cgst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '9.00', 'null' => false, 'after' => 'sac_code'],
            'sgst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '9.00', 'null' => false, 'after' => 'cgst_rate'],
            'igst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '9.00', 'null' => false, 'after' => 'sgst_rate'],
            'signature_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'igst_rate'],
        ];
        $this->forge->addColumn('bookings', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', [
            'gstin', 'pan', 'sac_code', 'cgst_rate', 'sgst_rate', 'igst_rate', 'signature_path'
        ]);
    }
}
