<?php

namespace App\Controllers;

use App\Services\SettingsService;
use App\Services\UnitScopeService;
use CodeIgniter\API\ResponseTrait;


class SettingsController extends BaseController
{
    use ResponseTrait;

    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    // ====================================================================
    // 1. SCHOOL PROFILE — Global + Per-Unit
    // ====================================================================

    public function schoolProfile()
    {
        $unitId = (int) session()->get('active_unit_id');
        $units  = \Config\Database::connect()->table('school_units')->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResultArray();
        $unit   = $this->settings->getUnitProfile($unitId);
        $global = $this->settings->getAllGrouped();

        return view('settings/school_profile', [
            'title'             => 'Profil Sekolah',
            'breadcrumb_active' => 'Profil Sekolah',
            'units'             => $units,
            'unit'              => $unit,
            'global'            => $global['school'] ?? [],
            'unitId'            => $unitId,
        ]);
    }

    public function saveSchoolProfile()
    {
        $unitId = (int) $this->request->getPost('unit_id');
        UnitScopeService::assertUnit($unitId);

        // Save unit-specific fields
        $unitData = [
            'name'          => $this->request->getPost('name'),
            'short_name'    => $this->request->getPost('short_name'),
            'npsn'          => $this->request->getPost('npsn'),
            'address'       => $this->request->getPost('address'),
            'phone'         => $this->request->getPost('phone'),
            'email'         => $this->request->getPost('email'),
            'head_name'     => $this->request->getPost('head_name'),
            'head_identifier' => $this->request->getPost('head_identifier'),
            'document_city' => $this->request->getPost('document_city'),
            'decree_prefix' => $this->request->getPost('decree_prefix'),
            'header_line_1' => $this->request->getPost('header_line_1'),
            'header_line_2' => $this->request->getPost('header_line_2'),
            'header_line_3' => $this->request->getPost('header_line_3'),
            'header_line_4' => $this->request->getPost('header_line_4'),
            'timezone'      => $this->request->getPost('timezone') ?: 'Asia/Jayapura',
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => (int) session()->get('user_id'),
        ];
        $this->settings->updateUnitProfile($unitId, $unitData);

        // Save global school settings
        $this->settings->saveBulk([
            'school' => [
                'motto'          => $this->request->getPost('motto'),
                'vision'         => $this->request->getPost('vision'),
                'mission'        => $this->request->getPost('mission'),
                'accreditation'  => $this->request->getPost('accreditation'),
                'bank_account'   => $this->request->getPost('bank_account'),
                'npwp'           => $this->request->getPost('npwp'),
            ],
        ]);

        return redirect()->to('settings/school-profile')->with('success', 'Profil sekolah berhasil diperbarui.');
    }

    // ====================================================================
    // 2. DATABASE MANAGER
    // ====================================================================

    public function database()
    {
        $tables  = $this->settings->listTables();
        $grouped = $this->settings->getTablesByModule();
        $summary = $this->settings->getStorageSummary();
        $global  = $this->settings->getAllGrouped();

        return view('settings/database_manager', [
            'title'             => 'Manajemen Database',
            'breadcrumb_active' => 'Database',
            'tables'            => $tables,
            'grouped'           => $grouped,
            'summary'           => $summary,
            'dbConfig'          => $global['database'] ?? [],
        ]);
    }

    public function saveBackup()
    {
        $this->settings->saveBulk([
            'database' => [
                'backup_retention_days' => $this->request->getPost('backup_retention_days'),
                'auto_backup_enabled'   => $this->request->getPost('auto_backup_enabled') ? '1' : '0',
                'auto_backup_time'      => $this->request->getPost('auto_backup_time'),
            ],
        ]);

        return redirect()->to('settings/database#tab-backup')->with('success', 'Pengaturan backup berhasil disimpan.');
    }

    public function databaseTruncate(string $table)
    {
        // Safety: only allow truncating non-critical tables
        $protected = ['users', 'roles', 'permissions', 'user_roles', 'user_unit_access', 'school_units', 'academic_periods', 'academic_years'];
        if (in_array($table, $protected)) {
            return redirect()->back()->with('error', "Tabel {$table} tidak boleh di-truncate karena berisi data kritis.");
        }

        if ($this->settings->truncateTable($table)) {
            return redirect()->back()->with('success', "Tabel {$table} berhasil dikosongkan.");
        }
        return redirect()->back()->with('error', "Gagal mengosongkan tabel {$table}.");
    }

    public function databaseExport(string $table)
    {
        $sql = $this->settings->exportTable($table);
        $filename = "{$table}_" . date('Y-m-d_His') . ".sql";

        return $this->response
            ->setHeader('Content-Type', 'application/sql')
            ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->setBody($sql);
    }

