<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandCustomerMasterFull extends Migration
{
    public function up()
    {
        $fields = [
            'gst_number'                => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'address'                   => ['type' => 'TEXT', 'null' => true],
            'state'                     => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'country'                   => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'operation_contact_name'    => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'operation_contact_number'  => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'operation_contact_email'   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'purchase_contact_name'     => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'purchase_contact_number'   => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'purchase_contact_email'    => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'sales_contact_name'        => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'sales_contact_number'      => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'sales_contact_email'       => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'plant_head_contact_name'   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'plant_head_contact_number' => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'plant_head_contact_email'  => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'billing_contact_name'      => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'billing_contact_number'    => ['type' => 'VARCHAR', 'constraint' => '50', 'null' => true],
            'billing_contact_email'     => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'mis_email_ids'             => ['type' => 'TEXT', 'null' => true],
            'mis_cc_email_ids'          => ['type' => 'TEXT', 'null' => true],
            'currency'                  => ['type' => 'VARCHAR', 'constraint' => '20', 'null' => true],
            'tds_percentage'            => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'other_1'                   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'other_2'                   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'other_3'                   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'other_4'                   => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
        ];

        $this->forge->addColumn('customers', $fields);
    }

    public function down()
    {
        $columns = [
            'gst_number', 'address', 'state', 'country',
            'operation_contact_name', 'operation_contact_number', 'operation_contact_email',
            'purchase_contact_name', 'purchase_contact_number', 'purchase_contact_email',
            'sales_contact_name', 'sales_contact_number', 'sales_contact_email',
            'plant_head_contact_name', 'plant_head_contact_number', 'plant_head_contact_email',
            'billing_contact_name', 'billing_contact_number', 'billing_contact_email',
            'mis_email_ids', 'mis_cc_email_ids', 'currency', 'tds_percentage',
            'other_1', 'other_2', 'other_3', 'other_4'
        ];
        foreach($columns as $col) {
            $this->forge->dropColumn('customers', $col);
        }
    }
}
