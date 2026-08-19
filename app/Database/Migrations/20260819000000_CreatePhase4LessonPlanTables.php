<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhase4LessonPlanTables extends Migration
{
    private array $permissions = [
        'lesson_plans.view' => ['module' => 'lesson_plans', 'name' => 'View Lesson Plans'],
        'lesson_plans.manage' => ['module' => 'lesson_plans', 'name' => 'Manage Lesson Plans'],
        'lesson_plans.clone' => ['module' => 'lesson_plans', 'name' => 'Clone Lesson Plans'],
    ];

    public function up(): void
    {
        $this->createLessonPlans();
        $this->createLessonPlanObjectives();
        $this->createLessonPlanStages();
        $this->createLessonPlanActivities();
        $this->createLessonPlanAssessments();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();

        foreach ([
            'lesson_plan_assessments',
            'lesson_plan_activities',
            'lesson_plan_stages',
            'lesson_plan_objectives',
            'lesson_plans',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function createLessonPlans(): void
    {
        $this->forge->addField($this->baseFields() + [
            'academic_period_id' => $this->bigInt(),
            'unit_id' => $this->bigInt(),
            'subject_id' => $this->bigInt(),
            'grade_level_id' => $this->bigInt(),
            'class_id' => $this->bigInt(true),
            'teacher_id' => $this->bigInt(),
            'schedule_entry_id' => $this->bigInt(true),
            'learning_pack_id' => $this->bigInt(true),
            'learning_unit_id' => $this->bigInt(true),
            'date' => ['type' => 'DATE'],
            'session_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'session_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'source_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'CUSTOM'],
            'source_locator' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'parent_plan_id' => $this->bigInt(true),
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'revision_number' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'identification_notes' => ['type' => 'TEXT', 'null' => true],
            'learner_readiness' => ['type' => 'TEXT', 'null' => true],
            'material_characteristics' => ['type' => 'TEXT', 'null' => true],
            'pedagogical_practice' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'learning_partnership' => ['type' => 'TEXT', 'null' => true],
            'learning_environment' => ['type' => 'TEXT', 'null' => true],
            'digital_utilization' => ['type' => 'TEXT', 'null' => true],
            'interdisciplinary_notes' => ['type' => 'TEXT', 'null' => true],
            'graduate_profile_dimensions' => ['type' => 'TEXT', 'null' => true],
            'ready_by' => $this->int(true),
            'ready_at' => ['type' => 'DATETIME', 'null' => true],
            'validated_by' => $this->int(true),
            'validated_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => $this->int(true),
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_by' => $this->int(true),
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'reflected_by' => $this->int(true),
            'reflected_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['academic_period_id', 'teacher_id', 'date']);
        $this->forge->addKey(['unit_id', 'subject_id', 'grade_level_id']);
        $this->forge->addKey(['status']);
        $this->forge->createTable('lesson_plans', true);
    }

    private function createLessonPlanObjectives(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
            'lesson_plan_id' => $this->bigInt(),
            'learning_objective_id' => $this->bigInt(),
            'role' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'PRIMARY'],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey(['lesson_plan_id', 'learning_objective_id', 'role'], 'uq_lp_obj_role');
        $this->forge->addForeignKey('lesson_plan_id', 'lesson_plans', 'id', 'CASCADE', 'RESTRICT');
        // FK to learning_objectives_tp omitted — enforced at application level
        // to avoid pre-existing migration idempotency issues.
        $this->forge->createTable('lesson_plan_objectives', true);
    }

    private function createLessonPlanStages(): void
    {
        $this->forge->addField($this->baseFields() + [
            'lesson_plan_id' => $this->bigInt(),
            'stage_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'estimated_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['lesson_plan_id', 'stage_type']);
        $this->forge->addForeignKey('lesson_plan_id', 'lesson_plans', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('lesson_plan_stages', true);
    }

    private function createLessonPlanActivities(): void
    {
        $this->forge->addField($this->baseFields() + [
            'lesson_plan_id' => $this->bigInt(),
            'lesson_plan_stage_id' => $this->bigInt(true),
            'learning_activity_id' => $this->bigInt(true),
            'custom_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'custom_description' => ['type' => 'TEXT', 'null' => true],
            'delivery_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'OTHER'],
            'grouping_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'FLEXIBLE'],
            'estimated_minutes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PLANNED'],
            'teacher_notes' => ['type' => 'TEXT', 'null' => true],
            'graduate_profile_alignment' => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['lesson_plan_id', 'lesson_plan_stage_id']);
        $this->forge->addForeignKey('lesson_plan_id', 'lesson_plans', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('lesson_plan_stage_id', 'lesson_plan_stages', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->addForeignKey('learning_activity_id', 'learning_activities', 'id', 'SET NULL', 'RESTRICT');
        $this->forge->createTable('lesson_plan_activities', true);
    }

    private function createLessonPlanAssessments(): void
    {
        $this->forge->addField($this->baseFields() + [
            'lesson_plan_id' => $this->bigInt(),
            'assessment_purpose' => ['type' => 'VARCHAR', 'constraint' => 30],
            'recommended_method' => ['type' => 'VARCHAR', 'constraint' => 255],
            'criteria_reference' => ['type' => 'TEXT', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'sequence_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['lesson_plan_id', 'assessment_purpose']);
        $this->forge->addForeignKey('lesson_plan_id', 'lesson_plans', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('lesson_plan_assessments', true);
    }

    private function baseFields(): array
    {
        return [
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'uuid' => ['type' => 'CHAR', 'constraint' => 36],
        ];
    }

    private function auditFields(): array
    {
        return [
            'created_by' => $this->int(true),
            'updated_by' => $this->int(true),
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function bigInt(bool $nullable = false, ?string $after = null): array
    {
        $field = ['type' => 'BIGINT', 'unsigned' => true, 'null' => $nullable];
        if ($after !== null) {
            $field['after'] = $after;
        }
        return $field;
    }

    private function int(bool $nullable = false, ?string $after = null): array
    {
        $field = ['type' => 'INT', 'unsigned' => true, 'null' => $nullable];
        if ($after !== null) {
            $field['after'] = $after;
        }
        return $field;
    }

    private function seedPermissions(): void
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->permissions as $code => $definition) {
            if ($this->db->table('permissions')->where('code', $code)->countAllResults() === 0) {
                $this->db->table('permissions')->insert($definition + [
                    'code' => $code,
                    'description' => $definition['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $roleMap = [
            'super_admin' => array_keys($this->permissions),
            'wakasek_kurikulum' => array_keys($this->permissions),
            'admin_smp' => ['lesson_plans.view', 'lesson_plans.manage', 'lesson_plans.clone'],
            'admin_sma' => ['lesson_plans.view', 'lesson_plans.manage', 'lesson_plans.clone'],
            'guru' => ['lesson_plans.view', 'lesson_plans.manage', 'lesson_plans.clone'],
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
                        'role_id' => $role['id'],
                        'permission_id' => $permission['id'],
                        'created_at' => $now,
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
}