    public function databaseTableDetail(string $table)
    {
        try {
            $columns = $this->settings->getTableColumns($table);
            $rows    = \Config\Database::connect()->table($table)->limit(50)->get()->getResultArray();
            $count   = \Config\Database::connect()->table($table)->countAllResults(false);

            return $this->response->setJSON([
                'status'  => 'success',
                'columns' => array_map(fn($c) => ['name' => $c->name, 'type' => $c->type, 'nullable' => $c->allow_null], $columns),
                'rows'    => $rows,
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(404);
        }
    }

    // ====================================================================
    // 3. SYSTEM APPEARANCE
    // ====================================================================

    public function appearance()
    {
        $global = $this->settings->getAllGrouped();

        return view('settings/system_appearance', [
            'title'             => 'Tampilan & Tema',
            'breadcrumb_active' => 'Tampilan & Tema',
            'appearance'        => $global['appearance'] ?? [],
            'email'             => $global['email'] ?? [],
            'security'          => $global['security'] ?? [],
        ]);
    }

    public function saveAppearance()
    {
        $this->settings->saveBulk([
            'appearance' => [
                'app_name'        => $this->request->getPost('app_name'),
                'app_tagline'     => $this->request->getPost('app_tagline'),
                'theme'           => $this->request->getPost('theme'),
                'primary_color'   => $this->request->getPost('primary_color'),
                'font_family'     => $this->request->getPost('font_family'),
                'font_size'       => $this->request->getPost('font_size'),
                'sidebar_compact' => $this->request->getPost('sidebar_compact') ? '1' : '0',
                'show_breadcrumbs'=> $this->request->getPost('show_breadcrumbs') ? '1' : '0',
                'table_page_size' => $this->request->getPost('table_page_size'),
                'date_format'     => $this->request->getPost('date_format'),
                'time_format'     => $this->request->getPost('time_format'),
            ],
        ]);

        return redirect()->to('settings/appearance')->with('success', 'Pengaturan tampilan berhasil disimpan.');
    }

    public function saveEmail()
    {
        $this->settings->saveBulk([
            'email' => [
                'smtp_host'       => $this->request->getPost('smtp_host'),
                'smtp_port'       => $this->request->getPost('smtp_port'),
                'smtp_user'       => $this->request->getPost('smtp_user'),
                'smtp_from_name'  => $this->request->getPost('smtp_from_name'),
                'smtp_from_email' => $this->request->getPost('smtp_from_email'),
            ],
        ]);

        return redirect()->to('settings/appearance#email')->with('success', 'Pengaturan email berhasil disimpan.');
    }

public function saveSecurity()
    {
        $this->settings->saveBulk([
            'security' => [
                'session_timeout'    => $this->request->getPost('session_timeout'),
                'max_login_attempts' => $this->request->getPost('max_login_attempts'),
                'lockout_duration'   => $this->request->getPost('lockout_duration'),
                'password_min_length'=> $this->request->getPost('password_min_length'),
                'require_2fa'        => $this->request->getPost('require_2fa') ? '1' : '0',
            ],
        ]);

        return redirect()->to('settings/appearance#security')->with('success', 'Pengaturan keamanan berhasil disimpan.');
    }

    // ====================================================================
    // 4. APPLICATION SETTINGS
    // ====================================================================

    public function application()
    {
        $global = $this->settings->getAllGrouped();

        return view('settings/application', [
            'title'             => 'Pengaturan Aplikasi',
            'breadcrumb_active' => 'Pengaturan Aplikasi',
            'application'       => $global['application'] ?? [],
            'maintenance'       => $global['maintenance'] ?? [],
            'registration'      => $global['registration'] ?? [],
        ]);
    }

    public function saveApplication()
    {
        $this->settings->saveBulk([
            'application' => [
                'app_timezone'       => $this->request->getPost('app_timezone'),
                'default_language'   => $this->request->getPost('default_language'),
                'date_format'        => $this->request->getPost('date_format'),
                'time_format'        => $this->request->getPost('time_format'),
                'currency'           => $this->request->getPost('currency'),
                'number_format'      => $this->request->getPost('number_format'),
            ],
        ]);

        return redirect()->to('settings/application')->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }

    public function saveMaintenance()
    {
        $this->settings->saveBulk([
            'maintenance' => [
                'mode'           => $this->request->getPost('maintenance_mode') ? '1' : '0',
                'message'        => $this->request->getPost('maintenance_message'),
                'allowed_ips'    => $this->request->getPost('allowed_ips'),
            ],
        ]);

        return redirect()->to('settings/application#maintenance')->with('success', 'Pengaturan maintenance berhasil disimpan.');
    }

    public function saveRegistration()
    {
        $this->settings->saveBulk([
            'registration' => [
                'allow_registration' => $this->request->getPost('allow_registration') ? '1' : '0',
                'require_approval'   => $this->request->getPost('require_approval') ? '1' : '0',
                'default_role'       => $this->request->getPost('default_role'),
            ],
        ]);

        return redirect()->to('settings/application#registration')->with('success', 'Pengaturan registrasi berhasil disimpan.');
    }
}
