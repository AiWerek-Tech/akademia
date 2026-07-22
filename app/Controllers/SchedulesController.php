<?php

namespace App\Controllers;

use App\Models\ScheduleVersionModel;
use App\Services\ScheduleWorkflowService;
use App\Services\UnitScopeService;
use CodeIgniter\HTTP\ResponseInterface;

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

        $versions = $this->versionModel
            ->select('schedule_versions.*, ap.name as period_name, cv.name as curriculum_name, av.name as assignment_name')
            ->join('academic_periods ap', 'ap.id = schedule_versions.academic_period_id')
            ->join('curriculum_versions cv', 'cv.id = schedule_versions.curriculum_version_id')
            ->join('assignment_versions av', 'av.id = schedule_versions.assignment_version_id')
            ->orderBy('schedule_versions.id', 'DESC')
            ->findAll();

        return view('schedules/index', [
            'title'    => 'Versi Jadwal Pelajaran',
            'unit'     => $unit,
            'versions' => $versions,
        ]);
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'academic_period_id'    => 'required|integer',
            'curriculum_version_id' => 'required|integer',
            'assignment_version_id' => 'required|integer',
            'code'                  => 'required|max_length[50]',
            'name'                  => 'required|max_length[150]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(400);
        }

        $userId = (int)session()->get('user_id');

        $data = [
            'uuid'                  => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'academic_period_id'    => (int)$this->request->getPost('academic_period_id'),
            'curriculum_version_id' => (int)$this->request->getPost('curriculum_version_id'),
            'assignment_version_id' => (int)$this->request->getPost('assignment_version_id'),
            'code'                  => (string)$this->request->getPost('code'),
            'name'                  => (string)$this->request->getPost('name'),
            'description'           => $this->request->getPost('description'),
            'workflow_status'       => 'DRAFT',
            'revision_number'       => 1,
            'is_active'             => 0,
            'created_by'            => $userId,
            'updated_by'            => $userId,
        ];

        $this->versionModel->insert($data);
        $id = (int)$this->versionModel->insertID();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['id' => $id],
        ]);
    }

    public function transition(int $id): ResponseInterface
    {
        $targetState     = (string)$this->request->getPost('target_state');
        $revisionNumber  = (int)$this->request->getPost('revision_number');
        $summary         = $this->request->getPost('change_summary');
        $userId          = (int)session()->get('user_id');

        try {
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
}
