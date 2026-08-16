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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

class MasterImportService
{
    public const SUPPORTED_TYPES = ['TEACHERS', 'SUBJECTS', 'GRADE_LEVELS', 'CLASSROOMS', 'ROOMS', 'STUDENTS'];

    public static function reviewColumns(string $type): array
    {
        $definition = self::templateDefinition(strtoupper($type));
        $preferred = match (strtoupper($type)) {
            'TEACHERS' => ['employee_number', 'nip', 'full_name', 'employment_status', 'primary_unit', 'additional_units'],
            'SUBJECTS' => ['code', 'name', 'short_name', 'category', 'units', 'aliases'],
            'GRADE_LEVELS' => ['unit', 'grade_number', 'code', 'name', 'phase', 'active'],
            'CLASSROOMS' => ['academic_year', 'semester', 'unit', 'grade', 'code', 'name', 'capacity'],
            'ROOMS' => ['code', 'name', 'room_type', 'unit', 'shared_between_units', 'capacity', 'location'],
            'STUDENTS' => ['student_number', 'full_name', 'unit', 'academic_year', 'current_grade', 'classroom_code', 'user_id'],
            default => array_keys($definition['columns']),
        };

        return array_intersect_key(
            array_map(static fn (array $column) => $column['label'], $definition['columns']),
            array_flip($preferred)
        );
    }

    public static function typeLabel(string $type): string
    {
        return self::templateDefinition(strtoupper($type))['title'];
    }

    /**
     * Generate Excel template file for master import type
     */
    public static function generateTemplate(string $type): string
    {
        return self::generateWorkbook($type);
    }

    /**
     * Generate an export workbook using the exact same columns and workbook
     * structure as the importer.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function generateDataWorkbook(string $type, array $rows, string $filename): string
    {
        return self::generateWorkbook($type, $rows, $filename);
    }

    /**
     * Single source of truth for template generation and import validation.
     *
     * @return array{title:string, columns:array<string, array<string, mixed>>}
     */
    public static function definition(string $type): array
    {
        return self::templateDefinition(strtoupper($type));
    }

    /** @return list<string> */
    public static function headers(string $type): array
    {
        return array_keys(self::definition($type)['columns']);
    }

