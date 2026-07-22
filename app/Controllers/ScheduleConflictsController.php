<?php

namespace App\Controllers;

use App\Models\ScheduleConflictModel;
use App\Services\ScheduleConflictDetectionService;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleConflictsController extends BaseController
{
    private ScheduleConflictModel $conflictModel;
    private ScheduleConflictDetectionService $detectionService;

    public function __construct()
    {
        $this->conflictModel    = new ScheduleConflictModel();
        $this->detectionService = new ScheduleConflictDetectionService();
    }

    public function audit(int $versionId): ResponseInterface
    {
        try {
            $result = $this->detectionService->detectConflicts($versionId);
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode(400);
        }
    }
}
