<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$offeringStats = ['primary' => 0, 'backup' => 0, 'eligible' => 0, 'below' => 0, 'over' => 0, 'empty' => 0];
$offeringTeachers = [];
foreach ($offerings as $offeringItem) {
    $primaryInterest = (int) ($offeringItem['primary_interest'] ?? 0);
    $offeringStats['primary'] += $primaryInterest;
    $offeringStats['backup'] += (int) ($offeringItem['backup_interest'] ?? 0);
    if ($primaryInterest === 0) $offeringStats['empty']++;
    if (($offeringItem['demand_status'] ?? '') === 'LAYAK DIBUKA') $offeringStats['eligible']++;
    if (($offeringItem['demand_status'] ?? '') === 'BELUM MINIMUM') $offeringStats['below']++;
    if (($offeringItem['demand_status'] ?? '') === 'KUOTA TERLAMPAUI') $offeringStats['over']++;
    if (!empty($offeringItem['teacher_name'])) {
        $offeringTeachers[(string) $offeringItem['teacher_name']] = (string) $offeringItem['teacher_name'];
    }
}
ksort($offeringTeachers, SORT_NATURAL | SORT_FLAG_CASE);

$studentStats = ['total' => count($students), 'submitted' => 0, 'draft' => 0, 'not_started' => 0, 'review' => 0];
$submittedStatuses = ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'];
foreach ($students as $studentItem) {
    $studentStatus = (string) ($studentItem['submission_status'] ?? '');
    if (in_array($studentStatus, $submittedStatuses, true)) $studentStats['submitted']++;
    elseif ($studentStatus === 'DRAFT' || $studentStatus === 'NEEDS_REVISION') $studentStats['draft']++;
    else $studentStats['not_started']++;
    if (in_array($studentStatus, ['WAITING_CURRICULUM', 'CHANGE_REQUESTED', 'NEEDS_REVISION'], true)) $studentStats['review']++;
}
$studentCompletion = $studentStats['total'] > 0
    ? (int) round(($studentStats['submitted'] / $studentStats['total']) * 100)
    : 0;

$conflictPairs = [];
$conflictMaximum = 0;
$conflictTotal = 0;
foreach ($offerings as $leftIndex => $leftOffering) {
    foreach ($offerings as $rightIndex => $rightOffering) {
        if ($rightIndex <= $leftIndex) continue;
        $conflictValue = (int) ($conflictMatrix[(int) $leftOffering['id']][(int) $rightOffering['id']] ?? 0);
        if ($conflictValue <= 0) continue;
        $conflictMaximum = max($conflictMaximum, $conflictValue);
        $conflictTotal += $conflictValue;
        $conflictPairs[] = [
            'left_code' => $leftOffering['subject_code'],
            'left_name' => $leftOffering['subject_name'],
            'right_code' => $rightOffering['subject_code'],
            'right_name' => $rightOffering['subject_name'],
            'value' => $conflictValue,
        ];
    }
}
usort($conflictPairs, static fn (array $a, array $b): int => $b['value'] <=> $a['value']);
$highConflictCount = count(array_filter($conflictPairs, static fn (array $pair): bool => $pair['value'] >= 5));
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= base_url('electives') ?>" class="btn btn-sm btn-light border rounded-3"><i data-lucide="arrow-left" style="width:16px;height:16px;"></i></a>
            <h4 class="fw-bold mb-0"><?= esc($period['title']) ?></h4>
        </div>
        <p class="text-muted fs-7 mb-0"><?= esc($period['unit_name']) ?> · <?= esc($period['academic_year_name']) ?> · Kelas <?= esc($period['source_grade']) ?> → <?= esc($period['target_grade']) ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (has_permission('electives.view') || has_permission('class_electives.manage') || is_super_admin()): ?>
            <div class="btn-group">
                <a href="<?= base_url('electives/' . $period['id'] . '/selections/export') ?>" class="btn btn-success rounded-start-3 btn-sm px-3 d-inline-flex align-items-center gap-1 shadow-sm fw-semibold">
                    <i data-lucide="file-spreadsheet" style="width:16px;height:16px;"></i> Export Excel Rekap (.xlsx)
                </a>
                <button type="button" class="btn btn-success rounded-end-3 btn-sm dropdown-toggle dropdown-toggle-split shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2" style="min-width: 280px;">
                    <li>
                        <a class="dropdown-item rounded-2 py-2 fs-7 d-flex align-items-center gap-2" href="<?= base_url('electives/' . $period['id'] . '/selections/export') ?>">
                            <i data-lucide="file-spreadsheet" class="text-success" style="width:18px;height:18px;"></i>
                            <div>
                                <span class="fw-semibold text-slate-800 d-block">Format Excel (.xlsx)</span>
                                <span class="text-muted fs-9 d-block">Laporan eksekutif 3-tab: Ringkasan, Matriks, & Raw Data</span>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 fs-7 d-flex align-items-center gap-2" href="<?= base_url('electives/' . $period['id'] . '/selections/export?format=csv') ?>">
                            <i data-lucide="file-text" class="text-secondary" style="width:18px;height:18px;"></i>
                            <div>
                                <span class="fw-semibold text-slate-800 d-block">Format Mentah CSV (.csv)</span>
                                <span class="text-muted fs-9 d-block">File teks sederhana 1 baris per pilihan</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (has_permission('electives.manage')): ?>
            <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editPeriodModal">
                <i data-lucide="pencil" class="me-1" style="width:14px;height:14px;"></i> Edit Periode
            </button>
        <?php endif ?>
        <?php if ($period['status'] === 'DRAFT' && has_permission('electives.publish')): ?>
            <form method="post" action="<?= base_url('electives/' . $period['id'] . '/publish') ?>" onsubmit="return confirm('Publikasikan periode ini agar siswa dapat mulai mengisi pilihan?')">
                <?= csrf_field() ?>
                <button class="btn btn-success rounded-3 btn-sm px-3 shadow-sm" <?= $assessment['compliant'] ? '' : 'disabled' ?>>
                    <i data-lucide="send" class="me-1" style="width:14px;height:14px;"></i> Publikasikan
                </button>
            </form>
        <?php endif ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<!-- Metric Top Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4"><i data-lucide="book-open" style="width:24px;height:24px;"></i></div>
                <div>
                    <small class="text-muted fs-8 d-block">Mapel Aktif Penawaran</small>
                    <div class="h4 fw-bold mb-0 text-slate-800"><?= $assessment['open_offerings'] ?> <span class="fs-7 text-muted fw-normal">/ <?= $assessment['required_offerings'] ?> Wajib</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-4"><i data-lucide="sliders" style="width:24px;height:24px;"></i></div>
                <div>
                    <small class="text-muted fs-8 d-block">Aturan Mapel Utama</small>
                    <div class="h4 fw-bold mb-0 text-slate-800"><?= esc($period['min_primary_choices']) ?>–<?= esc($period['max_primary_choices']) ?> Mapel</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 <?= $assessment['compliant'] ? 'bg-success' : 'bg-warning' ?> bg-opacity-10 <?= $assessment['compliant'] ? 'text-success' : 'text-warning' ?> rounded-4"><i data-lucide="<?= $assessment['compliant'] ? 'check-circle-2' : 'alert-triangle' ?>" style="width:24px;height:24px;"></i></div>
                <div>
                    <small class="text-muted fs-8 d-block">Kesiapan Publikasi</small>
                    <div class="h5 fw-bold mb-0 <?= $assessment['compliant'] ? 'text-success' : 'text-warning' ?>"><?= $assessment['compliant'] ? 'Siap Dipublikasikan' : 'Perlu Dilengkapi' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($assessment['errors']): ?>
    <div class="alert alert-danger border-0 rounded-4 mb-4"><strong>Penghambat Publikasi:</strong><ul class="mb-0 mt-1 ps-3"><?php foreach ($assessment['errors'] as $message): ?><li><?= esc($message) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php if ($assessment['warnings']): ?>
    <div class="alert alert-warning border-0 rounded-4 mb-4"><strong>Perlu Perhatian:</strong><ul class="mb-0 mt-1 ps-3"><?php foreach ($assessment['warnings'] as $message): ?><li><?= esc($message) ?></li><?php endforeach ?></ul></div>
<?php endif ?>

<!-- TAB NAVIGATION BAR -->
<ul class="nav nav-pills nav-fill mb-4 p-1.5 bg-white border shadow-sm rounded-4 gap-2" id="electiveTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 active d-flex align-items-center justify-content-center gap-2" id="tab-offerings-btn" data-bs-toggle="pill" data-bs-target="#tab-offerings" type="button" role="tab" aria-controls="tab-offerings" aria-selected="true">
            <i data-lucide="book-open" style="width:18px;height:18px;"></i>
            <span>Penawaran Mapel</span>
            <span class="badge text-bg-primary-subtle text-primary rounded-pill ms-1 px-2 py-1"><?= count($offerings) ?> Mapel</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2" id="tab-students-btn" data-bs-toggle="pill" data-bs-target="#tab-students" type="button" role="tab" aria-controls="tab-students" aria-selected="false">
            <i data-lucide="users" style="width:18px;height:18px;"></i>
            <span>Peserta & Status Pilihan</span>
            <span class="badge text-bg-success-subtle text-success rounded-pill ms-1 px-2 py-1"><?= count($students) ?> Siswa</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2" id="tab-matrix-btn" data-bs-toggle="pill" data-bs-target="#tab-matrix" type="button" role="tab" aria-controls="tab-matrix" aria-selected="false">
            <i data-lucide="grid" style="width:18px;height:18px;"></i>
            <span>Matriks Konflik & Analisis</span>
        </button>
    </li>
</ul>

