<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceSequences extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('invoice_prefix', 'companies')) {
            $this->forge->addColumn('companies', [
                'invoice_prefix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'name',
                ],
            ]);
        }

        if (!$this->db->tableExists('invoice_sequences')) {
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
                'financial_year' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 5,
                    'null'       => false,
                ],
                'prefix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                ],
                'last_number' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
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
            $this->forge->addUniqueKey(['company_id', 'financial_year', 'prefix'], 'uq_invoice_sequence_scope');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('invoice_sequences');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('invoice_sequences')) {
            $this->forge->dropTable('invoice_sequences');
        }

        if ($this->db->fieldExists('invoice_prefix', 'companies')) {
            $this->forge->dropColumn('companies', 'invoice_prefix');
        }
    }
}
