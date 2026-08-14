<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicOperatingSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'academic_year_id' => ['type' => 'INT', 'unsigned' => true],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'unit_scope_key' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'source_mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CURRICULUM'],
            'custom_working_days_json' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'effective_week_min_days' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 3],
            'compare_official_targets' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'non_school_event_policy' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PREVIOUS_WORKING_DAY'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['academic_year_id', 'unit_scope_key'], 'uq_academic_operating_scope');
        $this->forge->createTable('academic_operating_settings', true);

        $this->forge->addColumn('academic_calendars', [
            'working_days_json_snapshot' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'effective_week_min_days'],
            'working_day_source' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'working_days_json_snapshot'],
            'operating_setting_revision' => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'after' => 'working_day_source'],
        ]);

        // Targets in the legacy generator were automatically copied from a
        // six-day Dinas reference. They are not valid blockers for WMVAA's
        // five-day curriculum and are therefore removed unless entered later.
        $this->db->table('academic_calendars')
            ->where('target_hes_sem1', 134)->where('target_hes_sem2', 123)
            ->where('target_heb_sem1', 118)->where('target_heb_sem2', 115)
            ->update([
                'target_hes_sem1' => null, 'target_hes_sem2' => null,
                'target_heb_sem1' => null, 'target_heb_sem2' => null,
            ]);
    }

    public function down(): void
    {
        foreach (['working_days_json_snapshot', 'working_day_source', 'operating_setting_revision'] as $field) {
            if ($this->db->fieldExists($field, 'academic_calendars')) {
                $this->forge->dropColumn('academic_calendars', $field);
            }
        }
        $this->forge->dropTable('academic_operating_settings', true);
    }
}
