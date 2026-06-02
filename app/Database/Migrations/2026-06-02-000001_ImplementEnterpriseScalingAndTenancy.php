<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementEnterpriseScalingAndTenancy extends Migration
{
    public function up()
    {
        // 1. Create sys_sequences table for Pessimistic Row Locking
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'sequence_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'current_val' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 10000,
                'null'       => false,
            ],
            'prefix' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'default'    => 'DCK-',
                'null'       => true,
            ],
            'suffix_year' => [
                'type'       => 'VARCHAR',
                'constraint' => '4',
                'null'       => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'branch_id', 'sequence_type', 'suffix_year'], 'uq_branch_seq');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sys_sequences');

        // 2. Add branch_id to bookings table
        $fieldsBookings = [
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'company_id',
            ],
        ];
        $this->forge->addColumn('bookings', $fieldsBookings);

        // 3. Add branch_id to users table
        $fieldsUsers = [
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'role',
            ],
        ];
        $this->forge->addColumn('users', $fieldsUsers);

        // 4. Create Composite Operational and Search Indexes
        $this->db->query("CREATE INDEX idx_bookings_awb ON bookings (awb_no)");
        $this->db->query("CREATE INDEX idx_shipment_items_docket ON shipment_items (docket_no)");
        $this->db->query("CREATE INDEX idx_bookings_branch_date ON bookings (branch_id, booking_date DESC)");
        $this->db->query("CREATE INDEX idx_tracking_lookup ON tracking_history (booking_id, event_date DESC, event_time DESC)");
    }

    public function down()
    {
        $this->forge->dropTable('sys_sequences');
        
        $this->forge->dropColumn('bookings', 'branch_id');
        $this->forge->dropColumn('users', 'branch_id');

        $this->db->query("DROP INDEX idx_bookings_awb ON bookings");
        $this->db->query("DROP INDEX idx_shipment_items_docket ON shipment_items");
        $this->db->query("DROP INDEX idx_bookings_branch_date ON bookings");
        $this->db->query("DROP INDEX idx_tracking_lookup ON tracking_history");
    }
}
