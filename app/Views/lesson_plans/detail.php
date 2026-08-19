<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$pageTitle = 'Rencana Pembelajaran';
$pageIcon = 'book-open';
$pageDescription = ($plan['subject_name'] ?? '') . ' · ' . ($plan['grade_name'] ?? '') . ' · ' . ($plan['date'] ?? '');
$tabs = [
    'overview' => ['layout-dashboard', 'Ringkasan & Validasi'],
    'design' => ['pen-tool', 'Desain Pembelajaran'],
    'stages' => ['layers-3', 'Tahapan Belajar (3D)'],
    'activities' => ['list-checks', 'Aktivitas & Sumber Daya'],
    'assessments' => ['clipboard-check', 'Asesmen & Rubrik'],
];

$st = strtoupper($plan['status'] ?? 'DRAFT');
$stBadge = match($st) {
    'READY' => 'bg-info text-white',
    'IN_PROGRESS' => 'bg-warning text-dark',
    'COMPLETED' => 'bg-success text-white',
    'REFLECTED' => 'bg-dark text-white',
    default => 'bg-secondary bg-opacity-10 text-secondary border'
};
?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<!-- Header Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="card-body p-4 text-white">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-white bg-opacity-10 text-white rounded-pill font-monospace px-3 py-1">
                        Pertemuan #<?= (int)$plan['session_number'] ?>
                    </span>
                    <span class="badge <?= $stBadge ?> rounded-pill px-3 py-1 font-monospace">
                        <?= esc($st) ?>
                    </span>
                    <span class="text-white-50 small">·</span>
                    <span class="small text-white-50">Rev <?= (int)$plan['revision_number'] ?></span>
                    <?php if ($plan['source_type'] !== 'CUSTOM'): ?>
                        <span class="badge bg-light text-dark rounded-pill px-2 py-1 small"><?= esc($plan['source_type']) ?></span>
                    <?php endif ?>
                </div>
                <h3 class="fw-bold text-white mb-1"><?= esc($plan['session_label'] ?? 'Pertemuan ' . (int)$plan['session_number']) ?></h3>
                <div class="small text-white text-opacity-75">
                    <i data-lucide="user" class="me-1" style="width: 13px; height: 13px;"></i><?= esc($plan['teacher_name'] ?? '-') ?> · 
                    <i data-lucide="calendar" class="me-1 ms-2" style="width: 13px; height: 13px;"></i><?= esc($plan['date']) ?> · 
                    <i data-lucide="book" class="me-1 ms-2" style="width: 13px; height: 13px;"></i><?= esc($plan['subject_name'] ?? '-') ?> (<?= esc($plan['grade_name'] ?? '-') ?>)
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php $next = ['DRAFT' => 'READY', 'READY' => 'IN_PROGRESS', 'IN_PROGRESS' => 'COMPLETED', 'COMPLETED' => 'REFLECTED'][$st] ?? null; ?>
                <?php if ($next && $canManage): ?>
                    <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/transition') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="target_status" value="<?= esc($next) ?>">
                        <input type="hidden" name="revision_number" value="<?= (int) $plan['revision_number'] ?>">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i data-lucide="arrow-right-circle" class="me-1" style="width: 16px; height: 16px;"></i> Lanjut ke <?= esc($next) ?>
                        </button>
                    </form>
                <?php endif ?>
                <a href="<?= base_url('lesson-plans/' . $plan['uuid'] . '/print') ?>" target="_blank" class="btn btn-outline-light rounded-pill px-3">
                    <i data-lucide="printer" class="me-1" style="width: 14px; height: 14px;"></i> Cetak RPP
                </a>
                <?php if (has_permission('lesson_plans.clone')): ?>
                    <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/clone') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
                        <button type="submit" class="btn btn-outline-light rounded-pill px-3">
                            <i data-lucide="copy" class="me-1" style="width: 14px; height: 14px;"></i> Kloning
                        </button>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Segmented Navigation Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-2 bg-light">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($tabs as $key => [$icon, $label]): ?>
            <?php $isActive = ($section === $key); ?>
            <a class="btn btn-sm <?= $isActive ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary border-0 text-dark bg-white' ?> rounded-pill px-3 py-2 text-decoration-none d-flex align-items-center gap-2 transition-all flex-grow-1 flex-md-grow-0 justify-content-center" 
               href="<?= base_url('lesson-plans/' . $plan['uuid'] . '/' . $key) ?>">
                <i data-lucide="<?= esc($icon) ?>" style="width: 16px; height: 16px;"></i>
                <span class="fw-medium small"><?= esc($label) ?></span>
            </a>
        <?php endforeach ?>
    </div>
