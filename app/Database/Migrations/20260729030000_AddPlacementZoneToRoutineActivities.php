<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPlacementZoneToRoutineActivities extends Migration
{
    public function up()
    {
        $fields = [
            'placement_zone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'ACADEMIC_JP',
                'after'      => 'locked_period_end',
                'comment'    => 'PRE_ACADEMIC (# Sebelum JP1), ACADEMIC_JP (Jam ke-N), INTERMISSION_BREAK (# Istirahat), POST_ACADEMIC (# Setelah Jam Akhir)'
            ],
            'placement_sequence' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'after'      => 'placement_zone',
                'comment'    => 'Urutan sekuensial (Urutan 1, 2, 3) atau Antara JP-N'
            ],
        ];

        $this->forge->addColumn('school_routine_activities', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('school_routine_activities', ['placement_zone', 'placement_sequence']);
    }
}
