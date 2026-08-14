<?php

namespace App\Controllers;

use App\Models\ScheduleVersionModel;
use App\Services\ScheduleSetupService;
use App\Services\ScheduleWorkflowService;
use App\Services\UnitScopeService;
use App\Services\UuidService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class SchedulesController extends BaseController
{
    private ScheduleVersionModel $versionModel;
    private ScheduleWorkflowService $workflowService;

    public function __construct()
    {
        $this->versionModel    = new ScheduleVersionModel();
        $this->workflowService = new ScheduleWorkflowService();
    }

    public function index(): string
    {
        $unit = UnitScopeService::getCurrentUnit();
        $unitId = (int) ($unit['id'] ?? 0);

        $versionsBuilder = $this->versionModel
            ->select('schedule_versions.*, ap.name as period_name, cv.name as curriculum_name, av.name as assignment_name, su.name as unit_name')
            ->join('academic_periods ap', 'ap.id = schedule_versions.academic_period_id', 'left')
            ->join('curriculum_versions cv', 'cv.id = schedule_versions.curriculum_version_id', 'left')
            ->join('assignment_versions av', 'av.id = schedule_versions.assignment_version_id', 'left')
            ->join('school_units su', 'su.id = schedule_versions.unit_id', 'left')
            ->orderBy('schedule_versions.id', 'DESC');

        if ($unitId > 0) {
            $versionsBuilder->groupStart()
                ->where('schedule_versions.unit_id', $unitId)
                ->orWhere('schedule_versions.unit_id IS NULL')
            ->groupEnd();
        }
        $versions = $versionsBuilder->findAll();

        $db = Database::connect();
        $periods = $db->table('academic_periods ap')
            ->select('ap.*, ay.name AS year_name')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->orderBy('ap.is_active', 'DESC')->orderBy('ap.start_date', 'DESC')
            ->get()->getResultArray();

        $curricula = $db->table('curriculum_versions cv')
            ->select('cv.*')->distinct()
            ->join('curriculum_structures cs', 'cs.curriculum_version_id = cv.id', 'left')
            ->orderBy('cv.is_active', 'DESC')->orderBy('cv.id', 'DESC')
            ->get()->getResultArray();

        $assignments = $db->table('assignment_versions av')
            ->select('av.*')->distinct()
            ->orderBy('av.is_active', 'DESC')->orderBy('av.id', 'DESC')
            ->get()->getResultArray();

        return view('schedules/index', [
            'title'    => 'Jadwal Pelajaran',
            'unit'     => $unit,
            'versions' => $versions,
            'periods'  => $periods,
            'curricula'=> $curricula,
            'assignments' => $assignments,
        ]);
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'academic_period_id'    => 'required|integer',
            'code'                  => 'required|max_length[50]',
            'name'                  => 'required|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            if (! $this->request->isAJAX()) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(400);
        }

        $userId = (int)session()->get('user_id');
        $scopeType = (string)$this->request->getPost('scope_type');
        $periodId = (int) $this->request->getPost('academic_period_id');
        $db = Database::connect();

        if ($scopeType === 'COMBINED' || $this->request->getPost('is_combined') == 1) {
            $unitId = null;
            $curriculumId = null;
            $assignmentId = null;
        } else {
            $unit = UnitScopeService::getCurrentUnit();
            $unitId = (int) ($unit['id'] ?? 0);
            if ($unitId <= 0) {
                return $this->creationError('Pilih unit sekolah aktif terlebih dahulu.');
            }
            $curriculumId = (int) $this->request->getPost('curriculum_version_id');
            $assignmentId = (int) $this->request->getPost('assignment_version_id');

            if ($assignmentId > 0 && $curriculumId > 0) {
                $assignment = $db->table('assignment_versions')->where('id', $assignmentId)->get()->getRowArray();
                if (!$assignment || (int)$assignment['academic_period_id'] !== $periodId) {
                    return $this->creationError('Periode dan versi penugasan harus cocok.');
                }
            }
        }

        $data = [
            'uuid'                  => UuidService::v4(),
            'academic_period_id'    => $periodId,
            'unit_id'               => $unitId,
            'curriculum_version_id' => $curriculumId,
            'assignment_version_id' => $assignmentId,
            'code'                  => (string)$this->request->getPost('code'),
            'name'                  => (string)$this->request->getPost('name'),
            'description'           => $this->request->getPost('description'),
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ];

        try {
            $db->transException(true)->transStart();
            $this->versionModel->insert($data);
            $id = (int)$this->versionModel->insertID();
            $setup = (new ScheduleSetupService())->initialize($id, $unitId ?: 0);
            $db->transComplete();
        } catch (\Throwable $e) {
            return $this->creationError($e->getMessage());
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => ['id' => $id, 'setup' => $setup],
            ]);
        }

        return redirect()->to('/schedules/' . $id . '/editor')
            ->with('success', 'Versi jadwal siap. Hari, slot, dan kebutuhan mengajar dibuat otomatis.');
    }

    public function transition(int $id): ResponseInterface
    {
        $targetState     = (string)$this->request->getPost('target_state');
        $revisionNumber  = (int)$this->request->getPost('revision_number');
        $summary         = $this->request->getPost('change_summary');
        $userId          = (int)session()->get('user_id');

        try {
            $version = $this->versionModel->find($id);
            if (! $version) {
                throw new \RuntimeException('Versi jadwal tidak ditemukan.');
            }
            if (! empty($version['unit_id'])) {
                UnitScopeService::assertUnit((int) $version['unit_id']);
            } elseif (UnitScopeService::accessibleUnitIds() === []) {
                throw new \RuntimeException('Anda tidak memiliki akses ke jadwal gabungan.');
            }
            $result = $this->workflowService->transitionState($id, $targetState, $userId, $revisionNumber, $summary);
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            $code = $e->getCode() === 409 ? 409 : 400;
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode($code);
        }
    }

    public function update(int $id): ResponseInterface
    {
        $version = $this->versionModel->find($id);
        if (!$version) {
            return $this->creationError('Versi jadwal tidak ditemukan.');
        }
        if (! empty($version['unit_id'])) {
            UnitScopeService::assertUnit((int) $version['unit_id']);
        } elseif (UnitScopeService::accessibleUnitIds() === []) {
            return $this->creationError('Anda tidak memiliki akses ke jadwal gabungan.');
        }
        if ((string) $version['workflow_status'] !== 'DRAFT') {
            return $this->creationError('Hanya versi jadwal DRAFT yang dapat diperbarui.');
        }

        $rules = [
            'academic_period_id' => 'required|integer',
            'code'               => 'required|max_length[50]',
            'name'               => 'required|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            if (!$this->request->isAJAX()) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(400);
        }

        $userId = (int)session()->get('user_id');
        $scopeType = (string)$this->request->getPost('scope_type');
        $periodId = (int) $this->request->getPost('academic_period_id');
        $currentRevision = filter_var($this->request->getPost('revision_number'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $db = Database::connect();

        if ($currentRevision === false || $currentRevision === null) {
            return $this->creationError('Revisi jadwal wajib disertakan. Muat ulang halaman lalu coba lagi.');
        }

        $requestedCombined = $scopeType === 'COMBINED' || $this->request->getPost('is_combined') == 1;
        if ($requestedCombined !== empty($version['unit_id'])) {
            // empty(unit_id) means the stored version is combined.
            return $this->creationError('Lingkup unit/gabungan tidak dapat diubah setelah versi dibuat. Buat versi baru.');
        }
        if ($periodId !== (int) $version['academic_period_id']) {
            return $this->creationError('Periode akademik tidak dapat diubah setelah versi dibuat. Buat versi baru.');
        }

        if ($requestedCombined) {
            $unitId = null;
            $curriculumId = null;
            $assignmentId = null;
        } else {
            $unitId = (int) $version['unit_id'];
            $curriculumId = (int) ($version['curriculum_version_id'] ?? 0) ?: null;
            $assignmentId = (int) ($version['assignment_version_id'] ?? 0) ?: null;
            $requestedCurriculum = (int) $this->request->getPost('curriculum_version_id') ?: null;
            $requestedAssignment = (int) $this->request->getPost('assignment_version_id') ?: null;
            if ($requestedCurriculum !== $curriculumId || $requestedAssignment !== $assignmentId) {
                return $this->creationError('Versi kurikulum/pembagian tugas tidak dapat diubah setelah versi jadwal dibuat. Buat versi baru.');
            }
        }

        $data = [
            'academic_period_id'    => $periodId,
            'unit_id'               => $unitId,
            'curriculum_version_id' => $curriculumId,
            'assignment_version_id' => $assignmentId,
            'code'                  => (string)$this->request->getPost('code'),
            'name'                  => (string)$this->request->getPost('name'),
            'description'           => $this->request->getPost('description'),
            'revision_number'       => (int) $currentRevision + 1,
            'change_summary'        => 'Metadata versi jadwal diperbarui',
            'updated_by'            => $userId,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        try {
            $db->transException(true)->transBegin();
            $db->table('schedule_versions')->where('id', $id)
                ->where('revision_number', (int) $currentRevision)
                ->where('workflow_status', 'DRAFT')->update($data);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('Versi jadwal telah diubah oleh proses lain.', 409);
            }
            $db->table('schedule_revision_history')->insert([
                'uuid' => UuidService::v4(),
                'schedule_version_id' => $id,
                'revision_number' => (int) $currentRevision + 1,
                'action' => 'UPDATE_VERSION_METADATA',
                'changes_json' => json_encode([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                ], JSON_THROW_ON_ERROR),
                'performed_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->creationError($e->getMessage());
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Versi jadwal berhasil diperbarui.',
            ]);
        }

        return redirect()->to('/schedules')
            ->with('success', 'Versi jadwal berhasil diperbarui.');
    }

    private function creationError(string $message): ResponseInterface
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => $message,
            ]);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
