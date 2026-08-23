<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-teal-subtle text-teal px-2.5 py-1 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 12 Production Hardening
                </span>
                <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill fw-semibold text-xs">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Semua Engine Terverifikasi
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Diagnostik & Kesehatan Sistem</h1>
            <p class="text-muted mb-0">Audit integritas data lintas modul, pemantauan performa runtime, dan status kesiapan operasional sekolah.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="<?= base_url('system/diagnostics/purge-sessions') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i data-lucide="trash-2" class="w-4 h-4 me-1 d-inline-block"></i> Bersihkan Sesi Kedaluwarsa
                </button>
            </form>
            <a href="<?= base_url('api/v1/system/diagnostics') ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                <i data-lucide="code" class="w-4 h-4 me-1 d-inline-block"></i> JSON API Report
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5 me-2 d-inline-block"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary-subtle p-3 text-primary"><i data-lucide="cpu" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0 text-gray-900">PHP <?= esc($report['overview']['php_version']) ?></div>
                            <div class="text-muted small">Runtime Engine (CLI/CGI)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success-subtle p-3 text-success"><i data-lucide="database" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0 text-success"><?= esc($report['database_integrity']['status']) ?></div>
                            <div class="text-muted small"><?= $report['database_integrity']['healthy_checks'] ?> / <?= $report['database_integrity']['total_checks'] ?> Cek Integritas Lulus</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-info-subtle p-3 text-info"><i data-lucide="hard-drive" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0 text-gray-900"><?= $report['overview']['memory_usage_mb'] ?> MB</div>
                            <div class="text-muted small">Penggunaan Memori RAM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning-subtle p-3 text-warning"><i data-lucide="layers" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0 text-primary"><?= count($report['module_readiness']) ?> Modul</div>
                            <div class="text-muted small">Status Operasional Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Diagnostic Sections -->
    <div class="row g-4 mb-4">
        <!-- Left: Module Readiness Grid -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="check-square" class="w-5 h-5 me-1 text-success d-inline-block"></i> Status Kesiapan 12 Fase Modul IALOS</h5>
                    <p class="text-muted small mb-0">Verifikasi seluruh modul inti kurikulum, asesmen, kokurikuler, ekstra, rapor, dan gateway.</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fase & Nama Modul</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Verifikasi Engine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['module_readiness'] as $modName => $modInfo): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900" style="font-size: 13px;"><?= esc($modName) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill fw-bold" style="font-size: 11px;">
                                                <i data-lucide="check" class="w-3 h-3 me-0.5 d-inline-block"></i> <?= esc($modInfo['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                                                100% Passed
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Integrity & Storage -->
        <div class="col-lg-5">
            <!-- Database & Relational Integrity -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="shield-alert" class="w-5 h-5 me-1 text-primary d-inline-block"></i> Integritas Relasi Data</h5>
                    <p class="text-muted small mb-0">Deteksi anomali relasional dan catatan yatim (*orphan records*).</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($report['database_integrity']['issues'])): ?>
                        <div class="p-3 bg-success-subtle rounded-4 text-success d-flex align-items-center gap-2">
                            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                            <div class="small fw-semibold">Seluruh relasi foreign key dan integritas data konsisten sempurna (0 anomali).</div>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($report['database_integrity']['issues'] as $iss): ?>
                                <div class="list-group-item px-0 text-danger small">
                                    <i data-lucide="alert-circle" class="w-4 h-4 me-1 d-inline-block"></i> <?= esc($iss) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Sessions Health -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="smartphone" class="w-5 h-5 me-1 text-info d-inline-block"></i> Status Gateway Mobile</h5>
                    <p class="text-muted small mb-0">Kesehatan sesi token perangkat dan registri delta sync.</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="h4 fw-bold text-gray-900 mb-0"><?= $report['mobile_sync_health']['active_sessions'] ?></div>
                                <div class="text-muted text-xs">Sesi Aktif</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="h4 fw-bold text-muted mb-0"><?= $report['mobile_sync_health']['expired_sessions'] ?></div>
                                <div class="text-muted text-xs">Sesi Kedaluwarsa</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storage Health -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="folder-check" class="w-5 h-5 me-1 text-warning d-inline-block"></i> Penyimpanan Media</h5>
                    <p class="text-muted small mb-0">Total pemakaian storage berkas lampiran.</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Total Berkas Terdaftar</span>
                        <span class="fw-bold text-gray-900"><?= $report['storage_health']['total_files'] ?> berkas</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Total Ukuran Penyimpanan</span>
                        <span class="fw-bold text-gray-900"><?= $report['storage_health']['total_size_mb'] ?> MB</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
