<?php
$globalThemeColor = 'purple';
$schoolName = 'WMVAA Akademia';
$appInfo = (object) [
    'name' => 'WMVAA Akademia',
    'version' => '2.0.0',
    'developer' => 'WMVAA'
];

$userId = session()->get('user_id');
$userRole = session()->get('role_code') ?? 'guest';
$userName = session()->get('username') ?? 'Guest';
$activeUserDisplayName = active_user_name();
$nameParts = preg_split('/\s+/', trim($activeUserDisplayName)) ?: [];
$userInitials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $userInitials .= mb_substr($namePart, 0, 1);
}
$userInitials = mb_strtoupper($userInitials ?: mb_substr($userName, 0, 2));
$currentPath = trim(uri_string(), '/');
$bodyClasses = [];
if ($currentPath === '' || $currentPath === 'dashboard') {
    $bodyClasses[] = 'native-dashboard-page';
}
if (is_guru() || is_wali_kelas()) {
    $bodyClasses[] = 'persona-teacher';
}
if (str_starts_with($currentPath, 'portal/') || $currentPath === 'electives') {
    $bodyClasses[] = 'native-role-page';
}
$usesPortalUnitFilter = $currentPath === '' || $currentPath === 'dashboard' || str_starts_with($currentPath, 'portal/');
$portalUnitScope = $usesPortalUnitFilter
    ? ($unitScope ?? \App\Services\PortalUnitScopeService::resolve((string) service('request')->getGet('unit_scope')))
    : null;
