<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBankAccountsTable extends Migration
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
            'company_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'account_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => true,
            ],
            'bank_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'branch_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'branch_address' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'ifsc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'misc_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bank_accounts');

        // Migrate existing bank details from companies to bank_accounts
        $db = \Config\Database::connect();
        $companies = $db->table('companies')
            ->select('id, name, bank_name, branch_name, branch_address, ifsc_code, account_number, misc_code')
            ->get()
            ->getResultArray();

        foreach ($companies as $company) {
            if (!empty($company['account_number']) && !empty($company['bank_name'])) {
                $db->table('bank_accounts')->insert([
                    'company_id'     => $company['id'],
                    'account_name'   => $company['name'], // Default account name to company name
                    'bank_name'      => $company['bank_name'],
                    'branch_name'    => $company['branch_name'],
                    'branch_address' => $company['branch_address'],
                    'ifsc_code'      => $company['ifsc_code'],
                    'account_number' => $company['account_number'],
                    'misc_code'      => $company['misc_code'],
                    'is_default'     => 1,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('bank_accounts');
    }
}
