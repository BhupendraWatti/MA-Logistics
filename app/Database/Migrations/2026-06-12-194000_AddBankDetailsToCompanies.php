<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBankDetailsToCompanies extends Migration
{
    public function up()
    {
        $fields = [
            'bank_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'signature_path'
            ],
            'branch_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'bank_name'
            ],
            'branch_address' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'branch_name'
            ],
            'ifsc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'branch_address'
            ],
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'ifsc_code'
            ],
            'misc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
                'after'      => 'account_number'
            ]
        ];
        $this->forge->addColumn('companies', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('companies', ['bank_name', 'branch_name', 'branch_address', 'ifsc_code', 'account_number', 'misc_code']);
    }
}
