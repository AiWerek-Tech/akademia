<?php

namespace App\Services;

use App\Models\TeacherModel;
use App\Models\SubjectModel;
use App\Models\ClassroomModel;
use App\Models\RoomModel;
use App\Models\DuplicateReviewGroupModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;

class MasterExportService
{
    /**
     * Generate Excel export for specified master entity
     */
    public static function exportExcel(string $entity, array $filters = []): string
    {
        $entity = strtoupper($entity);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $exportDir = WRITEPATH . 'exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        switch ($entity) {
            case 'TEACHERS':
                $sheet->setTitle('Master Guru');
                $headers = ['No', 'NIP', 'NIK', 'NIPG', 'Nama Lengkap', 'Gelar Depan', 'Gelar Belakang', 'JK', 'Unit Utama', 'Status Pegawai', 'Profil Status', 'Status'];
                $sheet->fromArray([$headers], null, 'A1');
                $sheet->getStyle('A1:L1')->getFont()->setBold(true);

                $teachers = TeacherService::getTeachers($filters, 10000)['data'];
                $row = 2;
                foreach ($teachers as $idx => $t) {
                    $sheet->fromArray([[
                        $idx + 1,
                        $t['nip'] ?? '-',
                        $t['nik'] ?? '-',
                        $t['employee_number'] ?? '-',
                        $t['full_name'],
                        $t['title_prefix'] ?? '',
                        $t['degree_suffix'] ?? '',
                        $t['gender'] ?? '-',
                        $t['primary_unit_id'] ?? 'Semua',
                        $t['employment_status'],
                        $t['profile_status'],
                        (int)$t['is_active'] === 1 ? 'Aktif' : 'Non-Aktif',
                    ]], null, 'A' . $row);
                    $row++;
                }
                $filename = 'master_guru_' . date('Ymd_His') . '.xlsx';
                break;

            case 'SUBJECTS':
                $sheet->setTitle('Mata Pelajaran');
                $headers = ['No', 'Kode', 'Nama Mata Pelajaran', 'Nama Singkat', 'Kategori', 'Masuk Rapor', 'Dihitung Beban', 'Status'];
                $sheet->fromArray([$headers], null, 'A1');
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                $subjects = SubjectService::getSubjects($filters, 10000)['data'];
                $row = 2;
                foreach ($subjects as $idx => $s) {
                    $sheet->fromArray([[
                        $idx + 1,
                        $s['code'],
                        $s['name'],
                        $s['short_name'],
                        $s['category'],
                        (int)$s['counts_in_report'] === 1 ? 'Ya' : 'Tidak',
                        (int)$s['counts_as_teaching_load'] === 1 ? 'Ya' : 'Tidak',
                        (int)$s['is_active'] === 1 ? 'Aktif' : 'Non-Aktif',
                    ]], null, 'A' . $row);
                    $row++;
                }
                $filename = 'master_mapel_' . date('Ymd_His') . '.xlsx';
                break;

            case 'CLASSROOMS':
                $sheet->setTitle('Kelas Rombel');
                $headers = ['No', 'Periode', 'Unit', 'Tingkat', 'Kode', 'Nama Kelas', 'Jurusan', 'Kapasitas', 'Wali Kelas', 'Ruang Default', 'Status'];
                $sheet->fromArray([$headers], null, 'A1');
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);

                $classrooms = ClassroomService::getClassrooms($filters, 10000)['data'];
                $row = 2;
                foreach ($classrooms as $idx => $c) {
                    $sheet->fromArray([[
                        $idx + 1,
                        $c['period_name'] ?? '-',
                        $c['unit_code'] ?? '-',
                        $c['grade_code'] ?? '-',
                        $c['code'],
                        $c['name'],
                        $c['major'] ?? '-',
                        $c['capacity'] ?? '-',
                        $c['homeroom_teacher_name'] ?? 'Belum ditentukan',
                        $c['room_name'] ?? 'Belum ditentukan',
                        $c['status'],
                    ]], null, 'A' . $row);
                    $row++;
                }
                $filename = 'master_kelas_' . date('Ymd_His') . '.xlsx';
                break;

            case 'ROOMS':
                $sheet->setTitle('Ruang Sekolah');
                $headers = ['No', 'Kode', 'Nama Ruang', 'Jenis Ruang', 'Unit', 'Shared', 'Kapasitas', 'Lokasi', 'Lantai', 'Status'];
                $sheet->fromArray([$headers], null, 'A1');
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);

                $rooms = RoomService::getRooms($filters, 10000)['data'];
                $row = 2;
                foreach ($rooms as $idx => $r) {
                    $sheet->fromArray([[
                        $idx + 1,
                        $r['code'],
                        $r['name'],
                        $r['room_type_name'] ?? '-',
                        $r['unit_code'] ?? 'Shared',
                        (int)$r['shared_between_units'] === 1 ? 'Ya' : 'Tidak',
                        $r['capacity'] ?? '-',
                        $r['location'] ?? '-',
                        $r['floor'] ?? '-',
                        (int)$r['is_active'] === 1 ? 'Aktif' : 'Non-Aktif',
                    ]], null, 'A' . $row);
                    $row++;
                }
                $filename = 'master_ruang_' . date('Ymd_His') . '.xlsx';
                break;

            default:
                throw new \InvalidArgumentException('Entity export tidak dikenali.');
        }

        $filePath = $exportDir . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        AuditService::log('master_export', 'EXPORT_EXCEL', $entity, null, null, ['filename' => $filename], 'Export master data to Excel');

        return $filePath;
    }
}