<!-- TAB CONTENT CONTAINER -->
<div class="tab-content" id="electiveTabsContent">

    <!-- ==================== TAB 1: PENAWARAN MAPEL ==================== -->
    <div class="tab-pane fade show active" id="tab-offerings" role="tabpanel" aria-labelledby="tab-offerings-btn">

        <?php if ($period['status'] === 'DRAFT' && has_permission('electives.manage')): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h5 class="mb-0 fw-bold"><i data-lucide="plus-circle" class="me-2 text-primary" style="width:18px;height:18px;"></i>Tambah Mata Pelajaran Pilihan</h5>
            </div>
            <form method="post" action="<?= base_url('electives/' . $period['id'] . '/offerings') ?>">
                <div class="card-body p-4">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold fs-8">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3 fs-8" name="subject_id" required>
                                <option value="">Pilih mapel kategori PILIHAN…</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>"><?= esc($subject['code'] . ' · ' . $subject['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold fs-8">Guru Pengampu</label>
                            <select class="form-select rounded-3 fs-8" name="teacher_id">
                                <option value="">Belum ditetapkan</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>"><?= esc($teacher['full_name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-8">Minimum Siswa <span class="text-danger">*</span></label>
                            <input class="form-control rounded-3 fs-8" type="number" min="1" name="minimum_students" value="3" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-8">Kapasitas Maksimum <span class="text-danger">*</span></label>
                            <input class="form-control rounded-3 fs-8" type="number" min="1" name="maximum_students" value="36" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-8">JP/minggu <span class="text-danger">*</span></label>
                            <input class="form-control rounded-3 fs-8" type="number" min=".5" step=".5" name="weekly_hours" value="5" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold fs-8">Deskripsi untuk Siswa</label>
                            <input class="form-control rounded-3 fs-8" name="description" maxlength="1000" placeholder="Deskripsi materi/fokus bahasan">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold fs-8">Relevansi Studi / Karier</label>
                            <input class="form-control rounded-3 fs-8" name="study_relevance" maxlength="1000" placeholder="Prospek jurusan kuliah & karier">
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary rounded-3 px-4 shadow-sm fs-8"><i data-lucide="plus" class="me-1" style="width:14px;height:14px;"></i> Tambahkan Penawaran</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php endif ?>

        <!-- Dashboard ringkas dan filter penawaran -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <button type="button" class="offering-kpi offering-kpi-primary w-100 text-start" data-offering-quick="all">
                    <span class="offering-kpi-icon"><i data-lucide="users"></i></span>
                    <span><small>Total Pilihan Utama</small><strong><?= $offeringStats['primary'] ?></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="offering-kpi offering-kpi-secondary w-100 text-start" data-offering-quick="has-backup">
                    <span class="offering-kpi-icon"><i data-lucide="bookmark"></i></span>
                    <span><small>Total Pilihan Cadangan</small><strong><?= $offeringStats['backup'] ?></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="offering-kpi offering-kpi-success w-100 text-start" data-offering-quick="LAYAK DIBUKA">
                    <span class="offering-kpi-icon"><i data-lucide="circle-check-big"></i></span>
                    <span><small>Layak Dibuka</small><strong><?= $offeringStats['eligible'] ?> <em>mapel</em></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="offering-kpi offering-kpi-warning w-100 text-start" data-offering-quick="attention">
                    <span class="offering-kpi-icon"><i data-lucide="triangle-alert"></i></span>
                    <span><small>Perlu Perhatian</small><strong><?= $offeringStats['below'] + $offeringStats['over'] ?> <em>mapel</em></strong></span>
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3 offering-filter-panel">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="offering-heading-icon"><i data-lucide="sliders-horizontal"></i></span>
                        <div>
                            <h5 class="mb-0 fw-bold">Filter Analisis Peminat</h5>
                            <small class="text-muted">Temukan pola minat dan kondisi kuota secara instan.</small>
                        </div>
                    </div>
                    <span class="badge rounded-pill text-bg-light border px-3 py-2">
                        <span id="offeringVisibleCount"><?= count($offerings) ?></span> dari <?= count($offerings) ?> mapel
                    </span>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="offeringSearch" class="form-label fs-8 fw-semibold mb-1">Cari mapel atau guru</label>
                        <div class="input-group offering-search">
                            <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:16px;height:16px;"></i></span>
                            <input type="search" class="form-control border-start-0 ps-0" id="offeringSearch" placeholder="Nama, kode mapel, atau guru…" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="offeringStatusFilter" class="form-label fs-8 fw-semibold mb-1">Status</label>
                        <select class="form-select" id="offeringStatusFilter">
                            <option value="">Semua status</option>
                            <option value="LAYAK DIBUKA">Layak dibuka</option>
                            <option value="BELUM MINIMUM">Belum minimum</option>
                            <option value="KUOTA TERLAMPAUI">Kuota terlampaui</option>
                            <option value="NO_INTEREST">Tanpa peminat</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="offeringTeacherFilter" class="form-label fs-8 fw-semibold mb-1">Guru</label>
                        <select class="form-select" id="offeringTeacherFilter">
                            <option value="">Semua guru</option>
                            <?php foreach ($offeringTeachers as $teacherName): ?>
                                <option value="<?= esc(mb_strtolower($teacherName)) ?>"><?= esc($teacherName) ?></option>
                            <?php endforeach ?>
                            <option value="__unassigned">Belum ditetapkan</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="offeringDemandFilter" class="form-label fs-8 fw-semibold mb-1">Peminat</label>
                        <select class="form-select" id="offeringDemandFilter">
                            <option value="">Semua tingkat</option>
                            <option value="has-primary">Ada pilihan utama</option>
                            <option value="has-backup">Ada pilihan cadangan</option>
                            <option value="near-capacity">≥ 75% kapasitas</option>
                            <option value="available">Masih tersedia</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="offeringSort" class="form-label fs-8 fw-semibold mb-1">Urutkan</label>
                        <select class="form-select" id="offeringSort">
                            <option value="name-asc">Nama A–Z</option>
                            <option value="primary-desc">Utama terbanyak</option>
                            <option value="backup-desc">Cadangan terbanyak</option>
                            <option value="fill-desc">Kapasitas terpadat</option>
                            <option value="remaining-asc">Sisa kuota terkecil</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <small class="text-muted fw-semibold me-1">Filter cepat:</small>
                    <button type="button" class="offering-filter-chip active" data-offering-quick="all">Semua</button>
                    <button type="button" class="offering-filter-chip" data-offering-quick="LAYAK DIBUKA">Layak dibuka</button>
                    <button type="button" class="offering-filter-chip" data-offering-quick="BELUM MINIMUM">Belum minimum</button>
                    <button type="button" class="offering-filter-chip" data-offering-quick="KUOTA TERLAMPAUI">Kuota penuh</button>
                    <button type="button" class="offering-filter-chip" data-offering-quick="NO_INTEREST">Tanpa peminat</button>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none ms-auto d-none" id="offeringResetFilter">
                        <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Penawaran -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i data-lucide="list-checks" class="me-2 text-primary" style="width:20px;height:20px;"></i>Daftar Penawaran dan Peminat</h5>
                <?php if (has_permission('electives.manage') || is_super_admin()): ?>
                    <form method="post" action="<?= base_url('electives/' . $period['id'] . '/approve-all-eligible') ?>" onsubmit="return confirm('Setujui seluruh mata pelajaran pilihan yang ditawarkan untuk dikirim ke sistem jadwal?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success rounded-3 px-3 shadow-sm fw-semibold">
                            <i data-lucide="check-check" class="me-1" style="width:15px;height:15px;"></i> Setujui Semua Mapel ke Jadwal
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                            <th class="text-center ps-3" style="width: 45px;">No.</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru Pengampu</th>
                            <th class="text-center">JP</th>
                            <th class="text-center">Min–Maks</th>
                            <th class="text-center">Utama</th>
                            <th class="text-center">Cadangan</th>
                            <th class="text-center">Status Analisis</th>
                            <th class="text-center">Verifikasi Jadwal</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="offeringTableBody">
                        <?php if ($offerings === []): ?>
                            <tr><td colspan="10" class="text-center text-muted py-5">Belum ada mata pelajaran ditawarkan pada periode ini.</td></tr>
                        <?php endif ?>
                        <?php foreach ($offerings as $idx => $offering): ?>
                            <?php
                            $primaryInterest = (int) $offering['primary_interest'];
                            $backupInterest = (int) $offering['backup_interest'];
                            $maximumStudents = max(1, (int) $offering['maximum_students']);
                            $fillPercentage = (int) round(($primaryInterest / $maximumStudents) * 100);
                            $remainingCapacity = max(0, $maximumStudents - $primaryInterest);
                            $teacherFilterValue = $offering['teacher_name'] ? mb_strtolower((string) $offering['teacher_name']) : '__unassigned';
                            ?>
                            <tr class="offering-row"
                                data-name="<?= esc(mb_strtolower((string) $offering['subject_name'])) ?>"
                                data-code="<?= esc(mb_strtolower((string) $offering['subject_code'])) ?>"
                                data-teacher="<?= esc($teacherFilterValue) ?>"
                                data-status="<?= esc($offering['demand_status']) ?>"
                                data-primary="<?= $primaryInterest ?>"
                                data-backup="<?= $backupInterest ?>"
                                data-capacity="<?= $maximumStudents ?>"
                                data-fill="<?= $fillPercentage ?>"
                                data-remaining="<?= $remainingCapacity ?>">
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3 offering-row-number"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="offering-subject-avatar"><?= esc(mb_strtoupper(mb_substr((string) $offering['subject_name'], 0, 1))) ?></span>
                                        <div>
                                            <strong class="d-block"><?= esc($offering['subject_name']) ?></strong>
                                            <span class="font-monospace fs-8 text-primary fw-bold"><?= esc($offering['subject_code']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="<?= $offering['teacher_name'] ? '' : 'text-warning' ?>">
                                        <i data-lucide="<?= $offering['teacher_name'] ? 'user-round' : 'user-round-x' ?>" class="me-1" style="width:14px;height:14px;"></i>
                                        <?= esc($offering['teacher_name'] ?: 'Belum ditetapkan') ?>
                                    </span>
                                </td>
                                <td class="text-center"><span class="badge text-bg-primary-subtle text-primary border border-primary-subtle"><?= esc(rtrim(rtrim($offering['weekly_hours'], '0'), '.')) ?> JP</span></td>
                                <td style="min-width:145px">
                                    <div class="d-flex justify-content-between fs-8 mb-1">
                                        <span><?= $primaryInterest ?>/<?= $maximumStudents ?></span>
                                        <span class="text-muted"><?= $fillPercentage ?>%</span>
                                    </div>
                                    <div class="progress offering-capacity-progress">
                                        <div class="progress-bar <?= $fillPercentage > 100 ? 'bg-danger' : ($fillPercentage >= 75 ? 'bg-warning' : 'bg-primary') ?>" style="width:<?= min(100, $fillPercentage) ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $remainingCapacity ?> kursi · min <?= (int) $offering['minimum_students'] ?></small>
                                </td>
                                <td class="fw-bold text-center text-primary fs-6">
                                    <?php if ($primaryInterest > 0): ?>
                                        <button type="button" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none p-0 fs-6"
                                                data-bs-toggle="modal"
                                                data-bs-target="#interestModal-<?= $offering['id'] ?>-PRIMARY"
                                                title="Lihat daftar <?= $primaryInterest ?> siswa peminat utama">
                                            <?= $primaryInterest ?> <i data-lucide="eye" class="ms-0.5" style="width:14px;height:14px;display:inline-block;vertical-align:-1px;"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center text-secondary fw-semibold">
                                    <?php if ($backupInterest > 0): ?>
                                        <button type="button" class="btn btn-sm btn-link text-secondary fw-bold text-decoration-none p-0 fs-6"
                                                data-bs-toggle="modal"
                                                data-bs-target="#interestModal-<?= $offering['id'] ?>-BACKUP"
                                                title="Lihat daftar <?= $backupInterest ?> siswa peminat cadangan">
                                            <?= $backupInterest ?> <i data-lucide="eye" class="ms-0.5" style="width:14px;height:14px;display:inline-block;vertical-align:-1px;"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $offering['demand_status'] === 'LAYAK DIBUKA' ? 'text-bg-success' : ($offering['demand_status'] === 'KUOTA TERLAMPAUI' ? 'text-bg-danger' : 'text-bg-warning') ?>">
                                        <?= esc($offering['demand_status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)($offering['is_approved'] ?? 1) === 1): ?>
                                        <span class="badge text-bg-success border border-success-subtle px-2 py-1 rounded-pill shadow-xs fs-9" title="Mapel disetujui & dikirim ke Jadwal">
                                            <i data-lucide="check-circle" class="me-1" style="width:12px;height:12px;display:inline-block;vertical-align:-1px;"></i> Disetujui Jadwal
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary border px-2 py-1 rounded-pill shadow-xs fs-9 text-muted" title="Mapel ditahan / belum disetujui untuk jadwal">
                                            <i data-lucide="clock" class="me-1" style="width:12px;height:12px;display:inline-block;vertical-align:-1px;"></i> Belum Disetujui
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php if (has_permission('electives.manage') || is_super_admin()): ?>
                                            <form method="post" action="<?= base_url('electives/' . $period['id'] . '/offerings/' . $offering['id'] . '/toggle-approval') ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <?php if ((int)($offering['is_approved'] ?? 1) === 1): ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning rounded-3 fs-9 px-2" title="Batalkan persetujuan kirim ke jadwal">
                                                        <i data-lucide="x-circle" style="width:13px;height:13px;"></i> Batal Setujui
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-success rounded-3 text-white fs-9 px-2" title="Setujui dan kirim mapel pilihan ini ke jadwal">
                                                        <i data-lucide="check-circle-2" style="width:13px;height:13px;"></i> Setujui Jadwal
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($period['status'] === 'DRAFT' && has_permission('electives.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" data-bs-toggle="modal" data-bs-target="#editOfferingModal<?= $offering['id'] ?>" title="Edit penawaran"><i data-lucide="pencil" style="width:14px;height:14px;"></i></button>
                                            <form method="post" action="<?= base_url('electives/' . $period['id'] . '/offerings/' . $offering['id'] . '/delete') ?>" onsubmit="return confirm('Hapus penawaran ini?')">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger rounded-3" title="Hapus"><i data-lucide="trash-2" style="width:14px;height:14px;"></i> Hapus</button>
                                            </form>
                                        <?php endif ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <tr id="offeringEmptyState" class="d-none">
                            <td colspan="9" class="text-center py-5">
                                <div class="offering-empty-state">
                                    <i data-lucide="search-x"></i>
                                    <strong>Tidak ada mapel yang sesuai</strong>
                                    <span>Coba ubah kata kunci atau reset filter.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modals Modal Daftar Siswa Peminat Per Mapel (Utama & Cadangan) -->
        <?php foreach ($offerings as $offeringItemModal): ?>
            <?php
            $modalOfferingId = (int) $offeringItemModal['id'];
            $interestTypes = [
                'PRIMARY' => ['label' => 'Pilihan Utama', 'badge' => 'text-bg-primary', 'icon' => 'check-square'],
                'BACKUP'  => ['label' => 'Pilihan Cadangan', 'badge' => 'text-bg-warning text-dark', 'icon' => 'bookmark'],
            ];
            ?>
            <?php foreach ($interestTypes as $typeKey => $typeCfg): ?>
                <?php
                $studentList = $offeringStudents[$modalOfferingId][$typeKey] ?? [];
                ?>
                <div class="modal fade" id="interestModal-<?= $modalOfferingId ?>-<?= $typeKey ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-bottom p-4 bg-light rounded-top-4">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge text-bg-primary font-monospace fs-8"><?= esc($offeringItemModal['subject_code']) ?></span>
                                        <span class="badge <?= $typeCfg['badge'] ?> rounded-pill px-3 py-1 fs-8 fw-semibold">
                                            <i data-lucide="<?= $typeCfg['icon'] ?>" style="width:13px;height:13px;" class="me-1"></i>
                                            <?= $typeCfg['label'] ?> (<?= count($studentList) ?> Siswa)
                                        </span>
                                    </div>
                                    <h5 class="modal-title fw-bold text-slate-800 mb-0"><?= esc($offeringItemModal['subject_name']) ?></h5>
                                    <small class="text-muted">Guru Pengampu: <strong><?= esc($offeringItemModal['teacher_name'] ?: 'Belum ditentukan') ?></strong> · <?= esc(rtrim(rtrim($offeringItemModal['weekly_hours'], '0'), '.')) ?> JP/minggu</small>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <?php if (empty($studentList)): ?>
                                    <div class="text-center py-4 text-muted fs-8">
                                        <i data-lucide="info" class="d-block mx-auto mb-2 opacity-50" style="width:32px;height:32px;"></i>
                                        Belum ada siswa yang memilih mata pelajaran ini sebagai <?= strtolower($typeCfg['label']) ?>.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive rounded-3 border" style="max-height: 380px;">
                                        <table class="table align-middle table-hover mb-0 fs-8">
                                            <thead class="bg-light sticky-top">
                                                <tr class="text-uppercase text-muted fw-bold">
                                                    <th class="text-center ps-3" style="width:45px;">No.</th>
                                                    <th>Nomor Induk / NIS</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Rombel / Kelas</th>
                                                    <th class="text-center">Prioritas</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($studentList as $sIdx => $sRow): ?>
                                                    <tr>
                                                        <td class="text-center fw-semibold text-muted ps-3"><?= $sIdx + 1 ?>.</td>
                                                        <td><code class="fw-bold text-dark fs-8"><?= esc($sRow['student_number']) ?></code></td>
                                                        <td class="fw-bold text-slate-800"><?= esc($sRow['full_name']) ?></td>
                                                        <td>
                                                            <span class="badge text-bg-light border text-dark font-monospace"><?= esc($sRow['classroom_code'] ?: ($sRow['classroom_name'] ?: '-')) ?></span>
                                                        </td>
                                                        <td class="text-center fw-semibold">
                                                            <?php if ($typeKey === 'PRIMARY'): ?>
                                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1">Prioritas <?= (int)$sRow['priority_order'] ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle px-2.5 py-1">Cadangan <?= (int)$sRow['priority_order'] ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2.5 py-1 fs-9"><?= esc($sRow['submission_status']) ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer border-0 p-3 pt-0">
                                <button type="button" class="btn btn-secondary rounded-3 btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <!-- ==================== TAB 2: PESERTA & STATUS PILIHAN ==================== -->
    <div class="tab-pane fade" id="tab-students" role="tabpanel" aria-labelledby="tab-students-btn">
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <button type="button" class="student-kpi student-kpi-total w-100 text-start" data-student-quick="all">
                    <span class="student-kpi-icon"><i data-lucide="users-round"></i></span>
                    <span><small>Total Peserta</small><strong><?= $studentStats['total'] ?></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="student-kpi student-kpi-complete w-100 text-start" data-student-quick="submitted">
                    <span class="student-kpi-icon"><i data-lucide="badge-check"></i></span>
                    <span><small>Sudah Mengirim</small><strong><?= $studentStats['submitted'] ?></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="student-kpi student-kpi-draft w-100 text-start" data-student-quick="draft">
                    <span class="student-kpi-icon"><i data-lucide="file-pen-line"></i></span>
                    <span><small>Masih Draf</small><strong><?= $studentStats['draft'] ?></strong></span>
                </button>
            </div>
            <div class="col-6 col-xl-3">
                <button type="button" class="student-kpi student-kpi-pending w-100 text-start" data-student-quick="not-started">
                    <span class="student-kpi-icon"><i data-lucide="user-round-clock"></i></span>
                    <span><small>Belum Memilih</small><strong><?= $studentStats['not_started'] ?></strong></span>
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-bold"><i data-lucide="users" class="me-2 text-primary" style="width:20px;height:20px;"></i>Peserta dan Status Pilihan</h5>
                    <small class="text-muted">Siswa Kelas <?= esc($period['source_grade']) ?> (Tingkat Asal) yang berhak memilih mata pelajaran pilihan Fase F.</small>
                </div>
                <?php if (has_permission('electives.participants.manage')): ?>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="<?= base_url('electives/' . $period['id'] . '/students/sync-rombel') ?>" onsubmit="return confirm('Sistem akan secara otomatis menarik/mengaitkan seluruh siswa dari Rombel Kelas <?= esc($period['source_grade']) ?> ke periode ini. Lanjutkan?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success rounded-3 shadow-sm btn-sm px-3">
                            <i data-lucide="refresh-cw" class="me-1" style="width:14px;height:14px;"></i> Tarik Siswa per Rombel
                        </button>
                    </form>
                </div>
                <?php endif ?>
            </div>

            <div class="px-4 pb-3">
                <div class="student-progress-panel">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div>
                            <strong>Progres pengiriman kelas</strong>
                            <small class="text-muted d-block"><?= $studentStats['submitted'] ?> dari <?= $studentStats['total'] ?> siswa telah mengirim pilihan.</small>
                        </div>
                        <span class="student-progress-value"><?= $studentCompletion ?>%</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Progres pengiriman pilihan" aria-valuenow="<?= $studentCompletion ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width:<?= $studentCompletion ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="px-4 py-3 student-filter-panel border-top border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="studentSearchInput" class="form-label fs-8 fw-semibold mb-1">Cari peserta</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:14px;height:14px;"></i></span>
                            <input type="text" id="studentSearchInput" class="form-control border-start-0" placeholder="Cari nama siswa atau NIS/NISN...">
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="classroomFilterSelect" class="form-label fs-8 fw-semibold mb-1">Rombel/Kelas</label>
                        <select id="classroomFilterSelect" class="form-select">
                            <option value="">Semua Rombel/Kelas (Tingkat <?= esc($period['source_grade']) ?>)</option>
                            <?php foreach ($classrooms as $cls): ?>
                                <option value="<?= esc($cls['code']) ?>"><?= esc($cls['code'] . ' - ' . $cls['name']) ?></option>
                            <?php endforeach ?>
                            <option value="BELUM_DIASSIGN">Belum Di-assign Rombel</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="studentStatusFilter" class="form-label fs-8 fw-semibold mb-1">Status pilihan</label>
                        <select id="studentStatusFilter" class="form-select">
                            <option value="">Semua status</option>
                            <option value="SUBMITTED_GROUP">Sudah dikirim</option>
                            <option value="DRAFT_GROUP">Masih draf/revisi</option>
                            <option value="NOT_STARTED">Belum memilih</option>
                            <option value="WAITING_CURRICULUM">Menunggu kurikulum</option>
                            <option value="APPROVED">Disetujui</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="studentAccountFilter" class="form-label fs-8 fw-semibold mb-1">Akun siswa</label>
                        <select id="studentAccountFilter" class="form-select">
                            <option value="">Semua akun</option>
                            <option value="linked">Sudah tertaut</option>
                            <option value="unlinked">Belum tertaut</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="studentSortSelect" class="form-label fs-8 fw-semibold mb-1">Urutkan</label>
                        <select id="studentSortSelect" class="form-select">
                            <option value="name-asc">Nama A–Z</option>
                            <option value="name-desc">Nama Z–A</option>
                            <option value="status-asc">Status prioritas</option>
                            <option value="class-asc">Rombel/Kelas</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <small class="text-muted fw-semibold me-1">Filter cepat:</small>
                    <button type="button" class="student-filter-chip active" data-student-quick="all">Semua</button>
                    <button type="button" class="student-filter-chip" data-student-quick="submitted">Sudah mengirim</button>
                    <button type="button" class="student-filter-chip" data-student-quick="draft">Masih draf</button>
                    <button type="button" class="student-filter-chip" data-student-quick="not-started">Belum memilih</button>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none ms-auto d-none" id="studentResetFilter">
                        <i data-lucide="rotate-ccw" style="width:14px;height:14px"></i> Reset
                    </button>
                    <span class="badge text-bg-white border text-dark fs-8">Ditampilkan: <strong id="studentTotalCount"><?= count($students) ?></strong> siswa</span>
                </div>
            </div>

            <!-- Roster Table -->
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover" id="studentRosterTable">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                            <th class="text-center ps-3" style="width: 55px;">No.</th>
                            <th>Nama Siswa</th>
                            <th>Nomor Induk (NIS)</th>
                            <th>Kelas / Rombel</th>
                            <th>Status Pilihan</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students === []): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i data-lucide="user-x" class="d-block mx-auto mb-2 text-secondary" style="width:32px;height:32px;"></i>
                                Belum ada peserta terdaftar.<br>
                                <small>Klik tombol <strong>"Tarik Siswa per Rombel"</strong> di atas untuk mengaitkan siswa otomatis.</small>
                            </td>
                        </tr>
                        <?php endif ?>
                        <?php foreach ($students as $idx => $student): ?>
                        <tr class="student-row"
                            data-name="<?= esc(mb_strtolower((string) $student['full_name'])) ?>"
                            data-nis="<?= esc(mb_strtolower((string) $student['student_number'])) ?>"
                            data-class="<?= esc($student['classroom_code'] ?: 'BELUM_DIASSIGN') ?>"
                            data-status="<?= esc($student['submission_status'] ?: 'NOT_STARTED') ?>"
                            data-account="<?= $student['user_id'] ? 'linked' : 'unlinked' ?>">
                            <td class="text-center fw-semibold text-secondary fs-8 ps-3 student-row-number"><?= $idx + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="student-avatar"><?= esc(mb_strtoupper(mb_substr((string) $student['full_name'], 0, 1))) ?></span>
                                    <div>
                                        <strong class="d-block"><?= esc($student['full_name']) ?></strong>
                                <?php if ($student['user_id']): ?>
                                            <small class="text-success"><i data-lucide="link" style="width:11px;height:11px"></i> Akun tertaut</small>
                                        <?php else: ?>
                                            <small class="text-muted">Belum memiliki tautan akun</small>
                                <?php endif ?>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= esc($student['student_number']) ?></code></td>
                            <td>
                                <?php if ($student['classroom_code']): ?>
                                    <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle rounded-2">
                                        <i data-lucide="door-closed" class="me-1" style="width:12px;height:12px;"></i><?= esc($student['classroom_code']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge text-bg-light text-muted border">Belum di-assign</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php
                                $st = $student['submission_status'];
                                $badgeClass = match($st) {
                                    'SUBMITTED', 'APPROVED', 'FINALIZED' => 'text-bg-success',
                                    'WAITING_CURRICULUM', 'CHANGE_REQUESTED' => 'text-bg-warning',
                                    'DRAFT' => 'text-bg-info',
                                    default => 'text-bg-light border text-muted'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= esc($st ?: 'BELUM MEMILIH') ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if (has_permission('electives.selection.manage') || has_permission('class_electives.manage')): ?>
                                        <?php if ($student['submission_id'] || in_array((string) ($period['status'] ?? ''), ['PUBLISHED', 'SELECTION_OPEN'], true)): ?>
                                            <a class="btn btn-sm btn-outline-primary rounded-3" href="<?= base_url('electives/' . $period['id'] . '/students/' . $student['id'] . '/selection') ?>">
                                                <?= $student['submission_id'] ? 'Lihat pilihan' : 'Isi pilihan' ?>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" disabled title="Admin harus mempublikasikan periode terlebih dahulu.">
                                                <i data-lucide="lock" style="width:14px;height:14px;"></i> Menunggu publikasi
                                            </button>
                                        <?php endif ?>
                                    <?php endif ?>
                                    <?php if (has_permission('electives.participants.manage') && !in_array($student['submission_status'], ['SUBMITTED', 'APPROVED', 'FINALIZED'], true)): ?>
                                        <form method="post" action="<?= base_url('electives/' . $period['id'] . '/students/' . $student['id'] . '/delete') ?>" onsubmit="return confirm('Hapus peserta ini dari periode pemilihan?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Hapus peserta"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                                        </form>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach ?>
                        <tr id="studentEmptyState" class="d-none">
                            <td colspan="6" class="text-center py-5">
                                <div class="student-empty-state">
                                    <i data-lucide="user-round-search"></i>
                                    <strong>Tidak ada peserta yang sesuai</strong>
                                    <span>Coba ubah pencarian atau reset filter.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 3: MATRIKS KONFLIK & ANALISIS ==================== -->
    <div class="tab-pane fade" id="tab-matrix" role="tabpanel" aria-labelledby="tab-matrix-btn">
        <?php if ($offerings !== []): ?>
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="matrix-kpi matrix-kpi-primary">
                    <span class="matrix-kpi-icon"><i data-lucide="git-compare-arrows"></i></span>
                    <span><small>Pasangan Berkonflik</small><strong><?= count($conflictPairs) ?></strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="matrix-kpi matrix-kpi-danger">
                    <span class="matrix-kpi-icon"><i data-lucide="flame"></i></span>
                    <span><small>Konflik Tinggi</small><strong><?= $highConflictCount ?></strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="matrix-kpi matrix-kpi-warning">
                    <span class="matrix-kpi-icon"><i data-lucide="users"></i></span>
                    <span><small>Konflik Tertinggi</small><strong><?= $conflictMaximum ?> <em>siswa</em></strong></span>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="matrix-kpi matrix-kpi-success">
                    <span class="matrix-kpi-icon"><i data-lucide="calendar-check-2"></i></span>
                    <span><small>Rekomendasi</small><strong><?= $highConflictCount > 0 ? 'Pisah Blok' : 'Relatif Aman' ?></strong></span>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold"><i data-lucide="grid-3x3" class="me-2 text-warning" style="width:20px;height:20px;"></i>Matriks Konflik Pilihan Utama</h5>
                        <small class="text-muted">Semakin tinggi angkanya, semakin kuat kebutuhan menempatkan kedua mapel pada blok jadwal berbeda.</small>
                    </div>
                    <div class="d-flex flex-wrap gap-2 matrix-controls">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:14px;height:14px"></i></span>
                            <input type="search" id="matrixSearchInput" class="form-control border-start-0" placeholder="Cari kode/nama mapel…">
                        </div>
                        <select id="matrixIntensityFilter" class="form-select form-select-sm">
                            <option value="all">Semua intensitas</option>
                            <option value="high">Fokus tinggi (≥5)</option>
                            <option value="medium">Fokus sedang+ (≥2)</option>
                            <option value="any">Hanya yang berkonflik</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="matrixResetFilter" title="Reset tampilan matriks">
                            <i data-lucide="rotate-ccw" style="width:14px;height:14px"></i>
                        </button>
                    </div>
                </div>
                <div class="matrix-legend mt-3">
                    <span><i class="matrix-dot matrix-dot-none"></i> Tidak konflik</span>
                    <span><i class="matrix-dot matrix-dot-low"></i> Rendah (1)</span>
                    <span><i class="matrix-dot matrix-dot-medium"></i> Sedang (2–4)</span>
                    <span><i class="matrix-dot matrix-dot-high"></i> Tinggi (≥5)</span>
                </div>
            </div>
            <div class="table-responsive matrix-table-wrap">
                <table class="table table-sm text-center align-middle mb-0 table-bordered matrix-table" id="conflictMatrixTable">
                    <thead>
                        <tr class="bg-light">
                            <th class="text-start ps-4">Mapel</th>
                            <?php foreach ($offerings as $column): ?>
                                <th title="<?= esc($column['subject_name']) ?>"
                                    class="font-monospace text-primary matrix-column"
                                    data-matrix-code="<?= esc(mb_strtolower((string) $column['subject_code'])) ?>"
                                    data-matrix-name="<?= esc(mb_strtolower((string) $column['subject_name'])) ?>"><?= esc($column['subject_code']) ?></th>
                            <?php endforeach ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offerings as $row): ?>
                            <tr class="matrix-row"
                                data-matrix-code="<?= esc(mb_strtolower((string) $row['subject_code'])) ?>"
                                data-matrix-name="<?= esc(mb_strtolower((string) $row['subject_name'])) ?>">
                                <th class="text-start ps-4 bg-light">
                                    <span class="font-monospace text-primary fw-bold"><?= esc($row['subject_code']) ?></span>
                                    <small class="d-block text-muted fw-normal text-truncate" style="max-width:170px"><?= esc($row['subject_name']) ?></small>
                                </th>
                                <?php foreach ($offerings as $column): $value=$conflictMatrix[(int)$row['id']][(int)$column['id']] ?? 0; ?>
                                    <td data-conflict-value="<?= (int) $value ?>"
                                        data-matrix-column="<?= esc(mb_strtolower((string) $column['subject_code'])) ?>"
                                        title="<?= esc($row['subject_code'] . ' × ' . $column['subject_code'] . ': ' . $value . ' siswa') ?>"
                                        class="matrix-cell <?= (int)$row['id'] === (int)$column['id'] ? 'matrix-diagonal' : ($value >= 5 ? 'matrix-high' : ($value >= 2 ? 'matrix-medium' : ($value === 1 ? 'matrix-low' : 'matrix-none'))) ?>">
                                        <?= (int)$row['id'] === (int)$column['id'] ? '–' : esc($value) ?>
                                    </td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4 pb-2">
                <h5 class="mb-1 fw-bold"><i data-lucide="list-ordered" class="me-2 text-danger" style="width:19px;height:19px"></i>Prioritas Pemisahan Blok Jadwal</h5>
                <small class="text-muted">Pasangan mapel diurutkan berdasarkan jumlah siswa terdampak untuk membantu pembentukan blok jadwal.</small>
            </div>
            <div class="card-body p-4 pt-3">
                <?php if ($conflictPairs === []): ?>
                    <div class="matrix-empty-state py-4">
                        <i data-lucide="badge-check"></i>
                        <strong>Belum ditemukan konflik antarmapel</strong>
                        <span>Daftar akan terbentuk otomatis setelah siswa mengirim pilihan utama.</span>
                    </div>
                <?php else: ?>
                    <div class="row g-3" id="conflictPairList">
                        <?php foreach (array_slice($conflictPairs, 0, 12) as $rank => $pair): ?>
                            <div class="col-12 col-md-6 col-xl-4 conflict-pair-item" data-pair-value="<?= (int) $pair['value'] ?>">
                                <div class="conflict-pair-card">
                                    <span class="conflict-rank">#<?= $rank + 1 ?></span>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center gap-2 fw-bold">
                                            <span class="badge text-bg-primary-subtle text-primary"><?= esc($pair['left_code']) ?></span>
                                            <i data-lucide="move-horizontal" class="text-muted" style="width:14px;height:14px"></i>
                                            <span class="badge text-bg-primary-subtle text-primary"><?= esc($pair['right_code']) ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1 text-truncate" title="<?= esc($pair['left_name'] . ' dan ' . $pair['right_name']) ?>"><?= esc($pair['left_name']) ?> · <?= esc($pair['right_name']) ?></small>
                                    </div>
                                    <span class="conflict-score <?= $pair['value'] >= 5 ? 'is-high' : ($pair['value'] >= 2 ? 'is-medium' : 'is-low') ?>">
                                        <?= (int) $pair['value'] ?><small>siswa</small>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-light border rounded-4 py-5 text-center text-muted">
                Belum ada data mapel pilihan untuk dianalisis matriksnya.
            </div>
        <?php endif ?>
    </div>

</div>

<!-- EDIT OFFERING MODALS -->
<?php foreach ($offerings as $offering): ?>
<?php if ($period['status'] === 'DRAFT' && has_permission('electives.manage')): ?>
<div class="modal fade" id="editOfferingModal<?= $offering['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('electives/' . $period['id'] . '/offerings/' . $offering['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i>Edit Penawaran: <?= esc($offering['subject_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="row text-muted fs-8">
                            <div class="col-sm-6"><strong>Mata Pelajaran:</strong> <?= esc($offering['subject_code'] . ' · ' . $offering['subject_name']) ?></div>
                            <div class="col-sm-6"><strong>Status:</strong> <span class="badge <?= $offering['is_open'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $offering['is_open'] ? 'Aktif' : 'Nonaktif' ?></span></div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Guru Pengampu</label>
                            <select class="form-select rounded-3" name="teacher_id">
                                <option value="">Belum ditetapkan</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>" <?= (int) $offering['teacher_id'] === (int) $teacher['id'] ? 'selected' : '' ?>><?= esc($teacher['full_name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">JP/Minggu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-3" name="weekly_hours" value="<?= esc(rtrim(rtrim($offering['weekly_hours'], '0'), '.')) ?>" min="0.5" step="0.5" required>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4 pt-2">
                                <input type="checkbox" class="form-check-input" name="is_open" value="1" id="isOpen<?= $offering['id'] ?>" <?= $offering['is_open'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isOpen<?= $offering['id'] ?>">Aktif / Dibuka</label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Minimum Peminat <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-3" name="minimum_students" value="<?= esc($offering['minimum_students']) ?>" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kapasitas Maksimum <span class="text-danger">*</span></label>
                            <input type="number" class="form-control rounded-3" name="maximum_students" value="<?= esc($offering['maximum_students']) ?>" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi untuk siswa</label>
                        <textarea class="form-control rounded-3" name="description" rows="2" maxlength="1000" placeholder="Deskripsi singkat mata pelajaran ini..."><?= esc($offering['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Relevansi studi/karier</label>
                            <textarea class="form-control rounded-3" name="study_relevance" rows="2" maxlength="1000" placeholder="Relevansi dengan studi lanjut atau karier..."><?= esc($offering['study_relevance'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prasyarat</label>
                            <textarea class="form-control rounded-3" name="prerequisites" rows="2" maxlength="500" placeholder="Prasyarat untuk mengambil mapel ini..."><?= esc($offering['prerequisites'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>
<?php endforeach ?>

<!-- Modal: Edit Period Modal -->
<?php if (has_permission('electives.manage')): ?>
<div class="modal fade" id="editPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('electives/' . $period['id'] . '/update') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i>Edit Periode Pemilihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Periode Pemilihan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="title" value="<?= esc($period['title']) ?>" required maxlength="255">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Mulai Pemilihan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control rounded-3" name="selection_start_at" value="<?= esc(date('Y-m-d\TH:i', strtotime($period['selection_start_at']))) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Selesai Pemilihan <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control rounded-3" name="selection_end_at" value="<?= esc(date('Y-m-d\TH:i', strtotime($period['selection_end_at']))) ?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tenggat Perubahan Pilihan</label>
                            <input type="date" class="form-control rounded-3" name="change_deadline" value="<?= esc($period['change_deadline']) ?>">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4 pt-2">
                                <input type="checkbox" class="form-check-input" name="allow_changes" value="1" id="allowChangesCheck" <?= $period['allow_changes'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="allowChangesCheck">Izinkan Siswa Mengajukan Perubahan Pilihan</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan / Petunjuk untuk Siswa</label>
                        <textarea class="form-control rounded-3" name="notes" rows="3" maxlength="1000"><?= esc($period['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif ?>

<!-- Modal: Import CSV Peserta -->
<div class="modal fade" id="importStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('electives/' . $period['id'] . '/students/import') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="upload" class="me-2" style="width:18px;height:18px;"></i>Import Peserta via CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted fs-8 mb-3">Unggah file CSV berisi data peserta didik. Format kolom: <code>nomor_induk, nama_siswa, kelas_rombel, user_id</code>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Berkas CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control rounded-3" name="student_file" accept=".csv, .txt" required>
                    </div>
                    <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong class="d-block fs-8">Template CSV Resmi</strong>
                            <small class="text-muted">Gunakan format bawaan untuk menghindari kegagalan import.</small>
                        </div>
                        <a href="<?= base_url('electives/' . $period['id'] . '/students/template') ?>" class="btn btn-sm btn-outline-secondary rounded-3">
                            <i data-lucide="download" class="me-1" style="width:14px;height:14px;"></i> Unduh Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="upload" class="me-1" style="width:16px;height:16px;"></i> Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Tambah Manual Peserta -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="<?= base_url('electives/' . $period['id'] . '/enroll') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i data-lucide="user-plus" class="me-2" style="width:18px;height:18px;"></i>Daftarkan Peserta Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor Induk (NIS / NISN) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="student_number" required maxlength="40" placeholder="Contoh: 20261001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" name="full_name" required maxlength="150" placeholder="Contoh: Ahmad Dahlan">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas / Rombel</label>
                            <select class="form-select rounded-3" name="classroom_id">
                                <option value="">Pilih Rombel...</option>
                                <?php foreach ($classrooms as $cls): ?>
                                    <option value="<?= $cls['id'] ?>"><?= esc($cls['code'] . ' - ' . $cls['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">User ID (opsional)</label>
                            <input type="number" class="form-control rounded-3" name="user_id" min="1" placeholder="ID User di sistem">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3"><i data-lucide="check" class="me-1" style="width:16px;height:16px;"></i> Daftarkan Peserta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.offering-kpi {
    --kpi-color: #4f46e5;
    position: relative;
    display: flex;
    align-items: center;
    gap: .85rem;
    min-height: 96px;
    padding: 1rem;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--kpi-color) 18%, transparent);
    border-radius: 1rem;
    background: linear-gradient(145deg, #fff, color-mix(in srgb, var(--kpi-color) 5%, #fff));
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    color: #0f172a;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.offering-kpi::after {
    content: "";
    position: absolute;
    width: 82px;
    height: 82px;
    right: -28px;
    top: -32px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--kpi-color) 9%, transparent);
}
.offering-kpi:hover,
.offering-kpi:focus-visible {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--kpi-color) 42%, transparent);
    box-shadow: 0 14px 32px rgba(15, 23, 42, .1);
}
.offering-kpi-primary { --kpi-color: #4f46e5; }
.offering-kpi-secondary { --kpi-color: #7c3aed; }
.offering-kpi-success { --kpi-color: #059669; }
.offering-kpi-warning { --kpi-color: #d97706; }
.offering-kpi-icon,
.offering-heading-icon {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    width: 44px;
    height: 44px;
    border-radius: .85rem;
    color: var(--kpi-color);
    background: color-mix(in srgb, var(--kpi-color) 12%, #fff);
}
.offering-kpi-icon svg,
.offering-heading-icon svg { width: 21px; height: 21px; }
.offering-kpi small { display: block; color: #64748b; font-weight: 650; font-size: .73rem; }
.offering-kpi strong { display: block; margin-top: .15rem; font-size: 1.45rem; line-height: 1.1; }
.offering-kpi strong em { font-size: .72rem; font-style: normal; color: #64748b; font-weight: 600; }
.offering-heading-icon { --kpi-color: #4f46e5; width: 42px; height: 42px; }
.offering-filter-panel {
    background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(248,250,252,.96));
    border: 1px solid rgba(148, 163, 184, .16) !important;
}
.offering-filter-panel .form-control,
.offering-filter-panel .form-select,
.offering-search .input-group-text {
    min-height: 42px;
    border-color: #dbe3ef;
}
.offering-filter-panel .form-control:focus,
.offering-filter-panel .form-select:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 .2rem rgba(99,102,241,.12);
}
.offering-filter-chip {
    padding: .42rem .75rem;
    border: 1px solid #dbe3ef;
    border-radius: 999px;
    background: #fff;
    color: #475569;
    font-size: .75rem;
    font-weight: 650;
    transition: all .18s ease;
}
.offering-filter-chip:hover { border-color: #818cf8; color: #4f46e5; }
.offering-filter-chip.active {
    border-color: #4f46e5;
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 5px 12px rgba(79,70,229,.2);
}
.offering-subject-avatar {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border-radius: .72rem;
    background: linear-gradient(145deg, #eef2ff, #e0e7ff);
    color: #4f46e5;
    font-weight: 800;
}
.offering-capacity-progress { height: 6px; border-radius: 999px; background: #e9eef5; }
.offering-capacity-progress .progress-bar { border-radius: inherit; }
.offering-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    white-space: nowrap;
}
.offering-table tbody tr { transition: background-color .18s ease, transform .18s ease; }
.offering-table tbody tr:hover { background: #f8faff; }
.offering-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    color: #64748b;
}
.offering-empty-state svg { width: 34px; height: 34px; color: #94a3b8; margin-bottom: .25rem; }
.offering-empty-state strong { color: #334155; }
body.dark-mode .offering-kpi,
body.dark-mode .offering-filter-panel {
    background: linear-gradient(145deg, #172033, #111827);
    color: #e5e7eb;
    border-color: rgba(148,163,184,.2) !important;
}
body.dark-mode .offering-filter-chip { background: #172033; border-color: #334155; color: #cbd5e1; }
body.dark-mode .offering-filter-chip.active { background: #6366f1; color: #fff; }
body.dark-mode .offering-filter-panel .input-group-text { background: #111827 !important; }
body.dark-mode .offering-table tbody tr:hover { background: rgba(99,102,241,.08); }
@media (max-width: 575.98px) {
    .offering-kpi { min-height: 86px; padding: .8rem; gap: .6rem; }
    .offering-kpi-icon { width: 36px; height: 36px; border-radius: .7rem; }
    .offering-kpi strong { font-size: 1.15rem; }
    .offering-kpi small { font-size: .65rem; }
    .offering-filter-chip { flex: 1 0 auto; }
}

.student-kpi,
.matrix-kpi {
    --panel-color: #4f46e5;
    display: flex;
    align-items: center;
    gap: .85rem;
    min-height: 92px;
    padding: 1rem;
    border: 1px solid color-mix(in srgb, var(--panel-color) 18%, transparent);
    border-radius: 1rem;
    background: linear-gradient(145deg, #fff, color-mix(in srgb, var(--panel-color) 5%, #fff));
    box-shadow: 0 8px 24px rgba(15,23,42,.055);
    color: #0f172a;
}
button.student-kpi { transition: transform .2s ease, box-shadow .2s ease; }
button.student-kpi:hover,
button.student-kpi:focus-visible { transform: translateY(-2px); box-shadow: 0 13px 30px rgba(15,23,42,.1); }
.student-kpi-total, .matrix-kpi-primary { --panel-color: #4f46e5; }
.student-kpi-complete, .matrix-kpi-success { --panel-color: #059669; }
.student-kpi-draft, .matrix-kpi-warning { --panel-color: #d97706; }
.student-kpi-pending, .matrix-kpi-danger { --panel-color: #dc2626; }
.student-kpi-icon,
.matrix-kpi-icon {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    width: 44px;
    height: 44px;
    border-radius: .85rem;
    color: var(--panel-color);
    background: color-mix(in srgb, var(--panel-color) 12%, #fff);
}
.student-kpi-icon svg, .matrix-kpi-icon svg { width: 21px; height: 21px; }
.student-kpi small, .matrix-kpi small { display: block; color: #64748b; font-size: .72rem; font-weight: 650; }
.student-kpi strong, .matrix-kpi strong { display: block; margin-top: .15rem; font-size: 1.4rem; line-height: 1.1; }
.matrix-kpi strong em { font-size: .7rem; color: #64748b; font-style: normal; }
.student-progress-panel {
    padding: 1rem 1.15rem;
    border: 1px solid #dcfce7;
    border-radius: .9rem;
    background: linear-gradient(135deg, #f0fdf4, #f8fafc);
}
.student-progress-panel .progress { height: 8px; border-radius: 99px; background: #dcfce7; }
.student-progress-panel .progress-bar { border-radius: inherit; }
.student-progress-value { color: #047857; font-size: 1.25rem; font-weight: 800; }
.student-filter-panel { background: linear-gradient(145deg, #f8fafc, #f3f6fb); }
.student-filter-panel .form-control,
.student-filter-panel .form-select,
.student-filter-panel .input-group-text { min-height: 42px; border-color: #dbe3ef; }
.student-filter-chip {
    padding: .42rem .75rem;
    border: 1px solid #dbe3ef;
    border-radius: 999px;
    background: #fff;
    color: #475569;
    font-size: .75rem;
    font-weight: 650;
    transition: all .18s ease;
}
.student-filter-chip:hover { border-color: #10b981; color: #047857; }
.student-filter-chip.active { border-color: #059669; background: #059669; color: #fff; box-shadow: 0 5px 12px rgba(5,150,105,.18); }
.student-avatar {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(145deg, #ecfdf5, #d1fae5);
    color: #047857;
    font-weight: 800;
}
.student-empty-state,
.matrix-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .35rem;
    color: #64748b;
}
.student-empty-state svg,
.matrix-empty-state svg { width: 34px; height: 34px; color: #94a3b8; margin-bottom: .2rem; }
.student-empty-state strong,
.matrix-empty-state strong { color: #334155; }
.matrix-controls .input-group { width: min(260px, 100%); }
.matrix-controls .form-select { width: 180px; }
.matrix-legend { display: flex; flex-wrap: wrap; gap: .65rem 1rem; font-size: .72rem; color: #64748b; }
.matrix-legend span { display: inline-flex; align-items: center; gap: .35rem; }
.matrix-dot { width: 10px; height: 10px; border-radius: 3px; }
.matrix-dot-none { background: #f1f5f9; border: 1px solid #cbd5e1; }
.matrix-dot-low { background: #dbeafe; }
.matrix-dot-medium { background: #fef3c7; }
.matrix-dot-high { background: #fee2e2; }
.matrix-table-wrap { max-height: 68vh; }
.matrix-table { min-width: 820px; }
.matrix-table thead th { position: sticky; top: 0; z-index: 4; background: #f8fafc !important; }
.matrix-table thead th:first-child { left: 0; z-index: 6; }
.matrix-table tbody th { position: sticky; left: 0; z-index: 3; min-width: 190px; }
.matrix-cell {
    --bs-table-bg-state: transparent;
    width: 58px;
    min-width: 58px;
    height: 48px;
    font-weight: 700;
    box-shadow: none !important;
    transition: opacity .18s ease, transform .18s ease, box-shadow .18s ease;
}
.matrix-cell:not(.matrix-diagonal):hover { position: relative; z-index: 2; transform: scale(1.08); box-shadow: 0 4px 14px rgba(15,23,42,.16) !important; }
.matrix-table > tbody > tr > td.matrix-none { background: #f8fafc !important; color: #94a3b8 !important; }
.matrix-table > tbody > tr > td.matrix-low { background: #dbeafe !important; color: #1d4ed8 !important; }
.matrix-table > tbody > tr > td.matrix-medium { background: #fef3c7 !important; color: #92400e !important; }
.matrix-table > tbody > tr > td.matrix-high { background: #fee2e2 !important; color: #b91c1c !important; font-size: .95rem; }
.matrix-table > tbody > tr > td.matrix-diagonal { background: #e2e8f0 !important; color: #94a3b8 !important; }
.matrix-dimmed { opacity: .18; }
.matrix-column-highlight { outline: 2px solid rgba(79,70,229,.38); outline-offset: -2px; }
.conflict-pair-card {
    display: flex;
    align-items: center;
    gap: .75rem;
    height: 100%;
    padding: .85rem;
    border: 1px solid #e2e8f0;
    border-radius: .85rem;
    background: linear-gradient(145deg, #fff, #f8fafc);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}
.conflict-pair-card:hover { transform: translateY(-2px); border-color: #c7d2fe; box-shadow: 0 9px 24px rgba(15,23,42,.08); }
.conflict-rank { display: grid; place-items: center; flex: 0 0 auto; width: 34px; height: 34px; border-radius: .65rem; background: #f1f5f9; color: #64748b; font-size: .72rem; font-weight: 800; }
.conflict-score { display: grid; place-items: center; flex: 0 0 auto; min-width: 48px; min-height: 48px; padding: .35rem; border-radius: .75rem; font-size: 1.05rem; font-weight: 800; }
.conflict-score small { display: block; font-size: .55rem; font-weight: 650; }
.conflict-score.is-high { background: #fee2e2; color: #b91c1c; }
.conflict-score.is-medium { background: #fef3c7; color: #92400e; }
.conflict-score.is-low { background: #dbeafe; color: #1d4ed8; }
body.dark-mode .student-kpi,
body.dark-mode .matrix-kpi,
body.dark-mode .conflict-pair-card { background: linear-gradient(145deg, #172033, #111827); color: #e5e7eb; border-color: rgba(148,163,184,.2); }
body.dark-mode .student-progress-panel { background: linear-gradient(135deg, rgba(5,150,105,.12), #111827); border-color: rgba(16,185,129,.22); }
body.dark-mode .student-filter-panel { background: linear-gradient(145deg, #172033, #111827); }
body.dark-mode .student-filter-chip { background: #172033; border-color: #334155; color: #cbd5e1; }
body.dark-mode .student-filter-chip.active { background: #059669; color: #fff; }
body.dark-mode .matrix-table thead th,
body.dark-mode .matrix-table tbody th { background: #172033 !important; }
body.dark-mode .matrix-table > tbody > tr > td.matrix-none { background: #111827 !important; color: #64748b !important; }
body.dark-mode .matrix-table > tbody > tr > td.matrix-low { background: #172f4d !important; color: #93c5fd !important; }
body.dark-mode .matrix-table > tbody > tr > td.matrix-medium { background: #463614 !important; color: #fde68a !important; }
body.dark-mode .matrix-table > tbody > tr > td.matrix-high { background: #4b1f27 !important; color: #fca5a5 !important; }
body.dark-mode .matrix-table > tbody > tr > td.matrix-diagonal { background: #273449 !important; color: #94a3b8 !important; }
@media (max-width: 575.98px) {
    .student-kpi, .matrix-kpi { min-height: 84px; padding: .75rem; gap: .55rem; }
    .student-kpi-icon, .matrix-kpi-icon { width: 36px; height: 36px; border-radius: .7rem; }
    .student-kpi strong, .matrix-kpi strong { font-size: 1.1rem; }
    .student-kpi small, .matrix-kpi small { font-size: .63rem; }
    .student-filter-chip { flex: 1 0 auto; }
    .matrix-controls { width: 100%; }
    .matrix-controls .input-group, .matrix-controls .form-select { width: 100%; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preserve Active Tab via Hash
    const hash = window.location.hash;
    if (hash) {
        const triggerEl = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
    const tabButtons = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', event => {
            const target = event.target.getAttribute('data-bs-target');
            if (target) {
                history.replaceState(null, null, target);
            }
        });
    });

    // Advanced offering analysis filters
    const offeringSearch = document.getElementById('offeringSearch');
    const offeringStatus = document.getElementById('offeringStatusFilter');
    const offeringTeacher = document.getElementById('offeringTeacherFilter');
    const offeringDemand = document.getElementById('offeringDemandFilter');
    const offeringSort = document.getElementById('offeringSort');
    const offeringBody = document.getElementById('offeringTableBody');
    const offeringRows = Array.from(document.querySelectorAll('.offering-row'));
    const offeringVisibleCount = document.getElementById('offeringVisibleCount');
    const offeringEmptyState = document.getElementById('offeringEmptyState');
    const offeringReset = document.getElementById('offeringResetFilter');
    const offeringQuickButtons = document.querySelectorAll('[data-offering-quick]');
    let activeOfferingQuick = 'all';

    function offeringNumber(row, key) {
        return Number(row.dataset[key] || 0);
    }

    function matchesOfferingQuick(row) {
        if (activeOfferingQuick === 'all') return true;
        if (activeOfferingQuick === 'attention') {
            return row.dataset.status === 'BELUM MINIMUM' || row.dataset.status === 'KUOTA TERLAMPAUI';
        }
        if (activeOfferingQuick === 'has-backup') return offeringNumber(row, 'backup') > 0;
        if (activeOfferingQuick === 'NO_INTEREST') return offeringNumber(row, 'primary') === 0;
        return row.dataset.status === activeOfferingQuick;
    }

    function filterAndSortOfferings() {
        if (!offeringBody) return;
        const query = (offeringSearch?.value || '').toLocaleLowerCase('id').trim();
        const status = offeringStatus?.value || '';
        const teacher = offeringTeacher?.value || '';
        const demand = offeringDemand?.value || '';
        const sort = offeringSort?.value || 'name-asc';

        const sortedRows = offeringRows.slice().sort((a, b) => {
            if (sort === 'primary-desc') return offeringNumber(b, 'primary') - offeringNumber(a, 'primary') || a.dataset.name.localeCompare(b.dataset.name, 'id');
            if (sort === 'backup-desc') return offeringNumber(b, 'backup') - offeringNumber(a, 'backup') || a.dataset.name.localeCompare(b.dataset.name, 'id');
            if (sort === 'fill-desc') return offeringNumber(b, 'fill') - offeringNumber(a, 'fill') || a.dataset.name.localeCompare(b.dataset.name, 'id');
            if (sort === 'remaining-asc') return offeringNumber(a, 'remaining') - offeringNumber(b, 'remaining') || a.dataset.name.localeCompare(b.dataset.name, 'id');
            return a.dataset.name.localeCompare(b.dataset.name, 'id');
        });

        let visible = 0;
        sortedRows.forEach(row => {
            const searchable = `${row.dataset.name} ${row.dataset.code} ${row.dataset.teacher}`;
            const matchesQuery = !query || searchable.includes(query);
            const matchesStatus = !status
                || (status === 'NO_INTEREST' ? offeringNumber(row, 'primary') === 0 : row.dataset.status === status);
            const matchesTeacher = !teacher || row.dataset.teacher === teacher;
            let matchesDemand = true;
            if (demand === 'has-primary') matchesDemand = offeringNumber(row, 'primary') > 0;
            if (demand === 'has-backup') matchesDemand = offeringNumber(row, 'backup') > 0;
            if (demand === 'near-capacity') matchesDemand = offeringNumber(row, 'fill') >= 75;
            if (demand === 'available') matchesDemand = offeringNumber(row, 'remaining') > 0;

            const show = matchesQuery && matchesStatus && matchesTeacher && matchesDemand && matchesOfferingQuick(row);
            row.classList.toggle('d-none', !show);
            if (show) {
                visible++;
                const number = row.querySelector('.offering-row-number');
                if (number) number.textContent = visible;
            }
            offeringBody.appendChild(row);
        });

        if (offeringEmptyState) {
            offeringEmptyState.classList.toggle('d-none', visible !== 0 || offeringRows.length === 0);
            offeringBody.appendChild(offeringEmptyState);
        }
        if (offeringVisibleCount) offeringVisibleCount.textContent = visible;

        const hasFilter = query || status || teacher || demand || sort !== 'name-asc' || activeOfferingQuick !== 'all';
        offeringReset?.classList.toggle('d-none', !hasFilter);
    }

    offeringQuickButtons.forEach(button => {
        button.addEventListener('click', function () {
            activeOfferingQuick = this.dataset.offeringQuick || 'all';
            document.querySelectorAll('.offering-filter-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.offeringQuick === activeOfferingQuick);
            });
            filterAndSortOfferings();
        });
    });
    [offeringSearch, offeringStatus, offeringTeacher, offeringDemand, offeringSort].forEach(control => {
        control?.addEventListener(control === offeringSearch ? 'input' : 'change', filterAndSortOfferings);
    });
    offeringReset?.addEventListener('click', function () {
        if (offeringSearch) offeringSearch.value = '';
        if (offeringStatus) offeringStatus.value = '';
        if (offeringTeacher) offeringTeacher.value = '';
        if (offeringDemand) offeringDemand.value = '';
        if (offeringSort) offeringSort.value = 'name-asc';
        activeOfferingQuick = 'all';
        document.querySelectorAll('.offering-filter-chip').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.offeringQuick === 'all');
        });
        filterAndSortOfferings();
        offeringSearch?.focus();
    });
    filterAndSortOfferings();

    // Advanced participant progress filters
    const studentSearch = document.getElementById('studentSearchInput');
    const studentClass = document.getElementById('classroomFilterSelect');
    const studentStatus = document.getElementById('studentStatusFilter');
    const studentAccount = document.getElementById('studentAccountFilter');
    const studentSort = document.getElementById('studentSortSelect');
    const studentRows = Array.from(document.querySelectorAll('.student-row'));
    const studentBody = document.querySelector('#studentRosterTable tbody');
    const studentCount = document.getElementById('studentTotalCount');
    const studentEmpty = document.getElementById('studentEmptyState');
    const studentReset = document.getElementById('studentResetFilter');
    const studentQuickButtons = document.querySelectorAll('[data-student-quick]');
    const submittedStudentStatuses = ['SUBMITTED', 'WAITING_CURRICULUM', 'APPROVED', 'FINALIZED', 'CHANGE_REQUESTED', 'CHANGED'];
    let activeStudentQuick = 'all';

    function studentStatusGroup(status) {
        if (submittedStudentStatuses.includes(status)) return 'submitted';
        if (status === 'DRAFT' || status === 'NEEDS_REVISION') return 'draft';
        return 'not-started';
    }

    function filterAndSortStudents() {
        if (!studentBody) return;
        const query = (studentSearch?.value || '').toLocaleLowerCase('id').trim();
        const selectedClass = studentClass?.value || '';
        const selectedStatus = studentStatus?.value || '';
        const selectedAccount = studentAccount?.value || '';
        const sort = studentSort?.value || 'name-asc';

        const sorted = studentRows.slice().sort((a, b) => {
            if (sort === 'name-desc') return b.dataset.name.localeCompare(a.dataset.name, 'id');
            if (sort === 'class-asc') return a.dataset.class.localeCompare(b.dataset.class, 'id') || a.dataset.name.localeCompare(b.dataset.name, 'id');
            if (sort === 'status-asc') {
                const priority = {'not-started': 0, 'draft': 1, 'submitted': 2};
                return priority[studentStatusGroup(a.dataset.status)] - priority[studentStatusGroup(b.dataset.status)]
                    || a.dataset.name.localeCompare(b.dataset.name, 'id');
            }
            return a.dataset.name.localeCompare(b.dataset.name, 'id');
        });

        let visible = 0;
        sorted.forEach(row => {
            const group = studentStatusGroup(row.dataset.status);
            const matchesQuery = !query || `${row.dataset.name} ${row.dataset.nis}`.includes(query);
            const matchesClass = !selectedClass || row.dataset.class === selectedClass;
            const matchesAccount = !selectedAccount || row.dataset.account === selectedAccount;
            let matchesStatus = !selectedStatus;
            if (selectedStatus === 'SUBMITTED_GROUP') matchesStatus = group === 'submitted';
            else if (selectedStatus === 'DRAFT_GROUP') matchesStatus = group === 'draft';
            else if (selectedStatus === 'NOT_STARTED') matchesStatus = group === 'not-started';
            else if (selectedStatus) matchesStatus = row.dataset.status === selectedStatus;
            const matchesQuick = activeStudentQuick === 'all' || group === activeStudentQuick;
            const show = matchesQuery && matchesClass && matchesAccount && matchesStatus && matchesQuick;

            row.classList.toggle('d-none', !show);
            if (show) {
                visible++;
                const number = row.querySelector('.student-row-number');
                if (number) number.textContent = visible;
            }
            studentBody.appendChild(row);
        });

        if (studentEmpty) {
            studentEmpty.classList.toggle('d-none', visible !== 0 || studentRows.length === 0);
            studentBody.appendChild(studentEmpty);
        }
        if (studentCount) studentCount.textContent = visible;
        const hasFilter = query || selectedClass || selectedStatus || selectedAccount || sort !== 'name-asc' || activeStudentQuick !== 'all';
        studentReset?.classList.toggle('d-none', !hasFilter);
    }

    studentQuickButtons.forEach(button => {
        button.addEventListener('click', function () {
            activeStudentQuick = this.dataset.studentQuick || 'all';
            document.querySelectorAll('.student-filter-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.studentQuick === activeStudentQuick);
            });
            filterAndSortStudents();
        });
    });
    [studentSearch, studentClass, studentStatus, studentAccount, studentSort].forEach(control => {
        control?.addEventListener(control === studentSearch ? 'input' : 'change', filterAndSortStudents);
    });
    studentReset?.addEventListener('click', function () {
        if (studentSearch) studentSearch.value = '';
        if (studentClass) studentClass.value = '';
        if (studentStatus) studentStatus.value = '';
        if (studentAccount) studentAccount.value = '';
        if (studentSort) studentSort.value = 'name-asc';
        activeStudentQuick = 'all';
        document.querySelectorAll('.student-filter-chip').forEach(chip => {
            chip.classList.toggle('active', chip.dataset.studentQuick === 'all');
        });
        filterAndSortStudents();
        studentSearch?.focus();
    });
    filterAndSortStudents();

    // Matrix focus controls for schedule-block analysis
    const matrixSearch = document.getElementById('matrixSearchInput');
    const matrixIntensity = document.getElementById('matrixIntensityFilter');
    const matrixReset = document.getElementById('matrixResetFilter');
    const matrixRows = Array.from(document.querySelectorAll('.matrix-row'));
    const matrixColumns = Array.from(document.querySelectorAll('.matrix-column'));

    function filterConflictMatrix() {
        const query = (matrixSearch?.value || '').toLocaleLowerCase('id').trim();
        const intensity = matrixIntensity?.value || 'all';
        const threshold = intensity === 'high' ? 5 : (intensity === 'medium' ? 2 : (intensity === 'any' ? 1 : 0));

        matrixRows.forEach(row => {
            const matches = !query || `${row.dataset.matrixCode} ${row.dataset.matrixName}`.includes(query);
            row.classList.toggle('d-none', !matches);
            row.querySelectorAll('.matrix-cell').forEach(cell => {
                const value = Number(cell.dataset.conflictValue || 0);
                cell.classList.toggle('matrix-dimmed', threshold > 0 && value < threshold);
            });
        });
        matrixColumns.forEach(column => {
            const matches = !query || `${column.dataset.matrixCode} ${column.dataset.matrixName}`.includes(query);
            column.classList.remove('d-none');
            column.classList.toggle('matrix-column-highlight', Boolean(query) && matches);
            document.querySelectorAll(`[data-matrix-column="${column.dataset.matrixCode}"]`).forEach(cell => {
                cell.classList.remove('d-none');
                cell.classList.toggle('matrix-column-highlight', Boolean(query) && matches);
            });
        });
        document.querySelectorAll('.conflict-pair-item').forEach(item => {
            item.classList.toggle('d-none', threshold > 0 && Number(item.dataset.pairValue || 0) < threshold);
        });
    }

    matrixSearch?.addEventListener('input', filterConflictMatrix);
    matrixIntensity?.addEventListener('change', filterConflictMatrix);
    matrixReset?.addEventListener('click', function () {
        if (matrixSearch) matrixSearch.value = '';
        if (matrixIntensity) matrixIntensity.value = 'all';
        filterConflictMatrix();
        matrixSearch?.focus();
    });
    filterConflictMatrix();
});
</script>
<?= $this->endSection() ?>
