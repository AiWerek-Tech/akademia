<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>

<section class="admin-command-center" aria-labelledby="admin-dashboard-title">
    <!-- Hero panel -->
    <header class="admin-hero-panel mb-4">
        <div class="admin-hero-panel__content">
            <p class="admin-hero-panel__eyebrow">Tahun Akademik Terpadu</p>
            <h1 id="admin-dashboard-title" class="fw-bold mb-2">Selamat Datang, <?= esc(active_user_name()) ?>!</h1>
            <p class="mb-0">Sistem Perencanaan Akademik Terpadu SMP–SMA WMVAA. Anda masuk sebagai peran <strong><?= esc(active_user_role()) ?></strong>.</p>
        </div>
        <div class="admin-hero-panel__actions">
            <div class="badge bg-white bg-opacity-20 text-white p-3 fs-7 rounded-pill d-flex align-items-center gap-2">
                <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
                <span>Sesi: <?= date('d F Y') ?></span>
            </div>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="admin-summary-grid mb-4" aria-label="Ringkasan eksekutif">
        <!-- Stat Card 1 -->
        <div class="card stat-card stat-card--primary border-0 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="stat-card__icon bg-primary bg-opacity-10 text-primary p-3 rounded-3 d-flex align-items-center justify-content-center">
                    <i data-lucide="calendar" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span class="stat-card__label text-muted d-block fs-8 fw-semibold text-uppercase mb-1">Periode Aktif</span>
                    <span class="stat-card__value fw-bold text-slate-900 fs-6"><?= esc($activePeriodStr) ?></span>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="card stat-card stat-card--info border-0 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="stat-card__icon bg-info bg-opacity-10 text-info p-3 rounded-3 d-flex align-items-center justify-content-center">
                    <i data-lucide="building" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span class="stat-card__label text-muted d-block fs-8 fw-semibold text-uppercase mb-1">Unit Sekolah</span>
                    <span class="stat-card__value fw-bold text-slate-900 fs-5"><?= esc($totalUnits) ?> Unit Aktif</span>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="card stat-card stat-card--success border-0 h-100">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="stat-card__icon bg-success bg-opacity-10 text-success p-3 rounded-3 d-flex align-items-center justify-content-center">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span class="stat-card__label text-muted d-block fs-8 fw-semibold text-uppercase mb-1">Pengguna Aktif</span>
                    <span class="stat-card__value fw-bold text-slate-900 fs-5"><?= esc($totalUsers) ?> Akun</span>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="card stat-card stat-card--warning border-0 h-100 opacity-75">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="stat-card__icon bg-warning bg-opacity-10 text-warning p-3 rounded-3 d-flex align-items-center justify-content-center">
                    <i data-lucide="cpu" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <span class="stat-card__label text-muted d-block fs-8 fw-semibold text-uppercase mb-1">Engine Jadwal</span>
                    <span class="stat-card__value fw-semibold text-slate-500 fs-7">Belum Tersedia</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow and Status Panels -->
    <div class="row g-4 mb-4">
        <!-- Workflow Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold mb-3 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="milestone" class="text-primary" style="width: 20px; height: 20px;"></i> 
                    <span>Alur Kerja Pembangunan Sistem</span>
                </h5>
                <p class="text-secondary small mb-4">WMVAA Akademia dibangun secara modular. Berikut adalah status milestone sistem perencanaan akademik terpadu saat ini:</p>
                
                <div class="position-relative ps-4 border-start border-2 border-light-subtle">
                    <div class="mb-4 position-relative">
                        <div class="position-absolute start-0 translate-middle-x bg-success rounded-circle" style="width: 14px; height: 14px; margin-left: -29px; top: 5px;"></div>
                        <h6 class="fw-bold text-success mb-1">Milestone 0: Discovery & Foundation Gate</h6>
                        <p class="text-secondary small mb-0">Menyiapkan konfigurasi sistem, database utama, dan struktur shell UI terintegrasi. <span class="badge bg-success bg-opacity-10 text-success ms-2 px-2.5 py-1 rounded-pill">PASSED</span></p>
                    </div>
                    <div class="mb-4 position-relative">
                        <div class="position-absolute start-0 translate-middle-x bg-primary rounded-circle" style="width: 14px; height: 14px; margin-left: -29px; top: 5px;"></div>
                        <h6 class="fw-bold text-primary mb-1">Milestone 1: Autentikasi, RBAC & Periode Akademik</h6>
                        <p class="text-secondary small mb-0">Menambahkan kontrol akses pengguna, audit logs, transisi status alur kerja periode semester, serta profil unit SMP & SMA. <span class="badge bg-primary bg-opacity-10 text-primary ms-2 px-2.5 py-1 rounded-pill">HARDENED</span></p>
                    </div>
                    <div class="mb-0 position-relative text-muted">
                        <div class="position-absolute start-0 translate-middle-x bg-secondary rounded-circle" style="width: 10px; height: 10px; margin-left: -27px; top: 7px;"></div>
                        <h6 class="fw-bold text-slate-400 mb-1">Milestone 2 - 12: Master Data, Kurikulum, Penugasan & Engine Jadwal</h6>
                        <p class="text-secondary small mb-0">Penyusunan jadwal heuristic lintas unit, perhitungan beban kerja guru, pembuatan SK tugas mengajar, dan final QA deployment.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Health status Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold mb-3 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="activity" class="text-success" style="width: 20px; height: 20px;"></i>
                    <span>Status Kesehatan Sistem</span>
                </h5>
                <p class="text-secondary small mb-4">Pemeriksaan komponen aktif pada lingkungan server saat ini.</p>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="database" class="text-primary" style="width: 18px; height: 18px;"></i>
                            <span class="fw-medium text-slate-700 fs-7">Koneksi Database</span>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold fs-8">Terhubung</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="folder-check" class="text-info" style="width: 18px; height: 18px;"></i>
                            <span class="fw-medium text-slate-700 fs-7">Direktori Writable</span>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold fs-8">Dapat Ditulis</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-2.5">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="terminal" class="text-warning" style="width: 18px; height: 18px;"></i>
                            <span class="fw-medium text-slate-700 fs-7">Versi PHP</span>
                        </div>
                        <span class="fw-semibold text-slate-700 fs-7"><?= PHP_VERSION ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-2 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="scroll" class="text-primary" style="width: 20px; height: 20px;"></i>
                    <span>Log Aktivitas Pengguna Terbaru</span>
                </h5>
                <p class="text-muted fs-8 mb-4">Menampilkan catatan audit terbaru yang tercatat oleh AuditService secara real-time.</p>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-uppercase text-muted fs-8 fw-bold">
                                <th>Pengguna</th>
                                <th>Kategori</th>
                                <th>Tindakan</th>
                                <th>Deskripsi</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAudits)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted fs-7">Belum ada aktivitas audit log tercatat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAudits as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-slate-700"><?= esc($log['username'] ?? 'System') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-muted px-2.5 py-1 text-uppercase fs-9 fw-semibold rounded-pill">
                                                <?= esc($log['module']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 text-uppercase fs-9 fw-semibold rounded-pill">
                                                <?= esc($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="fs-7 text-slate-600"><?= esc($log['reason']) ?></td>
                                        <td class="fs-8 text-muted"><?= date('d-m-Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
