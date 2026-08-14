<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStartTimeJp1ToCurriculumPlanningSettings extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('start_time_jp1', 'curriculum_planning_settings')) {
            return;
        }
        $this->forge->addColumn('curriculum_planning_settings', [
            'start_time_jp1' => [
                'type'       => 'VARCHAR',
                'constraint' => '8',
                'default'    => '07:30',
                'after'      => 'minutes_per_jp',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('curriculum_planning_settings', 'start_time_jp1');
    }
}
