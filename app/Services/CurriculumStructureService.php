<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Models\CurriculumStructureModel;
use App\Models\CurriculumRevisionHistoryModel;
use App\Models\SubjectModel;
use App\Models\ClassroomModel;
use App\Models\GradeLevelModel;
use App\Services\CurriculumEffectiveHoursService;
use App\Services\BlockPatternService;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;

class CurriculumStructureService
{
    /**
     * Get structures for a curriculum version with filters
     */
    public static function getStructures(int $versionId, array $filters = []): array
    {
        $db = Database::connect();
        $builder = $db->table('curriculum_structures cs')
            ->select('cs.*, s.code as subject_code, s.name as subject_name, s.category as subject_default_category, gl.code as grade_code, gl.name as grade_name, c.name as classroom_name, rt.name as room_type_name, su.code as unit_code')
            ->join('subjects s', 's.id = cs.subject_id', 'left')
            ->join('grade_levels gl', 'gl.id = cs.grade_level_id', 'left')
            ->join('classrooms c', 'c.id = cs.classroom_id', 'left')
            ->join('room_types rt', 'rt.id = cs.required_room_type_id', 'left')
            ->join('school_units su', 'su.id = cs.unit_id', 'left')
            ->where('cs.curriculum_version_id', $versionId)
            ->where('cs.status', 'ACTIVE')
            ->where('cs.deleted_at IS NULL');

        if (session()->get('logged_in')) {
            $allowedUnitIds = UnitScopeService::accessibleUnitIds();
            if ($allowedUnitIds === []) {
                return ['data' => [], 'count' => 0];
            }
            $builder->whereIn('cs.unit_id', $allowedUnitIds);
        }

        if (!empty($filters['unit_id'])) {
            $unitId = (int) $filters['unit_id'];
            if (session()->get('logged_in')) {
                UnitScopeService::assertUnit($unitId);
            }
            $builder->where('cs.unit_id', $unitId);
        }
        if (!empty($filters['grade_level_id'])) {
            $builder->where('cs.grade_level_id', (int)$filters['grade_level_id']);
        }
        if (isset($filters['classroom_id'])) {
            if ($filters['classroom_id'] === 'null' || $filters['classroom_id'] === null || $filters['classroom_id'] === '') {
                $builder->where('cs.classroom_id IS NULL');
            } else {
                $builder->where('cs.classroom_id', (int)$filters['classroom_id']);
            }
        }
        if (!empty($filters['category'])) {
            $builder->where('cs.category', strtoupper(trim($filters['category'])));
        }

        $builder->orderBy('gl.grade_number', 'ASC')->orderBy('s.code', 'ASC');
        $data = $builder->get()->getResultArray();

        return [
            'data'  => $data,
            'count' => count($data),
        ];
    }

