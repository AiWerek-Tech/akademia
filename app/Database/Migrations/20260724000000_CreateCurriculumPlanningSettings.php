<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCurriculumPlanningSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'curriculum_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'workload_policy_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'teaching_days_per_week' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'default'  => 5,
            ],
            'selected_day_codes_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'daily_jp_capacity' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 9.00,
            ],
            'daily_jp_capacities_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'minutes_per_jp' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 40,
            ],
            'allow_custom_hours' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'revision_number' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['curriculum_version_id', 'unit_id'], 'uq_curriculum_planning_scope');
        $this->forge->addForeignKey('curriculum_version_id', 'curriculum_versions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('workload_policy_id', 'workload_policies', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('curriculum_planning_settings', true);
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        try {
            $this->forge->dropTable('curriculum_planning_settings', true);
        } finally {
            $this->db->enableForeignKeyChecks();
        }
    }
}
