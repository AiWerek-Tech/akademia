<?php

namespace App\Controllers;

use App\Models\ScheduleConflictModel;
use App\Services\ScheduleConflictDetectionService;
use App\Services\UnitScopeService;
use App\Models\ScheduleVersionModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\ScheduleConflictPresentationService;

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
            $version = (new ScheduleVersionModel())->find($versionId);
            if (! $version) {
                throw new \RuntimeException('Versi jadwal tidak ditemukan.');
            }
            if (!empty($version['unit_id'])) {
                UnitScopeService::assertUnit((int) $version['unit_id']);
            }
            $result = $this->detectionService->detectConflicts($versionId);
            $presented=ScheduleConflictPresentationService::split($result['conflicts']??[]);
            $result['blocking_conflicts']=$presented['blocking'];
            $result['advisories']=$presented['advisories'];
            $result['total_blocking_conflicts']=count($presented['blocking']);
            $result['total_advisories']=count($presented['advisories']);
            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);
        } catch (\Throwable $e) {
            $statusCode = in_array((int) $e->getCode(), [403, 409], true) ? (int) $e->getCode() : 400;
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ])->setStatusCode($statusCode);
        }
    }
}
