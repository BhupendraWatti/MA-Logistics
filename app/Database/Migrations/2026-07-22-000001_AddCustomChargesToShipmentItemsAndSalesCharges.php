<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomChargesToShipmentItemsAndSalesCharges extends Migration
{
    public function up()
    {
        $shipmentFields = [
            'custom_charges' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'misc_charges_name'
            ]
        ];
        $this->forge->addColumn('shipment_items', $shipmentFields);

        $salesFields = [
            'custom_charges' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'misc_charges'
            ]
        ];
        $this->forge->addColumn('sales_charges', $salesFields);
    }

    public function down()
    {
        $this->forge->dropColumn('shipment_items', 'custom_charges');
        $this->forge->dropColumn('sales_charges', 'custom_charges');
    }
}
