<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Deprecation Notice -->
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
        <div class="d-flex align-items-start gap-3">
            <div class="p-2 bg-info bg-opacity-10 rounded-circle flex-shrink-0">
                <i data-lucide="info" class="text-info" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Data Presensi Lama (Legacy)</h5>
                <p class="mb-2">Data presensi ini berasal dari sistem sebelumnya. Untuk pencatatan presensi baru, gunakan <strong>Ruang Mengajar (Teaching Workspace)</strong>.</p>
                <a href="<?= base_url('teaching/today') ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i data-lucide="arrow-right" class="me-1" style="width: 14px; height: 14px;"></i>
                    Buka Ruang Mengajar
                </a>
            </div>
        </div>
    </div>

    <!-- Legacy Session Data -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1">
                    <i data-lucide="clock" class="me-1" style="width: 14px; height: 14px;"></i>
                    Legacy Session
                </span>
                <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                    <?= esc($legacySession['status'] ?? 'DRAFT') ?>
                </span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4">
                        <div class="small text-muted mb-1">Kelas</div>
                        <div class="fw-bold text-dark"><?= esc($legacySession['classroom_name'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4">
                        <div class="small text-muted mb-1">Mata Pelajaran</div>
                        <div class="fw-bold text-dark"><?= esc($legacySession['subject_name'] ?? '-') ?> (<?= esc($legacySession['subject_code'] ?? '-') ?>)</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-4">
                        <div class="small text-muted mb-1">Tanggal</div>
                        <div class="fw-bold text-dark"><?= esc($legacySession['session_date'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-4">
                        <div class="small text-muted mb-1">Pertemuan</div>
                        <div class="fw-bold text-dark">#<?= (int) ($legacySession['meeting_number'] ?? 1) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-4">
                        <div class="small text-muted mb-1">Topik</div>
                        <div class="fw-bold text-dark"><?= esc($legacySession['topic'] ?? '-') ?></div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-warning bg-opacity-10 rounded-4 border border-warning border-opacity-25">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i data-lucide="alert-triangle" class="text-warning" style="width: 16px; height: 16px;"></i>
                    <strong class="text-warning">Informasi</strong>
                </div>
                <p class="small text-muted mb-0">
                    Data ini bersifat <strong>read-only</strong> dari sistem presensi lama. ID sesi: <code><?= esc($legacySession['legacy_id'] ?? '-') ?></code>.
                    Untuk modifikasi data, buka sesi baru di Ruang Mengajar.
                </p>
            </div>

            <div class="d-flex gap-2 mt-4">
                <a href="<?= base_url('teaching/today') ?>" class="btn btn-primary rounded-pill px-4">
                    <i data-lucide="arrow-right" class="me-1" style="width: 14px; height: 14px;"></i>
                    Buka Ruang Mengajar
                </a>
                <a href="<?= base_url('portal/attendance/session/' . ($legacySession['legacy_id'] ?? 0) . '/print') ?>" class="btn btn-outline-secondary rounded-pill px-4" target="_blank">
                    <i data-lucide="printer" class="me-1" style="width: 14px; height: 14px;"></i>
                    Cetak Jurnal Lama
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
