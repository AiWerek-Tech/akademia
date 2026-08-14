<?php

namespace App\Commands;

use App\Services\ScheduleExportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class ExportSchedulePrint extends BaseCommand
{
    protected $group = 'Akademia';
    protected $name = 'akademia:schedule-print';
    protected $description = 'Render the printable unit schedule to a standalone HTML file.';
    protected $usage = 'akademia:schedule-print <version-id> <unit-id> [--output filename.html]';

    public function run(array $params): int
    {
        $versionId = isset($params[0]) ? (int) $params[0] : 0;
        $unitId = isset($params[1]) ? (int) $params[1] : 0;
        if ($versionId <= 0 || $unitId <= 0) {
            CLI::error('Version ID dan unit ID wajib berupa angka positif.');
            return EXIT_ERROR;
        }

        $db = Database::connect();
        $context = $db->table('schedule_versions sv')
            ->select('sv.*, su.name AS unit_name, su.address AS unit_address, su.logo_path, su.logo_right_path, su.header_line_1, su.header_line_2, su.header_line_3, su.header_line_4, su.document_city, su.head_name, su.head_identifier, ap.name AS period_name, ay.name AS year_name')
            ->join('school_units su', 'su.id = sv.unit_id', 'left')
            ->join('academic_periods ap', 'ap.id = sv.academic_period_id')
            ->join('academic_years ay', 'ay.id = ap.academic_year_id')
            ->where('sv.id', $versionId)->where('sv.unit_id', $unitId)->get()->getRowArray();
        $unit = $db->table('school_units')->where('id', $unitId)->get()->getRowArray();
        if (! $context || ! $unit) {
            CLI::error('Versi jadwal dan unit tidak cocok atau tidak ditemukan.');
            return EXIT_ERROR;
        }
        $smaUnit = $db->table('school_units')->where('code', 'SMA')->get()->getRowArray();
        $html = view('schedules/reports/unit', [
            'context' => $context,
            'unit' => $unit,
            'grid' => (new ScheduleExportService())->getGridForUnit($versionId, $unitId),
            'director_name' => $smaUnit['head_name'] ?? 'MARTHEN REFASI, S.Ag',
        ]);

        $directory = WRITEPATH . 'exports';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            CLI::error('Direktori ekspor tidak dapat dibuat.');
            return EXIT_ERROR;
        }
        $requested = (string) (CLI::getOption('output') ?: "jadwal-unit-{$unitId}-versi-{$versionId}.html");
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($requested)) ?: 'jadwal.html';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $html) === false) {
            CLI::error('Berkas jadwal cetak gagal ditulis.');
            return EXIT_ERROR;
        }
        CLI::write($path, 'green');
        return EXIT_SUCCESS;
    }
}
