<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoutineActivityModel;
use App\Models\SchoolUnitModel;
use App\Services\UnitScopeService;
use App\Services\UuidService;

class RoutineActivitiesController extends BaseController
{
    public function index()
    {
        if (!has_permission('curriculum.view')) {
            return redirect()->to('/dashboard')->with('error', 'Hak akses ditolak.');
        }

        $routineModel = new RoutineActivityModel();

        // Resolve unit safely — allow null for "all units" view
        $selectedUnitId = $this->request->getGet('unit_id');
        $unitId = null;
        try {
            if ($selectedUnitId || ! is_super_admin()) {
                $unitId = UnitScopeService::resolveUnit($selectedUnitId ? (int) $selectedUnitId : null);
            }
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', $e->getMessage());
        }

        if ($unitId) {
            $routineModel->groupStart()
                         ->where('unit_id', $unitId)
                         ->orWhere('unit_id', null)
                         ->groupEnd();
        }

        $activities = $routineModel->where('is_active', 1)->orderBy('id', 'ASC')->findAll();

        // Use UnitScopeService for units list scoped to user
        $units = UnitScopeService::accessibleUnits();

        // Direct assignment choices must follow the selected unit scope.
        $teacherBuilder = \Config\Database::connect()->table('teachers t')
            ->distinct()->select('t.*')->where('t.is_active', 1)->where('t.deleted_at IS NULL');
        if ($unitId !== null) {
            $teacherBuilder->join('teacher_unit_assignments tua', 'tua.teacher_id = t.id')
                ->where('tua.unit_id', $unitId)->where('tua.status', 'ACTIVE');
        }
        $teachers = $teacherBuilder->orderBy('t.full_name', 'ASC')->get()->getResultArray();

        return view('routine_activities/index', [
            'pageTitle'  => 'Kegiatan Rutin Sekolah',
            'activities' => $activities,
            'units'      => $units,
            'teachers'   => $teachers,
            'unit_id'    => $unitId,
        ]);
    }

    public function store()
    {
        if (!has_permission('curriculum.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hak akses ditolak.']);
        }

        $rules = [
            'name'                => 'required|min_length[3]|max_length[150]',
            'code'                => 'required|min_length[2]|max_length[50]',
            'default_duration_jp' => 'required|numeric|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $this->validator->getErrors()]);
        }

