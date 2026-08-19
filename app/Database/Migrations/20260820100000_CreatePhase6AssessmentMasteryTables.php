<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 6 — Assessment & Mastery
 *
 * Creates the gradebook, evidence, mastery, and intervention tables that
 * turn TP (Tujuan Pembelajaran) progress into evidence-backed mastery.
 *
 * Status flow:
 *   assessments: DRAFT → PUBLISHED → CLOSED
 *   interventions: RECOMMENDED → APPROVED → COMPLETED | CANCELLED
 */
class CreatePhase6AssessmentMasteryTables extends Migration
{
    private array $permissions = [
        'assessment.view'    => ['module' => 'assessment', 'name' => 'View Assessments & Mastery'],
        'assessment.manage'  => ['module' => 'assessment', 'name' => 'Manage Assessments & Results'],
        'assessment.mastery' => ['module' => 'assessment', 'name' => 'Manage Mastery & Interventions'],
    ];

    public function up(): void
    {
        $this->createAssessments();
        $this->createAssessmentObjectives();
        $this->createAssessmentCriteria();
        $this->createAssessmentItems();
        $this->createAssessmentAttempts();
        $this->createCriterionResults();
        $this->createAssessmentEvidence();
        $this->createAssessmentFeedback();
        $this->createMasteryRecords();
        $this->createInterventions();
        $this->createReportingPolicies();
        $this->seedPermissions();
        $this->seedFeatureFlag();
    }

