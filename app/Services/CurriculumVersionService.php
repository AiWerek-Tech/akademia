<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Models\CurriculumStructureModel;
use App\Models\CurriculumRevisionHistoryModel;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;

class CurriculumVersionService
{
    /**
     * Fetch list of curriculum versions with optional filters
     */
    public static function getVersions(array $filters = [], int $limit = 50): array
    {
        $db = Database::connect();
        $builder = $db->table('curriculum_versions cv')
            ->select('cv.*, ap.name as period_name, ay.name as year_name, u_created.full_name as creator_name')
            ->join('academic_periods ap', 'ap.id = cv.academic_period_id', 'left')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->join('users u_created', 'u_created.id = cv.created_by', 'left');

        if (!empty($filters['academic_period_id'])) {
            $builder->where('cv.academic_period_id', (int)$filters['academic_period_id']);
        }
        if (!empty($filters['workflow_status'])) {
            $builder->where('cv.workflow_status', strtoupper(trim($filters['workflow_status'])));
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $builder->where('cv.is_active', (int)$filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('cv.code', $search)
                ->orLike('cv.name', $search)
                ->groupEnd();
        }

        $builder->orderBy('cv.id', 'DESC');
        $data = $builder->get($limit)->getResultArray();

        return [
            'data'  => $data,
            'count' => count($data),
        ];
    }

    /**
     * Get single curriculum version by UUID
     */
    public static function getVersionByUuid(string $uuid): ?array
    {
        $db = Database::connect();
        return $db->table('curriculum_versions cv')
            ->select('cv.*, ap.name as period_name, ay.name as year_name')
            ->join('academic_periods ap', 'ap.id = cv.academic_period_id', 'left')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id', 'left')
            ->where('cv.uuid', $uuid)
            ->get()
            ->getRowArray();
    }

    /**
     * Create new curriculum version
     */
    public static function createVersion(array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel = new CurriculumVersionModel();

            $code = strtoupper(trim($data['code'] ?? ''));
            $name = trim($data['name'] ?? '');
            $periodId = !empty($data['academic_period_id']) ? (int)$data['academic_period_id'] : null;

            if (empty($code) || empty($name) || !$periodId) {
                throw new \InvalidArgumentException('Kode, Nama, dan Periode Akademik wajib diisi.');
            }

            // Duplicate check per period
            $existing = $versionModel->where('academic_period_id', $periodId)->where('code', $code)->first();
            if ($existing) {
                throw new \InvalidArgumentException("Kode versi kurikulum '{$code}' sudah ada pada periode akademik ini.");
            }

            $userId = session()->get('user_id');
            $uuid = UuidService::v4();

            $record = [
                'uuid'                => $uuid,
                'academic_period_id'  => $periodId,
                'code'                => $code,
                'name'                => $name,
                'description'         => $data['description'] ?? null,
                'source_reference'    => $data['source_reference'] ?? null,
                'revision_number'     => 1,
                'workflow_status'     => 'DRAFT',
                'is_active'           => 0,
                'previous_version_id' => !empty($data['previous_version_id']) ? (int)$data['previous_version_id'] : null,
                'created_by'          => $userId,
            ];

            $id = $versionModel->insert($record);

            // History log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $id,
                'revision_number'       => 1,
                'action'                => 'CREATE_VERSION',
                'after_json'            => json_encode($record),
                'change_reason'         => 'Membuat versi kurikulum baru',
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'CREATE_VERSION', 'CurriculumVersion', $id, null, $record, "Membuat versi kurikulum {$code}");

            $db->transCommit();
            return self::getVersionByUuid($uuid);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update existing version with optimistic locking
     */
    public static function updateVersion(string $uuid, array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel = new CurriculumVersionModel();
            $version = $versionModel->where('uuid', $uuid)->first();
            if (!$version) {
                throw new \RuntimeException('Versi kurikulum tidak ditemukan.');
            }

            if (in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
                throw new \RuntimeException("Versi kurikulum berstatus {$version['workflow_status']} tidak dapat diubah secara langsung.");
            }

            // Optimistic Locking Check
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$version['revision_number']) {
                throw new \RuntimeException('Data telah diubah oleh pengguna lain. Silakan muat ulang halaman.');
            }

            $userId = session()->get('user_id');
            $newRevision = (int)$version['revision_number'] + 1;

            $updateData = [
                'name'             => isset($data['name']) ? trim($data['name']) : $version['name'],
                'description'      => isset($data['description']) ? trim($data['description']) : $version['description'],
                'source_reference' => isset($data['source_reference']) ? trim($data['source_reference']) : $version['source_reference'],
                'revision_number'  => $newRevision,
                'updated_by'       => $userId,
            ];

            $db->table('curriculum_versions')
                ->where('id', $version['id'])
                ->where('revision_number', $version['revision_number'])
                ->update($updateData);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Versi kurikulum telah diubah oleh pengguna lain. Silakan muat ulang halaman.');
            }

