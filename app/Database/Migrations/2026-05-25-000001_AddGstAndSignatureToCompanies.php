<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstAndSignatureToCompanies extends Migration
{
    public function up()
    {
        $fields = [
            'address'          => ['type' => 'TEXT', 'null' => true, 'after' => 'name'],
            'email'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'address'],
            'mobile'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'email'],
            'gstin'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'mobile'],
            'pan'              => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true, 'after' => 'gstin'],
            'sac_code'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'pan'],
            'cgst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00', 'null' => false, 'after' => 'sac_code'],
            'sgst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00', 'null' => false, 'after' => 'cgst_rate'],
            'igst_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00', 'null' => false, 'after' => 'sgst_rate'],
            'terms_conditions' => ['type' => 'TEXT', 'null' => true, 'after' => 'igst_rate'],
            'signature_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'terms_conditions'],
        ];
        $this->forge->addColumn('companies', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('companies', [
            'address', 'email', 'mobile', 'gstin', 'pan', 'sac_code',
            'cgst_rate', 'sgst_rate', 'igst_rate', 'terms_conditions', 'signature_path'
        ]);
    }
}
