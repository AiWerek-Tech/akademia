<?php

namespace App\Controllers;

use App\Models\TeacherAvailabilityRuleModel;
use App\Services\UnitScopeService;
use App\Services\UuidService;
use Config\Database;
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
        $status           = strtoupper((string)($this->request->getPost('availability_status') ?? 'UNAVAILABLE'));
        $reason           = $this->request->getPost('reason');

        if ($teacherId <= 0 || $academicPeriodId <= 0
            || ($dayOfWeek !== null && ($dayOfWeek < 1 || $dayOfWeek > 7))
            || ($slotNumber !== null && ($slotNumber < 1 || $slotNumber > 30))
            || ! in_array($status, ['AVAILABLE', 'UNAVAILABLE'], true)
            || mb_strlen((string) $reason) > 255) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Aturan ketersediaan guru tidak valid.'])->setStatusCode(422);
        }

        try {
            UnitScopeService::assertTeacher($teacherId);
            if (! Database::connect()->table('academic_periods')->where('id', $academicPeriodId)->get()->getRowArray()) {
                throw new \RuntimeException('Periode akademik tidak ditemukan.');
            }
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(403);
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'uuid'                => UuidService::v4(),
            'teacher_id'          => $teacherId,
            'academic_period_id' => $academicPeriodId,
            'day_of_week'         => $dayOfWeek,
            'slot_number'        => $slotNumber,
            'availability_status' => $status,
            'reason'              => $reason,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $existingQuery = $this->teacherAvailabilityModel
            ->where('teacher_id', $teacherId)
            ->where('academic_period_id', $academicPeriodId);
        $dayOfWeek === null ? $existingQuery->where('day_of_week IS NULL') : $existingQuery->where('day_of_week', $dayOfWeek);
        $slotNumber === null ? $existingQuery->where('slot_number IS NULL') : $existingQuery->where('slot_number', $slotNumber);
        $existing = $existingQuery->first();
        if ($existing) {
            unset($data['uuid'], $data['created_at']);
            $this->teacherAvailabilityModel->update((int) $existing['id'], $data);
            $id = (int) $existing['id'];
        } else {
            $this->teacherAvailabilityModel->insert($data);
            $id = (int)$this->teacherAvailabilityModel->insertID();
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['id' => $id],
        ]);
    }
}
