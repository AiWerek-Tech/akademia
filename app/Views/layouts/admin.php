<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'WMVAA Akademia' ?> | Integrated Academic Planning System</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom Sleek Styling -->
    <style>
        :root {
            --font-primary: 'Plus Jakarta Sans', sans-serif;
            --sidebar-width: 280px;
            --bg-sidebar: #0f172a; /* Slate 900 */
            --bg-sidebar-hover: #1e293b; /* Slate 800 */
            --sidebar-active-accent: #3b82f6; /* Blue 500 */
            --bg-body: #f8fafc; /* Slate 50 */
            --text-main: #334155; /* Slate 700 */
            --text-muted: #64748b; /* Slate 500 */
            --card-border-radius: 16px;
        }

        body {
            font-family: var(--font-primary);
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            color: #f1f5f9;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1030;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.15);
        }

        .sidebar-brand {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid #1e293b;
        }

        .brand-logo {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-subtitle {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 2px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 1rem;
            margin: 0;
            flex-grow: 1;
            overflow-y: auto;
        }

        .menu-section {
            font-size: 0.72rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1.5rem 0.75rem 0.5rem;
        }

        .menu-section:first-child {
            margin-top: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }

        .sidebar-menu a:hover {
            color: #fff;
            background-color: var(--bg-sidebar-hover);
        }

        .sidebar-menu li.active a {
            color: #fff;
            background-color: var(--sidebar-active-accent);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .sidebar-menu a i {
            font-size: 1.1rem;
        }

        /* Main Content Styling */
        .wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Topbar Styling */
        .topbar {
            height: 70px;
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sidebar-toggler {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-main);
            cursor: pointer;
            padding: 4px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .sidebar-toggler:hover {
            background-color: #f1f5f9;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .filter-select {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 0.4rem 2.2rem 0.4rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
            background-color: #fff;
            transition: all 0.2s;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--sidebar-active-accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }

        .notification-btn:hover {
            background-color: #f1f5f9;
            color: var(--text-main);
        }

        .notification-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-main);
            cursor: pointer;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .user-info {
            display: none;
        }

        @media (min-width: 768px) {
            .user-info {
                display: block;
                line-height: 1.2;
            }
            .user-name {
                font-weight: 600;
                font-size: 0.85rem;
                display: block;
            }
            .user-role {
                font-size: 0.75rem;
                color: var(--text-muted);
            }
        }

        /* Content Container */
        .content {
            padding: 2rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        /* Glassmorphism Panel styles */
        .panel-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .panel-glass:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }

        /* Breadcrumbs */
        .breadcrumb-item a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
        }
        .breadcrumb-item.active {
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Mobile Adjustments */
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                position: fixed;
                height: 100vh;
            }
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="bi bi-mortarboard-fill text-primary"></i>
                WMVAA Akademia
            </div>
            <div class="brand-subtitle">Academic Planning</div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="<?= current_url(true) === '/' ? 'active' : '' ?>">
                <a href="<?= base_url() ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <div class="menu-section">Organisasi</div>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-building"></i>
                    <span>Unit Sekolah</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-calendar-event"></i>
                    <span>Tahun & Semester</span>
                </a>
            </li>
            
            <div class="menu-section">Kurikulum & Guru</div>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-people"></i>
                    <span>Master Guru</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-journal-bookmark"></i>
                    <span>Mata Pelajaran</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-columns-gap"></i>
                    <span>Struktur Kurikulum</span>
                </a>
            </li>
            
            <div class="menu-section">Penugasan & Jadwal</div>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-person-workspace"></i>
                    <span>Penugasan Mengajar</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-speedometer2"></i>
                    <span>Beban Kerja Guru</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-calendar-week"></i>
                    <span>Jadwal Pelajaran</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-shield-check"></i>
                    <span>Jadwal Piket</span>
                </a>
            </li>
            
            <div class="menu-section">Dokumentasi</div>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-file-earmark-pdf"></i>
                    <span>SK Pembagian Tugas</span>
                </a>
            </li>
            
            <div class="menu-section">Sistem</div>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-database-fill-down"></i>
                    <span>Import Adapter</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-shield-lock"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="#" class="opacity-50 pointer-events-none">
                    <i class="bi bi-journal-text"></i>
                    <span>Audit Log</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Wrapper content -->
    <div class="wrapper">
        
        <!-- Topbar Navigation -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggler" id="sidebarToggler">
                    <i class="bi bi-list"></i>
                </button>
                <nav aria-label="breadcrumb" class="d-none d-sm-block m-0">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $breadcrumb_active ?? 'Dashboard' ?></li>
                    </ol>
                </nav>
            </div>
            
            <div class="topbar-right">
                <!-- Unit Selector Filter -->
                <select class="form-select filter-select d-none d-md-block" id="topbarUnitSelector">
                    <option value="ALL">Semua Unit (SMP & SMA)</option>
                    <option value="SMP">Unit SMP</option>
                    <option value="SMA">Unit SMA</option>
                </select>
                
                <!-- Period Selector Filter -->
                <select class="form-select filter-select d-none d-md-block" id="topbarPeriodSelector">
                    <option value="1">T.A 2026/2027 - Ganjil</option>
                    <option value="2">T.A 2026/2027 - Genap</option>
                </select>
                
                <!-- Notification -->
                <button class="notification-btn">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                
                <!-- Profile -->
                <div class="dropdown">
                    <div class="user-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100&h=100" alt="Avatar" class="user-avatar">
                        <div class="user-info">
                            <span class="user-name">Super Admin</span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="border-radius: 10px;">
                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-person me-2 text-muted"></i> Profil Saya</a></li>
                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-gear me-2 text-muted"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Dynamic Main Content -->
        <main class="content">
            <?= $this->renderSection('main_content') ?>
        </main>
        
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggler = document.getElementById('sidebarToggler');
            const sidebar = document.getElementById('sidebar');
            
            toggler.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && !sidebar.contains(e.target) && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
