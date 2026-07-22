<?php

namespace App\Services;

use App\Models\ScheduleEntryModel;
use App\Models\ScheduleImportBatchModel;
use App\Models\ScheduleImportRowModel;
use App\Models\ScheduleRequirementModel;
use Config\Database;

class ScheduleImportService
{
    private ScheduleImportBatchModel $batchModel;
    private ScheduleImportRowModel $rowModel;
    private ScheduleEntryModel $entryModel;
    private ScheduleRequirementModel $requirementModel;
    private $db;

    public function __construct()
    {
        $this->batchModel       = new ScheduleImportBatchModel();
        $this->rowModel         = new ScheduleImportRowModel();
        $this->entryModel       = new ScheduleEntryModel();
        $this->requirementModel = new ScheduleRequirementModel();
        $this->db               = Database::connect();
    }

    public function stageBatch(int $scheduleVersionId, string $fileName, array $rowsData, int $userId): array
    {
        $now = date('Y-m-d H:i:s');
        $batchData = [
            'uuid'                => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'schedule_version_id' => $scheduleVersionId,
            'file_name'           => $fileName,
            'total_rows'          => count($rowsData),
            'valid_rows'          => 0,
            'error_rows'          => 0,
            'status'              => 'STAGED',
            'created_by'          => $userId,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
        $this->batchModel->insert($batchData);
        $batchId = (int)$this->batchModel->insertID();

        $validCount = 0;
        $errorCount = 0;

        foreach ($rowsData as $index => $row) {
            $rowNum     = $index + 1;
            $dayCode    = trim((string)($row['day_code'] ?? ''));
            $slotNumber = isset($row['slot_number']) ? (int)$row['slot_number'] : null;
            $classCode  = trim((string)($row['class_code'] ?? ''));
            $teacherCode= trim((string)($row['teacher_code'] ?? ''));
            $subjectCode= trim((string)($row['subject_code'] ?? ''));
            $roomCode   = trim((string)($row['room_code'] ?? ''));

            $errors = [];
            if ($dayCode === '') {
                $errors[] = 'Hari wajib diisi.';
            }
            if ($slotNumber === null || $slotNumber <= 0) {
                $errors[] = 'Nomor slot tidak valid.';
            }
            if ($classCode === '') {
                $errors[] = 'Kode kelas wajib diisi.';
            }
            if ($teacherCode === '') {
                $errors[] = 'Kode guru wajib diisi.';
            }
            if ($subjectCode === '') {
                $errors[] = 'Kode mata pelajaran wajib diisi.';
            }

            $status = $errors === [] ? 'VALID' : 'INVALID';
            if ($status === 'VALID') {
                $validCount++;
            } else {
                $errorCount++;
            }

            $this->rowModel->insert([
                'batch_id'               => $batchId,
                'row_number'             => $rowNum,
                'raw_data_json'          => json_encode($row),
                'parsed_day_code'        => $dayCode,
                'parsed_slot_number'     => $slotNumber,
                'parsed_class_code'      => $classCode,
                'parsed_teacher_code'    => $teacherCode,
                'parsed_subject_code'    => $subjectCode,
                'parsed_room_code'       => $roomCode,
                'validation_status'      => $status,
                'validation_errors_json' => json_encode($errors),
                'created_at'             => $now,
            ]);
        }

        $this->batchModel->update($batchId, [
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
            'status'     => 'VALIDATED',
        ]);

        return [
            'status'     => 'success',
            'batch_id'   => $batchId,
            'total_rows' => count($rowsData),
            'valid_rows' => $validCount,
            'error_rows' => $errorCount,
        ];
    }

    public function applyBatch(int $batchId, int $userId): array
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            throw new \RuntimeException("Import batch ID {$batchId} not found.");
        }

        if ((string)$batch['status'] === 'APPLIED') {
            throw new \RuntimeException("Batch ID {$batchId} has already been applied.");
        }

        $versionId = (int)$batch['schedule_version_id'];
        $validRows = $this->rowModel->where('batch_id', $batchId)->where('validation_status', 'VALID')->findAll();

        $appliedCount = 0;
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        foreach ($validRows as $row) {
            // Match classroom
            $classroom = $this->db->table('classrooms')->where('code', $row['parsed_class_code'])->get()->getRowArray();
            // Match teacher (user)
            $teacher   = $this->db->table('users')->where('username', $row['parsed_teacher_code'])->get()->getRowArray();
            // Match subject
            $subject   = $this->db->table('subjects')->where('code', $row['parsed_subject_code'])->get()->getRowArray();
            // Match room
            $room      = !empty($row['parsed_room_code']) ? $this->db->table('rooms')->where('code', $row['parsed_room_code'])->get()->getRowArray() : null;

            if (!$classroom || !$teacher || !$subject) {
                continue;
            }

            // Find matching day_slot
            $daySlot = $this->db->table('schedule_day_slots sds')
                ->select('sds.id')
                ->join('schedule_days sd', 'sd.id = sds.day_id')
                ->where('sds.schedule_version_id', $versionId)
                ->where('sd.day_name', $row['parsed_day_code'])
                ->where('sds.slot_number', (int)$row['parsed_slot_number'])
                ->get()->getRowArray();

            if (!$daySlot) {
                continue;
            }

            // Find or create requirement
            $req = $this->requirementModel
                ->where('schedule_version_id', $versionId)
                ->where('classroom_id', $classroom['id'])
                ->where('subject_id', $subject['id'])
                ->first();

            if (!$req) {
                $this->requirementModel->insert([
                    'uuid'                       => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                    'schedule_version_id'        => $versionId,
                    'classroom_id'               => $classroom['id'],
                    'subject_id'                 => $subject['id'],
                    'teacher_id'                 => $teacher['id'],
                    'required_weekly_hours'      => 1.0,
                    'consecutive_slots_required' => 1,
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ]);
                $reqId = (int)$this->requirementModel->insertID();
            } else {
                $reqId = (int)$req['id'];
            }

            // Insert entry
            $this->entryModel->insert([
                'uuid'                    => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
                'schedule_version_id'     => $versionId,
                'day_slot_id'             => $daySlot['id'],
                'schedule_requirement_id' => $reqId,
                'classroom_id'            => $classroom['id'],
                'teacher_id'              => $teacher['id'],
                'subject_id'              => $subject['id'],
                'room_id'                 => $room ? $room['id'] : null,
                'is_locked'               => 0,
                'created_at'              => $now,
                'updated_at'              => $now,
                'created_by'              => $userId,
                'updated_by'              => $userId,
            ]);

            $appliedCount++;
        }

        $this->batchModel->update($batchId, [
            'status'     => 'APPLIED',
            'applied_at' => $now,
            'applied_by' => $userId,
        ]);

        $this->db->transComplete();

        return [
            'status'         => 'success',
            'batch_id'       => $batchId,
            'applied_entries'=> $appliedCount,
        ];
    }
}
