<?php

namespace App\Controllers;

use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumCapacityReconciliationService;
use App\Services\CurriculumValidationService;
use App\Services\UnitScopeService;
use Config\Database;

class CurriculumMatrixController extends BaseController
{
    /**
     * Display Interactive Curriculum Matrix Grid
     */
    public function index(string $uuid)
    {
        if (!has_permission('curriculum.view')) {
            return redirect()->to('/dashboard')->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        try {
            $unitId = UnitScopeService::resolveUnit($this->request->getGet('unit_id'));
        } catch (\Throwable $e) {
            return redirect()->to('/curriculum')->with('error', $e->getMessage());
        }

        // Auto-switch to unit's corresponding curriculum version if user selected a different unit in matrix view
        $selectedUnit = Database::connect()->table('school_units')->where('id', $unitId)->get()->getRowArray();
        if ($selectedUnit) {
            $unitCode = strtoupper((string)$selectedUnit['code']);
            $versionText = strtoupper((string)$version['code'] . ' ' . (string)$version['name']);

            if (
                ($unitCode === 'SMA' && str_contains($versionText, 'SMP') && !str_contains($versionText, 'SMA')) ||
                ($unitCode === 'SMP' && str_contains($versionText, 'SMA') && !str_contains($versionText, 'SMP'))
            ) {
                $targetVersion = Database::connect()->table('curriculum_versions')
                    ->where('academic_period_id', $version['academic_period_id'])
                    ->where('id !=', $version['id'])
                    ->groupStart()
                        ->like('code', $unitCode, 'both')
                        ->orLike('name', $unitCode, 'both')
                    ->groupEnd()
                    ->get()->getRowArray();

                if ($targetVersion && !empty($targetVersion['uuid'])) {
                    return redirect()->to('/curriculum/' . $targetVersion['uuid'] . '/matrix?unit_id=' . $unitId);
                }
            }
        }

        $matrixData = CurriculumStructureService::getMatrixView($version['id'], $unitId);
        $trimPreview = CurriculumCapacityReconciliationService::buildTrimPreview($version['id'], $unitId);
        $validation = CurriculumValidationService::validateVersion($version['id']);

        // Previous curriculum versions for cloning
        $previousVersions = Database::connect()->table('curriculum_versions')
            ->where('id !=', $version['id'])
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();

        $units = UnitScopeService::accessibleUnits();

        return view('curriculum/matrix/index', [
            'version'          => $version,
            'matrix'           => $matrixData,
            'trim_preview'     => $trimPreview,
            'validation'       => $validation,
            'units'            => $units,
            'selected_unit_id' => $unitId,
            'previous_versions'=> $previousVersions,
        ]);
    }

    /**
     * AJAX Endpoint: Update or Insert single cell in Matrix (Subject x Grade)
     */
    public function updateCell(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Hak akses ditolak.',
            ])->setStatusCode(403);
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Versi kurikulum tidak ditemukan.',
            ])->setStatusCode(404);
        }

        $json = $this->request->getJSON(true);
        $unitId       = (int)($json['unit_id'] ?? 0);
        $subjectId    = (int)($json['subject_id'] ?? 0);
        $gradeLevelId = (int)($json['grade_level_id'] ?? 0);
        $weeklyHours  = (float)($json['weekly_hours'] ?? 0);
        $effectiveSource = strtoupper((string)($json['effective_source'] ?? 'OFFICIAL'));
        $structureUuid = $json['structure_uuid'] ?? null;

        if (!$unitId || !$subjectId || !$gradeLevelId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Parameter unit, mapel, dan tingkat kelas wajib diisi.',
            ])->setStatusCode(400);
        }
        if (!in_array($effectiveSource, ['OFFICIAL', 'CUSTOM'], true)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Mode jam harus berupa jam resmi atau JP custom sekolah.',
            ])->setStatusCode(400);
        }

        try {
            UnitScopeService::assertUnit($unitId);

            $action = 'ignored';
            $weeklyHoursRes = 0.0;
            $effectiveSourceRes = $effectiveSource;
            $structureRes = null;
            $messageRes = '';

            if ($weeklyHours <= 0) {
                if (!empty($structureUuid)) {
                    CurriculumStructureService::deleteStructure($structureUuid, 'Hapus via Matrix Editor (Jam = 0)');
                    $action = 'deleted';
                    $messageRes = 'Mata pelajaran dihapus dari tingkat ini.';
                } else {
                    $action = 'ignored';
                    $messageRes = 'Nilai 0 tidak disimpan.';
                }
            } else if (!empty($structureUuid)) {
                $updateData = [
                    'effective_source' => $effectiveSource,
                    $effectiveSource === 'CUSTOM' ? 'custom_weekly_hours' : 'official_weekly_hours' => $weeklyHours,
                ];
                if ($effectiveSource === 'CUSTOM') {
                    $updateData['official_weekly_hours'] = null;
                    $updateData['adjustment_reason'] = 'Penyesuaian JP custom sekolah melalui editor matriks';
                } else {
                    $updateData['custom_weekly_hours'] = null;
                    $updateData['manual_weekly_hours'] = null;
                    $updateData['adjustment_reason'] = null;
                }
                $structureRes = CurriculumStructureService::updateStructure($structureUuid, $updateData);
                $action = 'updated';
                $weeklyHoursRes = (float)$structureRes['effective_weekly_hours'];
                $effectiveSourceRes = $structureRes['effective_source'];
                $messageRes = 'Jam pelajaran berhasil diperbarui.';
            } else {
                $createData = [
                    'curriculum_version_id' => $version['id'],
                    'unit_id'               => $unitId,
                    'grade_level_id'        => $gradeLevelId,
                    'subject_id'            => $subjectId,
                    'effective_source'      => $effectiveSource,
                    'counts_in_report'      => 1,
                    'counts_as_teaching_load' => 1,
                ];
                $createData[$effectiveSource === 'CUSTOM' ? 'custom_weekly_hours' : 'official_weekly_hours'] = $weeklyHours;
                if ($effectiveSource === 'CUSTOM') {
                    $createData['adjustment_reason'] = 'Penyesuaian JP custom sekolah melalui editor matriks';
                }
                $structureRes = CurriculumStructureService::createStructure($createData);
                $action = 'created';
                $weeklyHoursRes = (float)$structureRes['effective_weekly_hours'];
                $effectiveSourceRes = $structureRes['effective_source'];
                $messageRes = 'Mata pelajaran berhasil ditambahkan ke tingkat ini.';
            }

            // Fetch fresh server matrix totals to ensure 100% precision
            $matrixData = CurriculumStructureService::getMatrixView($version['id'], $unitId);

            return $this->response->setJSON([
                'status'           => 'success',
                'action'           => $action,
                'structure'        => $structureRes,
                'weekly_hours'     => $weeklyHoursRes,
                'effective_source' => $effectiveSourceRes,
                'message'          => $messageRes,
                'matrix_totals'    => [
                    'grade_totals'       => $matrixData['grade_totals'],
                    'official_totals'    => $matrixData['official_totals'],
                    'custom_totals'      => $matrixData['custom_totals'],
                    'breakdown_by_grade' => $matrixData['breakdown_by_grade'],
                    'grand_total'        => $matrixData['grand_total'],
                ],
                'csrf_hash'        => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(400);
        }
    }

    /**
     * Bulk Store: Add multiple subjects to multiple grade levels in 1 click
     */
    public function bulkStore(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        $unitId = (int)$this->request->getPost('unit_id');
        $subjectIds = $this->request->getPost('subject_ids');
        $gradeLevelIds = $this->request->getPost('grade_level_ids');
        $weeklyHours = (float)$this->request->getPost('weekly_hours');

        if (empty($subjectIds) || empty($gradeLevelIds) || $weeklyHours <= 0) {
            return redirect()->back()->with('error', 'Pilih minimal 1 mata pelajaran, 1 tingkat kelas, dan masukkan jam valid.');
        }

        try {
            UnitScopeService::assertUnit($unitId);
            $successCount = 0;

            foreach ($subjectIds as $subId) {
                foreach ($gradeLevelIds as $grdId) {
                    try {
                        CurriculumStructureService::createStructure([
                            'curriculum_version_id' => $version['id'],
                            'unit_id'               => $unitId,
                            'grade_level_id'        => (int)$grdId,
                            'subject_id'            => (int)$subId,
                            'official_weekly_hours' => $weeklyHours,
                            'effective_source'      => 'OFFICIAL',
                            'counts_in_report'      => 1,
                            'counts_as_teaching_load' => 1,
                        ]);
                        $successCount++;
                    } catch (\InvalidArgumentException $e) {
                        // Skip if already exists
                        continue;
                    }
                }
            }

            return redirect()->to('/curriculum/' . $uuid . '/matrix?unit_id=' . $unitId)
                ->with('success', "Berhasil menambahkan {$successCount} kombinasi struktur mata pelajaran.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Clone structure from previous version
     */
    public function cloneFromPrevious(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        $unitId = (int)$this->request->getPost('unit_id');
        $sourceVersionId = (int)$this->request->getPost('source_version_id');

        try {
            UnitScopeService::assertUnit($unitId);
            $copiedCount = CurriculumStructureService::cloneFromPreviousVersion($version['id'], $unitId, $sourceVersionId);

            return redirect()->to('/curriculum/' . $uuid . '/matrix?unit_id=' . $unitId)
                ->with('success', "Berhasil menyalin {$copiedCount} struktur dari kurikulum sebelumnya.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Apply default unit preset (all subjects x all grade levels)
     */
    public function applyPreset(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        $unitId = (int)$this->request->getPost('unit_id');
        $defaultHours = (float)($this->request->getPost('default_hours') ?: 2.0);

        try {
            UnitScopeService::assertUnit($unitId);
            $addedCount = CurriculumStructureService::applyUnitPreset($version['id'], $unitId, $defaultHours);

            return redirect()->to('/curriculum/' . $uuid . '/matrix?unit_id=' . $unitId)
                ->with('success', "Berhasil menerapkan preset unit sekolah ({$addedCount} struktur dibuat).");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Auto Trim: Adjust specific subject hours to match exact max capacity
     */
    public function autoTrim(string $uuid)
    {
        if (!has_permission('curriculum.manage')) {
            return redirect()->back()->with('error', 'Hak akses ditolak.');
        }

        $version = CurriculumVersionService::getVersionByUuid($uuid);
        if (!$version) {
            return redirect()->to('/curriculum')->with('error', 'Versi kurikulum tidak ditemukan.');
        }

        $unitId       = (int)$this->request->getPost('unit_id');
        $gradeLevelId = (int)$this->request->getPost('grade_level_id');
        $adjustments  = $this->request->getPost('adjustments'); // array of subject_id => new_hours

        try {
            UnitScopeService::assertUnit($unitId);
            if (empty($adjustments) || !is_array($adjustments)) {
                throw new \InvalidArgumentException('Tidak ada penyesuaian jam yang dipilih.');
            }

            $db = Database::connect();
            foreach ($adjustments as $subId => $newHours) {
                $subId = (int)$subId;
                $newHours = (float)$newHours;
                if ($subId <= 0) continue;

                $existing = $db->table('curriculum_structures')
                    ->where('curriculum_version_id', $version['id'])
                    ->where('unit_id', $unitId)
                    ->where('grade_level_id', $gradeLevelId)
                    ->where('subject_id', $subId)
                    ->where('status', 'ACTIVE')
                    ->where('deleted_at IS NULL')
                    ->get()->getRowArray();

                if ($existing) {
                    if (strtoupper((string)$existing['effective_source']) !== 'OFFICIAL') {
                        throw new \InvalidArgumentException('Asisten hanya boleh memotong jam resmi pemerintah. Baris custom sekolah tidak diubah.');
                    }
                    $currentHours = (float) $existing['effective_weekly_hours'];
                    if ($newHours < 0 || $newHours > $currentHours) {
                        throw new \InvalidArgumentException('Nilai penyesuaian tidak valid: jam baru tidak boleh negatif atau lebih besar dari jam saat ini.');
                    }
                    if ($newHours <= 0) {
                        CurriculumStructureService::deleteStructure($existing['uuid'], 'Pemotongan otomatis via Asisten Rekonsiliasi');
                    } else {
                        $updateData = [
                            'effective_source' => 'OFFICIAL',
                            'official_weekly_hours' => $newHours,
                            'custom_weekly_hours' => null,
                            'manual_weekly_hours' => null,
                            'adjustment_reason' => 'Penyesuaian jam resmi via Asisten Rekonsiliasi',
                        ];
                        CurriculumStructureService::updateStructure($existing['uuid'], $updateData);
                    }
                }
            }

            return redirect()->to('/curriculum/' . $uuid . '/matrix?unit_id=' . $unitId)
                ->with('success', 'Berhasil menerapkan penyesuaian jam. Kapasitas kini telah seimbang.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