            // History log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $version['id'],
                'revision_number'       => $newRevision,
                'action'                => 'UPDATE_VERSION',
                'before_json'           => json_encode($version),
                'after_json'            => json_encode($updateData),
                'change_reason'         => $data['change_reason'] ?? 'Memperbarui info versi kurikulum',
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'UPDATE_VERSION', 'CurriculumVersion', $version['id'], $version, $updateData, "Update versi kurikulum {$version['code']}");

            $db->transCommit();
            return self::getVersionByUuid($uuid);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Clone version and all its active structures into a new version
     */
    public static function cloneVersion(string $sourceUuid, string $newCode, string $newName, ?string $reason = null): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel   = new CurriculumVersionModel();
            $structureModel = new CurriculumStructureModel();

            $source = $versionModel->where('uuid', $sourceUuid)->first();
            if (!$source) {
                throw new \RuntimeException('Versi sumber tidak ditemukan.');
            }

            $newCode = strtoupper(trim($newCode));
            $newName = trim($newName);

            // Check duplicate code
            $existing = $versionModel->where('academic_period_id', $source['academic_period_id'])->where('code', $newCode)->first();
            if ($existing) {
                throw new \InvalidArgumentException("Kode versi kurikulum '{$newCode}' sudah ada pada periode akademik ini.");
            }

            $userId = session()->get('user_id');
            $newUuid = UuidService::v4();

            $newVersionRecord = [
                'uuid'                => $newUuid,
                'academic_period_id'  => $source['academic_period_id'],
                'code'                => $newCode,
                'name'                => $newName,
                'description'         => 'Klon dari ' . $source['code'] . ': ' . $source['description'],
                'source_reference'    => $source['source_reference'],
                'revision_number'     => 1,
                'workflow_status'     => 'DRAFT',
                'is_active'           => 0,
                'previous_version_id' => $source['id'],
                'change_summary'      => 'Diklon dari versi ' . $source['code'] . ($reason ? ' - ' . $reason : ''),
                'created_by'          => $userId,
            ];

            $newVersionId = $versionModel->insert($newVersionRecord);

            // Copy all active structures
            $structures = $structureModel->where('curriculum_version_id', $source['id'])
                ->where('status', 'ACTIVE')
                ->findAll();

            $clonedCount = 0;
            foreach ($structures as $s) {
                $structRecord = $s;
                unset($structRecord['id']);
                $structRecord['uuid']                  = UuidService::v4();
                $structRecord['curriculum_version_id'] = $newVersionId;
                $structRecord['revision_number']       = 1;
                $structRecord['created_by']            = $userId;
                $structRecord['updated_by']            = null;
                $structRecord['created_at']            = date('Y-m-d H:i:s');
                $structRecord['updated_at']            = date('Y-m-d H:i:s');

                $structureModel->insert($structRecord);
                $clonedCount++;
            }

            // History log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $newVersionId,
                'revision_number'       => 1,
                'action'                => 'CLONE_VERSION',
                'before_json'           => json_encode(['source_version_id' => $source['id'], 'source_code' => $source['code']]),
                'after_json'            => json_encode(['cloned_structures' => $clonedCount]),
                'change_reason'         => $reason ?? "Mengklon versi {$source['code']} ke {$newCode}",
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'CLONE_VERSION', 'CurriculumVersion', $newVersionId, ['source' => $source['code']], ['cloned' => $clonedCount], "Klon versi kurikulum {$newCode}");

            $db->transCommit();
            return self::getVersionByUuid($newUuid);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