$contextPeriods = get_academic_periods();
$contextActivePeriod = get_active_period();
$contextActivePeriodId = $contextActivePeriod ? (int) $contextActivePeriod['id'] : null;
$currentQuery = service('request')->getGet();
$routeTitles = [
    'curriculum/imports' => 'Import Kurikulum',
    'curriculum'         => 'Struktur Kurikulum',
    'electives'          => 'Pemilihan Mata Pelajaran',
    'portal/electives'   => 'Mapel Pilihan Saya',
    'portal/schedule'    => 'Jadwal Mengajar',
    'portal/workload'    => 'Beban Mengajar',
    'portal/attendance'  => 'Absensi & Jurnal',
    'portal/duty-schedule' => 'Jadwal Piket',
    'portal/classroom'   => 'Kelas Binaan',
    'portal/assignment-document' => 'SK Pembagian Tugas',
    'assignments/imports'=> 'Import Penugasan',
    'assignments'        => 'Penugasan Mengajar',
    'workloads/policies' => 'Kebijakan Beban Kerja',
    'workloads'          => 'Beban Kerja Guru',
    'imports/master'     => 'Import Master Data',
    'students'           => 'Master Peserta Didik',
    'schedules'          => 'Jadwal Pelajaran',
];
$inferredTitle = null;
foreach ($routeTitles as $routePrefix => $routeTitle) {
    if ($currentPath === $routePrefix || str_starts_with($currentPath, $routePrefix . '/')) {
        $inferredTitle = $routeTitle;
        break;
    }
}
$pageTitle = $title ?? $inferredTitle ?? 'Dashboard';
$pageBreadcrumb = $breadcrumb_active ?? $pageTitle;
$isCurrentPath = static function (array $patterns) use ($currentPath): bool {
    foreach ($patterns as $pattern) {
        $pattern = trim((string) $pattern, '/');
        if ($currentPath === $pattern || ($pattern !== '' && str_starts_with($currentPath, $pattern . '/'))) {
            return true;
        }
    }

    return false;
};
$mobileNavItems = [[
    'href' => 'dashboard', 'icon' => 'house', 'label' => 'Beranda', 'patterns' => ['dashboard'],
]];
$appendMobileNav = static function (array &$items, string $permission, string $href, string $icon, string $label, array $patterns): void {
    if ($permission === '' || has_permission($permission)) {
        $items[] = compact('href', 'icon', 'label', 'patterns');
    }
};
if (has_role('super_admin', 'superadmin', 'admin_smp', 'admin_sma')) {
    $appendMobileNav($mobileNavItems, 'teachers.view', 'teachers', 'users', 'Guru', ['teachers']);
    $appendMobileNav($mobileNavItems, 'assignments.view', 'assignments', 'briefcase', 'Tugas', ['assignments']);
    $appendMobileNav($mobileNavItems, 'schedules.view', 'schedules', 'calendar-days', 'Jadwal', ['schedules']);
} elseif (has_role('wakasek_kurikulum')) {
    $appendMobileNav($mobileNavItems, 'curriculum.view', 'curriculum', 'table-2', 'Kurikulum', ['curriculum']);
    $appendMobileNav($mobileNavItems, 'assignments.view', 'assignments', 'briefcase', 'Tugas', ['assignments']);
    $appendMobileNav($mobileNavItems, 'schedules.view', 'schedules', 'calendar-days', 'Jadwal', ['schedules']);
} elseif (has_role('kepala_sekolah', 'viewer_yayasan')) {
    $appendMobileNav($mobileNavItems, 'assignments.view', 'assignments', 'file-check-2', 'SK Tugas', ['assignments']);
    $appendMobileNav($mobileNavItems, 'workloads.view', 'workloads', 'bar-chart-3', 'Beban', ['workloads']);
    $appendMobileNav($mobileNavItems, 'schedules.view', 'schedules', 'calendar-check', 'Jadwal', ['schedules']);
} elseif (has_role('tata_usaha')) {
    $appendMobileNav($mobileNavItems, 'students.view', 'students', 'graduation-cap', 'Siswa', ['students']);
    $appendMobileNav($mobileNavItems, 'classrooms.view', 'classrooms', 'school', 'Rombel', ['classrooms']);
    $appendMobileNav($mobileNavItems, 'teachers.view', 'teachers', 'contact', 'Guru', ['teachers']);
} elseif (is_wali_kelas()) {
    $appendMobileNav($mobileNavItems, 'class_students.view', 'portal/classroom', 'school', 'Kelas', ['portal/classroom']);
    $appendMobileNav($mobileNavItems, 'teacher_schedule.view', 'portal/schedule', 'calendar-days', 'Jadwal', ['portal/schedule']);
    $appendMobileNav($mobileNavItems, 'teacher_electives.view', 'portal/electives', 'users-round', 'Pilihan', ['portal/electives']);
} elseif (is_guru()) {
    $appendMobileNav($mobileNavItems, 'teacher_schedule.view', 'portal/schedule', 'calendar-days', 'Jadwal', ['portal/schedule']);
    if (has_permission('teacher_attendance.view')) {
        $appendMobileNav($mobileNavItems, 'teacher_attendance.view', 'portal/attendance', 'clipboard-check', 'Absensi', ['portal/attendance']);
    } else {
        $appendMobileNav($mobileNavItems, 'teacher_electives.view', 'portal/electives', 'users-round', 'Pilihan', ['portal/electives']);
    }
    $appendMobileNav($mobileNavItems, 'teacher_workload.view', 'portal/workload', 'bar-chart-3', 'Beban', ['portal/workload']);
} elseif (has_role('siswa')) {
    $appendMobileNav($mobileNavItems, 'electives.selection.submit', 'my-electives', 'list-checks', 'Pilihan', ['my-electives']);
    $appendMobileNav($mobileNavItems, 'academic_calendar.view', 'academic-calendar', 'calendar-range', 'Kalender', ['academic-calendar']);
} else {
    $appendMobileNav($mobileNavItems, 'teacher_schedule.view', 'portal/schedule', 'calendar-days', 'Jadwal', ['portal/schedule']);
    $appendMobileNav($mobileNavItems, 'teacher_workload.view', 'portal/workload', 'bar-chart-3', 'Beban', ['portal/workload']);
}
$mobileNavItems = array_slice($mobileNavItems, 0, 4);
$mobileNavCount = count($mobileNavItems) + 1;
$renderMenuLink = static function (string $href, string $icon, string $label, bool $active = false, string $class = 'menu-link'): string {
    return '<a href="' . base_url($href) . '" class="' . $class . ($active && $class !== 'menu-link' ? ' active' : '') . '">'
        . '<i data-lucide="' . esc($icon, 'attr') . '"></i>'
        . '<span>' . esc($label) . '</span>'
        . '</a>';
};
$requestedMenuScope = strtolower(trim((string) service('request')->getGet('scope')));
$isMenuItemActive = static function (array $item) use ($isCurrentPath, $requestedMenuScope): bool {
    $active = $isCurrentPath($item['patterns'] ?? []);
    foreach ($item['excludePatterns'] ?? [] as $excludedPattern) {
        if ($isCurrentPath([$excludedPattern])) {
            $active = false;
            break;
        }
    }
    if (isset($item['queryScope'])) {
        $active = $active && $requestedMenuScope === $item['queryScope'];
    }
    if (in_array($requestedMenuScope, $item['excludeQueryScopes'] ?? [], true)) {
        $active = false;
    }
    return $active;
};
?>
<!DOCTYPE html>
<html lang="id" data-theme-color="<?= esc($globalThemeColor) ?>" data-service-worker="<?= esc(base_url('sw.js'), 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= esc($appInfo->name) ?> v<?= esc($appInfo->version) ?> - Dashboard Panel">
    <meta name="author" content="<?= esc($appInfo->developer) ?>">
    <meta name="application-name" content="<?= esc($appInfo->name) ?>">
    <meta name="version" content="<?= esc($appInfo->version) ?>">
    <title><?= esc($pageTitle) ?> - <?= esc($appInfo->name) ?></title>

    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/brand-mark.svg') ?>">
    <meta name="theme-color" content="#5b5ce2">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Akademia">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/pwa-icon-192.png') ?>">

    <script src="<?= base_url('assets/js/theme-sync.js?v=1.0.3') ?>"></script>
    <script>
        SpTheme.init({ serverTheme: 'purple', scope: 'dashboard' });
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins, Inter & Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Mobile Dashboard Optimization CSS -->
    <link href="<?= base_url('assets/css/dashboard-mobile.css?v=1.0.1') ?>" rel="stylesheet">

    <!-- Design System + Dashboard Layout -->
    <link href="<?= base_url('assets/css/foundation.css?v=1.0.1') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/dashboard.css?v=1.0.1') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/admin-dashboard.css?v=1.0.1') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/mobile-first-polish.css?v=1.0.0') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/academia-ui.css?v=2.1.3') ?>" rel="stylesheet">

    <!-- Lucide Icons CDN with local fallback -->
    <script src="https://unpkg.com/lucide@0.309.0/dist/umd/lucide.min.js"></script>
    <script>
        if (typeof lucide === 'undefined') {
            document.write('<script src="<?= base_url('assets/js/lucide.min.js?v=1.0.1') ?>"><\/script>');
        }
    </script>

    <?= $this->renderSection('additional_css') ?>
    <link href="<?= base_url('assets/css/native-mobile.css?v=1.2.0') ?>" rel="stylesheet">