    public function down(): void
    {
        $this->removeFeatureFlag();
        $this->removePermissions();

        foreach ([
            'reporting_policies',
            'interventions',
            'mastery_records',
            'assessment_feedback',
            'assessment_evidence',
            'criterion_results',
            'assessment_attempts',
            'assessment_items',
            'assessment_criteria',
            'assessment_objectives',
            'assessments',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    // ----------------------------------------------------------------
    // Table Definitions
    // ----------------------------------------------------------------

    private function createAssessments(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'                  => $this->bigInt(),
            'academic_period_id'       => $this->bigInt(),
            'classroom_id'             => $this->bigInt(),
            'subject_id'               => $this->bigInt(),
            'teacher_id'               => $this->bigInt(true),
            'lesson_plan_assessment_id'=> $this->bigInt(true),
            'learning_session_id'      => $this->bigInt(true),
            'title'                    => ['type' => 'VARCHAR', 'constraint' => 255],
            'assessment_type'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'FORMATIVE'],
            'assessment_form'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ANGKA'],
            'assessment_date'          => ['type' => 'DATE'],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'max_score'                => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'rubric_json'              => ['type' => 'TEXT', 'null' => true],
            'description'              => ['type' => 'TEXT', 'null' => true],
            'revision_number'          => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'published_at'             => ['type' => 'DATETIME', 'null' => true],
            'closed_at'                => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['unit_id', 'academic_period_id', 'subject_id', 'assessment_date']);
        $this->forge->addKey(['classroom_id', 'assessment_type']);
        $this->forge->addKey('status');
        $this->forge->addKey('teacher_id');
        $this->forge->addKey('lesson_plan_assessment_id');
        $this->forge->addKey('learning_session_id');
        $this->forge->createTable('assessments', true);
    }

    private function createAssessmentObjectives(): void
    {
        $this->forge->addField($this->baseFields() + [
            'assessment_id'        => $this->bigInt(),
            'learning_objective_id'=> $this->bigInt(),
            'sequence_order'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['assessment_id', 'learning_objective_id'], 'uq_assessment_objective');
        $this->forge->addKey('learning_objective_id');
        $this->forge->createTable('assessment_objectives', true);
    }

    private function createAssessmentCriteria(): void
    {
        $this->forge->addField($this->baseFields() + [
            'assessment_id'         => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(true),
            'criterion'             => ['type' => 'TEXT'],
            'weight'                => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 1.0],
            'sequence_order'        => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'rubric_levels_json'    => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['assessment_id', 'sequence_order']);
        $this->forge->addKey('learning_objective_id');
        $this->forge->createTable('assessment_criteria', true);
    }

    private function createAssessmentItems(): void
    {
        $this->forge->addField($this->baseFields() + [
            'assessment_id'  => $this->bigInt(),
            'item_type'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ESSAY'],
            'prompt'         => ['type' => 'TEXT'],
            'answer_key'     => ['type' => 'TEXT', 'null' => true],
            'max_score'      => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['assessment_id', 'sequence_order']);
        $this->forge->createTable('assessment_items', true);
    }

    private function createAssessmentAttempts(): void
    {
        $this->forge->addField($this->baseFields() + [
            'assessment_id' => $this->bigInt(),
            'student_id'    => $this->bigInt(),
            'classroom_id'  => $this->bigInt(),
            'score'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'is_complete'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'submitted_at'  => ['type' => 'DATETIME', 'null' => true],
            'revision_number'=> ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['assessment_id', 'student_id'], 'uq_assessment_student');
        $this->forge->addKey('student_id');
        $this->forge->addKey('classroom_id');
        $this->forge->createTable('assessment_attempts', true);
    }

    private function createCriterionResults(): void
    {
        $this->forge->addField($this->baseFields() + [
            'attempt_id'    => $this->bigInt(),
            'criterion_id'  => $this->bigInt(),
            'level_index'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'score'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['attempt_id', 'criterion_id'], 'uq_criterion_result');
        $this->forge->addKey('criterion_id');
        $this->forge->createTable('criterion_results', true);
    }

    private function createAssessmentEvidence(): void
    {
        $this->forge->addField($this->baseFields() + [
            'student_id'            => $this->bigInt(),
            'attempt_id'            => $this->bigInt(true),
            'learning_objective_id' => $this->bigInt(true),
            'criterion_id'          => $this->bigInt(true),
            'evidence_type'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'FILE'],
            'title'                 => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'               => ['type' => 'TEXT', 'null' => true],
            'file_path'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'meta_json'             => ['type' => 'TEXT', 'null' => true],
            'captured_at'           => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey('student_id');
        $this->forge->addKey('attempt_id');
        $this->forge->addKey('learning_objective_id');
        $this->forge->addKey('criterion_id');
        $this->forge->createTable('assessment_evidence', true);
    }

    private function createAssessmentFeedback(): void
    {
        $this->forge->addField($this->baseFields() + [
            'attempt_id'    => $this->bigInt(),
            'student_id'    => $this->bigInt(),
            'content'       => ['type' => 'TEXT'],
            'feedback_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MANUAL'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['attempt_id', 'feedback_type']);
        $this->forge->addKey('student_id');
        $this->forge->createTable('assessment_feedback', true);
    }

    private function createMasteryRecords(): void
    {
        $this->forge->addField($this->baseFields() + [
            'student_id'            => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
            'criterion_id'          => $this->bigInt(true),
            'evidence_id'           => $this->bigInt(true),
            'source_attempt_id'     => $this->bigInt(true),
            'result'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DEVELOPING'],
            'source'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ASSESSMENT'],
            'confidence'            => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'notes'                 => ['type' => 'TEXT', 'null' => true],
            'version'               => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['student_id', 'learning_objective_id'], 'uq_mastery_student_tp');
        $this->forge->addKey(['learning_objective_id', 'result']);
        $this->forge->addKey('criterion_id');
        $this->forge->addKey('evidence_id');
        $this->forge->addKey('source_attempt_id');
        $this->forge->createTable('mastery_records', true);
    }

    private function createInterventions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'student_id'            => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
            'trigger_evidence_id'   => $this->bigInt(true),
            'intervention_type'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'REMEDIAL'],
            'planned_activity'      => ['type' => 'TEXT'],
            'scheduled_at'          => ['type' => 'DATETIME', 'null' => true],
            'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'RECOMMENDED'],
            'outcome'               => ['type' => 'TEXT', 'null' => true],
            'completed_at'          => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['student_id', 'status']);
        $this->forge->addKey(['learning_objective_id', 'intervention_type']);
        $this->forge->addKey('trigger_evidence_id');
        $this->forge->createTable('interventions', true);
    }

    private function createReportingPolicies(): void
    {
        $this->forge->addField($this->baseFields() + [
            'unit_id'             => $this->bigInt(),
            'academic_period_id'  => $this->bigInt(),
            'subject_id'          => $this->bigInt(true),
            'policy_name'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'calculation_method'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AVERAGE'],
            'config_json'         => ['type' => 'TEXT', 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'version'             => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['unit_id', 'academic_period_id', 'subject_id', 'version'], 'uq_reporting_policy_version');
        $this->forge->addKey(['unit_id', 'academic_period_id']);
        $this->forge->addKey('subject_id');
        $this->forge->createTable('reporting_policies', true);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function baseFields(): array
    {
        return [
            'id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
        ];
    }

    private function auditFields(): array
    {
        return [
            'created_by'  => $this->int(true),
            'updated_by'  => $this->int(true),
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function bigInt(bool $nullable = false): array
    {
        return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
    }

    private function int(bool $nullable = false): array
    {
        return ['type' => 'INT', 'unsigned' => true, 'null' => $nullable];
    }

    // ----------------------------------------------------------------
    // Permission & Feature Flag Seeding
    // ----------------------------------------------------------------

    private function seedPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->permissions as $code => $definition) {
            if ($this->db->table('permissions')->where('code', $code)->countAllResults() === 0) {
                $this->db->table('permissions')->insert($definition + [
                    'code'        => $code,
                    'description' => $definition['name'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $allPerms = array_keys($this->permissions);
        $roleMap = [
            'super_admin'        => $allPerms,
            'wakasek_kurikulum'  => $allPerms,
            'admin_smp'          => $allPerms,
            'admin_sma'          => $allPerms,
        ];

        foreach ($roleMap as $roleCode => $codes) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }

            foreach ($codes as $code) {
                $permission = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
                if (! $permission) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permission['id'])
                    ->countAllResults() > 0;
                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $role['id'],
                        'permission_id' => $permission['id'],
                        'created_at'    => $now,
                    ]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        foreach (array_keys($this->permissions) as $code) {
            $perm = $this->db->table('permissions')->where('code', $code)->get()->getRowArray();
            if ($perm) {
                $this->db->table('role_permissions')->where('permission_id', $perm['id'])->delete();
                $this->db->table('permissions')->where('id', $perm['id'])->delete();
            }
        }
    }

    private function seedFeatureFlag(): void
    {
        if (! $this->db->tableExists('feature_flags')) {
            return;
        }

        $code = 'ialos_phase6_assessment';
        $exists = $this->db->table('feature_flags')->where('code', $code)->countAllResults() > 0;
        if (! $exists) {
            $this->db->table('feature_flags')->insert([
                'code'        => $code,
                'name'        => 'Assessment & Mastery',
                'description' => 'Phase 6: gradebook, evidence, mastery TP, dan intervensi.',
                'enabled'     => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function removeFeatureFlag(): void
    {
        if (! $this->db->tableExists('feature_flags')) {
            return;
        }

        $this->db->table('feature_flags')->where('code', 'ialos_phase6_assessment')->delete();
    }
}