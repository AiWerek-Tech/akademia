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

            $newCode = isset($data['code']) ? strtoupper(trim($data['code'])) : $version['code'];
            $newName = isset($data['name']) ? trim($data['name']) : $version['name'];
            $newPeriodId = !empty($data['academic_period_id']) ? (int)$data['academic_period_id'] : (int)$version['academic_period_id'];
            $newStatus = isset($data['workflow_status']) ? strtoupper(trim($data['workflow_status'])) : $version['workflow_status'];
            $newIsActive = isset($data['is_active']) ? (int)$data['is_active'] : (int)$version['is_active'];

            // Duplicate code check if code or period changed
            if ($newCode !== $version['code'] || $newPeriodId !== (int)$version['academic_period_id']) {
                $existing = $versionModel->where('academic_period_id', $newPeriodId)
                    ->where('code', $newCode)
                    ->where('id !=', $version['id'])
                    ->first();
                if ($existing) {
                    throw new \InvalidArgumentException("Kode versi kurikulum '{$newCode}' sudah ada pada periode akademik ini.");
                }
            }

            $userId = session()->get('user_id');
            $newRevision = (int)$version['revision_number'] + 1;

            $updateData = [
                'code'               => $newCode,
                'name'               => $newName,
                'academic_period_id' => $newPeriodId,
                'description'        => isset($data['description']) ? trim($data['description']) : $version['description'],
                'source_reference'   => isset($data['source_reference']) ? trim($data['source_reference']) : $version['source_reference'],
                'workflow_status'    => $newStatus,
                'is_active'          => $newIsActive,
                'revision_number'    => $newRevision,
                'updated_by'         => $userId,
            ];

            $db->table('curriculum_versions')
                ->where('id', $version['id'])
                ->update($updateData);

            if ($newIsActive === 1) {
                // Deactivate only other versions for the SAME UNIT in this academic period
                self::deactivateOtherVersionsForSameUnit((int)$version['id'], $newPeriodId, $userId);
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

    public static function getVersionUnitIds(int $versionId): array
    {
        $db = Database::connect();

        $planningUnits = $db->table('curriculum_planning_settings')
            ->select('unit_id')
            ->where('curriculum_version_id', $versionId)
            ->get()->getResultArray();
        $unitIds = array_filter(array_map('intval', array_column($planningUnits, 'unit_id')));

        if ($unitIds !== []) {
            return array_values(array_unique($unitIds));
        }

        $structUnits = $db->table('curriculum_structures')
            ->distinct()->select('unit_id')
            ->where('curriculum_version_id', $versionId)
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();
        $unitIds = array_filter(array_map('intval', array_column($structUnits, 'unit_id')));

        if ($unitIds !== []) {
            return array_values(array_unique($unitIds));
        }

        $version = $db->table('curriculum_versions')->where('id', $versionId)->get()->getRowArray();
        if ($version) {
            $str = strtoupper($version['code'] . ' ' . $version['name']);
            if (strpos($str, 'SMP') !== false) {
                $unit = $db->table('school_units')->where('code', 'SMP')->get()->getRowArray();
                if ($unit) return [(int)$unit['id']];
            } elseif (strpos($str, 'SMA') !== false) {
                $unit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
                if ($unit) return [(int)$unit['id']];
            }
        }

        return [];
    }

    public static function deactivateOtherVersionsForSameUnit(int $versionId, int $academicPeriodId, ?int $userId = null): void
    {
        $db = Database::connect();
        $unitIds = self::getVersionUnitIds($versionId);

        if ($unitIds !== []) {
            $otherVersionIds = [];

            $planRows = $db->table('curriculum_planning_settings cps')
                ->select('cps.curriculum_version_id')
                ->join('curriculum_versions cv', 'cv.id = cps.curriculum_version_id')
                ->where('cv.academic_period_id', $academicPeriodId)
                ->where('cv.id !=', $versionId)
                ->whereIn('cps.unit_id', $unitIds)
                ->get()->getResultArray();
            foreach ($planRows as $r) {
                $otherVersionIds[] = (int)$r['curriculum_version_id'];
            }

            $structRows = $db->table('curriculum_structures cs')
                ->select('cs.curriculum_version_id')
                ->join('curriculum_versions cv', 'cv.id = cs.curriculum_version_id')
                ->where('cv.academic_period_id', $academicPeriodId)
                ->where('cv.id !=', $versionId)
                ->whereIn('cs.unit_id', $unitIds)
                ->where('cs.deleted_at IS NULL')
                ->get()->getResultArray();
            foreach ($structRows as $r) {
                $otherVersionIds[] = (int)$r['curriculum_version_id'];
            }

            $version = $db->table('curriculum_versions')->where('id', $versionId)->get()->getRowArray();
            if ($version) {
                $unitCode = in_array(1, $unitIds, true) ? 'SMP' : (in_array(2, $unitIds, true) ? 'SMA' : '');
                if ($unitCode !== '') {
                    $codeRows = $db->table('curriculum_versions')
                        ->select('id')
                        ->where('academic_period_id', $academicPeriodId)
                        ->where('id !=', $versionId)
                        ->groupStart()
                            ->like('code', $unitCode)
                            ->orLike('name', $unitCode)
                        ->groupEnd()
                        ->get()->getResultArray();
                    foreach ($codeRows as $r) {
                        $otherVersionIds[] = (int)$r['id'];
                    }
                }
            }

            $otherVersionIds = array_values(array_unique(array_filter($otherVersionIds)));
            if ($otherVersionIds !== []) {
                $db->table('curriculum_versions')
                    ->whereIn('id', $otherVersionIds)
                    ->update([
                        'is_active'  => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'updated_by' => $userId ?: 1,
                    ]);
            }
        }
    }
}
