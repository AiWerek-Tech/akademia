<?php

namespace App\Services;

use App\Models\AssignmentImportBatchModel;
use App\Models\AssignmentImportRowModel;
use App\Models\AssignmentVersionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\SchoolUnitModel;
use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssignmentImportService
{
    /**
     * Generate Excel template file for teaching assignments import.
     */
    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Penugasan');

        $headers = [
            'academic_year', 'semester', 'assignment_version_code', 'unit', 'grade',
            'classroom', 'subject_code', 'subject_name', 'teacher_identifier',
            'teacher_name', 'allocation_mode', 'assignment_role', 'assigned_weekly_hours',
            'workload_weekly_hours', 'is_primary_teacher', 'team_group', 'notes'
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Instructions sheet
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk Pengisian');

        $instructions = [
            ['Kolom', 'Keterangan / Aturan', 'Contoh Nilai'],
            ['academic_year', 'Tahun Pelajaran (Wajib)', '2026/2027'],
            ['semester', 'Nomor Semester (1 atau 2) (Wajib)', '1'],
            ['assignment_version_code', 'Kode Versi Penugasan (Wajib)', 'TP-26-27-SM1-V1'],
            ['unit', 'Kode Unit Sekolah (SMP atau SMA) (Wajib)', 'SMP'],
            ['grade', 'Kode Tingkat (VII-XII) (Wajib)', 'VII'],
            ['classroom', 'Kode Rombel Kelas (Wajib)', '7A'],
            ['subject_code', 'Kode Mata Pelajaran (Wajib)', 'MAT-SMP'],
            ['subject_name', 'Nama Mata Pelajaran (Opsional)', 'Matematika'],
            ['teacher_identifier', 'NIP/NIK/Employee Number Guru (Wajib)', 'G001'],
            ['teacher_name', 'Nama Lengkap Guru (Opsional)', 'John Doe'],
            ['allocation_mode', 'SINGLE_TEACHER, SPLIT_HOURS, atau TEAM_TEACHING (Wajib)', 'SINGLE_TEACHER'],
            ['assignment_role', 'PRIMARY, CO_TEACHER, ASSISTANT, SUBSTITUTE, atau OTHER (Wajib)', 'PRIMARY'],
            ['assigned_weekly_hours', 'Jumlah jam dialokasikan di kelas (> 0) (Wajib)', '4'],
            ['workload_weekly_hours', 'Beban jam mingguan dihitung ke guru (Wajib)', '4'],
            ['is_primary_teacher', '1 jika guru utama, 0 jika bukan (Wajib)', '1'],
            ['team_group', 'UUID kelompok jika team teaching (Opsional)', 'group-uuid-here'],
        ];

        $row = 1;
        foreach ($instructions as $inst) {
            $infoSheet->setCellValue('A' . $row, $inst[0]);
            $infoSheet->setCellValue('B' . $row, $inst[1]);
            $infoSheet->setCellValue('C' . $row, $inst[2] ?? '');
            if ($row === 1) {
                $infoSheet->getStyle('A1:C1')->getFont()->setBold(true);
            }
            $row++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $targetDir = WRITEPATH . 'imports/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $path = $targetDir . 'template_penugasan_' . time() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /**
     * Process uploaded assignment workbook.
     */
    public static function processUpload(int $versionId, $file): array
    {
        $versionModel = new AssignmentVersionModel();
        $version = $versionModel->find($versionId);
        if (!$version) {
            throw new \InvalidArgumentException('Versi penugasan tidak ditemukan.');
        }
        if (in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED'], true)) {
            throw new \InvalidArgumentException('Versi penugasan terkunci tidak dapat diimpor.');
        }

        if (!$file->isValid() || $file->hasMoved()) {
            throw new \InvalidArgumentException('File upload tidak valid: ' . $file->getErrorString());
        }

        if ($file->getSize() <= 0 || $file->getSize() > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('Ukuran file import harus lebih dari 0 dan maksimal 10 MB.');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new \InvalidArgumentException('Ekstensi file harus berupa .xlsx, .xls, atau .csv');
        }

        $targetDir = WRITEPATH . 'imports/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newName  = $file->getRandomName();
        $filePath = $targetDir . $newName;
        $file->move($targetDir, $newName);

        $sourceHash = hash_file('sha256', $filePath);
        $sourceSize = filesize($filePath);
        $sourceMime = $file->getClientMimeType();

        // Load Spreadsheet
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $rowsData    = $worksheet->toArray(null, true, true, true);

        if (count($rowsData) < 2) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File spreadsheet kosong atau hanya berisi header.');
        }

        $headers    = array_map('trim', array_shift($rowsData));
        $headerKeys = array_values($headers);

        if (count(array_filter($headerKeys)) > 52) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File import melebihi batas 52 kolom.');
        }
        $requiredHeaders = [
            'unit', 'grade', 'classroom', 'subject_code', 'teacher_identifier',
            'assigned_weekly_hours',
        ];
        $missingHeaders = array_diff($requiredHeaders, $headerKeys);
        if ($missingHeaders !== []) {
            throw new \InvalidArgumentException('Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders));
        }
        if (count($headerKeys) !== count(array_unique($headerKeys))) {
            throw new \InvalidArgumentException('Header spreadsheet tidak boleh duplikat.');
        }

        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new AssignmentImportBatchModel();
            $rowModel   = new AssignmentImportRowModel();

            $batchUuid = UuidService::v4();
            $batchId   = $batchModel->insert([
                'uuid'                  => $batchUuid,
                'assignment_version_id' => $versionId,
                'source_filename'       => $file->getClientName(),
                'source_hash'           => $sourceHash,
                'source_mime'           => $sourceMime,
                'source_size'           => $sourceSize,
                'status'                => 'PARSED',
                'total_rows'            => count($rowsData),
                'created_by'            => session()->get('user_id'),
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            $validCount   = 0;
            $warningCount = 0;
            $errorCount   = 0;
            $rowNumber    = 2;

            foreach ($rowsData as $row) {
                $mappedRow = [];
                $colIndex  = 0;
                foreach ($row as $colVal) {
                    $key = $headerKeys[$colIndex] ?? ('col_' . $colIndex);
                    $mappedRow[$key] = trim((string)$colVal);
                    $colIndex++;
                }

                if (empty(array_filter($mappedRow))) {
                    continue;
                }

                $validation = self::validateRow($versionId, $mappedRow);

                if ($validation['status'] === 'VALID') {
                    $validCount++;
                } elseif ($validation['status'] === 'WARNING') {
                    $warningCount++;
                } else {
                    $errorCount++;
                }

                $rowModel->insert([
                    'batch_id'                 => $batchId,
                    'row_number'               => $rowNumber,
                    'raw_data_json'            => json_encode($mappedRow),
                    'normalized_data_json'     => json_encode($validation['normalized_data']),
                    'source_unit'              => $mappedRow['unit'] ?? null,
                    'source_grade'             => $mappedRow['grade'] ?? null,
                    'source_classroom'         => $mappedRow['classroom'] ?? null,
                    'source_subject'           => $mappedRow['subject_code'] ?? null,
                    'source_teacher'           => $mappedRow['teacher_identifier'] ?? null,
                    'mapped_unit_id'           => $validation['mapped_unit_id'],
                    'mapped_grade_level_id'    => $validation['mapped_grade_level_id'],
                    'mapped_classroom_id'      => $validation['mapped_classroom_id'],
                    'mapped_subject_id'        => $validation['mapped_subject_id'],
                    'mapped_teacher_id'        => $validation['mapped_teacher_id'],
                    'assigned_weekly_hours'    => $validation['normalized_data']['assigned_weekly_hours'] ?? null,
                    'workload_weekly_hours'    => $validation['normalized_data']['workload_weekly_hours'] ?? null,
                    'allocation_mode'          => $validation['normalized_data']['allocation_mode'] ?? null,
                    'assignment_role'          => $validation['normalized_data']['assignment_role'] ?? null,
                    'proposed_action'          => $validation['proposed_action'],
                    'validation_status'        => $validation['status'],
                    'validation_messages_json' => json_encode($validation['messages']),
                    'admin_decision'           => $validation['status'] === 'ERROR' ? 'SKIP' : 'APPLY',
                    'created_at'               => date('Y-m-d H:i:s'),
                ]);

                $rowNumber++;
            }

            $batchModel->update($batchId, [
                'status'       => 'VALIDATED',
                'valid_rows'   => $validCount,
                'warning_rows' => $warningCount,
                'error_rows'   => $errorCount,
            ]);

            AuditService::log('assignments', 'UPLOAD_IMPORT', 'AssignmentImportBatch', $batchId, null, ['rows' => count($rowsData)], 'Upload & parse staging import penugasan');

            $db->transCommit();
            return $batchModel->find($batchId);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Validate individual staged row.
     */
    private static function validateRow(int $versionId, array $row): array
    {
        $messages          = [];
        $status            = 'VALID';
        $proposedAction    = 'INSERT';
        $mappedUnitId      = null;
        $mappedGradeId     = null;
        $mappedClassroomId = null;
        $mappedSubjectId   = null;
        $mappedTeacherId   = null;

        $normData = [
            'unit'                  => trim($row['unit'] ?? ''),
            'grade'                 => trim($row['grade'] ?? ''),
            'classroom'             => trim($row['classroom'] ?? ''),
            'subject_code'          => trim($row['subject_code'] ?? ''),
            'teacher_identifier'    => trim($row['teacher_identifier'] ?? ''),
            'allocation_mode'       => strtoupper(trim($row['allocation_mode'] ?? 'SINGLE_TEACHER')),
            'assignment_role'       => strtoupper(trim($row['assignment_role'] ?? 'PRIMARY')),
            'assigned_weekly_hours' => isset($row['assigned_weekly_hours']) && $row['assigned_weekly_hours'] !== '' ? (float)$row['assigned_weekly_hours'] : null,
            'workload_weekly_hours' => isset($row['workload_weekly_hours']) && $row['workload_weekly_hours'] !== '' ? (float)$row['workload_weekly_hours'] : null,
            'is_primary_teacher'    => isset($row['is_primary_teacher']) ? (int)$row['is_primary_teacher'] : 1,
            'team_group'            => !empty($row['team_group']) ? trim($row['team_group']) : null,
        ];

        // 1. Map Unit
        if (!empty($normData['unit'])) {
            $unitModel = new SchoolUnitModel();
            $unit = $unitModel->where('code', $normData['unit'])->first();
            if ($unit) {
                $mappedUnitId = (int)$unit['id'];
                if (session()->get('logged_in')) {
                    try {
                        UnitScopeService::assertUnit($mappedUnitId);
                    } catch (\Throwable $e) {
                        $messages[] = "Anda tidak memiliki akses ke unit '{$normData['unit']}'.";
                        $status = 'ERROR';
                        $mappedUnitId = null;
                    }
                }
            } else {
                $messages[] = "Kode unit '{$normData['unit']}' tidak ditemukan.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Unit wajib diisi.';
            $status = 'ERROR';
        }

        // 2. Map Grade Level
        if (!empty($normData['grade']) && $mappedUnitId) {
            $gradeModel = new GradeLevelModel();
            $grade = $gradeModel->where('unit_id', $mappedUnitId)->where('code', $normData['grade'])->first();
            if ($grade) {
                $mappedGradeId = (int)$grade['id'];
            } else {
                $messages[] = "Kode tingkat '{$normData['grade']}' tidak ditemukan pada unit '{$normData['unit']}'.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Tingkat wajib diisi.';
            $status = 'ERROR';
        }

        // 3. Map Classroom
        if (!empty($normData['classroom']) && $mappedUnitId && $mappedGradeId) {
            $classroomModel = new ClassroomModel();
            $classroom = $classroomModel->where('unit_id', $mappedUnitId)
                                         ->where('grade_level_id', $mappedGradeId)
                                         ->where('code', $normData['classroom'])
                                         ->first();
            if ($classroom) {
                $mappedClassroomId = (int)$classroom['id'];
            } else {
                $messages[] = "Rombel kelas '{$normData['classroom']}' tidak ditemukan pada tingkat/unit ini.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Rombel kelas wajib diisi.';
            $status = 'ERROR';
        }

        // 4. Map Subject
        if (!empty($normData['subject_code'])) {
            $subjectModel = new SubjectModel();
            $subject = $subjectModel->where('code', $normData['subject_code'])->first();
            if ($subject) {
                $mappedSubjectId = (int)$subject['id'];
            } else {
                $messages[] = "Mata pelajaran '{$normData['subject_code']}' tidak ditemukan di database.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Kode mata pelajaran wajib diisi.';
            $status = 'ERROR';
        }

        // 5. Map Teacher (Strict, NO ambiguous mapping)
        if (!empty($normData['teacher_identifier'])) {
            $teacherModel = new TeacherModel();
            $db = \Config\Database::connect();

            // Search by exact employee number, nip, or nik in identifiers
            $match = $db->table('teacher_identifiers')
                        ->where('identifier_value', $normData['teacher_identifier'])
                        ->get()
                        ->getRowArray();

            if ($match) {
                $mappedTeacherId = (int)$match['teacher_id'];
            } else {
                // Try searching by name or exact email in teachers table
                $teachers = $teacherModel->groupStart()
                                            ->where('full_name', $normData['teacher_identifier'])
                                            ->orWhere('email', $normData['teacher_identifier'])
                                         ->groupEnd()
                                         ->findAll();

                if (count($teachers) === 1) {
                    $mappedTeacherId = (int)$teachers[0]['id'];
                } elseif (count($teachers) > 1) {
                    $messages[] = "Identifier guru '{$normData['teacher_identifier']}' ambigu: terdeteksi lebih dari satu guru yang cocok.";
                    $status = 'ERROR';
                } else {
                    $messages[] = "Guru dengan identifier '{$normData['teacher_identifier']}' tidak ditemukan.";
                    $status = 'ERROR';
                }
            }

            // Verify teacher status
            if ($mappedTeacherId) {
                $teacherObj = $teacherModel->find($mappedTeacherId);
                if ($teacherObj && (int)$teacherObj['is_active'] === 0) {
                    $messages[] = "Guru {$teacherObj['full_name']} berstatus tidak aktif.";
                    $status = 'ERROR';
                }
            }
        } else {
            $messages[] = 'Identifier guru wajib diisi.';
            $status = 'ERROR';
        }

        if ($mappedUnitId && $mappedSubjectId && session()->get('logged_in')) {
            try {
                UnitScopeService::assertSubjectInUnit($mappedSubjectId, $mappedUnitId);
            } catch (\Throwable $e) {
                $messages[] = $e->getMessage();
                $status = 'ERROR';
            }
        }
        if ($mappedUnitId && $mappedTeacherId && session()->get('logged_in')) {
            try {
                $targetVersion = (new AssignmentVersionModel())->find($versionId);
                UnitScopeService::assertTeacherInUnit(
                    $mappedTeacherId,
                    $mappedUnitId,
                    $targetVersion ? (int) $targetVersion['academic_period_id'] : null
                );
            } catch (\Throwable $e) {
                $messages[] = $e->getMessage();
                $status = 'ERROR';
            }
        }

        // 6. Validation of hours
        if ($normData['assigned_weekly_hours'] !== null && $normData['assigned_weekly_hours'] <= 0) {
            $messages[] = 'Jumlah jam yang ditugaskan harus lebih dari 0.';
            $status = 'ERROR';
        }

        return [
            'status'                => $status,
            'messages'              => $messages,
            'proposed_action'       => $proposedAction,
            'mapped_unit_id'        => $mappedUnitId,
            'mapped_grade_level_id' => $mappedGradeId,
            'mapped_classroom_id'   => $mappedClassroomId,
            'mapped_subject_id'     => $mappedSubjectId,
            'mapped_teacher_id'     => $mappedTeacherId,
            'normalized_data'       => $normData
        ];
    }

    /**
     * Apply staged batch to teaching assignments database.
     */
    public static function applyBatch(string $uuid): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new AssignmentImportBatchModel();
            $rowModel   = new AssignmentImportRowModel();

            $batch = $batchModel->where('uuid', $uuid)->first();
            if (!$batch || in_array($batch['status'], ['APPLIED', 'CANCELLED'], true)) {
                throw new \RuntimeException('Batch import tidak valid atau sudah diproses.');
            }

            $targetVersion = (new AssignmentVersionModel())->find($batch['assignment_version_id']);
            if (!$targetVersion || in_array($targetVersion['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED'], true)) {
                throw new \RuntimeException('Versi penugasan target terkunci atau tidak ditemukan.');
            }

            $rows = $rowModel->where('batch_id', $batch['id'])
                             ->where('validation_status !=', 'ERROR')
                             ->where('admin_decision', 'APPLY')
                             ->findAll();

            $appliedCount = 0;
            $assignmentModel = new TeachingAssignmentModel();
            $structureModel = new CurriculumStructureModel();

            foreach ($rows as $r) {
                if (session()->get('logged_in')) {
                    UnitScopeService::assertUnit((int) $r['mapped_unit_id']);
                    UnitScopeService::assertClassroom((int) $r['mapped_classroom_id']);
                    UnitScopeService::assertSubjectInUnit((int) $r['mapped_subject_id'], (int) $r['mapped_unit_id']);
                    UnitScopeService::assertTeacherInUnit(
                        (int) $r['mapped_teacher_id'],
                        (int) $r['mapped_unit_id'],
                        (int) $targetVersion['academic_period_id']
                    );
                }
                // Find curriculum_structure matching version, classroom, subject
                $version = (new AssignmentVersionModel())->find($batch['assignment_version_id']);
                
                $structure = $structureModel->where('curriculum_version_id', $version['curriculum_version_id'])
                                            ->where('classroom_id', $r['mapped_classroom_id'])
                                            ->where('subject_id', $r['mapped_subject_id'])
                                            ->where('status', 'ACTIVE')
                                            ->first();

                if (!$structure) {
                    // Fallback to grade-level default structure
                    $structure = $structureModel->where('curriculum_version_id', $version['curriculum_version_id'])
                                                ->where('grade_level_id', $r['mapped_grade_level_id'])
                                                ->where('classroom_id', null)
                                                ->where('subject_id', $r['mapped_subject_id'])
                                                ->where('status', 'ACTIVE')
                                                ->first();
                }

                if (!$structure) {
                    continue; // Skip if no structure matches
                }

                $normData = json_decode($r['normalized_data_json'], true);

                $assignmentModel->insert([
                    'uuid'                    => UuidService::v4(),
                    'assignment_version_id'   => $batch['assignment_version_id'],
                    'curriculum_structure_id' => $structure['id'],
                    'academic_period_id'      => $version['academic_period_id'],
                    'unit_id'                 => $r['mapped_unit_id'],
                    'grade_level_id'          => $r['mapped_grade_level_id'],
                    'classroom_id'            => $r['mapped_classroom_id'],
                    'subject_id'              => $r['mapped_subject_id'],
                    'teacher_id'              => $r['mapped_teacher_id'],
                    'assignment_role'         => $r['assignment_role'] ?? 'PRIMARY',
                    'assigned_weekly_hours'   => $r['assigned_weekly_hours'],
                    'workload_weekly_hours'   => $r['workload_weekly_hours'] ?? $r['assigned_weekly_hours'],
                    'source_weekly_hours'     => $structure['effective_weekly_hours'],
                    'is_primary_teacher'      => $normData['is_primary_teacher'] ?? 1,
                    'team_group_uuid'         => $normData['team_group'] ?? null,
                    'status'                  => 'ACTIVE'
                ]);

                $appliedCount++;
            }

            $batchModel->update($batch['id'], [
                'status'       => 'APPLIED',
                'applied_rows' => $appliedCount,
                'applied_by'   => session()->get('user_id'),
                'applied_at'   => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('assignments', 'APPLY_IMPORT', 'AssignmentImportBatch', $batch['id'], null, ['applied_rows' => $appliedCount], 'Menerapkan batch import penugasan');

            $db->transCommit();
            return ['applied_rows' => $appliedCount];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Rollback a previously applied import batch.
     */
    public static function rollbackBatch(string $uuid): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new AssignmentImportBatchModel();
            $batch = $batchModel->where('uuid', $uuid)->first();
            if (!$batch || $batch['status'] !== 'APPLIED') {
                throw new \RuntimeException('Batch import tidak valid atau belum diterapkan.');
            }

            $assignmentModel = new TeachingAssignmentModel();
            // Delete teaching assignments matching batch details
            $assignmentModel->where('assignment_version_id', $batch['assignment_version_id'])
                             ->where('created_at >=', $batch['applied_at'])
                             ->delete();

            $batchModel->update($batch['id'], [
                'status'          => 'VALIDATED',
                'rolled_back_by'  => session()->get('user_id'),
                'rolled_back_at'  => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('assignments', 'ROLLBACK_IMPORT', 'AssignmentImportBatch', $batch['id'], null, [], 'Melakukan rollback batch import penugasan');

            $db->transCommit();
            return ['status' => 'success'];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
