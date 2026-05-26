<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
            'code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'pan'           => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'pincode'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'city'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'bill_to'       => ['type' => 'TEXT', 'null' => true],
            'consignee'     => ['type' => 'TEXT', 'null' => true],
            'payment_type'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'narration'     => ['type' => 'TEXT', 'null' => true],
            // Contact Person 1
            'person1_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'person1_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'person1_email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // Contact Person 2
            'person2_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'person2_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'person2_email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            // Contact Person 3
            'person3_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'person3_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'person3_email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customers');
    }

    public function down()
    {
        $this->forge->dropTable('customers');
    }
}
