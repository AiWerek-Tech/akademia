<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 10 — Quality & AI Copilot.
 *
 * Provides teacher reflection, supervision, and AI copilot governance:
 *   1. Teacher Reflections — structured self-reflection with AI draft support.
 *   2. Supervision Records — formal observation with ratings and follow-up.
 *   3. AI Copilot Outputs — auditable AI-generated content with provenance.
 */
class CreatePhase10QualityCopilotTables extends Migration
{
    private array $permissions = [
        'teacher_reflection.view'   => ['module' => 'quality', 'name' => 'View Teacher Reflections'],
        'teacher_reflection.manage' => ['module' => 'quality', 'name' => 'Manage Teacher Reflections'],
        'supervision.view'          => ['module' => 'quality', 'name' => 'View Supervision Records'],
        'supervision.manage'        => ['module' => 'quality', 'name' => 'Manage Supervision Records'],
        'ksp_evaluation.view'       => ['module' => 'quality', 'name' => 'View KSP Evaluation'],
        'ksp_evaluation.manage'     => ['module' => 'quality', 'name' => 'Manage KSP Evaluation'],
        'quality.copilot'           => ['module' => 'quality', 'name' => 'Access AI Copilot'],
    ];

    public function up(): void
    {
        $this->createReflections();
        $this->createSupervision();
        $this->createAiOutputs();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();
        foreach (['ai_copilot_outputs', 'supervision_records', 'teacher_reflections'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createReflections(): void
    {
        $this->forge->addField($this->baseFields() + [
            'teacher_id'        => $this->bigInt(),
            'unit_id'           => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'subject_id'        => $this->bigInt(true),
            'classroom_id'      => $this->bigInt(true),
            'reflection_type'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'POST_LESSON'],
            'what_went_well'    => ['type' => 'TEXT', 'null' => true],
            'what_to_improve'   => ['type' => 'TEXT', 'null' => true],
            'next_steps'        => ['type' => 'TEXT', 'null' => true],
            'ai_draft'          => ['type' => 'TEXT', 'null' => true],
            'ai_status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'NONE'],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['teacher_id', 'academic_period_id']);
        $this->forge->addKey('unit_id');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('teacher_reflections', true);
    }

    private function createSupervision(): void
    {
        $this->forge->addField($this->baseFields() + [
            'teacher_id'        => $this->bigInt(),
            'supervisor_id'     => $this->bigInt(),
            'unit_id'           => $this->bigInt(),
            'academic_period_id' => $this->int(),
            'observation_date'  => ['type' => 'DATE'],
            'subject_id'        => $this->bigInt(true),
            'classroom_id'      => $this->bigInt(true),
            'observation_type'  => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'CLASSROOM'],
            'strengths'         => ['type' => 'TEXT', 'null' => true],
            'areas_for_growth'  => ['type' => 'TEXT', 'null' => true],
            'recommendations'   => ['type' => 'TEXT', 'null' => true],
            'overall_rating'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'follow_up_needed'  => ['type' => 'TINYINT', 'default' => 0],
            'follow_up_notes'   => ['type' => 'TEXT', 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['teacher_id', 'academic_period_id']);
        $this->forge->addKey('supervisor_id');
        $this->forge->addKey('unit_id');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('supervisor_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('unit_id', 'school_units', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('academic_period_id', 'academic_periods', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('supervision_records', true);
    }

    private function createAiOutputs(): void
    {
        $this->forge->addField($this->baseFields() + [
            'user_id'           => $this->int(),
            'prompt_type'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'context_json'      => ['type' => 'TEXT', 'null' => true],
            'context_hash'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'input_prompt'      => ['type' => 'TEXT'],
            'output_text'       => ['type' => 'TEXT'],
            'model_provider'    => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'rule_engine'],
            'approval_status'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'DRAFT'],
            'human_edit'        => ['type' => 'TEXT', 'null' => true],
            'feedback_rating'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'feedback_notes'    => ['type' => 'TEXT', 'null' => true],
            'source_type'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'source_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['user_id', 'prompt_type']);
        $this->forge->addKey('approval_status');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('ai_copilot_outputs', true);
    }

    private function seedPermissions(): void
    {
        $db = \Config\Database::connect();
        foreach ($this->permissions as $code => $data) {
            if (! $db->table('permissions')->where('code', $code)->get()->getRowArray()) {
                $db->table('permissions')->insert(array_merge(['code' => $code], $data));
            }
        }
        $permIds = $db->table('permissions')->whereIn('code', array_keys($this->permissions))->get()->getResultArray();
        $permIdMap = array_column($permIds, 'id', 'code');
        $roles = $db->table('roles')->whereIn('code', [
            'super_admin', 'superadmin', 'wakasek_kurikulum', 'admin_smp', 'admin_sma', 'kepala_sekolah',
        ])->get()->getResultArray();
        foreach ($roles as $role) {
            foreach ($permIdMap as $permId) {
                $exists = $db->table('role_permissions')->where('role_id', $role['id'])->where('permission_id', $permId)->countAllResults();
                if (! $exists) {
                    $db->table('role_permissions')->insert(['role_id' => $role['id'], 'permission_id' => $permId]);
                }
            }
        }
    }

    private function removePermissions(): void
    {
        $db = \Config\Database::connect();
        $permIds = $db->table('permissions')->whereIn('code', array_keys($this->permissions))->get()->getResultArray();
        if (! empty($permIds)) {
            $db->table('role_permissions')->whereIn('permission_id', array_column($permIds, 'id'))->delete();
            $db->table('permissions')->whereIn('code', array_keys($this->permissions))->delete();
        }
    }

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

    private function auditFields(): array { return []; }
    private function bigInt(bool $n = false): array { return ['type' => 'BIGINT', 'unsigned' => true, 'null' => $n]; }
    private function int(): array { return ['type' => 'INT', 'unsigned' => true, 'null' => true]; }
}