    /**
     * Create new curriculum structure record
     */
    public static function createStructure(array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel   = new CurriculumVersionModel();
            $structureModel = new CurriculumStructureModel();
            $subjectModel   = new SubjectModel();
            $gradeModel     = new GradeLevelModel();
            $classroomModel = new ClassroomModel();

            $versionId = (int)($data['curriculum_version_id'] ?? 0);
            $version = $versionModel->find($versionId);
            if (!$version) {
                throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
            }

            if (in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
                throw new \RuntimeException("Versi kurikulum berstatus {$version['workflow_status']} bersifat immutable. Tidak dapat menambahkan struktur baru.");
            }

            $unitId       = (int)($data['unit_id'] ?? 0);
            $gradeLevelId = (int)($data['grade_level_id'] ?? 0);
            $classroomId  = !empty($data['classroom_id']) ? (int)$data['classroom_id'] : null;
            $subjectId    = (int)($data['subject_id'] ?? 0);

            if (!$unitId || !$gradeLevelId || !$subjectId) {
                throw new \InvalidArgumentException('Unit, Tingkat Kelas, dan Mata Pelajaran wajib diisi.');
            }

            if (session()->get('logged_in')) {
                UnitScopeService::assertUnit($unitId);
            }

            // Verify Subject exists & unit availability
            $subject = $subjectModel->find($subjectId);
            if (!$subject || (int)$subject['is_active'] !== 1) {
                throw new \InvalidArgumentException('Mata pelajaran tidak ditemukan atau tidak aktif.');
            }

            $avail = $db->table('subject_unit_availability')
                ->where('subject_id', $subjectId)
                ->where('unit_id', $unitId)
                ->get()
                ->getRowArray();
            if (!$avail) {
                throw new \InvalidArgumentException("Mata pelajaran '{$subject['name']}' tidak dikonfigurasi untuk unit sekolah ini.");
            }

            // Verify Grade Level unit mismatch
            $grade = $gradeModel->find($gradeLevelId);
            if (!$grade || (int)$grade['unit_id'] !== $unitId) {
                throw new \InvalidArgumentException('Tingkat kelas tidak sesuai dengan unit sekolah.');
            }

            // Verify Classroom period & unit mismatch if override
            if ($classroomId !== null) {
                $classroom = $classroomModel->find($classroomId);
                if (!$classroom || (int)$classroom['unit_id'] !== $unitId || (int)$classroom['grade_level_id'] !== $gradeLevelId) {
                    throw new \InvalidArgumentException('Kelas override tidak sesuai dengan unit atau tingkat kelas.');
                }
                if ((int)$classroom['academic_period_id'] !== (int)$version['academic_period_id']) {
                    throw new \InvalidArgumentException('Kelas override berasal dari periode akademik yang berbeda.');
                }
            }

            // Duplicate Scope Guard
            if ($classroomId === null) {
                $dup = $structureModel->where('curriculum_version_id', $versionId)
                    ->where('unit_id', $unitId)
                    ->where('grade_level_id', $gradeLevelId)
                    ->where('classroom_id IS NULL')
                    ->where('subject_id', $subjectId)
                    ->where('status', 'ACTIVE')
                    ->first();
                if ($dup) {
                    throw new \InvalidArgumentException("Mata pelajaran '{$subject['name']}' sudah ada pada default tingkat kelas ini.");
                }
            } else {
                $dup = $structureModel->where('curriculum_version_id', $versionId)
                    ->where('classroom_id', $classroomId)
                    ->where('subject_id', $subjectId)
                    ->where('status', 'ACTIVE')
                    ->first();
                if ($dup) {
                    throw new \InvalidArgumentException("Mata pelajaran '{$subject['name']}' sudah ada pada override kelas ini.");
                }
            }

            // Calculate Effective Hours (Server-Side)
            $effResult = CurriculumEffectiveHoursService::calculateEffectiveHours($data);

            // Block Pattern Validation
            $blockPatternJson = null;
            if (!empty($data['block_pattern_json'])) {
                $minDays = !empty($data['minimum_days']) ? (int)$data['minimum_days'] : null;
                $maxHours = !empty($data['maximum_daily_hours']) ? (float)$data['maximum_daily_hours'] : null;

                $bpCheck = BlockPatternService::validateBlockPattern(
                    $data['block_pattern_json'],
                    $effResult['effective_weekly_hours'],
                    $minDays,
                    $maxHours
                );

                if (!$bpCheck['valid']) {
                    throw new \InvalidArgumentException('Pola blok tidak valid: ' . implode(', ', $bpCheck['errors']));
                }
                $blockPatternJson = BlockPatternService::canonicalize($data['block_pattern_json']);
            }

            $userId = session()->get('user_id');
            $uuid = UuidService::v4();

            $record = [
                'uuid'                    => $uuid,
                'curriculum_version_id'   => $versionId,
                'unit_id'                 => $unitId,
                'grade_level_id'          => $gradeLevelId,
                'classroom_id'            => $classroomId,
                'subject_id'              => $subjectId,
                'official_weekly_hours'   => !empty($data['official_weekly_hours']) ? (float)$data['official_weekly_hours'] : null,
                'custom_weekly_hours'     => !empty($data['custom_weekly_hours']) ? (float)$data['custom_weekly_hours'] : null,
                'manual_weekly_hours'     => !empty($data['manual_weekly_hours']) ? (float)$data['manual_weekly_hours'] : null,
                'effective_weekly_hours'  => $effResult['effective_weekly_hours'],
                'effective_source'        => $effResult['effective_source'],
                'category'                => !empty($data['category']) ? strtoupper(trim($data['category'])) : 'INTRAKURIKULER',
                'block_pattern_json'      => $blockPatternJson,
                'minimum_days'            => !empty($data['minimum_days']) ? (int)$data['minimum_days'] : null,
                'maximum_daily_hours'     => !empty($data['maximum_daily_hours']) ? (float)$data['maximum_daily_hours'] : null,
                'counts_in_report'        => isset($data['counts_in_report']) ? (int)$data['counts_in_report'] : 1,
                'counts_as_teaching_load' => isset($data['counts_as_teaching_load']) ? (int)$data['counts_as_teaching_load'] : 1,
                'required_room_type_id'   => !empty($data['required_room_type_id']) ? (int)$data['required_room_type_id'] : null,
                'schedule_priority'       => isset($data['schedule_priority']) ? (int)$data['schedule_priority'] : 0,
                'adjustment_reason'       => $effResult['adjustment_reason'],
                'legal_reference'         => $data['legal_reference'] ?? null,
                'notes'                   => $data['notes'] ?? null,
                'status'                  => 'ACTIVE',
                'revision_number'         => 1,
                'created_by'              => $userId,
            ];

            $id = $structureModel->insert($record);

            // History Log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $versionId,
                'structure_id'          => $id,
                'revision_number'       => 1,
                'action'                => 'CREATE_STRUCTURE',
                'after_json'            => json_encode($record),
                'change_reason'         => 'Menambahkan struktur mata pelajaran',
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'CREATE_STRUCTURE', 'CurriculumStructure', $id, null, $record, "Tambah struktur mapel ID {$subjectId}");

