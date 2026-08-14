<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMinutesPerJpToCurriculumPlanningSettings extends Migration
{
    public function up()
    {
        if (!$this->hasColumn('curriculum_planning_settings', 'minutes_per_jp')) {
            $this->forge->addColumn('curriculum_planning_settings', [
                'minutes_per_jp' => [
                    'type'       => 'SMALLINT',
                    'unsigned'   => true,
                    'default'    => 40,
                    'after'      => 'daily_jp_capacity',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->hasColumn('curriculum_planning_settings', 'minutes_per_jp')) {
            $this->forge->dropColumn('curriculum_planning_settings', 'minutes_per_jp');
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column))->getNumRows() > 0;
    }
}
