<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactsMasterTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'customer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'phone'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'contact_type'=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // e.g., CS, Billing, General
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('contacts_master');

        // Add new columns to customers table
        $this->forge->addColumn('customers', [
            'cs_person'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'contact_person' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'billing_person' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('contacts_master');
        $this->forge->dropColumn('customers', ['cs_person', 'contact_person', 'billing_person']);
    }
}
