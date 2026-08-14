<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Read-only deployment gate for scheduling and its operational portals.
 */
class SchedulingProductionCheck extends BaseCommand
{
    protected $group = 'Akademia';
    protected $name = 'akademia:scheduling-check';
    protected $description = 'Run read-only production readiness checks for scheduling, RBAC, and role portals.';
    protected $usage = 'akademia:scheduling-check';

    public function run(array $params): int
    {
        $failures = [];
        $warnings = [];
        $passes = [];

        $app = config('App');
        $cookie = config('Cookie');
        $filters = config('Filters');
        $database = config('Database');
        $session = config('Session');
        $scheduling = config('Scheduling');
        $csp = config('ContentSecurityPolicy');

        $this->require($passes, $failures, ENVIRONMENT === 'production', 'CI_ENVIRONMENT=production.');
        $this->require($passes, $failures, str_starts_with(strtolower((string) $app->baseURL), 'https://'), 'app.baseURL HTTPS eksplisit.');
        $this->require($passes, $failures, $app->forceGlobalSecureRequests === true, 'Global secure requests aktif.');
        $this->require($passes, $failures, $app->CSPEnabled === true, 'Content Security Policy aktif.');
        $this->require($passes, $failures, $cookie->secure === true, 'Secure cookie aktif.');
        $this->require($passes, $failures, $cookie->httponly === true, 'Cookie HTTPOnly aktif.');
        $this->require($passes, $failures, in_array($cookie->samesite, ['Lax', 'Strict'], true), 'Cookie SameSite harus Lax atau Strict.');
        $this->require($passes, $failures, ! in_array('toolbar', $filters->globals['after'] ?? [], true), 'Debug toolbar nonaktif.');
        $displayErrors = strtolower((string) ini_get('display_errors'));
        $this->require($passes, $failures, in_array($displayErrors, ['', '0', 'off'], true), 'display_errors nonaktif.');
        $this->require($passes, $failures, ! empty($app->allowedHostnames), 'allowedHostnames eksplisit.');
        $behindTrustedProxy = filter_var(
            getenv('app.behindTrustedProxy') ?: false,
            FILTER_VALIDATE_BOOLEAN
        );
        $this->require(
            $passes,
            $failures,
            ! $behindTrustedProxy || ! empty($app->proxyIPs),
            $behindTrustedProxy
                ? 'Trusted proxy IP/CIDR eksplisit.'
                : 'Mode direct/shared hosting tidak mempercayai proxy header.'
        );
        $this->require($passes, $failures, $session->cookieName !== 'ci_session', 'Nama cookie sesi production tidak memakai default.');
        $this->require($passes, $failures, $scheduling->teamTeachingEnabled === false, 'Team teaching scheduling tetap OFF sampai kontrak junction dimigrasikan.');
        $this->require($passes, $failures, ! is_file(FCPATH . 'scratch_run_test.php'), 'Debug script publik tidak tersedia.');
        foreach (['scriptSrc', 'styleSrc'] as $directive) {
            $sources = is_array($csp->{$directive}) ? $csp->{$directive} : [$csp->{$directive}];
            if (in_array('unsafe-inline', $sources, true)) {
                $warnings[] = "CSP {$directive} masih mengizinkan unsafe-inline untuk kompatibilitas UI legacy; migrasikan ke nonce/event listener.";
            }
        }

        $defaultDb = (string) ($database->default['database'] ?? '');
        $defaultDbUser = (string) ($database->default['username'] ?? '');
        $defaultDbPassword = (string) ($database->default['password'] ?? '');
        $testDb = (string) ($database->tests['database'] ?? '');
        $this->require($passes, $failures, $defaultDb !== '', 'Database default terkonfigurasi.');
        $this->require(
            $passes,
            $failures,
            ! str_contains(strtoupper($defaultDb), 'CHANGE_ME')
                && trim($defaultDbUser) !== ''
                && ! str_contains(strtoupper($defaultDbUser), 'CHANGE_ME')
                && trim($defaultDbPassword) !== ''
                && ! str_contains(strtoupper($defaultDbPassword), 'CHANGE_ME'),
            'Placeholder database telah diganti dan kredensial hosting tidak kosong.'
        );
        $this->require($passes, $failures, $testDb === '' || $testDb === ':memory:' || $testDb !== $defaultDb, 'Database test terpisah dari database default.');

        foreach (['mysqli', 'intl', 'mbstring', 'json', 'openssl', 'zip', 'fileinfo'] as $extension) {
            $this->require($passes, $failures, extension_loaded($extension), "Ekstensi PHP {$extension} aktif.");
        }

        try {
            $db = Database::connect();
            $db->initialize();
            foreach ([
                'assignment_versions', 'teaching_assignments', 'teacher_additional_duties',
                'elective_periods', 'elective_offerings', 'student_elective_submissions',
                'school_routine_activities', 'teacher_duty_schedules', 'teacher_schedule_substitutions',
                'teacher_substitution_repair_candidates',
                'users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'user_unit_access',
                'elective_students', 'student_elective_submissions', 'student_elective_choices',
                'attendance_sessions', 'student_attendances',
                'academic_calendars', 'academic_calendar_days', 'academic_calendar_events',
                'schedule_versions', 'schedule_day_slots', 'schedule_requirements',
                'schedule_entries', 'schedule_conflicts', 'schedule_generation_runs',
                'schedule_generation_candidates', 'schedule_candidate_entries',
                'schedule_revision_history',
            ] as $table) {
                $this->require($passes, $failures, $db->tableExists($table), "Tabel {$table} tersedia.");
            }

            if ($db->tableExists('schedule_versions')) {
                foreach (['unit_id', 'workflow_status', 'revision_number'] as $field) {
                    $this->require($passes, $failures, $db->fieldExists($field, 'schedule_versions'), "Kolom schedule_versions.{$field} tersedia.");
                }
            }
            if ($db->tableExists('schedule_day_slots')) {
                foreach (['start_time', 'end_time', 'slot_type'] as $field) {
                    $this->require($passes, $failures, $db->fieldExists($field, 'schedule_day_slots'), "Kolom schedule_day_slots.{$field} tersedia.");
                }
            }
            if ($db->tableExists('schedule_conflicts')) {
                foreach (['fingerprint', 'conflict_code', 'status', 'detected_at', 'active_generation_scope'] as $field) {
                    $this->require($passes, $failures, $db->fieldExists($field, 'schedule_conflicts'), "Kolom schedule_conflicts.{$field} tersedia.");
                }
            }
            if ($db->tableExists('teachers')) {
                foreach (['teacher_initial', 'color_code'] as $field) {
                    $this->require($passes, $failures, $db->fieldExists($field, 'teachers'), "Kolom teachers.{$field} tersedia.");
                }
            }

            foreach (['students.view', 'students.manage', 'teacher_attendance.view', 'attendances.record',
                'attendances.view', 'attendances.admin', 'academic_calendar.view'] as $permissionCode) {
                $this->require(
                    $passes,
                    $failures,
                    $db->table('permissions')->where('code', $permissionCode)->countAllResults() === 1,
                    "Permission {$permissionCode} tersedia tepat satu."
                );
            }

            $personalExecutiveGrants = $db->table('role_permissions rp')
                ->join('roles r', 'r.id = rp.role_id')
                ->join('permissions p', 'p.id = rp.permission_id')
                ->whereIn('r.code', ['guru', 'wali_kelas'])
                ->whereIn('p.code', ['attendances.view', 'attendances.admin'])
                ->countAllResults();
            $this->require($passes, $failures, $personalExecutiveGrants === 0,
                'Guru/Wali Kelas tidak memiliki akses monitoring absensi eksekutif.');

            foreach (['guru', 'wali_kelas'] as $roleCode) {
                foreach (['teacher_attendance.view', 'attendances.record'] as $permissionCode) {
                    $grantExists = $db->table('role_permissions rp')
                        ->join('roles r', 'r.id = rp.role_id')
                        ->join('permissions p', 'p.id = rp.permission_id')
                        ->where('r.code', $roleCode)->where('p.code', $permissionCode)
                        ->countAllResults() === 1;
                    $this->require($passes, $failures, $grantExists,
                        "Role {$roleCode} memiliki {$permissionCode}.");
                }
            }

            foreach ([
                'tata_usaha' => ['dashboard.view', 'students.manage', 'users.manage', 'attendances.view'],
                'siswa' => ['dashboard.view', 'academic_calendar.view', 'electives.selection.submit'],
            ] as $roleCode => $permissionCodes) {
                foreach ($permissionCodes as $permissionCode) {
                    $grantExists = $db->table('role_permissions rp')
                        ->join('roles r', 'r.id = rp.role_id')
                        ->join('permissions p', 'p.id = rp.permission_id')
                        ->where('r.code', $roleCode)->where('p.code', $permissionCode)
                        ->countAllResults() === 1;
                    $this->require($passes, $failures, $grantExists,
                        "Role {$roleCode} memiliki {$permissionCode}.");
                }
            }

            foreach (['tata_usaha', 'viewer_yayasan'] as $roleCode) {
                $hasAttendanceAdmin = $db->table('role_permissions rp')
                    ->join('roles r', 'r.id = rp.role_id')
                    ->join('permissions p', 'p.id = rp.permission_id')
                    ->where('r.code', $roleCode)->where('p.code', 'attendances.admin')
                    ->countAllResults() > 0;
                $this->require($passes, $failures, !$hasAttendanceAdmin,
                    "Role {$roleCode} tidak dapat memverifikasi/mengubah absensi.");
            }

            $teachersWithoutAccount = $db->table('teachers t')
                ->join('users u', 'u.teacher_id = t.id AND u.deleted_at IS NULL', 'left')
                ->where('t.is_active', 1)->where('t.deleted_at IS NULL')->where('u.id IS NULL')
                ->countAllResults();
            $this->require($passes, $failures, $teachersWithoutAccount === 0,
                'Seluruh guru aktif memiliki akun login tertaut.');

            $personalAccountsWithoutTeacher = $db->table('users u')
                ->join('user_roles ur', 'ur.user_id = u.id')
                ->join('roles r', 'r.id = ur.role_id')
                ->whereIn('r.code', ['guru', 'wali_kelas'])
                ->where('u.is_active', 1)->where('u.deleted_at IS NULL')->where('u.teacher_id IS NULL')
                ->countAllResults();
            $this->require($passes, $failures, $personalAccountsWithoutTeacher === 0,
                'Seluruh akun Guru/Wali Kelas tertaut ke profil guru.');

            $homeroomAccountsWithoutClassroom = $db->table('users u')
                ->join('user_roles ur', 'ur.user_id = u.id')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('r.code', 'wali_kelas')
                ->where('u.is_active', 1)->where('u.deleted_at IS NULL')->where('u.classroom_id IS NULL')
                ->countAllResults();
            $this->require($passes, $failures, $homeroomAccountsWithoutClassroom === 0,
                'Seluruh akun Wali Kelas tertaut ke rombel binaan.');

            $usersWithoutRole = (int) ($db->query(
                'SELECT COUNT(*) AS total FROM users u WHERE u.is_active = 1 AND u.deleted_at IS NULL '
                . 'AND NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id)'
            )->getRowArray()['total'] ?? 0);
            $this->require($passes, $failures, $usersWithoutRole === 0,
                'Seluruh akun aktif memiliki minimal satu role.');

            $usersWithoutUnit = (int) ($db->query(
                'SELECT COUNT(*) AS total FROM users u WHERE u.is_active = 1 AND u.deleted_at IS NULL '
                . 'AND NOT EXISTS (SELECT 1 FROM user_unit_access uua WHERE uua.user_id = u.id)'
            )->getRowArray()['total'] ?? 0);
            $this->require($passes, $failures, $usersWithoutUnit === 0,
                'Seluruh akun aktif memiliki minimal satu unit akses.');
        } catch (\Throwable $e) {
            $failures[] = 'Koneksi/schema database gagal: ' . $e->getMessage();
        }

        if (! is_writable(WRITEPATH)) {
            $failures[] = 'Direktori writable tidak dapat ditulis oleh proses aplikasi.';
        }
        if (is_dir(WRITEPATH . 'exports') && ! is_writable(WRITEPATH . 'exports')) {
            $failures[] = 'Direktori writable/exports tidak dapat ditulis.';
        }
        CLI::write('Akademia production readiness check', 'cyan');
        foreach ($passes as $pass) {
            CLI::write('[PASS] ' . $pass, 'green');
        }
        foreach ($warnings as $warning) {
            CLI::write('[WARN] ' . $warning, 'yellow');
        }
        foreach ($failures as $failure) {
            CLI::write('[FAIL] ' . $failure, 'red');
        }

        if ($failures !== []) {
            CLI::error('BLOCKED: ' . count($failures) . ' pemeriksaan wajib gagal.');
            return EXIT_ERROR;
        }

        CLI::write('PASSED: seluruh pemeriksaan wajib lulus.', 'green');
        return EXIT_SUCCESS;
    }

    private function require(array &$passes, array &$failures, bool $condition, string $message): void
    {
        $condition ? $passes[] = $message : $failures[] = $message;
    }
}
