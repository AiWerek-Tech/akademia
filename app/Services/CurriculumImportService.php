<?php

namespace App\Services;

use App\Models\CurriculumImportBatchModel;
use App\Models\CurriculumImportRowModel;
use App\Models\CurriculumVersionModel;
use App\Models\SchoolUnitModel;
use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumEffectiveHoursService;
use App\Services\BlockPatternService;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CurriculumImportService
{
    /**
     * Generate Excel template file for curriculum structure import
     */
    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data Kurikulum');

        $headers = [
            'unit', 'grade', 'classroom_optional', 'subject_code', 'subject_name',
            'category', 'official_weekly_hours', 'custom_weekly_hours', 'manual_weekly_hours',
            'effective_source', 'block_pattern', 'minimum_days', 'maximum_daily_hours',
            'counts_in_report', 'counts_as_teaching_load', 'required_room_type',
            'schedule_priority', 'adjustment_reason', 'legal_reference', 'notes'
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Add instructions sheet
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk Pengisian');

        $instructions = [
            ['Kolom', 'Keterangan / Aturan', 'Contoh Nilai'],
            ['unit', 'Kode unit sekolah (Wajib)', 'SMP atau SMA'],
            ['grade', 'Kode tingkat kelas (Wajib)', 'VII, VIII, IX, X, XI, XII'],
            ['classroom_optional', 'Kode kelas jika override (Opsional)', '7A, X-IPA-1 (Kosongkan jika default tingkat)'],
            ['subject_code', 'Kode unik mata pelajaran (Wajib)', 'MAT-SMP, IPA-SMP'],
            ['subject_name', 'Nama mata pelajaran (Wajib)', 'Matematika, IPA Terpadu'],
            ['category', 'Kategori kegiatan (Wajib)', 'INTRAKURIKULER, MUATAN_LOKAL, KOKURIKULER, EKSTRAKURIKULER, KEGIATAN_TETAP, PENGEMBANGAN_DIRI, OTHER'],
            ['official_weekly_hours', 'Jam resmi mingguan (Wajib jika OFFICIAL)', '4'],
            ['custom_weekly_hours', 'Jam custom mingguan (Wajib jika CUSTOM)', '2'],
            ['manual_weekly_hours', 'Jam manual mingguan (Wajib jika MANUAL)', '3'],
            ['effective_source', 'Sumber jam efektif (Wajib)', 'OFFICIAL, CUSTOM, atau MANUAL'],
            ['block_pattern', 'Pola blok JSON (Opsional)', '{"blocks":[2,2],"preferred":true}'],
            ['minimum_days', 'Minimum hari per minggu (Opsional)', '2'],
            ['maximum_daily_hours', 'Maksimum jam per hari (Opsional)', '3'],
            ['counts_in_report', '1 = Masuk rapor, 0 = Tidak (Default 1)', '1'],
            ['counts_as_teaching_load', '1 = Beban mengajar, 0 = Tidak (Default 1)', '1'],
            ['required_room_type', 'Kode jenis ruangan khusus (Opsional)', 'LAB_COMPUTER, LAB_SCIENCE'],
            ['schedule_priority', 'Prioritas jadwal 0-10 (Default 0)', '0'],
            ['adjustment_reason', 'Alasan jika CUSTOM/MANUAL (Wajib untuk CUSTOM/MANUAL)', 'Penyesuaian muatan lokal sekolah'],
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

        $path = $targetDir . 'template_kurikulum_' . time() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /**
     * Process uploaded file into staging database
     */
    public static function processUpload(int $versionId, $file): array
    {
        $versionModel = new CurriculumVersionModel();
        $version = $versionModel->find($versionId);
        if (!$version) {
            throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
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

        // Load & Parse Spreadsheet
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        if ($worksheet->getHighestDataRow() > 10000 || $worksheet->getHighestDataColumn() > 'AZ') {
            throw new \InvalidArgumentException('File import melebihi batas 10.000 baris atau 52 kolom.');
        }
        $rowsData    = $worksheet->toArray(null, true, true, true);

        if (count($rowsData) < 2) {
            throw new \InvalidArgumentException('File spreadsheet kosong atau hanya berisi header.');
        }

        $headers    = array_map('trim', array_shift($rowsData));
        $headerKeys = array_values($headers);

        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new CurriculumImportBatchModel();
            $rowModel   = new CurriculumImportRowModel();

            $batchUuid = UuidService::v4();
            $batchId   = $batchModel->insert([
                'uuid'                  => $batchUuid,
                'curriculum_version_id' => $versionId,
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
                    'source_classroom'         => $mappedRow['classroom_optional'] ?? null,
                    'source_subject'           => $mappedRow['subject_code'] ?? ($mappedRow['subject_name'] ?? null),
                    'mapped_unit_id'           => $validation['mapped_unit_id'],
                    'mapped_grade_level_id'    => $validation['mapped_grade_level_id'],
                    'mapped_classroom_id'      => $validation['mapped_classroom_id'],
                    'mapped_subject_id'        => $validation['mapped_subject_id'],
                    'official_hours'           => $validation['normalized_data']['official_weekly_hours'] ?? null,
                    'custom_hours'             => $validation['normalized_data']['custom_weekly_hours'] ?? null,
                    'manual_hours'             => $validation['normalized_data']['manual_weekly_hours'] ?? null,
                    'effective_source'         => $validation['normalized_data']['effective_source'] ?? 'OFFICIAL',
                    'category'                 => $validation['normalized_data']['category'] ?? 'INTRAKURIKULER',
                    'block_pattern_json'       => $validation['normalized_data']['block_pattern_json'] ?? null,
                    'proposed_action'          => $validation['proposed_action'],
                    'validation_status'        => $validation['status'],
                    'validation_messages_json' => json_encode($validation['messages']),
                    'admin_decision'           => $validation['proposed_action'],
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

            AuditService::log('curriculum', 'UPLOAD_IMPORT', 'CurriculumImportBatch', $batchId, null, ['rows' => count($rowsData)], 'Upload & parse staging import kurikulum');

            $db->transCommit();
            return $batchModel->find($batchId);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Validate an individual row from import file
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

        $normData = [
            'unit'                    => trim($row['unit'] ?? ''),
            'grade'                   => trim($row['grade'] ?? ''),
            'classroom_optional'      => trim($row['classroom_optional'] ?? ''),
            'subject_code'            => trim($row['subject_code'] ?? ''),
            'subject_name'            => trim($row['subject_name'] ?? ''),
            'category'                => strtoupper(trim($row['category'] ?? 'INTRAKURIKULER')),
            'official_weekly_hours'   => isset($row['official_weekly_hours']) && $row['official_weekly_hours'] !== '' ? (float)$row['official_weekly_hours'] : null,
            'custom_weekly_hours'     => isset($row['custom_weekly_hours']) && $row['custom_weekly_hours'] !== '' ? (float)$row['custom_weekly_hours'] : null,
            'manual_weekly_hours'     => isset($row['manual_weekly_hours']) && $row['manual_weekly_hours'] !== '' ? (float)$row['manual_weekly_hours'] : null,
            'effective_source'        => strtoupper(trim($row['effective_source'] ?? 'OFFICIAL')),
            'block_pattern_json'      => !empty($row['block_pattern']) ? trim($row['block_pattern']) : null,
            'minimum_days'            => isset($row['minimum_days']) && $row['minimum_days'] !== '' ? (int)$row['minimum_days'] : null,
            'maximum_daily_hours'     => isset($row['maximum_daily_hours']) && $row['maximum_daily_hours'] !== '' ? (float)$row['maximum_daily_hours'] : null,
            'counts_in_report'        => isset($row['counts_in_report']) && $row['counts_in_report'] !== '' ? (int)$row['counts_in_report'] : 1,
            'counts_as_teaching_load' => isset($row['counts_as_teaching_load']) && $row['counts_as_teaching_load'] !== '' ? (int)$row['counts_as_teaching_load'] : 1,
            'adjustment_reason'       => trim($row['adjustment_reason'] ?? ''),
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
            $messages[] = 'Kolom unit wajib diisi.';
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
            $messages[] = 'Kolom grade (tingkat) wajib diisi.';
            $status = 'ERROR';
        }

        // 3. Map Classroom Optional
        if (!empty($normData['classroom_optional']) && $mappedUnitId && $mappedGradeId) {
            $classroomModel = new ClassroomModel();
            $classroom = $classroomModel->where('unit_id', $mappedUnitId)
                ->where('grade_level_id', $mappedGradeId)
                ->where('code', $normData['classroom_optional'])
                ->first();
            if ($classroom) {
                $mappedClassroomId = (int)$classroom['id'];
            } else {
                $messages[] = "Kode kelas '{$normData['classroom_optional']}' tidak ditemukan pada unit/tingkat ini.";
                $status = 'ERROR';
            }
        }

        // 4. Map Subject (NO ambiguous auto-mapping)
        if (!empty($normData['subject_code'])) {
            $subjectModel = new SubjectModel();
            $subject = $subjectModel->where('code', $normData['subject_code'])->first();
            if ($subject) {
                $mappedSubjectId = (int)$subject['id'];
            } else {
                $messages[] = "Kode mata pelajaran '{$normData['subject_code']}' tidak ditemukan di database master.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Kolom subject_code (kode mapel) wajib diisi.';
            $status = 'ERROR';
        }

        // 5. Effective Source & Hours Validation
        try {
            $effCalc = CurriculumEffectiveHoursService::calculateEffectiveHours($normData);
            $normData['effective_weekly_hours'] = $effCalc['effective_weekly_hours'];
        } catch (\InvalidArgumentException $e) {
            $messages[] = $e->getMessage();
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
            'normalized_data'       => $normData,
        ];
    }

    /**
     * Apply validated import batch into curriculum structures
     */
    public static function applyBatch(string $uuid): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new CurriculumImportBatchModel();
            $rowModel   = new CurriculumImportRowModel();

            $batch = $batchModel->where('uuid', $uuid)->first();
            if (!$batch || in_array($batch['status'], ['APPLIED', 'CANCELLED'], true)) {
                throw new \RuntimeException('Batch import tidak valid atau sudah diterapkan/dibatalkan.');
            }

            if (empty($batch['curriculum_version_id'])) {
                throw new \RuntimeException('Versi kurikulum target belum ditentukan untuk batch import ini.');
            }

            $rows = $rowModel->where('batch_id', $batch['id'])
                ->where('validation_status !=', 'ERROR')
                ->where('admin_decision !=', 'SKIP')
                ->findAll();

            $appliedCount = 0;

            foreach ($rows as $r) {
                $normData = json_decode($r['normalized_data_json'], true) ?? [];

                $structureData = [
                    'curriculum_version_id'   => $batch['curriculum_version_id'],
                    'unit_id'                 => $r['mapped_unit_id'],
                    'grade_level_id'          => $r['mapped_grade_level_id'],
                    'classroom_id'            => $r['mapped_classroom_id'],
                    'subject_id'              => $r['mapped_subject_id'],
                    'official_weekly_hours'   => $r['official_hours'],
                    'custom_weekly_hours'     => $r['custom_hours'],
                    'manual_weekly_hours'     => $r['manual_hours'],
                    'effective_source'        => $r['effective_source'] ?? 'OFFICIAL',
                    'category'                => $r['category'] ?? 'INTRAKURIKULER',
                    'block_pattern_json'      => $r['block_pattern_json'],
                    'counts_in_report'        => $normData['counts_in_report'] ?? 1,
                    'counts_as_teaching_load' => $normData['counts_as_teaching_load'] ?? 1,
                    'adjustment_reason'       => $normData['adjustment_reason'] ?? null,
                ];

                CurriculumStructureService::createStructure($structureData);
                $appliedCount++;
            }

            $batchModel->update($batch['id'], [
                'status'       => 'APPLIED',
                'applied_rows' => $appliedCount,
                'applied_by'   => session()->get('user_id'),
                'applied_at'   => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'APPLY_IMPORT', 'CurriculumImportBatch', $batch['id'], null, ['applied_rows' => $appliedCount], 'Menerapkan batch import kurikulum');

            $db->transCommit();
            return [
                'applied_rows' => $appliedCount,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