            $db->transCommit();

            return $structureModel->find($id);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update structure record with optimistic locking
     */
    public static function updateStructure(string $uuid, array $data): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel   = new CurriculumVersionModel();
            $structureModel = new CurriculumStructureModel();

            $structure = $structureModel->where('uuid', $uuid)->first();
            if (!$structure) {
                throw new \RuntimeException('Struktur kurikulum tidak ditemukan.');
            }

            $version = $versionModel->find($structure['curriculum_version_id']);
            if (in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
                throw new \RuntimeException("Versi kurikulum berstatus {$version['workflow_status']} bersifat immutable. Tidak dapat diubah.");
            }

            // Optimistic Lock Check
            if (isset($data['revision_number']) && (int)$data['revision_number'] !== (int)$structure['revision_number']) {
                throw new \RuntimeException('Struktur telah diperbarui oleh pengguna lain. Silakan refresh halaman.');
            }

            // Merge data for recalculating effective hours
            $merged = array_merge($structure, $data);
            $effResult = CurriculumEffectiveHoursService::calculateEffectiveHours($merged);

            // Block Pattern Validation
            $blockPatternJson = $structure['block_pattern_json'];
            if (array_key_exists('block_pattern_json', $data)) {
                if (!empty($data['block_pattern_json'])) {
                    $minDays = isset($data['minimum_days']) ? (int)$data['minimum_days'] : (!empty($structure['minimum_days']) ? (int)$structure['minimum_days'] : null);
                    $maxHours = isset($data['maximum_daily_hours']) ? (float)$data['maximum_daily_hours'] : (!empty($structure['maximum_daily_hours']) ? (float)$structure['maximum_daily_hours'] : null);

                    $bpCheck = BlockPatternService::validateBlockPattern(
                        $data['block_pattern_json'],
                        $effResult['effective_weekly_hours'],
                        $minDays,
                        $maxHours
                    );

                    if (!$bpCheck['valid']) {
                        throw new \InvalidArgumentException('Pola blok tidak valid: ' . implode(', ', $bpCheck['errors']));
                    }
                    $blockPatternJson = BlockPatternService::canonicalize($data['block_pattern_json']);
                } else {
                    $blockPatternJson = null;
                }
            }

