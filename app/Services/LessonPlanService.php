<?php

namespace App\Services;

use App\Exceptions\ConcurrencyException;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class LessonPlanService
{
    private const STATUSES = ['DRAFT', 'READY', 'IN_PROGRESS', 'COMPLETED', 'REFLECTED'];
    private const SOURCE_TYPES = ['USE_AS_IS', 'ADAPT', 'CLONE', 'CUSTOM'];
    private const STAGE_TYPES = ['MEMAHAMI', 'MENGAPLIKASI', 'MEREFLEKSI'];
    private const ASSESSMENT_PURPOSES = ['INITIAL', 'FORMATIVE', 'SUMMATIVE'];
    private const ACTIVITY_STATUSES = ['PLANNED', 'DONE', 'SKIPPED'];

    /**
     * Create a new lesson plan.
     */
    public static function create(array $data): array
    {
        EducationFoundationService::requireFields($data, [
            'academic_period_id', 'unit_id', 'subject_id', 'grade_level_id',
            'teacher_id', 'date',
        ]);
        UnitScopeService::assertUnit((int) $data['unit_id']);

        $now = date('Y-m-d H:i:s');
        $record = [
            'uuid' => UuidService::v4(),
            'academic_period_id' => (int) $data['academic_period_id'],
            'unit_id' => (int) $data['unit_id'],
            'subject_id' => (int) $data['subject_id'],
            'grade_level_id' => (int) $data['grade_level_id'],
            'class_id' => isset($data['class_id']) ? (int) $data['class_id'] : null,
            'teacher_id' => (int) $data['teacher_id'],
            'schedule_entry_id' => isset($data['schedule_entry_id']) ? (int) $data['schedule_entry_id'] : null,
            'learning_pack_id' => isset($data['learning_pack_id']) ? (int) $data['learning_pack_id'] : null,
            'learning_unit_id' => isset($data['learning_unit_id']) ? (int) $data['learning_unit_id'] : null,
            'date' => $data['date'],
            'session_number' => (int) ($data['session_number'] ?? 1),
            'session_label' => $data['session_label'] ?? null,
            'source_type' => self::enum($data['source_type'] ?? 'CUSTOM', self::SOURCE_TYPES, 'source_type'),
            'source_locator' => $data['source_locator'] ?? null,
            'parent_plan_id' => isset($data['parent_plan_id']) ? (int) $data['parent_plan_id'] : null,
            'status' => 'DRAFT',
            'revision_number' => 1,
            'created_by' => EducationFoundationService::actorId(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db = Database::connect();
        $db->table('lesson_plans')->insert($record);
        $id = (int) $db->insertID();
        AuditService::log('lesson_plans', 'CREATE_PLAN', 'LessonPlan', $id, null, $record, null, $record['uuid']);

        return $db->table('lesson_plans')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Clone an existing lesson plan.
     */
    public static function clone(string $sourceUuid, array $data): array
    {
        $source = self::planByUuid($sourceUuid);
        $plan = self::create([
            'academic_period_id' => (int) ($data['academic_period_id'] ?? $source['academic_period_id']),
            'unit_id' => (int) ($data['unit_id'] ?? $source['unit_id']),
            'subject_id' => (int) ($data['subject_id'] ?? $source['subject_id']),
            'grade_level_id' => (int) ($data['grade_level_id'] ?? $source['grade_level_id']),
            'class_id' => $data['class_id'] ?? $source['class_id'],
            'teacher_id' => (int) $data['teacher_id'],
            'date' => $data['date'] ?? $source['date'],
            'session_number' => (int) ($data['session_number'] ?? $source['session_number'] + 1),
            'session_label' => $data['session_label'] ?? $source['session_label'],
            'source_type' => 'CLONE',
            'learning_pack_id' => $source['learning_pack_id'],
            'learning_unit_id' => $source['learning_unit_id'],
            'parent_plan_id' => (int) $source['id'],
        ]);

        // Clone identification/design fields
        $db = Database::connect();
        $db->table('lesson_plans')->where('id', (int) $plan['id'])->update([
            'identification_notes' => $source['identification_notes'],
            'learner_readiness' => $source['learner_readiness'],
            'material_characteristics' => $source['material_characteristics'],
            'pedagogical_practice' => $source['pedagogical_practice'],
            'learning_partnership' => $source['learning_partnership'],
            'learning_environment' => $source['learning_environment'],
            'digital_utilization' => $source['digital_utilization'],
            'interdisciplinary_notes' => $source['interdisciplinary_notes'],
            'graduate_profile_dimensions' => $source['graduate_profile_dimensions'],
        ]);

        // Clone objectives
        $objectives = $db->table('lesson_plan_objectives')
            ->where('lesson_plan_id', (int) $source['id'])
            ->get()->getResultArray();
        foreach ($objectives as $obj) {
            $db->table('lesson_plan_objectives')->insert([
                'uuid' => UuidService::v4(),
                'lesson_plan_id' => (int) $plan['id'],
                'learning_objective_id' => (int) $obj['learning_objective_id'],
                'role' => $obj['role'],
                'sequence_order' => (int) $obj['sequence_order'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Clone stages
        $stages = $db->table('lesson_plan_stages')
            ->where('lesson_plan_id', (int) $source['id'])
            ->orderBy('sequence_order')
            ->get()->getResultArray();
        $stageMap = [];
        foreach ($stages as $stage) {
            $newStageId = $db->insertID();
            $db->table('lesson_plan_stages')->insert([
                'uuid' => UuidService::v4(),
                'lesson_plan_id' => (int) $plan['id'],
                'stage_type' => $stage['stage_type'],
                'sequence_order' => (int) $stage['sequence_order'],
                'title' => $stage['title'],
                'description' => $stage['description'],
                'estimated_minutes' => $stage['estimated_minutes'],
                'notes' => $stage['notes'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $stageMap[(int) $stage['id']] = (int) $db->insertID();
        }

        // Clone activities
        $activities = $db->table('lesson_plan_activities')
            ->where('lesson_plan_id', (int) $source['id'])
            ->orderBy('sequence_order')
            ->get()->getResultArray();
        foreach ($activities as $act) {
            $newStageId = isset($act['lesson_plan_stage_id']) ? ($stageMap[(int) $act['lesson_plan_stage_id']] ?? null) : null;
            $db->table('lesson_plan_activities')->insert([
                'uuid' => UuidService::v4(),
                'lesson_plan_id' => (int) $plan['id'],
                'lesson_plan_stage_id' => $newStageId,
                'learning_activity_id' => $act['learning_activity_id'] ? (int) $act['learning_activity_id'] : null,
                'custom_title' => $act['custom_title'],
                'custom_description' => $act['custom_description'],
                'delivery_mode' => $act['delivery_mode'],
                'grouping_mode' => $act['grouping_mode'],
                'estimated_minutes' => $act['estimated_minutes'],
                'sequence_order' => (int) $act['sequence_order'],
                'status' => 'PLANNED',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Clone assessments
        $assessments = $db->table('lesson_plan_assessments')
            ->where('lesson_plan_id', (int) $source['id'])
            ->orderBy('sequence_order')
            ->get()->getResultArray();
        foreach ($assessments as $assess) {
            $db->table('lesson_plan_assessments')->insert([
                'uuid' => UuidService::v4(),
                'lesson_plan_id' => (int) $plan['id'],
                'assessment_purpose' => $assess['assessment_purpose'],
                'recommended_method' => $assess['recommended_method'],
                'criteria_reference' => $assess['criteria_reference'],
                'notes' => $assess['notes'],
                'sequence_order' => (int) $assess['sequence_order'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        AuditService::log('lesson_plans', 'CLONE_PLAN', 'LessonPlan', (int) $plan['id'], $source, $plan, null, $plan['uuid']);

        return self::planByUuid($plan['uuid']);
    }

    /**
     * Transition lesson plan status.
     */
    public static function transition(string $uuid, string $target, int $revisionNumber): array
    {
        $target = self::enum($target, self::STATUSES, 'status');
        $current = self::planByUuid($uuid);
        $allowed = [
            'DRAFT' => ['READY', 'IN_PROGRESS'],
            'READY' => ['IN_PROGRESS', 'DRAFT'],
            'IN_PROGRESS' => ['COMPLETED'],
            'COMPLETED' => ['REFLECTED'],
            'REFLECTED' => [],
        ];
        if (! in_array($target, $allowed[$current['status']] ?? [], true)) {
            throw new InvalidArgumentException("Transisi dari {$current['status']} ke {$target} tidak valid.");
        }

        $prefix = strtolower($target);
        $changes = ['status' => $target, 'revision_number' => $revisionNumber];
        if (in_array($target, ['READY', 'COMPLETED', 'REFLECTED'], true)) {
            $changes[$prefix . '_by'] = EducationFoundationService::actorId();
            $changes[$prefix . '_at'] = date('Y-m-d H:i:s');
        }

        $updated = EducationFoundationService::atomicUpdate('lesson_plans', $current, $changes);
        AuditService::log('lesson_plans', $target . '_PLAN', 'LessonPlan', (int) $current['id'], $current, $updated, null, $uuid);

        return $updated;
    }

    /**
     * Add a learning objective (TP) to the lesson plan.
     */
    public static function addObjective(string $planUuid, string $objectiveUuid, array $data = []): array
    {
        $plan = self::mutablePlan($planUuid);
        $objective = EducationFoundationService::byUuid('learning_objectives_tp', $objectiveUuid);
        $role = self::enum($data['role'] ?? 'PRIMARY', ['PRIMARY', 'SUPPORTING', 'ENRICHMENT'], 'role');

        $db = Database::connect();
        $db->table('lesson_plan_objectives')->ignore(true)->insert([
            'uuid' => UuidService::v4(),
            'lesson_plan_id' => (int) $plan['id'],
            'learning_objective_id' => (int) $objective['id'],
            'role' => $role,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $row = $db->table('lesson_plan_objectives')
            ->where('lesson_plan_id', (int) $plan['id'])
            ->where('learning_objective_id', (int) $objective['id'])
            ->get()->getRowArray();
        AuditService::log('lesson_plans', 'ADD_OBJECTIVE', 'LessonPlanObjective', (int) $row['id'], null, $row, null, $planUuid);

        return $row;
    }

    /**
     * Add a learning experience stage.
     */
    public static function addStage(string $planUuid, array $data): array
    {
        $plan = self::mutablePlan($planUuid);
        $stageType = self::enum($data['stage_type'], self::STAGE_TYPES, 'stage_type');

        return self::insert('lesson_plan_stages', [
            'lesson_plan_id' => (int) $plan['id'],
            'stage_type' => $stageType,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], 'ADD_STAGE', $planUuid);
    }

    /**
     * Add an activity to the lesson plan.
     */
    public static function addActivity(string $planUuid, array $data): array
    {
        $plan = self::mutablePlan($planUuid);
        $stageId = isset($data['lesson_plan_stage_uuid'])
            ? (int) self::stageByUuid((string) $data['lesson_plan_stage_uuid'])['id']
            : null;
        $activityId = isset($data['learning_activity_id']) ? (int) $data['learning_activity_id'] : null;

        return self::insert('lesson_plan_activities', [
            'lesson_plan_id' => (int) $plan['id'],
            'lesson_plan_stage_id' => $stageId,
            'learning_activity_id' => $activityId,
            'custom_title' => $data['custom_title'] ?? null,
            'custom_description' => $data['custom_description'] ?? null,
            'delivery_mode' => $data['delivery_mode'] ?? 'OTHER',
            'grouping_mode' => $data['grouping_mode'] ?? 'FLEXIBLE',
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'teacher_notes' => $data['teacher_notes'] ?? null,
        ], 'ADD_ACTIVITY', $planUuid);
    }

    /**
     * Add an assessment reference to the lesson plan.
     */
    public static function addAssessment(string $planUuid, array $data): array
    {
        $plan = self::mutablePlan($planUuid);
        $purpose = self::enum($data['assessment_purpose'] ?? 'FORMATIVE', self::ASSESSMENT_PURPOSES, 'assessment_purpose');

        return self::insert('lesson_plan_assessments', [
            'lesson_plan_id' => (int) $plan['id'],
            'assessment_purpose' => $purpose,
            'recommended_method' => trim((string) ($data['recommended_method'] ?? '')),
            'criteria_reference' => $data['criteria_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
        ], 'ADD_ASSESSMENT', $planUuid);
    }

    /**
     * Update identification and design fields.
     */
    public static function updateDesign(string $planUuid, array $data): array
    {
        $plan = self::mutablePlan($planUuid);
        $fields = array_intersect_key($data, array_flip([
            'identification_notes', 'learner_readiness', 'material_characteristics',
            'pedagogical_practice', 'learning_partnership', 'learning_environment',
            'digital_utilization', 'interdisciplinary_notes', 'graduate_profile_dimensions',
        ]));
        if ($fields === []) {
            return $plan;
        }
        $fields['updated_at'] = date('Y-m-d H:i:s');

        $db = Database::connect();
        $db->table('lesson_plans')->where('id', (int) $plan['id'])->update($fields);
        AuditService::log('lesson_plans', 'UPDATE_DESIGN', 'LessonPlan', (int) $plan['id'], $plan, $fields, null, $planUuid);

        return self::planByUuid($planUuid);
    }

    /**
     * Get plan summary with coverage stats.
     */
    public static function summary(string $planUuid): array
    {
        $plan = self::planByUuid($planUuid);
        $db = Database::connect();
        $planId = (int) $plan['id'];

        $objectives = $db->table('lesson_plan_objectives')
            ->where('lesson_plan_id', $planId)
            ->countAllResults();
        $stages = $db->table('lesson_plan_stages')
            ->where('lesson_plan_id', $planId)
            ->countAllResults();
        $activities = $db->table('lesson_plan_activities')
            ->where('lesson_plan_id', $planId)
            ->countAllResults();
        $assessments = $db->table('lesson_plan_assessments')
            ->where('lesson_plan_id', $planId)
            ->countAllResults();

        $minutesRow = $db->query('SELECT COALESCE(SUM(estimated_minutes), 0) AS total FROM lesson_plan_activities WHERE lesson_plan_id = ?', [$planId])->getRowArray();
        $totalMinutes = (int) ($minutesRow['total'] ?? 0);

        $warnings = [];
        if ($objectives === 0) {
            $warnings[] = 'Belum ada TP yang ditautkan.';
        }
        if ($stages === 0) {
            $warnings[] = 'Belum ada tahap pengalaman belajar.';
        }
        if ($activities === 0) {
            $warnings[] = 'Belum ada aktivitas.';
        }
        if ($assessments === 0) {
            $warnings[] = 'Belum ada rencana asesmen.';
        }

        return [
            'plan' => $plan,
            'objectives_count' => $objectives,
            'stages_count' => $stages,
            'activities_count' => $activities,
            'assessments_count' => $assessments,
            'total_estimated_minutes' => (int) $totalMinutes,
            'warnings' => $warnings,
        ];
    }

    // ---- Rubric methods ----

    /**
     * Add an assessment rubric criterion.
     */
    public static function addRubric(string $assessmentUuid, array $data): array
    {
        $assessment = EducationFoundationService::byUuid('lesson_plan_assessments', $assessmentUuid);
        $plan = self::planById((int) $assessment['lesson_plan_id']);

        $rubricLevels = null;
        if (isset($data['rubric_levels'])) {
            $rubricLevels = is_string($data['rubric_levels']) ? $data['rubric_levels'] : json_encode($data['rubric_levels'], JSON_UNESCAPED_UNICODE);
        }

        return self::insert('lesson_plan_assessment_rubrics', [
            'lesson_plan_assessment_id' => (int) $assessment['id'],
            'criterion_description' => trim((string) ($data['criterion_description'] ?? '')),
            'rubric_levels' => $rubricLevels,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
        ], 'ADD_RUBRIC', $plan['uuid']);
    }

    // ---- Activity resource methods ----

    /**
     * Link a resource to an activity.
     */
    public static function linkActivityResource(string $activityUuid, array $data): array
    {
        $activity = EducationFoundationService::byUuid('lesson_plan_activities', $activityUuid);
        $plan = self::planById((int) $activity['lesson_plan_id']);

        return self::insert('lesson_plan_activity_resources', [
            'lesson_plan_activity_id' => (int) $activity['id'],
            'learning_resource_id' => isset($data['learning_resource_id']) ? (int) $data['learning_resource_id'] : null,
            'custom_description' => $data['custom_description'] ?? null,
            'quantity' => (int) ($data['quantity'] ?? 1),
            'is_required' => ! empty($data['is_required']) ? 1 : 0,
        ], 'LINK_ACTIVITY_RESOURCE', $plan['uuid']);
    }

    // ---- Plan validation ----

    /**
     * Validate a plan is complete enough to transition to READY.
     */
    public static function validatePlan(string $planUuid): array
    {
        $plan = self::planByUuid($planUuid);
        $db = Database::connect();
        $planId = (int) $plan['id'];

        $objectives = $db->table('lesson_plan_objectives')->where('lesson_plan_id', $planId)->countAllResults();
        $stages = $db->table('lesson_plan_stages')->where('lesson_plan_id', $planId)->countAllResults();
        $activities = $db->table('lesson_plan_activities')->where('lesson_plan_id', $planId)->countAllResults();
        $assessments = $db->table('lesson_plan_assessments')->where('lesson_plan_id', $planId)->countAllResults();

        $errors = [];
        if (empty($plan['identification_notes'])) {
            $errors[] = 'identification_notes_missing';
        }
        if ($objectives === 0) {
            $errors[] = 'no_objectives';
        }
        if ($stages === 0) {
            $errors[] = 'no_stages';
        }
        if ($activities === 0) {
            $errors[] = 'no_activities';
        }
        if ($assessments === 0) {
            $errors[] = 'no_assessments';
        }

        return ['valid' => $errors === [], 'errors' => $errors];
    }

    // ---- Internal helpers ----

    private static function planByUuid(string $uuid, bool $assertUnit = true): array
    {
        $plan = EducationFoundationService::byUuid('lesson_plans', $uuid);
        if ($assertUnit) {
            UnitScopeService::assertUnit((int) $plan['unit_id']);
        }
        return $plan;
    }

    private static function planById(int $id): array
    {
        $row = Database::connect()->table('lesson_plans')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            throw new RuntimeException('Lesson plan tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $row['unit_id']);
        return $row;
    }

    private static function mutablePlan(string $uuid): array
    {
        $plan = self::planByUuid($uuid);
        if (in_array($plan['status'], ['COMPLETED', 'REFLECTED'], true)) {
            throw new RuntimeException('Lesson plan yang sudah selesai tidak dapat diubah.');
        }
        return $plan;
    }

    private static function stageByUuid(string $uuid): array
    {
        $stage = EducationFoundationService::byUuid('lesson_plan_stages', $uuid);
        $plan = Database::connect()->table('lesson_plans')
            ->where('id', (int) $stage['lesson_plan_id'])->get()->getRowArray();
        UnitScopeService::assertUnit((int) $plan['unit_id']);
        return $stage;
    }

    private static function insert(string $table, array $data, string $action, ?string $uuidForAudit = null): array
    {
        $now = date('Y-m-d H:i:s');
        $record = ['uuid' => UuidService::v4()] + $data + [
            'created_by' => EducationFoundationService::actorId(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $db = Database::connect();
        $db->table($table)->insert($record);
        $id = (int) $db->insertID();
        $row = $db->table($table)->where('id', $id)->get()->getRowArray();
        AuditService::log('lesson_plans', $action, $table, $id, null, $row, null, $uuidForAudit ?? $row['uuid']);

        return $row;
    }

    private static function enum($value, array $allowed, string $field): string
    {
        $value = strtoupper(trim((string) $value));
        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Nilai {$field} tidak valid.");
        }
        return $value;
    }
}
