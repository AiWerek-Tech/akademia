<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1100px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('reporting') ?>">Rapor</a></li><li class="breadcrumb-item active"><?= esc($snapshot['student_name'] ?? '') ?></li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1"><?= esc($snapshot['student_name'] ?? '') ?></h1>
            <p class="text-muted mb-0">
                <?= esc($snapshot['classroom_name'] ?? '') ?> · <?= $snapshot['snapshot_type'] ?> ·
                <?php $sc = match($snapshot['status']) { 'PUBLISHED' => 'success', 'LOCKED' => 'warning', default => 'secondary' }; ?>
                <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $snapshot['status'] ?></span>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (has_permission('reporting.manage')): ?>
                <?php if ($snapshot['status'] === 'DRAFT'): ?>
                    <form method="POST" action="<?= base_url('reporting/' . $snapshot['id'] . '/lock') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill"><i data-lucide="lock" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kunci</button>
                    </form>
                <?php endif; ?>
                <?php if ($snapshot['status'] !== 'PUBLISHED'): ?>
                    <form method="POST" action="<?= base_url('reporting/' . $snapshot['id'] . '/publish') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill"><i data-lucide="send" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Terbitkan</button>
                    </form>
                <?php endif; ?>
                <a href="<?= base_url('reporting/portfolio/' . $snapshot['student_id']) ?>" class="btn btn-outline-info btn-sm rounded-pill"><i data-lucide="briefcase" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Portofolio</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subject Results -->
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
                    <h5 class="fw-bold mb-0"><?= esc($subject['subject_name'] ?? '—') ?></h5>
                    <span class="text-muted small">Guru: <?= esc($subject['teacher_name'] ?? '—') ?></span>
                </div>
                <div class="card-body p-4 pt-0">
                    <!-- Scores -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-primary mb-0"><?= $subject['final_score'] ?? '—' ?></div>
                                <div class="text-muted small">Nilai Akhir</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-success mb-0"><?= $subject['final_predicate'] ?? '—' ?></div>
                                <div class="text-muted small">Predikat</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-info mb-0"><?= $subject['mastery_pct'] ?? '—' ?>%</div>
                                <div class="text-muted small">Penguasaan Kompetensi</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="h4 fw-bold text-warning mb-0"><?= $subject['tp_coverage_pct'] ?? '—' ?>%</div>
                                <div class="text-muted small">Cakupan TP</div>
                            </div>
                        </div>
                    </div>

                    <!-- Narratives -->
                    <h6 class="fw-bold mb-3">Narasi</h6>
                    <?php if (empty($subject['narratives'])): ?>
                        <p class="text-muted small mb-3"><em>Belum ada narasi.</em></p>
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

                    <!-- Add/Edit Narrative -->
                    <?php if (has_permission('reporting.manage') && ($snapshot['status'] === 'DRAFT' || $snapshot['status'] === 'LOCKED')): ?>
                        <?php
                        $draftContent = '';
                        $draftTarget = 0;
                        if (session()->getFlashdata('draft_subject_result_id') == $subject['id']) {
                            $draftContent = session()->getFlashdata('draft_narrative');
                            $draftTarget = $subject['id'];
                        }
                        ?>
                        <form method="POST" action="<?= base_url('reporting/narrative/' . $subject['id'] . '/save') ?>" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="narrative_type" value="<?= \App\Services\ReportingService::NARRATIVE_SUBJECT ?>">
                            <input type="hidden" name="source" value="<?= \App\Services\ReportingService::SOURCE_TEACHER ?>">
                            <div class="mb-2">
                                <textarea name="content" class="form-control" rows="3" placeholder="Tulis narasi penilaian..."><?= esc($draftContent ?: '') ?></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">Simpan Narasi</button>
                                <a href="<?= base_url('reporting/narrative/' . $subject['id'] . '/generate') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Generate Draft
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Portfolio Items -->
    <?php if (! empty($snapshot['portfolio'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-2"><h5 class="fw-bold mb-0">Portofolio Siswa</h5></div>
            <div class="card-body p-4 pt-0">
                <div class="row g-3">
                    <?php foreach ($snapshot['portfolio'] as $item): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border <?= $item['is_highlighted'] ? 'border-warning bg-warning-subtle' : 'bg-light' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-light text-dark border" style="font-size:.6rem"><?= $item['category'] ?></span>
                                        <h6 class="fw-bold mb-1 mt-1"><?= esc($item['title']) ?></h6>
                                        <?php if ($item['description']): ?>
                                            <p class="text-muted small mb-0"><?= esc($item['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['is_highlighted']): ?>
                                        <i data-lucide="star" class="text-warning" style="width:16px;height:16px"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
