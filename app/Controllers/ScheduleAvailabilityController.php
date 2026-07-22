<?php

namespace App\Controllers;

use App\Models\TeacherAvailabilityRuleModel;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleAvailabilityController extends BaseController
{
    private TeacherAvailabilityRuleModel $teacherAvailabilityModel;

    public function __construct()
    {
        $this->teacherAvailabilityModel = new TeacherAvailabilityRuleModel();
    }

    public function saveTeacherRule(): ResponseInterface
    {
        $teacherId        = (int)$this->request->getPost('teacher_id');
        $academicPeriodId = (int)$this->request->getPost('academic_period_id');
        $dayOfWeek        = $this->request->getPost('day_of_week') ? (int)$this->request->getPost('day_of_week') : null;
        $slotNumber       = $this->request->getPost('slot_number') ? (int)$this->request->getPost('slot_number') : null;
        $status           = (string)($this->request->getPost('availability_status') ?? 'UNAVAILABLE');
        $reason           = $this->request->getPost('reason');

        $now = date('Y-m-d H:i:s');
        $data = [
            'uuid'                => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'teacher_id'          => $teacherId,
            'academic_period_id' => $academicPeriodId,
            'day_of_week'         => $dayOfWeek,
            'slot_number'        => $slotNumber,
            'availability_status' => $status,
            'reason'              => $reason,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $this->teacherAvailabilityModel->insert($data);
        $id = (int)$this->teacherAvailabilityModel->insertID();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['id' => $id],
        ]);
    }
}
