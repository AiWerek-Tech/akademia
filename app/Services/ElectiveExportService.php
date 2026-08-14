<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Config\Database;

/**
 * Service profesional untuk menghasilkan rekapitulasi pemilihan
 * mata pelajaran pilihan SMA (Fase F) dalam format Excel (.xlsx)
 * dengan standar layout eksekutif, terstruktur, dan siap cetak.
 */
class ElectiveExportService
{
    public function generateSpreadsheet(int $periodId, ?array $scopedClassroom = null): Spreadsheet
    {
        $db = Database::connect();

        // 1. Ambil Informasi Periode Pemilihan
        $period = $db->table('elective_periods ep')
            ->select('ep.*, ay.name AS academic_year_name, su.name AS unit_name, su.code AS unit_code')
            ->join('academic_years ay', 'ay.id = ep.academic_year_id')
            ->join('school_units su', 'su.id = ep.unit_id')
            ->where('ep.id', $periodId)
            ->get()->getRowArray();

        if (! $period) {
            throw new \RuntimeException('Periode pemilihan mata pelajaran tidak ditemukan.');
        }

        // 2. Ambil Daftar Penawaran Mapel & Guru
        $offerings = $db->table('elective_offerings eo')
            ->select('eo.*, s.code AS subject_code, s.name AS subject_name, t.full_name AS teacher_name')
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('teachers t', 't.id = eo.teacher_id', 'left')
            ->where('eo.elective_period_id', $periodId)
            ->orderBy('s.name')
            ->get()->getResultArray();

        // 3. Ambil Peserta Terdaftar
        $studentQuery = $db->table('elective_students es')
            ->select('es.*, c.code AS classroom_code, c.name AS classroom_name, t.full_name AS homeroom_teacher_name')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->join('teachers t', 't.id = c.homeroom_teacher_id', 'left')
            ->where('es.unit_id', $period['unit_id'])
            ->where('es.academic_year_id', $period['academic_year_id'])
            ->where('es.current_grade', $period['source_grade'])
            ->where('es.is_active', 1);

        if ($scopedClassroom && isset($scopedClassroom['classroom_id'])) {
            $studentQuery->where('es.classroom_id', $scopedClassroom['classroom_id']);
        }

        $students = $studentQuery->orderBy('c.code')->orderBy('es.full_name')->get()->getResultArray();

        // 4. Ambil Submissions & Map Pilihan
        $submissionsQuery = $db->table('student_elective_submissions ses')
            ->select('ses.*, es.student_number, es.full_name, es.classroom_id')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->where('ses.elective_period_id', $periodId);

        if ($scopedClassroom && isset($scopedClassroom['classroom_id'])) {
            $submissionsQuery->where('es.classroom_id', $scopedClassroom['classroom_id']);
        }

        $submissionsRaw = $submissionsQuery->get()->getResultArray();
        $submissionsMap = [];
        foreach ($submissionsRaw as $sub) {
            $submissionsMap[(int) $sub['student_id']] = $sub;
        }

        // Ambil Pilihan Detail
        $choicesRaw = $db->table('student_elective_choices sec')
            ->select('sec.*, ses.student_id, ses.status AS submission_status, es.student_number, es.full_name, c.code AS classroom_code, c.name AS classroom_name, eo.subject_id, s.code AS subject_code, s.name AS subject_name, t.full_name AS teacher_name')
            ->join('student_elective_submissions ses', 'ses.id = sec.submission_id')
            ->join('elective_students es', 'es.id = ses.student_id')
            ->join('elective_offerings eo', 'eo.id = sec.offering_id')
            ->join('subjects s', 's.id = eo.subject_id')
            ->join('teachers t', 't.id = eo.teacher_id', 'left')
            ->join('classrooms c', 'c.id = es.classroom_id', 'left')
            ->where('ses.elective_period_id', $periodId)
            ->orderBy('c.code')
            ->orderBy('es.full_name')
            ->orderBy('sec.choice_type')
            ->orderBy('sec.priority_order')
            ->get()->getResultArray();

        $studentChoicesMap = [];
        $offeringInterestMap = [];

        foreach ($choicesRaw as $c) {
            $stId = (int) $c['student_id'];
            $offId = (int) $c['offering_id'];
            $type = $c['choice_type']; // PRIMARY or BACKUP
            $prio = (int) $c['priority_order'];
            $status = $c['submission_status'];

            $studentChoicesMap[$stId][$type][$prio] = $c;

            if (in_array($status, ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'], true)) {
                if (!isset($offeringInterestMap[$offId])) {
                    $offeringInterestMap[$offId] = ['PRIMARY' => 0, 'BACKUP' => 0];
                }
                $offeringInterestMap[$offId][$type] = ($offeringInterestMap[$offId][$type] ?? 0) + 1;
            }
        }

        // Ringkasan Per Kelas
        $classroomSummaryMap = [];
        foreach ($students as $st) {
            $cId = (int) ($st['classroom_id'] ?? 0);
            $cName = $st['classroom_name'] ?: 'Bukan Rombel';
            $cCode = $st['classroom_code'] ?: '-';
            $wName = $st['homeroom_teacher_name'] ?: '-';

            if (!isset($classroomSummaryMap[$cId])) {
                $classroomSummaryMap[$cId] = [
                    'code' => $cCode,
                    'name' => $cName,
                    'homeroom' => $wName,
                    'total_students' => 0,
                    'submitted' => 0,
                    'draft' => 0,
                ];
            }

            $classroomSummaryMap[$cId]['total_students']++;
            $stSub = $submissionsMap[(int) $st['id']] ?? null;
            if ($stSub && in_array($stSub['status'], ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'], true)) {
                $classroomSummaryMap[$cId]['submitted']++;
            } else {
                $classroomSummaryMap[$cId]['draft']++;
            }
        }

        // Inisialisasi Workbook
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        // Sheet 1: Ringkasan & Analisis Minat
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan & Analisis Minat');
        $this->buildExecutiveSummarySheet($sheet1, $period, $offerings, $students, $submissionsMap, $offeringInterestMap, $classroomSummaryMap);

        // Sheet 2: Matriks Pilihan Siswa
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Matriks Pilihan Siswa');
        $this->buildStudentMatrixSheet($sheet2, $period, $offerings, $students, $submissionsMap, $studentChoicesMap);

        // Sheet 3: Data Raw Pilihan
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Data Raw Pilihan');
        $this->buildRawDataSheet($sheet3, $period, $choicesRaw);

        // Set Active Sheet kembali ke Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buildExecutiveSummarySheet(
        Worksheet $sheet,
        array $period,
        array $offerings,
        array $students,
        array $submissionsMap,
        array $offeringInterestMap,
        array $classroomSummaryMap
    ): void {
        $sheet->setShowGridLines(true);

        // --- BANNER HEADER ---
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', 'REKAPITULASI SELEKSI MATA PELAJARAN PILIHAN (FASE F SMA)');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->mergeCells('A2:K2');
        $subHeader = sprintf(
            'Unit: %s | Tahun Pelajaran: %s | Skema Pemilihan: Kelas %s → Kelas %s | Tanggal Cetak: %s',
            $period['unit_name'],
            $period['academic_year_name'],
            $period['source_grade'],
            $period['target_grade'],
            date('d-m-Y H:i')
        );
        $sheet->setCellValue('A2', $subHeader);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('F8FAFC'));
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // --- KPI SUMMARY METRICS (Baris 4-5) ---
        $totalStudents = count($students);
        $totalSubmitted = 0;
        foreach ($submissionsMap as $sub) {
            if (in_array($sub['status'], ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'], true)) {
                $totalSubmitted++;
            }
        }
        $totalDraft = $totalStudents - $totalSubmitted;
        $completionPct = $totalStudents > 0 ? ($totalSubmitted / $totalStudents) : 0;
        $totalOfferings = count($offerings);

        // Build Metric Cards
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('A4', 'TOTAL PESERTA');
        $sheet->mergeCells('A5:B5');
        $sheet->setCellValue('A5', $totalStudents);

        $sheet->mergeCells('C4:D4');
        $sheet->setCellValue('C4', 'SUDAH MENGIRIM');
        $sheet->mergeCells('C5:D5');
        $sheet->setCellValue('C5', $totalSubmitted);

        $sheet->mergeCells('E4:F4');
        $sheet->setCellValue('E4', 'BELUM MENGIRIM');
        $sheet->mergeCells('E5:F5');
        $sheet->setCellValue('E5', $totalDraft);

        $sheet->mergeCells('G4:H4');
        $sheet->setCellValue('G4', 'PERSENTASE SELESAI');
        $sheet->mergeCells('G5:H5');
        $sheet->setCellValue('G5', $completionPct);
        $sheet->getStyle('G5')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

        $sheet->mergeCells('I4:K4');
        $sheet->setCellValue('I4', 'MAPEL DITAWARKAN');
        $sheet->mergeCells('I5:K5');
        $sheet->setCellValue('I5', $totalOfferings . ' Mapel');

        // Style Metric Cards
        $metricRangesLabel = ['A4:B4', 'C4:D4', 'E4:F4', 'G4:H4', 'I4:K4'];
        $metricRangesValue = ['A5:B5', 'C5:D5', 'E5:F5', 'G5:H5', 'I5:K5'];

        foreach ($metricRangesLabel as $range) {
            $sheet->getStyle($range)->getFont()->setSize(9)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));
            $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        foreach ($metricRangesValue as $range) {
            $sheet->getStyle($range)->getFont()->setSize(14)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E293B'));
            $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getStyle('A4:K5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // --- TABEL 1: REKAPITULASI MINAT MAPEL PILIHAN ---
        $rowIdx = 7;
        $sheet->mergeCells("A{$rowIdx}:K{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", '1. REKAPITULASI MINAT & KELAYAKAN MATA PELAJARAN PILIHAN');
        $sheet->getStyle("A{$rowIdx}")->getFont()->setSize(11)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E3A8A'));

        $rowIdx++;
        $headersTable1 = [
            'No.', 'Kode Mapel', 'Nama Mata Pelajaran Pilihan', 'Guru Pengampu',
            'Kuota Min', 'Kuota Max', 'Peminat Utama (P1)', 'Peminat Cadangan (P2)',
            'Total Peminat', 'Rasio Kuota Utama (%)', 'Status Kelayakan'
        ];
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        foreach ($headersTable1 as $i => $hText) {
            $cellRef = $colLetters[$i] . $rowIdx;
            $sheet->setCellValue($cellRef, $hText);
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
            $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        }
        $sheet->getRowDimension($rowIdx)->setRowHeight(28);

        $table1StartRow = $rowIdx + 1;
        $rowIdx++;

        foreach ($offerings as $no => $offering) {
            $offId = (int) $offering['id'];
            $pCount = $offeringInterestMap[$offId]['PRIMARY'] ?? 0;
            $bCount = $offeringInterestMap[$offId]['BACKUP'] ?? 0;
            $tCount = $pCount + $bCount;
            $maxQuota = (int) $offering['maximum_students'];
            $minQuota = (int) $offering['minimum_students'];
            $ratio = $maxQuota > 0 ? ($pCount / $maxQuota) : 0;

            $statusText = 'LAYAK DIBUKA';
            $statusBg = 'FFDCFCE7'; // Light Green
            $statusFg = 'FF15803D'; // Dark Green

            if ($pCount > $maxQuota) {
                $statusText = 'KUOTA TERLAMPAUI';
                $statusBg = 'FFFEE2E2'; // Light Red
                $statusFg = 'FFB91C1C'; // Dark Red
            } elseif ($pCount < $minQuota) {
                $statusText = 'BELUM MINIMUM';
                $statusBg = 'FFFEF3C7'; // Light Yellow
                $statusFg = 'FFB45309'; // Dark Amber
            }

            $bgZebra = ($no % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';

            $sheet->setCellValue('A' . $rowIdx, $no + 1);
            $sheet->setCellValue('B' . $rowIdx, $offering['subject_code']);
            $sheet->setCellValue('C' . $rowIdx, $offering['subject_name']);
            $sheet->setCellValue('D' . $rowIdx, $offering['teacher_name'] ?: '(Belum Ditentukan)');
            $sheet->setCellValue('E' . $rowIdx, $minQuota);
            $sheet->setCellValue('F' . $rowIdx, $maxQuota);
            $sheet->setCellValue('G' . $rowIdx, $pCount);
            $sheet->setCellValue('H' . $rowIdx, $bCount);
            $sheet->setCellValue('I' . $rowIdx, $tCount);
            $sheet->setCellValue('J' . $rowIdx, $ratio);
            $sheet->getStyle('J' . $rowIdx)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $sheet->setCellValue('K' . $rowIdx, $statusText);

            // Row Styling
            $sheet->getStyle("A{$rowIdx}:K{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgZebra);
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowIdx}:J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Custom Status Pill Style
            $sheet->getStyle('K' . $rowIdx)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($statusFg));
            $sheet->getStyle('K' . $rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($statusBg);

            $rowIdx++;
        }

        $table1EndRow = $rowIdx - 1;

        // Total Summary Row Table 1
        if ($table1EndRow >= $table1StartRow) {
            $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
            $sheet->setCellValue("A{$rowIdx}", 'TOTAL / RATA-RATA');
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$rowIdx}:K{$rowIdx}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowIdx}:K{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

            $sheet->setCellValue("E{$rowIdx}", "=SUM(E{$table1StartRow}:E{$table1EndRow})");
            $sheet->setCellValue("F{$rowIdx}", "=SUM(F{$table1StartRow}:F{$table1EndRow})");
            $sheet->setCellValue("G{$rowIdx}", "=SUM(G{$table1StartRow}:G{$table1EndRow})");
            $sheet->setCellValue("H{$rowIdx}", "=SUM(H{$table1StartRow}:H{$table1EndRow})");
            $sheet->setCellValue("I{$rowIdx}", "=SUM(I{$table1StartRow}:I{$table1EndRow})");
            $sheet->setCellValue("J{$rowIdx}", "=AVERAGE(J{$table1StartRow}:J{$table1EndRow})");
            $sheet->getStyle("J{$rowIdx}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $sheet->getStyle("E{$rowIdx}:J{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("A{$table1StartRow}:K{$rowIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // --- TABEL 2: REKAPITULASI PARTISIPASI PER KELAS / ROMBEL ---
        $rowIdx += 3;
        $sheet->mergeCells("A{$rowIdx}:H{$rowIdx}");
        $sheet->setCellValue("A{$rowIdx}", '2. REKAPITULASI PARTISIPASI PEMILIHAN PER ROMBEL / KELAS');
        $sheet->getStyle("A{$rowIdx}")->getFont()->setSize(11)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E3A8A'));

        $rowIdx++;
        $headersTable2 = ['No.', 'Kode Kelas', 'Nama Kelas / Rombel', 'Wali Kelas', 'Total Siswa', 'Sudah Mengirim', 'Belum Mengirim', 'Kelengkapan (%)'];
        foreach ($headersTable2 as $i => $hText) {
            $cellRef = $colLetters[$i] . $rowIdx;
            $sheet->setCellValue($cellRef, $hText);
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cellRef)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
            $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension($rowIdx)->setRowHeight(26);

        $table2StartRow = $rowIdx + 1;
        $rowIdx++;

        $cNo = 1;
        foreach ($classroomSummaryMap as $cSummary) {
            $tot = $cSummary['total_students'];
            $sub = $cSummary['submitted'];
            $drf = $cSummary['draft'];
            $pct = $tot > 0 ? ($sub / $tot) : 0;
            $bgZebra = ($cNo % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';

            $sheet->setCellValue('A' . $rowIdx, $cNo++);
            $sheet->setCellValue('B' . $rowIdx, $cSummary['code']);
            $sheet->setCellValue('C' . $rowIdx, $cSummary['name']);
            $sheet->setCellValue('D' . $rowIdx, $cSummary['homeroom']);
            $sheet->setCellValue('E' . $rowIdx, $tot);
            $sheet->setCellValue('F' . $rowIdx, $sub);
            $sheet->setCellValue('G' . $rowIdx, $drf);
            $sheet->setCellValue('H' . $rowIdx, $pct);
            $sheet->getStyle('H' . $rowIdx)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

            $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgZebra);
            $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowIdx}:H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowIdx++;
        }

        $table2EndRow = $rowIdx - 1;

        // Total Summary Row Table 2
        if ($table2EndRow >= $table2StartRow) {
            $sheet->mergeCells("A{$rowIdx}:D{$rowIdx}");
            $sheet->setCellValue("A{$rowIdx}", 'TOTAL KESELURUHAN');
            $sheet->getStyle("A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowIdx}:H{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

            $sheet->setCellValue("E{$rowIdx}", "=SUM(E{$table2StartRow}:E{$table2EndRow})");
            $sheet->setCellValue("F{$rowIdx}", "=SUM(F{$table2StartRow}:F{$table2EndRow})");
            $sheet->setCellValue("G{$rowIdx}", "=SUM(G{$table2StartRow}:G{$table2EndRow})");
            $sheet->setCellValue("H{$rowIdx}", "=IF(E{$rowIdx}>0, F{$rowIdx}/E{$rowIdx}, 0)");
            $sheet->getStyle("H{$rowIdx}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);
            $sheet->getStyle("E{$rowIdx}:H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("A{$table2StartRow}:H{$rowIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');

        // Auto-fit Column Widths
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function buildStudentMatrixSheet(
        Worksheet $sheet,
        array $period,
        array $offerings,
        array $students,
        array $submissionsMap,
        array $studentChoicesMap
    ): void {
        $sheet->setShowGridLines(true);

        // Header Banner
        $sheet->mergeCells('A1:P1');
        $sheet->setCellValue('A1', 'MATRIKS PEMILIHAN MATA PELAJARAN PILIHAN SISWA (FASE F SMA)');
        $sheet->getStyle('A1')->getFont()->setSize(13)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E'); // Dark Teal
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:P2');
        $sheet->setCellValue('A2', 'Periode: ' . $period['title'] . ' | Skema: Kelas ' . $period['source_grade'] . ' → ' . $period['target_grade'] . ' | Unit: ' . $period['unit_name']);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCFBF1'));
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF115E59');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Header Columns (Baris 4)
        $maxPrimary = max(4, (int) ($period['max_primary_choices'] ?? 5));
        $maxBackup  = max(1, (int) ($period['max_backup_choices'] ?? 2));

        $headers = ['No.', 'Nomor Induk', 'Nama Lengkap Siswa', 'Kelas / Rombel', 'Status Pengajuan'];
        for ($p = 1; $p <= $maxPrimary; $p++) {
            $headers[] = "Pilihan Utama {$p}";
        }
        for ($b = 1; $b <= $maxBackup; $b++) {
            $headers[] = "Pilihan Cadangan {$b}";
        }
        array_push($headers, 'Cita-Cita / Rencana Karir', 'Program Studi Tujuan', 'Alasan Pemilihan', 'Waktu Kirim');

        $rowIdx = 4;
        $colIdx = 1;
        foreach ($headers as $hText) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . $rowIdx;
            $sheet->setCellValue($cell, $hText);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $colIdx++;
        }
        $sheet->getRowDimension($rowIdx)->setRowHeight(28);

        // Populate Data Rows
        $startRow = 5;
        $rowIdx = $startRow;

        foreach ($students as $idx => $st) {
            $stId = (int) $st['id'];
            $sub = $submissionsMap[$stId] ?? null;
            $status = $sub ? $sub['status'] : 'BELUM MEMILIH';
            $isSubmitted = in_array($status, ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'], true);

            $bgZebra = ($idx % 2 === 0) ? 'FFFFFFFF' : 'FFF8FAFC';

            $sheet->setCellValue('A' . $rowIdx, $idx + 1);
            $sheet->setCellValue('B' . $rowIdx, $st['student_number']);
            $sheet->setCellValue('C' . $rowIdx, $st['full_name']);
            $sheet->setCellValue('D' . $rowIdx, $st['classroom_name'] ?: 'Bukan Rombel');
            $sheet->setCellValue('E' . $rowIdx, $status);

            $curCol = 6;
            // Primary Choices
            for ($p = 1; $p <= $maxPrimary; $p++) {
                $colName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);
                $choice = $studentChoicesMap[$stId]['PRIMARY'][$p] ?? null;
                $sheet->setCellValue($colName . $rowIdx, $choice ? ($choice['subject_code'] . ' - ' . $choice['subject_name']) : '-');
            }

            // Backup Choices
            for ($b = 1; $b <= $maxBackup; $b++) {
                $colName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);
                $choice = $studentChoicesMap[$stId]['BACKUP'][$b] ?? null;
                $sheet->setCellValue($colName . $rowIdx, $choice ? ($choice['subject_code'] . ' - ' . $choice['subject_name']) : '-');
            }

            // Career & Notes
            $colCareer = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);
            $colMajor  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);
            $colReason = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);
            $colTime   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol++);

            $sheet->setCellValue($colCareer . $rowIdx, $sub['career_plan'] ?? '-');
            $sheet->setCellValue($colMajor . $rowIdx, $sub['intended_major'] ?? '-');
            $sheet->setCellValue($colReason . $rowIdx, $sub['selection_reason'] ?? '-');
            $sheet->setCellValue($colTime . $rowIdx, !empty($sub['submitted_at']) ? date('d-m-Y H:i', strtotime($sub['submitted_at'])) : '-');

            // Apply Row Styling
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($curCol - 1);
            $sheet->getStyle("A{$rowIdx}:{$lastColLetter}{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgZebra);

            // Alignment
            $sheet->getStyle("A{$rowIdx}:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colTime}{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Status Styling
            if ($isSubmitted) {
                $sheet->getStyle("E{$rowIdx}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('15803D'));
                $sheet->getStyle("E{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('DCFCE7');
            } else {
                $sheet->getStyle("E{$rowIdx}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309'));
                $sheet->getStyle("E{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FEF3C7');
            }

            $rowIdx++;
        }

        $endRow = $rowIdx - 1;
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        if ($endRow >= $startRow) {
            $sheet->getStyle("A{$startRow}:{$lastColLetter}{$endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        }

        // Freeze Panes on D5 (Keep Columns A-C and Rows 1-4 pinned)
        $sheet->freezePane('D5');

        // Auto-fit Column Widths
        for ($c = 1; $c <= count($headers); $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    private function buildRawDataSheet(Worksheet $sheet, array $period, array $choicesRaw): void
    {
        $sheet->setShowGridLines(true);

        $headers = [
            'ID Periode', 'Judul Periode', 'Kelas Asal', 'Kelas Tujuan',
            'Nomor Induk', 'Nama Siswa', 'Kode Rombel', 'Nama Rombel',
            'Status Pengajuan', 'Jenis Pilihan', 'Prioritas',
            'Kode Mapel', 'Nama Mapel', 'Guru Pengampu', 'Status Alokasi'
        ];

        $colIdx = 1;
        foreach ($headers as $hText) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . '1';
            $sheet->setCellValue($cell, $hText);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $colIdx++;
        }
        $sheet->getRowDimension(1)->setRowHeight(26);

        $rowIdx = 2;
        foreach ($choicesRaw as $c) {
            $sheet->setCellValue('A' . $rowIdx, $period['id']);
            $sheet->setCellValue('B' . $rowIdx, $period['title']);
            $sheet->setCellValue('C' . $rowIdx, $period['source_grade']);
            $sheet->setCellValue('D' . $rowIdx, $period['target_grade']);
            $sheet->setCellValue('E' . $rowIdx, $c['student_number']);
            $sheet->setCellValue('F' . $rowIdx, $c['full_name']);
            $sheet->setCellValue('G' . $rowIdx, $c['classroom_code'] ?: '');
            $sheet->setCellValue('H' . $rowIdx, $c['classroom_name'] ?: '');
            $sheet->setCellValue('I' . $rowIdx, $c['submission_status']);
            $sheet->setCellValue('J' . $rowIdx, $c['choice_type']);
            $sheet->setCellValue('K' . $rowIdx, $c['priority_order']);
            $sheet->setCellValue('L' . $rowIdx, $c['subject_code']);
            $sheet->setCellValue('M' . $rowIdx, $c['subject_name']);
            $sheet->setCellValue('N' . $rowIdx, $c['teacher_name'] ?: '');
            $sheet->setCellValue('O' . $rowIdx, $c['allocation_status'] ?: 'ALLOCATED');

            $rowIdx++;
        }

        $endRow = $rowIdx - 1;
        if ($endRow >= 2) {
            $sheet->getStyle("A2:O{$endRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        }

        for ($c = 1; $c <= count($headers); $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}
