<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeMorningRoutinePlacement extends Migration
{
    public function up()
    {
        $this->db->table('school_routine_activities')
            ->whereIn('code', ['UPACARA', 'CHAPEL', 'SENAM', 'SID'])
            ->update([
                'placement_zone' => 'PRE_ACADEMIC',
                'locked_period_start' => null,
                'locked_period_end' => null,
            ]);
    }

    public function down()
    {
        $this->db->table('school_routine_activities')
            ->whereIn('code', ['UPACARA', 'CHAPEL', 'SENAM', 'SID'])
            ->update([
                'placement_zone' => 'ACADEMIC_JP',
                'locked_period_start' => 1,
                'locked_period_end' => 1,
            ]);
    }
}
