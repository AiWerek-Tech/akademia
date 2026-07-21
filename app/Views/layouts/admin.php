<?php
$globalThemeColor = 'purple';
$schoolName = 'WMVAA Akademia';
$appInfo = (object) [
    'name' => 'WMVAA Akademia',
    'version' => '1.0.0',
    'developer' => 'Google DeepMind Team & WMVAA'
];

$userAvatarUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100&h=100';
$userId = session()->get('user_id');
$userRole = session()->get('role_code') ?? 'guest';
$userName = session()->get('username') ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="id" data-theme-color="<?= esc($globalThemeColor) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= esc($appInfo->name) ?> v<?= esc($appInfo->version) ?> - Dashboard Panel">
    <meta name="author" content="<?= esc($appInfo->developer) ?>">
    <meta name="application-name" content="<?= esc($appInfo->name) ?>">
    <meta name="version" content="<?= esc($appInfo->version) ?>">
    <title><?= $title ?? 'Dashboard' ?> - <?= esc($appInfo->name) ?></title>

    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">

    <script src="<?= base_url('assets/js/theme-sync.js?v=1.0.1') ?>"></script>
    <script>
        SpTheme.init({ serverTheme: 'purple', scope: 'dashboard' });
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

    <!-- Lucide Icons CDN with local fallback -->
    <script src="https://unpkg.com/lucide@0.309.0/dist/umd/lucide.min.js"></script>
    <script>
        if (typeof lucide === 'undefined') {
            document.write('<script src="<?= base_url('assets/js/lucide.min.js?v=1.0.1') ?>"><\/script>');
        }
    </script>

    <?= $this->renderSection('additional_css') ?>
