<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceSchoolRoutineActivities extends Migration
{
    public function up()
    {
        $fields = [
            'duration_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'STANDARD_JP',
                'after'      => 'default_duration_jp',
            ],
            'duration_minutes' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'duration_mode',
            ],
            'assignment_strategy' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'NONE',
                'after'      => 'assignment_role_default',
            ],
            'specific_teacher_id' => [
                'type'       => 'BIGINT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'assignment_strategy',
            ],
            'locked_period_start' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'default_period_number',
            ],
            'locked_period_end' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'locked_period_start',
            ],
        ];

        $this->forge->addColumn('school_routine_activities', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('school_routine_activities', [
            'duration_mode',
            'duration_minutes',
            'assignment_strategy',
            'specific_teacher_id',
            'locked_period_start',
            'locked_period_end',
        ]);
    }
}
