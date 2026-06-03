<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExpectedDeliveryToBookings extends Migration
{
    public function up()
    {
        $fields = [
            'expected_delivery_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'booking_date',
            ],
            'expected_delivery_time' => [
                'type' => 'TIME',
                'null' => true,
                'after' => 'expected_delivery_date',
            ],
        ];
        $this->forge->addColumn('bookings', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['expected_delivery_date', 'expected_delivery_time']);
    }
}