</head>
<body data-user-role="<?= esc($userRole) ?>">
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
                    <i data-lucide="graduation-cap"></i>
                </div>
                <h5 class="brand-text"><?= esc($schoolName) ?></h5>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-header">Menu Utama</li>
                
                <li class="menu-item <?= current_url(true) === '/' || str_contains(current_url(true), 'dashboard') ? 'active' : '' ?>">
                    <a href="<?= base_url('dashboard') ?>" class="menu-link">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if (has_permission('units.view') || has_permission('academic_years.view') || has_permission('academic_periods.view')): ?>
                    <li class="menu-header">Organisasi</li>
                    
                    <?php if (has_permission('units.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'settings/units') ? 'active' : '' ?>">
                            <a href="<?= base_url('settings/units') ?>" class="menu-link">
                                <i data-lucide="building"></i>
                                <span>Unit Sekolah</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (has_permission('academic_years.view') || has_permission('academic_periods.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'academic-years') || str_contains(current_url(true), 'academic-periods') ? 'active' : '' ?>">
                            <a href="<?= base_url('academic-periods') ?>" class="menu-link">
                                <i data-lucide="calendar"></i>
                                <span>Tahun & Periode</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <li class="menu-header">Master Data Akademik</li>
                <?php if (has_permission('teachers.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'teachers') ? 'active' : '' ?>">
                        <a href="<?= base_url('teachers') ?>" class="menu-link">
                            <i data-lucide="users"></i>
                            <span>Master Guru</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('duplicates.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'duplicates') ? 'active' : '' ?>">
                        <a href="<?= base_url('duplicates') ?>" class="menu-link">
                            <i data-lucide="copy-check"></i>
                            <span>Review Duplikat</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('subjects.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'subjects') ? 'active' : '' ?>">
                        <a href="<?= base_url('subjects') ?>" class="menu-link">
                            <i data-lucide="book-open"></i>
                            <span>Mata Pelajaran</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('grade_levels.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'grade-levels') ? 'active' : '' ?>">
                        <a href="<?= base_url('grade-levels') ?>" class="menu-link">
                            <i data-lucide="layers"></i>
                            <span>Tingkat Kelas</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('classrooms.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'classrooms') ? 'active' : '' ?>">
                        <a href="<?= base_url('classrooms') ?>" class="menu-link">
                            <i data-lucide="door-open"></i>
                            <span>Kelas / Rombel</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('rooms.view')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'rooms') ? 'active' : '' ?>">
                        <a href="<?= base_url('rooms') ?>" class="menu-link">
                            <i data-lucide="building-2"></i>
                            <span>Ruang Sekolah</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('teachers.import') || has_permission('subjects.import')): ?>
                    <li class="menu-item <?= str_contains(current_url(true), 'imports/master') ? 'active' : '' ?>">
                        <a href="<?= base_url('imports/master') ?>" class="menu-link">
                            <i data-lucide="file-up"></i>
                            <span>Import Master</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (has_permission('curriculum.view')): ?>
                    <li class="menu-header">PERENCANAAN</li>
                    <li class="menu-item <?= str_contains(current_url(true), 'curriculum') && !str_contains(current_url(true), 'curriculum/imports') ? 'active' : '' ?>">
                        <a href="<?= base_url('curriculum') ?>" class="menu-link">
                            <i data-lucide="grid"></i>
                            <span>Struktur Kurikulum</span>
                        </a>
                    </li>
                    <li class="menu-item <?= str_contains(current_url(true), 'curriculum/imports') ? 'active' : '' ?>">
                        <a href="<?= base_url('curriculum/imports') ?>" class="menu-link">
                            <i data-lucide="file-spreadsheets"></i>
                            <span>Import Kurikulum</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="menu-header">Penugasan & Jadwal [M4-M8]</li>
                <li class="menu-item disabled opacity-50">
                    <a href="#" class="menu-link">
                        <i data-lucide="briefcase"></i>
                        <span>Penugasan Mengajar</span>
                    </a>
                </li>
                <li class="menu-item disabled opacity-50">
                    <a href="#" class="menu-link">
                        <i data-lucide="bar-chart-2"></i>
                        <span>Beban Kerja Guru</span>
                    </a>
                </li>
                <li class="menu-item disabled opacity-50">
                    <a href="#" class="menu-link">
                        <i data-lucide="calendar-days"></i>
                        <span>Jadwal Pelajaran</span>
                    </a>
                </li>

                <?php if (has_permission('users.view') || has_permission('roles.view') || has_permission('audit.view') || has_permission('settings.view')): ?>
                    <li class="menu-header">Sistem</li>
                    
                    <?php if (has_permission('users.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'users') ? 'active' : '' ?>">
                            <a href="<?= base_url('users') ?>" class="menu-link">
                                <i data-lucide="shield-alert"></i>
                                <span>User Management</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (has_permission('roles.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'roles') ? 'active' : '' ?>">
                            <a href="<?= base_url('roles') ?>" class="menu-link">
                                <i data-lucide="shield-check"></i>
                                <span>Role & Permission</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (has_permission('audit.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'audit') ? 'active' : '' ?>">
                            <a href="<?= base_url('audit') ?>" class="menu-link">
                                <i data-lucide="history"></i>
                                <span>Audit Log</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (has_permission('settings.view')): ?>
                        <li class="menu-item <?= str_contains(current_url(true), 'settings/application') ? 'active' : '' ?>">
                            <a href="<?= base_url('settings/application') ?>" class="menu-link">
                                <i data-lucide="settings"></i>
                                <span>Pengaturan Aplikasi</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Container -->
        <div class="main-container">
            <!-- Navbar -->
            <header class="navbar navbar-expand navbar-light bg-white px-4 border-bottom sticky-top" style="height: var(--sp-navbar-height);">
                <div class="container-fluid d-flex align-items-center justify-content-between p-0">
                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-icon p-1" id="btnToggleSidebar" aria-label="Toggle navigasi">
                            <i data-lucide="menu"></i>
                        </button>
                        <nav aria-label="breadcrumb" class="d-none d-md-block">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= esc($breadcrumb_active ?? 'Dashboard') ?></li>
                            </ol>
                        </nav>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <!-- Unit Selector -->
                        <form action="<?= base_url('context/unit') ?>" method="POST" class="d-none d-md-block" id="unitContextForm">
                            <?= csrf_field() ?>
                            <select class="form-select border-0 bg-light rounded-pill px-3 py-2" style="font-size: 0.85rem;" name="unit_id" onchange="document.getElementById('unitContextForm').submit()">
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
                            <select class="form-select border-0 bg-light rounded-pill px-3 py-2" style="font-size: 0.85rem;" name="period_id" onchange="document.getElementById('periodContextForm').submit()">
                                <?php 
                                $periods = get_academic_periods();
                                $activePeriod = get_active_period();
                                $activePeriodId = $activePeriod ? $activePeriod['id'] : null;
                                if (empty($periods)):
                                ?>
                                    <option value="">Belum ada periode aktif</option>
                                <?php else: ?>
                                    <?php foreach ($periods as $ap): ?>
                                        <option value="<?= $ap['id'] ?>" <?= (int)$ap['id'] === (int)$activePeriodId ? 'selected' : '' ?>>
                                            T.A <?= esc($ap['year_name']) ?> - <?= (int)$ap['semester_number'] === 1 ? 'Ganjil' : 'Genap' ?> (<?= esc($ap['workflow_status']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </form>

                        <!-- Dark Mode Toggle Button -->
                        <button type="button" class="btn btn-icon rounded-circle p-2" id="btnToggleTheme" aria-label="Toggle tema gelap/terang">
                            <i data-lucide="moon" id="themeToggleIcon"></i>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button type="button" class="btn border-0 d-flex align-items-center gap-2 px-2 py-1 rounded-pill bg-light" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= esc($userAvatarUrl) ?>" alt="Avatar" class="rounded-circle border" width="36" height="36">
                                <div class="text-start d-none d-md-block me-1">
                                    <span class="d-block fw-semibold" style="font-size: 0.82rem; line-height: 1.1;"><?= esc(active_user_name()) ?></span>
                                    <span class="text-muted d-block" style="font-size: 0.72rem;"><?= esc(active_user_role()) ?></span>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-3">
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
            <footer class="footer bg-white border-top py-3 px-4 mt-auto">
                <div class="container-fluid d-flex align-items-center justify-content-between text-muted" style="font-size: 0.8rem;">
                    <span>&copy; <?= date('Y') ?> <?= esc($appInfo->name) ?>. All rights reserved.</span>
                    <span>v<?= esc($appInfo->version) ?></span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Sidebar Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnToggleSidebar = document.getElementById('btnToggleSidebar');
            const layoutWrapper = document.getElementById('layoutWrapper');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const sidebar = document.getElementById('sidebar');

            // Responsive Sidebar Toggle
            btnToggleSidebar.addEventListener('click', function() {
                if (window.innerWidth >= 992) {
                    layoutWrapper.classList.toggle('sidebar-collapsed');
                } else {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                }
            });

            // Dismiss Mobile Sidebar on backdrop click
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
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
            const swalTheme = SpTheme.getSwalTheme();
            
            <?php if (session()->getFlashdata('error')): ?>
                Swal.fire(SpTheme.mergeSwalOptions({
                    icon: 'error',
                    title: 'Gagal',
                    text: '<?= esc(session()->getFlashdata('error'), 'js') ?>'
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
