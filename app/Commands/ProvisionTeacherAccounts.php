<?php

namespace App\Commands;

use App\Services\TeacherAccountProvisioningService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProvisionTeacherAccounts extends BaseCommand
{
    protected $group = 'Akademia';
    protected $name = 'akademia:provision-teacher-accounts';
    protected $description = 'Buat akun login untuk seluruh guru aktif yang belum memiliki akun.';
    protected $usage = 'akademia:provision-teacher-accounts [--output path.csv] [--refresh-pending]';
    protected $options = [
        '--output' => 'Lokasi CSV kredensial sementara.',
        '--refresh-pending' => 'Rotasi password sementara untuk akun onboarding yang belum pernah login.',
    ];

    public function run(array $params)
    {
        $result = TeacherAccountProvisioningService::provisionAll(null);
        CLI::write("Akun baru: {$result['created']}; akun direkonsiliasi: {$result['reconciled']}", 'green');
        if (CLI::getOption('refresh-pending')) {
            $result['credentials'] = TeacherAccountProvisioningService::refreshPendingCredentials(null);
            CLI::write('Password sementara numerik diperbarui: ' . count($result['credentials']), 'green');
        }
        if ($result['credentials'] === []) {
            CLI::write('Tidak ada kredensial baru yang perlu diekspor.', 'yellow');
            return;
        }

        $output = CLI::getOption('output');
        if (!$output) {
            $directory = WRITEPATH . 'exports';
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException('Direktori ekspor tidak dapat dibuat.');
            }
            $output = $directory . DIRECTORY_SEPARATOR . 'teacher-credentials-' . date('Ymd-His') . '.csv';
        }
        $handle = fopen($output, 'wb');
        if (!$handle) {
            throw new \RuntimeException('File kredensial tidak dapat ditulis: ' . $output);
        }
        fputcsv($handle, ['ID Guru', 'Nama Guru', 'Username Sementara', 'Password Sementara', 'Unit', 'Role']);
        foreach ($result['credentials'] as $credential) {
            fputcsv($handle, [
                $credential['teacher_id'], $credential['full_name'], $credential['username'],
                $credential['temporary_password'], $credential['units'], $credential['roles'],
            ]);
        }
        fclose($handle);
        @chmod($output, 0600);
        CLI::write('CSV kredensial: ' . $output, 'cyan');
        CLI::write('Simpan aman dan hapus setelah kredensial dibagikan.', 'yellow');
    }
}
