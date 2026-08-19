<?php

namespace App\Services;

use App\Exceptions\ConcurrencyException;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class SubjectLearningPackEngineService
{
    private const PACK_STATUSES = ['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED', 'ARCHIVED'];
    private const SOURCE_TYPES = ['NATIONAL_REFERENCE', 'SCHOOL_ADAPTATION', 'TEACHER_ADAPTATION', 'CUSTOM'];
    private const UNIT_TYPES = ['CHAPTER', 'UNIT', 'TOPIC', 'SUBTOPIC', 'MODULE', 'PROJECT', 'OTHER'];
    private const OBJECTIVE_ROLES = ['PRIMARY', 'SUPPORTING', 'PREREQUISITE', 'ENRICHMENT'];
    private const CONCEPT_TYPES = ['CORE', 'SUPPORTING', 'EXTENSION', 'CROSS_DISCIPLINARY'];
    private const CONCEPT_RELATIONS = ['PREREQUISITE', 'BUILDS_ON', 'RELATED_TO', 'CONTRASTS_WITH', 'APPLIES_TO', 'EXTENDS'];
    private const MATERIAL_LEVELS = ['ESSENTIAL', 'SUPPORTING', 'EXTENSION'];
    private const MISCONCEPTION_SEVERITIES = ['INFO', 'ATTENTION', 'IMPORTANT'];
    private const ACTIVATION_TYPES = ['APERCEPTION', 'PRIOR_KNOWLEDGE', 'HOOK', 'QUESTION_PROMPT', 'CONTEXTUAL_TRIGGER'];
    private const DELIVERY_MODES = ['PLUGGED', 'UNPLUGGED', 'HYBRID', 'PHYSICAL', 'FIELD', 'DISCUSSION', 'PRACTICE', 'PROJECT', 'LAB', 'OTHER'];
    private const GROUPING_MODES = ['INDIVIDUAL', 'PAIR', 'SMALL_GROUP', 'LARGE_GROUP', 'WHOLE_CLASS', 'FLEXIBLE'];
    private const RESOURCE_TYPES = ['BOOK', 'DOCUMENT', 'VIDEO', 'WEBSITE', 'DEVICE', 'APPLICATION', 'LAB_EQUIPMENT', 'MATERIAL', 'ROOM', 'COMMUNITY_RESOURCE', 'PARTNER', 'OTHER'];
    private const GUIDANCE_TYPES = ['PREPARATION', 'INSTRUCTION', 'FACILITATION', 'QUESTION', 'SCAFFOLDING', 'TROUBLESHOOTING', 'SAFETY', 'REFLECTION_PROMPT'];
    private const RESPONSE_TYPES = ['EXPECTED', 'COMMON_DIFFICULTY', 'COMMON_ERROR', 'ALTERNATIVE_RESPONSE', 'MISCONCEPTION_SIGNAL'];
    private const EXPERIENCES = ['UNDERSTAND', 'APPLY', 'REFLECT'];
    private const ASSESSMENT_PURPOSES = ['INITIAL', 'FORMATIVE', 'SUMMATIVE'];
    private const FOLLOWUP_TYPES = ['REMEDIAL', 'REINFORCEMENT', 'ENRICHMENT'];
    private const REFLECTION_AUDIENCES = ['STUDENT', 'TEACHER'];

    public static function createPack(array $data): array
    {
        EducationFoundationService::requireFields($data, ['curriculum_version_id', 'unit_id', 'subject_id', 'grade_level_id', 'code', 'name']);
        UnitScopeService::assertUnit((int) $data['unit_id']);
        UnitScopeService::assertSubjectInUnit((int) $data['subject_id'], (int) $data['unit_id']);

        $sourceType = self::enum($data['source_type'] ?? 'CUSTOM', self::SOURCE_TYPES, 'source_type');
        $parentId = null;
        if (! empty($data['parent_pack_uuid'])) {
            $parent = self::packByUuid((string) $data['parent_pack_uuid'], false);
            $parentId = (int) $parent['id'];
            if ($sourceType !== 'NATIONAL_REFERENCE') {
                UnitScopeService::assertUnit((int) $data['unit_id']);
            }
        }

        $now = date('Y-m-d H:i:s');
        $record = [
            'uuid' => UuidService::v4(),
            'curriculum_version_id' => (int) $data['curriculum_version_id'],
            'unit_id' => (int) $data['unit_id'],
            'subject_id' => (int) $data['subject_id'],
            'grade_level_id' => (int) $data['grade_level_id'],
            'phase' => strtoupper(trim((string) ($data['phase'] ?? ''))),
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'source_type' => $sourceType,
            'source_id' => isset($data['source_id']) ? (int) $data['source_id'] : null,
            'source_locator' => $data['source_locator'] ?? null,
            'parent_pack_id' => $parentId,
            'status' => 'DRAFT',
            'revision_number' => 1,
            'created_by' => EducationFoundationService::actorId(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db = Database::connect();
        $db->table('subject_learning_packs')->insert($record);
        $id = (int) $db->insertID();
        AuditService::log('learning_packs', 'CREATE_PACK', 'SubjectLearningPack', $id, null, $record, null, $record['uuid']);

        return $db->table('subject_learning_packs')->where('id', $id)->get()->getRowArray();
    }

    public static function clonePack(string $sourceUuid, array $data): array
    {
        $source = self::packByUuid($sourceUuid, false);
        $clone = self::createPack([
            'curriculum_version_id' => $data['curriculum_version_id'] ?? $source['curriculum_version_id'],
            'unit_id' => $data['unit_id'] ?? $source['unit_id'],
            'subject_id' => $data['subject_id'] ?? $source['subject_id'],
            'grade_level_id' => $data['grade_level_id'] ?? $source['grade_level_id'],
            'phase' => $data['phase'] ?? $source['phase'],
            'code' => $data['code'],
            'name' => $data['name'] ?? ($source['name'] . ' Adaptasi'),
            'description' => $data['description'] ?? $source['description'],
            'source_type' => $data['source_type'] ?? 'SCHOOL_ADAPTATION',
            'source_id' => $data['source_id'] ?? $source['source_id'],
            'source_locator' => $data['source_locator'] ?? $source['source_locator'],
            'parent_pack_uuid' => $sourceUuid,
        ]);
        AuditService::log('learning_packs', 'CLONE_PACK', 'SubjectLearningPack', (int) $clone['id'], $source, $clone, null, $clone['uuid']);

        return $clone;
    }

    public static function transition(string $uuid, string $target, int $revisionNumber): array
    {
        $target = self::enum($target, self::PACK_STATUSES, 'status');
        $current = self::packByUuid($uuid);
        $allowed = [
            'DRAFT' => ['VALIDATED', 'ARCHIVED'],
            'VALIDATED' => ['REVIEWED', 'DRAFT', 'ARCHIVED'],
            'REVIEWED' => ['APPROVED', 'DRAFT', 'ARCHIVED'],
            'APPROVED' => ['LOCKED', 'ARCHIVED'],
            'LOCKED' => ['ARCHIVED'],
            'ARCHIVED' => [],
        ];
        if (! in_array($target, $allowed[$current['status']] ?? [], true)) {
            throw new InvalidArgumentException('Transisi status learning pack tidak valid.');
        }

        $prefix = strtolower($target);
        $changes = ['status' => $target, 'revision_number' => $revisionNumber];
        if (in_array($target, ['VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED'], true)) {
            $changes[$prefix . '_by'] = EducationFoundationService::actorId();
            $changes[$prefix . '_at'] = date('Y-m-d H:i:s');
        }

        $updated = EducationFoundationService::atomicUpdate('subject_learning_packs', $current, $changes);
        AuditService::log('learning_packs', $target . '_PACK', 'SubjectLearningPack', (int) $current['id'], $current, $updated, null, $uuid);

        return $updated;
    }

    public static function createUnit(string $packUuid, array $data): array
    {
        $pack = self::mutablePack($packUuid);
        self::enum($data['unit_type'] ?? 'UNIT', self::UNIT_TYPES, 'unit_type');
        $parentId = self::optionalScopedUnitId($data['parent_unit_uuid'] ?? null, (int) $pack['id']);

        return self::insert('learning_units', [
            'learning_pack_id' => (int) $pack['id'],
            'parent_unit_id' => $parentId,
            'code' => strtoupper(trim((string) $data['code'])),
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'unit_type' => strtoupper((string) ($data['unit_type'] ?? 'UNIT')),
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'copyright_notes' => $data['copyright_notes'] ?? null,
            'license_notes' => $data['license_notes'] ?? null,
            'revision_number' => 1,
            'status' => strtoupper((string) ($data['status'] ?? 'DRAFT')),
        ], 'CREATE_UNIT', $pack['uuid']);
    }

    public static function mapUnitObjective(string $unitUuid, string $objectiveUuid, array $data = []): void
    {
        $unit = self::unitByUuid($unitUuid);
        $pack = self::mutablePackById((int) $unit['learning_pack_id']);
        $objective = EducationFoundationService::byUuid('learning_objectives_tp', $objectiveUuid);
        self::assertObjectiveFitsPack($objective, $pack);
        $role = self::enum($data['role'] ?? 'PRIMARY', self::OBJECTIVE_ROLES, 'role');
        Database::connect()->table('learning_unit_objectives')->ignore(true)->insert([
            'learning_unit_id' => (int) $unit['id'],
            'learning_objective_id' => (int) $objective['id'],
            'role' => $role,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        AuditService::log('learning_packs', 'MAP_UNIT_OBJECTIVE', 'LearningUnit', (int) $unit['id'], null, ['objective_id' => $objective['id'], 'role' => $role], null, $unitUuid);
    }

    public static function createConcept(string $packUuid, array $data): array
    {
        $pack = self::mutablePack($packUuid);
        $unitId = self::optionalScopedUnitId($data['learning_unit_uuid'] ?? null, (int) $pack['id']);
        return self::insert('learning_concepts', [
            'learning_pack_id' => (int) $pack['id'],
            'learning_unit_id' => $unitId,
            'code' => strtoupper(trim((string) $data['code'])),
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'concept_type' => self::enum($data['concept_type'] ?? 'CORE', self::CONCEPT_TYPES, 'concept_type'),
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'revision_number' => 1,
        ], 'CREATE_CONCEPT', $pack['uuid']);
    }

    public static function relateConcepts(string $fromUuid, string $toUuid, string $relationType): void
    {
        $from = self::conceptByUuid($fromUuid);
        $to = self::conceptByUuid($toUuid);
        if ((int) $from['learning_pack_id'] !== (int) $to['learning_pack_id']) {
            throw new InvalidArgumentException('Relasi konsep lintas paket tidak valid.');
        }
        self::mutablePackById((int) $from['learning_pack_id']);
        $relationType = self::enum($relationType, self::CONCEPT_RELATIONS, 'relation_type');
        if ((int) $from['id'] === (int) $to['id']) {
            throw new InvalidArgumentException('Konsep tidak dapat menjadi prasyarat dirinya sendiri.');
        }
        if ($relationType === 'PREREQUISITE' && self::conceptPathExists((int) $to['id'], (int) $from['id'])) {
            throw new InvalidArgumentException('Siklus prasyarat konsep terdeteksi.');
        }
        Database::connect()->table('learning_concept_relations')->ignore(true)->insert([
            'from_concept_id' => (int) $from['id'],
            'to_concept_id' => (int) $to['id'],
            'relation_type' => $relationType,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function addUnitPrerequisite(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        $refs = array_filter([
            'prerequisite_unit_id' => self::optionalScopedUnitId($data['prerequisite_unit_uuid'] ?? null, (int) $unit['learning_pack_id']),
            'prerequisite_objective_id' => isset($data['prerequisite_objective_uuid']) ? (int) EducationFoundationService::byUuid('learning_objectives_tp', (string) $data['prerequisite_objective_uuid'])['id'] : null,
            'prerequisite_concept_id' => isset($data['prerequisite_concept_uuid']) ? (int) self::conceptByUuid((string) $data['prerequisite_concept_uuid'])['id'] : null,
        ], static fn ($value) => $value !== null);
        if (count($refs) !== 1) {
            throw new InvalidArgumentException('Prasyarat unit harus menunjuk tepat satu unit, TP, atau konsep.');
        }
        if (isset($refs['prerequisite_unit_id']) && self::unitPathExists((int) $refs['prerequisite_unit_id'], (int) $unit['id'])) {
            throw new InvalidArgumentException('Siklus prasyarat unit terdeteksi.');
        }

        return self::insert('learning_unit_prerequisites', ['learning_unit_id' => (int) $unit['id']] + $refs + [
            'description' => $data['description'] ?? null,
        ], 'ADD_UNIT_PREREQUISITE', $unitUuid);
    }

    public static function addMaterialTopic(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        return self::insert('learning_material_topics', [
            'learning_unit_id' => (int) $unit['id'],
            'code' => strtoupper(trim((string) $data['code'])),
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'material_level' => self::enum($data['material_level'] ?? 'ESSENTIAL', self::MATERIAL_LEVELS, 'material_level'),
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'teacher_notes' => $data['teacher_notes'] ?? null,
        ], 'ADD_MATERIAL_TOPIC', $unitUuid);
    }

    public static function addMisconception(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        $conceptId = isset($data['concept_uuid']) ? (int) self::conceptByUuid((string) $data['concept_uuid'])['id'] : null;
        return self::insert('learning_misconceptions', [
            'learning_unit_id' => (int) $unit['id'],
            'concept_id' => $conceptId,
            'title' => trim((string) $data['title']),
            'description' => trim((string) $data['description']),
            'detection_hint' => $data['detection_hint'] ?? null,
            'teacher_response_suggestion' => $data['teacher_response_suggestion'] ?? null,
            'severity' => self::enum($data['severity'] ?? 'INFO', self::MISCONCEPTION_SEVERITIES, 'severity'),
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'status' => strtoupper((string) ($data['status'] ?? 'ACTIVE')),
            'revision_number' => 1,
        ], 'ADD_MISCONCEPTION', $unitUuid);
    }

    public static function addActivation(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        return self::insert('learning_activations', [
            'learning_unit_id' => (int) $unit['id'],
            'activation_type' => self::enum($data['activation_type'] ?? 'APERCEPTION', self::ACTIVATION_TYPES, 'activation_type'),
            'title' => trim((string) $data['title']),
            'instructions' => trim((string) $data['instructions']),
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'resource_id' => isset($data['resource_uuid']) ? (int) self::resourceByUuid((string) $data['resource_uuid'])['id'] : null,
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
        ], 'ADD_ACTIVATION', $unitUuid);
    }

    public static function createResource(string $packUuid, array $data): array
    {
        $pack = self::mutablePack($packUuid);
        return self::insert('learning_resources', [
            'learning_pack_id' => (int) $pack['id'],
            'resource_type' => self::enum($data['resource_type'] ?? 'OTHER', self::RESOURCE_TYPES, 'resource_type'),
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'url' => $data['url'] ?? null,
            'file_reference' => $data['file_reference'] ?? null,
            'device_count' => $data['device_count'] ?? null,
            'internet_required' => ! empty($data['internet_required']) ? 1 : 0,
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'copyright_notes' => $data['copyright_notes'] ?? null,
            'license_notes' => $data['license_notes'] ?? null,
            'revision_number' => 1,
            'status' => strtoupper((string) ($data['status'] ?? 'ACTIVE')),
        ], 'CREATE_RESOURCE', $pack['uuid']);
    }

    public static function createActivity(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        $pack = self::mutablePackById((int) $unit['learning_pack_id']);
        return self::insert('learning_activities', [
            'learning_pack_id' => (int) $pack['id'],
            'learning_unit_id' => (int) $unit['id'],
            'code' => strtoupper(trim((string) $data['code'])),
            'title' => trim((string) $data['title']),
            'description' => $data['description'] ?? null,
            'activity_type' => strtoupper((string) ($data['activity_type'] ?? 'PRACTICE')),
            'delivery_mode' => self::enum($data['delivery_mode'] ?? 'OTHER', self::DELIVERY_MODES, 'delivery_mode'),
            'grouping_mode' => self::enum($data['grouping_mode'] ?? 'FLEXIBLE', self::GROUPING_MODES, 'grouping_mode'),
            'estimated_minutes' => (int) $data['estimated_minutes'],
            'minimum_minutes' => $data['minimum_minutes'] ?? null,
            'maximum_minutes' => $data['maximum_minutes'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? null,
            'internet_requirement' => strtoupper((string) ($data['internet_requirement'] ?? 'NOT_REQUIRED')),
            'device_requirement' => $data['device_requirement'] ?? null,
            'room_requirement' => $data['room_requirement'] ?? null,
            'teacher_guidance' => $data['teacher_guidance'] ?? null,
            'student_instructions' => $data['student_instructions'] ?? null,
            'expected_output' => $data['expected_output'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
            'copyright_notes' => $data['copyright_notes'] ?? null,
            'license_notes' => $data['license_notes'] ?? null,
            'status' => strtoupper((string) ($data['status'] ?? 'DRAFT')),
            'revision_number' => 1,
        ], 'CREATE_ACTIVITY', $unitUuid);
    }

    public static function attachActivityResource(string $activityUuid, string $resourceUuid, array $data = []): void
    {
        $activity = self::activityByUuid($activityUuid);
        $resource = self::resourceByUuid($resourceUuid);
        if ((int) $activity['learning_pack_id'] !== (int) $resource['learning_pack_id']) {
            throw new InvalidArgumentException('Resource tidak berada dalam paket yang sama.');
        }
        self::mutablePackById((int) $activity['learning_pack_id']);
        Database::connect()->table('learning_activity_resources')->ignore(true)->insert([
            'activity_id' => (int) $activity['id'],
            'resource_id' => (int) $resource['id'],
            'requirement_type' => strtoupper((string) ($data['requirement_type'] ?? 'REQUIRED')),
            'quantity' => $data['quantity'] ?? null,
            'is_required' => ! array_key_exists('is_required', $data) || (bool) $data['is_required'] ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function addAlternative(string $groupUuid, string $activityUuid, array $data = []): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);
        if (isset($data['condition_json'])) {
            json_decode((string) $data['condition_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Condition JSON alternatif tidak valid.');
            }
        }
        Database::connect()->table('learning_activity_alternatives')->ignore(true)->insert([
            'group_uuid' => $groupUuid,
            'activity_id' => (int) $activity['id'],
            'priority' => (int) ($data['priority'] ?? 1),
            'condition_json' => $data['condition_json'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function addTeacherGuidance(array $data): array
    {
        $owner = self::resolveGuidanceOwner($data);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_teacher_guidance', [
            'guidance_type' => self::enum($data['guidance_type'] ?? 'INSTRUCTION', self::GUIDANCE_TYPES, 'guidance_type'),
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'activity_id' => $owner['activity_id'] ?? null,
            'concept_id' => $owner['concept_id'] ?? null,
            'title' => trim((string) $data['title']),
            'guidance' => trim((string) $data['guidance']),
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
        ], 'ADD_TEACHER_GUIDANCE', $data['learning_unit_uuid'] ?? $data['activity_uuid'] ?? $data['concept_uuid']);
    }

    public static function addExpectedResponse(string $activityUuid, array $data): array
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);
        return self::insert('learning_expected_responses', [
            'activity_id' => (int) $activity['id'],
            'response_type' => self::enum($data['response_type'] ?? 'EXPECTED', self::RESPONSE_TYPES, 'response_type'),
            'description' => trim((string) $data['description']),
            'teacher_response' => $data['teacher_response'] ?? null,
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
        ], 'ADD_EXPECTED_RESPONSE', $activityUuid);
    }

    public static function addExperience(string $activityUuid, string $experienceType, int $sequenceOrder = 1): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);
        Database::connect()->table('learning_activity_experiences')->ignore(true)->insert([
            'activity_id' => (int) $activity['id'],
            'experience_type' => self::enum($experienceType, self::EXPERIENCES, 'experience_type'),
            'sequence_order' => $sequenceOrder,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function mapPracticeToUnit(string $unitUuid, string $practiceCode): void
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        $practice = self::practiceByCode($practiceCode);
        Database::connect()->table('learning_unit_practices')->ignore(true)->insert([
            'learning_unit_id' => (int) $unit['id'],
            'practice_id' => (int) $practice['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function mapPracticeToActivity(string $activityUuid, string $practiceCode): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);
        $practice = self::practiceByCode($practiceCode);
        Database::connect()->table('learning_activity_practices')->ignore(true)->insert([
            'activity_id' => (int) $activity['id'],
            'practice_id' => (int) $practice['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function alignGraduateProfile(string $ownerType, string $ownerUuid, string $dimensionCode, ?string $notes = null): array
    {
        $ownerType = self::enum($ownerType, ['UNIT', 'ACTIVITY', 'OBJECTIVE'], 'owner_type');
        $db = Database::connect();
        $ownerId = match ($ownerType) {
            'UNIT' => (int) self::unitByUuid($ownerUuid)['id'],
            'ACTIVITY' => (int) self::activityByUuid($ownerUuid)['id'],
            'OBJECTIVE' => (int) EducationFoundationService::byUuid('learning_objectives_tp', $ownerUuid)['id'],
        };
        $dimension = $db->table('graduate_profile_dimensions')->where('code', strtoupper($dimensionCode))->get()->getRowArray();
        if (! $dimension) {
            throw new InvalidArgumentException('Dimensi profil lulusan tidak ditemukan.');
        }

        return self::insert('learning_graduate_profile_alignments', [
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'dimension_id' => (int) $dimension['id'],
            'notes' => $notes,
        ], 'ALIGN_GRADUATE_PROFILE', $ownerUuid);
    }

    public static function addInterdisciplinaryLink(string $packUuid, array $data): array
    {
        $pack = self::mutablePack($packUuid);
        return self::insert('learning_interdisciplinary_links', [
            'learning_pack_id' => (int) $pack['id'],
            'source_subject_id' => (int) ($data['source_subject_id'] ?? $pack['subject_id']),
            'related_subject_id' => (int) $data['related_subject_id'],
            'related_objective_id' => isset($data['related_objective_uuid']) ? (int) EducationFoundationService::byUuid('learning_objectives_tp', (string) $data['related_objective_uuid'])['id'] : null,
            'description' => trim((string) $data['description']),
            'link_type' => strtoupper((string) ($data['link_type'] ?? 'RELATED')),
        ], 'ADD_INTERDISCIPLINARY_LINK', $packUuid);
    }

    public static function addAssessmentReference(array $data): array
    {
        $owner = self::resolveUnitOrActivityOwner($data);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_assessment_references', [
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'activity_id' => $owner['activity_id'] ?? null,
            'assessment_purpose' => self::enum($data['assessment_purpose'] ?? 'FORMATIVE', self::ASSESSMENT_PURPOSES, 'assessment_purpose'),
            'recommended_method' => trim((string) $data['recommended_method']),
            'criteria_reference' => $data['criteria_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source_locator' => $data['source_locator'] ?? null,
        ], 'ADD_ASSESSMENT_REFERENCE', $data['learning_unit_uuid'] ?? $data['activity_uuid']);
    }

    public static function addFollowupGuidance(array $data): array
    {
        $owner = self::resolveUnitOrActivityOwner($data);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_followup_guidance', [
            'guidance_type' => self::enum($data['guidance_type'] ?? 'REMEDIAL', self::FOLLOWUP_TYPES, 'guidance_type'),
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'learning_objective_id' => isset($data['learning_objective_uuid']) ? (int) EducationFoundationService::byUuid('learning_objectives_tp', (string) $data['learning_objective_uuid'])['id'] : null,
            'activity_id' => $owner['activity_id'] ?? null,
            'trigger_description' => trim((string) $data['trigger_description']),
            'guidance' => trim((string) $data['guidance']),
            'recommended_activity_id' => isset($data['recommended_activity_uuid']) ? (int) self::activityByUuid((string) $data['recommended_activity_uuid'])['id'] : null,
        ], 'ADD_FOLLOWUP_GUIDANCE', $data['learning_unit_uuid'] ?? $data['activity_uuid']);
    }

    public static function addReflectionPrompt(array $data): array
    {
        $owner = self::resolveUnitOrActivityOwner($data);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_reflection_prompts', [
            'audience' => self::enum($data['audience'] ?? 'STUDENT', self::REFLECTION_AUDIENCES, 'audience'),
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'activity_id' => $owner['activity_id'] ?? null,
            'prompt' => trim((string) $data['prompt']),
            'prompt_type' => strtoupper((string) ($data['prompt_type'] ?? 'OPEN')),
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
        ], 'ADD_REFLECTION_PROMPT', $data['learning_unit_uuid'] ?? $data['activity_uuid']);
    }

    public static function coverage(string $packUuid): array
    {
        $pack = self::packByUuid($packUuid);
        $db = Database::connect();
        $packId = (int) $pack['id'];
        $totalTp = $db->table('subject_learning_pack_objectives')->where('subject_learning_pack_id', $packId)->countAllResults();
        $tpWithUnit = $db->table('learning_unit_objectives luo')
            ->join('learning_units lu', 'lu.id=luo.learning_unit_id')
            ->where('lu.learning_pack_id', $packId)
            ->select('luo.learning_objective_id')
            ->distinct()
            ->countAllResults();
        $unitIds = array_map('intval', array_column($db->table('learning_units')->select('id')->where('learning_pack_id', $packId)->get()->getResultArray(), 'id'));
        $activityCount = $unitIds === [] ? 0 : $db->table('learning_activities')->whereIn('learning_unit_id', $unitIds)->countAllResults();
        $assessmentRefs = $unitIds === [] ? 0 : $db->table('learning_assessment_references')->whereIn('learning_unit_id', $unitIds)->countAllResults();
        $unitsWithoutObjective = $unitIds === [] ? 0 : $db->table('learning_units lu')
            ->join('learning_unit_objectives luo', 'luo.learning_unit_id=lu.id', 'left')
            ->where('lu.learning_pack_id', $packId)
            ->where('luo.learning_unit_id IS NULL')
            ->countAllResults();
        $sourceRows = $db->table('learning_units')->where('learning_pack_id', $packId)->groupStart()->where('source_id IS NULL')->where('source_locator IS NULL')->groupEnd()->countAllResults();
        $warnings = [];
        if ($totalTp === 0) {
            $warnings[] = 'Learning pack belum menautkan TP.';
        }
        if ($unitsWithoutObjective > 0) {
            $warnings[] = $unitsWithoutObjective . ' unit belum memiliki TP.';
        }
        if ($assessmentRefs === 0) {
            $warnings[] = 'Belum ada assessment reference.';
        }
        if ($sourceRows > 0) {
            $warnings[] = $sourceRows . ' unit belum memiliki provenance lengkap.';
        }

        return [
            'tp_total' => $totalTp,
            'tp_with_unit' => $tpWithUnit,
            'tp_with_activity' => $activityCount > 0 ? $tpWithUnit : 0,
            'tp_with_assessment_reference' => $assessmentRefs > 0 ? $tpWithUnit : 0,
            'units_without_objective' => $unitsWithoutObjective,
            'activity_count' => $activityCount,
            'assessment_reference_count' => $assessmentRefs,
            'source_provenance_gaps' => $sourceRows,
            'warnings' => $warnings,
        ];
    }

    public static function validatePack(string $packUuid): array
    {
        $coverage = self::coverage($packUuid);
        $errors = [];
        if ($coverage['tp_total'] === 0) {
            $errors[] = 'missing_tp';
        }
        if ($coverage['units_without_objective'] > 0) {
            $errors[] = 'unit_without_objective';
        }
        return ['valid' => $errors === [], 'errors' => $errors, 'coverage' => $coverage];
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
        AuditService::log('learning_packs', $action, $table, $id, null, $row, null, $uuidForAudit ?? $row['uuid']);

        return $row;
    }

    private static function mutablePack(string $uuid): array
    {
        $pack = self::packByUuid($uuid);
        self::assertMutable($pack);
        return $pack;
    }

    private static function mutablePackById(int $id): array
    {
        $pack = Database::connect()->table('subject_learning_packs')->where('id', $id)->get()->getRowArray();
        if (! $pack) {
            throw new RuntimeException('Learning pack tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $pack['unit_id']);
        self::assertMutable($pack);
        return $pack;
    }

    private static function packByUuid(string $uuid, bool $assertUnit = true): array
    {
        $pack = EducationFoundationService::byUuid('subject_learning_packs', $uuid);
        if ($assertUnit) {
            UnitScopeService::assertUnit((int) $pack['unit_id']);
        }
        return $pack;
    }

    private static function assertMutable(array $pack): void
    {
        if (in_array($pack['status'], ['LOCKED', 'ARCHIVED'], true)) {
            throw new RuntimeException('Learning pack terkunci/diarsipkan tidak dapat diubah.');
        }
    }

    private static function unitByUuid(string $uuid): array
    {
        $unit = EducationFoundationService::byUuid('learning_units', $uuid);
        $pack = Database::connect()->table('subject_learning_packs')->where('id', (int) $unit['learning_pack_id'])->get()->getRowArray();
        UnitScopeService::assertUnit((int) $pack['unit_id']);
        return $unit;
    }

    private static function optionalScopedUnitId(?string $uuid, int $packId): ?int
    {
        if ($uuid === null || $uuid === '') {
            return null;
        }
        $unit = self::unitByUuid($uuid);
        if ((int) $unit['learning_pack_id'] !== $packId) {
            throw new InvalidArgumentException('Unit tidak berada dalam paket yang sama.');
        }
        return (int) $unit['id'];
    }

    private static function conceptByUuid(string $uuid): array
    {
        $concept = EducationFoundationService::byUuid('learning_concepts', $uuid);
        self::packByIdForScope((int) $concept['learning_pack_id']);
        return $concept;
    }

    private static function activityByUuid(string $uuid): array
    {
        $activity = EducationFoundationService::byUuid('learning_activities', $uuid);
        self::packByIdForScope((int) $activity['learning_pack_id']);
        return $activity;
    }

    private static function resourceByUuid(string $uuid): array
    {
        $resource = EducationFoundationService::byUuid('learning_resources', $uuid);
        self::packByIdForScope((int) $resource['learning_pack_id']);
        return $resource;
    }

    private static function practiceByCode(string $code): array
    {
        $practice = Database::connect()->table('pedagogical_practices')->where('code', strtoupper($code))->get()->getRowArray();
        if (! $practice) {
            throw new InvalidArgumentException('Praktik pedagogis tidak ditemukan.');
        }
        return $practice;
    }

    private static function packByIdForScope(int $id): array
    {
        $pack = Database::connect()->table('subject_learning_packs')->where('id', $id)->get()->getRowArray();
        if (! $pack) {
            throw new RuntimeException('Learning pack tidak ditemukan.');
        }
        UnitScopeService::assertUnit((int) $pack['unit_id']);
        return $pack;
    }

    private static function assertObjectiveFitsPack(array $objective, array $pack): void
    {
        $cp = Database::connect()->table('learning_outcomes_cp')->where('id', (int) $objective['learning_outcome_id'])->get()->getRowArray();
        if (! $cp || (int) $cp['subject_id'] !== (int) $pack['subject_id'] || (int) $cp['grade_level_id'] !== (int) $pack['grade_level_id']) {
            throw new InvalidArgumentException('TP tidak sesuai mata pelajaran atau tingkat paket.');
        }
        if ($objective['unit_id'] !== null && (int) $objective['unit_id'] !== (int) $pack['unit_id']) {
            throw new InvalidArgumentException('TP adaptasi lintas unit tidak dapat ditautkan.');
        }
    }

    private static function conceptPathExists(int $fromId, int $targetId): bool
    {
        $seen = [];
        $stack = [$fromId];
        $db = Database::connect();
        while ($stack !== []) {
            $id = array_pop($stack);
            if ($id === $targetId) {
                return true;
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rows = $db->table('learning_concept_relations')->select('to_concept_id')->where('from_concept_id', $id)->where('relation_type', 'PREREQUISITE')->get()->getResultArray();
            foreach ($rows as $row) {
                $stack[] = (int) $row['to_concept_id'];
            }
        }
        return false;
    }

    private static function unitPathExists(int $fromId, int $targetId): bool
    {
        $seen = [];
        $stack = [$fromId];
        $db = Database::connect();
        while ($stack !== []) {
            $id = array_pop($stack);
            if ($id === $targetId) {
                return true;
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rows = $db->table('learning_unit_prerequisites')->select('prerequisite_unit_id')->where('learning_unit_id', $id)->where('prerequisite_unit_id IS NOT NULL')->get()->getResultArray();
            foreach ($rows as $row) {
                $stack[] = (int) $row['prerequisite_unit_id'];
            }
        }
        return false;
    }

    private static function resolveGuidanceOwner(array $data): array
    {
        $set = array_filter([
            'learning_unit_uuid' => $data['learning_unit_uuid'] ?? null,
            'activity_uuid' => $data['activity_uuid'] ?? null,
            'concept_uuid' => $data['concept_uuid'] ?? null,
        ]);
        if (count($set) !== 1) {
            throw new InvalidArgumentException('Guidance harus ditautkan tepat ke satu unit, aktivitas, atau konsep.');
        }
        if (isset($set['learning_unit_uuid'])) {
            $unit = self::unitByUuid((string) $set['learning_unit_uuid']);
            return ['learning_pack_id' => (int) $unit['learning_pack_id'], 'learning_unit_id' => (int) $unit['id']];
        }
        if (isset($set['activity_uuid'])) {
            $activity = self::activityByUuid((string) $set['activity_uuid']);
            return ['learning_pack_id' => (int) $activity['learning_pack_id'], 'activity_id' => (int) $activity['id']];
        }
        $concept = self::conceptByUuid((string) $set['concept_uuid']);
        return ['learning_pack_id' => (int) $concept['learning_pack_id'], 'concept_id' => (int) $concept['id']];
    }

    private static function resolveUnitOrActivityOwner(array $data): array
    {
        $set = array_filter([
            'learning_unit_uuid' => $data['learning_unit_uuid'] ?? null,
            'activity_uuid' => $data['activity_uuid'] ?? null,
        ]);
        if (count($set) !== 1) {
            throw new InvalidArgumentException('Data harus ditautkan tepat ke satu unit atau aktivitas.');
        }
        if (isset($set['learning_unit_uuid'])) {
            $unit = self::unitByUuid((string) $set['learning_unit_uuid']);
            return ['learning_pack_id' => (int) $unit['learning_pack_id'], 'learning_unit_id' => (int) $unit['id']];
        }
        $activity = self::activityByUuid((string) $set['activity_uuid']);
        return ['learning_pack_id' => (int) $activity['learning_pack_id'], 'activity_id' => (int) $activity['id']];
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
