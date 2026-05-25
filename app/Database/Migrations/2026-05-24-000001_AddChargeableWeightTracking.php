<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChargeableWeightTracking extends Migration
{
    public function up()
    {
        $modifyFields = [
            'chargeable_weight' => [
                'name'       => 'final_chargeable_weight',
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ]
        ];
        $this->forge->modifyColumn('shipment_items', $modifyFields);

        $addFields = [
            'calculated_chargeable_weight' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ]
        ];
        $this->forge->addColumn('shipment_items', $addFields);
    }

    public function down()
    {
        $this->forge->dropColumn('shipment_items', 'calculated_chargeable_weight');
        
        $modifyFields = [
            'final_chargeable_weight' => [
                'name'       => 'chargeable_weight',
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
            ]
        ];
        $this->forge->modifyColumn('shipment_items', $modifyFields);
    }
}
