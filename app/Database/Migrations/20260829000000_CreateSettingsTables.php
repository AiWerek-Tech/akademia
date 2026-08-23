<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTables extends Migration
{
    public function up()
    {
        // Global key-value settings (theme, app name, dark mode, etc.)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true, 'constraint' => 11],
            'group_key'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'value_type'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'string'], // string, int, bool, json
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group_key', 'setting_key']);
        $this->forge->createTable('system_settings', true);

        // Seed default settings
        $this->seedDefaults();
    }

    public function down()
    {
        $this->forge->dropTable('system_settings', true);
    }

    private function seedDefaults(): void
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $defaults = [
            // Appearance
            ['appearance', 'app_name', 'IALOS Education', 'string', 'Nama aplikasi yang ditampilkan di sidebar & header'],
            ['appearance', 'app_tagline', 'ACADEMIC SUITE', 'string', 'Tagline di bawah nama aplikasi'],
            ['appearance', 'theme', 'light', 'string', 'light atau dark'],
            ['appearance', 'primary_color', '#6366f1', 'string', 'Warna utama tema (hex)'],
            ['appearance', 'font_family', 'Inter', 'string', 'Font family utama'],
            ['appearance', 'font_size', '14', 'int', 'Ukuran font default (px)'],
            ['appearance', 'sidebar_compact', '0', 'bool', 'Sidebar compact mode'],
            ['appearance', 'show_breadcrumbs', '1', 'bool', 'Tampilkan breadcrumb'],
            ['appearance', 'table_page_size', '20', 'int', 'Jumlah baris per halaman tabel'],
            ['appearance', 'date_format', 'd M Y', 'string', 'Format tanggal tampilan'],
            ['appearance', 'time_format', 'H:i', 'string', 'Format waktu tampilan'],
            // School
            ['school', 'motto', '', 'string', 'Motto sekolah'],
            ['school', 'vision', '', 'string', 'Visi sekolah'],
            ['school', 'mission', '', 'string', 'Misi sekolah'],
            ['school', 'accreditation', '', 'string', 'Akreditasi sekolah'],
            ['school', 'bank_account', '', 'string', 'Rekening bank sekolah'],
            ['school', 'npwp', '', 'string', 'NPWP sekolah'],
            // Email
            ['email', 'smtp_host', '', 'string', 'SMTP host'],
            ['email', 'smtp_port', '587', 'int', 'SMTP port'],
            ['email', 'smtp_user', '', 'string', 'SMTP username'],
            ['email', 'smtp_from_name', 'IALOS Education', 'string', 'Nama pengirim email'],
            ['email', 'smtp_from_email', '', 'string', 'Email pengirim'],
            // Security
            ['security', 'session_timeout', '7200', 'int', 'Session timeout dalam detik'],
            ['security', 'max_login_attempts', '5', 'int', 'Maksimal percobaan login'],
            ['security', 'lockout_duration', '900', 'int', 'Durasi lockout dalam detik'],
            ['security', 'password_min_length', '8', 'int', 'Panjang minimum password'],
            ['security', 'require_2fa', '0', 'bool', 'Wajibkan 2FA untuk admin'],
            // Database
            ['database', 'backup_retention_days', '30', 'int', 'Jumlah hari backup disimpan'],
            ['database', 'auto_backup_enabled', '1', 'bool', 'Aktifkan backup otomatis'],
            ['database', 'auto_backup_time', '02:00', 'string', 'Waktu backup otomatis'],
        ];

        foreach ($defaults as [$group, $key, $value, $type, $desc]) {
            $db->table('system_settings')->insert([
                'group_key'     => $group,
                'setting_key'   => $key,
                'setting_value' => $value,
                'value_type'    => $type,
                'description'   => $desc,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }
}
