<?php

namespace App\Database\Migrations;

use App\Services\AcademicCalendarGeneratorService;
use CodeIgniter\Database\Migration;

class RevalidateAcademicCalendars extends Migration
{
    public function up(): void
    {
        $service = new AcademicCalendarGeneratorService();
        foreach ($this->db->table('academic_calendars')->select('id, status')->get()->getResultArray() as $calendar) {
            if ($calendar['status'] === 'DRAFT') {
                $service->rebuild((int) $calendar['id'], true);
            }
        }
    }

    public function down(): void
    {
    }
}
