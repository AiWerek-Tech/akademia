<?php

namespace App\Database\Migrations;

use App\Services\AcademicCalendarGeneratorService;
use CodeIgniter\Database\Migration;

class BackfillAcademicCalendarRules extends Migration
{
    public function up(): void
    {
        $service = new AcademicCalendarGeneratorService();
        foreach ($this->db->table('academic_calendars')->select('id')->get()->getResultArray() as $calendar) {
            $service->initializeExistingCalendar((int) $calendar['id']);
        }
    }

    public function down(): void
    {
        // Data backfill is intentionally retained when rolling schema versions back.
    }
}
