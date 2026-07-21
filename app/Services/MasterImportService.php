<?php

namespace App\Services;

use App\Models\MasterImportBatchModel;
use App\Models\MasterImportRowModel;
use App\Models\TeacherModel;
use App\Models\SubjectModel;
use App\Models\ClassroomModel;
use App\Models\RoomModel;
use App\Models\SchoolUnitModel;
use App\Models\GradeLevelModel;
use App\Models\AcademicYearModel;
use App\Models\AcademicPeriodModel;
use App\Models\RoomTypeModel;
use Config\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterImportService
{
    public const SUPPORTED_TYPES = ['TEACHERS', 'SUBJECTS', 'CLASSROOMS', 'ROOMS'];

    /**
     * Generate Excel template file for master import type
     */
    public static function generateTemplate(string $type): string
    {
        $type = strtoupper($type);
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Jenis import master tidak dikenal.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Data');

        $headers = [];
        $instructions = [];

        switch ($type) {
            case 'TEACHERS':
                $headers = [
                    'employee_number', 'nip', 'nik', 'full_name', 'title_prefix',
                    'degree_suffix', 'gender', 'birth_place', 'birth_date', 'phone',
                    'email', 'address', 'employment_status', 'employment_type',
                    'primary_unit', 'additional_units', 'hire_date', 'notes', 'active'
                ];
                $instructions = [
                    ['Kolom', 'Keterangan', 'Wajib / Opsional'],
                    ['full_name', 'Nama lengkap guru', 'Wajib'],
                    ['employment_status', 'Status kepegawaian (GURU_TETAP, GURU_HONORER, DPK, dll)', 'Wajib'],
                    ['primary_unit', 'Kode unit utama (SMP atau SMA)', 'Wajib'],
                    ['additional_units', 'Unit tambahan dipisah koma (misal: SMA)', 'Opsional'],
                    ['birth_date', 'Format YYYY-MM-DD', 'Opsional'],
                ];
                break;

            case 'SUBJECTS':
                $headers = [
                    'code', 'name', 'short_name', 'category', 'counts_in_report',
                    'counts_as_teaching_load', 'units', 'aliases', 'default_room_type',
                    'sort_order', 'active'
                ];
                $instructions = [
                    ['Kolom', 'Keterangan', 'Wajib / Opsional'],
                    ['code', 'Kode mata pelajaran unik (misal: MAT-SMP)', 'Wajib'],
                    ['name', 'Nama lengkap mata pelajaran', 'Wajib'],
                    ['category', 'WAJIB, PILIHAN, MUATAN_LOKAL, KOKURIKULER, EKSTRAKURIKULER, KEGIATAN_TETAP, OTHER', 'Wajib'],
                    ['units', 'Kode unit yang menggunakan (SMP, SMA, atau SMP,SMA)', 'Wajib'],
                ];
                break;

            case 'CLASSROOMS':
                $headers = [
                    'academic_year', 'semester', 'unit', 'grade', 'code', 'name',
                    'major', 'specialization', 'capacity', 'homeroom_teacher_identifier',
                    'default_room_code', 'active'
                ];
                $instructions = [
                    ['Kolom', 'Keterangan', 'Wajib / Opsional'],
                    ['unit', 'Kode unit (SMP / SMA)', 'Wajib'],
                    ['grade', 'Kode tingkat (VII, VIII, IX, X, XI, XII)', 'Wajib'],
                    ['code', 'Kode kelas unik per periode & unit (misal: 7A, X-IPA-1)', 'Wajib'],
                    ['name', 'Nama tampilan kelas (misal: Kelas VII A)', 'Wajib'],
                ];
                break;

            case 'ROOMS':
                $headers = [
                    'code', 'name', 'room_type', 'unit', 'shared_between_units',
                    'capacity', 'location', 'floor', 'facilities', 'active'
                ];
                $instructions = [
                    ['Kolom', 'Keterangan', 'Wajib / Opsional'],
                    ['code', 'Kode ruang unik (misal: R-101, LAB-KOMP-1)', 'Wajib'],
                    ['name', 'Nama lengkap ruang', 'Wajib'],
                    ['room_type', 'Kode jenis ruang (CLASSROOM, LAB_COMPUTER, LAB_SCIENCE, dll)', 'Wajib'],
                    ['shared_between_units', '1 jika ruang dipakai bersama SMP & SMA, 0 jika tidak', 'Wajib'],
                ];
                break;
        }

        // Set headers on sheet 1
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Add instructions on sheet 2
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk Pengisian');
        $row = 1;
        foreach ($instructions as $inst) {
            $infoSheet->setCellValue('A' . $row, $inst[0]);
            $infoSheet->setCellValue('B' . $row, $inst[1] ?? '');
            $infoSheet->setCellValue('C' . $row, $inst[2] ?? '');
            if ($row === 1) {
                $infoSheet->getStyle('A1:C1')->getFont()->setBold(true);
            }
            $row++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = WRITEPATH . 'imports/template_' . strtolower($type) . '_' . time() . '.xlsx';
        if (!is_dir(WRITEPATH . 'imports')) {
            mkdir(WRITEPATH . 'imports', 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Handle master file upload, staging parsing, and row validation
     */
    public static function processUpload(string $type, $file): array
    {
        $type = strtoupper($type);
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Jenis import master tidak valid.');
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

        // Move to writable/imports
        $targetDir = WRITEPATH . 'imports/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($targetDir, $newName);
        $filePath = $targetDir . $newName;

        $sourceHash = hash_file('sha256', $filePath);
        $sourceSize = filesize($filePath);
        $sourceMime = $file->getClientMimeType();

        // Parse Spreadsheet safely
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        if ($worksheet->getHighestDataRow() > 10000 || $worksheet->getHighestDataColumn() > 'AZ') {
            throw new \InvalidArgumentException('File import melebihi batas 10.000 baris atau 52 kolom.');
        }
        $rowsData = $worksheet->toArray(null, true, true, true);

        if (count($rowsData) < 2) {
            throw new \InvalidArgumentException('File spreadsheet kosong atau hanya memiliki header.');
        }

        $headers = array_map('trim', array_shift($rowsData));
        $headerKeys = array_values($headers);

        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new MasterImportBatchModel();
            $rowModel   = new MasterImportRowModel();

            $batchId = $batchModel->insert([
                'import_type'     => $type,
                'source_filename' => $file->getClientName(),
                'source_hash'     => $sourceHash,
                'source_mime'     => $sourceMime,
                'source_size'     => $sourceSize,
                'status'          => 'PARSED',
                'total_rows'      => count($rowsData),
                'created_by'      => session()->get('user_id'),
            ]);

            $validCount   = 0;
            $warningCount = 0;
            $errorCount   = 0;

            $rowNumber = 2; // Row 1 was header
            foreach ($rowsData as $row) {
                // Map columns by header name
                $mappedRow = [];
                $colIndex = 0;
                foreach ($row as $colVal) {
                    $key = $headerKeys[$colIndex] ?? ('col_' . $colIndex);
                    $mappedRow[$key] = trim((string)$colVal);
                    $colIndex++;
                }

                // Ignore empty rows
                if (empty(array_filter($mappedRow))) {
                    continue;
                }

                $validation = self::validateRow($type, $mappedRow);

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
                    'entity_type'              => $type,
                    'raw_data_json'            => json_encode($mappedRow),
                    'normalized_data_json'     => json_encode($validation['normalized_data']),
                    'proposed_action'          => $validation['proposed_action'],
                    'target_entity_id'         => $validation['target_entity_id'],
                    'validation_status'        => $validation['status'],
                    'validation_messages_json' => json_encode($validation['messages']),
                    'admin_decision'           => $validation['proposed_action'],
                ]);

                $rowNumber++;
            }

            $batchModel->update($batchId, [
                'status'       => 'VALIDATED',
                'valid_rows'   => $validCount,
                'warning_rows' => $warningCount,
                'error_rows'   => $errorCount,
            ]);

            AuditService::log('master_import', 'UPLOAD', 'MasterImportBatch', $batchId, null, ['type' => $type, 'rows' => count($rowsData)], 'Upload & parse staging import file');

            $db->transCommit();
            return $batchModel->find($batchId);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Import upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate an individual row from staging data
     */
    private static function validateRow(string $type, array $row): array
    {
        $messages = [];
        $status = 'VALID';
        $proposedAction = 'INSERT';
        $targetEntityId = null;
        $normData = $row;

        switch ($type) {
            case 'TEACHERS':
                if (empty($row['full_name'])) {
                    $messages[] = 'Nama lengkap (full_name) wajib diisi';
                    $status = 'ERROR';
                }

                if (empty($row['employment_status'])) {
                    $messages[] = 'Status kepegawaian (employment_status) wajib diisi';
                    $status = 'ERROR';
                }

                $primaryUnit = self::resolveUnitCode($row['primary_unit'] ?? '');
                if ($primaryUnit === null) {
                    $messages[] = 'Unit utama (primary_unit) tidak ditemukan atau tidak dapat diakses.';
                    $status = 'ERROR';
                } else {
                    $normData['primary_unit_id'] = (int) $primaryUnit['id'];
                    $normData['unit_ids'] = [(int) $primaryUnit['id']];
                    foreach (self::splitCodes($row['additional_units'] ?? '') as $code) {
                        $additionalUnit = self::resolveUnitCode($code);
                        if ($additionalUnit === null) {
                            $messages[] = "Unit tambahan '{$code}' tidak ditemukan atau tidak dapat diakses.";
                            $status = 'ERROR';
                            continue;
                        }
                        $normData['unit_ids'][] = (int) $additionalUnit['id'];
                    }
                    $normData['unit_ids'] = array_values(array_unique($normData['unit_ids']));
                }

                // Duplicate check against database
                if (!empty($row['full_name'])) {
                    $matches = TeacherDuplicateDetectionService::scanForDuplicates($row);
                    if (!empty($matches)) {
                        $topMatch = $matches[0];
                        if ($topMatch['score'] >= 80) {
                            $proposedAction = 'MERGE';
                            $targetEntityId = $topMatch['teacher']['id'];
                            $messages[] = 'Terdeteksi calon duplikat dengan ' . $topMatch['teacher']['full_name'] . ' (Score: ' . $topMatch['score'] . '%)';
                            if ($status !== 'ERROR') {
                                $status = 'WARNING';
                            }
                        }
                    }
                }
                break;

            case 'SUBJECTS':
                if (empty($row['code'])) {
                    $messages[] = 'Kode mata pelajaran (code) wajib diisi';
                    $status = 'ERROR';
                } else {
                    $subjectModel = new SubjectModel();
                    $existing = $subjectModel->where('code', strtoupper(trim($row['code'])))->where('deleted_at IS NULL')->first();
                    if ($existing) {
                        $proposedAction = 'UPDATE';
                        $targetEntityId = $existing['id'];
                        $messages[] = 'Kode mapel ' . $row['code'] . ' sudah ada. Baris ini akan memperbarui data yang ada.';
                        if ($status !== 'ERROR') {
                            $status = 'WARNING';
                        }
                    }
                }

                if (empty($row['name'])) {
                    $messages[] = 'Nama mata pelajaran (name) wajib diisi';
                    $status = 'ERROR';
                }
                $normData['unit_ids'] = [];
                foreach (self::splitCodes($row['units'] ?? '') as $code) {
                    $unit = self::resolveUnitCode($code);
                    if ($unit === null) {
                        $messages[] = "Unit '{$code}' tidak ditemukan atau tidak dapat diakses.";
                        $status = 'ERROR';
                        continue;
                    }
                    $normData['unit_ids'][] = (int) $unit['id'];
                }
                if ($normData['unit_ids'] === []) {
                    $messages[] = 'Minimal satu unit yang dapat diakses wajib diisi pada kolom units.';
                    $status = 'ERROR';
                }
                break;

            case 'CLASSROOMS':
                if (empty($row['code'])) {
                    $messages[] = 'Kode kelas (code) wajib diisi';
                    $status = 'ERROR';
                }
                if (empty($row['name'])) {
                    $messages[] = 'Nama kelas (name) wajib diisi';
                    $status = 'ERROR';
                }
                $unit = self::resolveUnitCode($row['unit'] ?? '');
                $year = (new AcademicYearModel())->where('name', trim($row['academic_year'] ?? ''))->first();
                $semester = (int) ($row['semester'] ?? 0);
                $period = $year && $semester > 0
                    ? (new AcademicPeriodModel())->where('academic_year_id', $year['id'])->where('semester_number', $semester)->first()
                    : null;
                $grade = $unit
                    ? (new GradeLevelModel())->where('unit_id', $unit['id'])->where('code', trim($row['grade'] ?? ''))->first()
                    : null;
                if (!$unit || !$period || !$grade) {
                    $messages[] = 'Kombinasi tahun ajaran, semester, unit, atau tingkat kelas tidak valid/tidak dapat diakses.';
                    $status = 'ERROR';
                } else {
                    $normData['unit_id'] = (int) $unit['id'];
                    $normData['academic_period_id'] = (int) $period['id'];
                    $normData['grade_level_id'] = (int) $grade['id'];
                }
                break;

            case 'ROOMS':
                if (empty($row['code'])) {
                    $messages[] = 'Kode ruang (code) wajib diisi';
                    $status = 'ERROR';
                } else {
                    $roomModel = new RoomModel();
                    $existing = $roomModel->where('code', strtoupper(trim($row['code'])))->where('deleted_at IS NULL')->first();
                    if ($existing) {
                        $proposedAction = 'UPDATE';
                        $targetEntityId = $existing['id'];
                        $messages[] = 'Kode ruang ' . $row['code'] . ' sudah ada. Baris ini akan memperbarui ruang yang ada.';
                        if ($status !== 'ERROR') {
                            $status = 'WARNING';
                        }
                    }
                }

                if (empty($row['name'])) {
                    $messages[] = 'Nama ruang (name) wajib diisi';
                    $status = 'ERROR';
                }
                $isShared = !empty($row['shared_between_units']) ? 1 : 0;
                $unit = !empty($row['unit']) ? self::resolveUnitCode($row['unit']) : null;
                $roomType = (new RoomTypeModel())->where('code', strtoupper(trim($row['room_type'] ?? '')))->first();
                if (!$isShared && !$unit) {
                    $messages[] = 'Ruang non-shared wajib memiliki unit yang dapat diakses.';
                    $status = 'ERROR';
                }
                if (!$roomType) {
                    $messages[] = 'Kode jenis ruang tidak ditemukan.';
                    $status = 'ERROR';
                }
                $normData['shared_between_units'] = $isShared;
                $normData['unit_id'] = $unit ? (int) $unit['id'] : null;
                $normData['room_type_id'] = $roomType ? (int) $roomType['id'] : null;
                break;
        }

        return [
            'status'           => $status,
            'messages'         => $messages,
            'proposed_action'  => $proposedAction,
            'target_entity_id' => $targetEntityId,
            'normalized_data'  => $normData,
        ];
    }

    private static function splitCodes(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn ($code) => strtoupper(trim($code)),
            preg_split('/[,;]+/', $value) ?: []
        )));
    }

    private static function resolveUnitCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $unit = (new SchoolUnitModel())->where('code', $code)->where('is_active', 1)->first();
        if (!$unit) {
            return null;
        }

        if (session()->get('logged_in')) {
            try {
                UnitScopeService::assertUnit((int) $unit['id']);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $unit;
    }

    private static function authorizedUnitIds(array $unitIds): array
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $unitIds))));
        if (session()->get('logged_in')) {
            return UnitScopeService::assertUnits($normalized);
        }
        if ($normalized === []) {
            throw new \RuntimeException('Data import tidak memiliki unit sekolah yang valid.');
        }

        return $normalized;
    }

    /**
     * Apply validated import batch in a single database transaction
     */
    public static function applyBatch(string $uuid): array
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $batchModel = new MasterImportBatchModel();
            $rowModel   = new MasterImportRowModel();

            $batch = $batchModel->where('uuid', $uuid)->first();
            if (!$batch || in_array($batch['status'], ['APPLIED', 'CANCELLED'], true)) {
                throw new \RuntimeException('Batch import tidak valid atau sudah diterapkan/dibatalkan.');
            }

            $rows = $rowModel->where('batch_id', $batch['id'])
                ->where('validation_status !=', 'ERROR')
                ->where('admin_decision !=', 'SKIP')
                ->findAll();

            $appliedCount = 0;

            foreach ($rows as $r) {
                $normData = json_decode($r['normalized_data_json'], true) ?? [];
                $action   = $r['admin_decision'] ?? $r['proposed_action'];

                switch ($batch['import_type']) {
                    case 'TEACHERS':
                        if ($action === 'INSERT') {
                            $unitIds = self::authorizedUnitIds((array) ($normData['unit_ids'] ?? []));
                            TeacherService::createTeacher([
                                'full_name'         => $normData['full_name'] ?? '',
                                'nip'               => $normData['nip'] ?? null,
                                'nik'               => $normData['nik'] ?? null,
                                'employee_number'   => $normData['employee_number'] ?? null,
                                'gender'            => $normData['gender'] ?? null,
                                'birth_date'        => !empty($normData['birth_date']) ? $normData['birth_date'] : null,
                                'phone'             => $normData['phone'] ?? null,
                                'email'             => $normData['email'] ?? null,
                                'address'           => $normData['address'] ?? null,
                                'employment_status' => $normData['employment_status'] ?? 'GURU_TETAP',
                                'primary_unit_id'    => $normData['primary_unit_id'] ?? null,
                                'notes'             => $normData['notes'] ?? null,
                                'is_active'         => 1,
                            ], $unitIds);
                            $appliedCount++;
                        }
                        break;

                    case 'SUBJECTS':
                        if ($action === 'INSERT' || $action === 'UPDATE') {
                            $code = strtoupper(trim($normData['code'] ?? ''));
                            $subjectModel = new SubjectModel();
                            $existing = $subjectModel->where('code', $code)->where('deleted_at IS NULL')->first();

                            if ($existing) {
                                $unitIds = self::authorizedUnitIds((array) ($normData['unit_ids'] ?? []));
                                if (session()->get('logged_in')) {
                                    UnitScopeService::assertSubject((int) $existing['id']);
                                }
                                SubjectService::updateSubject($existing['uuid'], [
                                    'name'       => $normData['name'] ?? $existing['name'],
                                    'category'   => $normData['category'] ?? $existing['category'],
                                    'short_name' => $normData['short_name'] ?? $existing['short_name'],
                                    'revision_number' => $existing['revision_number'],
                                ], $unitIds);
                            } else {
                                $unitIds = self::authorizedUnitIds((array) ($normData['unit_ids'] ?? []));
                                SubjectService::createSubject([
                                    'code'                    => $code,
                                    'name'                    => $normData['name'] ?? '',
                                    'short_name'              => $normData['short_name'] ?? ($normData['code'] ?? ''),
                                    'category'                => $normData['category'] ?? 'WAJIB',
                                    'counts_in_report'        => 1,
                                    'counts_as_teaching_load' => 1,
                                ], $unitIds);
                            }
                            $appliedCount++;
                        }
                        break;

                    case 'ROOMS':
                        if ($action === 'INSERT' || $action === 'UPDATE') {
                            $code = strtoupper(trim($normData['code'] ?? ''));
                            $roomModel = new RoomModel();
                            $existing = $roomModel->where('code', $code)->where('deleted_at IS NULL')->first();

                            if (!$existing) {
                                if (!empty($normData['unit_id'])) {
                                    if (session()->get('logged_in')) {
                                        UnitScopeService::assertUnit((int) $normData['unit_id']);
                                    }
                                }
                                RoomService::createRoom([
                                    'code'                 => $code,
                                    'name'                 => $normData['name'] ?? '',
                                    'room_type_id'         => $normData['room_type_id'],
                                    'unit_id'              => $normData['unit_id'],
                                    'shared_between_units' => !empty($normData['shared_between_units']) ? 1 : 0,
                                    'capacity'             => !empty($normData['capacity']) ? (int)$normData['capacity'] : 30,
                                    'is_active'            => 1,
                                ]);
                                $appliedCount++;
                            } else {
                                if (session()->get('logged_in')) {
                                    UnitScopeService::assertRoom((int) $existing['id']);
                                }
                                RoomService::updateRoom($existing['uuid'], [
                                    'name'                 => $normData['name'] ?? $existing['name'],
                                    'room_type_id'         => $normData['room_type_id'] ?? $existing['room_type_id'],
                                    'unit_id'              => $normData['unit_id'] ?? $existing['unit_id'],
                                    'shared_between_units' => $normData['shared_between_units'] ?? $existing['shared_between_units'],
                                    'capacity'             => !empty($normData['capacity']) ? (int) $normData['capacity'] : $existing['capacity'],
                                    'revision_number'      => $existing['revision_number'],
                                ]);
                                $appliedCount++;
                            }
                        }
                        break;

                    case 'CLASSROOMS':
                        if ($action === 'INSERT') {
                            if (session()->get('logged_in')) {
                                UnitScopeService::assertUnit((int) ($normData['unit_id'] ?? 0));
                            }
                            ClassroomService::createClassroom([
                                'academic_period_id' => $normData['academic_period_id'] ?? null,
                                'unit_id'            => $normData['unit_id'] ?? null,
                                'grade_level_id'     => $normData['grade_level_id'] ?? null,
                                'code'               => $normData['code'] ?? '',
                                'name'               => $normData['name'] ?? '',
                                'major'              => $normData['major'] ?? null,
                                'specialization'     => $normData['specialization'] ?? null,
                                'capacity'           => !empty($normData['capacity']) ? (int) $normData['capacity'] : null,
                                'is_active'          => isset($normData['active']) ? (int) $normData['active'] : 1,
                            ]);
                            $appliedCount++;
                        }
                        break;
                }
            }

            $batchModel->update($batch['id'], [
                'status'       => 'APPLIED',
                'applied_rows' => $appliedCount,
                'applied_by'   => session()->get('user_id'),
                'applied_at'   => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('master_import', 'APPLY', 'MasterImportBatch', $batch['id'], $batch, ['applied_rows' => $appliedCount], 'Apply master import batch');

            $db->transCommit();
            return [
                'applied_rows' => $appliedCount,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Apply import batch failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
