<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLookupValuesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            // type values: origin, destination, mode, material_type, material_category, payment_type
            'type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'type']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lookup_values');
    }

    public function down()
    {
        $this->forge->dropTable('lookup_values');
    }
}
