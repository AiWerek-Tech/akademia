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

    public static function updatePack(string $uuid, array $data): array
    {
        $current = self::mutablePack($uuid);
        $changes = [];
        foreach (['name', 'description', 'phase', 'source_locator'] as $f) {
            if (array_key_exists($f, $data)) {
                $changes[$f] = $data[$f] !== null ? trim((string) $data[$f]) : null;
            }
        }
        if (isset($data['source_type'])) {
            $changes['source_type'] = self::enum($data['source_type'], self::SOURCE_TYPES, 'source_type');
        }
        if (isset($data['revision_number'])) {
            $changes['revision_number'] = (int) $data['revision_number'];
        }

        $updated = EducationFoundationService::atomicUpdate('subject_learning_packs', $current, $changes);
        AuditService::log('learning_packs', 'UPDATE_PACK', 'SubjectLearningPack', (int) $current['id'], $current, $updated, null, $uuid);

        return $updated;
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

    public static function updateUnit(string $unitUuid, array $data): array
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);

        $changes = [];
        if (isset($data['code'])) $changes['code'] = strtoupper(trim((string) $data['code']));
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (array_key_exists('description', $data)) $changes['description'] = $data['description'];
        if (isset($data['unit_type'])) $changes['unit_type'] = self::enum($data['unit_type'], self::UNIT_TYPES, 'unit_type');
        if (isset($data['sequence_order'])) $changes['sequence_order'] = (int) $data['sequence_order'];
        if (array_key_exists('estimated_hours', $data)) $changes['estimated_hours'] = $data['estimated_hours'];
        if (array_key_exists('source_locator', $data)) $changes['source_locator'] = $data['source_locator'];
        if (array_key_exists('copyright_notes', $data)) $changes['copyright_notes'] = $data['copyright_notes'];
        if (array_key_exists('license_notes', $data)) $changes['license_notes'] = $data['license_notes'];
        if (isset($data['status'])) $changes['status'] = strtoupper((string) $data['status']);
        if (isset($data['revision_number'])) $changes['revision_number'] = (int) $data['revision_number'];

        $updated = EducationFoundationService::atomicUpdate('learning_units', $unit, $changes);
        AuditService::log('learning_packs', 'UPDATE_UNIT', 'LearningUnit', (int) $unit['id'], $unit, $updated, null, $unitUuid);

        return $updated;
    }

    public static function deleteUnit(string $unitUuid): void
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);

        Database::connect()->table('learning_units')->where('id', (int) $unit['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_UNIT', 'LearningUnit', (int) $unit['id'], $unit, null, null, $unitUuid);
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

    public static function unmapUnitObjective(string $unitUuid, string $objectiveUuid): void
    {
        $unit = self::unitByUuid($unitUuid);
        self::mutablePackById((int) $unit['learning_pack_id']);
        $objective = EducationFoundationService::byUuid('learning_objectives_tp', $objectiveUuid);

        Database::connect()->table('learning_unit_objectives')
            ->where('learning_unit_id', (int) $unit['id'])
            ->where('learning_objective_id', (int) $objective['id'])
            ->delete();
        AuditService::log('learning_packs', 'UNMAP_UNIT_OBJECTIVE', 'LearningUnit', (int) $unit['id'], ['objective_id' => $objective['id']], null, null, $unitUuid);
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

    public static function updateConcept(string $conceptUuid, array $data): array
    {
        $concept = self::conceptByUuid($conceptUuid);
        self::mutablePackById((int) $concept['learning_pack_id']);

        $changes = [];
        if (isset($data['code'])) $changes['code'] = strtoupper(trim((string) $data['code']));
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (array_key_exists('description', $data)) $changes['description'] = $data['description'];
        if (isset($data['concept_type'])) $changes['concept_type'] = self::enum($data['concept_type'], self::CONCEPT_TYPES, 'concept_type');
        if (array_key_exists('source_locator', $data)) $changes['source_locator'] = $data['source_locator'];
        if (isset($data['revision_number'])) $changes['revision_number'] = (int) $data['revision_number'];

        $updated = EducationFoundationService::atomicUpdate('learning_concepts', $concept, $changes);
        AuditService::log('learning_packs', 'UPDATE_CONCEPT', 'LearningConcept', (int) $concept['id'], $concept, $updated, null, $conceptUuid);

        return $updated;
    }

    public static function deleteConcept(string $conceptUuid): void
    {
        $concept = self::conceptByUuid($conceptUuid);
        self::mutablePackById((int) $concept['learning_pack_id']);

        Database::connect()->table('learning_concepts')->where('id', (int) $concept['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_CONCEPT', 'LearningConcept', (int) $concept['id'], $concept, null, null, $conceptUuid);
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

        $prereqUnitId = null;
        if (! empty($data['prerequisite_unit_uuid'])) {
            $prereqUnit = self::unitByUuid((string) $data['prerequisite_unit_uuid']);
            $prereqUnitId = (int) $prereqUnit['id'];
        }

        $refs = array_filter([
            'prerequisite_unit_id' => $prereqUnitId,
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

    public static function deleteUnitPrerequisite(string $prerequisiteUuid): void
    {
        $row = EducationFoundationService::byUuid('learning_unit_prerequisites', $prerequisiteUuid);
        $unit = Database::connect()->table('learning_units')->where('id', (int) $row['learning_unit_id'])->get()->getRowArray();
        self::mutablePackById((int) $unit['learning_pack_id']);

        Database::connect()->table('learning_unit_prerequisites')->where('id', (int) $row['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_UNIT_PREREQUISITE', 'LearningUnitPrerequisite', (int) $row['id'], $row, null, null, $prerequisiteUuid);
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

    public static function updateMaterialTopic(string $topicUuid, array $data): array
    {
        $topic = EducationFoundationService::byUuid('learning_material_topics', $topicUuid);
        $unit = Database::connect()->table('learning_units')->where('id', (int) $topic['learning_unit_id'])->get()->getRowArray();
        self::mutablePackById((int) $unit['learning_pack_id']);

        $changes = [];
        if (isset($data['code'])) $changes['code'] = strtoupper(trim((string) $data['code']));
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (array_key_exists('description', $data)) $changes['description'] = $data['description'];
        if (isset($data['material_level'])) $changes['material_level'] = self::enum($data['material_level'], self::MATERIAL_LEVELS, 'material_level');
        if (isset($data['sequence_order'])) $changes['sequence_order'] = (int) $data['sequence_order'];
        if (array_key_exists('teacher_notes', $data)) $changes['teacher_notes'] = $data['teacher_notes'];

        $updated = EducationFoundationService::atomicUpdate('learning_material_topics', $topic, $changes);
        AuditService::log('learning_packs', 'UPDATE_MATERIAL_TOPIC', 'LearningMaterialTopic', (int) $topic['id'], $topic, $updated, null, $topicUuid);

        return $updated;
    }

    public static function deleteMaterialTopic(string $topicUuid): void
    {
        $topic = EducationFoundationService::byUuid('learning_material_topics', $topicUuid);
        $unit = Database::connect()->table('learning_units')->where('id', (int) $topic['learning_unit_id'])->get()->getRowArray();
        self::mutablePackById((int) $unit['learning_pack_id']);

        Database::connect()->table('learning_material_topics')->where('id', (int) $topic['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_MATERIAL_TOPIC', 'LearningMaterialTopic', (int) $topic['id'], $topic, null, null, $topicUuid);
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

    public static function updateMisconception(string $misconceptionUuid, array $data): array
    {
        $row = EducationFoundationService::byUuid('learning_misconceptions', $misconceptionUuid);
        $unit = Database::connect()->table('learning_units')->where('id', (int) $row['learning_unit_id'])->get()->getRowArray();
        self::mutablePackById((int) $unit['learning_pack_id']);

        $changes = [];
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (isset($data['description'])) $changes['description'] = trim((string) $data['description']);
        if (array_key_exists('detection_hint', $data)) $changes['detection_hint'] = $data['detection_hint'];
        if (array_key_exists('teacher_response_suggestion', $data)) $changes['teacher_response_suggestion'] = $data['teacher_response_suggestion'];
        if (isset($data['severity'])) $changes['severity'] = self::enum($data['severity'], self::MISCONCEPTION_SEVERITIES, 'severity');
        if (isset($data['status'])) $changes['status'] = strtoupper((string) $data['status']);
        if (isset($data['revision_number'])) $changes['revision_number'] = (int) $data['revision_number'];

        $updated = EducationFoundationService::atomicUpdate('learning_misconceptions', $row, $changes);
        AuditService::log('learning_packs', 'UPDATE_MISCONCEPTION', 'LearningMisconception', (int) $row['id'], $row, $updated, null, $misconceptionUuid);

        return $updated;
    }

    public static function deleteMisconception(string $misconceptionUuid): void
    {
        $row = EducationFoundationService::byUuid('learning_misconceptions', $misconceptionUuid);
        $unit = Database::connect()->table('learning_units')->where('id', (int) $row['learning_unit_id'])->get()->getRowArray();
        self::mutablePackById((int) $unit['learning_pack_id']);

        Database::connect()->table('learning_misconceptions')->where('id', (int) $row['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_MISCONCEPTION', 'LearningMisconception', (int) $row['id'], $row, null, null, $misconceptionUuid);
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

    public static function updateResource(string $resourceUuid, array $data): array
    {
        $resource = self::resourceByUuid($resourceUuid);
        self::mutablePackById((int) $resource['learning_pack_id']);

        $changes = [];
        if (isset($data['resource_type'])) $changes['resource_type'] = self::enum($data['resource_type'], self::RESOURCE_TYPES, 'resource_type');
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (array_key_exists('description', $data)) $changes['description'] = $data['description'];
        if (array_key_exists('url', $data)) $changes['url'] = $data['url'];
        if (array_key_exists('device_count', $data)) $changes['device_count'] = $data['device_count'];
        if (array_key_exists('internet_required', $data)) $changes['internet_required'] = ! empty($data['internet_required']) ? 1 : 0;
        if (isset($data['status'])) $changes['status'] = strtoupper((string) $data['status']);
        if (isset($data['revision_number'])) $changes['revision_number'] = (int) $data['revision_number'];

        $updated = EducationFoundationService::atomicUpdate('learning_resources', $resource, $changes);
        AuditService::log('learning_packs', 'UPDATE_RESOURCE', 'LearningResource', (int) $resource['id'], $resource, $updated, null, $resourceUuid);

        return $updated;
    }

    public static function deleteResource(string $resourceUuid): void
    {
        $resource = self::resourceByUuid($resourceUuid);
        self::mutablePackById((int) $resource['learning_pack_id']);

        Database::connect()->table('learning_resources')->where('id', (int) $resource['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_RESOURCE', 'LearningResource', (int) $resource['id'], $resource, null, null, $resourceUuid);
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

    public static function updateActivity(string $activityUuid, array $data): array
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);

        $changes = [];
        if (isset($data['code'])) $changes['code'] = strtoupper(trim((string) $data['code']));
        if (isset($data['title'])) $changes['title'] = trim((string) $data['title']);
        if (array_key_exists('description', $data)) $changes['description'] = $data['description'];
        if (isset($data['activity_type'])) $changes['activity_type'] = strtoupper((string) $data['activity_type']);
        if (isset($data['delivery_mode'])) $changes['delivery_mode'] = self::enum($data['delivery_mode'], self::DELIVERY_MODES, 'delivery_mode');
        if (isset($data['grouping_mode'])) $changes['grouping_mode'] = self::enum($data['grouping_mode'], self::GROUPING_MODES, 'grouping_mode');
        if (isset($data['estimated_minutes'])) $changes['estimated_minutes'] = (int) $data['estimated_minutes'];
        if (array_key_exists('teacher_guidance', $data)) $changes['teacher_guidance'] = $data['teacher_guidance'];
        if (array_key_exists('student_instructions', $data)) $changes['student_instructions'] = $data['student_instructions'];
        if (array_key_exists('expected_output', $data)) $changes['expected_output'] = $data['expected_output'];
        if (isset($data['status'])) $changes['status'] = strtoupper((string) $data['status']);
        if (isset($data['revision_number'])) $changes['revision_number'] = (int) $data['revision_number'];

        $updated = EducationFoundationService::atomicUpdate('learning_activities', $activity, $changes);
        AuditService::log('learning_packs', 'UPDATE_ACTIVITY', 'LearningActivity', (int) $activity['id'], $activity, $updated, null, $activityUuid);

        return $updated;
    }

    public static function deleteActivity(string $activityUuid): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);

        Database::connect()->table('learning_activities')->where('id', (int) $activity['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_ACTIVITY', 'LearningActivity', (int) $activity['id'], $activity, null, null, $activityUuid);
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
        AuditService::log('learning_packs', 'ATTACH_ACTIVITY_RESOURCE', 'LearningActivity', (int) $activity['id'], null, ['resource_id' => $resource['id']], null, $activityUuid);
    }

    public static function detachActivityResource(string $activityUuid, string $resourceUuid): void
    {
        $activity = self::activityByUuid($activityUuid);
        $resource = self::resourceByUuid($resourceUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);

        Database::connect()->table('learning_activity_resources')
            ->where('activity_id', (int) $activity['id'])
            ->where('resource_id', (int) $resource['id'])
            ->delete();
        AuditService::log('learning_packs', 'DETACH_ACTIVITY_RESOURCE', 'LearningActivity', (int) $activity['id'], ['resource_id' => $resource['id']], null, null, $activityUuid);
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

    public static function removeAlternative(string $groupUuid, string $activityUuid): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);

        Database::connect()->table('learning_activity_alternatives')
            ->where('group_uuid', $groupUuid)
            ->where('activity_id', (int) $activity['id'])
            ->delete();
    }

    public static function addTeacherGuidance(array $data, ?string $packUuid = null): array
    {
        $owner = self::resolveGuidanceOwner($data, $packUuid);
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
        ], 'ADD_TEACHER_GUIDANCE', $data['learning_unit_uuid'] ?? $data['activity_uuid'] ?? $data['concept_uuid'] ?? $packUuid ?? null);
    }

    public static function deleteTeacherGuidance(string $guidanceUuid): void
    {
        $guidance = EducationFoundationService::byUuid('learning_teacher_guidance', $guidanceUuid);
        $packId = null;
        if ($guidance['learning_unit_id']) {
            $unit = Database::connect()->table('learning_units')->where('id', (int) $guidance['learning_unit_id'])->get()->getRowArray();
            $packId = (int) $unit['learning_pack_id'];
        } elseif ($guidance['activity_id']) {
            $act = Database::connect()->table('learning_activities')->where('id', (int) $guidance['activity_id'])->get()->getRowArray();
            $packId = (int) $act['learning_pack_id'];
        } elseif ($guidance['concept_id']) {
            $concept = Database::connect()->table('learning_concepts')->where('id', (int) $guidance['concept_id'])->get()->getRowArray();
            $packId = (int) $concept['learning_pack_id'];
        }
        if ($packId) {
            self::mutablePackById($packId);
        }

        Database::connect()->table('learning_teacher_guidance')->where('id', (int) $guidance['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_TEACHER_GUIDANCE', 'LearningTeacherGuidance', (int) $guidance['id'], $guidance, null, null, $guidanceUuid);
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

    public static function deleteExpectedResponse(string $responseUuid): void
    {
        $resp = EducationFoundationService::byUuid('learning_expected_responses', $responseUuid);
        $act = Database::connect()->table('learning_activities')->where('id', (int) $resp['activity_id'])->get()->getRowArray();
        self::mutablePackById((int) $act['learning_pack_id']);

        Database::connect()->table('learning_expected_responses')->where('id', (int) $resp['id'])->delete();
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

    public static function removeExperience(string $activityUuid, string $experienceType): void
    {
        $activity = self::activityByUuid($activityUuid);
        self::mutablePackById((int) $activity['learning_pack_id']);
        Database::connect()->table('learning_activity_experiences')
            ->where('activity_id', (int) $activity['id'])
            ->where('experience_type', strtoupper($experienceType))
            ->delete();
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

    public static function deleteInterdisciplinaryLink(string $linkUuid): void
    {
        $link = EducationFoundationService::byUuid('learning_interdisciplinary_links', $linkUuid);
        self::mutablePackById((int) $link['learning_pack_id']);

        Database::connect()->table('learning_interdisciplinary_links')->where('id', (int) $link['id'])->delete();
    }

    public static function addAssessmentReference(array $data, ?string $packUuid = null): array
    {
        $owner = self::resolveUnitOrActivityOwner($data, $packUuid);
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
        ], 'ADD_ASSESSMENT_REFERENCE', $data['learning_unit_uuid'] ?? $data['activity_uuid'] ?? $packUuid ?? null);
    }

    public static function deleteAssessmentReference(string $referenceUuid): void
    {
        $ref = EducationFoundationService::byUuid('learning_assessment_references', $referenceUuid);
        $packId = null;
        if ($ref['learning_unit_id']) {
            $unit = Database::connect()->table('learning_units')->where('id', (int) $ref['learning_unit_id'])->get()->getRowArray();
            $packId = (int) $unit['learning_pack_id'];
        } elseif ($ref['activity_id']) {
            $act = Database::connect()->table('learning_activities')->where('id', (int) $ref['activity_id'])->get()->getRowArray();
            $packId = (int) $act['learning_pack_id'];
        }
        if ($packId) {
            self::mutablePackById($packId);
        }

        Database::connect()->table('learning_assessment_references')->where('id', (int) $ref['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_ASSESSMENT_REFERENCE', 'LearningAssessmentReference', (int) $ref['id'], $ref, null, null, $referenceUuid);
    }

    public static function addFollowupGuidance(array $data, ?string $packUuid = null): array
    {
        $owner = self::resolveUnitOrActivityOwner($data, $packUuid);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_followup_guidance', [
            'guidance_type' => self::enum($data['guidance_type'] ?? 'REMEDIAL', self::FOLLOWUP_TYPES, 'guidance_type'),
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'learning_objective_id' => isset($data['learning_objective_uuid']) ? (int) EducationFoundationService::byUuid('learning_objectives_tp', (string) $data['learning_objective_uuid'])['id'] : null,
            'activity_id' => $owner['activity_id'] ?? null,
            'trigger_description' => trim((string) $data['trigger_description']),
            'guidance' => trim((string) $data['guidance']),
            'recommended_activity_id' => isset($data['recommended_activity_uuid']) ? (int) self::activityByUuid((string) $data['recommended_activity_uuid'])['id'] : null,
        ], 'ADD_FOLLOWUP_GUIDANCE', $data['learning_unit_uuid'] ?? $data['activity_uuid'] ?? $packUuid ?? null);
    }

    public static function deleteFollowupGuidance(string $guidanceUuid): void
    {
        $row = EducationFoundationService::byUuid('learning_followup_guidance', $guidanceUuid);
        $packId = null;
        if ($row['learning_unit_id']) {
            $unit = Database::connect()->table('learning_units')->where('id', (int) $row['learning_unit_id'])->get()->getRowArray();
            $packId = (int) $unit['learning_pack_id'];
        } elseif ($row['activity_id']) {
            $act = Database::connect()->table('learning_activities')->where('id', (int) $row['activity_id'])->get()->getRowArray();
            $packId = (int) $act['learning_pack_id'];
        }
        if ($packId) {
            self::mutablePackById($packId);
        }

        Database::connect()->table('learning_followup_guidance')->where('id', (int) $row['id'])->delete();
        AuditService::log('learning_packs', 'DELETE_FOLLOWUP_GUIDANCE', 'LearningFollowupGuidance', (int) $row['id'], $row, null, null, $guidanceUuid);
    }

    public static function addReflectionPrompt(array $data, ?string $packUuid = null): array
    {
        $owner = self::resolveUnitOrActivityOwner($data, $packUuid);
        self::mutablePackById((int) $owner['learning_pack_id']);
        return self::insert('learning_reflection_prompts', [
            'audience' => self::enum($data['audience'] ?? 'STUDENT', self::REFLECTION_AUDIENCES, 'audience'),
            'learning_unit_id' => $owner['learning_unit_id'] ?? null,
            'activity_id' => $owner['activity_id'] ?? null,
            'prompt' => trim((string) $data['prompt']),
            'prompt_type' => strtoupper((string) ($data['prompt_type'] ?? 'OPEN')),
            'sequence_order' => (int) ($data['sequence_order'] ?? 1),
        ], 'ADD_REFLECTION_PROMPT', $data['learning_unit_uuid'] ?? $data['activity_uuid'] ?? $packUuid ?? null);
    }

    public static function deleteReflectionPrompt(string $promptUuid): void
    {
        $row = EducationFoundationService::byUuid('learning_reflection_prompts', $promptUuid);
        $packId = null;
        if ($row['learning_unit_id']) {
            $unit = Database::connect()->table('learning_units')->where('id', (int) $row['learning_unit_id'])->get()->getRowArray();
            $packId = (int) $unit['learning_pack_id'];
        } elseif ($row['activity_id']) {
            $act = Database::connect()->table('learning_activities')->where('id', (int) $row['activity_id'])->get()->getRowArray();
            $packId = (int) $act['learning_pack_id'];
        }
        if ($packId) {
            self::mutablePackById($packId);
        }

        Database::connect()->table('learning_reflection_prompts')->where('id', (int) $row['id'])->delete();
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
        $units = $db->table('learning_units')->where('learning_pack_id', $packId)->get()->getResultArray();
        $unitIds = array_map('intval', array_column($units, 'id'));
        $activityCount = $unitIds === [] ? 0 : $db->table('learning_activities')->whereIn('learning_unit_id', $unitIds)->countAllResults();
        $assessmentRefs = $unitIds === [] ? 0 : $db->table('learning_assessment_references')->whereIn('learning_unit_id', $unitIds)->countAllResults();
        $unitsWithoutObjective = $unitIds === [] ? 0 : $db->table('learning_units lu')
            ->join('learning_unit_objectives luo', 'luo.learning_unit_id=lu.id', 'left')
            ->where('lu.learning_pack_id', $packId)
            ->where('luo.learning_unit_id IS NULL')
            ->countAllResults();
        $sourceRows = $db->table('learning_units')->where('learning_pack_id', $packId)->groupStart()->where('source_id IS NULL')->where('source_locator IS NULL')->groupEnd()->countAllResults();

        // Deep Learning 3 Experiences Analysis (Understand, Apply, Reflect)
        $expCounts = ['UNDERSTAND' => 0, 'APPLY' => 0, 'REFLECT' => 0];
        if ($unitIds !== []) {
            $expRows = $db->table('learning_activity_experiences lae')
                ->join('learning_activities la', 'la.id=lae.activity_id')
                ->whereIn('la.learning_unit_id', $unitIds)
                ->select('lae.experience_type, COUNT(*) as cnt')
                ->groupBy('lae.experience_type')
                ->get()->getResultArray();
            foreach ($expRows as $row) {
                $type = strtoupper((string) $row['experience_type']);
                if (isset($expCounts[$type])) {
                    $expCounts[$type] = (int) $row['cnt'];
                }
            }
        }

        $unitsWithoutActivities = 0;
        $unitsMissingExperiences = [];
        foreach ($units as $u) {
            $actCnt = $db->table('learning_activities')->where('learning_unit_id', (int) $u['id'])->countAllResults();
            if ($actCnt === 0) {
                $unitsWithoutActivities++;
                $unitsMissingExperiences[] = $u['code'] . ' (Belum ada aktivitas)';
            }
        }

        $warnings = [];
        if ($totalTp === 0) {
            $warnings[] = 'Learning pack belum menautkan TP.';
        }
        if ($unitsWithoutObjective > 0) {
            $warnings[] = $unitsWithoutObjective . ' unit belum memiliki TP.';
        }
        if ($unitsWithoutActivities > 0) {
            $warnings[] = $unitsWithoutActivities . ' unit belum memiliki aktivitas belajar.';
        }
        if ($assessmentRefs === 0) {
            $warnings[] = 'Belum ada assessment reference.';
        }
        if ($sourceRows > 0) {
            $warnings[] = $sourceRows . ' unit belum memiliki provenance lengkap.';
        }
        if ($expCounts['UNDERSTAND'] === 0 || $expCounts['APPLY'] === 0 || $expCounts['REFLECT'] === 0) {
            $missingList = [];
            if ($expCounts['UNDERSTAND'] === 0) $missingList[] = 'Memahami (Understand)';
            if ($expCounts['APPLY'] === 0) $missingList[] = 'Mengaplikasi (Apply)';
            if ($expCounts['REFLECT'] === 0) $missingList[] = 'Merefleksi (Reflect)';
            $warnings[] = 'Pengalaman Pembelajaran Mendalam belum lengkap: ' . implode(', ', $missingList) . '.';
        }

        // Composite readiness score calculation
        $scoreWeights = 0;
        $scoreEarned = 0;

        // 1. TP linked
        $scoreWeights += 25;
        if ($totalTp > 0) $scoreEarned += 25;

        // 2. Units have TP
        $scoreWeights += 25;
        if ($units !== [] && $unitsWithoutObjective === 0) $scoreEarned += 25;
        elseif ($units !== []) $scoreEarned += (int) (25 * ($tpWithUnit / max(1, $totalTp)));

        // 3. Activities available
        $scoreWeights += 20;
        if ($activityCount > 0 && $unitsWithoutActivities === 0) $scoreEarned += 20;
        elseif ($activityCount > 0) $scoreEarned += 10;

        // 4. Deep Learning Experiences
        $scoreWeights += 15;
        $dlCount = ($expCounts['UNDERSTAND'] > 0 ? 1 : 0) + ($expCounts['APPLY'] > 0 ? 1 : 0) + ($expCounts['REFLECT'] > 0 ? 1 : 0);
        $scoreEarned += (int) (15 * ($dlCount / 3));

        // 5. Assessment References
        $scoreWeights += 15;
        if ($assessmentRefs > 0) $scoreEarned += 15;

        $readinessScore = (int) round(($scoreEarned / $scoreWeights) * 100);

        return [
            'tp_total' => $totalTp,
            'tp_with_unit' => $tpWithUnit,
            'tp_with_activity' => $activityCount > 0 ? $tpWithUnit : 0,
            'tp_with_assessment_reference' => $assessmentRefs > 0 ? $tpWithUnit : 0,
            'units_without_objective' => $unitsWithoutObjective,
            'units_without_activities' => $unitsWithoutActivities,
            'activity_count' => $activityCount,
            'assessment_reference_count' => $assessmentRefs,
            'source_provenance_gaps' => $sourceRows,
            'deep_learning_experiences' => $expCounts,
            'units_missing_experiences' => $unitsMissingExperiences,
            'readiness_score' => $readinessScore,
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

    /**
     * Eager loads the entire structured Learning Pack tree for Phase 4 (Lesson Planner).
     */
    public static function getPackStructureForPlanning(string $packUuid): array
    {
        $pack = self::packByUuid($packUuid);
        $db = Database::connect();
        $packId = (int) $pack['id'];

        $units = $db->table('learning_units')->where('learning_pack_id', $packId)->orderBy('sequence_order', 'ASC')->get()->getResultArray();
        $unitIds = array_map('intval', array_column($units, 'id')) ?: [0];

        // Eager load child relations
        $unitObjectives = $db->table('learning_unit_objectives luo')
            ->select('luo.*, tp.uuid as tp_uuid, tp.code as tp_code, tp.statement as tp_statement')
            ->join('learning_objectives_tp tp', 'tp.id=luo.learning_objective_id')
            ->whereIn('luo.learning_unit_id', $unitIds)
            ->orderBy('luo.sequence_order', 'ASC')
            ->get()->getResultArray();

        $concepts = $db->table('learning_concepts')->where('learning_pack_id', $packId)->orderBy('code', 'ASC')->get()->getResultArray();
        $materials = $db->table('learning_material_topics')->whereIn('learning_unit_id', $unitIds)->orderBy('sequence_order', 'ASC')->get()->getResultArray();
        $misconceptions = $db->table('learning_misconceptions')->whereIn('learning_unit_id', $unitIds)->get()->getResultArray();
        $activations = $db->table('learning_activations')->whereIn('learning_unit_id', $unitIds)->get()->getResultArray();
        $activities = $db->table('learning_activities')->where('learning_pack_id', $packId)->orderBy('code', 'ASC')->get()->getResultArray();
        $activityIds = array_map('intval', array_column($activities, 'id')) ?: [0];

        $resources = $db->table('learning_resources')->where('learning_pack_id', $packId)->orderBy('title', 'ASC')->get()->getResultArray();
        $activityResources = $db->table('learning_activity_resources lar')
            ->select('lar.*, lr.uuid as resource_uuid, lr.title as resource_title, lr.resource_type')
            ->join('learning_resources lr', 'lr.id=lar.resource_id')
            ->whereIn('lar.activity_id', $activityIds)
            ->get()->getResultArray();

        $alternatives = $db->table('learning_activity_alternatives')->whereIn('activity_id', $activityIds)->orderBy('priority', 'ASC')->get()->getResultArray();
        $experiences = $db->table('learning_activity_experiences')->whereIn('activity_id', $activityIds)->orderBy('sequence_order', 'ASC')->get()->getResultArray();
        $guidances = $db->table('learning_teacher_guidance')->whereIn('learning_unit_id', $unitIds)->orWhereIn('activity_id', $activityIds)->get()->getResultArray();
        $expectedResponses = $db->table('learning_expected_responses')->whereIn('activity_id', $activityIds)->orderBy('sequence_order', 'ASC')->get()->getResultArray();
        $assessmentRefs = $db->table('learning_assessment_references')->whereIn('learning_unit_id', $unitIds)->orWhereIn('activity_id', $activityIds)->get()->getResultArray();
        $followups = $db->table('learning_followup_guidance')->whereIn('learning_unit_id', $unitIds)->get()->getResultArray();
        $reflectionPrompts = $db->table('learning_reflection_prompts')->whereIn('learning_unit_id', $unitIds)->orWhereIn('activity_id', $activityIds)->get()->getResultArray();

        // Assemble tree structure
        $structuredUnits = [];
        foreach ($units as $u) {
            $uId = (int) $u['id'];
            $uObjectives = array_values(array_filter($unitObjectives, static fn ($o) => (int) $o['learning_unit_id'] === $uId));
            $uMaterials = array_values(array_filter($materials, static fn ($m) => (int) $m['learning_unit_id'] === $uId));
            $uMisconceptions = array_values(array_filter($misconceptions, static fn ($mc) => (int) $mc['learning_unit_id'] === $uId));
            $uActivations = array_values(array_filter($activations, static fn ($ac) => (int) $ac['learning_unit_id'] === $uId));
            $uAssessments = array_values(array_filter($assessmentRefs, static fn ($ar) => (int) $ar['learning_unit_id'] === $uId));
            $uFollowups = array_values(array_filter($followups, static fn ($fu) => (int) $fu['learning_unit_id'] === $uId));
            $uReflections = array_values(array_filter($reflectionPrompts, static fn ($rp) => (int) $rp['learning_unit_id'] === $uId));

            $uActivities = [];
            foreach ($activities as $act) {
                if ((int) $act['learning_unit_id'] !== $uId) continue;
                $actId = (int) $act['id'];
                $act['resources'] = array_values(array_filter($activityResources, static fn ($r) => (int) $r['activity_id'] === $actId));
                $act['alternatives'] = array_values(array_filter($alternatives, static fn ($alt) => (int) $alt['activity_id'] === $actId));
                $act['experiences'] = array_values(array_filter($experiences, static fn ($e) => (int) $e['activity_id'] === $actId));
                $act['guidance'] = array_values(array_filter($guidances, static fn ($g) => (int) $g['activity_id'] === $actId));
                $act['expected_responses'] = array_values(array_filter($expectedResponses, static fn ($er) => (int) $er['activity_id'] === $actId));
                $act['assessment_references'] = array_values(array_filter($assessmentRefs, static fn ($ar) => (int) $ar['activity_id'] === $actId));
                $act['reflection_prompts'] = array_values(array_filter($reflectionPrompts, static fn ($rp) => (int) $rp['activity_id'] === $actId));
                $uActivities[] = $act;
            }

            $u['objectives'] = $uObjectives;
            $u['materials'] = $uMaterials;
            $u['misconceptions'] = $uMisconceptions;
            $u['activations'] = $uActivations;
            $u['activities'] = $uActivities;
            $u['assessment_references'] = $uAssessments;
            $u['followup_guidance'] = $uFollowups;
            $u['reflection_prompts'] = $uReflections;

            $structuredUnits[] = $u;
        }

        $pack['units'] = $structuredUnits;
        $pack['concepts'] = $concepts;
        $pack['resources'] = $resources;
        $pack['coverage'] = self::coverage($packUuid);

        return $pack;
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

    private static function resolveGuidanceOwner(array $data, ?string $packUuid = null): array
    {
        $set = array_filter([
            'learning_unit_uuid' => $data['learning_unit_uuid'] ?? null,
            'activity_uuid' => $data['activity_uuid'] ?? null,
            'concept_uuid' => $data['concept_uuid'] ?? null,
        ]);
        if (count($set) > 1) {
            throw new InvalidArgumentException('Guidance tidak dapat ditautkan ke lebih dari satu entitas anak.');
        }
        if (isset($set['learning_unit_uuid'])) {
            $unit = self::unitByUuid((string) $set['learning_unit_uuid']);
            return ['learning_pack_id' => (int) $unit['learning_pack_id'], 'learning_unit_id' => (int) $unit['id']];
        }
        if (isset($set['activity_uuid'])) {
            $activity = self::activityByUuid((string) $set['activity_uuid']);
            return ['learning_pack_id' => (int) $activity['learning_pack_id'], 'activity_id' => (int) $activity['id']];
        }
        if (isset($set['concept_uuid'])) {
            $concept = self::conceptByUuid((string) $set['concept_uuid']);
            return ['learning_pack_id' => (int) $concept['learning_pack_id'], 'concept_id' => (int) $concept['id']];
        }

        $pUuid = $packUuid ?? $data['learning_pack_uuid'] ?? null;
        if ($pUuid) {
            $pack = self::packByUuid((string) $pUuid);
            return ['learning_pack_id' => (int) $pack['id']];
        }

        throw new InvalidArgumentException('Guidance harus ditautkan ke unit, aktivitas, konsep, atau paket belajar.');
    }

    private static function resolveUnitOrActivityOwner(array $data, ?string $packUuid = null): array
    {
        $set = array_filter([
            'learning_unit_uuid' => $data['learning_unit_uuid'] ?? null,
            'activity_uuid' => $data['activity_uuid'] ?? null,
        ]);
        if (count($set) > 1) {
            throw new InvalidArgumentException('Data tidak dapat ditautkan ke lebih dari satu entitas.');
        }
        if (isset($set['learning_unit_uuid'])) {
            $unit = self::unitByUuid((string) $set['learning_unit_uuid']);
            return ['learning_pack_id' => (int) $unit['learning_pack_id'], 'learning_unit_id' => (int) $unit['id']];
        }
        if (isset($set['activity_uuid'])) {
            $activity = self::activityByUuid((string) $set['activity_uuid']);
            return ['learning_pack_id' => (int) $activity['learning_pack_id'], 'activity_id' => (int) $activity['id']];
        }

        $pUuid = $packUuid ?? $data['learning_pack_uuid'] ?? null;
        if ($pUuid) {
            $pack = self::packByUuid((string) $pUuid);
            return ['learning_pack_id' => (int) $pack['id']];
        }

        throw new InvalidArgumentException('Data harus ditautkan ke unit, aktivitas, atau paket belajar.');
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
