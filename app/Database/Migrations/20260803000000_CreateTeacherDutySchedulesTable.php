<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherDutySchedulesTable extends Migration
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
            'academic_year_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'teacher_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'day_of_week' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
            ],
            'day_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'duty_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'GURU_PIKET',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'updated_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['academic_year_id', 'teacher_id', 'day_of_week']);
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teacher_duty_schedules', true);
    }

    public function down()
    {
        $this->forge->dropTable('teacher_duty_schedules', true);
    }
}
