<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRequiredUsernameChange extends Migration
{
    public function up()
    {
        $this->db->resetDataCache();
        if (!$this->db->fieldExists('must_change_username', 'users')) {
            $this->forge->addColumn('users', [
                'must_change_username' => [
                    'type' => 'TINYINT', 'constraint' => 1, 'default' => 0,
                    'null' => false, 'after' => 'must_change_password',
                ],
            ]);
            $this->db->resetDataCache();
        }
        if (!$this->db->fieldExists('username_changed_at', 'users')) {
            $this->forge->addColumn('users', [
                'username_changed_at' => [
                    'type' => 'DATETIME', 'null' => true, 'after' => 'password_changed_at',
                ],
            ]);
            $this->db->resetDataCache();
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('users')) {
            return;
        }

        $this->db->resetDataCache();
        if ($this->hasColumn('users', 'username_changed_at')) {
            $this->forge->dropColumn('users', 'username_changed_at');
            $this->db->resetDataCache();
        }
        if ($this->hasColumn('users', 'must_change_username')) {
            $this->forge->dropColumn('users', 'must_change_username');
            $this->db->resetDataCache();
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        )->getRowArray() !== null;
    }
}
