<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOriginDestinationToCustomerRates extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('customer_rates')) {
            return;
        }

        $fields = $this->db->getFieldNames('customer_rates');
        $add = [];

        if (!in_array('origin', $fields, true)) {
            $add['origin'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'customer_name',
            ];
        }

        if (!in_array('destination', $fields, true)) {
            $add['destination'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'origin',
            ];
        }

        if (!empty($add)) {
            $this->forge->addColumn('customer_rates', $add);
        }

        $this->db->query('CREATE INDEX idx_customer_rates_od_lookup ON customer_rates (company_id, customer_name, origin, destination, effective_from)');
    }

    public function down()
    {
        if (!$this->db->tableExists('customer_rates')) {
            return;
        }

        $this->db->query('DROP INDEX idx_customer_rates_od_lookup ON customer_rates');

        $fields = $this->db->getFieldNames('customer_rates');
        if (in_array('destination', $fields, true)) {
            $this->forge->dropColumn('customer_rates', 'destination');
        }
        if (in_array('origin', $fields, true)) {
            $this->forge->dropColumn('customer_rates', 'origin');
        }
    }
}
