<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <!-- Top Action Bar / Session Header -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden border-top border-purple border-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1.5">
                        <a href="<?= base_url('teaching/today') ?>" class="btn btn-sm btn-outline-secondary rounded-pill py-0.5 px-2.5 text-xs">
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Kembali ke Timeline
                        </a>
                        <span class="badge bg-purple text-white px-2.5 py-1 rounded-pill text-xs fw-bold">
                            <?= esc($session['classroom_name'] ?? 'Kelas') ?>
                        </span>
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill text-xs fw-semibold">
                            Pertemuan #<?= esc($session['meeting_number']) ?> (<?= esc($session['jp_count']) ?> JP)
                        </span>
                        <?php if ($session['status'] === 'IN_PROGRESS'): ?>
                            <span class="badge bg-primary text-white px-3 py-1 rounded-pill text-xs fw-bold animate-pulse">
                                <i data-lucide="play" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Sedang Berlangsung
                            </span>
                        <?php elseif (in_array($session['status'], ['COMPLETED', 'REFLECTED'])): ?>
                            <span class="badge bg-success text-white px-3 py-1 rounded-pill text-xs fw-bold">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 me-1 d-inline-block"></i> <?= esc($session['status']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary text-white px-3 py-1 rounded-pill text-xs fw-bold">
                                <?= esc($session['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h2 class="h4 fw-bold text-gray-900 mb-0">
                        <?= esc($session['subject_name']) ?>: <?= esc($session['topic'] ?: 'Sesi Pembelajaran') ?>
                    </h2>
                    <p class="text-xs text-muted mb-0 mt-1">
                        Guru: <strong><?= esc($session['teacher_name']) ?></strong> | Tanggal: <?= date('d F Y', strtotime($session['session_date'])) ?> | Waktu: <?= $session['start_time'] ? substr($session['start_time'], 0, 5) : '--:--' ?> - <?= $session['end_time'] ? substr($session['end_time'], 0, 5) : '--:--' ?>
                    </p>
                </div>

                <!-- Timer & Controls -->
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <!-- Live Timer Widget -->
                    <div class="d-flex align-items-center gap-2 bg-light border px-3 py-1.5 rounded-pill shadow-xs">
                        <i data-lucide="timer" class="w-4 h-4 text-purple"></i>
                        <span id="sessionTimer" class="font-monospace fw-bold fs-5 text-gray-900">00:00:00</span>
                        <button type="button" id="btnToggleTimer" class="btn btn-xs btn-outline-secondary rounded-circle p-1" title="Pause / Resume">
                            <i data-lucide="pause" id="timerIcon" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Workflow Actions -->
                    <?php if ($session['status'] === 'PLANNED'): ?>
                        <form method="POST" action="<?= base_url('teaching/session/' . $session['uuid'] . '/start') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">
                                <i data-lucide="play" class="w-4 h-4 me-1.5 d-inline-block"></i> Mulai Kelas Sekarang
                            </button>
                        </form>
                    <?php elseif ($session['status'] === 'IN_PROGRESS'): ?>
                        <button type="button" class="btn btn-success px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#completeSessionModal">
                            <i data-lucide="check-circle" class="w-4 h-4 me-1.5 d-inline-block"></i> Selesaikan Kelas
                        </button>
                    <?php elseif ($session['status'] === 'COMPLETED'): ?>
                        <a href="<?= base_url('teaching/session/' . $session['uuid'] . '/reflect') ?>" class="btn btn-warning text-dark px-4 rounded-pill fw-bold shadow-sm">
                            <i data-lucide="pencil" class="w-4 h-4 me-1.5 d-inline-block"></i> Isi Jurnal Refleksi
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('teaching/session/' . $session['uuid'] . '/reflect') ?>" class="btn btn-outline-success px-3 rounded-pill fw-semibold">
                            <i data-lucide="file-check" class="w-4 h-4 me-1.5 d-inline-block"></i> Lihat Refleksi Selesai
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace 3-Column Layout -->
    <div class="row g-4">
        <!-- Left Column: Objectives, Pedagogical Practice, Misconceptions -->
        <div class="col-lg-3">
            <div class="d-flex flex-column gap-3">
                <!-- Objectives & Focus Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-3 px-3 pb-1 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-gray-900 mb-0 d-flex align-items-center gap-1.5">
                            <i data-lucide="target" class="w-4 h-4 text-purple"></i> Tujuan Belajar (TP)
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-xs text-gray-700 mb-2 leading-relaxed">
                            <?= nl2br(esc($session['learning_objective_summary'] ?: 'Mengembangkan pemahaman konsep dan keterampilan aplikatif sesuai Capaian Pembelajaran fase ini.')) ?>
                        </p>

                        <!-- Link to RPP -->
                        <div class="p-2.5 bg-purple-subtle text-purple rounded-3 mt-2 text-xs">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-semibold">RPP Terhubung</span>
                                <button type="button" class="btn btn-xs btn-link text-purple p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#linkPlanModal">
                                    Ganti
                                </button>
                            </div>
                            <div class="fw-bold text-gray-900 text-xs mt-1">
                                <?= esc($session['plan_session_label'] ?: ($session['lesson_plan_uuid'] ? 'RPP Sesi Terlampir' : 'RPP Generik 3D Deep Learning')) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Misconceptions Radar Alert -->
                <?php if (!empty($session['misconception_warnings'])): ?>
                    <div class="card border-0 shadow-sm rounded-4 bg-warning-subtle text-dark border-start border-warning border-4">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-xs text-uppercase tracking-wider mb-1.5 d-flex align-items-center gap-1.5 text-gray-900">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-warning"></i> Warning Miskonsepsi
                            </h6>
                            <p class="text-xs text-gray-800 mb-0 leading-relaxed">
                                <?= nl2br(esc($session['misconception_warnings'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Adaptive Recommendations -->
                <?php if (!empty($recommendations)): ?>
                    <div class="card border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-header bg-white border-0 pt-3 px-3 pb-1">
                            <h6 class="fw-bold text-gray-900 mb-0 text-xs d-flex align-items-center gap-1.5">
                                <i data-lucide="sparkles" class="w-4 h-4 text-primary"></i> Rekomendasi Adaptif
                            </h6>
                        </div>
                        <div class="card-body p-3 d-flex flex-column gap-2">
                            <?php foreach ($recommendations as $rec): ?>
                                <div class="p-2.5 bg-light rounded-3 text-xs">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="text-gray-900"><?= esc($rec['title']) ?></strong>
                                        <span class="badge bg-primary-subtle text-primary text-2xs"><?= esc($rec['badge']) ?></span>
                                    </div>
                                    <p class="text-muted mb-0 text-2xs"><?= esc($rec['description']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Center Column: 3D Deep Learning Checklist -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white border-0 pt-3 px-4 pb-2 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-gray-900 mb-0">Alur Belajar 3D Deep Learning</h5>
                        <p class="text-2xs text-muted mb-0">Checklist aktivitas langsung di dalam kelas</p>
                    </div>
                    <span class="badge bg-purple-subtle text-purple px-2.5 py-1 rounded-pill text-xs fw-semibold">
                        <?= count($activities) ?> Aktivitas
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <?php
                    $stageKeys = ['MEMAHAMI', 'MENGAPLIKASI', 'MEREFLEKSI'];
                    $stageTotals = [];
                    $stageDone = [];
                    foreach ($stageKeys as $key) {
                        $stageTotals[$key] = count($grouped_activities[$key] ?? []);
                        $stageDone[$key] = count(array_filter($grouped_activities[$key] ?? [], fn($a) => (int) $a['is_completed'] === 1));
                    }
                    $overallTotal = array_sum($stageTotals);
                    $overallDone = array_sum($stageDone);
                    ?>
                    <!-- Overall Progress -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="progress flex-grow-1" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $overallTotal > 0 ? round(($overallDone / $overallTotal) * 100) : 0 ?>%"></div>
                        </div>
                        <span class="text-2xs fw-bold text-muted"><?= $overallDone ?> / <?= $overallTotal ?> selesai</span>
                    </div>

                    <!-- 1. MEMAHAMI -->
                    <div class="stage-section mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill text-xs fw-bold">
                                1. MEMAHAMI
                            </span>
                            <span class="text-xs text-muted">Apersepsi & Eksplorasi Konsep</span>
                            <span class="badge bg-light text-muted text-2xs border ms-auto"><?= $stageDone['MEMAHAMI'] ?>/<?= $stageTotals['MEMAHAMI'] ?></span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($grouped_activities['MEMAHAMI'] as $act): ?>
                                <div class="p-3 border rounded-3 bg-white hover-shadow transition-all activity-row <?= $act['is_completed'] ? 'bg-light-subtle border-success-subtle' : '' ?>" id="act_card_<?= esc($act['uuid']) ?>">
                                    <div class="d-flex align-items-start gap-2.5">
                                        <div class="form-check pt-0.5">
                                            <input class="form-check-input act-check" type="checkbox" data-uuid="<?= esc($act['uuid']) ?>" <?= $act['is_completed'] ? 'checked' : '' ?>>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="fw-bold text-sm text-gray-900 mb-0 <?= $act['is_completed'] ? 'text-decoration-line-through text-muted' : '' ?>">
                                                    <?= esc($act['title']) ?>
                                                </h6>
                                                <span class="badge bg-light text-muted text-2xs border">
                                                    <?= esc($act['actual_minutes'] ?: 15) ?> mnt
                                                </span>
                                            </div>
                                            <?php if (!empty($act['description'])): ?>
                                                <p class="text-xs text-muted mb-0 mt-1"><?= esc($act['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 2. MENGAPLIKASI -->
                    <div class="stage-section mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="badge bg-primary text-white px-2.5 py-1 rounded-pill text-xs fw-bold">
                                2. MENGAPLIKASI
                            </span>
                            <span class="text-xs text-muted">Praktik, Kolaborasi & Asesmen Formatif</span>
                            <span class="badge bg-light text-muted text-2xs border ms-auto"><?= $stageDone['MENGAPLIKASI'] ?>/<?= $stageTotals['MENGAPLIKASI'] ?></span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($grouped_activities['MENGAPLIKASI'] as $act): ?>
                                <div class="p-3 border rounded-3 bg-white hover-shadow transition-all activity-row <?= $act['is_completed'] ? 'bg-light-subtle border-success-subtle' : '' ?>" id="act_card_<?= esc($act['uuid']) ?>">
                                    <div class="d-flex align-items-start gap-2.5">
                                        <div class="form-check pt-0.5">
                                            <input class="form-check-input act-check" type="checkbox" data-uuid="<?= esc($act['uuid']) ?>" <?= $act['is_completed'] ? 'checked' : '' ?>>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="fw-bold text-sm text-gray-900 mb-0 <?= $act['is_completed'] ? 'text-decoration-line-through text-muted' : '' ?>">
                                                    <?= esc($act['title']) ?>
                                                </h6>
                                                <span class="badge bg-light text-muted text-2xs border">
                                                    <?= esc($act['actual_minutes'] ?: 30) ?> mnt
                                                </span>
                                            </div>
                                            <?php if (!empty($act['description'])): ?>
                                                <p class="text-xs text-muted mb-0 mt-1"><?= esc($act['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. MEREFLEKSI -->
                    <div class="stage-section">
                        <div class="d-flex align-items-center gap-2 mb-2.5">
                            <span class="badge bg-success text-white px-2.5 py-1 rounded-pill text-xs fw-bold">
                                3. MEREFLEKSI
                            </span>
                            <span class="text-xs text-muted">Exit Ticket & Penarikan Kesimpulan</span>
                            <span class="badge bg-light text-muted text-2xs border ms-auto"><?= $stageDone['MEREFLEKSI'] ?>/<?= $stageTotals['MEREFLEKSI'] ?></span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($grouped_activities['MEREFLEKSI'] as $act): ?>
                                <div class="p-3 border rounded-3 bg-white hover-shadow transition-all activity-row <?= $act['is_completed'] ? 'bg-light-subtle border-success-subtle' : '' ?>" id="act_card_<?= esc($act['uuid']) ?>">
                                    <div class="d-flex align-items-start gap-2.5">
                                        <div class="form-check pt-0.5">
                                            <input class="form-check-input act-check" type="checkbox" data-uuid="<?= esc($act['uuid']) ?>" <?= $act['is_completed'] ? 'checked' : '' ?>>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="fw-bold text-sm text-gray-900 mb-0 <?= $act['is_completed'] ? 'text-decoration-line-through text-muted' : '' ?>">
                                                    <?= esc($act['title']) ?>
                                                </h6>
                                                <span class="badge bg-light text-muted text-2xs border">
                                                    <?= esc($act['actual_minutes'] ?: 10) ?> mnt
                                                </span>
                                            </div>
                                            <?php if (!empty($act['description'])): ?>
                                                <p class="text-xs text-muted mb-0 mt-1"><?= esc($act['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Quick Attendance & Formative Observation -->
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
                <!-- Quick Attendance Card -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-3 px-3 pb-1 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold text-gray-900 mb-0 d-flex align-items-center gap-1.5 text-xs">
                                <i data-lucide="users" class="w-4 h-4 text-primary"></i> Presensi Kelas Cepat
                            </h6>
                            <span class="text-2xs text-muted"><?= count($students) ?> Siswa Terdaftar</span>
                        </div>
                        <button type="button" id="btnSaveAttendance" class="btn btn-xs btn-primary rounded-pill px-2.5 shadow-xs">
                            <i data-lucide="save" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Simpan
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="roster-list d-flex flex-column gap-1.5 overflow-auto" style="max-height: 260px;">
                            <?php foreach ($students as $st): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light-subtle border border-gray-100 text-xs">
                                    <div class="text-truncate me-2">
                                        <span class="fw-semibold text-gray-900"><?= esc($st['full_name']) ?></span>
                                    </div>
                                    <div class="btn-group btn-group-xs attendance-btn-group" data-student-id="<?= $st['id'] ?>">
                                        <?php foreach (['HADIR' => 'H', 'TERLAMBAT' => 'T', 'IZIN' => 'I', 'SAKIT' => 'S', 'ALPA' => 'A'] as $code => $label): ?>
                                            <button type="button" class="btn btn-xs <?= ($st['attendance_status'] === $code) ? ($code === 'HADIR' ? 'btn-success' : 'btn-warning') : 'btn-outline-secondary' ?> att-btn" data-status="<?= $code ?>">
                                                <?= $label ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Formative Observation & Misconceptions Box -->
                <div class="card border-0 shadow-sm rounded-4 bg-white">
                    <div class="card-header bg-white border-0 pt-3 px-3 pb-1">
                        <h6 class="fw-bold text-gray-900 mb-0 d-flex align-items-center gap-1.5 text-xs">
                            <i data-lucide="clipboard-pen" class="w-4 h-4 text-warning"></i> Catat Observasi Formatif
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <form id="observationForm">
                            <div class="mb-2">
                                <label class="form-label text-2xs fw-semibold text-muted mb-1">Pilih Siswa (Opsional)</label>
                                <select id="obsStudentId" class="form-select form-select-xs text-xs">
                                    <option value="">-- Observasi Umum Kelas --</option>
                                    <?php foreach ($students as $st): ?>
                                        <option value="<?= $st['id'] ?>"><?= esc($st['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label text-2xs fw-semibold text-muted mb-1">Pemahaman</label>
                                    <select id="obsRating" class="form-select form-select-xs text-xs">
                                        <option value="EXCELLENT">Sangat Paham</option>
                                        <option value="GOOD" selected>Baik / Paham</option>
                                        <option value="UNCERTAIN">Ragu-ragu</option>
                                        <option value="NEEDS_HELP">Perlu Bantuan</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-2xs fw-semibold text-muted mb-1">Tipe</label>
                                    <select id="obsType" class="form-select form-select-xs text-xs">
                                        <option value="FORMATIVE">Formatif</option>
                                        <option value="BEHAVIOR">Sikap/Partisipasi</option>
                                        <option value="GENERAL">Umum</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <textarea id="obsNotes" class="form-control text-xs" rows="2" placeholder="Catatan cepat pengamatan siswa di kelas..."></textarea>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="obsMisconception">
                                <label class="form-check-label text-xs fw-semibold text-danger" for="obsMisconception">Tandai Miskonsepsi Terjadi</label>
                            </div>
                            <div id="misconceptionDetailBox" class="mb-2 d-none">
                                <input type="text" id="obsMisconceptionDetail" class="form-control form-control-xs text-xs border-danger" placeholder="Detail miskonsepsi yang dialami siswa...">
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="obsFollowUp">
                                <label class="form-check-label text-xs text-muted" for="obsFollowUp">Perlu Tindak Lanjut / Remedial</label>
                            </div>
                            <button type="button" id="btnAddObservation" class="btn btn-xs btn-outline-primary w-100 rounded-pill fw-semibold">
                                <i data-lucide="plus" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Tambah Catatan
                            </button>
                        </form>

                        <!-- Observations Stream -->
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-2xs fw-bold text-muted text-uppercase">Log Observasi Sesi Ini</span>
                                <span id="obsCountBadge" class="badge bg-light text-dark text-2xs"><?= count($observations) ?></span>
                            </div>
                            <div id="obsStream" class="d-flex flex-column gap-2 overflow-auto" style="max-height: 200px;">
                                <?php if (empty($observations)): ?>
                                    <div id="obsEmptyState" class="text-center py-2 text-muted text-2xs">Belum ada catatan observasi.</div>
                                <?php else: ?>
                                    <?php foreach ($observations as $o): ?>
                                        <div class="p-2 border rounded-2 bg-light text-2xs position-relative" id="obs_item_<?= esc($o['uuid']) ?>">
                                            <div class="d-flex align-items-center justify-content-between mb-0.5">
                                                <strong class="text-gray-900"><?= esc($o['student_name'] ?: 'Umum') ?></strong>
                                                <button type="button" class="btn btn-xs btn-link text-danger p-0 delete-obs-btn" data-uuid="<?= esc($o['uuid']) ?>">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                </button>
                                            </div>
                                            <p class="text-muted mb-0"><?= esc($o['notes']) ?></p>
                                            <?php if ($o['misconception_found']): ?>
                                                <span class="badge bg-danger-subtle text-danger text-2xs mt-1">Miskonsepsi</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Complete Session -->
<div class="modal fade" id="completeSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-gray-900">Selesaikan Sesi Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= base_url('teaching/session/' . $session['uuid'] . '/complete') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <p class="text-xs text-muted mb-3">
                        Selamat! Anda telah menyelesaikan kegiatan belajar di kelas. Catat deviasi jika ada, lalu lanjutkan untuk mengisi jurnal refleksi.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Catatan Deviasi dari Rencana Awal (Opsional)</label>
                        <textarea name="deviation_notes" class="form-control text-xs" rows="3" placeholder="Contoh: Diskusi kelompok membutuhkan waktu lebih lama 10 menit karena siswa sangat antusias..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i data-lucide="check" class="w-4 h-4 me-1.5 d-inline-block"></i> Selesai & Lanjut Refleksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Link Lesson Plan -->
<div class="modal fade" id="linkPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-gray-900">Hubungkan Rencana Pembelajaran (RPP)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= base_url('teaching/session/' . $session['uuid'] . '/link-plan') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <p class="text-xs text-muted mb-3">
                        Memilih RPP akan menyelaraskan checklist aktivitas, tujuan belajar, dan radar miskonsepsi ke dalam Teaching Mode.
                    </p>
                    <div class="mb-3">
                        <label class="form-label text-xs fw-semibold text-gray-900">Pilih RPP Aktif</label>
                        <select name="lesson_plan_id" class="form-select text-xs" required>
                            <option value="">-- Pilih RPP --</option>
                            <?php foreach ($availablePlans as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ((int) ($session['lesson_plan_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
                                    Pertemuan #<?= $p['session_number'] ?> - <?= esc($p['session_label'] ?: ('Pertemuan #' . $p['session_number'])) ?> (<?= $p['status'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        Hubungkan RPP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Client-side Interactive Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionUuid = '<?= esc($session['uuid']) ?>';
    const csrfToken = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    // 1. Live Session Timer (seeded from server actual_start_time)
    const actualStart = '<?= esc($session['actual_start_time'] ?? '') ?>';
    let secondsElapsed = 0;
    if (actualStart) {
        const start = new Date();
        const parts = actualStart.split(':').map(Number);
        start.setHours(parts[0] || 0, parts[1] || 0, parts[2] || 0, 0);
        secondsElapsed = Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000));
    }
    let timerRunning = true;
    const timerDisplay = document.getElementById('sessionTimer');
    const timerToggleBtn = document.getElementById('btnToggleTimer');
    const timerIcon = document.getElementById('timerIcon');

    function renderTimer() {
        const hrs = String(Math.floor(secondsElapsed / 3600)).padStart(2, '0');
        const mins = String(Math.floor((secondsElapsed % 3600) / 60)).padStart(2, '0');
        const secs = String(secondsElapsed % 60).padStart(2, '0');
        timerDisplay.textContent = `${hrs}:${mins}:${secs}`;
    }
    function updateTimer() {
        if (!timerRunning) return;
        secondsElapsed++;
        renderTimer();
    }
    renderTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    timerToggleBtn?.addEventListener('click', function() {
        timerRunning = !timerRunning;
        if (timerRunning) {
            timerIcon.setAttribute('data-lucide', 'pause');
        } else {
            timerIcon.setAttribute('data-lucide', 'play');
        }
        lucide.createIcons();
    });

    // 2. Interactive Activity Checklist Toggle (AJAX)
    document.querySelectorAll('.act-check').forEach(chk => {
        chk.addEventListener('change', function() {
            const actUuid = this.getAttribute('data-uuid');
            const isCompleted = this.checked ? 1 : 0;
            const card = document.getElementById('act_card_' + actUuid);

            fetch('<?= base_url('teaching/session/') ?>' + sessionUuid + '/activities/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfHash
                },
                body: new URLSearchParams({
                    [csrfToken]: csrfHash,
                    activity_uuid: actUuid,
                    is_completed: isCompleted
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (isCompleted) {
                        card.classList.add('bg-light-subtle', 'border-success-subtle');
                        card.querySelector('h6')?.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        card.classList.remove('bg-light-subtle', 'border-success-subtle');
                        card.querySelector('h6')?.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                }
            });
        });
    });

    // 3. Quick Attendance Interactive Buttons
    document.querySelectorAll('.attendance-btn-group').forEach(group => {
        group.querySelectorAll('.att-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                group.querySelectorAll('.att-btn').forEach(b => {
                    b.className = 'btn btn-xs btn-outline-secondary att-btn';
                });
                const status = this.getAttribute('data-status');
                if (status === 'HADIR') {
                    this.className = 'btn btn-xs btn-success att-btn';
                } else {
                    this.className = 'btn btn-xs btn-warning att-btn';
                }
            });
        });
    });

    // Save Quick Attendance via AJAX
    document.getElementById('btnSaveAttendance')?.addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        const attendanceData = [];
        document.querySelectorAll('.attendance-btn-group').forEach(group => {
            const studentId = group.getAttribute('data-student-id');
            const activeBtn = group.querySelector('.btn-success, .btn-warning');
            const status = activeBtn ? activeBtn.getAttribute('data-status') : 'HADIR';
            attendanceData.push({ student_id: parseInt(studentId), status: status });
        });

        fetch('<?= base_url('teaching/session/') ?>' + sessionUuid + '/attendance/quick', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfHash
            },
            body: JSON.stringify({
                [csrfToken]: csrfHash,
                attendance: attendanceData
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Tersimpan!';
            lucide.createIcons();
            setTimeout(() => {
                btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Simpan';
                lucide.createIcons();
            }, 2000);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="save" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Simpan';
            lucide.createIcons();
            alert('Gagal menyimpan presensi.');
        });
    });

    // 4. Misconception Checkbox Reveal
    const chkMisconception = document.getElementById('obsMisconception');
    const boxMisconception = document.getElementById('misconceptionDetailBox');
    chkMisconception?.addEventListener('change', function() {
        if (this.checked) {
            boxMisconception.classList.remove('d-none');
        } else {
            boxMisconception.classList.add('d-none');
        }
    });

    // 5. Add Formative Observation via AJAX
    document.getElementById('btnAddObservation')?.addEventListener('click', function() {
        const studentId = document.getElementById('obsStudentId').value;
        const rating = document.getElementById('obsRating').value;
        const obsType = document.getElementById('obsType').value;
        const notes = document.getElementById('obsNotes').value;
        const isMisconception = document.getElementById('obsMisconception').checked ? 1 : 0;
        const misconceptionDetail = document.getElementById('obsMisconceptionDetail').value;
        const followUp = document.getElementById('obsFollowUp').checked ? 1 : 0;

        if (!notes && !isMisconception) {
            alert('Tuliskan catatan observasi atau detail miskonsepsi.');
            return;
        }

        fetch('<?= base_url('teaching/session/') ?>' + sessionUuid + '/observations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfHash
            },
            body: new URLSearchParams({
                [csrfToken]: csrfHash,
                student_id: studentId,
                rating: rating,
                observation_type: obsType,
                notes: notes,
                misconception_found: isMisconception,
                misconception_detail: misconceptionDetail,
                follow_up_needed: followUp
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const obs = data.observation;
                const stream = document.getElementById('obsStream');
                document.getElementById('obsEmptyState')?.remove();

                const itemHtml = `
                    <div class="p-2 border rounded-2 bg-light text-2xs position-relative" id="obs_item_${obs.uuid}">
                        <div class="d-flex align-items-center justify-content-between mb-0.5">
                            <strong class="text-gray-900">${obs.student_id ? 'Siswa' : 'Umum'}</strong>
                            <button type="button" class="btn btn-xs btn-link text-danger p-0 delete-obs-btn" data-uuid="${obs.uuid}">
                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                            </button>
                        </div>
                        <p class="text-muted mb-0">${obs.notes || ''}</p>
                        ${obs.misconception_found ? '<span class="badge bg-danger-subtle text-danger text-2xs mt-1">Miskonsepsi</span>' : ''}
                    </div>
                `;
                stream.insertAdjacentHTML('afterbegin', itemHtml);
                lucide.createIcons();
                const countBadge = document.getElementById('obsCountBadge');
                if (countBadge) {
                    countBadge.textContent = parseInt(countBadge.textContent || '0', 10) + 1;
                }

                // Reset form
                document.getElementById('obsNotes').value = '';
                document.getElementById('obsMisconceptionDetail').value = '';
                document.getElementById('obsMisconception').checked = false;
                boxMisconception.classList.add('d-none');
            }
        });
    });

    // Delete Observation
    document.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.delete-obs-btn');
        if (!delBtn) return;
        const obsUuid = delBtn.getAttribute('data-uuid');

        fetch('<?= base_url('teaching/session/') ?>' + sessionUuid + '/observations/' + obsUuid + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfHash
            },
            body: new URLSearchParams({ [csrfToken]: csrfHash })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('obs_item_' + obsUuid)?.remove();
                const countBadge = document.getElementById('obsCountBadge');
                if (countBadge) {
                    countBadge.textContent = Math.max(0, parseInt(countBadge.textContent || '0', 10) - 1);
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