</head>
<body class="<?= esc(implode(' ', $bodyClasses), 'attr') ?>" data-user-role="<?= esc($userRole) ?>">
    <script>SpTheme.initDarkMode();</script>

    <!-- Skip Navigation -->
    <a href="#main-content" class="sp-skip-link">Lewati ke konten utama</a>

    <div class="layout-wrapper" id="layoutWrapper">
        <!-- Sidebar Backdrop for Mobile -->
        <div class="sidebar-overlay" id="sidebar-overlay" role="presentation"></div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu navigasi utama">
            <div class="sidebar-brand">
                <div class="brand-logo">
                    <img src="<?= base_url('assets/img/brand-mark.svg') ?>" alt="">
                </div>
                <div class="brand-copy">
                    <h5 class="brand-text"><?= esc($schoolName) ?></h5>
                    <span class="brand-kicker">Academic Suite</span>
                </div>
            </div>

            <div class="sidebar-search">
                <i data-lucide="search"></i>
                <label class="visually-hidden" for="sidebarMenuSearch">Cari menu</label>
                <input type="search" id="sidebarMenuSearch" placeholder="Cari menu..." autocomplete="off">
                <kbd>/</kbd>
            </div>

            <ul class="sidebar-menu" id="sidebarMenu">
                <li class="menu-header">Menu Utama</li>

                <li class="menu-item <?= $isCurrentPath(['dashboard']) || $currentPath === '' ? 'active' : '' ?>">
                    <?= $renderMenuLink('dashboard', 'layout-dashboard', 'Dashboard', $isCurrentPath(['dashboard']) || $currentPath === '') ?>
                </li>

                <?php
                $isPlatformAdminPersona = has_role('super_admin', 'superadmin', 'admin_smp', 'admin_sma');
                $isAcademicAdminPersona = !$isPlatformAdminPersona && has_role('wakasek_kurikulum');
                $isExecutivePersona = !$isPlatformAdminPersona && !$isAcademicAdminPersona && has_role('kepala_sekolah', 'viewer_yayasan');
                $isOperationsPersona = !$isPlatformAdminPersona && !$isAcademicAdminPersona && !$isExecutivePersona && has_role('tata_usaha');
                $hasAdministrativePersona = $isPlatformAdminPersona || $isAcademicAdminPersona || $isExecutivePersona || $isOperationsPersona;
                if ($isExecutivePersona) {
                    $menuGroups = [
                        [
                            'key' => 'executive-monitoring',
                            'label' => 'Monitoring Akademik',
                            'icon' => 'line-chart',
                            'items' => [
                                ['permission' => 'assignments.view', 'href' => 'assignments', 'icon' => 'file-check-2', 'label' => 'SK Pembagian Tugas', 'patterns' => ['assignments']],
                                ['permission' => 'workloads.view', 'href' => 'workloads', 'icon' => 'bar-chart-3', 'label' => 'Beban Kerja Guru', 'patterns' => ['workloads']],
                                ['permission' => 'schedules.view', 'href' => 'schedules', 'icon' => 'calendar-check', 'label' => 'Jadwal Resmi', 'patterns' => ['schedules']],
                                ['permissionAny' => ['duty_schedules.view', 'schedules.view'], 'href' => 'duty-schedules', 'icon' => 'shield-check', 'label' => 'Jadwal Piket', 'patterns' => ['duty-schedules']],
                                ['permission' => 'learning_sequences.view', 'href' => 'curriculum/sequences', 'icon' => 'network', 'label' => 'IALOS Education', 'patterns' => ['curriculum/sequences', 'curriculum/coverage']],
                                ['permission' => 'learning_packs.view', 'href' => 'curriculum/learning-packs', 'icon' => 'package-open', 'label' => 'Paket Pembelajaran', 'patterns' => ['curriculum/learning-packs']],
                            ],
                        ],
                        [
                            'key' => 'executive-reference',
                            'label' => 'Referensi Sekolah',
                            'icon' => 'library',
                            'items' => [
                                ['permission' => 'teachers.view', 'href' => 'teachers', 'icon' => 'users', 'label' => 'Daftar Guru', 'patterns' => ['teachers']],
                                ['permission' => 'classrooms.view', 'href' => 'classrooms', 'icon' => 'school', 'label' => 'Daftar Rombel', 'patterns' => ['classrooms']],
                                ['permission' => 'students.view', 'href' => 'students', 'icon' => 'graduation-cap', 'label' => 'Peserta Didik', 'patterns' => ['students']],
                                ['permission' => 'regulations.view', 'href' => 'references/regulations', 'icon' => 'landmark', 'label' => 'Regulasi Pendidikan', 'patterns' => ['references/regulations']],
                                ['permission' => 'graduate_profile.view', 'href' => 'references/graduate-profile', 'icon' => 'badge-check', 'label' => 'Profil Lulusan', 'patterns' => ['references/graduate-profile']],
                            ],
                        ],
                    ];
                } elseif ($isOperationsPersona) {
                    $menuGroups = [[
                        'key' => 'school-administration',
                        'label' => 'Administrasi Sekolah',
                        'icon' => 'folders',
                        'items' => [
                            ['permission' => 'teachers.view', 'href' => 'teachers', 'icon' => 'contact', 'label' => 'Data Guru', 'patterns' => ['teachers']],
                            ['permission' => 'students.view', 'href' => 'students', 'icon' => 'users-round', 'label' => 'Peserta Didik', 'patterns' => ['students']],
                            ['permission' => 'classrooms.view', 'href' => 'classrooms', 'icon' => 'school', 'label' => 'Kelas / Rombel', 'patterns' => ['classrooms']],
                            ['permission' => 'academic_periods.view', 'href' => 'academic-periods', 'icon' => 'calendar-range', 'label' => 'Periode Akademik', 'patterns' => ['academic-periods']],
                            ['permission' => 'users.view', 'href' => 'users', 'icon' => 'user-cog', 'label' => 'Akun Pengguna', 'patterns' => ['users']],
                        ],
                    ]];
                } elseif (!$hasAdministrativePersona && is_wali_kelas()) {
                    $menuGroups = [
                        [
                            'key' => 'homeroom-workspace',
                            'label' => 'Kelas Binaan',
                            'icon' => 'school',
                            'items' => [
                                ['permission' => 'class_students.view', 'href' => 'portal/classroom', 'icon' => 'users-round', 'label' => 'Siswa Kelas Saya', 'patterns' => ['portal/classroom']],
                                ['permission' => 'class_electives.manage', 'href' => 'electives', 'icon' => 'list-checks', 'label' => 'Pemilihan Mapel Kelas', 'patterns' => ['electives']],
                                ['permission' => 'class_schedule.view', 'href' => 'portal/schedule?scope=classroom', 'icon' => 'calendar-range', 'label' => 'Jadwal Kelas Saya', 'patterns' => ['portal/schedule'], 'queryScope' => 'classroom'],
                            ],
                        ],
                        [
                            'key' => 'personal-teaching',
                            'label' => 'Tugas Mengajar Saya',
                            'icon' => 'briefcase',
                            'items' => [
                                ['permission' => 'teacher_schedule.view', 'href' => 'portal/schedule', 'icon' => 'calendar-days', 'label' => 'Jadwal Mengajar', 'patterns' => ['portal/schedule'], 'excludeQueryScopes' => ['classroom']],
                                ['permission' => 'teacher_attendance.view', 'href' => 'portal/attendance', 'icon' => 'clipboard-check', 'label' => 'Absensi & Jurnal Saya', 'patterns' => ['portal/attendance']],
                                ['permission' => 'teacher_electives.view', 'href' => 'portal/electives', 'icon' => 'users-round', 'label' => 'Mapel Pilihan Saya', 'patterns' => ['portal/electives']],
                                ['permission' => 'teacher_workload.view', 'href' => 'portal/workload', 'icon' => 'bar-chart-2', 'label' => 'Beban Mengajar', 'patterns' => ['portal/workload']],
                                ['permission' => 'teacher_assignment_document.view', 'href' => 'portal/assignment-document', 'icon' => 'file-signature', 'label' => 'SK Pembagian Tugas', 'patterns' => ['portal/assignment-document']],
                                ['permission' => 'teacher_duty_schedule.view', 'href' => 'portal/duty-schedule', 'icon' => 'shield-check', 'label' => 'Jadwal Piket', 'patterns' => ['portal/duty-schedule']],
                                ['permission' => 'learning_objectives.view', 'href' => 'curriculum/objectives', 'icon' => 'network', 'label' => 'IALOS Education', 'patterns' => ['curriculum/objectives']],
                                ['permission' => 'learning_packs.view', 'href' => 'curriculum/learning-packs', 'icon' => 'package-open', 'label' => 'Paket Pembelajaran', 'patterns' => ['curriculum/learning-packs']],
                            ],
                        ],
                    ];
                } elseif (!$hasAdministrativePersona && is_guru()) {
                    $menuGroups = [[
                        'key' => 'personal-teaching',
                        'label' => 'Tugas Mengajar Saya',
                        'icon' => 'briefcase',
                        'items' => [
                            ['permission' => 'teacher_schedule.view', 'href' => 'portal/schedule', 'icon' => 'calendar-days', 'label' => 'Jadwal Mengajar', 'patterns' => ['portal/schedule']],
                            ['permission' => 'teacher_attendance.view', 'href' => 'portal/attendance', 'icon' => 'clipboard-check', 'label' => 'Absensi & Jurnal Saya', 'patterns' => ['portal/attendance']],
                            ['permission' => 'teacher_electives.view', 'href' => 'portal/electives', 'icon' => 'users-round', 'label' => 'Mapel Pilihan Saya', 'patterns' => ['portal/electives']],
                            ['permission' => 'teacher_workload.view', 'href' => 'portal/workload', 'icon' => 'bar-chart-2', 'label' => 'Beban Mengajar', 'patterns' => ['portal/workload']],
                            ['permission' => 'teacher_assignment_document.view', 'href' => 'portal/assignment-document', 'icon' => 'file-signature', 'label' => 'SK Pembagian Tugas', 'patterns' => ['portal/assignment-document']],
                            ['permission' => 'teacher_duty_schedule.view', 'href' => 'portal/duty-schedule', 'icon' => 'shield-check', 'label' => 'Jadwal Piket', 'patterns' => ['portal/duty-schedule']],
                        ],
                    ]];
                } elseif (!$hasAdministrativePersona && has_role('siswa')) {
                    $menuGroups = [[
                        'key' => 'student-academic',
                        'label' => 'Akademik Saya',
                        'icon' => 'graduation-cap',
                        'items' => [
                            ['permission' => 'electives.selection.submit', 'href' => 'my-electives', 'icon' => 'list-checks', 'label' => 'Pilihan Mata Pelajaran', 'patterns' => ['my-electives']],
                            ['permission' => 'academic_calendar.view', 'href' => 'academic-calendar', 'icon' => 'calendar-range', 'label' => 'Kalender Pendidikan', 'patterns' => ['academic-calendar']],
                        ],
                    ]];
                } else {
                    $menuGroups = [
                    [
                        'key' => 'organization',
                        'label' => 'Organisasi',
                        'icon' => 'building-2',
                        'items' => [
                            ['permission' => 'units.view', 'href' => 'settings/units', 'icon' => 'building', 'label' => 'Unit Sekolah', 'patterns' => ['settings/units']],
                            ['permissionAny' => ['academic_years.view', 'academic_periods.view'], 'href' => 'academic-periods', 'icon' => 'calendar', 'label' => 'Tahun & Periode', 'patterns' => ['academic-years', 'academic-periods']],
                        ],
                    ],
                    [
                        'key' => 'master-data',
                        'label' => 'Master Data',
                        'icon' => 'database',
                        'items' => [
                            ['permission' => 'teachers.view', 'href' => 'teachers', 'icon' => 'users', 'label' => 'Master Guru', 'patterns' => ['teachers']],
                            ['permission' => 'duplicates.view', 'href' => 'duplicates', 'icon' => 'copy-check', 'label' => 'Review Duplikat', 'patterns' => ['duplicates']],
                            ['permission' => 'subjects.view', 'href' => 'subjects', 'icon' => 'book-open', 'label' => 'Mata Pelajaran', 'patterns' => ['subjects']],
                            ['permission' => 'curriculum.view', 'href' => 'routine-activities', 'icon' => 'clock', 'label' => 'Kegiatan Rutin Sekolah', 'patterns' => ['routine-activities']],
                            ['permission' => 'grade_levels.view', 'href' => 'grade-levels', 'icon' => 'layers', 'label' => 'Tingkat Kelas', 'patterns' => ['grade-levels']],
                            ['permission' => 'classrooms.view', 'href' => 'classrooms', 'icon' => 'door-open', 'label' => 'Kelas / Rombel', 'patterns' => ['classrooms']],
                            ['permission' => 'students.view', 'href' => 'students', 'icon' => 'user-check', 'label' => 'Peserta Didik', 'patterns' => ['students']],
                            ['permission' => 'rooms.view', 'href' => 'rooms', 'icon' => 'building-2', 'label' => 'Ruang Sekolah', 'patterns' => ['rooms']],
                            ['permission' => 'regulations.view', 'href' => 'references/regulations', 'icon' => 'landmark', 'label' => 'Regulasi Pendidikan', 'patterns' => ['references/regulations']],
                            ['permission' => 'curriculum_sources.view', 'href' => 'references/curriculum-sources', 'icon' => 'library', 'label' => 'Sumber Kurikulum', 'patterns' => ['references/curriculum-sources']],
                            ['permission' => 'graduate_profile.view', 'href' => 'references/graduate-profile', 'icon' => 'badge-check', 'label' => 'Profil Lulusan', 'patterns' => ['references/graduate-profile']],
                            ['permissionAny' => ['teachers.import', 'subjects.import'], 'href' => 'imports/master', 'icon' => 'file-up', 'label' => 'Import Master', 'patterns' => ['imports/master']],
                        ],
                    ],
                    [
                        'key' => 'planning',
                        'label' => 'Perencanaan',
                        'icon' => 'workflow',
                        'items' => [
                            ['permission' => 'curriculum.view', 'href' => 'curriculum', 'icon' => 'grid', 'label' => 'Struktur Kurikulum', 'patterns' => ['curriculum'], 'excludePatterns' => ['curriculum/imports', 'curriculum/outcomes', 'curriculum/objectives', 'curriculum/sequences', 'curriculum/coverage', 'curriculum/learning-packs', 'curriculum/education-imports']],
                            ['permission' => 'curriculum.import', 'href' => 'curriculum/imports', 'icon' => 'file-spreadsheet', 'label' => 'Import Kurikulum', 'patterns' => ['curriculum/imports']],
                            ['permission' => 'learning_outcomes.view', 'href' => 'curriculum/outcomes', 'icon' => 'network', 'label' => 'IALOS Education', 'patterns' => ['curriculum/outcomes']],
                            ['permission' => 'learning_objectives.view', 'href' => 'curriculum/objectives', 'icon' => 'target', 'label' => 'Adaptasi TP', 'patterns' => ['curriculum/objectives']],
                            ['permission' => 'learning_sequences.view', 'href' => 'curriculum/sequences', 'icon' => 'route', 'label' => 'ATP & Coverage', 'patterns' => ['curriculum/sequences', 'curriculum/coverage']],
                            ['permission' => 'learning_packs.view', 'href' => 'curriculum/learning-packs', 'icon' => 'package-open', 'label' => 'Paket Pembelajaran', 'patterns' => ['curriculum/learning-packs']],
                            ['permissionAny' => ['electives.view', 'class_electives.manage'], 'href' => 'electives', 'icon' => 'list-checks', 'label' => has_permission('electives.view') ? 'Pemilihan Mapel' : 'Pemilihan Mapel Kelas', 'patterns' => ['electives']],
                            ['permission' => 'electives.selection.submit', 'permissionNot' => 'electives.view', 'href' => 'my-electives', 'icon' => 'check-square', 'label' => 'Pilihan Saya', 'patterns' => ['my-electives']],
                            ['permission' => 'academic_calendar.view', 'href' => 'academic-calendar', 'icon' => 'calendar-range', 'label' => 'Kalender Pendidikan', 'patterns' => ['academic-calendar']],
                        ],
                    ],
                    [
                        'key' => 'scheduling',
                        'label' => 'Penugasan & Jadwal',
                        'icon' => 'calendar-days',
                        'items' => [
                            ['permission' => 'assignments.view', 'href' => 'assignments', 'icon' => 'briefcase', 'label' => 'Penugasan Mengajar', 'patterns' => ['assignments']],
                            ['permission' => 'workloads.view', 'href' => 'workloads', 'icon' => 'bar-chart-2', 'label' => 'Beban Kerja Guru', 'patterns' => ['workloads']],
                            ['permission' => 'schedules.view', 'href' => 'schedules', 'icon' => 'calendar-days', 'label' => 'Jadwal Pelajaran', 'patterns' => ['schedules'], 'excludePatterns' => ['schedules/substitutions']],
                            ['permission' => 'schedules.view', 'href' => 'schedules/substitutions', 'icon' => 'user-round-cog', 'label' => 'Substitusi Guru', 'patterns' => ['schedules/substitutions']],
                            ['permissionAny' => ['duty_schedules.view', 'schedules.view'], 'href' => 'duty-schedules', 'icon' => 'shield-check', 'label' => 'Jadwal Piket', 'patterns' => ['duty-schedules']],
                            ['permission' => 'attendances.view', 'href' => 'attendances', 'icon' => 'activity', 'label' => 'Absensi & Jurnal Kelas', 'patterns' => ['attendances']],
                        ],
                    ],
                    [
                        'key' => 'portal',
                        'label' => 'Portal',
                        'icon' => 'panel-left',
                        'items' => [
                            ['permission' => 'teacher_schedule.view', 'href' => 'portal/schedule', 'icon' => 'calendar', 'label' => 'Jadwal Mengajar Saya', 'patterns' => ['portal/schedule']],
                            ['permission' => 'teacher_attendance.view', 'href' => 'portal/attendance', 'icon' => 'clipboard-check', 'label' => 'Absensi & Jurnal Saya', 'patterns' => ['portal/attendance']],
                            ['permission' => 'teacher_electives.view', 'href' => 'portal/electives', 'icon' => 'users-round', 'label' => 'Mapel Pilihan Saya', 'patterns' => ['portal/electives']],
                            ['permission' => 'teacher_workload.view', 'href' => 'portal/workload', 'icon' => 'bar-chart', 'label' => 'Penugasan & Beban Saya', 'patterns' => ['portal/workload']],
                            ['permission' => 'teacher_assignment_document.view', 'href' => 'portal/assignment-document', 'icon' => 'file-signature', 'label' => 'SK Tugas Saya', 'patterns' => ['portal/assignment-document']],
                            ['permission' => 'teacher_duty_schedule.view', 'href' => 'portal/duty-schedule', 'icon' => 'shield-check', 'label' => 'Piket Saya', 'patterns' => ['portal/duty-schedule']],
                            ['permission' => 'learning_objectives.view', 'href' => 'curriculum/objectives', 'icon' => 'network', 'label' => 'IALOS Education', 'patterns' => ['curriculum/objectives']],
                            ['permission' => 'learning_packs.view', 'href' => 'curriculum/learning-packs', 'icon' => 'package-open', 'label' => 'Paket Pembelajaran', 'patterns' => ['curriculum/learning-packs']],
                            ['permission' => 'class_students.view', 'href' => 'portal/classroom', 'icon' => 'school', 'label' => 'Kelas Binaan Saya', 'patterns' => ['portal/classroom']],
                        ],
                    ],
                    [
                        'key' => 'system',
                        'label' => 'Sistem',
                        'icon' => 'settings',
                        'items' => [
                            ['permission' => 'users.view', 'href' => 'users', 'icon' => 'shield-alert', 'label' => 'User Management', 'patterns' => ['users']],
                            ['permission' => 'roles.view', 'href' => 'roles', 'icon' => 'shield-check', 'label' => 'Role & Permission', 'patterns' => ['roles']],
                            ['permission' => 'audit.view', 'href' => 'audit', 'icon' => 'history', 'label' => 'Audit Log', 'patterns' => ['audit']],
                            ['permission' => 'settings.view', 'href' => 'settings/application', 'icon' => 'settings', 'label' => 'Pengaturan Aplikasi', 'patterns' => ['settings/application']],
                            ['permission' => 'academic_calendar.manage', 'href' => 'settings/academic-operations', 'icon' => 'calendar-cog', 'label' => 'Operasional Akademik', 'patterns' => ['settings/academic-operations']],
                            ['permission' => 'attendances.admin', 'href' => 'settings/attendance', 'icon' => 'clipboard-cog', 'label' => 'Pengaturan Absensi', 'patterns' => ['settings/attendance']],
                        ],
                    ],
                    ];
                }
                ?>

                <?php foreach ($menuGroups as $group): ?>
                    <?php
                    $visibleItems = [];
                    foreach ($group['items'] as $item) {
                        $allowed = true;
                        if (isset($item['permission'])) {
                            $allowed = has_permission($item['permission']);
                        }
                        if (isset($item['permissionAny'])) {
                            $allowed = false;
                            foreach ($item['permissionAny'] as $permission) {
                                if (has_permission($permission)) {
                                    $allowed = true;
                                    break;
                                }
                            }
                        }
                        if (isset($item['permissionNot']) && has_permission($item['permissionNot'])) {
                            $allowed = false;
                        }
                        if ($allowed) {
                            $visibleItems[] = $item;
                        }
                    }
                    $groupActive = false;
                    foreach ($visibleItems as $item) {
                        $itemActive = $isMenuItemActive($item);
                        $groupActive = $groupActive || $itemActive;
                    }
                    ?>
                    <?php if ($visibleItems !== []): ?>
                        <li class="menu-item has-submenu <?= $groupActive ? 'active open' : '' ?>" data-menu-group="<?= esc($group['key'], 'attr') ?>">
                            <button type="button" class="menu-link submenu-toggle" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>">
                                <i data-lucide="<?= esc($group['icon'], 'attr') ?>"></i>
                                <span><?= esc($group['label']) ?></span>
                                <i data-lucide="chevron-down" class="submenu-chevron"></i>
                            </button>
                            <ul class="submenu-list">
                                <?php foreach ($visibleItems as $item): ?>
                                    <?php
                                    $itemActive = $isMenuItemActive($item);
                                    ?>
                                    <li>
                                        <?= $renderMenuLink($item['href'], $item['icon'], $item['label'], $itemActive, 'submenu-link') ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main Container -->
        <div class="main-container">
            <!-- Navbar -->
            <header class="navbar navbar-expand navbar-light px-4 border-bottom sticky-top app-navbar">
                <div class="container-fluid d-flex align-items-center justify-content-between p-0">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn app-icon-button p-0" id="btnToggleSidebar" aria-label="Buka atau tutup navigasi" aria-controls="sidebar">
                            <i data-lucide="menu"></i>
                        </button>
                        <div class="native-mobile-title d-md-none" aria-hidden="true">
                            <strong><?= esc($pageTitle) ?></strong>
                            <span><?= esc(active_user_role()) ?></span>
                        </div>
                        <nav aria-label="breadcrumb" class="d-none d-md-block">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= esc($pageBreadcrumb) ?></li>
                            </ol>
                        </nav>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <!-- Unit Selector -->
                        <?php if ($usesPortalUnitFilter): ?>
                        <form action="<?= base_url($currentPath ?: 'dashboard') ?>" method="GET" class="d-none d-md-block" id="portalUnitFilterForm">
                            <?php foreach ($currentQuery as $queryKey => $queryValue): if ($queryKey === 'unit_scope' || is_array($queryValue)) continue; ?>
                                <input type="hidden" name="<?= esc($queryKey, 'attr') ?>" value="<?= esc((string) $queryValue, 'attr') ?>">
                            <?php endforeach; ?>
                            <label class="visually-hidden" for="desktopPortalUnitScope">Filter unit tampilan</label>
                            <select class="form-select context-select" id="desktopPortalUnitScope" name="unit_scope" data-auto-submit>
                                <?php if (count($portalUnitScope['units']) > 1): ?><option value="all" <?= $portalUnitScope['selected'] === 'all' ? 'selected' : '' ?>>Semua Unit</option><?php endif; ?>
                                <?php foreach ($portalUnitScope['units'] as $portalUnit): ?>
                                    <option value="<?= (int) $portalUnit['id'] ?>" <?= $portalUnitScope['selected'] === (string) (int) $portalUnit['id'] ? 'selected' : '' ?>><?= esc($portalUnit['code']) ?> &mdash; <?= esc($portalUnit['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <?php else: ?>
                        <form action="<?= base_url('context/unit') ?>" method="POST" class="d-none d-md-block" id="unitContextForm">
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="desktopUnitContext">Unit sekolah aktif</label>
                            <select class="form-select context-select" id="desktopUnitContext" name="unit_id" data-auto-submit>
                                <?php
                                $userUnits = get_user_units();
                                $activeUnit = get_active_unit();
                                $activeUnitId = $activeUnit ? $activeUnit['id'] : null;
                                foreach ($userUnits as $uu):
                                ?>
                                    <option value="<?= $uu['id'] ?>" <?= (int)$uu['id'] === (int)$activeUnitId ? 'selected' : '' ?>>
                                        <?= esc($uu['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>

                        <!-- Period Selector -->
                        <form action="<?= base_url('context/period') ?>" method="POST" class="d-none d-md-block" id="periodContextForm">
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="desktopPeriodContext">Periode akademik aktif</label>
                            <select class="form-select context-select" id="desktopPeriodContext" name="period_id" data-auto-submit>
                                <?php
                                if (empty($contextPeriods)):
                                ?>
                                    <option value="">Belum ada periode aktif</option>
                                <?php else: ?>
                                    <?php foreach ($contextPeriods as $ap): ?>
                                        <option value="<?= $ap['id'] ?>" <?= (int)$ap['id'] === (int)$contextActivePeriodId ? 'selected' : '' ?>>
                                            T.A <?= esc($ap['year_name']) ?> - <?= (int)$ap['semester_number'] === 1 ? 'Ganjil' : 'Genap' ?><?= (int)$ap['is_active'] === 1 ? ' — Aktif' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </form>
                        <?php endif; ?>

                        <div class="dropdown d-md-none">
                            <button type="button" class="btn app-icon-button p-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Pilih unit dan periode">
                                <i data-lucide="sliders-horizontal"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end mobile-context-menu">
                                <div class="fw-bold small mb-2">Konteks kerja</div>
                                <?php if ($usesPortalUnitFilter): ?>
                                <form action="<?= base_url($currentPath ?: 'dashboard') ?>" method="GET" class="mb-3">
                                    <?php foreach ($currentQuery as $queryKey => $queryValue): if ($queryKey === 'unit_scope' || is_array($queryValue)) continue; ?>
                                        <input type="hidden" name="<?= esc($queryKey, 'attr') ?>" value="<?= esc((string) $queryValue, 'attr') ?>">
                                    <?php endforeach; ?>
                                    <label class="form-label" for="mobilePortalUnitScope">Filter tampilan unit</label>
                                    <select class="form-select context-select" id="mobilePortalUnitScope" name="unit_scope" data-auto-submit>
                                        <?php if (count($portalUnitScope['units']) > 1): ?><option value="all" <?= $portalUnitScope['selected'] === 'all' ? 'selected' : '' ?>>Semua Unit</option><?php endif; ?>
                                        <?php foreach ($portalUnitScope['units'] as $portalUnit): ?>
                                            <option value="<?= (int) $portalUnit['id'] ?>" <?= $portalUnitScope['selected'] === (string) (int) $portalUnit['id'] ? 'selected' : '' ?>><?= esc($portalUnit['code']) ?> &mdash; <?= esc($portalUnit['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php else: ?>
                                <form action="<?= base_url('context/unit') ?>" method="POST" class="mb-3">
                                    <?= csrf_field() ?>
                                    <label class="form-label" for="mobileUnitContext">Unit sekolah</label>
                                    <select class="form-select context-select" id="mobileUnitContext" name="unit_id" data-auto-submit>
                                        <?php foreach ($userUnits as $uu): ?>
                                            <option value="<?= $uu['id'] ?>" <?= (int)$uu['id'] === (int)$activeUnitId ? 'selected' : '' ?>>
                                                <?= esc($uu['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php endif; ?>
                                <form action="<?= base_url('context/period') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <label class="form-label" for="mobilePeriodContext">Periode akademik</label>
                                    <select class="form-select context-select" id="mobilePeriodContext" name="period_id" data-auto-submit>
                                        <?php if (empty($contextPeriods)): ?>
                                            <option value="">Belum ada periode aktif</option>
                                        <?php else: ?>
                                            <?php foreach ($contextPeriods as $ap): ?>
                                                <option value="<?= $ap['id'] ?>" <?= (int)$ap['id'] === (int)$contextActivePeriodId ? 'selected' : '' ?>>
                                                    <?= esc($ap['year_name']) ?> · <?= (int)$ap['semester_number'] === 1 ? 'Ganjil' : 'Genap' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle Button -->
                        <button type="button" class="btn app-icon-button p-0" id="btnToggleTheme" aria-label="Ubah tema gelap atau terang">
                            <i data-lucide="moon" id="themeToggleIcon"></i>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button type="button" class="btn user-menu-trigger d-flex align-items-center gap-2 px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun <?= esc($activeUserDisplayName) ?>">
                                <span class="user-avatar" aria-hidden="true"><?= esc($userInitials) ?></span>
                                <div class="text-start d-none d-md-block me-1">
                                    <span class="d-block fw-semibold" style="font-size: 0.78rem; line-height: 1.1;"><?= esc($activeUserDisplayName) ?></span>
                                    <span class="text-muted d-block" style="font-size: 0.72rem;"><?= esc(active_user_role()) ?></span>
                                </div>
                                <i data-lucide="chevron-down" class="d-none d-md-block" style="width:14px;height:14px"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-3">
                                <li data-pwa-install hidden>
                                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 rounded-2 border-0 bg-transparent w-100 text-start" data-pwa-install>
                                        <i data-lucide="download" style="width:16px;height:16px"></i>
                                        <span>Instal Aplikasi</span>
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 rounded-2" href="<?= base_url('change-password') ?>">
                                        <i data-lucide="key-round" style="width: 16px; height: 16px;"></i>
                                        <span>Ganti Password</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form action="<?= base_url('logout') ?>" method="POST" id="logoutForm">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-danger rounded-2 border-0 bg-transparent w-100 text-start">
                                            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                                            <span>Keluar</span>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Body -->
            <main class="content-body" id="main-content">
                <?= $this->renderSection('main_content') ?>
            </main>

            <!-- Footer -->
            <footer class="footer border-top py-3 px-4 mt-auto app-footer">
                <div class="container-fluid d-flex align-items-center justify-content-between text-muted" style="font-size: 0.8rem;">
                    <span>&copy; <?= date('Y') ?> <?= esc($appInfo->name) ?> · Sistem Perencanaan Akademik</span>
                    <span class="footer-version">v<?= esc($appInfo->version) ?></span>
                </div>
            </footer>
        </div>
    </div>

    <nav class="dashboard-mobile-bottom-nav" aria-label="Navigasi utama aplikasi" style="--native-nav-count:<?= (int) $mobileNavCount ?>">
        <?php foreach ($mobileNavItems as $mobileItem): ?>
            <?php $mobileActive = $isCurrentPath($mobileItem['patterns']) || ($mobileItem['href'] === 'dashboard' && $currentPath === ''); ?>
            <a href="<?= base_url($mobileItem['href']) ?>" class="dashboard-bottom-item <?= $mobileActive ? 'active' : '' ?>" <?= $mobileActive ? 'aria-current="page"' : '' ?>>
                <span class="dashboard-bottom-icon"><i data-lucide="<?= esc($mobileItem['icon'], 'attr') ?>"></i></span>
                <span class="dashboard-bottom-label"><?= esc($mobileItem['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <button type="button" class="dashboard-bottom-item" data-mobile-menu aria-label="Buka semua menu">
            <span class="dashboard-bottom-icon"><i data-lucide="layout-grid"></i></span>
            <span class="dashboard-bottom-label">Lainnya</span>
        </button>
    </nav>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= base_url('assets/js/academia-ui.js?v=2.0.1') ?>"></script>
    <script src="<?= base_url('assets/js/native-mobile.js?v=1.2.0') ?>"></script>
    <script src="<?= base_url('assets/js/pwa.js?v=1.1.0') ?>"></script>

    <!-- Sidebar Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggleSidebar = document.getElementById('btnToggleSidebar');
            const layoutWrapper = document.getElementById('layoutWrapper');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const sidebar = document.getElementById('sidebar');

            if (window.innerWidth >= 992 && localStorage.getItem('ak-sidebar-collapsed') === 'true') {
                layoutWrapper.classList.add('sidebar-collapsed');
            }

            function syncSidebarState() {
                const isDesktop = window.innerWidth >= 992;
                const isOpen = isDesktop
                    ? !layoutWrapper.classList.contains('sidebar-collapsed')
                    : sidebar.classList.contains('active');
                btnToggleSidebar.setAttribute('aria-expanded', String(isOpen));
            }

            // Responsive Sidebar Toggle
            btnToggleSidebar.addEventListener('click', function() {
                if (window.innerWidth >= 992) {
                    layoutWrapper.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('ak-sidebar-collapsed', String(layoutWrapper.classList.contains('sidebar-collapsed')));
                } else {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                }
                syncSidebarState();
            });

            // Dismiss Mobile Sidebar on backdrop click
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                syncSidebarState();
            });

            sidebar.querySelectorAll('a.menu-link, a.submenu-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth >= 992) return;
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    syncSidebarState();
                });
            });

            window.addEventListener('resize', syncSidebarState);
            syncSidebarState();

            sidebar.querySelectorAll('.menu-item.has-submenu').forEach(function(group) {
                const toggle = group.querySelector('.submenu-toggle');
                const groupKey = group.dataset.menuGroup;
                if (!toggle || !groupKey) return;

                const storedState = localStorage.getItem('ak-sidebar-group-' + groupKey);
                if (!group.classList.contains('active') && storedState === 'open') {
                    group.classList.add('open');
                }
                if (!group.classList.contains('active') && storedState === 'closed') {
                    group.classList.remove('open');
                }
                toggle.setAttribute('aria-expanded', String(group.classList.contains('open')));

                toggle.addEventListener('click', function() {
                    group.classList.toggle('open');
                    const isOpen = group.classList.contains('open');
                    toggle.setAttribute('aria-expanded', String(isOpen));
                    localStorage.setItem('ak-sidebar-group-' + groupKey, isOpen ? 'open' : 'closed');
                });
            });

            // Bind Dark Mode Button
            const btnToggleTheme = document.getElementById('btnToggleTheme');
            const themeToggleIcon = document.getElementById('themeToggleIcon');
            if (btnToggleTheme && themeToggleIcon) {
                SpTheme.bindDarkModeToggle(btnToggleTheme, themeToggleIcon);
            }

            // Re-draw Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>

    <!-- Flash Message handler with premium SweetAlert2 styling -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('submit', function(event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;

                const trigger = event.submitter;
                const message = trigger?.dataset.confirm || form.dataset.confirm;
                if (!message || form.dataset.confirmed === 'true') {
                    if (form.dataset.confirmed === 'true') delete form.dataset.confirmed;
                    return;
                }

                event.preventDefault();

                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: trigger?.dataset.confirmIcon || form.dataset.confirmIcon || 'question',
                    title: trigger?.dataset.confirmTitle || form.dataset.confirmTitle || 'Konfirmasi tindakan',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: trigger?.dataset.confirmButton || form.dataset.confirmButton || 'Ya, lanjutkan',
                    cancelButtonText: trigger?.dataset.cancelButton || form.dataset.cancelButton || 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'btn btn-primary rounded-3 px-4 ms-2',
                        cancelButton: 'btn btn-light border rounded-3 px-4'
                    }
                })).then(function(result) {
                    if (!result.isConfirmed) return;

                    const loadingText = trigger?.dataset.loadingText || form.dataset.loadingText;
                    if (loadingText && trigger) {
                        trigger.disabled = true;
                        trigger.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + loadingText;
                    }

                    form.dataset.confirmed = 'true';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(trigger || undefined);
                    } else {
                        form.submit();
                    }
                });
            });

            <?php if (session()->getFlashdata('error')): ?>
                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: 'error',
                    title: 'Gagal',
                    text: '<?= esc(session()->getFlashdata('error'), 'js') ?>'
                }));
            <?php endif; ?>

            <?php if (session()->getFlashdata('errors')): ?>
                <?php
                    $errors = session()->getFlashdata('errors');
                    $errorText = is_array($errors) ? implode('<br>• ', array_map('esc', $errors)) : esc($errors);
                ?>
                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: 'error',
                    title: 'Gagal Validasi',
                    html: '<div class="text-start fs-7">• <?= $errorText ?></div>'
                }));
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: 'success',
                    title: 'Berhasil',
                    text: '<?= esc(session()->getFlashdata('success'), 'js') ?>'
                }));
            <?php endif; ?>

            <?php if (session()->getFlashdata('warning')): ?>
                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: '<?= esc(session()->getFlashdata('warning'), 'js') ?>'
                }));
            <?php endif; ?>
        });
    </script>
</body>
</html>
