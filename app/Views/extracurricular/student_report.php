<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$narrative = $narrative ?? ((new \App\Services\ExtracurricularService())->generateStudentNarrative((int) $report['program']['id'], (int) $report['member']['student_id']));
?>
<div class="container-fluid px-0 px-md-3" style="max-width:950px">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $report['program']['id']) ?>"><?= esc($report['program']['title']) ?></a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('extracurricular/' . $report['program']['id'] . '/reports') ?>">Laporan</a></li>
                    <li class="breadcrumb-item active"><?= esc($report['member']['student_name']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-0">Lembar Capaian Ekstrakurikuler</h1>
            <p class="text-muted mb-0"><?= esc($report['member']['student_name']) ?> — <?= esc($report['member']['classroom_name'] ?? 'Kelas Siswa') ?></p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-dark shadow-sm rounded-pill px-3" onclick="window.print()">
                <i data-lucide="printer" class="w-4 h-4 me-1"></i> Cetak Lembar Rapor
            </button>
            <a href="<?= base_url('extracurricular/' . $report['program']['id'] . '/reports') ?>" class="btn btn-outline-secondary shadow-sm rounded-pill px-3">
                <i data-lucide="arrow-left" class="w-4 h-4 me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Student Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Tingkat Presensi</div>
                <div class="h3 fw-bold text-primary mb-0"><?= $report['attendance_rate'] ?>%</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Total Prestasi</div>
                <div class="h3 fw-bold text-success mb-0"><?= count($report['achievements']) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Kompetensi</div>
                <div class="h3 fw-bold text-warning mb-0"><?= $report['achieved_competencies'] ?>/<?= $report['total_competencies'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Sesi Latihan</div>
                <div class="h3 fw-bold text-info mb-0"><?= $report['total_sessions'] ?> Sesi</div>
            </div>
        </div>
    </div>

    <!-- ✨ Draf Narasi Rapor Ekstrakurikuler -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="fw-bold text-gray-900 mb-1"><i data-lucide="sparkles" class="w-4 h-4 me-1 text-purple"></i> Draf Narasi Deskriptif Rapor (Kualitatif)</h6>
                    <small class="text-muted">Siap disalin ke rapor cetak Kurikulum Merdeka atau K13.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-purple shadow-sm rounded-pill px-3 text-xs" id="btnCopySingleStudentReport">
                    <i data-lucide="copy" class="w-3.5 h-3.5 me-1"></i> Salin Narasi
                </button>
            </div>
            <div class="p-3 bg-light-subtle rounded-3 border mt-2">
                <p class="small text-dark mb-0 lh-base" id="narrativeStudentText" style="font-size: 0.9rem;">
                    <?= nl2br(esc($narrative)) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Attendance Detail -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0 text-gray-900">Rekapitulasi Kehadiran</h5></div>
        <div class="card-body p-4 pt-0">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-success-subtle">
                        <i data-lucide="check-circle" class="text-success" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-success"><?= $report['present_count'] ?></div><div class="text-muted small">Hadir</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-warning-subtle">
                        <i data-lucide="clock" class="text-warning" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-warning"><?= $report['late_count'] ?></div><div class="text-muted small">Terlambat</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-danger-subtle">
                        <i data-lucide="x-circle" class="text-danger" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-danger"><?= $report['absent_count'] ?></div><div class="text-muted small">Tidak Hadir</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-info-subtle">
                        <i data-lucide="shield" class="text-info" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-info"><?= $report['excused_count'] ?></div><div class="text-muted small">Izin</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements & Badges -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0 text-gray-900">Pencapaian Kompetensi & Lencana</h5></div>
        <div class="card-body p-4 pt-0">
            <?php if (empty($report['achievements'])): ?>
                <p class="text-muted mb-0 py-3"><em>Belum ada pencapaian atau lencana tercatat untuk peserta didik ini.</em></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5">Kompetensi / Keterampilan</th>
                                <th class="py-2.5">Tanggal</th>
                                <th class="py-2.5 text-center">Level Capaian</th>
                                <th class="py-2.5 text-center">Skor</th>
                                <th class="py-2.5">Catatan Penghargaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['achievements'] as $a): ?>
                                <tr>
                                    <td class="fw-bold text-gray-900 py-2.5"><?= esc($a['competency_name'] ?? '—') ?></td>
                                    <td class="py-2.5 small"><?= $a['achieved_date'] ? date('d M Y', strtotime($a['achieved_date'])) : '—' ?></td>
                                    <td class="py-2.5 text-center">
                                        <?php if ($a['level']): ?>
                                            <span class="badge bg-success-subtle text-success rounded-pill"><?= esc($a['level']) ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td class="py-2.5 text-center small"><?= $a['score'] ? number_format((float) $a['score'], 1) : '—' ?></td>
                                    <td class="py-2.5 text-muted small"><?= esc($a['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const btnCopy = document.getElementById('btnCopySingleStudentReport');
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            const txt = document.getElementById('narrativeStudentText')?.textContent.trim() || '';
            if (txt) {
                navigator.clipboard.writeText(txt).then(() => {
                    alert('Narasi rapor kualitatif berhasil disalin!');
                });
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
