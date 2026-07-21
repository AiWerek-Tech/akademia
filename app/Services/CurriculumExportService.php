<?php

namespace App\Services;

use App\Models\CurriculumVersionModel;
use App\Services\CurriculumStructureService;
use App\Services\CurriculumReconciliationService;
use App\Services\CurriculumValidationService;
use App\Services\AuditService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CurriculumExportService
{
    /**
     * Export complete curriculum structure & reconciliation data to Excel
     */
    public static function exportExcel(string $versionUuid): string
    {
        $versionModel = new CurriculumVersionModel();
        $version = $versionModel->where('uuid', $versionUuid)->first();
        if (!$version) {
            throw new \InvalidArgumentException('Versi kurikulum tidak ditemukan.');
        }

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Structure Matrix
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Struktur Kurikulum');

        $headers1 = ['No', 'Unit', 'Tingkat', 'Kelas Override', 'Kode Mapel', 'Nama Mata Pelajaran', 'Kategori', 'Jam Resmi', 'Jam Custom', 'Jam Manual', 'Jam Efektif', 'Sumber', 'Masuk Rapor', 'Beban Mengajar', 'Ruangan', 'Alasan Penyesuaian'];
        $sheet1->fromArray([$headers1], null, 'A1');
        $sheet1->getStyle('A1:P1')->getFont()->setBold(true);

        $structures = CurriculumStructureService::getStructures($version['id'])['data'];
        $row = 2;
        foreach ($structures as $idx => $s) {
            $sheet1->fromArray([[
                $idx + 1,
                $s['unit_code'] ?? '-',
                $s['grade_code'] ?? '-',
                $s['classroom_name'] ?? 'Default Tingkat',
                $s['subject_code'] ?? '-',
                $s['subject_name'] ?? '-',
                $s['category'],
                $s['official_weekly_hours'] ?? 0,
                $s['custom_weekly_hours'] ?? 0,
                $s['manual_weekly_hours'] ?? 0,
                $s['effective_weekly_hours'],
                $s['effective_source'],
                (int)$s['counts_in_report'] === 1 ? 'Ya' : 'Tidak',
                (int)$s['counts_as_teaching_load'] === 1 ? 'Ya' : 'Tidak',
                $s['room_type_name'] ?? 'Standar',
                $s['adjustment_reason'] ?? '-',
            ]], null, 'A' . $row);
            $row++;
        }

        // Sheet 2: Reconciliation Summary
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rekonsiliasi JP');

        $headers2 = ['Unit', 'Tingkat', 'Jumlah Mapel', 'Total Official JP', 'Total Custom JP', 'Total Manual JP', 'Total Efektif JP', 'Jumlah Warning', 'Jumlah Error', 'Unresolved Rows', 'Status Rekonsiliasi'];
        $sheet2->fromArray([$headers2], null, 'A1');
        $sheet2->getStyle('A1:K1')->getFont()->setBold(true);

        $recon = CurriculumReconciliationService::reconcileVersion($version['id']);
        $row = 2;
        foreach ($recon['reconciliation'] as $r) {
            $sheet2->fromArray([[
                $r['unit_code'],
                $r['grade_code'] . ' - ' . $r['grade_name'],
                $r['subject_count'],
                $r['total_official'],
                $r['total_custom'],
                $r['total_manual'],
                $r['total_effective'],
                $r['warning_count'],
                $r['error_count'],
                $r['unresolved_rows'],
                $r['status'],
            ]], null, 'A' . $row);
            $row++;
        }

        $exportDir = WRITEPATH . 'exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = 'curriculum_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $version['code']) . '_' . date('Ymd_His') . '.xlsx';
        $filePath = $exportDir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        AuditService::log('curriculum', 'EXPORT_EXCEL', 'CurriculumVersion', $version['id'], null, ['filename' => $filename], 'Ekspor struktur kurikulum ke Excel');

        return $filePath;
    }
}
