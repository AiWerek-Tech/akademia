<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTeachingLoadToRoutineActivities extends Migration
{
    public function up()
    {
        $fields = [
            'counts_as_teaching_load' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_locked_slot',
            ],
            'assignment_role_default' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'counts_as_teaching_load',
            ],
        ];
        $this->forge->addColumn('school_routine_activities', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('school_routine_activities', ['counts_as_teaching_load', 'assignment_role_default']);
    }
}
