<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSchoolRoutineActivitiesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'unit_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'short_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'activity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'FIXED_ROUTINE',
            ],
            'default_duration_jp' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'default'    => 1.00,
            ],
            'color_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '#6c757d',
            ],
            'is_locked_slot' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'default_day' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'default_period_number' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'notes' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_by' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('unit_id');
        $this->forge->createTable('school_routine_activities', true);
    }

    public function down()
    {
        $this->forge->dropTable('school_routine_activities', true);
    }
}
