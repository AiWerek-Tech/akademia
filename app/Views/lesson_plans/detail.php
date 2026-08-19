<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$pageTitle = 'Rencana Pembelajaran';
$pageIcon = 'book-open';
$pageDescription = ($plan['subject_name'] ?? '') . ' · ' . ($plan['grade_name'] ?? '') . ' · ' . ($plan['date'] ?? '');
$tabs = [
    'overview' => ['layout-dashboard', 'Ringkasan'],
    'design' => ['pen-tool', 'Desain'],
    'stages' => ['layers-3', 'Tahapan'],
    'activities' => ['list-checks', 'Aktivitas'],
    'assessments' => ['clipboard-check', 'Asesmen'],
];
?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <span class="badge text-bg-light mb-2"><?= esc($plan['uuid'] ? substr($plan['uuid'], 0, 8) : '') ?></span>
                <h4 class="fw-bold mb-1"><?= esc($plan['session_label'] ?? 'Pertemuan ' . (int) $plan['session_number']) ?></h4>
                <div class="text-muted small">
                    <?= esc($plan['teacher_name'] ?? '-') ?> · <?= esc($plan['date']) ?> · Revisi <?= (int) $plan['revision_number'] ?>
                    <?php if ($plan['source_type'] !== 'CUSTOM'): ?>
                        · Source: <?= esc($plan['source_type']) ?>
                    <?php endif ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <span class="badge text-bg-primary px-3 py-2"><?= esc($plan['status']) ?></span>
                <?php $next = ['DRAFT' => 'READY', 'READY' => 'IN_PROGRESS', 'IN_PROGRESS' => 'COMPLETED', 'COMPLETED' => 'REFLECTED'][$plan['status']] ?? null; ?>
                <?php if ($next && $canManage): ?>
                    <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/transition') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_status" value="<?= esc($next) ?>">
                        <input type="hidden" name="revision_number" value="<?= (int) $plan['revision_number'] ?>">
                        <button class="btn btn-primary btn-sm">Kirim ke <?= esc($next) ?></button>
                    </form>
                <?php endif ?>
                <?php if (has_permission('lesson_plans.clone')): ?>
                    <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/clone') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
                        <button class="btn btn-outline-secondary btn-sm">Kloning</button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<nav class="d-flex gap-2 flex-wrap mb-4" aria-label="Lesson plan sections">
    <?php foreach ($tabs as $key => [$icon, $label]): ?>
        <a class="btn <?= $section === $key ? 'btn-primary' : 'btn-light border' ?>" href="<?= base_url('lesson-plans/' . $plan['uuid'] . '/' . $key) ?>">
            <i data-lucide="<?= esc($icon) ?>" class="me-1" style="width:16px;height:16px"></i><?= esc($label) ?>
        </a>
    <?php endforeach ?>
</nav>

