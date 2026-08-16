<?php

namespace App\Services;

use App\Models\CurriculumSourceModel;
use App\Models\RegulationModel;
use App\Models\RegulationVersionModel;
use Config\Database;
use RuntimeException;

class RegulationRegistryService
{
    public static function regulations(): array
    {
        return Database::connect()->table('regulations r')->select('r.*, COUNT(rv.id) version_count')
            ->join('regulation_versions rv', 'rv.regulation_id = r.id', 'left')->groupBy('r.id')->orderBy('r.code')->get()->getResultArray();
    }

    public static function createRegulation(array $data): array
    {
        EducationFoundationService::requireFields($data, ['code', 'title', 'authority', 'regulation_type']);
        $record = [
            'uuid' => UuidService::v4(), 'code' => strtoupper(trim($data['code'])), 'title' => trim($data['title']),
            'authority' => trim($data['authority']), 'regulation_type' => strtoupper(trim($data['regulation_type'])),
            'status' => strtoupper($data['status'] ?? 'DRAFT'), 'published_at' => $data['published_at'] ?? null,
            'effective_from' => $data['effective_from'] ?? null, 'effective_until' => $data['effective_until'] ?? null,
            'description' => $data['description'] ?? null, 'created_by' => EducationFoundationService::actorId(),
        ];
        $id = (new RegulationModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_REGULATION', 'Regulation', (int) $id, null, $record);
        return (new RegulationModel())->find($id);
    }

    public static function addVersion(string $regulationUuid, array $data): array
    {
        $regulation = EducationFoundationService::byUuid('regulations', $regulationUuid);
        $latest = Database::connect()->table('regulation_versions')->selectMax('version_number')->where('regulation_id', $regulation['id'])->get()->getRowArray();
        $record = [
            'uuid' => UuidService::v4(), 'regulation_id' => $regulation['id'],
            'version_number' => ((int) ($latest['version_number'] ?? 0)) + 1,
            'document_url' => $data['document_url'] ?? null, 'document_hash' => $data['document_hash'] ?? null,
            'mime_type' => $data['mime_type'] ?? null, 'notes' => $data['notes'] ?? null,
            'is_published' => 0, 'created_by' => EducationFoundationService::actorId(),
        ];
        $id = (new RegulationVersionModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_REGULATION_VERSION', 'RegulationVersion', (int) $id, null, $record);
        return (new RegulationVersionModel())->find($id);
    }

    public static function publishVersion(string $uuid): array
    {
        $version = EducationFoundationService::byUuid('regulation_versions', $uuid);
        if ((int) $version['is_published'] === 1) {
            return $version;
        }
        $changes = ['is_published' => 1, 'published_at' => date('Y-m-d H:i:s'), 'updated_by' => EducationFoundationService::actorId(), 'updated_at' => date('Y-m-d H:i:s')];
        Database::connect()->table('regulation_versions')->where('id', $version['id'])->update($changes);
        AuditService::log('education_foundation', 'PUBLISH_REGULATION_VERSION', 'RegulationVersion', (int) $version['id'], $version, $changes);
        return EducationFoundationService::byUuid('regulation_versions', $uuid);
    }

    public static function updateVersion(string $uuid, array $data): array
    {
        $version = EducationFoundationService::byUuid('regulation_versions', $uuid);
        if ((int) $version['is_published'] === 1) {
            throw new RuntimeException('Versi regulasi yang telah diterbitkan bersifat immutable. Buat versi baru.');
        }
        $allowed = array_intersect_key($data, array_flip(['document_url', 'document_hash', 'mime_type', 'notes']));
        Database::connect()->table('regulation_versions')->where('id', $version['id'])->update($allowed + ['updated_at' => date('Y-m-d H:i:s'), 'updated_by' => EducationFoundationService::actorId()]);
        AuditService::log('education_foundation', 'UPDATE_REGULATION_VERSION', 'RegulationVersion', (int) $version['id'], $version, $allowed);
        return EducationFoundationService::byUuid('regulation_versions', $uuid);
    }

    public static function createSource(array $data): array
    {
        EducationFoundationService::requireFields($data, ['code', 'title', 'source_type', 'issuer']);
        $record = array_intersect_key($data, array_flip(['regulation_version_id','source_url','source_hash','published_at','metadata_json'])) + [
            'uuid' => UuidService::v4(), 'code' => strtoupper(trim($data['code'])), 'title' => trim($data['title']),
            'source_type' => strtoupper(trim($data['source_type'])), 'issuer' => trim($data['issuer']),
            'status' => strtoupper($data['status'] ?? 'DRAFT'), 'created_by' => EducationFoundationService::actorId(),
        ];
        if ($record['status']==='PUBLISHED' && empty($record['regulation_version_id']) && empty($record['source_url']) && empty($record['source_hash'])) throw new \InvalidArgumentException('Sumber terbit wajib memiliki provenance regulasi, URL, atau hash.');
        $id = (new CurriculumSourceModel())->insert($record, true);
        AuditService::log('education_foundation', 'CREATE_CURRICULUM_SOURCE', 'CurriculumSource', (int) $id, null, $record);
        return (new CurriculumSourceModel())->find($id);
    }
}
