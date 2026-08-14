<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTotalAmountToInvoiceDownloads extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('total_amount', 'invoice_downloads')) {
            $this->forge->addColumn('invoice_downloads', [
                'total_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'null'       => true,
                    'after'      => 'item_ids',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('total_amount', 'invoice_downloads')) {
            $this->forge->dropColumn('invoice_downloads', 'total_amount');
        }
    }
}
