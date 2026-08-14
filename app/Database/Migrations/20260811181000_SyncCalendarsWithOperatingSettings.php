<?php

namespace App\Database\Migrations;

use App\Services\AcademicCalendarGeneratorService;
use CodeIgniter\Database\Migration;

class SyncCalendarsWithOperatingSettings extends Migration
{
    public function up(): void
    {
        $generator = new AcademicCalendarGeneratorService();
        foreach ($this->db->table('academic_calendars')->select('id, status')->get()->getResultArray() as $calendar) {
            if ($calendar['status'] === 'DRAFT') {
                $generator->rebuild((int) $calendar['id'], true);
            }
        }
    }

    public function down(): void
    {
    }
}
