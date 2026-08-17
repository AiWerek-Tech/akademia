<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnforceAcademicCalendarScopeUniqueness extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('unit_scope_key', 'academic_calendars')) {
            $this->forge->addColumn('academic_calendars', [
                'unit_scope_key' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'after' => 'unit_id'],
            ]);
        }
        $this->db->query('UPDATE academic_calendars SET unit_scope_key = COALESCE(unit_id, 0)');
        $indexes = $this->db->getIndexData('academic_calendars');
        if (!isset($indexes['uq_academic_calendar_year_scope'])) {
            $this->db->query('ALTER TABLE academic_calendars ADD UNIQUE KEY uq_academic_calendar_year_scope (academic_year_id, unit_scope_key)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('academic_calendars')) {
            return;
        }

        $indexes = $this->db->getIndexData('academic_calendars');
        if (isset($indexes['uq_academic_calendar_year_scope'])) {
            $this->db->query('ALTER TABLE academic_calendars DROP INDEX uq_academic_calendar_year_scope');
        }
        if ($this->db->fieldExists('unit_scope_key', 'academic_calendars')) {
            $this->forge->dropColumn('academic_calendars', 'unit_scope_key');
            $this->db->resetDataCache();
        }
    }
}
