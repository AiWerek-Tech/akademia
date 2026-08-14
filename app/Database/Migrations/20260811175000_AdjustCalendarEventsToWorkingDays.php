<?php

namespace App\Database\Migrations;

use App\Services\AcademicCalendarGeneratorService;
use CodeIgniter\Database\Migration;

class AdjustCalendarEventsToWorkingDays extends Migration
{
    public function up(): void
    {
        $service = new AcademicCalendarGeneratorService();
        foreach ($this->db->table('academic_calendars')->select('id')->where('status', 'DRAFT')->get()->getResultArray() as $calendar) {
            $service->normalizeRuleDates((int) $calendar['id']);
        }
    }

    public function down(): void
    {
    }
}
