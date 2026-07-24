<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCurriculumPlanningSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'curriculum_version_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'teaching_days_per_week' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 5],
            'daily_jp_capacity' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 9.00],
            'teacher_minimum_hours' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 24.00],
            'teacher_maximum_hours' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 40.00],
            'allow_custom_hours' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['curriculum_version_id', 'unit_id'], 'uq_curriculum_planning_scope');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('curriculum_planning_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('curriculum_planning_settings', true);
    }
}
