<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 5 — Daily Teaching Workspace
 *
 * Creates the learning session tables that bridge
 * schedule entries, lesson plans, attendance sessions,
 * and teacher reflections into one operational workflow.
 *
 * State Machine:
 *   PLANNED → IN_PROGRESS → COMPLETED → REFLECTED
 */
class CreatePhase5TeachingWorkspaceTables extends Migration
{
    private array $permissions = [
        'teaching.workspace' => ['module' => 'teaching', 'name' => 'View Teaching Workspace'],
        'teaching.teach'     => ['module' => 'teaching', 'name' => 'Conduct Teaching Sessions'],
        'teaching.reflect'   => ['module' => 'teaching', 'name' => 'Submit Teaching Reflections'],
    ];

    public function up(): void
    {
        $this->createLearningSessions();
        $this->createLearningSessionActivities();
        $this->createLearningSessionObservations();
        $this->createLearningSessionReflections();
        $this->seedPermissions();
    }

    public function down(): void
    {
        $this->removePermissions();

        foreach ([
            'learning_session_reflections',
            'learning_session_observations',
            'learning_session_activities',
            'learning_sessions',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    // ----------------------------------------------------------------
    // Table Definitions
    // ----------------------------------------------------------------

    private function createLearningSessions(): void
    {
        $this->forge->addField($this->baseFields() + [
            'academic_period_id'    => $this->bigInt(),
            'unit_id'               => $this->bigInt(),
            'teacher_id'            => $this->bigInt(),
            'classroom_id'          => $this->bigInt(),
            'subject_id'            => $this->bigInt(),
            'grade_level_id'        => $this->bigInt(true),
            'schedule_entry_id'     => $this->bigInt(true),
            'lesson_plan_id'        => $this->bigInt(true),
            'attendance_session_id' => $this->bigInt(true),
            'session_date'          => ['type' => 'DATE'],
            'meeting_number'        => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'jp_count'              => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'start_time'            => ['type' => 'TIME', 'null' => true],
            'end_time'              => ['type' => 'TIME', 'null' => true],
            'actual_start_time'     => ['type' => 'TIME', 'null' => true],
            'actual_end_time'       => ['type' => 'TIME', 'null' => true],
            'topic'                 => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'learning_objective_summary' => ['type' => 'TEXT', 'null' => true],
            'misconception_warnings'     => ['type' => 'TEXT', 'null' => true],
            'deviation_notes'       => ['type' => 'TEXT', 'null' => true],
            'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PLANNED'],
            'revision_number'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'started_at'            => ['type' => 'DATETIME', 'null' => true],
            'completed_at'          => ['type' => 'DATETIME', 'null' => true],
            'reflected_at'          => ['type' => 'DATETIME', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['academic_period_id', 'teacher_id', 'session_date']);
        $this->forge->addKey(['unit_id', 'classroom_id', 'subject_id']);
        $this->forge->addKey('schedule_entry_id');
        $this->forge->addKey('lesson_plan_id');
        $this->forge->addKey('attendance_session_id');
        $this->forge->addKey('status');
        $this->forge->createTable('learning_sessions', true);
    }

    private function createLearningSessionActivities(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_session_id'       => $this->bigInt(),
            'lesson_plan_activity_id'   => $this->bigInt(true),
            'lesson_plan_stage_id'      => $this->bigInt(true),
            'stage_type'                => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'title'                     => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'               => ['type' => 'TEXT', 'null' => true],
            'sequence_order'            => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'is_completed'              => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'completed_at'              => ['type' => 'DATETIME', 'null' => true],
            'actual_minutes'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notes'                     => ['type' => 'TEXT', 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['learning_session_id', 'stage_type']);
        $this->forge->addForeignKey('learning_session_id', 'learning_sessions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_session_activities', true);
    }

    private function createLearningSessionObservations(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_session_id' => $this->bigInt(),
            'student_id'          => $this->bigInt(true),
            'observation_type'    => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'GENERAL'],
            'rating'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'misconception_found' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'misconception_detail'=> ['type' => 'TEXT', 'null' => true],
            'follow_up_needed'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addKey(['learning_session_id', 'observation_type']);
        $this->forge->addKey('student_id');
        $this->forge->addForeignKey('learning_session_id', 'learning_sessions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_session_observations', true);
    }

    private function createLearningSessionReflections(): void
    {
        $this->forge->addField($this->baseFields() + [
            'learning_session_id'   => $this->bigInt(),
            'what_went_well'        => ['type' => 'TEXT', 'null' => true],
            'challenges'            => ['type' => 'TEXT', 'null' => true],
            'student_engagement'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'objective_achievement' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tp_coverage_notes'     => ['type' => 'TEXT', 'null' => true],
            'follow_up_plan'        => ['type' => 'TEXT', 'null' => true],
            'next_session_notes'    => ['type' => 'TEXT', 'null' => true],
            'self_rating'           => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
        ] + $this->auditFields());
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->addUniqueKey('learning_session_id', 'uq_reflection_session');
        $this->forge->addForeignKey('learning_session_id', 'learning_sessions', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('learning_session_reflections', true);
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
    // Permission Seeding
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
            'guru'               => $allPerms,
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
}
