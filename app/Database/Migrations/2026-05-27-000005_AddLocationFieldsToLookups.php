<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLocationFieldsToLookups extends Migration
{
    public function up()
    {
        $fields = [
            'pincode'  => ['type' => 'VARCHAR', 'constraint' => '10', 'null' => true, 'after' => 'value'],
            'city'     => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'pincode'],
            'district' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'city'],
            'state'    => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true, 'after' => 'district'],
        ];

        $this->forge->addColumn('lookup_values', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('lookup_values', ['pincode', 'city', 'district', 'state']);
    }
}
