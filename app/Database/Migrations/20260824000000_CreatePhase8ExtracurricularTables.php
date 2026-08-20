<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 8 — Extracurricular & Character (Blueprint §9).
 *
 * Provides dedicated extracurricular module with:
 *   1. Programs — rationale, objectives, coach, management, funding, status.
 *   2. Members — student enrollment with role (MEMBER/COACH/LEADER).
 *   3. Sessions — schedule with date, time, location.
 *   4. Attendance — per member per session (PRESENT/ABSENT/EXCUSED/LATE).
 *   5. Competencies — competency framework per program.
 *   6. Achievements — student achievements & honors.
 *   7. Evaluations — annual INPUT→PROCESS→OUTPUT→OUTCOME evaluation.
 */
class CreatePhase8ExtracurricularTables extends Migration
{
    private array $permissions = [
        'extracurricular.view'   => ['module' => 'extracurricular', 'name' => 'View Extracurricular Programs'],
        'extracurricular.manage' => ['module' => 'extracurricular', 'name' => 'Manage Extracurricular Programs'],
    ];

    public function up(): void
    {
        $this->createPrograms();
        $this->createMembers();
        $this->createSessions();
        $this->createAttendance();
        $this->createCompetencies();
        $this->createAchievements();
        $this->createEvaluations();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();

        foreach ([
            'extracurricular_evaluations',
            'extracurricular_achievements',
            'extracurricular_competencies',
            'extracurricular_attendance',
            'extracurricular_sessions',
            'extracurricular_members',
            'extracurricular_programs',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    // ----------------------------------------------------------------
    // Table Definitions
    // ----------------------------------------------------------------

    private function createPrograms(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'            => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'code'               => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'CLUB'],
            'rationale'          => ['type' => 'TEXT', 'null' => true],
            'objective'          => ['type' => 'TEXT', 'null' => true],
            'description'        => ['type' => 'TEXT', 'null' => true],
            'coach_teacher_id'   => $this->bigInt(true),
            'management_notes'   => ['type' => 'TEXT', 'null' => true],
            'funding_source'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'funding_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'max_members'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'meeting_day'        => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'meeting_time'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'location'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['unit_id', 'academic_period_id', 'status']);
        $this->forge->addKey(['unit_id', 'coach_teacher_id']);
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('coach_teacher_id', 'teachers', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('extracurricular_programs', true);
    }

    private function createMembers(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'  => $this->bigInt(),
            'student_id'  => $this->bigInt(),
            'role'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MEMBER'],
            'join_date'   => ['type' => 'DATE', 'null' => true],
            'leave_date'  => ['type' => 'DATE', 'null' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ACTIVE'],
            'notes'       => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'student_id']);
        $this->forge->addKey(['program_id', 'status']);
        $this->forge->addKey('student_id');
        $this->forge->addForeignKey('program_id', 'extracurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('extracurricular_members', true);
    }

    private function createSessions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'    => $this->bigInt(),
            'session_date'  => ['type' => 'DATE'],
            'start_time'    => ['type' => 'TIME', 'null' => true],
            'end_time'      => ['type' => 'TIME', 'null' => true],
            'location'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'topic'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PLANNED'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'session_date']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('program_id', 'extracurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('extracurricular_sessions', true);
    }

    private function createAttendance(): void
    {
        $this->forge->addField($this->baseFields() + [
            'session_id' => $this->bigInt(),
            'member_id'  => $this->bigInt(),
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ABSENT'],
            'notes'      => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['session_id', 'member_id']);
        $this->forge->addKey('member_id');
        $this->forge->addForeignKey('session_id', 'extracurricular_sessions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('member_id', 'extracurricular_members', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('extracurricular_attendance', true);
    }

    private function createCompetencies(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'    => $this->bigInt(),
            'code'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'assessment_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'QUALITATIVE'],
            'sort_order'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['program_id', 'code']);
        $this->forge->addKey('program_id');
        $this->forge->addForeignKey('program_id', 'extracurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('extracurricular_competencies', true);
    }

    private function createAchievements(): void
    {
        $this->forge->addField($this->baseFields() + [
            'member_id'      => $this->bigInt(),
            'competency_id'  => $this->bigInt(true),
            'achieved_date'  => ['type' => 'DATE', 'null' => true],
            'level'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'score'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'remarks'        => ['type' => 'TEXT', 'null' => true],
            'evidence_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'assessed_by'    => $this->bigInt(true),
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['member_id', 'competency_id']);
        $this->forge->addKey('member_id');
        $this->forge->addForeignKey('member_id', 'extracurricular_members', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('competency_id', 'extracurricular_competencies', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('assessed_by', 'teachers', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('extracurricular_achievements', true);
    }

    private function createEvaluations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'program_id'         => $this->bigInt(),
            'evaluation_period'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'input_data'         => ['type' => 'TEXT', 'null' => true],
            'process_data'       => ['type' => 'TEXT', 'null' => true],
            'output_data'        => ['type' => 'TEXT', 'null' => true],
            'outcome_data'       => ['type' => 'TEXT', 'null' => true],
            'findings'           => ['type' => 'TEXT', 'null' => true],
            'recommendations'    => ['type' => 'TEXT', 'null' => true],
            'overall_rating'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['program_id', 'evaluation_period']);
        $this->forge->addForeignKey('program_id', 'extracurricular_programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('extracurricular_evaluations', true);
    }

    // ----------------------------------------------------------------
    // RBAC
    // ----------------------------------------------------------------

    private function seedPermissions(): void
    {
        $db = \Config\Database::connect();
        foreach ($this->permissions as $code => $data) {
            if (! $db->table('permissions')->where('code', $code)->get()->getRowArray()) {
                $db->table('permissions')->insert(array_merge(['code' => $code], $data));
            }
        }

        // Grant to management roles
        $permIds = $db->table('permissions')
            ->whereIn('code', array_keys($this->permissions))
            ->get()->getResultArray();
        $permIdMap = array_column($permIds, 'id', 'key');

        $roles = $db->table('roles')->whereIn('code', [
            'super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma', 'kepala_sekolah',
        ])->get()->getResultArray();

        foreach ($roles as $role) {
            foreach ($permIdMap as $permId) {
                $exists = $db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permId)
                    ->countAllResults();
                if (! $exists) {
                    $db->table('role_permissions')->insert([
                        'role_id'       => $role['id'],
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        $db = \Config\Database::connect();
        $permIds = $db->table('permissions')
            ->whereIn('code', array_keys($this->permissions))
            ->get()->getResultArray();
        if (! empty($permIds)) {
            $db->table('role_permissions')
                ->whereIn('permission_id', array_column($permIds, 'id'))
                ->delete();
            $db->table('permissions')
                ->whereIn('key', array_keys($this->permissions))
                ->delete();
        }
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function baseFields(): array
    {
        return [
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid'       => ['type' => 'CHAR', 'constraint' => 36],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ];
    }

    private function auditFields(): array
    {
        return [];
    }

    private function bigInt(bool $nullable = false): array
    {
        return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
    }

    private function int(): array
    {
        return ['type' => 'INT', 'unsigned' => true, 'null' => true];
    }
}
