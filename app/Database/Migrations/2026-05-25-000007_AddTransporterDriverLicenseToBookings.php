<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTransporterDriverLicenseToBookings extends Migration
{
    public function up()
    {
        $this->forge->addColumn('bookings', [
            'transporter_name'   => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'vehicle_no'],
            'transporter_mobile' => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true, 'after' => 'transporter_name'],
            'driver_license_no'  => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true, 'after' => 'driver_mobile'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('bookings', ['transporter_name', 'transporter_mobile', 'driver_license_no']);
    }
}
