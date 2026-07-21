<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1 text-slate-800">Selamat Datang di WMVAA Akademia!</h2>
        <p class="text-secondary m-0">Sistem Perencanaan Akademik Terpadu SMP–SMA WMVAA. Di sini Anda dapat mengelola perencanaan kurikulum, penugasan guru, beban kerja, dan penyusunan jadwal terpadu.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 py-2 border-0" style="border-radius: 10px; background-color: var(--sidebar-active-accent); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
            <i class="bi bi-plus-lg"></i> Mulai Perencanaan Baru
        </button>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card panel-glass p-3 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <span class="text-secondary d-block fs-7 fw-medium mb-1">Periode Aktif (Mockup)</span>
                    <span class="fw-bold text-slate-900 fs-5">Belum tersedia</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card panel-glass p-3 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-4 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <span class="text-secondary d-block fs-7 fw-medium mb-1">Guru Terdaftar (Mockup)</span>
                    <span class="fw-bold text-slate-900 fs-5">0 Guru</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card panel-glass p-3 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-4 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <span class="text-secondary d-block fs-7 fw-medium mb-1">Mata Pelajaran (Mockup)</span>
                    <span class="fw-bold text-slate-900 fs-5">0 Mapel</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card panel-glass p-3 border-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-4 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <span class="text-secondary d-block fs-7 fw-medium mb-1">Status Jadwal (Mockup)</span>
                    <span class="fw-bold text-slate-900 fs-5">Belum tersedia</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Workflow and Status Panels -->
<div class="row g-4">
    <!-- Workflow Card -->
    <div class="col-lg-8">
        <div class="card panel-glass p-4 border-0 h-100">
            <h4 class="fw-bold mb-3"><i class="bi bi-git me-2 text-primary"></i> Alur Kerja Perencanaan Akademik</h4>
            <p class="text-secondary mb-4">Sistem ini memandu Anda melalui milestone penyusunan struktur pengajaran terintegrasi lintas unit SMP-SMA.</p>
            
            <div class="position-relative ps-4 border-start border-2 border-light-subtle">
                <div class="mb-4 position-relative">
                    <div class="position-absolute start-0 translate-middle-x bg-primary rounded-circle" style="width: 14px; height: 14px; margin-left: -29px; top: 5px;"></div>
                    <h6 class="fw-bold text-primary mb-1">Milestone 0: Discovery & Foundation</h6>
                    <p class="text-secondary small">Menyiapkan konfigurasi sistem, database utama, dan struktur shell UI terintegrasi. <span class="badge bg-success bg-opacity-10 text-success ms-2">Completed</span></p>
                </div>
                <div class="mb-4 position-relative">
                    <div class="position-absolute start-0 translate-middle-x bg-secondary rounded-circle" style="width: 10px; height: 10px; margin-left: -27px; top: 7px;"></div>
                    <h6 class="fw-bold text-slate-700 mb-1">Milestone 1: Autentikasi, RBAC & Unit Sekolah</h6>
                    <p class="text-secondary small">Menambahkan manajemen akses pengguna, role penugasan (Super Admin, Wakasek, Guru), serta inisialisasi unit SMP & SMA.</p>
                </div>
                <div class="mb-0 position-relative">
                    <div class="position-absolute start-0 translate-middle-x bg-secondary rounded-circle" style="width: 10px; height: 10px; margin-left: -27px; top: 7px;"></div>
                    <h6 class="fw-bold text-slate-700 mb-1">Milestone 2 - 12: Master Data, Kurikulum, Penugasan & Engine Jadwal</h6>
                    <p class="text-secondary small">Penyusunan jadwal otomatis heuristic lintas unit, perhitungan beban kerja guru, pembuatan SK tugas mengajar, dan final QA deployment.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Health status Card -->
    <div class="col-lg-4">
        <div class="card panel-glass p-4 border-0 h-100">
            <h4 class="fw-bold mb-3"><i class="bi bi-activity me-2 text-success"></i> Status Kesehatan Sistem</h4>
            <p class="text-secondary small mb-4">Pemeriksaan komponen aktif pada lingkungan server saat ini.</p>
            
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-database text-primary fs-5"></i>
                        <span class="fw-medium text-slate-700">Koneksi Database</span>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold">Terhubung</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-folder-check text-info fs-5"></i>
                        <span class="fw-medium text-slate-700">Direktori Writable</span>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1.5 fw-semibold">Dapat Ditulis</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cpu text-warning fs-5"></i>
                        <span class="fw-medium text-slate-700">Versi PHP</span>
                    </div>
                    <span class="fw-semibold text-slate-700"><?= PHP_VERSION ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
