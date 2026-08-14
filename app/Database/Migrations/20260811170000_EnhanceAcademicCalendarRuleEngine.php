<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceAcademicCalendarRuleEngine extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'unit_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'working_days_json' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => '[1,2,3,4,5]'],
            'effective_week_min_days' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 3],
            'sabbath_day' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('academic_calendar_profiles', true);

        $now = date('Y-m-d H:i:s');
        $this->db->table('academic_calendar_profiles')->insertBatch([
            [
                'code' => 'WMVAA_5_DAY',
                'name' => 'WMVAA Advent 5 Hari',
                'description' => 'Senin-Jumat sebagai hari sekolah; Sabtu adalah Sabat dan Minggu libur mingguan.',
                'working_days_json' => '[1,2,3,4,5]',
                'effective_week_min_days' => 3,
                'sabbath_day' => 6,
                'is_default' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'DINAS_6_DAY_REFERENCE',
                'name' => 'Referensi Dinas 6 Hari',
                'description' => 'Senin-Sabtu. Disediakan sebagai pembanding target resmi, bukan profil operasional default WMVAA.',
                'working_days_json' => '[1,2,3,4,5,6]',
                'effective_week_min_days' => 3,
                'sabbath_day' => null,
                'is_default' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->forge->addColumn('academic_calendars', [
            'profile_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'unit_id'],
            'effective_week_min_days' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 3, 'after' => 'status'],
            'target_hes_sem1' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'effective_week_min_days'],
            'target_hes_sem2' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'target_hes_sem1'],
            'target_heb_sem1' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'target_hes_sem2'],
            'target_heb_sem2' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'target_heb_sem1'],
            'validation_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING', 'after' => 'target_heb_sem2'],
            'validation_summary_json' => ['type' => 'TEXT', 'null' => true, 'after' => 'validation_status'],
            'generated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'validation_summary_json'],
        ]);

        $defaultProfile = $this->db->table('academic_calendar_profiles')->where('code', 'WMVAA_5_DAY')->get()->getRowArray();
        if ($defaultProfile) {
            $this->db->table('academic_calendars')->where('profile_id IS NULL')->update([
                'profile_id' => (int) $defaultProfile['id'],
                'validation_status' => 'PENDING',
            ]);
        }

        $this->forge->addColumn('academic_calendar_days', [
            'source_layer' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PROFILE', 'after' => 'event_title'],
            'source_rule_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'source_layer'],
            'is_manual_override' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'source_rule_id'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'calendar_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 200],
            'day_type_code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'start_date' => ['type' => 'DATE'],
            'end_date' => ['type' => 'DATE'],
            'source_layer' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'SCHOOL'],
            'priority' => ['type' => 'SMALLINT', 'default' => 50],
            'is_school_effective' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_learning_effective' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['calendar_id', 'start_date']);
        $this->forge->createTable('academic_calendar_rules', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('academic_calendar_rules', true);
        foreach (['profile_id', 'effective_week_min_days', 'target_hes_sem1', 'target_hes_sem2', 'target_heb_sem1', 'target_heb_sem2', 'validation_status', 'validation_summary_json', 'generated_at'] as $field) {
            if ($this->db->fieldExists($field, 'academic_calendars')) {
                $this->forge->dropColumn('academic_calendars', $field);
            }
        }
        foreach (['source_layer', 'source_rule_id', 'is_manual_override'] as $field) {
            if ($this->db->fieldExists($field, 'academic_calendar_days')) {
                $this->forge->dropColumn('academic_calendar_days', $field);
            }
        }
        $this->forge->dropTable('academic_calendar_profiles', true);
    }
}
