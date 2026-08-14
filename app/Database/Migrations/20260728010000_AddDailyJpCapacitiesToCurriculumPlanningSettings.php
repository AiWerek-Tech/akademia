<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDailyJpCapacitiesToCurriculumPlanningSettings extends Migration
{
    public function up()
    {
        if (!$this->hasColumn('curriculum_planning_settings', 'daily_jp_capacities_json')) {
            $this->forge->addColumn('curriculum_planning_settings', [
                'daily_jp_capacities_json' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'daily_jp_capacity',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->hasColumn('curriculum_planning_settings', 'daily_jp_capacities_json')) {
            $this->forge->dropColumn('curriculum_planning_settings', 'daily_jp_capacities_json');
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column))->getNumRows() > 0;
    }
}
