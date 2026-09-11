<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserCompanyAccessTable extends Migration
{
    public function up()
    {
        // 1. Add is_root flag to companies table if not present
        if (!$this->db->fieldExists('is_root', 'companies')) {
            $this->forge->addColumn('companies', [
                'is_root' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'name',
                ],
            ]);
        }

        // MA Logistic (id = 1) is root
        $this->db->table('companies')->where('id', 1)->update(['is_root' => 1]);

        // 2. Create user_company_access table
        if (!$this->db->tableExists('user_company_access')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'company_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'role' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'user',
                    'null'       => false,
                ],
                'can_create' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
                'can_edit' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
                'can_delete' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_id', 'company_id'], 'uq_user_company');
            $this->forge->addKey(['company_id', 'user_id'], false, false, 'idx_company_user');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('user_company_access', true);
        }

        // 3. Backfill existing users
        $users = $this->db->table('users')->get()->getResultArray();
        $companies = $this->db->table('companies')->get()->getResultArray();
        $now = date('Y-m-d H:i:s');

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $userRole = (string) ($user['role'] ?? 'user');
            $canCreate = (int) ($user['can_create'] ?? 0);
            $canEdit = (int) ($user['can_edit'] ?? 0);
            $canDelete = (int) ($user['can_delete'] ?? 0);
            $isActive = (int) ($user['is_active'] ?? 1);

            // Assign every existing user to Root Company (id = 1)
            $existingRoot = $this->db->table('user_company_access')
                ->where('user_id', $userId)
                ->where('company_id', 1)
                ->get()
                ->getRowArray();

            if (!$existingRoot) {
                $this->db->table('user_company_access')->insert([
                    'user_id'    => $userId,
                    'company_id' => 1,
                    'role'       => $userRole,
                    'can_create' => $canCreate,
                    'can_edit'   => $canEdit,
                    'can_delete' => $canDelete,
                    'is_active'  => $isActive,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // If user is an admin, ensure they have access to all companies
            if ($userRole === 'admin') {
                foreach ($companies as $company) {
                    $compId = (int) $company['id'];
                    if ($compId === 1) continue;

                    $exists = $this->db->table('user_company_access')
                        ->where('user_id', $userId)
                        ->where('company_id', $compId)
                        ->get()
                        ->getRowArray();

                    if (!$exists) {
                        $this->db->table('user_company_access')->insert([
                            'user_id'    => $userId,
                            'company_id' => $compId,
                            'role'       => 'admin',
                            'can_create' => 1,
                            'can_edit'   => 1,
                            'can_delete' => 1,
                            'is_active'  => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('user_company_access')) {
            $this->forge->dropTable('user_company_access', true);
        }

        if ($this->db->fieldExists('is_root', 'companies')) {
            $this->forge->dropColumn('companies', 'is_root');
        }
    }
}
