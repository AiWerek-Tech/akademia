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

    public function applyBatch(int $batchId, int $userId, ?int $expectedRevision = null): array
    {
        $batch = $this->batchModel->find($batchId);
        if (!$batch) {
            throw new \RuntimeException("Import batch ID {$batchId} not found.");
        }
        if ((string) $batch['status'] !== 'VALIDATED' || (int) ($batch['error_rows'] ?? 0) > 0) {
            throw new \RuntimeException('Batch belum tervalidasi penuh atau sudah pernah diterapkan.');
        }

        $versionId = (int) $batch['schedule_version_id'];
        $validRows = $this->rowModel->where('batch_id', $batchId)->where('validation_status', 'VALID')->findAll();
        if (count($validRows) !== (int) $batch['total_rows'] || $validRows === []) {
            throw new \RuntimeException('Seluruh baris import harus valid sebelum diterapkan.');
        }

        $appliedCount = 0;
        $now = date('Y-m-d H:i:s');
        $this->db->transException(true)->transBegin();

        try {
            $version = $this->db->query(
                'SELECT id, unit_id, academic_period_id, workflow_status, revision_number FROM schedule_versions WHERE id = ? FOR UPDATE',
                [$versionId]
            )->getRowArray();
            if (! $version || (string) $version['workflow_status'] !== 'DRAFT') {
                throw new \RuntimeException('Import hanya dapat diterapkan pada versi jadwal DRAFT.');
            }
            if ($expectedRevision !== null && (int) $version['revision_number'] !== $expectedRevision) {
                throw new \RuntimeException('Versi jadwal telah berubah. Muat ulang sebelum menerapkan import.', 409);
            }

            foreach ($validRows as $row) {
                $classroomQuery = $this->db->table('classrooms')->where('code', $row['parsed_class_code'])
                    ->where('academic_period_id', (int) $version['academic_period_id'])
                    ->where('is_active', 1)->where('deleted_at IS NULL');
                if (! empty($version['unit_id'])) $classroomQuery->where('unit_id', (int) $version['unit_id']);
                $classroom = $classroomQuery->get()->getRowArray();

                $teacher = $this->db->table('teachers')
                    ->groupStart()->where('employee_number', $row['parsed_teacher_code'])->orWhere('nip', $row['parsed_teacher_code'])->groupEnd()
                    ->where('is_active', 1)->where('deleted_at IS NULL')->get()->getRowArray();
                $subject = $this->db->table('subjects')->where('code', $row['parsed_subject_code'])
                    ->where('is_active', 1)->where('deleted_at IS NULL')->get()->getRowArray();

                $roomQuery = ! empty($row['parsed_room_code'])
                    ? $this->db->table('rooms')->where('code', $row['parsed_room_code'])->where('is_active', 1)->where('deleted_at IS NULL')
                    : null;
                if ($roomQuery && ! empty($version['unit_id'])) {
                    $roomQuery->groupStart()->where('unit_id', (int) $version['unit_id'])->orWhere('shared_between_units', 1)->groupEnd();
                }
                $room = $roomQuery ? $roomQuery->get()->getRowArray() : null;

                if (!$classroom || !$teacher || !$subject || (! empty($row['parsed_room_code']) && ! $room)) {
                    throw new \RuntimeException('Baris ' . $row['row_number'] . ': referensi berada di luar lingkup versi jadwal.');
                }

                $daySlot = $this->db->table('schedule_day_slots sds')->select('sds.id')
                    ->join('schedule_days sd', 'sd.id = sds.day_id')
                    ->where('sds.schedule_version_id', $versionId)
                    ->where('sd.day_name', $row['parsed_day_code'])
                    ->where('sds.slot_number', (int) $row['parsed_slot_number'])->get()->getRowArray();
                if (!$daySlot) {
                    throw new \RuntimeException('Baris ' . $row['row_number'] . ': hari atau slot tidak tersedia.');
                }

                $req = $this->requirementModel->where('schedule_version_id', $versionId)
                    ->where('classroom_id', $classroom['id'])->where('subject_id', $subject['id'])->first();
                if (!$req || ((int) $req['teacher_id'] !== (int) $teacher['id']
                    && (int) ($req['second_teacher_id'] ?? 0) !== (int) $teacher['id'])) {
                    throw new \RuntimeException('Baris ' . $row['row_number'] . ': tidak sesuai pembagian tugas guru.');
                }
                (new TeamTeachingPolicyService())->assertRequirementSupported($req);

                $occupied = $this->db->table('schedule_entries')->where('schedule_version_id', $versionId)
                    ->where('day_slot_id', (int) $daySlot['id'])
                    ->groupStart()->where('classroom_id', (int) $classroom['id'])
                        ->orWhere('teacher_id', (int) $teacher['id'])->orWhere('second_teacher_id', (int) $teacher['id']);
                if ($room) $occupied->orWhere('room_id', (int) $room['id']);
                $occupied->groupEnd();
                if ($occupied->countAllResults() > 0) {
                    throw new \RuntimeException('Baris ' . $row['row_number'] . ': slot, guru, kelas, atau ruang sudah terpakai.');
                }

                if (! $this->entryModel->insert([
                    'uuid' => UuidService::v4(),
                    'schedule_version_id' => $versionId,
                    'day_slot_id' => $daySlot['id'],
                    'schedule_requirement_id' => (int) $req['id'],
                    'classroom_id' => $classroom['id'],
                    'teacher_id' => $teacher['id'],
                    'subject_id' => $subject['id'],
                    'room_id' => $room ? $room['id'] : null,
                    'is_locked' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ])) {
                    throw new \RuntimeException('Baris ' . $row['row_number'] . ': entri gagal disimpan.');
                }
                $appliedCount++;
            }

            $audit = (new ScheduleConflictDetectionService())->detectConflicts($versionId);
            if (array_filter($audit['conflicts'] ?? [], static fn (array $conflict): bool => ($conflict['severity'] ?? '') === 'CRITICAL') !== []) {
                throw new \RuntimeException('Import menghasilkan konflik kritis dan seluruh perubahan dibatalkan.');
            }

            $newRevision = (int) $version['revision_number'] + 1;
            $this->batchModel->update($batchId, ['status' => 'APPLIED', 'applied_at' => $now, 'applied_by' => $userId]);
            $this->db->table('schedule_versions')->where('id', $versionId)->update([
                'revision_number' => $newRevision,
                'change_summary' => 'Batch import jadwal #' . $batchId . ' diterapkan',
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            $this->db->table('schedule_revision_history')->insert([
                'uuid' => UuidService::v4(),
                'schedule_version_id' => $versionId,
                'revision_number' => $newRevision,
                'action' => 'APPLY_IMPORT_BATCH',
                'changes_json' => json_encode(['batch_id' => $batchId, 'applied_entries' => $appliedCount], JSON_THROW_ON_ERROR),
                'performed_by' => $userId,
                'created_at' => $now,
            ]);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return [
            'status' => 'success',
            'batch_id' => $batchId,
            'applied_entries' => $appliedCount,
            'new_revision' => $newRevision,
        ];
    }
}
