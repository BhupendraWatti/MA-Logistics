<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Supports logistics/ajax-datatable: WHERE company_id ORDER BY id DESC LIMIT/OFFSET.
 */
class AddBookingsCompanyListIndex extends Migration
{
    public function up()
    {
        $indexes = $this->db->query("SHOW INDEX FROM bookings WHERE Key_name = 'idx_bookings_company_id'")->getResultArray();
        if (empty($indexes)) {
            $this->db->query('CREATE INDEX idx_bookings_company_id ON bookings (company_id, id DESC)');
        }
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_bookings_company_id ON bookings');
    }
}
