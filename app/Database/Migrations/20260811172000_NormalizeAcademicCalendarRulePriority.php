<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeAcademicCalendarRulePriority extends Migration
{
    public function up(): void
    {
        $this->db->table('academic_calendar_rules')
            ->where('source_layer', 'DINAS')
            ->whereIn('day_type_code', ['LU', 'CB'])
            ->update(['priority' => 90, 'updated_at' => date('Y-m-d H:i:s')]);

        foreach ($this->db->table('academic_calendars')->select('id, status')->get()->getResultArray() as $calendar) {
            if ($calendar['status'] === 'DRAFT') {
                (new \App\Services\AcademicCalendarGeneratorService())->rebuild((int) $calendar['id'], true);
            }
        }
    }

    public function down(): void
    {
        $this->db->table('academic_calendar_rules')
            ->where('source_layer', 'DINAS')
            ->whereIn('day_type_code', ['LU', 'CB'])
            ->update(['priority' => 70, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
