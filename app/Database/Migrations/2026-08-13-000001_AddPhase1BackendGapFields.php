<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhase1BackendGapFields extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('payment_type', 'shipment_items')) {
            $this->forge->addColumn('shipment_items', [
                'payment_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'consignee',
                ],
            ]);
        }

        if (!$this->db->fieldExists('material_category', 'shipment_items')) {
            $this->forge->addColumn('shipment_items', [
                'material_category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'payment_type',
                ],
            ]);
        }

        if (!$this->db->fieldExists('remarks', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'remarks' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'narration',
                ],
            ]);
        }

        if (!$this->db->tableExists('customer_rates')) {
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
                'customer_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'customer_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'material_category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'effective_from' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'effective_to' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
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
            $this->forge->addKey(['company_id', 'customer_name', 'material_category', 'effective_from'], false, false, 'idx_customer_rates_lookup');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('customer_rates');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('customer_rates')) {
            $this->forge->dropTable('customer_rates');
        }

        if ($this->db->fieldExists('remarks', 'bookings')) {
            $this->forge->dropColumn('bookings', 'remarks');
        }

        if ($this->db->fieldExists('material_category', 'shipment_items')) {
            $this->forge->dropColumn('shipment_items', 'material_category');
        }

        if ($this->db->fieldExists('payment_type', 'shipment_items')) {
            $this->forge->dropColumn('shipment_items', 'payment_type');
        }
    }
}
