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
        $this->db->resetDataCache();
        $columns = $this->db->getFieldNames('users');
        if (in_array('username_changed_at', $columns, true)) {
            $this->forge->dropColumn('users', 'username_changed_at');
            $this->db->resetDataCache();
        }
        $columns = $this->db->getFieldNames('users');
        if (in_array('must_change_username', $columns, true)) {
            $this->forge->dropColumn('users', 'must_change_username');
        }
    }
}
