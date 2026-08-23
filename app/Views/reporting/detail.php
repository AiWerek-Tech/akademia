<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1150px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li>
                    <li class="breadcrumb-item active"><?= esc($snapshot['student_name'] ?? '') ?></li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1"><?= esc($snapshot['student_name'] ?? '') ?></h1>
            <p class="text-muted mb-0">
                <?= esc($snapshot['classroom_name'] ?? '') ?> · NIS: <?= esc($snapshot['student_number'] ?? '—') ?> · <?= $snapshot['snapshot_type'] ?> ·
                <?php $sc = match($snapshot['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill px-2.5 py-1"><?= $snapshot['status'] ?></span>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('reporting/' . $snapshot['id'] . '/print') ?>" target="_blank" class="btn btn-outline-dark shadow-sm rounded-pill px-3">
                <i data-lucide="printer" class="w-4 h-4 me-1 d-inline-block"></i> Cetak Lembar Rapor
            </a>
            <?php if (has_permission('reporting.manage')): ?>
                <?php if ($snapshot['status'] === 'DRAFT'): ?>
                    <form method="POST" action="<?= base_url('reporting/' . $snapshot['id'] . '/lock') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-warning shadow-sm rounded-pill px-3">
                            <i data-lucide="lock" class="w-4 h-4 me-1 d-inline-block"></i> Kunci Rapor
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($snapshot['status'] !== 'PUBLISHED'): ?>
                    <form method="POST" action="<?= base_url('reporting/' . $snapshot['id'] . '/publish') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary shadow-sm rounded-pill px-4">
                            <i data-lucide="send" class="w-4 h-4 me-1 d-inline-block"></i> Terbitkan Rapor
                        </button>
                    </form>
                <?php endif; ?>
                <a href="<?= base_url('reporting/portfolio/' . $snapshot['student_id']) ?>" class="btn btn-outline-info shadow-sm rounded-pill px-3">
                    <i data-lucide="briefcase" class="w-4 h-4 me-1 d-inline-block"></i> Portofolio
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Mata Pelajaran</div>
                <div class="h3 fw-bold text-primary mb-0"><?= count($snapshot['subjects'] ?? []) ?> Mapel</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Ekstrakurikuler</div>
                <div class="h3 fw-bold text-success mb-0"><?= count($snapshot['extracurriculars'] ?? []) ?> Kegiatan</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Kokurikuler (P5)</div>
                <div class="h3 fw-bold text-warning mb-0"><?= count($snapshot['cocurriculars'] ?? []) ?> Projek</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100">
                <div class="text-xs text-muted text-uppercase fw-semibold mb-1">Kehadiran (Hadir)</div>
                <div class="h3 fw-bold text-info mb-0"><?= (int) ($snapshot['attendance_summary']['hadir'] ?? 0) ?> Hari</div>
            </div>
        </div>
    </div>

    <!-- A. Capaian Intrakurikuler -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-gray-900 mb-0"><i data-lucide="book-open" class="w-5 h-5 me-1 text-primary"></i> A. Capaian Mata Pelajaran & Narasi Kompetensi</h5>
    </div>

    <?php if (empty($snapshot['subjects'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="text-center py-5">
                <p class="text-muted mb-0">Belum ada data mata pelajaran. Generate ulang laporan jika diperlukan.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($snapshot['subjects'] as $subject): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-gray-900 mb-0"><?= esc($subject['subject_name'] ?? '—') ?></h5>
                        <small class="text-muted">Kode: <?= esc($subject['subject_code'] ?? '—') ?> · Kategori: <?= esc($subject['subject_category'] ?? 'Umum') ?></small>
                    </div>
                    <span class="text-muted small">Guru: <strong><?= esc($subject['teacher_name'] ?? '—') ?></strong></span>
                </div>
                <div class="card-body p-4 pt-2">
                    <!-- Scores -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-primary mb-0"><?= $subject['final_score'] !== null ? number_format((float) $subject['final_score'], 0) : '—' ?></div>
                                <div class="text-muted small">Nilai Akhir</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-success mb-0"><?= esc($subject['final_predicate'] ?? '—') ?></div>
                                <div class="text-muted small">Predikat</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-info mb-0"><?= $subject['mastery_pct'] !== null ? $subject['mastery_pct'] . '%' : '—' ?></div>
                                <div class="text-muted small">Penguasaan Kompetensi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-warning mb-0"><?= $subject['tp_coverage_pct'] !== null ? $subject['tp_coverage_pct'] . '%' : '—' ?></div>
                                <div class="text-muted small">Cakupan TP</div>
                            </div>
                        </div>
                    </div>

                    <!-- Narratives -->
                    <h6 class="fw-bold mb-2 text-gray-900"><i data-lucide="message-square" class="w-4 h-4 me-1 text-purple"></i> Narasi Deskriptif Capaian Pembelajaran</h6>
                    <?php if (empty($subject['narratives'])): ?>
                        <div class="p-3 rounded-3 bg-light-subtle border mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge bg-info-subtle text-info rounded-pill" style="font-size:.65rem">Auto-Generated Draft</span>
                            </div>
                            <p class="mb-0 small text-muted"><?= nl2br(esc($subject['auto_narrative'])) ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($subject['narratives'] as $n): ?>
                            <div class="p-3 rounded-3 bg-light mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="badge bg-<?= $n['source'] === 'AI' ? 'info' : 'primary' ?>-subtle text-<?= $n['source'] === 'AI' ? 'info' : 'primary' ?> rounded-pill" style="font-size:.65rem"><?= $n['source'] ?></span>
                                    <span class="text-muted" style="font-size:.65rem"><?= $n['narrative_type'] ?></span>
                                </div>
                                <p class="mb-0 small"><?= nl2br(esc($n['content'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Add/Edit Narrative Form -->
                    <?php if (has_permission('reporting.manage') && ($snapshot['status'] === 'DRAFT' || $snapshot['status'] === 'LOCKED')): ?>
                        <form method="POST" action="<?= base_url('reporting/narrative/' . $subject['id'] . '/save') ?>" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="narrative_type" value="<?= \App\Services\ReportingService::NARRATIVE_SUBJECT ?>">
                            <input type="hidden" name="source" value="<?= \App\Services\ReportingService::SOURCE_TEACHER ?>">
                            <div class="mb-2">
                                <textarea name="content" class="form-control form-control-sm" rows="2" placeholder="Tulis atau perbaiki narasi capaian belajar..."><?= esc($subject['auto_narrative']) ?></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i data-lucide="save" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Simpan Narasi
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- B. Capaian Ekstrakurikuler & Karakter (Fase 8) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <h5 class="fw-bold text-gray-900 mb-0"><i data-lucide="award" class="w-5 h-5 me-1 text-warning"></i> B. Capaian Kegiatan Ekstrakurikuler & Lencana Kepanduan</h5>
        </div>
        <div class="card-body p-4 pt-2">
            <?php if (empty($snapshot['extracurriculars'])): ?>
                <p class="text-muted mb-0 py-3"><em>Belum ada keikutsertaan ekstrakurikuler aktif tercatat.</em></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light text-xs text-muted text-uppercase">
                            <tr>
                                <th class="py-2.5">Kegiatan Ekstrakurikuler</th>
                                <th class="py-2.5 text-center">Kehadiran</th>
                                <th class="py-2.5 text-center">Predikat</th>
                                <th class="py-2.5">Deskripsi Capaian</th>
                                <th class="py-2.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($snapshot['extracurriculars'] as $extra): ?>
                                <tr>
                                    <td class="fw-bold text-gray-900 py-2.5">
                                        <?= esc($extra['program_title']) ?>
                                        <div class="text-xs text-muted">Pembina: <?= esc($extra['coach_name']) ?></div>
                                    </td>
                                    <td class="text-center py-2.5 fw-semibold"><?= $extra['attendance_rate'] ?>%</td>
                                    <td class="text-center py-2.5">
                                        <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1"><?= esc($extra['predicate_label']) ?> (<?= esc($extra['predicate']) ?>)</span>
                                    </td>
                                    <td class="small text-muted py-2.5" style="max-width: 400px;"><?= esc($extra['narrative']) ?></td>
                                    <td class="text-end py-2.5">
                                        <a href="<?= base_url('extracurricular/' . $extra['program_id'] . '/certificate/' . $snapshot['student_id']) ?>" target="_blank" class="btn btn-xs btn-outline-warning text-dark rounded-pill px-2.5" style="font-size: 11px;">
                                            <i data-lucide="award" class="w-3 h-3 d-inline-block"></i> Piagam
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- C. Rekapitulasi Presensi -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 p-4 pb-2">
            <h5 class="fw-bold text-gray-900 mb-0"><i data-lucide="calendar-check" class="w-5 h-5 me-1 text-info"></i> C. Rekapitulasi Kehadiran Siswa</h5>
        </div>
        <div class="card-body p-4 pt-2">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-success-subtle">
                        <i data-lucide="check-circle" class="text-success" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-success"><?= (int) ($snapshot['attendance_summary']['hadir'] ?? 0) ?></div><div class="text-muted small">Hadir</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-warning-subtle">
                        <i data-lucide="alert-circle" class="text-warning" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-warning"><?= (int) ($snapshot['attendance_summary']['sakit'] ?? 0) ?></div><div class="text-muted small">Sakit</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-info-subtle">
                        <i data-lucide="shield" class="text-info" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-info"><?= (int) ($snapshot['attendance_summary']['izin'] ?? 0) ?></div><div class="text-muted small">Izin</div></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-3 bg-danger-subtle">
                        <i data-lucide="x-circle" class="text-danger" style="width:20px;height:20px"></i>
                        <div><div class="h5 fw-bold mb-0 text-danger"><?= (int) ($snapshot['attendance_summary']['alpa'] ?? 0) ?></div><div class="text-muted small">Alpa</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- D. Portofolio Siswa -->
    <?php if (! empty($snapshot['portfolio'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-gray-900 mb-0"><i data-lucide="briefcase" class="w-5 h-5 me-1 text-primary"></i> D. Kumpulan Portofolio Karya Unggulan</h5>
                <a href="<?= base_url('reporting/portfolio/' . $snapshot['student_id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Kelola Portofolio</a>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="row g-3">
                    <?php foreach ($snapshot['portfolio'] as $item): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border <?= $item['is_highlighted'] ? 'border-warning bg-warning-subtle' : 'bg-light' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="badge bg-light text-dark border text-xs"><?= $item['category'] ?></span>
                                    <?php if ($item['is_highlighted']): ?>
                                        <i data-lucide="star" class="text-warning" style="width:16px;height:16px"></i>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold text-gray-900 mb-1 mt-1"><?= esc($item['title']) ?></h6>
                                <?php if ($item['description']): ?>
                                    <p class="text-muted small mb-0"><?= esc($item['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
