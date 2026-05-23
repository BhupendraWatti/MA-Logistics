<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTotalAmountAndTimestampsToSalesCharges extends Migration
{
    public function up()
    {
        $fields = [
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'after'      => 'misc_charges'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'total_amount'
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_at'
            ],
        ];
        $this->forge->addColumn('sales_charges', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('sales_charges', ['total_amount', 'created_at', 'updated_at']);
    }
}
