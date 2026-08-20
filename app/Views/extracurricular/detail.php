<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$ipoo = $ipooHealth ?? [
    'overall_score'   => 3.5,
    'overall_percent' => 70,
    'status'          => ['label' => 'BAIK', 'class' => 'bg-primary text-white'],
];
$attendanceStats = $attStats ?? [
    'overall_attendance_pct' => 85,
    'total_sessions'         => (int) ($program['session_count'] ?? 0),
];
?>
<div class="container-fluid px-0 px-md-3" style="max-width:1150px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="trophy" class="w-3.5 h-3.5 me-1 d-inline-block"></i> <?= esc($program['category']) ?>
                </span>
                <?php
                $statusClass = match($program['status']) {
                    'ACTIVE'    => 'bg-success-subtle text-success',
                    'DRAFT'     => 'bg-warning-subtle text-warning',
                    'COMPLETED' => 'bg-primary-subtle text-primary',
                    'CANCELLED' => 'bg-danger-subtle text-danger',
                    default     => 'bg-secondary-subtle text-secondary',
                };
                ?>
                <span class="badge <?= $statusClass ?> rounded-pill px-2.5 py-1 text-xs"><?= $program['status'] ?></span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mb-1"><?= esc($program['title']) ?></h1>
            <p class="text-muted mb-0">
                <?php if ($program['code']): ?><span class="me-2"><?= esc($program['code']) ?></span> · <?php endif; ?>
                Pembina / Pelatih: <strong><?= esc($program['coach_name'] ?? 'Belum ditentukan') ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (has_permission('extracurricular.manage')): ?>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm rounded-pill shadow-sm"><i data-lucide="pencil" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Edit</a>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/members') ?>" class="btn btn-outline-info btn-sm rounded-pill shadow-sm"><i data-lucide="users" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Anggota</a>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/sessions') ?>" class="btn btn-outline-success btn-sm rounded-pill shadow-sm"><i data-lucide="calendar" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Sesi</a>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/competencies') ?>" class="btn btn-outline-warning btn-sm rounded-pill shadow-sm"><i data-lucide="award" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kompetensi</a>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/evaluations') ?>" class="btn btn-outline-dark btn-sm rounded-pill shadow-sm"><i data-lucide="clipboard-list" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Evaluasi (IPOO)</a>
                <a href="<?= base_url('extracurricular/' . $program['id'] . '/reports') ?>" class="btn btn-outline-purple btn-sm rounded-pill shadow-sm"><i data-lucide="file-text" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Laporan Rapor</a>
            <?php endif; ?>
            <a href="<?= base_url('extracurricular') ?>" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm"><i data-lucide="arrow-left" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kembali</a>
        </div>
    </div>

    <!-- Stats & IPOO Scorecard -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Anggota Aktif</div>
                <div class="h3 fw-bold text-primary mb-0"><?= $program['member_count'] ?></div>
                <div class="text-xs text-muted mt-1"><?= $program['max_members'] ? 'Kapasitas: ' . (int) $program['max_members'] : 'Tanpa Kuota' ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Total Sesi</div>
                <div class="h3 fw-bold text-success mb-0"><?= $program['session_count'] ?></div>
                <div class="text-xs text-muted mt-1">Presensi: <?= $attendanceStats['overall_attendance_pct'] ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Standar Keterampilan</div>
                <div class="h3 fw-bold text-warning mb-0"><?= $program['competency_count'] ?></div>
                <div class="text-xs text-muted mt-1">Kompetensi & Lencana</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Indeks Mutu IPOO</div>
                <div class="h3 fw-bold text-purple mb-0"><?= $ipoo['overall_percent'] ?>%</div>
                <div class="text-xs text-muted mt-1"><span class="badge <?= $ipoo['status']['class'] ?> rounded-pill"><?= $ipoo['status']['label'] ?></span></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Info Card -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0 text-gray-900">Rasional & Deskripsi Program</h5></div>
                <div class="card-body p-4 pt-2">
                    <?php if ($program['rationale']): ?>
                        <h6 class="fw-bold text-muted text-xs text-uppercase mb-1">Latar Belakang & Kebutuhan</h6>
                        <p class="small text-muted mb-3 lh-base"><?= nl2br(esc($program['rationale'])) ?></p>
                    <?php endif; ?>
                    <?php if ($program['objective']): ?>
                        <h6 class="fw-bold text-muted text-xs text-uppercase mb-1">Tujuan Program</h6>
                        <p class="small text-muted mb-3 lh-base"><?= nl2br(esc($program['objective'])) ?></p>
                    <?php endif; ?>
                    <?php if ($program['description']): ?>
                        <h6 class="fw-bold text-muted text-xs text-uppercase mb-1">Deskripsi & Ruang Lingkup</h6>
                        <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($program['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (! $program['rationale'] && ! $program['objective'] && ! $program['description']): ?>
                        <p class="text-muted mb-0 py-3"><em>Belum ada deskripsi program yang diisi.</em></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Schedule & Funding -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0 text-gray-900">Jadwal & Tempat Latihan</h5></div>
                <div class="card-body p-4 pt-2">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tr><td class="text-muted fw-semibold" style="width:130px">Hari Latihan</td><td><?= esc($program['meeting_day'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Jam Pelaksanaan</td><td><?= esc($program['meeting_time'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Lokasi</td><td><?= esc($program['location'] ?? '—') ?></td></tr>
                    </table>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0 text-gray-900">Anggaran & Manajemen</h5></div>
                <div class="card-body p-4 pt-2">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tr><td class="text-muted fw-semibold" style="width:130px">Sumber Pendanaan</td><td><?= esc($program['funding_source'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Alokasi Anggaran</td><td><?= $program['funding_amount'] ? 'Rp ' . number_format((float) $program['funding_amount'], 0, ',', '.') : '—' ?></td></tr>
                        <tr><td class="text-muted fw-semibold">Kuota Anggota</td><td><?= esc($program['max_members'] ?? '—') ?> peserta</td></tr>
                        <tr><td class="text-muted fw-semibold">Catatan Pengelola</td><td><?= esc($program['management_notes'] ?? '—') ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Transition & Actions -->
    <?php if (has_permission('extracurricular.manage')): ?>
    <div class="card border-0 shadow-sm rounded-4 mt-4 mb-5">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1 text-gray-900">Ubah Status Program</h6>
                    <p class="text-muted text-xs mb-2">Transisikan status program ekstrakurikuler sesuai siklus berjalan.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($statuses as $s): ?>
                            <?php if ($s !== $program['status']): ?>
                                <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/transition') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $s ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><?= $s ?></button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <form method="POST" action="<?= base_url('extracurricular/' . $program['id'] . '/delete') ?>" onsubmit="return confirm('Hapus program ekstrakurikuler ini beserta seluruh sesi dan anggotanya?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Hapus Program
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
<?= $this->endSection() ?>
