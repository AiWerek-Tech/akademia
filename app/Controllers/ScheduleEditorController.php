<?php

namespace App\Controllers;

use App\Models\ScheduleEntryModel;
use App\Models\ScheduleVersionModel;
use App\Services\ScheduleConflictDetectionService;
use CodeIgniter\HTTP\ResponseInterface;

class ScheduleEditorController extends BaseController
{
    private ScheduleVersionModel $versionModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleConflictDetectionService $conflictService;

    public function __construct()
    {
        $this->versionModel    = new ScheduleVersionModel();
        $this->entryModel      = new ScheduleEntryModel();
        $this->conflictService = new ScheduleConflictDetectionService();
    }

    public function view(int $versionId): string
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Versi jadwal #{$versionId} tidak ditemukan.");
        }

        return view('schedules/editor', [
            'title'   => 'Editor Jadwal Pelajaran - ' . $version['name'],
            'version' => $version,
        ]);
    }

    public function saveEntry(int $versionId): ResponseInterface
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Versi jadwal tidak ditemukan'])->setStatusCode(444);
        }

        if (in_array((string)$version['workflow_status'], ['LOCKED', 'ARCHIVED'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Jadwal terkunci/diarsipkan dan tidak dapat diubah'])->setStatusCode(400);
        }

        $daySlotId             = (int)$this->request->getPost('day_slot_id');
        $scheduleRequirementId = (int)$this->request->getPost('schedule_requirement_id');
        $classroomId           = (int)$this->request->getPost('classroom_id');
        $teacherId             = (int)$this->request->getPost('teacher_id');
        $secondTeacherId       = $this->request->getPost('second_teacher_id') ? (int)$this->request->getPost('second_teacher_id') : null;
        $subjectId             = (int)$this->request->getPost('subject_id');
        $roomId                = $this->request->getPost('room_id') ? (int)$this->request->getPost('room_id') : null;
        $userId                = (int)session()->get('user_id');

        $now = date('Y-m-d H:i:s');
        $data = [
            'uuid'                    => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'schedule_version_id'     => $versionId,
            'day_slot_id'             => $daySlotId,
            'schedule_requirement_id' => $scheduleRequirementId,
            'classroom_id'            => $classroomId,
            'teacher_id'              => $teacherId,
            'second_teacher_id'       => $secondTeacherId,
            'subject_id'              => $subjectId,
            'room_id'                 => $roomId,
            'is_locked'               => 0,
            'created_at'              => $now,
            'updated_at'              => $now,
            'created_by'              => $userId,
            'updated_by'              => $userId,
        ];

        // Unique constraint check for classroom at same slot
        $existing = $this->entryModel
            ->where('schedule_version_id', $versionId)
            ->where('day_slot_id', $daySlotId)
            ->where('classroom_id', $classroomId)
            ->first();

        if ($existing) {
            $this->entryModel->update($existing['id'], $data);
            $entryId = (int)$existing['id'];
        } else {
            $this->entryModel->insert($data);
            $entryId = (int)$this->entryModel->insertID();
        }

        // Re-run conflict detection
        $conflictReport = $this->conflictService->detectConflicts($versionId);

        return $this->response->setJSON([
            'status'          => 'success',
            'data'            => ['entry_id' => $entryId],
            'conflict_report' => $conflictReport,
        ]);
    }

    public function deleteEntry(int $entryId): ResponseInterface
    {
        $entry = $this->entryModel->find($entryId);
        if (!$entry) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Entri jadwal tidak ditemukan'])->setStatusCode(404);
        }

        if ((int)$entry['is_locked'] === 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Entri terkunci dan tidak dapat dihapus'])->setStatusCode(400);
        }

        $versionId = (int)$entry['schedule_version_id'];
        $this->entryModel->delete($entryId);

        $conflictReport = $this->conflictService->detectConflicts($versionId);

        return $this->response->setJSON([
            'status'          => 'success',
            'conflict_report' => $conflictReport,
        ]);
    }
}