        try {
            $unitId = $this->resolveWritableUnit($this->request->getPost('unit_id'));
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $routineModel = new RoutineActivityModel();

        // Check for duplicate code
        $existingCode = $routineModel->where('code', strtoupper(trim($this->request->getPost('code'))))->first();
        if ($existingCode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode kegiatan "' . strtoupper(trim($this->request->getPost('code'))) . '" sudah digunakan.']);
        }

        $durationJp        = (float)$this->request->getPost('default_duration_jp');
        $durationMode      = $this->request->getPost('duration_mode') ?: 'STANDARD_JP';
        $durationMinutes   = $this->request->getPost('duration_minutes') ? (int)$this->request->getPost('duration_minutes') : null;
        $strategy          = $this->request->getPost('assignment_strategy') ?: 'NONE';
        $specificTeacher   = $this->request->getPost('specific_teacher_id') ? (int)$this->request->getPost('specific_teacher_id') : null;
        if ($specificTeacher !== null) {
            try {
                $unitId !== null
                    ? UnitScopeService::assertTeacherInUnit($specificTeacher, $unitId)
                    : UnitScopeService::assertTeacher($specificTeacher);
            } catch (\Throwable $e) {
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        $lockedStart       = $this->request->getPost('locked_period_start') ? (int)$this->request->getPost('locked_period_start') : null;
        $lockedEndInput    = $this->request->getPost('locked_period_end') ? (int)$this->request->getPost('locked_period_end') : null;
        $placementZone     = $this->request->getPost('placement_zone') ?: 'ACADEMIC_JP';
        $placementSequence = $this->request->getPost('placement_sequence') ? (int)$this->request->getPost('placement_sequence') : 1;

        $lockedEnd = $lockedStart;
        if ($lockedStart !== null) {
            if ($lockedEndInput !== null && $lockedEndInput >= $lockedStart) {
                $lockedEnd = $lockedEndInput;
            } elseif ($durationJp > 1.0) {
                $lockedEnd = $lockedStart + (int)ceil($durationJp) - 1;
            }
        }

        $routineModel->insert([
            'unit_id'                 => $unitId,
            'code'                    => strtoupper(trim($this->request->getPost('code'))),
            'name'                    => trim($this->request->getPost('name')),
            'short_name'              => trim($this->request->getPost('short_name')) ?: null,
            'activity_type'           => $this->request->getPost('activity_type') ?: 'FIXED_ROUTINE',
            'default_duration_jp'     => $durationJp,
            'duration_mode'           => $durationMode,
            'duration_minutes'        => $durationMinutes,
            'color_label'             => $this->request->getPost('color_label') ?: '#6c757d',
            'is_locked_slot'          => $this->request->getPost('is_locked_slot') ? 1 : 0,
            'counts_as_teaching_load' => $this->request->getPost('counts_as_teaching_load') ? 1 : 0,
            'assignment_role_default' => trim((string)$this->request->getPost('assignment_role_default')) ?: null,
            'assignment_strategy'     => $strategy,
            'specific_teacher_id'     => $specificTeacher,
            'default_day'             => $this->request->getPost('default_day') ?: null,
            'default_period_number'   => $lockedStart,
            'locked_period_start'     => $lockedStart,
            'locked_period_end'       => $lockedEnd,
            'placement_zone'          => $placementZone,
            'placement_sequence'      => $placementSequence,
            'notes'                   => trim((string)$this->request->getPost('notes')) ?: null,
            'is_active'               => 1,
            'created_by'              => session()->get('user_id'),
        ]);

        $this->syncSchedules();

        return $this->response->setJSON([
            'status'    => 'success',
            'message'   => 'Kegiatan rutin "' . trim($this->request->getPost('name')) . '" berhasil ditambahkan.',
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function update(int $id)
    {
        if (!has_permission('curriculum.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hak akses ditolak.']);
        }

        $routineModel = new RoutineActivityModel();
        $activity = $routineModel->find($id);
        if (!$activity) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kegiatan rutin tidak ditemukan.']);
        }

        try {
            if (! empty($activity['unit_id'])) {
                UnitScopeService::assertUnit((int)$activity['unit_id']);
            } elseif (! is_super_admin()) {
                throw new \RuntimeException('Kegiatan global hanya dapat diubah oleh super administrator.');
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $rules = [
            'name'                => 'required|min_length[3]|max_length[150]',
            'code'                => 'required|min_length[2]|max_length[50]',
            'default_duration_jp' => 'required|numeric|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $this->validator->getErrors()]);
        }

        try {
            $unitId = $this->resolveWritableUnit($this->request->getPost('unit_id'));
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }

        // Check duplicate code (excluding current record)
        $existingCode = $routineModel->where('code', strtoupper(trim($this->request->getPost('code'))))
                                     ->where('id !=', $id)
                                     ->first();
        if ($existingCode) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kode kegiatan "' . strtoupper(trim($this->request->getPost('code'))) . '" sudah digunakan oleh kegiatan lain.']);
        }

        $durationJp        = (float)$this->request->getPost('default_duration_jp');
        $durationMode      = $this->request->getPost('duration_mode') ?: 'STANDARD_JP';
        $durationMinutes   = $this->request->getPost('duration_minutes') ? (int)$this->request->getPost('duration_minutes') : null;
        $strategy          = $this->request->getPost('assignment_strategy') ?: 'NONE';
        $specificTeacher   = $this->request->getPost('specific_teacher_id') ? (int)$this->request->getPost('specific_teacher_id') : null;
        if ($specificTeacher !== null) {
            try {
                $unitId !== null
                    ? UnitScopeService::assertTeacherInUnit($specificTeacher, $unitId)
                    : UnitScopeService::assertTeacher($specificTeacher);
            } catch (\Throwable $e) {
                return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        $lockedStart       = $this->request->getPost('locked_period_start') ? (int)$this->request->getPost('locked_period_start') : null;
        $lockedEndInput    = $this->request->getPost('locked_period_end') ? (int)$this->request->getPost('locked_period_end') : null;
        $placementZone     = $this->request->getPost('placement_zone') ?: 'ACADEMIC_JP';
        $placementSequence = $this->request->getPost('placement_sequence') ? (int)$this->request->getPost('placement_sequence') : 1;

        $lockedEnd = $lockedStart;
        if ($lockedStart !== null) {
            if ($lockedEndInput !== null && $lockedEndInput >= $lockedStart) {
                $lockedEnd = $lockedEndInput;
            } elseif ($durationJp > 1.0) {
                $lockedEnd = $lockedStart + (int)ceil($durationJp) - 1;
            }
        }

        $routineModel->update($id, [
            'unit_id'                 => $unitId,
            'code'                    => strtoupper(trim($this->request->getPost('code'))),
            'name'                    => trim($this->request->getPost('name')),
            'short_name'              => trim($this->request->getPost('short_name')) ?: null,
            'activity_type'           => $this->request->getPost('activity_type') ?: 'FIXED_ROUTINE',
            'default_duration_jp'     => $durationJp,
            'duration_mode'           => $durationMode,
            'duration_minutes'        => $durationMinutes,
            'color_label'             => $this->request->getPost('color_label') ?: '#6c757d',
            'is_locked_slot'          => $this->request->getPost('is_locked_slot') ? 1 : 0,
            'counts_as_teaching_load' => $this->request->getPost('counts_as_teaching_load') ? 1 : 0,
            'assignment_role_default' => trim((string)$this->request->getPost('assignment_role_default')) ?: null,
            'assignment_strategy'     => $strategy,
            'specific_teacher_id'     => $specificTeacher,
            'default_day'             => $this->request->getPost('default_day') ?: null,
            'default_period_number'   => $lockedStart,
            'locked_period_start'     => $lockedStart,
            'locked_period_end'       => $lockedEnd,
            'placement_zone'          => $placementZone,
            'placement_sequence'      => $placementSequence,
            'notes'                   => trim((string)$this->request->getPost('notes')) ?: null,
            'updated_by'              => session()->get('user_id'),
        ]);

        $this->syncSchedules();

        return $this->response->setJSON([
            'status'    => 'success',
            'message'   => 'Kegiatan rutin "' . trim($this->request->getPost('name')) . '" berhasil diperbarui.',
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function delete(int $id)
    {
        if (!has_permission('curriculum.manage')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hak akses ditolak.', 'csrf_hash' => csrf_hash()]);
        }

        $routineModel = new RoutineActivityModel();
        $activity = $routineModel->find($id);
        if (!$activity) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kegiatan rutin tidak ditemukan.', 'csrf_hash' => csrf_hash()]);
        }

        try {
            if (! empty($activity['unit_id'])) {
                UnitScopeService::assertUnit((int)$activity['unit_id']);
            } elseif (! is_super_admin()) {
                throw new \RuntimeException('Kegiatan global hanya dapat dihapus oleh super administrator.');
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage(), 'csrf_hash' => csrf_hash()]);
        }

        $activityName = $activity['name'];
        $routineModel->delete($id);

        $this->syncSchedules();

        return $this->response->setJSON([
            'status'    => 'success',
            'message'   => 'Kegiatan rutin "' . $activityName . '" berhasil dihapus.',
            'csrf_hash' => csrf_hash(),
        ]);
    }

    private function syncSchedules(): void
    {
        try {
            $db = Database::connect();
            $versions = $db->table('schedule_versions')->get()->getResultArray();
            $syncService = new \App\Services\RoutineActivityScheduleSyncService();
            $setupService = new \App\Services\ScheduleSetupService();
            foreach ($versions as $v) {
                $setupService->initialize((int)$v['id'], (int)($v['unit_id'] ?? 1));
                $syncService->syncForVersion((int)$v['id']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'RoutineActivity schedule sync error: ' . $e->getMessage());
        }
    }

    private function resolveWritableUnit($requestedUnitId): ?int
    {
        if ($requestedUnitId) {
            return UnitScopeService::resolveUnit((int) $requestedUnitId);
        }
        if (is_super_admin()) {
            return null;
        }

        return UnitScopeService::resolveUnit();
    }
}