<?php if ($section === 'overview'): ?>
    <div class="row g-3">
        <?php foreach ([['TP', $summary['objectives_count'], 'target'], ['Tahapan', $summary['stages_count'], 'layers-3'], ['Aktivitas', $summary['activities_count'], 'list-checks'], ['Asesmen', $summary['assessments_count'], 'clipboard-check']] as [$label, $value, $icon]): ?>
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><i data-lucide="<?= $icon ?>" class="text-primary mb-3"></i><div class="text-muted small"><?= esc($label) ?></div><div class="fs-3 fw-bold"><?= (int) $value ?></div></div></div></div>
        <?php endforeach ?>
    </div>
    <?php if ($summary['total_estimated_minutes'] > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 mt-3"><div class="card-body p-4"><div class="text-muted small">Total Estimasi Waktu</div><div class="fs-4 fw-bold"><?= (int) $summary['total_estimated_minutes'] ?> menit</div></div></div>
    <?php endif ?>
    <?php if ($summary['warnings']): ?>
        <div class="alert alert-warning mt-3 mb-0"><?= esc(implode(' ', $summary['warnings'])) ?></div>
    <?php endif ?>

<?php elseif ($section === 'design'): ?>
    <?php if ($canManage): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Desain Pembelajaran</h5>
            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/design') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Catatan Identifikasi</label>
                    <textarea class="form-control" name="identification_notes" rows="3" placeholder="Kelas, fase, TP, konteks..."><?= esc($plan['identification_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kesiapan Peserta Didik</label>
                    <textarea class="form-control" name="learner_readiness" rows="3" placeholder="Kesiapan belajar siswa..."><?= esc($plan['learner_readiness'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Karakteristik Materi</label>
                    <textarea class="form-control" name="material_characteristics" rows="3"><?= esc($plan['material_characteristics'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Praktik Pedagogis</label>
                    <input class="form-control" name="pedagogical_practice" value="<?= esc($plan['pedagogical_practice'] ?? '') ?>" placeholder="Problem-based, project-based, dll.">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kemitraan Pembelajaran</label>
                    <textarea class="form-control" name="learning_partnership" rows="2"><?= esc($plan['learning_partnership'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Lingkungan Pembelajaran</label>
                    <textarea class="form-control" name="learning_environment" rows="2"><?= esc($plan['learning_environment'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Pemanfaatan Digital</label>
                    <textarea class="form-control" name="digital_utilization" rows="2"><?= esc($plan['digital_utilization'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Dimensi Profil Lulusan</label>
                    <input class="form-control" name="graduate_profile_dimensions" value="<?= esc($plan['graduate_profile_dimensions'] ?? '') ?>" placeholder="Beriman, Kreatif, dll.">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Catatan Antarmapel</label>
                    <textarea class="form-control" name="interdisciplinary_notes" rows="2"><?= esc($plan['interdisciplinary_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-12 text-end"><button class="btn btn-primary">Simpan desain</button></div>
            </form>
        </div>
    </div>
    <?php endif ?>

<?php elseif ($section === 'stages'): ?>
    <?php if ($canManage): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold">Tambah Tahapan</h5>
            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/stages') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <select class="form-select" name="stage_type" required>
                        <option value="MEMAHAMI">Memahami</option>
                        <option value="MENGAPLIKASI">Mengaplikasi</option>
                        <option value="MEREFLEKSI">Merefleksi</option>
                    </select>
                </div>
                <div class="col-md-3"><input class="form-control" name="title" placeholder="Judul tahapan"></div>
                <div class="col-md-3"><input class="form-control" type="number" name="estimated_minutes" placeholder="Menit" min="1"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Simpan</button></div>
                <div class="col-12"><textarea class="form-control" name="description" rows="2" placeholder="Deskripsi tahapan..."></textarea></div>
            </form>
        </div>
    </div>
    <?php endif ?>
    <?= view('lesson_plans/_simple_table', ['headers' => ['Tahap', 'Judul', 'Estimasi', 'Catatan'], 'rows' => array_map(fn ($s) => [esc($s['stage_type']), esc($s['title'] ?? '-'), $s['estimated_minutes'] ? (int) $s['estimated_minutes'] . ' menit' : '-', esc($s['notes'] ?? '-')], $stages)]) ?>

<?php elseif ($section === 'activities'): ?>
    <?php if ($canManage): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold">Tambah Aktivitas</h5>
            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/activities') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <select class="form-select" name="lesson_plan_stage_uuid">
                        <option value="">Tanpa tahapan</option>
                        <?php foreach ($stages_for_select as $s): ?>
                            <option value="<?= esc($s['uuid']) ?>"><?= esc($s['stage_type'] . ' — ' . ($s['title'] ?? '')) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="learning_activity_id">
                        <option value="">Aktivitas custom</option>
                        <?php foreach ($packActivities as $a): ?>
                            <option value="<?= (int) $a['id'] ?>"><?= esc($a['code'] . ' — ' . $a['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" name="custom_title" placeholder="Judul custom"></div>
                <div class="col-md-2"><input class="form-control" type="number" name="estimated_minutes" placeholder="Menit" min="1"></div>
                <div class="col-md-3">
                    <select class="form-select" name="delivery_mode">
                        <option>DISCUSSION</option><option>PLUGGED</option><option>UNPLUGGED</option>
                        <option>HYBRID</option><option>PRACTICE</option><option>PROJECT</option><option>OTHER</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="grouping_mode">
                        <option>FLEXIBLE</option><option>INDIVIDUAL</option><option>PAIR</option>
                        <option>SMALL_GROUP</option><option>LARGE_GROUP</option><option>WHOLE_CLASS</option>
                    </select>
                </div>
                <div class="col-md-3"><input class="form-control" name="teacher_notes" placeholder="Catatan guru"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100">Simpan</button></div>
            </form>
        </div>
    </div>
    <?php endif ?>
    <?= view('lesson_plans/_simple_table', ['headers' => ['Tahap', 'Aktivitas', 'Mode', 'Kelompok', 'Menit', 'Status'], 'rows' => array_map(fn ($a) => [
        esc($a['lesson_plan_stage_id'] ? ($stages[array_search($a['lesson_plan_stage_id'], array_column($stages, 'id'))]['stage_type'] ?? '-') : '-'),
        esc($a['custom_title'] ?: ($a['learning_activity_id'] ? 'Pack Activity #' . $a['learning_activity_id'] : '-')),
        esc($a['delivery_mode']),
        esc($a['grouping_mode']),
        $a['estimated_minutes'] ? (int) $a['estimated_minutes'] : '-',
        esc($a['status']),
    ], $activities)]) ?>

<?php elseif ($section === 'assessments'): ?>
    <?php if ($canManage): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold">Tambah Rencana Asesmen</h5>
            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/assessments') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <select class="form-select" name="assessment_purpose">
                        <option>INITIAL</option><option>FORMATIVE</option><option>SUMMATIVE</option>
                    </select>
                </div>
                <div class="col-md-5"><input class="form-control" name="recommended_method" placeholder="Metode asesmen" required></div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Simpan</button></div>
                <div class="col-md-6"><textarea class="form-control" name="criteria_reference" rows="2" placeholder="Kriteria penilaian..."></textarea></div>
                <div class="col-md-6"><textarea class="form-control" name="notes" rows="2" placeholder="Catatan..."></textarea></div>
            </form>
        </div>
    </div>
    <?php endif ?>
    <?= view('lesson_plans/_simple_table', ['headers' => ['Tujuan', 'Metode', 'Kriteria', 'Catatan'], 'rows' => array_map(fn ($a) => [esc($a['assessment_purpose']), esc($a['recommended_method']), esc($a['criteria_reference'] ?? '-'), esc($a['notes'] ?? '-')], $assessments)]) ?>
    <?php if (! empty($assessments) && $canManage): ?>
    <div class="card border-0 shadow-sm rounded-4 mt-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Tambah Rubrik Kriteria</h5>
            <form method="post" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <select class="form-select" name="assessment_uuid" required>
                        <option value="">Pilih asesmen</option>
                        <?php foreach ($assessments as $a): ?>
                            <option value="<?= esc($a['uuid']) ?>"><?= esc($a['assessment_purpose'] . ' — ' . $a['recommended_method']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-6"><input class="form-control" name="criterion_description" placeholder="Deskripsi kriteria penilaian" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100" formaction="<?= base_url('lesson-plans/' . $plan['uuid'] . '/assessments') ?>">Simpan</button></div>
            </form>
        </div>
    </div>
    <?php endif ?>

<?php endif ?>

<?= $this->endSection() ?>
