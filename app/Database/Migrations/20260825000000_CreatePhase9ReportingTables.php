<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 9 — Reporting & Portfolio (Blueprint §7–8, §9).
 *
 * Provides semester reporting and student portfolio:
 *   1. Reporting Policies — versioned calculation rules per unit/period/subject.
 *   2. Report Snapshots — frozen semester report per student per period.
 *   3. Report Subject Results — per-subject attainment inside a snapshot.
 *   4. Report Narratives — teacher/AI-written narrative per subject.
 *   5. Portfolio Collections — student work evidence aggregation.
 */
class CreatePhase9ReportingTables extends Migration
{
    private array $permissions = [
        'reporting.view'   => ['module' => 'reporting', 'name' => 'View Reports'],
        'reporting.manage' => ['module' => 'reporting', 'name' => 'Manage Reports & Generate Snapshots'],
    ];

    public function up(): void
    {
        $this->createPolicies();
        $this->createSnapshots();
        $this->createSubjectResults();
        $this->createNarratives();
        $this->createPortfolio();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();

        foreach ([
            'portfolio_collections',
            'report_narratives',
            'report_subject_results',
            'report_snapshots',
            'reporting_policies',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    // ----------------------------------------------------------------
    // Table Definitions
    // ----------------------------------------------------------------

    private function createPolicies(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'            => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'subject_id'         => $this->bigInt(true),
            'name'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'method'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'AVERAGE'],
            'description'        => ['type' => 'TEXT', 'null' => true],
            'config_json'        => ['type' => 'TEXT', 'null' => true],
            'is_active'          => ['type' => 'TINYINT', 'default' => 1],
            'version'            => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['unit_id', 'academic_period_id', 'is_active']);
        $this->forge->addKey('subject_id');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('reporting_policies', true);
    }

    private function createSnapshots(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'            => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'student_id'         => $this->bigInt(),
            'snapshot_type'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'SEMESTER'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'locked_by'          => $this->bigInt(true),
            'locked_at'          => ['type' => 'DATETIME', 'null' => true],
            'published_at'       => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'academic_period_id', 'student_id', 'snapshot_type']);
        $this->forge->addKey(['unit_id', 'academic_period_id', 'status']);
        $this->forge->addKey('student_id');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('report_snapshots', true);
    }

    private function createSubjectResults(): void
    {
        $this->forge->addField($this->baseFields() + [
            'snapshot_id'       => $this->bigInt(),
            'subject_id'        => $this->bigInt(),
            'teacher_id'        => $this->bigInt(true),
            'final_score'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'final_predicate'   => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'tp_coverage_pct'   => ['type' => 'DECIMAL', 'constraint' => '5,1', 'null' => true],
            'mastery_pct'       => ['type' => 'DECIMAL', 'constraint' => '5,1', 'null' => true],
            'attendance_pct'    => ['type' => 'DECIMAL', 'constraint' => '5,1', 'null' => true],
            'detail_json'       => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['snapshot_id', 'subject_id']);
        $this->forge->addKey('snapshot_id');
        $this->forge->addKey('subject_id');
        $this->forge->addForeignKey('snapshot_id', 'report_snapshots', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('report_subject_results', true);
    }

    private function createNarratives(): void
    {
        $this->forge->addField($this->baseFields() + [
            'subject_result_id' => $this->bigInt(),
            'narrative_type'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'SUBJECT'],
            'content'           => ['type' => 'TEXT'],
            'source'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'TEACHER'],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('subject_result_id');
        $this->forge->addForeignKey('subject_result_id', 'report_subject_results', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('report_narratives', true);
    }

    private function createPortfolio(): void
    {
        $this->forge->addField($this->baseFields() + [
            'student_id'        => $this->bigInt(),
            'unit_id'           => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'category'          => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'BEST_WORK'],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'source_type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'source_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'file_url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'is_highlighted'    => ['type' => 'TINYINT', 'default' => 0],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['student_id', 'academic_period_id', 'category']);
        $this->forge->addKey('unit_id');
        $this->forge->addForeignKey('student_id', 'elective_students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('portfolio_collections', true);
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

        $permIds = $db->table('permissions')
            ->whereIn('code', array_keys($this->permissions))
            ->get()->getResultArray();
        $permIdMap = array_column($permIds, 'id', 'code');

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
                ->whereIn('code', array_keys($this->permissions))
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