            $userId = session()->get('user_id');
            $newRevision = (int)$structure['revision_number'] + 1;

            $updateData = [
                'official_weekly_hours'   => array_key_exists('official_weekly_hours', $data) ? ($data['official_weekly_hours'] !== '' && $data['official_weekly_hours'] !== null ? (float)$data['official_weekly_hours'] : null) : $structure['official_weekly_hours'],
                'custom_weekly_hours'     => array_key_exists('custom_weekly_hours', $data) ? ($data['custom_weekly_hours'] !== '' && $data['custom_weekly_hours'] !== null ? (float)$data['custom_weekly_hours'] : null) : $structure['custom_weekly_hours'],
                'manual_weekly_hours'     => array_key_exists('manual_weekly_hours', $data) ? ($data['manual_weekly_hours'] !== '' && $data['manual_weekly_hours'] !== null ? (float)$data['manual_weekly_hours'] : null) : $structure['manual_weekly_hours'],
                'effective_weekly_hours'  => $effResult['effective_weekly_hours'],
                'effective_source'        => $effResult['effective_source'],
                'category'                => isset($data['category']) ? strtoupper(trim($data['category'])) : $structure['category'],
                'block_pattern_json'      => $blockPatternJson,
                'minimum_days'            => array_key_exists('minimum_days', $data) ? ($data['minimum_days'] !== '' && $data['minimum_days'] !== null ? (int)$data['minimum_days'] : null) : $structure['minimum_days'],
                'maximum_daily_hours'     => array_key_exists('maximum_daily_hours', $data) ? ($data['maximum_daily_hours'] !== '' && $data['maximum_daily_hours'] !== null ? (float)$data['maximum_daily_hours'] : null) : $structure['maximum_daily_hours'],
                'counts_in_report'        => isset($data['counts_in_report']) ? (int)$data['counts_in_report'] : $structure['counts_in_report'],
                'counts_as_teaching_load' => isset($data['counts_as_teaching_load']) ? (int)$data['counts_as_teaching_load'] : $structure['counts_as_teaching_load'],
                'required_room_type_id'   => array_key_exists('required_room_type_id', $data) ? ($data['required_room_type_id'] !== '' && $data['required_room_type_id'] !== null ? (int)$data['required_room_type_id'] : null) : $structure['required_room_type_id'],
                'schedule_priority'       => isset($data['schedule_priority']) ? (int)$data['schedule_priority'] : $structure['schedule_priority'],
                'adjustment_reason'       => $effResult['adjustment_reason'],
                'legal_reference'         => array_key_exists('legal_reference', $data) ? $data['legal_reference'] : $structure['legal_reference'],
                'notes'                   => array_key_exists('notes', $data) ? $data['notes'] : $structure['notes'],
                'revision_number'         => $newRevision,
                'updated_by'              => $userId,
            ];

            $updated = $db->table('curriculum_structures')
                ->where('id', $structure['id'])
                ->where('revision_number', $structure['revision_number'])
                ->update($updateData);
            $persistedRevision = $db->table('curriculum_structures')->select('revision_number')
                ->where('id', $structure['id'])->get()->getRowArray();
            if (!$updated || !$persistedRevision || (int) $persistedRevision['revision_number'] !== $newRevision) {
                throw new \RuntimeException('Struktur telah diperbarui oleh pengguna lain. Silakan muat ulang halaman.');
            }

