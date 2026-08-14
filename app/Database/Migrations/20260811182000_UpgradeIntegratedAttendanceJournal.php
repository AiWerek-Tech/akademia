<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Turns the original subject-only attendance table into one operational
 * ledger for subject, homeroom, morning assembly, and afternoon assembly.
 */
class UpgradeIntegratedAttendanceJournal extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('attendance_sessions', [
            'subject_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'teacher_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);

        $columns = [
            'session_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'SUBJECT', 'after' => 'schedule_entry_id'],
            'routine_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'session_type'],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'MANUAL', 'after' => 'routine_code'],
            'source_key' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'source_type'],
            'learning_objectives' => ['type' => 'TEXT', 'null' => true, 'after' => 'teaching_summary'],
            'learning_activity' => ['type' => 'TEXT', 'null' => true, 'after' => 'learning_objectives'],
            'assessment_summary' => ['type' => 'TEXT', 'null' => true, 'after' => 'learning_activity'],
            'follow_up' => ['type' => 'TEXT', 'null' => true, 'after' => 'assessment_summary'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'after' => 'status'],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'revision_number'],
            'submitted_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'submitted_at'],
            'verified_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'submitted_by'],
            'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'verified_at'],
            'locked_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'verified_by'],
        ];
        foreach ($columns as $name => $definition) {
            if (!$this->db->fieldExists($name, 'attendance_sessions')) {
                $this->forge->addColumn('attendance_sessions', [$name => $definition]);
            }
        }

        $studentColumns = [
            'arrival_time' => ['type' => 'TIME', 'null' => true, 'after' => 'status'],
            'late_minutes' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0, 'after' => 'arrival_time'],
            'source_session_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'notes'],
        ];
        foreach ($studentColumns as $name => $definition) {
            if (!$this->db->fieldExists($name, 'student_attendances')) {
                $this->forge->addColumn('student_attendances', [$name => $definition]);
            }
        }

        $now = date('Y-m-d H:i:s');
        $legacy = $this->db->table('attendance_sessions')->select('id, academic_period_id, classroom_id, subject_id, attendance_date, meeting_number, schedule_entry_id, status')->get()->getResultArray();
        foreach ($legacy as $row) {
            $keyPart = !empty($row['schedule_entry_id']) ? 'SE' . (int) $row['schedule_entry_id'] : 'S' . (int) $row['subject_id'] . 'M' . (int) $row['meeting_number'];
            $this->db->table('attendance_sessions')->where('id', (int) $row['id'])->update([
                'session_type' => 'SUBJECT',
                'source_type' => !empty($row['schedule_entry_id']) ? 'SCHEDULE' : 'MANUAL_ASSIGNMENT',
                'source_key' => 'SUBJECT:' . (int) $row['academic_period_id'] . ':' . (int) $row['classroom_id'] . ':' . $row['attendance_date'] . ':' . $keyPart,
                'submitted_at' => strtoupper((string) $row['status']) !== 'DRAFT' ? $now : null,
            ]);
        }

        if (!$this->db->tableExists('attendance_operating_settings')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'academic_year_id' => ['type' => 'INT', 'unsigned' => true],
                'unit_id' => ['type' => 'BIGINT', 'unsigned' => true],
                'morning_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Apel & Absensi Pagi'],
                'morning_start_time' => ['type' => 'TIME', 'default' => '06:45:00'],
                'morning_end_time' => ['type' => 'TIME', 'default' => '07:30:00'],
                'afternoon_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Apel & Absensi Siang'],
                'afternoon_start_time' => ['type' => 'TIME', 'default' => '14:00:00'],
                'afternoon_end_time' => ['type' => 'TIME', 'default' => '15:30:00'],
                'carry_forward_absence' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'allow_off_schedule_subject' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['academic_year_id', 'unit_id']);
            $this->forge->createTable('attendance_operating_settings');
        }

        $years = $this->db->table('academic_years')->select('id')->get()->getResultArray();
        $units = $this->db->table('school_units')->select('id')->where('is_active', 1)->get()->getResultArray();
        foreach ($years as $year) {
            foreach ($units as $unit) {
                $exists = $this->db->table('attendance_operating_settings')
                    ->where('academic_year_id', (int) $year['id'])->where('unit_id', (int) $unit['id'])->countAllResults() > 0;
                if (!$exists) {
                    $this->db->table('attendance_operating_settings')->insert([
                        'academic_year_id' => (int) $year['id'], 'unit_id' => (int) $unit['id'],
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('attendance_operating_settings', true);
        foreach (['arrival_time', 'late_minutes', 'source_session_id'] as $field) {
            if ($this->db->fieldExists($field, 'student_attendances')) {
                $this->forge->dropColumn('student_attendances', $field);
            }
        }
        foreach (['session_type', 'routine_code', 'source_type', 'source_key', 'learning_objectives', 'learning_activity', 'assessment_summary', 'follow_up', 'revision_number', 'submitted_at', 'submitted_by', 'verified_at', 'verified_by', 'locked_at'] as $field) {
            if ($this->db->fieldExists($field, 'attendance_sessions')) {
                $this->forge->dropColumn('attendance_sessions', $field);
            }
        }
        $this->forge->modifyColumn('attendance_sessions', [
            'subject_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
            'teacher_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
        ]);
    }
}