</div>

<!-- TAB 1: OVERVIEW -->
<?php if ($section === 'overview'): ?>
    <div class="row g-3 mb-4">
        <?php 
        $metrics = [
            ['Tujuan TP', $summary['objectives_count'], 'target', 'primary'],
            ['Tahapan (3D)', $summary['stages_count'], 'layers-3', 'info'],
            ['Aktivitas Belajar', $summary['activities_count'], 'list-checks', 'warning'],
            ['Asesmen & Rubrik', $summary['assessments_count'] . ' (' . (int)($summary['rubrics_count'] ?? 0) . ' rubrik)', 'clipboard-check', 'success']
        ];
        ?>
        <?php foreach ($metrics as [$label, $val, $icon, $color]): ?>
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-3 bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                            <i data-lucide="<?= $icon ?>" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-<?= $color ?>"><?= $val ?></div>
                            <div class="small text-muted"><?= esc($label) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i data-lucide="target" class="text-primary" style="width: 20px; height: 20px;"></i>
                    Tujuan Pembelajaran yang Dialokasikan
                </h5>
                <div class="list-group list-group-flush mb-3">
                    <?php foreach ($objectives as $obj): ?>
                        <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary font-monospace rounded-pill px-2 py-1 mb-1">
                                    <?= esc($obj['code'] ?? 'TP') ?>
                                </span>
                                <div class="fw-medium text-dark small"><?= esc($obj['statement'] ?? '-') ?></div>
                            </div>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small"><?= esc($obj['role']) ?></span>
                        </div>
                    <?php endforeach ?>
                    <?php if (empty($objectives)): ?>
                        <div class="text-center text-muted py-4">Belum ada TP yang dialokasikan ke rencana ini.</div>
                    <?php endif ?>
                </div>

                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 align-self-start" data-bs-toggle="modal" data-bs-target="#addObjectiveModal">
                        <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Tambah TP Lainnya
                    </button>
                <?php endif ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i data-lucide="clock" class="text-info" style="width: 20px; height: 20px;"></i>
                    Estimasi Waktu & Validasi
                </h5>
                <div class="p-3 bg-light rounded-4 mb-3 text-center">
                    <div class="small text-muted mb-1">Total Estimasi Durasi</div>
                    <div class="fs-2 fw-bold text-primary font-monospace"><?= (int)$summary['total_estimated_minutes'] ?> <span class="fs-6 fw-normal text-muted">menit</span></div>
                </div>

                <?php if (!empty($summary['warnings'])): ?>
                    <div class="alert alert-warning border-0 rounded-4 p-3 mb-0 small">
                        <div class="fw-bold mb-1 d-flex align-items-center gap-1">
                            <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i> Catatan Kelengkapan:
                        </div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($summary['warnings'] as $w): ?>
                                <li><?= esc($w) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success border-0 rounded-4 p-3 mb-0 small text-center">
                        <i data-lucide="check-circle" class="d-block mx-auto mb-1 text-success" style="width: 24px; height: 24px;"></i>
                        <strong>Struktur Lengkap!</strong> Rencana pembelajaran telah memenuhi seluruh komponen Deep Learning.
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
    <!-- Modal Add Objective -->
    <div class="modal fade" id="addObjectiveModal" tabindex="-1" aria-labelledby="addObjectiveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold" id="addObjectiveModalLabel">Tautkan Tujuan Pembelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/objectives') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">UUID / Kode Tujuan Pembelajaran</label>
                            <input class="form-control rounded-3" name="objective_uuid" placeholder="Masukkan UUID TP..." required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Tautkan TP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>