            // History Log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $structure['curriculum_version_id'],
                'structure_id'          => $structure['id'],
                'revision_number'       => $newRevision,
                'action'                => 'UPDATE_STRUCTURE',
                'before_json'           => json_encode($structure),
                'after_json'            => json_encode($updateData),
                'change_reason'         => $data['change_reason'] ?? 'Update struktur mata pelajaran',
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'UPDATE_STRUCTURE', 'CurriculumStructure', $structure['id'], $structure, $updateData, "Update struktur ID {$structure['id']}");

            $db->transCommit();
            return $structureModel->find($structure['id']);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Soft delete structure record
     */
    public static function deleteStructure(string $uuid, ?string $reason = null): bool
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $versionModel   = new CurriculumVersionModel();
            $structureModel = new CurriculumStructureModel();

            $structure = $structureModel->where('uuid', $uuid)->first();
            if (!$structure) {
                throw new \RuntimeException('Struktur kurikulum tidak ditemukan.');
            }

            $version = $versionModel->find($structure['curriculum_version_id']);
            if (in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
                throw new \RuntimeException("Versi kurikulum berstatus {$version['workflow_status']} bersifat immutable.");
            }

            $userId = session()->get('user_id');

            // Soft delete via model
            $structureModel->delete($structure['id']);

            // History Log
            $revHistoryModel = new CurriculumRevisionHistoryModel();
            $revHistoryModel->insert([
                'curriculum_version_id' => $structure['curriculum_version_id'],
                'structure_id'          => $structure['id'],
                'revision_number'       => (int)$structure['revision_number'] + 1,
                'action'                => 'DELETE_STRUCTURE',
                'before_json'           => json_encode($structure),
                'after_json'            => null,
                'change_reason'         => $reason ?? 'Hapus struktur mata pelajaran',
                'actor_id'              => $userId,
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'DELETE_STRUCTURE', 'CurriculumStructure', $structure['id'], $structure, null, "Hapus struktur mapel ID {$structure['subject_id']}");

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Get Matrix view data (Subjects x Grade Levels grid) for a unit
     */
    public static function getMatrixView(int $versionId, int $unitId): array
    {
        $db = Database::connect();

        // 1. Get Grade Levels for Unit
        $grades = $db->table('grade_levels')
            ->where('unit_id', $unitId)
            ->where('is_active', 1)
            ->orderBy('grade_number', 'ASC')
            ->get()->getResultArray();

        // 2. Get All Active Subjects available for Unit
        $subjects = $db->table('subjects s')
            ->select('s.*, sua.is_available')
            ->join('subject_unit_availability sua', 'sua.subject_id = s.id')
            ->where('sua.unit_id', $unitId)
            ->where('sua.is_available', 1)
            ->where('s.is_active', 1)
            ->where('s.deleted_at IS NULL')
            ->orderBy('s.category', 'ASC')
            ->orderBy('s.code', 'ASC')
            ->get()->getResultArray();

        // 3. Get Existing Structures for this version & unit
        $structures = $db->table('curriculum_structures cs')
            ->select('cs.*, s.code as subject_code, s.name as subject_name')
            ->join('subjects s', 's.id = cs.subject_id', 'left')
            ->where('cs.curriculum_version_id', $versionId)
            ->where('cs.unit_id', $unitId)
            ->where('cs.status', 'ACTIVE')
            ->where('cs.deleted_at IS NULL')
            ->get()->getResultArray();

        // Build 2D lookup map: [subject_id][grade_level_id] => structure
        $matrixMap = [];
        $gradeTotals = [];
        foreach ($grades as $g) {
            $gradeTotals[(int)$g['id']] = 0.0;
        }
        $grandTotal = 0.0;

        foreach ($structures as $s) {
            $subId = (int)$s['subject_id'];
            $grdId = (int)$s['grade_level_id'];
            $hours = (float)$s['effective_weekly_hours'];

            $matrixMap[$subId][$grdId] = $s;
            if (isset($gradeTotals[$grdId])) {
                $gradeTotals[$grdId] += $hours;
            } else {
                $gradeTotals[$grdId] = $hours;
            }
            $grandTotal += $hours;
        }

        return [
            'grades'       => $grades,
            'subjects'     => $subjects,
            'matrix'       => $matrixMap,
            'grade_totals' => $gradeTotals,
            'grand_total'  => $grandTotal,
            'raw_count'    => count($structures),
        ];
    }

    /**
     * Clone all structures from a source version to target version for a unit
     */
    public static function cloneFromPreviousVersion(int $targetVersionId, int $unitId, int $sourceVersionId): int
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $sourceRows = $db->table('curriculum_structures')
                ->where('curriculum_version_id', $sourceVersionId)
                ->where('unit_id', $unitId)
                ->where('status', 'ACTIVE')
                ->where('deleted_at IS NULL')
                ->get()->getResultArray();

            if (empty($sourceRows)) {
                throw new \RuntimeException('Versi sumber tidak memiliki data struktur kurikulum untuk unit ini.');
            }

            $count = 0;
            foreach ($sourceRows as $row) {
                $data = [
                    'curriculum_version_id'   => $targetVersionId,
                    'unit_id'                 => $unitId,
                    'grade_level_id'          => $row['grade_level_id'],
                    'classroom_id'            => $row['classroom_id'],
                    'subject_id'              => $row['subject_id'],
                    'official_weekly_hours'   => $row['official_weekly_hours'],
                    'custom_weekly_hours'     => $row['custom_weekly_hours'],
                    'manual_weekly_hours'     => $row['manual_weekly_hours'],
                    'effective_source'        => $row['effective_source'],
                    'category'                => $row['category'],
                    'block_pattern_json'      => $row['block_pattern_json'],
                    'minimum_days'            => $row['minimum_days'],
                    'maximum_daily_hours'     => $row['maximum_daily_hours'],
                    'counts_in_report'        => $row['counts_in_report'],
                    'counts_as_teaching_load' => $row['counts_as_teaching_load'],
                    'required_room_type_id'   => $row['required_room_type_id'],
                    'schedule_priority'       => $row['schedule_priority'],
                    'adjustment_reason'       => $row['adjustment_reason'],
                    'notes'                   => $row['notes'],
                ];

                try {
                    self::createStructure($data);
                    $count++;
                } catch (\InvalidArgumentException $e) {
                    // Ignore duplicate key if already exists
                    continue;
                }
            }

            $db->transCommit();
            return $count;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Apply default unit preset (all active subjects x all grade levels)
     */
    public static function applyUnitPreset(int $versionId, int $unitId, float $defaultHours = 2.0): int
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $grades = $db->table('grade_levels')
                ->where('unit_id', $unitId)
                ->where('is_active', 1)
                ->where('deleted_at IS NULL')
                ->get()->getResultArray();

            $subjects = $db->table('subjects s')
                ->join('subject_unit_availability sua', 'sua.subject_id = s.id')
                ->where('sua.unit_id', $unitId)
                ->where('sua.is_available', 1)
                ->where('s.is_active', 1)
                ->where('s.deleted_at IS NULL')
                ->get()->getResultArray();

            if (empty($grades) || empty($subjects)) {
                throw new \RuntimeException('Data tingkat kelas atau mata pelajaran belum tersedia untuk unit ini.');
            }

            $count = 0;
            foreach ($subjects as $subject) {
                foreach ($grades as $grade) {
                    $data = [
                        'curriculum_version_id'   => $versionId,
                        'unit_id'                 => $unitId,
                        'grade_level_id'          => $grade['id'],
                        'classroom_id'            => null,
                        'subject_id'              => $subject['id'],
                        'official_weekly_hours'   => $defaultHours,
                        'effective_source'        => 'OFFICIAL',
                        'category'                => $subject['category'] ?? 'INTRAKURIKULER',
                        'counts_in_report'        => 1,
                        'counts_as_teaching_load' => 1,
                    ];

                    try {
                        self::createStructure($data);
                        $count++;
                    } catch (\InvalidArgumentException $e) {
                        // Skip if already exists
                        continue;
                    }
                }
            }

            $db->transCommit();
            return $count;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
