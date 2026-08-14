<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherScheduleSubstitutions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'academic_period_id' => ['type' => 'INT', 'unsigned' => true],
            'absent_teacher_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'substitute_teacher_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'effective_from' => ['type' => 'DATE'],
            'effective_to' => ['type' => 'DATE'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ACTIVE'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['academic_period_id', 'absent_teacher_id', 'status']);
        $this->forge->addKey(['academic_period_id', 'substitute_teacher_id', 'status']);
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('absent_teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('substitute_teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('teacher_schedule_substitutions', true);
    }

    public function down()
    {
        $this->forge->dropTable('teacher_schedule_substitutions', true);
    }
}
