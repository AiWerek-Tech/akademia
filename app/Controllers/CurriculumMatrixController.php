<?php

namespace App\Controllers;

use App\Services\CurriculumVersionService;
use App\Services\CurriculumStructureService;
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

        $matrixData = CurriculumStructureService::getMatrixView($version['id'], $unitId);
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

            if ($weeklyHours <= 0) {
                // If hours set to 0 and structure exists, delete structure
                if (!empty($structureUuid)) {
                    CurriculumStructureService::deleteStructure($structureUuid, 'Hapus via Matrix Editor (Jam = 0)');
                    return $this->response->setJSON([
                        'status'       => 'success',
                        'action'       => 'deleted',
                        'message'      => 'Mata pelajaran dihapus dari tingkat ini.',
                        'weekly_hours' => 0,
                    ]);
                }
                return $this->response->setJSON([
                    'status'       => 'success',
                    'action'       => 'ignored',
                    'message'      => 'Nilai 0 tidak disimpan.',
                    'weekly_hours' => 0,
                ]);
            }

            if (!empty($structureUuid)) {
                // Update existing structure
                $updateData = [
                    'effective_source' => $effectiveSource,
                    $effectiveSource === 'CUSTOM' ? 'custom_weekly_hours' : 'official_weekly_hours' => $weeklyHours,
                ];
                if ($effectiveSource === 'CUSTOM') {
                    $updateData['adjustment_reason'] = 'Penyesuaian JP custom sekolah melalui editor matriks';
                }
                $updated = CurriculumStructureService::updateStructure($structureUuid, $updateData);
                return $this->response->setJSON([
                    'status'         => 'success',
                    'action'         => 'updated',
                    'structure'      => $updated,
                    'weekly_hours'   => (float)$updated['effective_weekly_hours'],
                    'effective_source' => $updated['effective_source'],
                    'message'        => 'Jam pelajaran berhasil diperbarui.',
                ]);
            } else {
                // Create new structure
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
                $created = CurriculumStructureService::createStructure($createData);
                return $this->response->setJSON([
                    'status'         => 'success',
                    'action'         => 'created',
                    'structure'      => $created,
                    'weekly_hours'   => (float)$created['effective_weekly_hours'],
                    'effective_source' => $created['effective_source'],
                    'message'        => 'Mata pelajaran berhasil ditambahkan ke tingkat ini.',
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
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
}