    /**
     * @param array<int, array<string, mixed>> $dataRows
     */
    private static function generateWorkbook(string $type, array $dataRows = [], ?string $outputFilename = null): string
    {
        $type = strtoupper($type);
        if (!in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Jenis import master tidak dikenal.');
        }

        $definition = self::templateDefinition($type);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Import');
        $headers = array_keys($definition['columns']);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A1');
        $orderedRows = [];
        foreach ($dataRows as $rowNumber => $row) {
            $unknownHeaders = array_diff(array_keys($row), $headers);
            if ($unknownHeaders !== []) {
                throw new \InvalidArgumentException(
                    'Kolom data ekspor tidak dikenal pada baris ' . ($rowNumber + 1) . ': ' . implode(', ', $unknownHeaders) . '.'
                );
            }
            $orderedRows[] = array_map(static fn (string $header) => $row[$header] ?? '', $headers);
        }
        if ($orderedRows !== []) {
            $sheet->fromArray($orderedRows, null, 'A2');
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->setShowGridlines(false);
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '0F766E']]],
        ]);

        foreach ($definition['columns'] as $index => $column) {
            $columnIndex = array_search($index, $headers, true) + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setWidth($column['width'] ?? 18);
            $sheet->getComment($columnLetter . '1')->getText()->createTextRun(
                ($column['required'] ? 'WAJIB — ' : 'OPSIONAL — ') . $column['description']
            );
            $formatEndRow = max(500, min(10001, count($orderedRows) + 101));
            $sheet->getStyle($columnLetter . '2:' . $columnLetter . $formatEndRow)->getNumberFormat()
                ->setFormatCode(($column['format'] ?? 'text') === 'date' ? 'yyyy-mm-dd' : '@');
        }

        $exampleSheet = $spreadsheet->createSheet();
        $exampleSheet->setTitle('Contoh');
        $exampleSheet->fromArray($headers, null, 'A1');
        $exampleSheet->fromArray(array_map(static fn (array $column) => $column['example'] ?? '', $definition['columns']), null, 'A2');
        $exampleSheet->freezePane('A2');
        $exampleSheet->setShowGridlines(false);
        $exampleSheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $exampleSheet->getStyle("A2:{$lastColumn}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECFDF5');
        foreach ($definition['columns'] as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex(array_search($index, $headers, true) + 1);
            $exampleSheet->getColumnDimension($letter)->setWidth($column['width'] ?? 18);
        }

        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Petunjuk Pengisian');
        $infoSheet->mergeCells('A1:E1');
        $infoSheet->setCellValue('A1', 'Panduan Import ' . $definition['title']);
        $infoSheet->setCellValue('A2', 'Isi hanya sheet “Data Import”. Jangan mengubah nama header pada baris pertama. Maksimal 10.000 baris dan 10 MB.');
        $infoSheet->mergeCells('A2:E2');
        $infoSheet->fromArray(['Kolom Teknis', 'Nama Kolom', 'Status', 'Aturan Pengisian', 'Contoh'], null, 'A4');
        $row = 5;
        foreach ($definition['columns'] as $key => $column) {
            $infoSheet->fromArray([
                $key,
                $column['label'],
                $column['required'] ? 'WAJIB' : 'Opsional',
                $column['description'],
                $column['example'] ?? '',
            ], null, 'A' . $row++);
        }
        $lastInfoRow = $row - 1;
        $infoSheet->setShowGridlines(false);
        $infoSheet->freezePane('A5');
        $infoSheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $infoSheet->getRowDimension(1)->setRowHeight(34);
        $infoSheet->getStyle('A2:E2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0F2FE');
        $infoSheet->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);
        $infoSheet->getStyle("A5:E{$lastInfoRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        foreach (['A' => 24, 'B' => 24, 'C' => 14, 'D' => 70, 'E' => 32] as $letter => $width) {
            $infoSheet->getColumnDimension($letter)->setWidth($width);
        }

        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');
        $references = self::templateReferenceLists();
        $referenceRanges = [];
        $referenceColumn = 1;
        foreach ($references as $referenceKey => $values) {
            $letter = Coordinate::stringFromColumnIndex($referenceColumn++);
            $referenceSheet->setCellValue($letter . '1', $referenceKey);
            $values = array_values(array_unique(array_filter(array_map('strval', $values), static fn ($value) => $value !== '')));
            foreach ($values as $offset => $value) {
                $referenceSheet->setCellValueExplicit($letter . ($offset + 2), $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $lastReferenceRow = max(2, count($values) + 1);
            $referenceRanges[$referenceKey] = "'Referensi'!\${$letter}\$2:\${$letter}\${$lastReferenceRow}";
            $referenceSheet->getColumnDimension($letter)->setWidth(26);
        }
        $referenceSheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(max(1, $referenceColumn - 1)) . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
        ]);
        $referenceSheet->freezePane('A2');
        $referenceSheet->setShowGridlines(false);

        foreach ($definition['columns'] as $key => $column) {
            if (empty($column['list']) || empty($referenceRanges[$column['list']])) {
                continue;
            }
            $letter = Coordinate::stringFromColumnIndex(array_search($key, $headers, true) + 1);
            $validationEndRow = max(500, min(10001, count($orderedRows) + 101));
            $validation = (new DataValidation())
                ->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(!$column['required'])
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Nilai tidak valid')
                ->setError('Pilih nilai dari daftar referensi yang tersedia.')
                ->setFormula1($referenceRanges[$column['list']]);
            $sheet->setDataValidation("{$letter}2:{$letter}{$validationEndRow}", $validation);
        }

        $tableName = 'Import' . str_replace('_', '', ucwords(strtolower($type), '_')) . 'Table';
        $tableEndRow = max(2, count($orderedRows) + 1);
        $table = new Table("A1:{$lastColumn}{$tableEndRow}", $tableName);
        $tableStyle = new TableStyle();
        $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
        $table->setStyle($tableStyle);
        $sheet->addTable($table);

        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getProperties()
            ->setCreator('IALOS Education')
            ->setTitle('Template Import ' . $definition['title'])
            ->setDescription('Template resmi untuk staging import master data IALOS Education.');

        $targetDirectory = $outputFilename === null ? WRITEPATH . 'imports/' : WRITEPATH . 'exports/';
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }
        $safeFilename = $outputFilename === null
            ? 'template_' . strtolower($type) . '_' . time() . '.xlsx'
            : basename($outputFilename);
        if (!str_ends_with(strtolower($safeFilename), '.xlsx')) {
            $safeFilename .= '.xlsx';
        }
        $tempPath = $targetDirectory . $safeFilename;

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
        $duplicateBatch = (new MasterImportBatchModel())
            ->where('import_type', $type)
            ->where('source_hash', $sourceHash)
            ->where('status !=', 'CANCELLED')
            ->orderBy('id', 'DESC')
            ->first();
        if ($duplicateBatch) {
            @unlink($filePath);
            throw new \InvalidArgumentException(
                'File yang sama sudah pernah diunggah pada batch #' . $duplicateBatch['id'] . '. Gunakan batch tersebut atau ubah isi file sebelum mengunggah ulang.'
            );
        }

        // Parse Spreadsheet safely
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rowsData = $worksheet->toArray(null, true, true, true);

        if (count($rowsData) < 2) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File spreadsheet kosong atau hanya memiliki header.');
        }

        $headers = array_map(
            static fn ($header) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $header))),
            array_shift($rowsData)
        );
        $headerKeys = array_values($headers);
        if (count(array_filter($headerKeys)) > 52) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File import melebihi batas 52 kolom.');
        }

        $definition = self::templateDefinition($type);
        $allowedHeaders = array_keys($definition['columns']);
        $requiredHeaders = array_keys(array_filter(
            $definition['columns'],
            static fn (array $column): bool => $column['required']
        ));
        $missingHeaders = array_values(array_diff($requiredHeaders, $headerKeys));
        $unknownHeaders = array_values(array_diff(array_filter($headerKeys), $allowedHeaders));
        if (in_array('', $headerKeys, true)) {
            @unlink($filePath);
            throw new \InvalidArgumentException('Header spreadsheet tidak boleh kosong.');
        }
        if (count($headerKeys) !== count(array_unique($headerKeys))) {
            @unlink($filePath);
            throw new \InvalidArgumentException('Header spreadsheet tidak boleh duplikat.');
        }
        if ($missingHeaders !== []) {
            @unlink($filePath);
            throw new \InvalidArgumentException('Header wajib tidak ditemukan: ' . implode(', ', $missingHeaders) . '.');
        }
        if ($unknownHeaders !== []) {
            @unlink($filePath);
            throw new \InvalidArgumentException('Header tidak dikenal: ' . implode(', ', $unknownHeaders) . '. Gunakan template terbaru.');
        }

        $nonEmptyRows = array_values(array_filter($rowsData, static function (array $row): bool {
            return count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) > 0;
        }));
        if ($nonEmptyRows === []) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File spreadsheet tidak memiliki baris data untuk diimpor.');
        }
        if (count($nonEmptyRows) > 10000) {
            @unlink($filePath);
            throw new \InvalidArgumentException('File import melebihi batas 10.000 baris data.');
        }

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
                'total_rows'      => count($nonEmptyRows),
                'created_by'      => session()->get('user_id'),
            ]);

            $validCount   = 0;
            $warningCount = 0;
            $errorCount   = 0;

            $seenIdentities = [];
            foreach ($rowsData as $rowOffset => $row) {
                $rowNumber = $rowOffset + 2; // Excel row; row 1 is the header.
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
                $identity = self::rowIdentity($type, $mappedRow);
                if ($identity !== null && isset($seenIdentities[$identity])) {
                    $validation['status'] = 'ERROR';
                    $validation['messages'][] = 'Data duplikat di dalam file; sama dengan baris ' . $seenIdentities[$identity] . '.';
                    $validation['proposed_action'] = 'SKIP';
                } elseif ($identity !== null) {
                    $seenIdentities[$identity] = $rowNumber;
                }

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
                    'admin_decision'           => $validation['status'] === 'ERROR' || $validation['proposed_action'] === 'MERGE'
                        ? 'SKIP'
                        : $validation['proposed_action'],
                ]);
            }

            $batchModel->update($batchId, [
                'status'       => 'VALIDATED',
                'valid_rows'   => $validCount,
                'warning_rows' => $warningCount,
                'error_rows'   => $errorCount,
            ]);

            AuditService::log('master_import', 'UPLOAD', 'MasterImportBatch', $batchId, null, ['type' => $type, 'rows' => count($nonEmptyRows)], 'Upload & parse staging import file');

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
        $normData = array_map(static fn ($value) => trim((string) $value), $row);

        switch ($type) {
            case 'TEACHERS':
                if (mb_strlen(trim($row['full_name'] ?? '')) < 3) {
                    $messages[] = 'Nama lengkap (full_name) wajib diisi minimal 3 karakter.';
                    $status = 'ERROR';
                }

                if (empty($row['employment_status'])) {
                    $messages[] = 'Status kepegawaian (employment_status) wajib diisi';
                    $status = 'ERROR';
                }
                if (!empty($row['email']) && !filter_var(trim($row['email']), FILTER_VALIDATE_EMAIL)) {
                    $messages[] = 'Format email tidak valid.';
                    $status = 'ERROR';
                }
                if (!empty($row['gender']) && !in_array(strtoupper(trim($row['gender'])), ['LAKI_LAKI', 'PEREMPUAN'], true)) {
                    $messages[] = 'gender harus LAKI_LAKI atau PEREMPUAN.';
                    $status = 'ERROR';
                } elseif (!empty($row['gender'])) {
                    $normData['gender'] = strtoupper(trim($row['gender']));
                }
                $normData['employment_status'] = strtoupper(trim($row['employment_status'] ?? ''));
                $normData['employment_type'] = strtoupper(trim($row['employment_type'] ?? '')) ?: null;
                foreach (['birth_date', 'hire_date'] as $dateField) {
                    if (!empty($row[$dateField])) {
                        $normalizedDate = self::normalizeDate($row[$dateField]);
                        if ($normalizedDate === null) {
                            $messages[] = "{$dateField} harus berupa tanggal valid dengan format YYYY-MM-DD.";
                            $status = 'ERROR';
                        } else {
                            $normData[$dateField] = $normalizedDate;
                        }
                    }
                }
                if (!empty($normData['birth_date']) && $normData['birth_date'] > date('Y-m-d')) {
                    $messages[] = 'Tanggal lahir tidak boleh berada di masa depan.';
                    $status = 'ERROR';
                }
                $active = self::normalizeBoolean($row['active'] ?? '', 1);
                if ($active === null) {
                    $messages[] = 'active harus bernilai 1 atau 0.';
                    $status = 'ERROR';
                } else {
                    $normData['is_active'] = $active;
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
                $category = strtoupper(trim($row['category'] ?? ''));
                if (!in_array($category, SubjectService::CATEGORIES, true)) {
                    $messages[] = 'Kategori mata pelajaran tidak valid.';
                    $status = 'ERROR';
                } else {
                    $normData['category'] = $category;
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
                foreach (['counts_in_report', 'counts_as_teaching_load', 'active'] as $booleanField) {
                    $boolean = self::normalizeBoolean($row[$booleanField] ?? '', 1);
                    if ($boolean === null) {
                        $messages[] = "{$booleanField} harus bernilai 1 atau 0.";
                        $status = 'ERROR';
                    } else {
                        $normData[$booleanField === 'active' ? 'is_active' : $booleanField] = $boolean;
                    }
                }
                $normData['alias_list'] = self::splitTextValues($row['aliases'] ?? '');
                $normData['sort_order'] = ($row['sort_order'] ?? '') === '' ? 0 : (int) $row['sort_order'];
                if ($normData['sort_order'] < 0 || !preg_match('/^\d+$/', (string) ($row['sort_order'] ?? '0'))) {
                    $messages[] = 'sort_order harus berupa angka bulat tidak negatif.';
                    $status = 'ERROR';
                }
                if (!empty($row['default_room_type'])) {
                    $roomType = (new RoomTypeModel())->where('code', strtoupper(trim($row['default_room_type'])))->where('is_active', 1)->first();
                    if (!$roomType) {
                        $messages[] = 'default_room_type tidak ditemukan atau tidak aktif.';
                        $status = 'ERROR';
                    } else {
                        $normData['default_room_type_id'] = (int) $roomType['id'];
                    }
                }
                break;

            case 'GRADE_LEVELS':
                $unit = self::resolveUnitCode($row['unit'] ?? '');
                $gradeNumber = filter_var($row['grade_number'] ?? null, FILTER_VALIDATE_INT);
                $code = strtoupper(trim($row['code'] ?? ''));
                if (!$unit) {
                    $messages[] = 'Unit tidak ditemukan atau tidak dapat diakses.';
                    $status = 'ERROR';
                }
                if ($gradeNumber === false || $gradeNumber < 1 || $gradeNumber > 12) {
                    $messages[] = 'grade_number harus berupa angka 1 sampai 12.';
                    $status = 'ERROR';
                }
                if ($code === '') {
                    $messages[] = 'Kode tingkat (code) wajib diisi.';
                    $status = 'ERROR';
                }
                if (mb_strlen(trim($row['name'] ?? '')) < 2) {
                    $messages[] = 'Nama tingkat (name) wajib diisi minimal 2 karakter.';
                    $status = 'ERROR';
                }
                if ($unit && $gradeNumber !== false && $code !== '') {
                    $normData['unit_id'] = (int) $unit['id'];
                    $normData['grade_number'] = (int) $gradeNumber;
                    $normData['code'] = $code;
                    $existing = (new GradeLevelModel())
                        ->where('unit_id', $unit['id'])
                        ->groupStart()->where('code', $code)->orWhere('grade_number', $gradeNumber)->groupEnd()
                        ->first();
                    if ($existing) {
                        $proposedAction = 'UPDATE';
                        $targetEntityId = (int) $existing['id'];
                        $messages[] = 'Tingkat dengan kode atau nomor yang sama sudah ada dan akan diperbarui.';
                        if ($status !== 'ERROR') {
                            $status = 'WARNING';
                        }
                    }
                }
                $normData['sort_order'] = ($row['sort_order'] ?? '') === '' ? (int) ($gradeNumber ?: 0) : (int) $row['sort_order'];
                $active = self::normalizeBoolean($row['active'] ?? '', 1);
                if ($active === null) {
                    $messages[] = 'active harus bernilai 1 atau 0.';
                    $status = 'ERROR';
                } else {
                    $normData['is_active'] = $active;
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
                $semester = filter_var($row['semester'] ?? null, FILTER_VALIDATE_INT);
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
                    $existing = (new ClassroomModel())
                        ->where('academic_period_id', $period['id'])
                        ->where('unit_id', $unit['id'])
                        ->where('code', strtoupper(trim($row['code'] ?? '')))
                        ->where('deleted_at IS NULL')
                        ->first();
                    if ($existing) {
                        $proposedAction = 'UPDATE';
                        $targetEntityId = (int) $existing['id'];
                        $messages[] = 'Kode kelas sudah ada pada unit dan periode tersebut; data akan diperbarui.';
                        if ($status !== 'ERROR') {
                            $status = 'WARNING';
                        }
                    }
                }
                if ($semester === false || !in_array((int) $semester, [1, 2], true)) {
                    $messages[] = 'semester harus bernilai 1 atau 2.';
                    $status = 'ERROR';
                }
                if (($row['capacity'] ?? '') !== '' && (!preg_match('/^\d+$/', trim($row['capacity'])) || (int) $row['capacity'] < 0)) {
                    $messages[] = 'capacity harus berupa angka bulat tidak negatif.';
                    $status = 'ERROR';
                }
                $normData['capacity'] = ($row['capacity'] ?? '') === '' ? null : (int) $row['capacity'];
                $active = self::normalizeBoolean($row['active'] ?? '', 1);
                if ($active === null) {
                    $messages[] = 'active harus bernilai 1 atau 0.';
                    $status = 'ERROR';
                } else {
                    $normData['is_active'] = $active;
                }
                if ($unit && $period && !empty($row['homeroom_teacher_identifier'])) {
                    $teacher = self::resolveTeacherIdentifier($row['homeroom_teacher_identifier']);
                    if (!$teacher) {
                        $messages[] = 'Identitas wali kelas tidak ditemukan atau ambigu.';
                        $status = 'ERROR';
                    } else {
                        try {
                            UnitScopeService::assertTeacherInUnit((int) $teacher['id'], (int) $unit['id'], (int) $period['id']);
                            $normData['homeroom_teacher_id'] = (int) $teacher['id'];
                        } catch (\Throwable $e) {
                            $messages[] = $e->getMessage();
                            $status = 'ERROR';
                        }
                    }
                }
                if ($unit && !empty($row['default_room_code'])) {
                    $room = (new RoomModel())->where('code', strtoupper(trim($row['default_room_code'])))->where('deleted_at IS NULL')->first();
                    if (!$room || (!(int) $room['shared_between_units'] && (int) $room['unit_id'] !== (int) $unit['id'])) {
                        $messages[] = 'Kode ruang default tidak ditemukan atau tidak tersedia pada unit tersebut.';
                        $status = 'ERROR';
                    } else {
                        $normData['default_room_id'] = (int) $room['id'];
                    }
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
                $isShared = self::normalizeBoolean($row['shared_between_units'] ?? '', null);
                $unit = !empty($row['unit']) ? self::resolveUnitCode($row['unit']) : null;
                $roomType = (new RoomTypeModel())->where('code', strtoupper(trim($row['room_type'] ?? '')))->first();
                if ($isShared === null) {
                    $messages[] = 'shared_between_units wajib bernilai 1 atau 0.';
                    $status = 'ERROR';
                    $isShared = 0;
                }
                if ($isShared === 0 && !$unit) {
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
                if (($row['capacity'] ?? '') !== '' && (!preg_match('/^\d+$/', trim($row['capacity'])) || (int) $row['capacity'] < 0)) {
                    $messages[] = 'capacity harus berupa angka bulat tidak negatif.';
                    $status = 'ERROR';
                }
                $normData['capacity'] = ($row['capacity'] ?? '') === '' ? null : (int) $row['capacity'];
                $normData['facilities'] = self::splitTextValues($row['facilities'] ?? '');
                $active = self::normalizeBoolean($row['active'] ?? '', 1);
                if ($active === null) {
                    $messages[] = 'active harus bernilai 1 atau 0.';
                    $status = 'ERROR';
                } else {
                    $normData['is_active'] = $active;
                }
                break;

            case 'STUDENTS':
                $studentNumber = trim((string) ($row['student_number'] ?? ''));
                $fullName      = trim((string) ($row['full_name'] ?? ''));
                $unitCode      = strtoupper(trim((string) ($row['unit'] ?? '')));
                $yearName      = trim((string) ($row['academic_year'] ?? ''));
                $gradeNumber   = filter_var($row['current_grade'] ?? null, FILTER_VALIDATE_INT);
                $classCode     = trim((string) ($row['classroom_code'] ?? ''));

                if ($studentNumber === '') {
                    $messages[] = 'Nomor Induk (student_number) wajib diisi.';
                    $status = 'ERROR';
                }
                if (mb_strlen($fullName) < 2) {
                    $messages[] = 'Nama Lengkap (full_name) wajib diisi minimal 2 karakter.';
                    $status = 'ERROR';
                }
                $unit = self::resolveUnitCode($unitCode);
                if (!$unit) {
                    $messages[] = "Kode Unit '{$unitCode}' tidak ditemukan atau tidak dapat diakses.";
                    $status = 'ERROR';
                }
                $year = (new AcademicYearModel())->where('name', $yearName)->first();
                if (!$year) {
                    $messages[] = "Tahun Pelajaran '{$yearName}' tidak ditemukan.";
                    $status = 'ERROR';
                }
                if ($gradeNumber === false || $gradeNumber < 1 || $gradeNumber > 12) {
                    $messages[] = 'current_grade harus berupa angka 1 sampai 12.';
                    $status = 'ERROR';
                }

                $classroomId = null;
                if ($unit && $gradeNumber !== false && $classCode !== '') {
                    $db = Database::connect();
                    $cls = $db->table('classrooms c')
                        ->join('grade_levels gl', 'gl.id = c.grade_level_id')
                        ->where('c.unit_id', $unit['id'])
                        ->where('gl.grade_number', $gradeNumber)
                        ->groupStart()
                            ->where('c.code', $classCode)
                            ->orWhere('c.name', $classCode)
                        ->groupEnd()
                        ->select('c.id')->get()->getRowArray();
                    if ($cls) {
                            $classroomId = (int) $cls['id'];
                    } else {
                        $messages[] = "Rombel/Kelas '{$classCode}' tidak ditemukan untuk unit dan tingkat tersebut.";
                        if ($status !== 'ERROR') $status = 'WARNING';
                    }
                }

                if ($unit && $year && $studentNumber !== '') {
                    $db = Database::connect();
                    $userId = !empty($row['user_id']) ? (int) $row['user_id'] : null;
                    if ($userId > 0) {
                        $userExists = $db->table('users')->where('id', $userId)->get()->getRowArray();
                        if (!$userExists) {
                            $userId = null;
                        }
                    }

                    $normData['student_number']   = $studentNumber;
                    $normData['full_name']        = $fullName;
                    $normData['unit_id']          = (int) $unit['id'];
                    $normData['academic_year_id'] = (int) $year['id'];
                    $normData['current_grade']    = (int) $gradeNumber;
                    $normData['classroom_id']     = $classroomId;
                    $normData['user_id']          = $userId;

                    $existing = $db->table('elective_students')
                        ->where('unit_id', $unit['id'])
                        ->where('academic_year_id', $year['id'])
                        ->where('student_number', $studentNumber)
                        ->get()->getRowArray();
                    if ($existing) {
                        $proposedAction = 'UPDATE';
                        $targetEntityId = (int) $existing['id'];
                        $messages[] = "Peserta dengan NIS {$studentNumber} sudah ada dan akan diperbarui data Rombel/namanya.";
                        if ($status !== 'ERROR') $status = 'WARNING';
                    }
                }
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

    private static function splitTextValues(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($item) => trim($item),
            preg_split('/[,;]+/', $value) ?: []
        ))));
    }

    private static function normalizeBoolean($value, ?int $default = null): ?int
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return $default;
        }
        if (in_array($value, ['1', 'true', 'ya', 'yes', 'aktif'], true)) {
            return 1;
        }
        if (in_array($value, ['0', 'false', 'tidak', 'no', 'nonaktif', 'non-aktif'], true)) {
            return 0;
        }

        return null;
    }

    private static function normalizeDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private static function resolveTeacherIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        $matches = (new TeacherModel())
            ->groupStart()
                ->where('employee_number', $identifier)
                ->orWhere('nip', $identifier)
                ->orWhere('nik', $identifier)
            ->groupEnd()
            ->where('is_active', 1)
            ->where('deleted_at IS NULL')
            ->findAll(2);

        return count($matches) === 1 ? $matches[0] : null;
    }

    private static function templateDefinition(string $type): array
    {
        $definitions = [
            'TEACHERS' => [
                'title' => 'Master Guru',
                'columns' => [
                    'employee_number' => ['label' => 'Nomor Pegawai', 'required' => false, 'description' => 'Nomor internal sekolah. Simpan sebagai teks agar angka nol di depan tidak hilang.', 'example' => 'G-0001', 'width' => 18],
                    'nip' => ['label' => 'NIP', 'required' => false, 'description' => 'NIP unik. Simpan sebagai teks.', 'example' => '198501012010011001', 'width' => 22],
                    'nik' => ['label' => 'NIK', 'required' => false, 'description' => 'NIK unik 16 digit. Simpan sebagai teks.', 'example' => '3273010101850001', 'width' => 20],
                    'full_name' => ['label' => 'Nama Lengkap', 'required' => true, 'description' => 'Nama tanpa gelar, minimal 3 karakter.', 'example' => 'Ahmad Fauzi', 'width' => 28],
                    'title_prefix' => ['label' => 'Gelar Depan', 'required' => false, 'description' => 'Contoh: Dr., Drs., Hj.', 'example' => 'Drs.', 'width' => 14],
                    'degree_suffix' => ['label' => 'Gelar Belakang', 'required' => false, 'description' => 'Pisahkan beberapa gelar dengan koma.', 'example' => 'S.Pd., M.Pd.', 'width' => 20],
                    'gender' => ['label' => 'Jenis Kelamin', 'required' => false, 'description' => 'Pilih LAKI_LAKI atau PEREMPUAN.', 'example' => 'LAKI_LAKI', 'width' => 16, 'list' => 'GENDER'],
                    'birth_place' => ['label' => 'Tempat Lahir', 'required' => false, 'description' => 'Nama kota/kabupaten kelahiran.', 'example' => 'Bandung', 'width' => 18],
                    'birth_date' => ['label' => 'Tanggal Lahir', 'required' => false, 'description' => 'Gunakan tanggal Excel atau format YYYY-MM-DD.', 'example' => '1985-01-01', 'width' => 16, 'format' => 'date'],
                    'phone' => ['label' => 'Telepon', 'required' => false, 'description' => 'Nomor telepon aktif, sebaiknya diawali 08 atau kode negara.', 'example' => '081234567890', 'width' => 18],
                    'email' => ['label' => 'Email', 'required' => false, 'description' => 'Alamat email valid.', 'example' => 'ahmad@example.sch.id', 'width' => 28],
                    'address' => ['label' => 'Alamat', 'required' => false, 'description' => 'Alamat domisili.', 'example' => 'Jl. Pendidikan No. 1', 'width' => 34],
                    'employment_status' => ['label' => 'Status Kepegawaian', 'required' => true, 'description' => 'Pilih status kepegawaian dari referensi.', 'example' => 'GURU_TETAP', 'width' => 22, 'list' => 'EMPLOYMENT_STATUS'],
                    'employment_type' => ['label' => 'Tipe Kepegawaian', 'required' => false, 'description' => 'Pilih FULL_TIME atau PART_TIME.', 'example' => 'FULL_TIME', 'width' => 18, 'list' => 'EMPLOYMENT_TYPE'],
                    'primary_unit' => ['label' => 'Unit Utama', 'required' => true, 'description' => 'Kode unit utama yang tersedia pada sheet Referensi.', 'example' => 'SMP', 'width' => 14, 'list' => 'UNIT_CODE'],
                    'additional_units' => ['label' => 'Unit Tambahan', 'required' => false, 'description' => 'Kode unit tambahan dipisahkan koma, misalnya SMP,SMA.', 'example' => 'SMA', 'width' => 20],
                    'hire_date' => ['label' => 'Tanggal Mulai', 'required' => false, 'description' => 'Gunakan tanggal Excel atau format YYYY-MM-DD.', 'example' => '2010-07-01', 'width' => 16, 'format' => 'date'],
                    'notes' => ['label' => 'Catatan', 'required' => false, 'description' => 'Catatan administratif singkat.', 'example' => 'Guru matematika', 'width' => 30],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif. Kosong dianggap aktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
            'SUBJECTS' => [
                'title' => 'Master Mata Pelajaran',
                'columns' => [
                    'code' => ['label' => 'Kode Mapel', 'required' => true, 'description' => 'Kode unik global, tanpa spasi. Kode yang sudah ada akan diperbarui.', 'example' => 'MAT-SMP', 'width' => 18],
                    'name' => ['label' => 'Nama Mapel', 'required' => true, 'description' => 'Nama lengkap mata pelajaran.', 'example' => 'Matematika', 'width' => 30],
                    'short_name' => ['label' => 'Nama Singkat', 'required' => false, 'description' => 'Nama singkat untuk tampilan jadwal.', 'example' => 'MAT', 'width' => 16],
                    'category' => ['label' => 'Kategori', 'required' => true, 'description' => 'Pilih kategori resmi dari referensi.', 'example' => 'WAJIB', 'width' => 22, 'list' => 'SUBJECT_CATEGORY'],
                    'counts_in_report' => ['label' => 'Masuk Rapor', 'required' => false, 'description' => '1 = masuk rapor, 0 = tidak. Kosong dianggap 1.', 'example' => '1', 'width' => 14, 'list' => 'BOOLEAN'],
                    'counts_as_teaching_load' => ['label' => 'Hitung Beban Mengajar', 'required' => false, 'description' => '1 = dihitung sebagai beban mengajar, 0 = tidak.', 'example' => '1', 'width' => 22, 'list' => 'BOOLEAN'],
                    'units' => ['label' => 'Unit Tersedia', 'required' => true, 'description' => 'Satu atau beberapa kode unit dipisahkan koma.', 'example' => 'SMP,SMA', 'width' => 18],
                    'aliases' => ['label' => 'Alias', 'required' => false, 'description' => 'Nama alternatif dipisahkan koma atau titik koma.', 'example' => 'MTK;Math', 'width' => 28],
                    'default_room_type' => ['label' => 'Jenis Ruang Default', 'required' => false, 'description' => 'Kode jenis ruang default dari referensi.', 'example' => 'CLASSROOM', 'width' => 24, 'list' => 'ROOM_TYPE'],
                    'sort_order' => ['label' => 'Urutan', 'required' => false, 'description' => 'Angka urutan tampilan, minimal 0.', 'example' => '10', 'width' => 10],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif. Kosong dianggap aktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
            'GRADE_LEVELS' => [
                'title' => 'Master Tingkat Kelas',
                'columns' => [
                    'unit' => ['label' => 'Unit', 'required' => true, 'description' => 'Kode unit sekolah.', 'example' => 'SMP', 'width' => 14, 'list' => 'UNIT_CODE'],
                    'grade_number' => ['label' => 'Nomor Tingkat', 'required' => true, 'description' => 'Nomor tingkat numerik, misalnya 7 untuk VII dan 10 untuk X.', 'example' => '7', 'width' => 16],
                    'code' => ['label' => 'Kode Tingkat', 'required' => true, 'description' => 'Kode unik per unit, misalnya VII atau X.', 'example' => 'VII', 'width' => 16],
                    'name' => ['label' => 'Nama Tingkat', 'required' => true, 'description' => 'Nama tampilan tingkat kelas.', 'example' => 'Kelas VII', 'width' => 24],
                    'phase' => ['label' => 'Fase', 'required' => false, 'description' => 'Fase kurikulum, misalnya D, E, atau F.', 'example' => 'D', 'width' => 12, 'list' => 'PHASE'],
                    'sort_order' => ['label' => 'Urutan', 'required' => false, 'description' => 'Urutan tampilan. Kosong mengikuti nomor tingkat.', 'example' => '7', 'width' => 12],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
            'CLASSROOMS' => [
                'title' => 'Master Kelas / Rombel',
                'columns' => [
                    'academic_year' => ['label' => 'Tahun Pelajaran', 'required' => true, 'description' => 'Nama tahun pelajaran yang sudah tersedia.', 'example' => '2026/2027', 'width' => 18, 'list' => 'ACADEMIC_YEAR'],
                    'semester' => ['label' => 'Semester', 'required' => true, 'description' => 'Nomor semester 1 atau 2.', 'example' => '1', 'width' => 12, 'list' => 'SEMESTER'],
                    'unit' => ['label' => 'Unit', 'required' => true, 'description' => 'Kode unit sekolah.', 'example' => 'SMP', 'width' => 14, 'list' => 'UNIT_CODE'],
                    'grade' => ['label' => 'Tingkat', 'required' => true, 'description' => 'Kode tingkat sesuai unit.', 'example' => 'VII', 'width' => 14, 'list' => 'GRADE_CODE'],
                    'code' => ['label' => 'Kode Rombel', 'required' => true, 'description' => 'Kode unik pada unit dan periode.', 'example' => 'VII-A', 'width' => 16],
                    'name' => ['label' => 'Nama Rombel', 'required' => true, 'description' => 'Nama tampilan kelas/rombel.', 'example' => 'Kelas VII A', 'width' => 26],
                    'major' => ['label' => 'Jurusan', 'required' => false, 'description' => 'Jurusan untuk SMA jika relevan.', 'example' => 'MIPA', 'width' => 16, 'list' => 'MAJOR'],
                    'specialization' => ['label' => 'Peminatan', 'required' => false, 'description' => 'Keterangan peminatan atau konsentrasi.', 'example' => 'Sains', 'width' => 20],
                    'capacity' => ['label' => 'Kapasitas', 'required' => false, 'description' => 'Jumlah siswa maksimal, angka tidak negatif.', 'example' => '32', 'width' => 12],
                    'homeroom_teacher_identifier' => ['label' => 'Identitas Wali Kelas', 'required' => false, 'description' => 'NIP, NIK, atau nomor pegawai yang unik.', 'example' => 'G-0001', 'width' => 26],
                    'default_room_code' => ['label' => 'Kode Ruang Default', 'required' => false, 'description' => 'Kode ruang yang tersedia pada unit.', 'example' => 'R-101', 'width' => 22, 'list' => 'ROOM_CODE'],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
            'ROOMS' => [
                'title' => 'Master Ruangan',
                'columns' => [
                    'code' => ['label' => 'Kode Ruang', 'required' => true, 'description' => 'Kode unik global. Kode yang sudah ada akan diperbarui.', 'example' => 'R-101', 'width' => 18],
                    'name' => ['label' => 'Nama Ruang', 'required' => true, 'description' => 'Nama lengkap ruangan.', 'example' => 'Ruang Kelas 101', 'width' => 28],
                    'room_type' => ['label' => 'Jenis Ruang', 'required' => true, 'description' => 'Kode jenis ruang aktif dari referensi.', 'example' => 'CLASSROOM', 'width' => 22, 'list' => 'ROOM_TYPE'],
                    'unit' => ['label' => 'Unit', 'required' => false, 'description' => 'Wajib untuk ruang non-shared; kosong boleh untuk ruang shared.', 'example' => 'SMP', 'width' => 14, 'list' => 'UNIT_CODE'],
                    'shared_between_units' => ['label' => 'Dipakai Bersama', 'required' => true, 'description' => '1 = dapat dipakai lintas unit, 0 = khusus satu unit.', 'example' => '0', 'width' => 20, 'list' => 'BOOLEAN'],
                    'capacity' => ['label' => 'Kapasitas', 'required' => false, 'description' => 'Jumlah kursi/orang, angka tidak negatif.', 'example' => '32', 'width' => 12],
                    'location' => ['label' => 'Lokasi', 'required' => false, 'description' => 'Gedung atau area.', 'example' => 'Gedung A', 'width' => 22],
                    'floor' => ['label' => 'Lantai', 'required' => false, 'description' => 'Nomor/nama lantai.', 'example' => '1', 'width' => 12],
                    'facilities' => ['label' => 'Fasilitas', 'required' => false, 'description' => 'Daftar fasilitas dipisahkan koma.', 'example' => 'Proyektor,AC,Papan Tulis', 'width' => 34],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
            'STUDENTS' => [
                'title' => 'Master Peserta Didik',
                'columns' => [
                    'student_number' => ['label' => 'NIS / NISN', 'required' => true, 'description' => 'Nomor Induk Siswa / NISN unik.', 'example' => '2026001', 'width' => 18],
                    'full_name' => ['label' => 'Nama Lengkap Siswa', 'required' => true, 'description' => 'Nama lengkap peserta didik.', 'example' => 'Budi Santoso', 'width' => 28],
                    'unit' => ['label' => 'Unit Sekolah', 'required' => true, 'description' => 'Kode unit sekolah (SMA/SMP).', 'example' => 'SMA', 'width' => 14, 'list' => 'UNIT_CODE'],
                    'academic_year' => ['label' => 'Tahun Pelajaran', 'required' => true, 'description' => 'Tahun pelajaran aktif.', 'example' => '2026/2027', 'width' => 18, 'list' => 'ACADEMIC_YEAR'],
                    'current_grade' => ['label' => 'Tingkat Kelas', 'required' => true, 'description' => 'Angka tingkat kelas (7-12).', 'example' => '10', 'width' => 14],
                    'classroom_code' => ['label' => 'Kode Rombel', 'required' => false, 'description' => 'Kode Rombel/Kelas yang tersedia.', 'example' => 'X-1', 'width' => 16],
                    'user_id' => ['label' => 'User ID', 'required' => false, 'description' => 'ID Akun User jika sudah ada (opsional).', 'example' => '101', 'width' => 12],
                    'active' => ['label' => 'Aktif', 'required' => false, 'description' => '1 = aktif, 0 = nonaktif.', 'example' => '1', 'width' => 10, 'list' => 'BOOLEAN'],
                ],
            ],
        ];

        if (!isset($definitions[$type])) {
            throw new \InvalidArgumentException('Jenis import master tidak dikenal.');
        }

        return $definitions[$type];
    }

    private static function templateReferenceLists(): array
    {
        $db = Database::connect();
        $unitIds = session()->get('logged_in') ? UnitScopeService::accessibleUnitIds() : [];

        $unitBuilder = $db->table('school_units')->select('code')->where('is_active', 1)->orderBy('code', 'ASC');
        $gradeBuilder = $db->table('grade_levels')->select('code')->where('is_active', 1)->orderBy('sort_order', 'ASC');
        $roomBuilder = $db->table('rooms')->select('code')->where('is_active', 1)->where('deleted_at IS NULL')->orderBy('code', 'ASC');
        if ($unitIds !== []) {
            $unitBuilder->whereIn('id', $unitIds);
            $gradeBuilder->whereIn('unit_id', $unitIds);
            $roomBuilder->groupStart()->where('shared_between_units', 1)->orWhereIn('unit_id', $unitIds)->groupEnd();
        }

        return [
            'UNIT_CODE' => array_column($unitBuilder->get()->getResultArray(), 'code'),
            'GRADE_CODE' => array_column($gradeBuilder->get()->getResultArray(), 'code'),
            'ROOM_CODE' => array_column($roomBuilder->get()->getResultArray(), 'code'),
            'ROOM_TYPE' => array_column($db->table('room_types')->select('code')->where('is_active', 1)->orderBy('code', 'ASC')->get()->getResultArray(), 'code'),
            'ACADEMIC_YEAR' => array_column($db->table('academic_years')->select('name')->orderBy('start_date', 'DESC')->get()->getResultArray(), 'name'),
            'BOOLEAN' => ['1', '0'],
            'SEMESTER' => ['1', '2'],
            'GENDER' => ['LAKI_LAKI', 'PEREMPUAN'],
            'EMPLOYMENT_STATUS' => ['GURU_TETAP', 'GURU_HONORER', 'PNS', 'PPPK', 'DPK', 'KONTRAK'],
            'EMPLOYMENT_TYPE' => ['FULL_TIME', 'PART_TIME'],
            'SUBJECT_CATEGORY' => SubjectService::CATEGORIES,
            'PHASE' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'MAJOR' => ['MIPA', 'IPS', 'BAHASA'],
        ];
    }

    private static function rowIdentity(string $type, array $row): ?string
    {
        $parts = match ($type) {
            'TEACHERS' => [
                ($row['nip'] ?? '') ?: (($row['nik'] ?? '') ?: (($row['employee_number'] ?? '') ?: strtolower(trim($row['full_name'] ?? '')))),
                strtoupper(trim($row['primary_unit'] ?? '')),
            ],
            'SUBJECTS', 'ROOMS' => [strtoupper(trim($row['code'] ?? ''))],
            'GRADE_LEVELS' => [strtoupper(trim($row['unit'] ?? '')), strtoupper(trim($row['code'] ?? ''))],
            'CLASSROOMS' => [
                trim($row['academic_year'] ?? ''),
                trim($row['semester'] ?? ''),
                strtoupper(trim($row['unit'] ?? '')),
                strtoupper(trim($row['code'] ?? '')),
            ],
            'STUDENTS' => [
                trim($row['student_number'] ?? ''),
                strtoupper(trim($row['unit'] ?? '')),
            ],
            default => [],
        };
        if ($parts === [] || count(array_filter($parts, static fn ($part) => $part !== '')) !== count($parts)) {
            return null;
        }

        return $type . ':' . implode('|', $parts);
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

                try {
                    switch ($batch['import_type']) {
                    case 'TEACHERS':
                        if ($action === 'INSERT') {
                            $unitIds = self::authorizedUnitIds((array) ($normData['unit_ids'] ?? []));
                            TeacherService::createTeacher([
                                'full_name'         => $normData['full_name'] ?? '',
                                'nip'               => $normData['nip'] ?? null,
                                'nik'               => $normData['nik'] ?? null,
                                'employee_number'   => $normData['employee_number'] ?? null,
                                'title_prefix'      => $normData['title_prefix'] ?? null,
                                'degree_suffix'     => $normData['degree_suffix'] ?? null,
                                'gender'            => $normData['gender'] ?? null,
                                'birth_place'       => $normData['birth_place'] ?? null,
                                'birth_date'        => !empty($normData['birth_date']) ? $normData['birth_date'] : null,
                                'phone'             => $normData['phone'] ?? null,
                                'email'             => $normData['email'] ?? null,
                                'address'           => $normData['address'] ?? null,
                                'employment_status' => $normData['employment_status'] ?? 'GURU_TETAP',
                                'employment_type'   => $normData['employment_type'] ?? null,
                                'hire_date'         => !empty($normData['hire_date']) ? $normData['hire_date'] : null,
                                'primary_unit_id'    => $normData['primary_unit_id'] ?? null,
                                'notes'             => $normData['notes'] ?? null,
                                'is_active'         => $normData['is_active'] ?? 1,
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
                                    'code'       => $code,
                                    'name'       => $normData['name'] ?? $existing['name'],
                                    'category'   => $normData['category'] ?? $existing['category'],
                                    'short_name' => $normData['short_name'] ?? $existing['short_name'],
                                    'counts_in_report' => $normData['counts_in_report'] ?? $existing['counts_in_report'],
                                    'counts_as_teaching_load' => $normData['counts_as_teaching_load'] ?? $existing['counts_as_teaching_load'],
                                    'default_room_type_id' => $normData['default_room_type_id'] ?? $existing['default_room_type_id'],
                                    'sort_order' => $normData['sort_order'] ?? $existing['sort_order'],
                                    'is_active' => $normData['is_active'] ?? $existing['is_active'],
                                    'revision_number' => $existing['revision_number'],
                                ], $unitIds, (array) ($normData['alias_list'] ?? []));
                            } else {
                                $unitIds = self::authorizedUnitIds((array) ($normData['unit_ids'] ?? []));
                                SubjectService::createSubject([
                                    'code'                    => $code,
                                    'name'                    => $normData['name'] ?? '',
                                    'short_name'              => $normData['short_name'] ?? ($normData['code'] ?? ''),
                                    'category'                => $normData['category'] ?? 'WAJIB',
                                    'counts_in_report'        => $normData['counts_in_report'] ?? 1,
                                    'counts_as_teaching_load' => $normData['counts_as_teaching_load'] ?? 1,
                                    'default_room_type_id'    => $normData['default_room_type_id'] ?? null,
                                    'sort_order'              => $normData['sort_order'] ?? 0,
                                    'is_active'               => $normData['is_active'] ?? 1,
                                ], $unitIds, (array) ($normData['alias_list'] ?? []));
                            }
                            $appliedCount++;
                        }
                        break;

                    case 'GRADE_LEVELS':
                        if ($action === 'INSERT' || $action === 'UPDATE') {
                            if (session()->get('logged_in')) {
                                UnitScopeService::assertUnit((int) ($normData['unit_id'] ?? 0));
                            }
                            $gradeModel = new GradeLevelModel();
                            $existing = !empty($r['target_entity_id']) ? $gradeModel->find($r['target_entity_id']) : null;
                            if ($existing) {
                                GradeLevelService::updateGradeLevel($existing['uuid'], [
                                    'name' => $normData['name'] ?? $existing['name'],
                                    'phase' => $normData['phase'] ?? $existing['phase'],
                                    'sort_order' => $normData['sort_order'] ?? $existing['sort_order'],
                                    'is_active' => $normData['is_active'] ?? $existing['is_active'],
                                ]);
                            } else {
                                $gradeModel->insert([
                                    'unit_id' => (int) $normData['unit_id'],
                                    'grade_number' => (int) $normData['grade_number'],
                                    'code' => strtoupper(trim($normData['code'] ?? '')),
                                    'name' => $normData['name'] ?? '',
                                    'phase' => ($normData['phase'] ?? '') !== '' ? $normData['phase'] : null,
                                    'sort_order' => (int) ($normData['sort_order'] ?? $normData['grade_number']),
                                    'is_active' => $normData['is_active'] ?? 1,
                                    'created_by' => session()->get('user_id'),
                                ]);
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
                                    'capacity'             => $normData['capacity'] !== null ? (int) $normData['capacity'] : 30,
                                    'location'             => $normData['location'] ?? null,
                                    'floor'                => $normData['floor'] ?? null,
                                    'facilities'           => (array) ($normData['facilities'] ?? []),
                                    'is_active'            => $normData['is_active'] ?? 1,
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
                                    'capacity'             => $normData['capacity'] !== null ? (int) $normData['capacity'] : $existing['capacity'],
                                    'location'             => $normData['location'] ?? $existing['location'],
                                    'floor'                => $normData['floor'] ?? $existing['floor'],
                                    'facilities'           => (array) ($normData['facilities'] ?? []),
                                    'is_active'            => $normData['is_active'] ?? $existing['is_active'],
                                    'revision_number'      => $existing['revision_number'],
                                ]);
                                $appliedCount++;
                            }
                        }
                        break;

                    case 'CLASSROOMS':
                        if ($action === 'INSERT' || $action === 'UPDATE') {
                            if (session()->get('logged_in')) {
                                UnitScopeService::assertUnit((int) ($normData['unit_id'] ?? 0));
                            }
                            $payload = [
                                'academic_period_id' => $normData['academic_period_id'] ?? null,
                                'unit_id'            => $normData['unit_id'] ?? null,
                                'grade_level_id'     => $normData['grade_level_id'] ?? null,
                                'code'               => $normData['code'] ?? '',
                                'name'               => $normData['name'] ?? '',
                                'major'              => $normData['major'] ?? null,
                                'specialization'     => $normData['specialization'] ?? null,
                                'capacity'           => $normData['capacity'] !== null ? (int) $normData['capacity'] : null,
                                'homeroom_teacher_id'=> $normData['homeroom_teacher_id'] ?? null,
                                'default_room_id'    => $normData['default_room_id'] ?? null,
                                'is_active'          => $normData['is_active'] ?? 1,
                            ];
                            $existing = !empty($r['target_entity_id']) ? (new ClassroomModel())->find($r['target_entity_id']) : null;
                            if ($existing) {
                                $payload['revision_number'] = $existing['revision_number'];
                                ClassroomService::updateClassroom($existing['uuid'], $payload);
                            } else {
                                ClassroomService::createClassroom($payload);
                            }
                            $appliedCount++;
                        }
                        break;

                    case 'STUDENTS':
                        if ($action === 'INSERT' || $action === 'UPDATE') {
                            $userId = !empty($normData['user_id']) ? (int) $normData['user_id'] : null;
                            if ($userId > 0) {
                                $userExists = $db->table('users')->where('id', $userId)->get()->getRowArray();
                                if (!$userExists) {
                                    $userId = null;
                                }
                            }

                            $existing = !empty($r['target_entity_id'])
                                ? $db->table('elective_students')->where('id', $r['target_entity_id'])->get()->getRowArray()
                                : null;
                            if ($existing) {
                                $db->table('elective_students')->where('id', $existing['id'])->update([
                                    'full_name'    => $normData['full_name'] ?? $existing['full_name'],
                                    'user_id'      => $userId ?: $existing['user_id'],
                                    'classroom_id' => !empty($normData['classroom_id']) ? (int) $normData['classroom_id'] : $existing['classroom_id'],
                                    'updated_at'   => date('Y-m-d H:i:s'),
                                ]);
                            } else {
                                $db->table('elective_students')->insert([
                                    'uuid'             => UuidService::v4(),
                                    'user_id'          => $userId,
                                    'unit_id'          => (int) $normData['unit_id'],
                                    'academic_year_id' => (int) $normData['academic_year_id'],
                                    'student_number'   => trim((string) ($normData['student_number'] ?? '')),
                                    'full_name'        => trim((string) ($normData['full_name'] ?? '')),
                                    'current_grade'    => (int) ($normData['current_grade'] ?? 10),
                                    'classroom_id'     => !empty($normData['classroom_id']) ? (int) $normData['classroom_id'] : null,
                                    'is_active'        => 1,
                                    'created_by'       => session()->get('user_id'),
                                    'created_at'       => date('Y-m-d H:i:s'),
                                    'updated_at'       => date('Y-m-d H:i:s'),
                                ]);
                            }
                            $appliedCount++;
                        }
                        break;
                    }
                } catch (\Throwable $rowError) {
                    throw new \RuntimeException('Baris Excel ' . $r['row_number'] . ' gagal diterapkan: ' . $rowError->getMessage(), 0, $rowError);
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
