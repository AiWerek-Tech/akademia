<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-indigo-subtle text-indigo px-2.5 py-1 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Phase 11 Universal Sync
                </span>
                <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill fw-semibold text-xs">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kodular Offline-First
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Gateway Integrasi & Sinkronisasi Mobile</h1>
            <p class="text-muted mb-0">Manajemen versioning delta sync, kontrol sesi aplikasi mobile, dan gateway penyimpanan berkas.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('api/v1/sync?action=health') ?>" target="_blank" class="btn btn-outline-success shadow-sm rounded-pill px-3">
                <i data-lucide="activity" class="w-4 h-4 me-1 d-inline-block"></i> Status API
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i data-lucide="check-circle" class="w-5 h-5 me-2 d-inline-block"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i data-lucide="alert-triangle" class="w-5 h-5 me-2 d-inline-block"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Top KPI Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary-subtle p-3 text-primary"><i data-lucide="database" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h3 fw-bold mb-0 text-gray-900"><?= count($tables) ?></div>
                            <div class="text-muted small">Tabel Terdaftar</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success-subtle p-3 text-success"><i data-lucide="smartphone" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h3 fw-bold mb-0 text-gray-900"><?= $overview['active_sessions'] ?? 0 ?></div>
                            <div class="text-muted small">Sesi Mobile Aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-info-subtle p-3 text-info"><i data-lucide="folder" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h3 fw-bold mb-0 text-gray-900"><?= $overview['total_files'] ?? 0 ?></div>
                            <div class="text-muted small">Berkas Terunggah</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning-subtle p-3 text-warning"><i data-lucide="zap" class="w-6 h-6"></i></div>
                        <div>
                            <div class="h4 fw-bold mb-0 text-success">ONLINE</div>
                            <div class="text-muted small">Delta Sync Engine</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="row g-4 mb-4">
        <!-- Left: Version Registry & Table Sync -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="layers" class="w-5 h-5 me-1 text-primary d-inline-block"></i> Registry Versi Tabel (Delta Sync)</h5>
                        <p class="text-muted small mb-0">Kontrol versioning otomatis per unit. Menaikkan versi akan memicu pembaruan TinyDB pada klien mobile.</p>
                    </div>
                </div>
                <div class="card-body p-4 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Entitas / Tabel</th>
                                    <th class="text-center">Versi Terkini</th>
                                    <th>Aksi Pembaruan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $t): ?>
                                    <?php $currentVer = $overview['versions'][$t] ?? 1; ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-gray-900 text-uppercase" style="font-size: 13px;"><?= esc($t) ?></div>
                                            <span class="text-muted small">Tabel tersinkronisasi offline TinyDB</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary px-3 py-1.5 rounded-pill fw-bold" style="font-size: 12px;">v<?= $currentVer ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" action="<?= base_url('system/sync/bump/' . $t) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none">
                                                    <i data-lucide="arrow-up-circle" class="w-4 h-4 me-1 d-inline-block"></i> Update Versi (+1)
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Connected Devices & Files -->
        <div class="col-lg-5">
            <!-- Active Sessions -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="smartphone" class="w-5 h-5 me-1 text-success d-inline-block"></i> Sesi Mobile Terkini</h5>
                    <p class="text-muted small mb-0">Perangkat pengguna yang baru saja melakukan sinkronisasi data.</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($overview['recent_syncs'])): ?>
                        <div class="text-center py-4 text-muted">
                            <i data-lucide="wifi-off" class="w-8 h-8 mb-2 d-inline-block text-muted"></i>
                            <p class="small mb-0">Belum ada perangkat mobile yang terhubung.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($overview['recent_syncs'] as $s): ?>
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-gray-900 small"><?= esc($s['full_name'] ?? $s['username']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            Peran: <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= esc($s['app_role']) ?></span>
                                            · Sync: <?= date('d M H:i', strtotime($s['last_sync_at'] ?? $s['created_at'])) ?>
                                        </div>
                                    </div>
                                    <?php if (! $s['is_revoked'] && strtotime($s['expires_at']) > time()): ?>
                                        <form method="POST" action="<?= base_url('system/sync/revoke/' . $s['id']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" title="Cabut Akses Sesi" style="font-size: 11px;">
                                                Cabut
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill">Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Uploaded Files Vault -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="file-check" class="w-5 h-5 me-1 text-info d-inline-block"></i> Berkas Media Terdaftar</h5>
                    <p class="text-muted small mb-0">Penyimpanan berkas terintegrasi mobile & web.</p>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($files)): ?>
                        <div class="text-center py-4 text-muted">
                            <i data-lucide="inbox" class="w-8 h-8 mb-2 d-inline-block text-muted"></i>
                            <p class="small mb-0">Belum ada berkas media terunggah.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($files as $f): ?>
                                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i data-lucide="file" class="w-4 h-4 text-muted"></i>
                                        <div>
                                            <div class="fw-semibold text-gray-900 small text-truncate" style="max-width: 180px;"><?= esc($f['original_name']) ?></div>
                                            <span class="text-muted" style="font-size: 11px;"><?= esc($f['category']) ?> · <?= round($f['file_size_kb']) ?> KB</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-muted border rounded-pill"><?= esc($f['file_type']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- API Reference Accordion -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <h5 class="fw-bold text-gray-900 mb-1"><i data-lucide="code" class="w-5 h-5 me-1 text-purple d-inline-block"></i> Referensi Protokol API Central Router (Aturan Global WMVAA)</h5>
            <p class="text-muted small mb-0">Spesifikasi format endpoint untuk aplikasi mobile Kodular / client API.</p>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="fw-bold text-primary mb-1">1. Autentikasi Mobile</div>
                        <code class="text-dark small d-block mb-2">POST <?= base_url('api/v1/sync?action=login') ?></code>
                        <div class="text-muted small"><strong>Body:</strong> <code>{ "username": "...", "password": "..." }</code></div>
                        <div class="text-muted small mt-1"><strong>Response:</strong> <code>{ "status": "success", "data": { "token": "...", "versions": {...} } }</code></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="fw-bold text-success mb-1">2. Sinkronisasi Penuh (Initial Install)</div>
                        <code class="text-dark small d-block mb-2">GET <?= base_url('api/v1/sync?action=sync_all') ?></code>
                        <div class="text-muted small"><strong>Header:</strong> <code>Authorization: Bearer &lt;token&gt;</code></div>
                        <div class="text-muted small mt-1"><strong>Output:</strong> Seluruh dataset tersimpan di TinyDB lokal perangkat.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="fw-bold text-warning mb-1">3. Sinkronisasi Delta (Bandwidth-Optimized)</div>
                        <code class="text-dark small d-block mb-2">POST <?= base_url('api/v1/sync?action=sync_delta') ?></code>
                        <div class="text-muted small"><strong>Body:</strong> <code>{ "versions": { "jadwal": 2, "pengumuman": 1 } }</code></div>
                        <div class="text-muted small mt-1"><strong>Output:</strong> Hanya mengembalikan tabel yang versinya lebih baru di server.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="fw-bold text-info mb-1">4. Unggah Berkas & Media</div>
                        <code class="text-dark small d-block mb-2">POST <?= base_url('api/v1/sync?action=upload_file') ?></code>
                        <div class="text-muted small"><strong>Form-Data:</strong> <code>file: [binary], category: "STUDENT_MEDIA"</code></div>
                        <div class="text-muted small mt-1"><strong>Header:</strong> <code>Authorization: Bearer &lt;token&gt;</code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
