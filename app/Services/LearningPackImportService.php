<?php

namespace App\Services;

use Config\Database;
use InvalidArgumentException;

class LearningPackImportService
{
    private const ENTITY_TYPES = [
        'LEARNING_UNIT',
        'CONCEPT',
        'PREREQUISITE',
        'MATERIAL_TOPIC',
        'MISCONCEPTION',
        'ACTIVITY',
        'RESOURCE',
        'GUIDANCE',
        'EXPECTED_RESPONSE',
        'ASSESSMENT_REFERENCE',
        'FOLLOWUP_GUIDANCE',
    ];

    public static function stage(int $unitId, ?string $packUuid, string $sourceFilename, array $rows): array
    {
        UnitScopeService::assertUnit($unitId);
        $db = Database::connect();
        $pack = null;
        if ($packUuid !== null) {
            $pack = EducationFoundationService::byUuid('subject_learning_packs', $packUuid);
            UnitScopeService::assertUnit((int) $pack['unit_id']);
            if ((int) $pack['unit_id'] !== $unitId) {
                throw new InvalidArgumentException('Import pack lintas unit ditolak.');
            }
        }

        $now = date('Y-m-d H:i:s');
        $hash = hash('sha256', json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
        $batch = [
            'uuid' => UuidService::v4(),
            'unit_id' => $unitId,
            'learning_pack_id' => $pack ? (int) $pack['id'] : null,
            'source_filename' => $sourceFilename,
            'source_hash' => $hash,
            'status' => 'STAGED',
            'total_rows' => count($rows),
            'valid_rows' => 0,
            'error_rows' => 0,
            'applied_rows' => 0,
            'created_by' => EducationFoundationService::actorId(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->transBegin();
        $db->table('learning_pack_import_batches')->insert($batch);
        $batchId = (int) $db->insertID();
        $valid = 0;
        $error = 0;
        foreach (array_values($rows) as $index => $row) {
            $entityType = strtoupper((string) ($row['entity_type'] ?? ''));
            $message = self::validatePayload($entityType, (array) ($row['payload'] ?? []));
            $status = $message === null ? 'VALID' : 'ERROR';
            $valid += $status === 'VALID' ? 1 : 0;
            $error += $status === 'ERROR' ? 1 : 0;
            $db->table('learning_pack_import_rows')->insert([
                'uuid' => UuidService::v4(),
                'batch_id' => $batchId,
                'row_number' => $index + 1,
                'entity_type' => $entityType,
                'payload_json' => json_encode((array) ($row['payload'] ?? []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status' => $status,
                'error_message' => $message,
                'created_by' => EducationFoundationService::actorId(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $db->table('learning_pack_import_batches')->where('id', $batchId)->update([
            'valid_rows' => $valid,
            'error_rows' => $error,
            'status' => $error === 0 ? 'VALIDATED' : 'NEEDS_FIX',
            'updated_at' => $now,
        ]);
        $db->transCommit();

        $created = $db->table('learning_pack_import_batches')->where('id', $batchId)->get()->getRowArray();
        AuditService::log('learning_packs', 'STAGE_IMPORT', 'LearningPackImportBatch', $batchId, null, $created, null, $created['uuid']);

        return $created;
    }

    public static function preview(string $batchUuid): array
    {
        $batch = EducationFoundationService::byUuid('learning_pack_import_batches', $batchUuid);
        UnitScopeService::assertUnit((int) $batch['unit_id']);
        $rows = Database::connect()->table('learning_pack_import_rows')->where('batch_id', (int) $batch['id'])->orderBy('row_number')->get()->getResultArray();

        return ['batch' => $batch, 'rows' => $rows];
    }

    public static function apply(string $batchUuid): array
    {
        $preview = self::preview($batchUuid);
        $batch = $preview['batch'];
        if ($batch['status'] !== 'VALIDATED') {
            throw new InvalidArgumentException('Batch import belum valid.');
        }
        $db = Database::connect();
        $pack = $batch['learning_pack_id'] ? $db->table('subject_learning_packs')->where('id', (int) $batch['learning_pack_id'])->get()->getRowArray() : null;
        if (! $pack) {
            throw new InvalidArgumentException('Batch import Phase 3 wajib memiliki learning pack target.');
        }

        $applied = 0;
        $db->transBegin();
        foreach ($preview['rows'] as $row) {
            $payload = json_decode((string) $row['payload_json'], true) ?: [];
            $created = self::applyRow($pack['uuid'], (string) $row['entity_type'], $payload);
            $db->table('learning_pack_import_rows')->where('id', (int) $row['id'])->update([
                'status' => 'APPLIED',
                'applied_entity_type' => $row['entity_type'],
                'applied_entity_id' => (int) ($created['id'] ?? 0),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $applied++;
        }
        $db->table('learning_pack_import_batches')->where('id', (int) $batch['id'])->update([
            'status' => 'APPLIED',
            'applied_rows' => $applied,
            'applied_by' => EducationFoundationService::actorId(),
            'applied_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();

        $updated = $db->table('learning_pack_import_batches')->where('id', (int) $batch['id'])->get()->getRowArray();
        AuditService::log('learning_packs', 'APPLY_IMPORT', 'LearningPackImportBatch', (int) $batch['id'], $batch, $updated, null, $batchUuid);

        return $updated;
    }

    private static function applyRow(string $packUuid, string $entityType, array $payload): array
    {
        return match ($entityType) {
            'LEARNING_UNIT' => SubjectLearningPackEngineService::createUnit($packUuid, $payload),
            'CONCEPT' => SubjectLearningPackEngineService::createConcept($packUuid, $payload),
            'MATERIAL_TOPIC' => SubjectLearningPackEngineService::addMaterialTopic((string) $payload['learning_unit_uuid'], $payload),
            'MISCONCEPTION' => SubjectLearningPackEngineService::addMisconception((string) $payload['learning_unit_uuid'], $payload),
            'ACTIVITY' => SubjectLearningPackEngineService::createActivity((string) $payload['learning_unit_uuid'], $payload),
            'RESOURCE' => SubjectLearningPackEngineService::createResource($packUuid, $payload),
            'GUIDANCE' => SubjectLearningPackEngineService::addTeacherGuidance($payload),
            'EXPECTED_RESPONSE' => SubjectLearningPackEngineService::addExpectedResponse((string) $payload['activity_uuid'], $payload),
            'ASSESSMENT_REFERENCE' => SubjectLearningPackEngineService::addAssessmentReference($payload),
            'FOLLOWUP_GUIDANCE' => SubjectLearningPackEngineService::addFollowupGuidance($payload),
            default => throw new InvalidArgumentException('Entity import belum didukung untuk apply.'),
        };
    }

    private static function validatePayload(string $entityType, array $payload): ?string
    {
        if (! in_array($entityType, self::ENTITY_TYPES, true)) {
            return 'Entity type tidak didukung.';
        }
        $required = match ($entityType) {
            'LEARNING_UNIT' => ['code', 'title'],
            'CONCEPT' => ['code', 'title'],
            'MATERIAL_TOPIC', 'MISCONCEPTION', 'ACTIVITY' => ['learning_unit_uuid', 'code', 'title'],
            'RESOURCE' => ['resource_type', 'title'],
            'GUIDANCE' => ['title', 'guidance'],
            'EXPECTED_RESPONSE' => ['activity_uuid', 'description'],
            'ASSESSMENT_REFERENCE' => ['recommended_method'],
            'FOLLOWUP_GUIDANCE' => ['trigger_description', 'guidance'],
            'PREREQUISITE' => ['learning_unit_uuid'],
        };
        foreach ($required as $field) {
            if (! isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                return "Field {$field} wajib diisi.";
            }
        }

        return null;
    }
}
