<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceTemplatesAndDocketSeries extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('prefix', 'sys_sequences')) {
            $this->forge->modifyColumn('sys_sequences', [
                'prefix' => [
                    'name'       => 'prefix',
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'DCK-',
                    'null'       => true,
                ],
            ]);
        }

        if (!$this->db->tableExists('invoice_templates')) {
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
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'gst_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'gst',
                ],
                'prefix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => false,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addUniqueKey(['company_id', 'prefix'], 'uq_invoice_template_prefix');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('invoice_templates');
        }

        if (!$this->db->tableExists('docket_series')) {
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
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'prefix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => false,
                ],
                'entry_mode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'auto',
                ],
                'current_number' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 10000,
                    'null'       => false,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
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
            $this->forge->addUniqueKey(['company_id', 'prefix'], 'uq_docket_series_prefix');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('docket_series');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('docket_series')) {
            $this->forge->dropTable('docket_series');
        }

        if ($this->db->tableExists('invoice_templates')) {
            $this->forge->dropTable('invoice_templates');
        }
    }
}
