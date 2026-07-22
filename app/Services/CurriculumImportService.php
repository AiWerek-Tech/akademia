<?php

namespace App\Services;

use App\Models\CurriculumImportBatchModel;
use App\Models\CurriculumImportRowModel;
use App\Models\CurriculumVersionModel;
use App\Models\SchoolUnitModel;
use App\Models\GradeLevelModel;
use App\Models\ClassroomModel;
use App\Models\SubjectModel;
use App\Models\RoomTypeModel;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumEffectiveHoursService;
use App\Services\BlockPatternService;
use App\Services\UuidService;
use App\Services\AuditService;
use Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CurriculumImportService
{
    private const HEADERS = [
        'unit', 'grade', 'classroom_optional', 'subject_code', 'subject_name',
        'category', 'official_weekly_hours', 'custom_weekly_hours', 'manual_weekly_hours',
        'effective_source', 'block_pattern', 'minimum_days', 'maximum_daily_hours',
        'counts_in_report', 'counts_as_teaching_load', 'required_room_type',
        'schedule_priority', 'adjustment_reason', 'legal_reference', 'notes',
    ];

    private const REQUIRED_HEADERS = ['unit', 'grade', 'subject_code', 'effective_source'];

    /**
     * Generate Excel template file for curriculum structure import
     */
    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Import');

        foreach (self::HEADERS as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0B5D56']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getStyle("A2:{$lastColumn}501")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        foreach (range(1, count(self::HEADERS)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(in_array($columnIndex, [18, 19, 20], true) ? 28 : 20);
        }
        $sheet->getColumnDimension('E')->setWidth(28);
        $sheet->getColumnDimension('R')->setWidth(34);
        $sheet->getColumnDimension('T')->setWidth(34);

        self::addListValidation($sheet, 'F2:F501', 'INTRAKURIKULER,MUATAN_LOKAL,KOKURIKULER,EKSTRAKURIKULER,KEGIATAN_TETAP,PENGEMBANGAN_DIRI,OTHER');
        self::addListValidation($sheet, 'J2:J501', 'OFFICIAL,CUSTOM,MANUAL');
        self::addListValidation($sheet, 'N2:O501', '1,0');

        $sheet->getStyle('A2:T2')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);
        $sheet->getComment('A2')->getText()->createTextRun('Isi kode unit yang tersedia pada sheet Referensi.');

        // Add instructions sheet
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Panduan');

        $instructions = [
            ['Kolom', 'Keterangan / Aturan', 'Contoh Nilai'],
            ['unit', 'Kode unit sekolah (wajib)', 'SMP'],
            ['grade', 'Kode tingkat kelas (Wajib)', 'VII, VIII, IX, X, XI, XII'],
            ['classroom_optional', 'Kode kelas jika override (Opsional)', '7A, X-IPA-1 (Kosongkan jika default tingkat)'],
            ['subject_code', 'Kode unik mata pelajaran (Wajib)', 'MAT-SMP, IPA-SMP'],
            ['subject_name', 'Nama mata pelajaran (opsional, hanya sebagai informasi)', 'Matematika'],
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

        array_unshift($instructions, ['CARA PAKAI', '1. Salin kode dari sheet Referensi. 2. Isi Data Import. 3. Unggah dan tinjau hasil pemeriksaan. 4. Klik Terapkan.', 'Jangan mengubah nama header.']);
        $row = 1;
        foreach ($instructions as $inst) {
            $infoSheet->setCellValue('A' . $row, $inst[0]);
            $infoSheet->setCellValue('B' . $row, $inst[1]);
            $infoSheet->setCellValue('C' . $row, $inst[2] ?? '');
            $row++;
        }

        $infoSheet->mergeCells('A1:A1');
        $infoSheet->getStyle('A1:C2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $infoSheet->freezePane('A3');
        $infoSheet->getColumnDimension('A')->setWidth(28);
        $infoSheet->getColumnDimension('B')->setWidth(72);
        $infoSheet->getColumnDimension('C')->setWidth(42);
        $infoSheet->getStyle('A1:C' . ($row - 1))->getAlignment()->setWrapText(true);

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');
        $referenceSheet->fromArray([['Kode Unit', 'Nama Unit', 'Kode Tingkat', 'Nama Tingkat', 'Kode Mapel', 'Nama Mapel', 'Kode Jenis Ruang']], null, 'A1');
        $db = Database::connect();
        $allowedUnits = session()->get('logged_in') ? UnitScopeService::accessibleUnitIds() : [];
        $unitRows = $db->table('school_units')->select('code, name')->where('is_active', 1);
        $gradeRows = $db->table('grade_levels gl')->select('gl.code, gl.name, su.code AS unit_code')->join('school_units su', 'su.id = gl.unit_id')->where('gl.is_active', 1);
        $subjectRows = $db->table('subjects')->select('code, name')->where('is_active', 1);
        if ($allowedUnits !== []) {
            $unitRows->whereIn('id', $allowedUnits);
            $gradeRows->whereIn('gl.unit_id', $allowedUnits);
        }
        $units = $unitRows->orderBy('code')->get()->getResultArray();
        $grades = $gradeRows->orderBy('su.code')->orderBy('gl.grade_number')->get()->getResultArray();
        $subjects = $subjectRows->orderBy('code')->get()->getResultArray();
        $roomTypes = $db->table('room_types')->select('code, name')->where('is_active', 1)->orderBy('code')->get()->getResultArray();
        $maxRows = max(count($units), count($grades), count($subjects), count($roomTypes));
        for ($i = 0; $i < $maxRows; $i++) {
            $referenceSheet->fromArray([[
                $units[$i]['code'] ?? '', $units[$i]['name'] ?? '',
                $grades[$i]['code'] ?? '', isset($grades[$i]) ? $grades[$i]['unit_code'] . ' - ' . $grades[$i]['name'] : '',
                $subjects[$i]['code'] ?? '', $subjects[$i]['name'] ?? '',
                $roomTypes[$i]['code'] ?? '',
            ]], null, 'A' . ($i + 2));
        }
        $referenceSheet->freezePane('A2');
        $referenceSheet->setAutoFilter('A1:G' . max(2, $maxRows + 1));
        $referenceSheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
        ]);
        foreach (range('A', 'G') as $column) {
            $referenceSheet->getColumnDimension($column)->setWidth(in_array($column, ['B', 'D', 'F'], true) ? 34 : 20);
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

    private static function addListValidation($sheet, string $range, string $values): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
            ->setErrorTitle('Nilai tidak valid')->setError('Pilih salah satu nilai yang tersedia.')
            ->setFormula1('"' . $values . '"');
        $sheet->setDataValidation($range, $validation);
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
        if ($version['workflow_status'] === 'ARCHIVED') {
            throw new \InvalidArgumentException('Kurikulum yang sudah diarsipkan tidak dapat menerima data impor.');
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
        if ($worksheet->getHighestDataRow() > 10000 || Coordinate::columnIndexFromString($worksheet->getHighestDataColumn()) > 52) {
            throw new \InvalidArgumentException('File import melebihi batas 10.000 baris atau 52 kolom.');
        }
        $rowsData    = $worksheet->toArray(null, true, true, true);

        if (count($rowsData) < 2) {
            throw new \InvalidArgumentException('File spreadsheet kosong atau hanya berisi header.');
        }

        $headers = array_map(static fn ($value) => self::normalizeHeader((string) $value), array_shift($rowsData));
        $headerKeys = array_values($headers);
        $duplicates = array_diff_assoc($headerKeys, array_unique($headerKeys));
        if ($duplicates !== []) {
            throw new \InvalidArgumentException('Header ganda ditemukan: ' . implode(', ', array_unique($duplicates)) . '.');
        }
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headerKeys));
        if ($missing !== []) {
            throw new \InvalidArgumentException('Kolom wajib belum tersedia: ' . implode(', ', $missing) . '. Gunakan template terbaru.');
        }

        $parsedRows = [];
        $sourceRowNumber = 2;
        foreach ($rowsData as $row) {
            $mappedRow = [];
            foreach (array_values($row) as $colIndex => $colVal) {
                $key = $headerKeys[$colIndex] ?? '';
                if ($key !== '') {
                    $mappedRow[$key] = trim((string) $colVal);
                }
            }
            if (array_filter($mappedRow, static fn ($value) => $value !== '') !== []) {
                $parsedRows[] = ['number' => $sourceRowNumber, 'data' => $mappedRow];
            }
            $sourceRowNumber++;
        }
        if ($parsedRows === []) {
            throw new \InvalidArgumentException('Tidak ada data yang dapat dibaca. Isi data mulai baris 2 pada sheet pertama.');
        }

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
                'total_rows'            => count($parsedRows),
                'created_by'            => session()->get('user_id'),
                'created_at'            => date('Y-m-d H:i:s'),
            ]);

            $validCount   = 0;
            $warningCount = 0;
            $errorCount   = 0;
            foreach ($parsedRows as $parsedRow) {
                $mappedRow = $parsedRow['data'];
                $rowNumber = $parsedRow['number'];

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
            }

            $batchModel->update($batchId, [
                'status'       => 'VALIDATED',
                'valid_rows'   => $validCount,
                'warning_rows' => $warningCount,
                'error_rows'   => $errorCount,
            ]);

            AuditService::log('curriculum', 'UPLOAD_IMPORT', 'CurriculumImportBatch', $batchId, null, ['rows' => count($parsedRows)], 'Upload dan pemeriksaan awal impor kurikulum');

            $db->transCommit();
            return $batchModel->find($batchId);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $header)));
        $header = preg_replace('/[\s\-]+/', '_', $header) ?? $header;
        return [
            'kode_unit' => 'unit', 'unit_sekolah' => 'unit',
            'tingkat' => 'grade', 'kode_tingkat' => 'grade',
            'kelas' => 'classroom_optional', 'kode_kelas' => 'classroom_optional',
            'kode_mapel' => 'subject_code', 'mata_pelajaran' => 'subject_name', 'nama_mapel' => 'subject_name',
            'jam_resmi' => 'official_weekly_hours', 'jam_custom' => 'custom_weekly_hours', 'jam_manual' => 'manual_weekly_hours',
            'sumber_jam' => 'effective_source', 'jenis_ruang' => 'required_room_type',
        ][$header] ?? $header;
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
            'required_room_type'      => strtoupper(trim($row['required_room_type'] ?? '')),
            'required_room_type_id'   => null,
            'schedule_priority'       => isset($row['schedule_priority']) && $row['schedule_priority'] !== '' ? (int)$row['schedule_priority'] : 0,
            'adjustment_reason'       => trim($row['adjustment_reason'] ?? ''),
            'legal_reference'         => trim($row['legal_reference'] ?? ''),
            'notes'                   => trim($row['notes'] ?? ''),
        ];

        $allowedCategories = ['INTRAKURIKULER', 'MUATAN_LOKAL', 'KOKURIKULER', 'EKSTRAKURIKULER', 'KEGIATAN_TETAP', 'PENGEMBANGAN_DIRI', 'OTHER'];
        if (!in_array($normData['category'], $allowedCategories, true)) {
            $messages[] = "Kategori '{$normData['category']}' tidak valid.";
            $status = 'ERROR';
        }
        if (!in_array($normData['counts_in_report'], [0, 1], true) || !in_array($normData['counts_as_teaching_load'], [0, 1], true)) {
            $messages[] = 'Kolom masuk rapor dan beban mengajar hanya boleh bernilai 1 atau 0.';
            $status = 'ERROR';
        }
        if ($normData['schedule_priority'] < 0 || $normData['schedule_priority'] > 10) {
            $messages[] = 'Prioritas jadwal harus berada pada rentang 0 sampai 10.';
            $status = 'ERROR';
        }

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
            $grade = $gradeModel->where('unit_id', $mappedUnitId)->where('code', $normData['grade'])->where('is_active', 1)->first();
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
                ->where('academic_period_id', (new CurriculumVersionModel())->find($versionId)['academic_period_id'])
                ->where('is_active', 1)
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
            $subject = $subjectModel->where('code', $normData['subject_code'])->where('is_active', 1)->first();
            if ($subject) {
                $mappedSubjectId = (int)$subject['id'];
                if ($mappedUnitId && !Database::connect()->table('subject_unit_availability')
                    ->where('subject_id', $mappedSubjectId)->where('unit_id', $mappedUnitId)->where('is_available', 1)
                    ->get()->getRowArray()) {
                    $messages[] = "Mata pelajaran '{$normData['subject_code']}' belum tersedia untuk unit '{$normData['unit']}'.";
                    $status = 'ERROR';
                }
            } else {
                $messages[] = "Kode mata pelajaran '{$normData['subject_code']}' tidak ditemukan di database master.";
                $status = 'ERROR';
            }
        } else {
            $messages[] = 'Kolom subject_code (kode mapel) wajib diisi.';
            $status = 'ERROR';
        }

        if ($normData['required_room_type'] !== '') {
            $roomType = (new RoomTypeModel())->where('code', $normData['required_room_type'])->where('is_active', 1)->first();
            if (!$roomType) {
                $messages[] = "Kode jenis ruang '{$normData['required_room_type']}' tidak ditemukan atau tidak aktif.";
                $status = 'ERROR';
            } else {
                $normData['required_room_type_id'] = (int) $roomType['id'];
            }
        }

        if ($status !== 'ERROR' && $mappedUnitId && $mappedGradeId && $mappedSubjectId) {
            $existing = self::findExistingStructure($versionId, $mappedUnitId, $mappedGradeId, $mappedClassroomId, $mappedSubjectId);
            if ($existing) {
                $proposedAction = 'UPDATE';
                $messages[] = 'Data sudah ada dan akan diperbarui.';
                $status = 'WARNING';
            }
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

    private static function findExistingStructure(int $versionId, int $unitId, int $gradeId, ?int $classroomId, int $subjectId): ?array
    {
        $builder = Database::connect()->table('curriculum_structures')
            ->where('curriculum_version_id', $versionId)->where('unit_id', $unitId)
            ->where('grade_level_id', $gradeId)->where('subject_id', $subjectId)
            ->where('status', 'ACTIVE')->where('deleted_at IS NULL');
        $classroomId === null ? $builder->where('classroom_id IS NULL') : $builder->where('classroom_id', $classroomId);
        return $builder->get()->getRowArray() ?: null;
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
            $version = (new CurriculumVersionModel())->find($batch['curriculum_version_id']);
            if (!$version || $version['workflow_status'] === 'ARCHIVED') {
                throw new \RuntimeException('Kurikulum tujuan tidak tersedia atau sudah diarsipkan.');
            }

            $rows = $rowModel->where('batch_id', $batch['id'])
                ->where('validation_status !=', 'ERROR')
                ->where('admin_decision !=', 'SKIP')
                ->findAll();

            $appliedCount = 0;
            $insertedCount = 0;
            $updatedCount = 0;

            if ($rows === []) {
                throw new \RuntimeException('Tidak ada baris valid yang dapat diterapkan. Perbaiki file lalu unggah kembali.');
            }

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
                    'minimum_days'            => $normData['minimum_days'] ?? null,
                    'maximum_daily_hours'     => $normData['maximum_daily_hours'] ?? null,
                    'counts_in_report'        => $normData['counts_in_report'] ?? 1,
                    'counts_as_teaching_load' => $normData['counts_as_teaching_load'] ?? 1,
                    'required_room_type_id'   => $normData['required_room_type_id'] ?? null,
                    'schedule_priority'       => $normData['schedule_priority'] ?? 0,
                    'adjustment_reason'       => $normData['adjustment_reason'] ?? null,
                    'legal_reference'         => $normData['legal_reference'] ?? null,
                    'notes'                   => $normData['notes'] ?? null,
                ];

                UnitScopeService::assertUnit((int) $r['mapped_unit_id']);
                $existing = self::findExistingStructure(
                    (int) $batch['curriculum_version_id'], (int) $r['mapped_unit_id'],
                    (int) $r['mapped_grade_level_id'], $r['mapped_classroom_id'] !== null ? (int) $r['mapped_classroom_id'] : null,
                    (int) $r['mapped_subject_id']
                );
                if ($existing) {
                    $structureData['revision_number'] = (int) $existing['revision_number'];
                    CurriculumStructureService::updateStructure($existing['uuid'], $structureData);
                    $updatedCount++;
                } else {
                    CurriculumStructureService::createStructure($structureData);
                    $insertedCount++;
                }
                $appliedCount++;
            }

            $batchModel->update($batch['id'], [
                'status'       => 'APPLIED',
                'applied_rows' => $appliedCount,
                'applied_by'   => session()->get('user_id'),
                'applied_at'   => date('Y-m-d H:i:s'),
            ]);

            AuditService::log('curriculum', 'APPLY_IMPORT', 'CurriculumImportBatch', $batch['id'], null, ['applied_rows' => $appliedCount, 'inserted_rows' => $insertedCount, 'updated_rows' => $updatedCount], 'Menerapkan batch import kurikulum');

            $db->transCommit();
            return [
                'applied_rows' => $appliedCount,
                'inserted_rows' => $insertedCount,
                'updated_rows' => $updatedCount,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
