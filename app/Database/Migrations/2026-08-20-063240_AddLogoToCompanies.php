<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLogoToCompanies extends Migration
{
    public function up()
    {
        $fields = [
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'terms_conditions',
            ],
            'logo_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'logo_path',
            ],
        ];
        $this->forge->addColumn('companies', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('companies', ['logo_path', 'logo_image']);
    }
}
