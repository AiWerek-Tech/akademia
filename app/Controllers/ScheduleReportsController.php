<?php

namespace App\Controllers;

use App\Services\ScheduleExportService;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleReportsController extends BaseController
{
    private ScheduleExportService $exportService;

    public function __construct()
    {
        $this->exportService = new ScheduleExportService();
    }

    public function classroomReport(int $versionId, int $classroomId): ResponseInterface
    {
        $data = $this->exportService->getGridForClassroom($versionId, $classroomId);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    public function teacherReport(int $versionId, int $teacherId): ResponseInterface
    {
        $data = $this->exportService->getGridForTeacher($versionId, $teacherId);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}