<!-- TAB 2: DESIGN -->
<?php elseif ($section === 'design'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Desain Pembelajaran Mendalam (Deep Learning)</h5>
                    <p class="small text-muted mb-0">Identifikasi peserta didik, kemitraan belajar, lingkungan, serta integrasi teknologi digital.</p>
                </div>
            </div>

            <?php if ($canManage): ?>
            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/design') ?>" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Catatan Identifikasi Konteks</label>
                    <textarea class="form-control rounded-3" name="identification_notes" rows="3" placeholder="Konteks kelas, fase, dan integrasi kurikulum..."><?= esc($plan['identification_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kesiapan Peserta Didik (Learner Readiness)</label>
                    <textarea class="form-control rounded-3" name="learner_readiness" rows="3" placeholder="Tingkat pemahaman awal dan kesiapan belajar murid..."><?= esc($plan['learner_readiness'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Karakteristik Materi Esensial</label>
                    <textarea class="form-control rounded-3" name="material_characteristics" rows="3" placeholder="Sifat konsep, tingkat abstraksi, potensi miskonsepsi..."><?= esc($plan['material_characteristics'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Praktik Pedagogis (Model Belajar)</label>
                    <input class="form-control rounded-3 mb-2" name="pedagogical_practice" value="<?= esc($plan['pedagogical_practice'] ?? '') ?>" placeholder="Problem-based Learning, Inquiry, Project-based...">
                    <label class="form-label small fw-semibold">Dimensi Profil Lulusan</label>
                    <input class="form-control rounded-3" name="graduate_profile_dimensions" value="<?= esc($plan['graduate_profile_dimensions'] ?? '') ?>" placeholder="Nalar Kritis, Kreativitas, Gotong Royong...">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Kemitraan Pembelajaran (Learning Partnership)</label>
                    <textarea class="form-control rounded-3" name="learning_partnership" rows="2" placeholder="Kolaborasi teman sebaya, peran guru fasilitator, mitra orang tua..."><?= esc($plan['learning_partnership'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Lingkungan Pembelajaran (Learning Environment)</label>
                    <textarea class="form-control rounded-3" name="learning_environment" rows="2" placeholder="Ruang kelas kolaboratif, laboratorium komputer, luar kelas..."><?= esc($plan['learning_environment'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Pemanfaatan Digital (Digital Utilization)</label>
                    <textarea class="form-control rounded-3" name="digital_utilization" rows="2" placeholder="Aplikasi, simulasi interaktif, perangkat, IDE koding..."><?= esc($plan['digital_utilization'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Koneksi Antarmapel (Interdisciplinary Notes)</label>
                    <textarea class="form-control rounded-3" name="interdisciplinary_notes" rows="2" placeholder="Keterkaitan konsep dengan mapel Matematika, Sains, dll..."><?= esc($plan['interdisciplinary_notes'] ?? '') ?></textarea>
                </div>
                <div class="col-12 text-end pt-3 border-top">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan Desain</button>
                </div>
            </form>
            <?php endif ?>
        </div>
    </div>

<!-- TAB 3: STAGES -->
<?php elseif ($section === 'stages'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Tahapan Pengalaman Belajar (3 Dimensi)</h5>
                    <p class="small text-muted mb-0">Struktur alur pembelajaran: Memahami (Apersepsi), Mengaplikasi (Eksplorasi/Praktik), dan Merefleksi.</p>
                </div>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createStageModal">
                        <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Tahapan
                    </button>
                <?php endif ?>
            </div>

            <div class="row g-3">
                <?php foreach ($stages as $s): ?>
                    <?php
                    $type = strtoupper($s['stage_type']);
                    $typeColor = match($type) {
                        'MEMAHAMI' => 'primary',
                        'MENGAPLIKASI' => 'warning',
                        'MEREFLEKSI' => 'success',
                        default => 'secondary'
                    };
                    ?>
                    <div class="col-md-4">
                        <div class="card border rounded-4 h-100 p-3 card-hover transition-all bg-white" style="border-top: 4px solid var(--bs-<?= $typeColor ?>) !important;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= $typeColor ?> bg-opacity-10 text-<?= $typeColor ?> border border-<?= $typeColor ?>-subtle rounded-pill px-3 py-1 font-monospace small">
                                    <?= esc($type) ?>
                                </span>
                                <?php if ($s['estimated_minutes']): ?>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                        <i data-lucide="clock" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= (int)$s['estimated_minutes'] ?> menit
                                    </span>
                                <?php endif ?>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?= esc($s['title'] ?? $type) ?></h5>
                            <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($s['description'] ?? 'Tahapan pelaksanaan pembelajaran.')) ?></p>
                        </div>
                    </div>
                <?php endforeach ?>
                <?php if (empty($stages)): ?>
                    <div class="col-12 text-center text-muted py-5">
                        <i data-lucide="layers" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                        Tahapan belajar belum disusun.
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
    <!-- Modal Create Stage -->
    <div class="modal fade" id="createStageModal" tabindex="-1" aria-labelledby="createStageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold" id="createStageModalLabel">Tambah Tahapan Belajar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/stages') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Dimensi Tahapan</label>
                                <select class="form-select rounded-3" name="stage_type" required>
                                    <option value="MEMAHAMI">MEMAHAMI (Apersepsi / Konsep)</option>
                                    <option value="MENGAPLIKASI" selected>MENGAPLIKASI (Praktik / Eksplorasi)</option>
                                    <option value="MEREFLEKSI">MEREFLEKSI (Refleksi / Penutup)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Judul Tahapan</label>
                                <input class="form-control rounded-3" name="title" placeholder="Contoh: Praktik Pemrograman Berpasangan" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Estimasi (Menit)</label>
                                <input type="number" class="form-control rounded-3" name="estimated_minutes" value="30" min="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Deskripsi Kegiatan pada Tahapan</label>
                                <textarea class="form-control rounded-3" name="description" rows="3" placeholder="Uraikan aktivitas pembelajaran..." required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Tahapan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>

<!-- TAB 4: ACTIVITIES -->
<?php elseif ($section === 'activities'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Aktivitas Belajar & Sumber Daya Terkait</h5>
                    <p class="small text-muted mb-0">Rincian aktivitas kelas, moda penyampaian, pengelompokan murid, dan kebutuhan sarana.</p>
                </div>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createActivityModal">
                        <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Aktivitas
                    </button>
                <?php endif ?>
            </div>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($activities as $a): ?>
                    <div class="card border rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($a['delivery_mode']) ?>
                                    </span>
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                        <i data-lucide="users" class="me-1" style="width: 12px; height: 12px;"></i><?= esc($a['grouping_mode']) ?>
                                    </span>
                                    <?php if ($a['estimated_minutes']): ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                            <i data-lucide="clock" class="me-1" style="width: 12px; height: 12px;"></i><?= (int)$a['estimated_minutes'] ?> menit
                                        </span>
                                    <?php endif ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">
                                    <?= esc($a['custom_title'] ?: ($a['learning_activity_id'] ? 'Aktivitas Paket #' . $a['learning_activity_id'] : 'Aktivitas Belajar')) ?>
                                </h5>
                                <?php if (!empty($a['custom_description'])): ?>
                                    <p class="small text-muted mb-2"><?= nl2br(esc($a['custom_description'])) ?></p>
                                <?php endif ?>
                                <?php if (!empty($a['graduate_profile_alignment'])): ?>
                                    <div class="small text-indigo mb-2" style="color: #6366f1;">
                                        <i data-lucide="badge-check" class="me-1" style="width: 14px; height: 14px;"></i><strong>Profil Lulusan:</strong> <?= esc($a['graduate_profile_alignment']) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small font-monospace"><?= esc($a['status']) ?></span>
                        </div>

                        <!-- Resources Linked -->
                        <?php $aResources = $resourcesByActivity[$a['uuid']] ?? [] ?>
                        <?php if (!empty($aResources)): ?>
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    <i data-lucide="paperclip" class="me-1 text-primary" style="width: 13px; height: 13px;"></i>Sumber Daya / Sarana Dibutuhkan (<?= count($aResources) ?>)
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless align-middle mb-0 bg-white rounded-2">
                                        <thead>
                                            <tr class="text-muted small border-bottom">
                                                <th class="ps-3">Deskripsi Sumber Daya</th>
                                                <th class="text-center" style="width:80px">Jumlah</th>
                                                <th class="text-center" style="width:80px">Wajib</th>
                                                <th class="text-end pe-3" style="width:80px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aResources as $res): ?>
                                                <tr class="border-bottom">
                                                    <td class="ps-3 small fw-semibold text-dark"><?= esc($res['resource_title'] ?? $res['custom_description'] ?? '-') ?></td>
                                                    <td class="text-center small font-monospace"><?= (int)$res['quantity'] ?></td>
                                                    <td class="text-center small"><?= $res['is_required'] ? '<span class="badge bg-success bg-opacity-10 text-success rounded-pill">Ya</span>' : '<span class="badge bg-light text-muted rounded-pill">Opsional</span>' ?></td>
                                                    <td class="text-end pe-3">
                                                        <?php if ($canManage): ?>
                                                            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/resources/' . $res['uuid'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Lepas sumber daya ini?')">
                                                                <?= csrf_field() ?>
                                                                <button class="btn btn-link text-danger p-0 small" title="Lepas"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                                                            </form>
                                                        <?php endif ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif ?>

                        <?php if ($canManage): ?>
                            <div class="pt-2 border-top d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-link-resource"
                                        data-bs-toggle="modal" data-bs-target="#linkResourceModal"
                                        data-activity-uuid="<?= esc($a['uuid']) ?>"
                                        data-activity-title="<?= esc($a['custom_title'] ?: 'Aktivitas Belajar') ?>">
                                    <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Tautkan Sumber Daya
                                </button>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
                <?php if (empty($activities)): ?>
                    <div class="text-center text-muted py-5">Belum ada aktivitas pembelajaran.</div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
    <!-- Modal Create Activity -->
    <div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold" id="createActivityModalLabel">Tambah Aktivitas Belajar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/activities') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Tahapan Rujukan</label>
                                <select class="form-select rounded-3" name="lesson_plan_stage_uuid">
                                    <option value="">Tanpa tahapan khusus</option>
                                    <?php foreach ($stages_for_select as $s): ?>
                                        <option value="<?= esc($s['uuid']) ?>"><?= esc($s['stage_type'] . ' — ' . ($s['title'] ?? '')) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Rujukan Aktivitas Paket (Opsional)</label>
                                <select class="form-select rounded-3" name="learning_activity_id">
                                    <option value="">Aktivitas rancangan mandiri (Custom)</option>
                                    <?php foreach ($packActivities as $a): ?>
                                        <option value="<?= (int) $a['id'] ?>"><?= esc($a['code'] . ' — ' . $a['title']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Judul Aktivitas</label>
                                <input class="form-control rounded-3" name="custom_title" placeholder="Contoh: Eksplorasi Algoritma Bubble Sort" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Estimasi (Menit)</label>
                                <input type="number" class="form-control rounded-3" name="estimated_minutes" value="20" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Moda Penyampaian</label>
                                <select class="form-select rounded-3" name="delivery_mode">
                                    <option value="PLUGGED">PLUGGED (Menggunakan Komputer/Perangkat)</option>
                                    <option value="UNPLUGGED">UNPLUGGED (Tanpa Komputer / Kartu / Fisik)</option>
                                    <option value="DISCUSSION">DISCUSSION (Diskusi / Kolaborasi)</option>
                                    <option value="HYBRID">HYBRID (Gabungan Plugged & Unplugged)</option>
                                    <option value="PRACTICE">PRACTICE (Praktikum Mandiri/Kelompok)</option>
                                    <option value="PROJECT">PROJECT (Projek Terbimbing)</option>
                                    <option value="OTHER">OTHER (Lainnya)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Moda Pengelompokan</label>
                                <select class="form-select rounded-3" name="grouping_mode">
                                    <option value="PAIR">PAIR (Berpasangan 2 Siswa)</option>
                                    <option value="SMALL_GROUP">SMALL_GROUP (Kelompok Kecil 3-4 Siswa)</option>
                                    <option value="INDIVIDUAL">INDIVIDUAL (Kerja Individu)</option>
                                    <option value="WHOLE_CLASS">WHOLE_CLASS (Seluruh Kelas)</option>
                                    <option value="FLEXIBLE">FLEXIBLE (Fleksibel)</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Penyelarasan Dimensi Profil Lulusan</label>
                                <input class="form-control rounded-3" name="graduate_profile_alignment" placeholder="Contoh: Penalaran Kritis & Kolaborasi Gotong Royong">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Catatan Guru / Petunjuk Fasilitasi</label>
                                <textarea class="form-control rounded-3" name="teacher_notes" rows="2" placeholder="Panduan bagi guru dalam membimbing aktivitas..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Aktivitas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Link Resource -->
    <div class="modal fade" id="linkResourceModal" tabindex="-1" aria-labelledby="linkResourceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <div>
                        <h5 class="modal-title fw-bold" id="linkResourceModalLabel">Tautkan Sumber Daya</h5>
                        <div class="small text-muted" id="linkResourceActTitle">Aktivitas: -</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="linkResourceForm" method="post" action="">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Deskripsi Sumber Daya / Alat</label>
                            <input class="form-control rounded-3" name="custom_description" placeholder="Contoh: Laptop / Lembar Kerja Siswa / Proyektor" required maxlength="500">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Jumlah Kebutuhan</label>
                                <input type="number" class="form-control rounded-3" name="quantity" value="1" min="1" required>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <div class="form-check pb-2">
                                    <input class="form-check-input" type="checkbox" name="is_required" value="1" id="reqCheck" checked>
                                    <label class="form-check-label small fw-semibold" for="reqCheck">Wajib Tersedia</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Tautkan Resource</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>

<!-- TAB 5: ASSESSMENTS -->
<?php elseif ($section === 'assessments'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Rencana Asesmen & Rubrik Penilaian</h5>
                    <p class="small text-muted mb-0">Asesmen diagnostik awal, formatif proses belajar, sumatif, serta kriteria rubrik penentuan ketercapaian.</p>
                </div>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                        <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Asesmen
                    </button>
                <?php endif ?>
            </div>

            <div class="d-flex flex-column gap-3">
                <?php foreach ($assessments as $asm): ?>
                    <?php
                    $purpose = strtoupper($asm['assessment_purpose']);
                    $pColor = match($purpose) {
                        'INITIAL' => 'info',
                        'FORMATIVE' => 'primary',
                        'SUMMATIVE' => 'success',
                        default => 'secondary'
                    };
                    ?>
                    <div class="card border rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-<?= $pColor ?> bg-opacity-10 text-<?= $pColor ?> border border-<?= $pColor ?>-subtle rounded-pill px-3 py-1 font-monospace small mb-1">
                                    <?= esc($purpose) ?>
                                </span>
                                <h5 class="fw-bold text-dark mb-1"><?= esc($asm['recommended_method']) ?></h5>
                                <?php if (!empty($asm['criteria_reference'])): ?>
                                    <div class="small text-muted mb-1"><strong>Kriteria:</strong> <?= esc($asm['criteria_reference']) ?></div>
                                <?php endif ?>
                                <?php if (!empty($asm['notes'])): ?>
                                    <div class="small text-muted"><em><?= esc($asm['notes']) ?></em></div>
                                <?php endif ?>
                            </div>
                        </div>

                        <!-- Rubrics List -->
                        <?php $rubrics = $rubricsByAssessment[$asm['uuid']] ?? [] ?>
                        <?php if (!empty($rubrics)): ?>
                            <div class="p-3 bg-light rounded-3 border my-2">
                                <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    <i data-lucide="table" class="me-1 text-primary" style="width: 13px; height: 13px;"></i>Kriteria Rubrik Penilaian (<?= count($rubrics) ?>)
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless align-middle mb-0 bg-white rounded-2">
                                        <thead>
                                            <tr class="text-muted small border-bottom">
                                                <th class="ps-3">Deskripsi Kriteria</th>
                                                <th>Tingkatan Rubrik</th>
                                                <th class="text-end pe-3" style="width:60px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rubrics as $rub): ?>
                                                <tr class="border-bottom">
                                                    <td class="ps-3 small fw-semibold text-dark"><?= esc($rub['criterion_description']) ?></td>
                                                    <td class="small text-muted"><?= esc($rub['rubric_levels'] ?? '-') ?></td>
                                                    <td class="text-end pe-3">
                                                        <?php if ($canManage): ?>
                                                            <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/rubrics/' . $rub['uuid'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus kriteria rubrik ini?')">
                                                                <?= csrf_field() ?>
                                                                <button class="btn btn-link text-danger p-0 small" title="Hapus"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                                                            </form>
                                                        <?php endif ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif ?>

                        <?php if ($canManage): ?>
                            <div class="pt-2 border-top d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-add-rubric"
                                        data-bs-toggle="modal" data-bs-target="#addRubricModal"
                                        data-assessment-uuid="<?= esc($asm['uuid']) ?>"
                                        data-assessment-method="<?= esc($asm['recommended_method']) ?>">
                                    <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Tambah Rubrik
                                </button>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
                <?php if (empty($assessments)): ?>
                    <div class="text-center text-muted py-5">Belum ada asesmen yang didaftarkan.</div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
    <!-- Modal Create Assessment -->
    <div class="modal fade" id="createAssessmentModal" tabindex="-1" aria-labelledby="createAssessmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold" id="createAssessmentModalLabel">Tambah Rencana Asesmen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="<?= base_url('lesson-plans/' . $plan['uuid'] . '/assessments') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tujuan Asesmen</label>
                                <select class="form-select rounded-3" name="assessment_purpose" required>
                                    <option value="INITIAL">INITIAL (Asesmen Awal / Diagnostik)</option>
                                    <option value="FORMATIVE" selected>FORMATIVE (Formatif Proses Belajar)</option>
                                    <option value="SUMMATIVE">SUMMATIVE (Sumatif Akhir)</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Metode & Bentuk Asesmen</label>
                                <input class="form-control rounded-3" name="recommended_method" placeholder="Contoh: Observasi Diskusi Kelompok & Lembar Ceklis" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Kriteria / Indikator Rujukan</label>
                                <input class="form-control rounded-3" name="criteria_reference" placeholder="Contoh: Ketepatan logika algoritma & kerjasama">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Catatan Pelaksanaan Asesmen</label>
                                <input class="form-control rounded-3" name="notes" placeholder="Contoh: Tindak lanjut remedial bagi skor < 70">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Asesmen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add Rubric -->
    <div class="modal fade" id="addRubricModal" tabindex="-1" aria-labelledby="addRubricModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                    <div>
                        <h5 class="modal-title fw-bold" id="addRubricModalLabel">Tambah Kriteria Rubrik</h5>
                        <div class="small text-muted" id="addRubricMethodLabel">Asesmen: -</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addRubricForm" method="post" action="">
                    <?= csrf_field() ?>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Deskripsi Kriteria Penilaian</label>
                            <input class="form-control rounded-3" name="criterion_description" placeholder="Contoh: Kemampuan menguraikan masalah ke sub-masalah" required maxlength="500">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tingkatan / Deskriptor Rubrik</label>
                            <textarea class="form-control rounded-3" name="rubric_levels" rows="3" placeholder="Contoh: 1=Perlu Bimbingan, 2=Cukup, 3=Baik, 4=Sangat Baik"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Rubrik</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif ?>
<?php endif ?>

<script>
document.querySelectorAll('.btn-link-resource').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.activityUuid;
        const title = this.dataset.activityTitle;
        document.getElementById('linkResourceActTitle').textContent = 'Aktivitas: ' + title;
        document.getElementById('linkResourceForm').action = '<?= base_url('lesson-plans/' . $plan['uuid'] . '/activities/') ?>/' + encodeURIComponent(uuid) + '/resources';
    });
});

document.querySelectorAll('.btn-add-rubric').forEach(btn => {
    btn.addEventListener('click', function() {
        const asmUuid = this.dataset.assessmentUuid;
        const method = this.dataset.assessmentMethod;
        document.getElementById('addRubricMethodLabel').textContent = 'Asesmen: ' + method;
        document.getElementById('addRubricForm').action = '<?= base_url('lesson-plans/' . $plan['uuid'] . '/assessments/') ?>/' + encodeURIComponent(asmUuid) + '/rubrics';
    });
});
</script>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
