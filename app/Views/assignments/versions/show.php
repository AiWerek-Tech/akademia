<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <a href="<?= base_url('assignments') ?>" class="btn btn-sm btn-light rounded-circle shadow-sm p-1 d-inline-flex align-items-center justify-content-center" style="width:32px; height:32px;" title="Kembali ke Daftar Penugasan">
            <i class="bi bi-arrow-left fs-6"></i>
        </a>
        <span class="text-muted small">Penugasan Mengajar</span>
        <span class="text-muted small">/</span>
        <span class="fw-semibold small text-primary"><?= esc($version['code']) ?></span>
    </div>

    <!-- Hero Header Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-white"><?= esc($version['name']) ?></h4>
                        <?php
                        $st = $version['workflow_status'];
                        $statusBadgeClass = match ($st) {
                            'DRAFT'      => 'bg-secondary bg-opacity-25 text-light border-secondary',
                            'VALIDATED'  => 'bg-info bg-opacity-25 text-info border-info',
                            'REVIEWED'   => 'bg-primary bg-opacity-25 text-primary-subtle border-primary',
                            'APPROVED'   => 'bg-success bg-opacity-25 text-success-subtle border-success',
                            'LOCKED'     => 'bg-warning bg-opacity-25 text-warning-subtle border-warning',
                            default      => 'bg-secondary bg-opacity-25 text-light border-secondary'
                        };
                        $statusIcon = match ($st) {
                            'DRAFT'      => 'bi-pencil-square',
                            'VALIDATED'  => 'bi-search',
                            'REVIEWED'   => 'bi-file-text',
                            'APPROVED'   => 'bi-check-all',
                            'LOCKED'     => 'bi-lock-fill',
                            default      => 'bi-info-circle'
                        };
                        ?>
                        <span class="badge border rounded-pill px-3 py-1 fs-8 fw-semibold <?= $statusBadgeClass ?>">
                            <i class="bi <?= $statusIcon ?> me-1"></i><?= esc($st) ?>
                        </span>
                    </div>
                    <p class="text-white-50 mb-0 small">
                        Kode: <span class="text-white font-monospace fw-semibold me-3"><?= esc($version['code']) ?></span>
                        Unit Aktif: <span class="text-white fw-semibold me-3"><?= esc($units[array_search($unit_id, array_column($units, 'id'))]['name'] ?? 'Global') ?></span>
                    </p>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
                        <button type="button" class="btn btn-sm btn-primary rounded-3 shadow-sm px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#assignTeacherModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Alokasikan Guru
                        </button>
                    <?php endif; ?>

                    <?php if ($version['workflow_status'] === 'DRAFT' && has_permission('assignments.manage')): ?>
                        <button type="button" id="btnAutoAssign" class="btn btn-sm btn-warning rounded-3 shadow-sm px-3 py-2 fw-bold text-dark" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff;">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Otomatisasi Alokasi 1-Klik
                        </button>
                    <?php endif; ?>

                    <?php if (has_permission('assignments.export')): ?>
                        <a href="<?= base_url('assignments/' . $version['uuid'] . '/export?unit_id=' . $unit_id) ?>" class="btn btn-sm btn-light rounded-3 shadow-sm border-0 fw-semibold text-dark px-3 py-2">
                            <i class="bi bi-file-earmark-excel text-success me-1"></i> Excel/CSV
                        </a>
                        <a target="_blank" href="<?= base_url('assignments/' . $version['uuid'] . '/documents/sk?unit_id=' . $unit_id) ?>" class="btn btn-sm btn-light rounded-3 shadow-sm border-0 fw-semibold text-primary px-3 py-2">
                            <i class="bi bi-award me-1"></i> Cetak SK
                        </a>
                    <?php endif; ?>

                    <!-- Workflow Actions -->
                    <?php if ($version['workflow_status'] === 'DRAFT' && has_permission('assignments.validate')): ?>
                        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/validate') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-info text-white rounded-3 shadow-sm px-3 py-2 fw-semibold">
                                <i class="bi bi-search me-1"></i> Validasi
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($version['workflow_status'] === 'VALIDATED' && has_permission('assignments.review')): ?>
                        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/review') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-primary rounded-3 shadow-sm px-3 py-2 fw-semibold">
                                <i class="bi bi-check-circle me-1"></i> Setujui Review
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($version['workflow_status'] === 'REVIEWED' && has_permission('assignments.approve')): ?>
                        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/approve') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-success rounded-3 shadow-sm px-3 py-2 fw-semibold">
                                <i class="bi bi-patch-check me-1"></i> Approve
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($version['workflow_status'] === 'APPROVED' && has_permission('assignments.lock')): ?>
                        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/workflow/lock') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-warning text-dark rounded-3 shadow-sm px-3 py-2 fw-semibold">
                                <i class="bi bi-lock-fill me-1"></i> Kunci & Aktifkan
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (($version['workflow_status'] === 'LOCKED' || $version['workflow_status'] === 'APPROVED') && has_permission('assignments.revise')): ?>
                        <button type="button" class="btn btn-sm btn-outline-light rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#revisionModal">
                            <i class="bi bi-pencil-square me-1"></i> Buat Revisi Baru
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= esc(session()->getFlashdata('success')) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= esc(session()->getFlashdata('error')) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Scope Switcher & Quick Metrics Summary -->
<?php
$matchedCount = 0;
$unassignedCount = 0;
$overAllocatedCount = 0;
$totalRequiredJP = 0.0;
$totalAssignedJP = 0.0;
foreach ($matrix as $mItem) {
    if (($mItem['validation_status'] ?? '') === 'MATCHED') $matchedCount++;
    elseif (($mItem['validation_status'] ?? '') === 'UNASSIGNED') $unassignedCount++;
    elseif (($mItem['validation_status'] ?? '') === 'OVER_ALLOCATED') $overAllocatedCount++;
    $totalRequiredJP += (float) ($mItem['effective_weekly_hours'] ?? 0);
    $totalAssignedJP += (float) ($mItem['assigned_weekly_hours'] ?? 0);
}
$scheduleShortageJP = 0.0;
$scheduleBlockShortage = 0;
if (!empty($scheduleCapacity['has_schedule'])) {
    foreach (($scheduleCapacity['classrooms'] ?? []) as $capacity) {
        $scheduleShortageJP += max(0, (float) ($capacity['capacity_gap'] ?? 0));
        $scheduleBlockShortage += max(0, (int) ($capacity['block_gap'] ?? 0));
    }
}
?>
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-4">
        <form method="get" class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary fs-5"></i>
                <label class="small fw-bold text-muted text-nowrap mb-0">Tampilkan Unit:</label>
                <select name="unit_id" class="form-select form-select-sm border-0 bg-light rounded-3 fw-semibold" onchange="this.form.submit()">
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($unit_id == $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="col-md-8">
        <div class="row g-2">
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                    <div class="small text-muted fw-semibold">Mapel Terisi Pas</div>
                    <div class="fs-4 fw-bold text-success mt-1"><i class="bi bi-check-circle me-1"></i><?= $matchedCount ?></div>
                    <div class="small text-muted">baris mapel, bukan JP</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                    <div class="small text-muted fw-semibold">Belum Ada Guru</div>
                    <div class="fs-4 fw-bold text-danger mt-1"><i class="bi bi-exclamation-triangle me-1"></i><?= $unassignedCount ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center h-100">
                    <div class="small text-muted fw-semibold">Kelebihan Jam</div>
                    <div class="fs-4 fw-bold text-warning mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $overAllocatedCount ?></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 <?= $scheduleShortageJP > 0 ? 'bg-danger bg-opacity-10 border border-danger-subtle' : 'bg-white' ?>">
                    <div class="small fw-semibold <?= $scheduleShortageJP > 0 ? 'text-danger' : 'text-muted' ?>">Kapasitas Jadwal</div>
                    <?php if ($scheduleShortageJP > 0): ?>
                        <div class="fs-4 fw-bold text-danger mt-1"><i class="bi bi-calendar2-x me-1"></i>-<?= rtrim(rtrim(number_format($scheduleShortageJP, 1, '.', ''), '0'), '.') ?> JP</div>
                        <div class="small text-danger">kurangi beban mapel<?= $scheduleBlockShortage > 0 ? ' · ' . $scheduleBlockShortage . ' blok 2 JP' : '' ?></div>
                    <?php elseif (!empty($scheduleCapacity['has_schedule'])): ?>
                        <div class="fs-4 fw-bold text-success mt-1"><i class="bi bi-calendar2-check me-1"></i>OK</div>
                        <div class="small text-muted">slot akademik cukup</div>
                    <?php else: ?>
                        <div class="fs-5 fw-bold text-muted mt-1">Belum tersedia</div>
                        <div class="small text-muted">versi jadwal belum ada</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($scheduleCapacity['has_schedule'])): ?>
    <?php
    $capacityWarnings = array_filter($scheduleCapacity['classrooms'] ?? [], static function (array $capacity): bool {
        return (float)($capacity['capacity_gap'] ?? 0) > 0 || (int)($capacity['block_gap'] ?? 0) > 0;
    });
    ?>
    <?php if ($capacityWarnings): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-calendar2-x-fill fs-5"></i>
                <div>
                    <div class="fw-bold">Beban Penugasan dan Kapasitas Jadwal Belum Seimbang</div>
                    <div class="small mt-1">Perhitungan ini sudah memasukkan kegiatan rutin yang memakai JP akademik. Kegiatan tersebut tidak mengurangi kebutuhan mapel, tetapi mengurangi slot yang tersedia untuk menempatkannya.</div>
                    <ul class="small mb-0 mt-2">
                        <?php foreach ($capacityWarnings as $capacity): ?>
                            <li><strong><?= esc($capacity['classroom_name']) ?></strong>: kebutuhan <?= esc(rtrim(rtrim(number_format((float)$capacity['required_jp'], 1, '.', ''), '0'), '.')) ?> JP, tersedia <?= esc($capacity['available_slots']) ?> JP setelah <?= esc($capacity['routine_jp']) ?> JP kegiatan rutin; kapasitas blok 2 JP <?= esc($capacity['available_2jp_blocks']) ?>.</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- NAVIGATION TABS -->
<ul class="nav nav-pills nav-fill mb-4 p-1.5 bg-white border shadow-sm rounded-4 gap-2" id="assignmentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 active d-flex align-items-center justify-content-center gap-2" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix-pane" type="button" role="tab">
            <i class="bi bi-table fs-6"></i> Matriks Mengajar Tatap Muka
            <span class="badge text-bg-primary-subtle text-primary rounded-pill ms-1 px-2 py-1"><?= count($matrix) ?> Slot</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2" id="duty-tab" data-bs-toggle="tab" data-bs-target="#duty-pane" type="button" role="tab">
            <i class="bi bi-person-workspace fs-6"></i> Tugas Tambahan & Ekuivalensi (Permendikbud)
            <span class="badge text-bg-info-subtle text-info rounded-pill ms-1 px-2 py-1"><?= count($duties) ?> Tugas</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-3 fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2" id="load-tab" data-bs-toggle="tab" data-bs-target="#load-pane" type="button" role="tab">
            <i class="bi bi-people-fill fs-6"></i> Beban Kerja Guru
            <span class="badge text-bg-success-subtle text-success rounded-pill ms-1 px-2 py-1"><?= count($teachers) ?> Guru</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="assignmentTabsContent">

    <!-- ==================== TAB 1: MATRIKS MENGAJAR ==================== -->
    <div class="tab-pane fade show active" id="matrix-pane" role="tabpanel">

        <!-- Live Filter Bar for Grade Level, Classroom & Search -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-1 flex-wrap" id="gradePillContainer">
                        <span class="small fw-bold text-muted me-2">Tingkat:</span>
                        <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 fw-bold grade-filter-btn active" data-grade="ALL">Semua</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="7">Kelas 7</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="8">Kelas 8</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="9">Kelas 9</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="10">Kelas 10</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="11">Kelas 11</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 fw-semibold grade-filter-btn" data-grade="12">Kelas 12</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="matrixClassroomFilter" class="form-select form-select-sm rounded-3">
                        <option value="">Semua Rombel...</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= esc(strtolower($c['name'])) ?>"><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="matrixSearchInput" class="form-control border-start-0 bg-light" placeholder="Cari mapel atau guru...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Matriks Kebutuhan & Alokasi Mengajar</h5>
                    <small class="text-muted">Daftar kebutuhan jam pelajaran rombel dan alokasi pengampu guru</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-1">Tampil: <strong id="matrixVisibleCount"><?= count($matrix) ?></strong> Slot</span>
                    <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
                        <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#assignTeacherModal">
                            <i class="bi bi-plus-lg me-1"></i> Alokasikan Guru
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" id="matrixTable">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light border-bottom">
                            <th class="ps-4" style="width: 160px;">Kelas / Rombel</th>
                            <th>Mata Pelajaran</th>
                            <th class="text-center" style="width: 90px;">Kebutuhan</th>
                            <th class="text-center" style="width: 90px;">Teralokasi</th>
                            <th class="text-center" style="width: 80px;">Sisa</th>
                            <th>Guru Pengampu</th>
                            <th class="text-center" style="width: 140px;">Status</th>
                            <th class="text-end pe-4" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($matrix)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    Belum ada struktur kurikulum atau rombel yang tersedia pada unit ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($matrix as $row): ?>
                                <?php
                                $clsName = (string)$row['classroom_name'];
                                // Extract Grade digits from classroom name (e.g. 7A -> 7, X-IPA -> 10)
                                preg_match('/\b(7|8|9|10|11|12|VII|VIII|IX|X|XI|XII)\b/i', $clsName, $m);
                                $gradeCode = '7';
                                if (!empty($m[1])) {
                                    $g = strtoupper($m[1]);
                                    $gradeCode = match($g) {
                                        'VII', '7' => '7',
                                        'VIII', '8' => '8',
                                        'IX', '9' => '9',
                                        'X', '10' => '10',
                                        'XI', '11' => '11',
                                        'XII', '12' => '12',
                                        default => '7'
                                    };
                                }
                                $teachersStr = '';
                                foreach ($row['teachers'] ?? [] as $tItem) {
                                    $teachersStr .= strtolower($tItem['full_name']) . ' ';
                                }
                                ?>
                                <tr class="matrix-row" data-grade="<?= $gradeCode ?>" data-class="<?= esc(strtolower($clsName)) ?>" data-search="<?= esc(strtolower($row['subject_name'] . ' ' . $row['subject_code'] . ' ' . $teachersStr)) ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= esc($row['classroom_name']) ?></div>
                                        <span class="badge text-bg-light border text-muted fs-9">Tingkat <?= $gradeCode ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?= esc($row['subject_name']) ?></div>
                                        <span class="badge bg-light text-muted border font-monospace fs-9"><?= esc($row['subject_code']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1 fw-semibold"><?= esc($row['effective_weekly_hours']) ?> JP</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 fw-bold"><?= esc($row['assigned_weekly_hours']) ?> JP</span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['remaining_weekly_hours'] > 0): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 fw-bold">+<?= esc($row['remaining_weekly_hours']) ?> JP</span>
                                        <?php elseif ($row['remaining_weekly_hours'] < 0): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 fw-bold"><?= esc($row['remaining_weekly_hours']) ?> JP</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 fw-bold">0 JP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (empty($row['teachers'])): ?>
                                            <span class="text-muted small italic"><i class="bi bi-person-x me-1"></i>Belum ditugaskan</span>
                                        <?php else: ?>
                                            <div class="d-flex flex-column gap-1">
                                                <?php foreach ($row['teachers'] as $t): ?>
                                                    <div class="d-flex align-items-center justify-content-between bg-light rounded-3 px-2 py-1 small gap-2">
                                                        <div>
                                                            <strong class="text-dark me-1"><?= esc($t['full_name']) ?></strong>
                                                            <span class="badge bg-white border text-secondary fs-9"><?= esc($t['assigned_weekly_hours']) ?> JP</span>
                                                            <span class="badge bg-info-subtle text-info-emphasis fs-9 ms-1"><?= esc($t['assignment_role']) ?></span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                            <?php if (!in_array($version['workflow_status'], ['APPROVED','LOCKED','ARCHIVED']) && has_permission('assignments.manage')): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-edit-assignment" title="Ganti atau perbarui guru" data-assignment-id="<?= (int)$t['assignment_id'] ?>" data-teacher-id="<?= (int)$t['id'] ?>" data-role="<?= esc($t['assignment_role']) ?>" data-hours="<?= esc($t['assigned_weekly_hours']) ?>" data-revision="<?= (int)$t['revision_number'] ?>" data-classroom-id="<?= (int)$row['classroom_id'] ?>" data-subject-id="<?= (int)$row['subject_id'] ?>" data-bs-toggle="modal" data-bs-target="#assignTeacherModal"><i class="bi bi-pencil-square"></i></button>
                                                                <form method="post" action="<?= base_url('assignments/'.$version['uuid'].'/delete-assignment/'.(int)$t['assignment_id']) ?>" class="d-inline" onsubmit="return confirm('Hapus alokasi <?= esc($t['full_name'],'js') ?> dari mapel ini?');"><?= csrf_field() ?><input type="hidden" name="revision_number" value="<?= (int)$t['revision_number'] ?>"><button class="btn btn-sm btn-outline-danger border-0 p-1" title="Hapus alokasi"><i class="bi bi-trash3"></i></button></form>
                                                            <?php endif; ?>
                                                            <?php if (has_permission('assignments.export')): ?><a target="_blank" class="btn btn-sm btn-link text-muted p-1" title="Cetak Surat Tugas Guru" href="<?= base_url('assignments/' . $version['uuid'] . '/documents/teacher/' . $t['id'] . '?unit_id=' . $unit_id) ?>"><i class="bi bi-printer text-primary"></i></a><?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['validation_status'] === 'MATCHED'): ?>
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle px-3 py-1 fw-bold">
                                                <i class="bi bi-check-circle me-1"></i>Terisi Pas
                                            </span>
                                        <?php elseif ($row['validation_status'] === 'UNASSIGNED'): ?>
                                            <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-1 fw-bold">
                                                <i class="bi bi-exclamation-triangle me-1"></i>Belum Ada Guru
                                            </span>
                                        <?php elseif ($row['validation_status'] === 'OVER_ALLOCATED'): ?>
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 fw-bold">
                                                <i class="bi bi-exclamation-circle me-1"></i>Kelebihan Jam
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-1 fw-bold"><?= esc($row['validation_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1 btn-quick-assign" data-classroom-id="<?= $row['classroom_id'] ?>" data-subject-id="<?= $row['subject_id'] ?>" data-bs-toggle="modal" data-bs-target="#assignTeacherModal">
                                                <i class="bi <?= empty($row['teachers']) ? 'bi-plus-circle' : 'bi-arrow-repeat' ?> me-1"></i> <?= empty($row['teachers']) ? 'Alokasi' : 'Ganti Guru' ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: TUGAS TAMBAHAN ==================== -->
    <div class="tab-pane fade" id="duty-pane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Tugas Tambahan & Ekuivalensi Jam Guru</h5>
                    <small class="text-muted">Wali Kelas, Wakil Kepala Sekolah, Kepala Lab/Perpus, Pembina OSIS/Pathfinder (Permendikbud No. 15/2018)</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light border-bottom">
                            <th class="ps-4">Nama Guru</th>
                            <th>Jenis Tugas Tambahan</th>
                            <th class="text-center">Jam Ekuivalensi</th>
                            <th>Keterangan / SK</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($duties)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-badge fs-2 d-block mb-2 text-secondary"></i>
                                    Belum ada tugas tambahan yang dialokasikan pada versi ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($duties as $d): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= esc($d['teacher_name']) ?></td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle px-2.5 py-1 rounded-pill fw-bold">
                                            <?= esc($d['duty_type_name'] ?: $d['duty_type_code']) ?>
                                        </span>
                                        <?php if (!empty($d['title_override'])): ?>
                                            <small class="d-block text-muted mt-1"><?= esc($d['title_override']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-success">+<?= esc($d['workload_hours']) ?> JP</td>
                                    <td><small class="text-muted"><?= esc($d['notes'] ?: '-') ?></small></td>
                                    <td class="text-end pe-4">
                                        <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-1 me-1 btn-edit-duty"
                                                    data-duty-id="<?= $d['id'] ?>"
                                                    data-teacher-id="<?= $d['teacher_id'] ?>"
                                                    data-duty-type-id="<?= $d['duty_type_id'] ?>"
                                                    data-title-override="<?= esc($d['title_override'] ?? '') ?>"
                                                    data-workload-hours="<?= $d['workload_hours'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editDutyModal"
                                                    title="Edit Tugas Tambahan">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/delete-duty/' . $d['id']) ?>" class="d-inline" onsubmit="return confirm('Hapus tugas tambahan ini?')">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger border-0 rounded-circle p-1" title="Hapus"><i class="bi bi-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Duty Form Card -->
        <?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Tambah Tugas Tambahan & Ekuivalensi Guru</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fs-8"><i class="bi bi-patch-check me-1"></i>Ekuivalensi Permendikbud</span>
                </div>
                <form id="assignDutyForm" method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/store-duty') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Guru Pengampu <span class="text-danger">*</span></label>
                            <select name="teacher_id" class="form-select rounded-3" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Jenis Tugas Tambahan <span class="text-danger">*</span></label>
                            <select name="duty_type_id" id="dutyTypeSelect" class="form-select rounded-3" required onchange="handleDutyTypeChange()">
                                <option value="">-- Pilih Jenis Tugas --</option>
                                <?php foreach ($dutyTypes as $dt): ?>
                                    <option value="<?= $dt['id'] ?>" data-code="<?= esc($dt['code'] ?? '') ?>" data-name="<?= esc(strtolower($dt['name'])) ?>" data-hours="<?= $dt['default_workload_hours'] ?>"><?= esc($dt['name']) ?> (+<?= esc($dt['default_workload_hours']) ?> JP)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3" id="dutyClassroomCol" style="display: none;">
                            <label class="form-label small fw-bold text-muted"><i class="bi bi-door-open me-1 text-primary"></i>Pilih Kelas (Wali Kelas) <span class="text-danger">*</span></label>
                            <select id="dutyClassroomSelect" class="form-select rounded-3">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classrooms as $c): ?>
                                    <option value="Wali Kelas <?= esc($c['name']) ?>">Kelas <?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Detail / Judul Tugas</label>
                            <input type="text" name="title_override" id="dutyTitleInput" class="form-control rounded-3" placeholder="Contoh: Wali Kelas X-1 / Pembina OSIS">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Jam Ekuivalensi (JP) <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" min="0.5" name="workload_hours" id="dutyHoursInput" class="form-control rounded-3" placeholder="Contoh: 2" required>
                        </div>
                        <div class="col-12 text-end pt-2">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 fw-bold shadow-sm"><i class="bi bi-save me-1"></i> Simpan Tugas Tambahan</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- ==================== TAB 3: BEBAN KERJA GURU ==================== -->
    <div class="tab-pane fade" id="load-pane" role="tabpanel">
        <!-- Toolbar Search & Sorting -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="teacherLoadSearchInput" class="form-control border-start-0 bg-light" placeholder="Cari nama guru...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-muted text-nowrap mb-0"><i class="bi bi-sort-down me-1"></i>Urutkan:</label>
                        <select id="teacherLoadSortSelect" class="form-select form-select-sm rounded-3 fw-semibold">
                            <option value="JP_DESC">Jam Terbanyak → Terkecil (⬇️)</option>
                            <option value="JP_ASC">Jam Terkecil → Terbanyak (⬆️)</option>
                            <option value="NAME_ASC">Nama Guru (A - Z)</option>
                            <option value="NEED_FIRST">Belum Terpenuhi Dulu (⚠️)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <span class="badge text-bg-light border text-dark fs-8">Total: <strong><?= count($teachers) ?></strong> Guru</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold mb-0">Ringkasan Beban Kerja Guru (Mengajar + Tugas Tambahan)</h5>
                <small class="text-muted">Target minimal sertifikasi Permendikbud: 24 JP / Mingguan</small>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0" id="teacherLoadTable">
                    <thead>
                        <tr class="text-uppercase text-muted fs-8 fw-bold bg-light border-bottom">
                            <th class="ps-4" style="width: 50px;">No.</th>
                            <th>Nama Guru</th>
                            <th class="text-center">Jam Tatap Muka</th>
                            <th class="text-center">Tugas Tambahan</th>
                            <th class="text-center">Total Beban (JP)</th>
                            <th class="text-center pe-4">Status Pemenuhan</th>
                        </tr>
                    </thead>
                    <tbody id="teacherLoadTbody">
                        <?php foreach ($teachers as $idx => $t): ?>
                            <?php
                            $tId = (int)$t['id'];
                            $l = $teacherLoads[$tId] ?? [];
                            $teachingJp = (float)($l['teaching_workload_hours'] ?? $l['teaching_assigned_hours'] ?? 0);
                            $dutyJp = (float)($l['additional_duty_hours'] ?? 0);
                            $tot = (float)($l['total_workload_hours'] ?? ($teachingJp + $dutyJp));

                            $formattedTeaching = rtrim(rtrim(number_format($teachingJp, 1, '.', ''), '0'), '.');
                            $formattedDuty = rtrim(rtrim(number_format($dutyJp, 1, '.', ''), '0'), '.');
                            $formattedTot = rtrim(rtrim(number_format($tot, 1, '.', ''), '0'), '.');
                            ?>
                            <tr class="teacher-load-row" data-tot="<?= $tot ?>" data-name="<?= esc(strtolower($t['full_name'])) ?>" data-status="<?= $tot >= 24 ? 1 : 0 ?>">
                                <td class="ps-4 fw-bold text-secondary fs-8 row-num"><?= $idx + 1 ?>.</td>
                                <td>
                                    <strong class="text-dark d-block"><?= esc($t['full_name']) ?></strong>
                                    <span class="badge text-bg-light border text-muted fs-9"><?= esc($t['employment_status'] ?? 'GURU_TETAP') ?></span>
                                </td>
                                <td class="text-center fw-bold text-primary"><?= $formattedTeaching ?> JP</td>
                                <td class="text-center fw-semibold text-info">+<?= $formattedDuty ?> JP</td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fs-7 px-3 py-1 rounded-pill fw-bold"><?= $formattedTot ?> JP</span>
                                </td>
                                <td class="text-center pe-4">
                                    <?php if ($tot >= 40): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-1"><i class="bi bi-exclamation-circle me-1"></i>Overload (&gt;40 JP)</span>
                                    <?php elseif ($tot >= 24): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1"><i class="bi bi-check-circle me-1"></i>Terpenuhi (&ge;24 JP)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-1"><i class="bi bi-clock me-1"></i>Kurang (<?= 24 - $tot ?> JP lagi)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- MODAL: ALOKASIKAN GURU PENGAMPU (FLEKSIBEL & BATCH) -->
<?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
<div class="modal fade" id="assignTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="assignTeacherForm" method="post" data-create-action="<?= base_url('assignments/' . $version['uuid'] . '/store-assignment') ?>" action="<?= base_url('assignments/' . $version['uuid'] . '/store-assignment') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="revision_number" id="assignmentRevision" value="1">
                <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                    <div>
                        <h5 id="assignmentModalTitle" class="modal-title fw-bold fs-6 text-white mb-0"><i class="bi bi-person-plus-fill me-2"></i>Alokasikan Guru Pengampu</h5>
                        <div id="assignmentModalSubtitle" class="text-white-50 small mt-0.5">Pilih guru, lalu centang beberapa mapel & kelas sekaligus untuk alokasi cepat.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-muted">Guru Pengampu <span class="text-danger">*</span></label>
                            <select name="teacher_id" id="selectTeacher" class="form-select rounded-3" required>
                                <option value="">-- Pilih Guru Pengampu --</option>
                                <?php foreach ($teachers as $t): ?>
                                    <?php
                                    $tId = (int)$t['id'];
                                    $l = $teacherLoads[$tId] ?? [];
                                    $tot = $l['total_workload_hours'] ?? 0;
                                    $rem = max(0, 24 - $tot);
                                    $stBadge = ($tot >= 40) ? '🔴 Overload' : (($tot >= 24) ? '🟢 Terpenuhi 24JP' : '🟡 Sisa ' . $rem . ' JP');
                                    ?>
                                    <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?> (Total: <?= $tot ?> JP | <?= $stBadge ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted">Peran Mengajar <span class="text-danger">*</span></label>
                            <select name="assignment_role" id="assignmentRoleSelect" class="form-select rounded-3" required>
                                <option value="PRIMARY">PRIMARY (Guru Utama)</option>
                                <option value="CO_TEACHER">CO_TEACHER (Guru Pendamping)</option>
                                <option value="ASSISTANT">ASSISTANT (Asisten Pengajar)</option>
                                <option value="SUBSTITUTE">SUBSTITUTE (Guru Pengganti)</option>
                                <option value="OTHER">OTHER (Lainnya)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Pilihan Mata Pelajaran -->
                        <div class="col-md-6">
                            <div class="card border rounded-3 h-100">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small text-dark"><i class="bi bi-journal-bookmark me-1 text-primary"></i> Mata Pelajaran <span class="text-danger">*</span></span>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 fs-9 fw-semibold" onclick="toggleAllSubjects(true)">Pilih Semua</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 fs-9 fw-semibold" onclick="toggleAllSubjects(false)">Reset</button>
                                    </div>
                                </div>
                                <div class="card-body p-2 overflow-auto" style="max-height: 220px;">
                                    <div class="vstack gap-1">
                                        <?php foreach ($subjects as $s): ?>
                                            <div class="form-check form-check-sm p-2 rounded hover-bg-light border-bottom border-light">
                                                <input class="form-check-input subject-checkbox me-2" type="checkbox" name="subject_ids[]" value="<?= $s['id'] ?>" id="subj_<?= $s['id'] ?>">
                                                <label class="form-check-label w-100 small fw-semibold text-dark cursor-pointer" for="subj_<?= $s['id'] ?>">
                                                    <?= esc($s['name']) ?> <span class="text-muted font-monospace fs-9 me-1">(<?= esc($s['code']) ?>)</span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pilihan Rombel Kelas -->
                        <div class="col-md-6">
                            <div class="card border rounded-3 h-100">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small text-dark"><i class="bi bi-door-open me-1 text-primary"></i> Rombel Kelas <span class="text-danger">*</span></span>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 fs-9 fw-semibold" onclick="toggleAllClassrooms(true)">Pilih Semua</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0 fs-9 fw-semibold" onclick="toggleAllClassrooms(false)">Reset</button>
                                    </div>
                                </div>
                                <div class="card-body p-2 overflow-auto" style="max-height: 220px;">
                                    <div class="vstack gap-1">
                                        <?php foreach ($classrooms as $c): ?>
                                            <div class="form-check form-check-sm p-2 rounded hover-bg-light border-bottom border-light">
                                                <input class="form-check-input classroom-checkbox me-2" type="checkbox" name="classroom_ids[]" value="<?= $c['id'] ?>" id="cls_<?= $c['id'] ?>">
                                                <label class="form-check-label w-100 small fw-semibold text-dark cursor-pointer" for="cls_<?= $c['id'] ?>">
                                                    <?= esc($c['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Pelajaran Config -->
                    <div class="mt-3 p-3 bg-light rounded-3 border">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="use_curriculum_jp" id="useCurriculumJpToggle" value="1" checked onchange="toggleCustomHoursInput(this)">
                            <label class="form-check-label fw-bold small text-dark" for="useCurriculumJpToggle">
                                <i class="bi bi-magic me-1 text-success"></i> Otomatis Gunakan JP Kurikulum (Sangat Direkomendasikan)
                            </label>
                        </div>
                        <div id="customHoursContainer" class="mt-2" style="display: none;">
                            <label class="form-label small fw-semibold text-muted">Jumlah Jam Mingguan Manual (JP per-Mapel)</label>
                            <input type="number" step="0.5" min="0.5" name="assigned_weekly_hours" id="assignedWeeklyHoursInput" class="form-control rounded-3" placeholder="Contoh: 3">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="assignmentSubmitButton" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Penugasan Guru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: EDIT TUGAS TAMBAHAN -->
<?php if (!in_array($version['workflow_status'], ['APPROVED', 'LOCKED', 'ARCHIVED']) && has_permission('assignments.manage')): ?>
<div class="modal fade" id="editDutyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="editDutyForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold fs-6 text-white mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Tugas Tambahan Guru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Guru Pengampu <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="editDutyTeacherSelect" class="form-select rounded-3" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Jenis Tugas Tambahan <span class="text-danger">*</span></label>
                        <select name="duty_type_id" id="editDutyTypeSelect" class="form-select rounded-3" required onchange="handleEditDutyTypeChange()">
                            <option value="">-- Pilih Jenis Tugas --</option>
                            <?php foreach ($dutyTypes as $dt): ?>
                                <option value="<?= $dt['id'] ?>" data-code="<?= esc($dt['code'] ?? '') ?>" data-name="<?= esc(strtolower($dt['name'])) ?>" data-hours="<?= $dt['default_workload_hours'] ?>"><?= esc($dt['name']) ?> (+<?= esc($dt['default_workload_hours']) ?> JP)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="editDutyClassroomCol" style="display: none;">
                        <label class="form-label small fw-bold text-muted"><i class="bi bi-door-open me-1 text-primary"></i>Pilih Kelas (Wali Kelas) <span class="text-danger">*</span></label>
                        <select id="editDutyClassroomSelect" class="form-select rounded-3">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classrooms as $c): ?>
                                <option value="Wali Kelas <?= esc($c['name']) ?>">Kelas <?= esc($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Detail / Judul Tugas</label>
                        <input type="text" name="title_override" id="editDutyTitleInput" class="form-control rounded-3" placeholder="Contoh: Wali Kelas X-1 / Pembina OSIS">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Jam Ekuivalensi (JP) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" min="0.5" name="workload_hours" id="editDutyHoursInput" class="form-control rounded-3" placeholder="Contoh: 2" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: REVISION MODAL -->
<div class="modal fade" id="revisionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('assignments/' . $version['uuid'] . '/clone') ?>" class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <?= csrf_field() ?>
            <div class="modal-header bg-primary text-white p-3">
                <h5 class="modal-title fw-bold fs-6 text-white"><i class="bi bi-pencil-square me-2"></i>Buat Revisi Penugasan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Kode Versi Baru <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3" placeholder="Contoh: TP-26-27-SM1-V1-REV1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Nama Versi Baru <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Penugasan Guru Ganjil v1 Revisi 1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Alasan Perubahan / Revisi <span class="text-danger">*</span></label>
                    <textarea name="change_reason" class="form-control rounded-3" rows="3" placeholder="Jelaskan alasan revisi..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-light rounded-3 px-4 fw-semibold" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger rounded-3 px-4 fw-bold">Buat Revisi Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Grade & Class & Search Live Filtering Engine (Tab 1)
    const gradeButtons = document.querySelectorAll('.grade-filter-btn');
    const classSelect = document.getElementById('matrixClassroomFilter');
    const searchInput = document.getElementById('matrixSearchInput');
    const matrixRows = document.querySelectorAll('.matrix-row');
    const visibleCountEl = document.getElementById('matrixVisibleCount');

    let activeGrade = 'ALL';

    function filterMatrix() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedClass = classSelect ? classSelect.value.toLowerCase().trim() : '';
        let count = 0;

        matrixRows.forEach(row => {
            const rGrade = row.getAttribute('data-grade') || '';
            const rClass = row.getAttribute('data-class') || '';
            const rSearch = row.getAttribute('data-search') || '';

            const matchesGrade = (activeGrade === 'ALL' || rGrade === activeGrade);
            const matchesClass = (!selectedClass || rClass.includes(selectedClass));
            const matchesSearch = (!query || rSearch.includes(query));

            if (matchesGrade && matchesClass && matchesSearch) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCountEl) visibleCountEl.textContent = count;
    }

    gradeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            gradeButtons.forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'active');
            activeGrade = this.getAttribute('data-grade');
            filterMatrix();
        });
    });

    if (classSelect) classSelect.addEventListener('change', filterMatrix);
    if (searchInput) searchInput.addEventListener('input', filterMatrix);

    function prepareAssignmentModal(classId, subId, editData = null) {
        const form = document.getElementById('assignTeacherForm');
        if (!form) return;
        form.reset();
        form.action = editData ? '<?= base_url('assignments/'.$version['uuid'].'/update-assignment/') ?>' + editData.assignmentId : form.dataset.createAction;
        document.querySelectorAll('.subject-checkbox,.classroom-checkbox').forEach(cb => cb.checked = false);
        const classBox = document.getElementById('cls_' + classId);
        const subjectBox = document.getElementById('subj_' + subId);
        if (classBox) classBox.checked = true;
        if (subjectBox) subjectBox.checked = true;
        const title = document.getElementById('assignmentModalTitle');
        const subtitle = document.getElementById('assignmentModalSubtitle');
        const submit = document.getElementById('assignmentSubmitButton');
        if (editData) {
            document.getElementById('selectTeacher').value = editData.teacherId;
            document.getElementById('assignmentRoleSelect').value = editData.role;
            document.getElementById('assignmentRevision').value = editData.revision;
            document.getElementById('useCurriculumJpToggle').checked = false;
            document.getElementById('assignedWeeklyHoursInput').value = editData.hours;
            toggleCustomHoursInput(document.getElementById('useCurriculumJpToggle'));
            if (title) title.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit / Ganti Guru Pengampu';
            if (subtitle) subtitle.textContent = 'Perubahan memperbarui alokasi yang sama, bukan menambahkan guru kedua.';
            if (submit) submit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Perbarui Penugasan';
        } else {
            document.getElementById('assignmentRevision').value = '1';
            if (title) title.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Alokasikan Guru Pengampu';
            if (subtitle) subtitle.textContent = 'Jika mapel sudah memiliki guru utama, pilihan baru akan menggantikan guru lama.';
            if (submit) submit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Penugasan Guru';
        }
        handleClassroomSubjectChange();
    }

    // 2. Quick Assign Button Pre-filling
    document.querySelectorAll('.btn-quick-assign').forEach(btn => {
        btn.addEventListener('click', function() {
            const clsId = this.getAttribute('data-classroom-id');
            const subId = this.getAttribute('data-subject-id');
            prepareAssignmentModal(clsId, subId);
        });
    });
    document.querySelectorAll('.btn-edit-assignment').forEach(btn => {
        btn.addEventListener('click', function() {
            prepareAssignmentModal(this.dataset.classroomId, this.dataset.subjectId, {
                assignmentId: this.dataset.assignmentId,
                teacherId: this.dataset.teacherId,
                role: this.dataset.role,
                hours: this.dataset.hours,
                revision: this.dataset.revision
            });
        });
    });

    // 3. Teacher Load Sort & Search Engine (Tab 3)
    const loadSearchInput = document.getElementById('teacherLoadSearchInput');
    const loadSortSelect  = document.getElementById('teacherLoadSortSelect');
    const loadTbody       = document.getElementById('teacherLoadTbody');

    function sortAndFilterTeacherLoads() {
        if (!loadTbody) return;
        const query   = loadSearchInput ? loadSearchInput.value.toLowerCase().trim() : '';
        const sortVal = loadSortSelect ? loadSortSelect.value : 'JP_DESC';
        const rows    = Array.from(loadTbody.querySelectorAll('.teacher-load-row'));

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            if (!query || name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        rows.sort((a, b) => {
            const totA   = parseFloat(a.getAttribute('data-tot') || '0');
            const totB   = parseFloat(b.getAttribute('data-tot') || '0');
            const nameA  = a.getAttribute('data-name') || '';
            const nameB  = b.getAttribute('data-name') || '';
            const isOkA  = parseInt(a.getAttribute('data-status') || '0');
            const isOkB  = parseInt(b.getAttribute('data-status') || '0');

            if (sortVal === 'JP_DESC') return totB - totA;
            if (sortVal === 'JP_ASC')  return totA - totB;
            if (sortVal === 'NAME_ASC') return nameA.localeCompare(nameB);
            if (sortVal === 'NEED_FIRST') {
                if (isOkA !== isOkB) return isOkA - isOkB;
                return totA - totB;
            }
            return 0;
        });

        let visibleIdx = 1;
        rows.forEach(row => {
            loadTbody.appendChild(row);
            if (row.style.display !== 'none') {
                const numCell = row.querySelector('.row-num');
                if (numCell) numCell.textContent = visibleIdx + '.';
                visibleIdx++;
            }
        });
    }

    if (loadSearchInput) loadSearchInput.addEventListener('input', sortAndFilterTeacherLoads);
    if (loadSortSelect)  loadSortSelect.addEventListener('change', sortAndFilterTeacherLoads);

    // Initial Sort on Load
    sortAndFilterTeacherLoads();

    // 4. Edit Duty Pre-filling Listener
    document.querySelectorAll('.btn-edit-duty').forEach(btn => {
        btn.addEventListener('click', function() {
            const dutyId = this.getAttribute('data-duty-id');
            const teacherId = this.getAttribute('data-teacher-id');
            const dutyTypeId = this.getAttribute('data-duty-type-id');
            const titleOverride = this.getAttribute('data-title-override');
            const workloadHours = this.getAttribute('data-workload-hours');

            const editForm = document.getElementById('editDutyForm');
            if (editForm && dutyId) {
                editForm.action = '<?= base_url('assignments/' . $version['uuid'] . '/update-duty/') ?>' + dutyId;
            }

            const selectTeacher = document.getElementById('editDutyTeacherSelect');
            const selectType = document.getElementById('editDutyTypeSelect');
            const inputTitle = document.getElementById('editDutyTitleInput');
            const inputHours = document.getElementById('editDutyHoursInput');

            if (selectTeacher) selectTeacher.value = teacherId;
            if (selectType) selectType.value = dutyTypeId;
            if (inputTitle) inputTitle.value = titleOverride;
            if (inputHours) inputHours.value = workloadHours;

            handleEditDutyTypeChange();
        });
    });
});

// Batch Toggle JS Helpers
function toggleAllSubjects(checked) {
    document.querySelectorAll('.subject-checkbox').forEach(cb => cb.checked = checked);
}

function toggleAllClassrooms(checked) {
    document.querySelectorAll('.classroom-checkbox').forEach(cb => cb.checked = checked);
}

function toggleCustomHoursInput(el) {
    const container = document.getElementById('customHoursContainer');
    if (container) {
        container.style.display = el.checked ? 'none' : 'block';
    }
}

// Matrix requirement lookup table for instant smart auto-fill
const matrixLookup = {
<?php foreach ($matrix as $mRow): ?>
    "<?= $mRow['classroom_id'] ?>-<?= $mRow['subject_id'] ?>": <?= (float)$mRow['remaining_weekly_hours'] > 0 ? (float)$mRow['remaining_weekly_hours'] : (float)$mRow['effective_weekly_hours'] ?>,
<?php endforeach; ?>
};

function handleClassroomSubjectChange() {
    const classId = document.getElementById('selectClassroom')?.value;
    const subId = document.getElementById('selectSubject')?.value;
    const inputHours = document.getElementById('assignedWeeklyHoursInput');
    const badge = document.getElementById('autoFillBadge');

    if (classId && subId && inputHours) {
        const key = classId + '-' + subId;
        if (matrixLookup[key] !== undefined) {
            inputHours.value = matrixLookup[key];
            if (badge) badge.style.display = 'inline-flex';
        } else {
            if (badge) badge.style.display = 'none';
        }
    }
}

function handleDutyTypeChange() {
    const select = document.getElementById('dutyTypeSelect');
    const inputHours = document.getElementById('dutyHoursInput');
    const classCol = document.getElementById('dutyClassroomCol');
    const titleInput = document.getElementById('dutyTitleInput');
    const classSelect = document.getElementById('dutyClassroomSelect');

    if (!select || select.selectedOptions.length === 0) return;
    const option = select.selectedOptions[0];
    const hours = option.getAttribute('data-hours');
    const code = (option.getAttribute('data-code') || '').toUpperCase();
    const name = (option.getAttribute('data-name') || '').toLowerCase();

    if (hours && inputHours) {
        inputHours.value = hours;
    }

    const isWaliKelas = code.includes('WALI') || name.includes('wali');
    if (isWaliKelas) {
        if (classCol) classCol.style.display = 'block';
        if (classSelect) {
            classSelect.required = true;
            classSelect.onchange = function() {
                if (titleInput && this.value) {
                    titleInput.value = this.value;
                }
            };
            if (classSelect.value && titleInput) {
                titleInput.value = classSelect.value;
            }
        }
    } else {
        if (classCol) classCol.style.display = 'none';
        if (classSelect) {
            classSelect.required = false;
            classSelect.value = '';
        }
        if (titleInput && titleInput.value.startsWith('Wali Kelas')) {
            titleInput.value = '';
        }
    }
}

function handleEditDutyTypeChange() {
    const select = document.getElementById('editDutyTypeSelect');
    const inputHours = document.getElementById('editDutyHoursInput');
    const classCol = document.getElementById('editDutyClassroomCol');
    const titleInput = document.getElementById('editDutyTitleInput');
    const classSelect = document.getElementById('editDutyClassroomSelect');

    if (!select || select.selectedOptions.length === 0) return;
    const option = select.selectedOptions[0];
    const hours = option.getAttribute('data-hours');
    const code = (option.getAttribute('data-code') || '').toUpperCase();
    const name = (option.getAttribute('data-name') || '').toLowerCase();

    if (hours && inputHours && (!inputHours.value || inputHours.value == 0)) {
        inputHours.value = hours;
    }

    const isWaliKelas = code.includes('WALI') || name.includes('wali');
    if (isWaliKelas) {
        if (classCol) classCol.style.display = 'block';
        if (classSelect) {
            classSelect.required = true;
            classSelect.onchange = function() {
                if (titleInput && this.value) {
                    titleInput.value = this.value;
                }
            };
            if (titleInput && titleInput.value) {
                const matchedOption = Array.from(classSelect.options).find(opt => opt.value === titleInput.value);
                if (matchedOption) {
                    classSelect.value = matchedOption.value;
                }
            }
        }
    } else {
        if (classCol) classCol.style.display = 'none';
        if (classSelect) {
            classSelect.required = false;
        }
    }
}

// CSRF Helper Utilities
function getLiveCsrfToken() {
    const name = 'csrf_cookie_name=';
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    const hiddenInput = document.querySelector('input[name="csrf_token_name"]');
    if (hiddenInput && hiddenInput.value) {
        return hiddenInput.value;
    }
    return '<?= csrf_hash() ?>';
}

function syncCsrfTokenInDOM(newToken) {
    if (!newToken) return;
    document.querySelectorAll('input[name="csrf_token_name"]').forEach(inp => {
        inp.value = newToken;
    });
}

// ⚡ Auto-Assign 1-Click Button Assistant
document.getElementById('btnAutoAssign')?.addEventListener('click', function() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: 'Jalankan Otomatisasi Alokasi?',
        text: 'Asisten Cerdas akan memindai slot mengajar yang belum terisi dan menugaskan guru dengan kuota terluang secara otomatis.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Jalankan Sekarang',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-4',
            confirmButton: 'btn btn-primary rounded-3 px-4 me-2',
            cancelButton: 'btn btn-light rounded-3 px-4'
        },
        buttonsStyling: false
    }).then(res => {
        if (res.isConfirmed) {
            Swal.fire({
                title: 'Memproses Alokasi...',
                text: 'Asisten Cerdas sedang menghitung kuota dan mencocokkan guru.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const tokenVal = getLiveCsrfToken();
            fetch('<?= base_url('assignments/' . $version['uuid'] . '/auto-assign') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': tokenVal,
                    'csrf_token_name': tokenVal
                }
            })
            .then(async r => {
                let data;
                try { data = await r.json(); } catch(e) { data = { status: 'error', message: 'Respon server bermasalah.' }; }
                syncCsrfTokenInDOM(getLiveCsrfToken());
                if (!r.ok || data.status === 'error') throw new Error(data.message || 'Gagal otomatisasi.');
                return data;
            })
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Otomatisasi Selesai',
                    text: data.message,
                    timer: 1800,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Gagal', text: err.message || 'Terjadi kesalahan' });
            });
        }
    });
});

// Assign Teacher Form Handler
document.getElementById('assignTeacherForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const url = form.action;
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonContent = submitButton?.innerHTML;

    var showMessage = (options) => (typeof Swal !== 'undefined')
        ? Swal.fire(Object.assign({ confirmButtonText: 'Mengerti', buttonsStyling: false, customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4' } }, options))
        : alert(options.title + '\n' + (options.text || ''));

    // Validate at least 1 subject and 1 classroom are checked
    const checkedSubjects = form.querySelectorAll('.subject-checkbox:checked');
    const checkedClassrooms = form.querySelectorAll('.classroom-checkbox:checked');
    if (checkedSubjects.length === 0 || checkedClassrooms.length === 0) {
        showMessage({ icon: 'warning', title: 'Pilihan Belum Lengkap', text: 'Silakan centang minimal satu Mata Pelajaran dan satu Rombel Kelas.' });
        return;
    }

    const tokenVal = getLiveCsrfToken();
    formData.set('csrf_token_name', tokenVal);

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan ' + (checkedSubjects.length * checkedClassrooms.length) + ' penugasan...';
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': tokenVal
        }
    })
    .then(async res => {
        let data;
        try {
            data = await res.json();
        } catch (err) {
            data = { status: 'error', message: 'Gagal memproses respon server (' + res.status + ').' };
        }
        syncCsrfTokenInDOM(getLiveCsrfToken());
        if (!res.ok || data.status === 'error') {
            const details = data.errors ? Object.values(data.errors).join('\n') : '';
            throw new Error(data.message + (details ? '\n' + details : ''));
        }
        return data;
    })
    .then(data => {
        return showMessage({
            icon: 'success',
            title: 'Penugasan tersimpan',
            text: data.message,
            timer: 1600,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => window.location.reload());
    })
    .catch(err => {
        console.error(err);
        return showMessage({ icon: 'error', title: 'Gagal menyimpan', text: err.message || 'Terjadi kesalahan sistem.' });
    })
    .finally(() => {
        if (submitButton && document.body.contains(submitButton)) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
        }
    });
});

// Assign Duty Form Handler
document.getElementById('assignDutyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const url = form.action;
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonContent = submitButton?.innerHTML;

    const tokenVal = getLiveCsrfToken();
    formData.set('csrf_token_name', tokenVal);

    var showMessage = (options) => (typeof Swal !== 'undefined')
        ? Swal.fire(Object.assign({ confirmButtonText: 'Mengerti', buttonsStyling: false, customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4' } }, options))
        : alert(options.title + '\n' + (options.text || ''));

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': tokenVal
        }
    })
    .then(async res => {
        let data;
        try {
            data = await res.json();
        } catch (err) {
            data = { status: 'error', message: 'Gagal memproses respon server (' + res.status + ').' };
        }
        syncCsrfTokenInDOM(getLiveCsrfToken());
        if (!res.ok || data.status === 'error') {
            const details = data.errors ? Object.values(data.errors).join('\n') : '';
            throw new Error(data.message + (details ? '\n' + details : ''));
        }
        return data;
    })
    .then(data => {
        return showMessage({
            icon: 'success',
            title: 'Tugas tambahan tersimpan',
            text: data.message,
            timer: 1600,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => window.location.reload());
    })
    .catch(err => {
        console.error(err);
        return showMessage({ icon: 'error', title: 'Gagal menyimpan', text: err.message || 'Terjadi kesalahan sistem.' });
    })
    .finally(() => {
        if (submitButton && document.body.contains(submitButton)) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
        }
    });
});

// Edit Duty Form Handler
document.getElementById('editDutyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const url = form.action;
    if (!url) return;
    const formData = new FormData(form);
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonContent = submitButton?.innerHTML;

    const tokenVal = getLiveCsrfToken();
    formData.set('csrf_token_name', tokenVal);

    var showMessage = (options) => (typeof Swal !== 'undefined')
        ? Swal.fire(Object.assign({ confirmButtonText: 'Mengerti', buttonsStyling: false, customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4' } }, options))
        : alert(options.title + '\n' + (options.text || ''));

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': tokenVal
        }
    })
    .then(async res => {
        let data;
        try {
            data = await res.json();
        } catch (err) {
            data = { status: 'error', message: 'Gagal memproses respon server (' + res.status + ').' };
        }
        syncCsrfTokenInDOM(getLiveCsrfToken());
        if (!res.ok || data.status === 'error') {
            const details = data.errors ? Object.values(data.errors).join('\n') : '';
            throw new Error(data.message + (details ? '\n' + details : ''));
        }
        return data;
    })
    .then(data => {
        return showMessage({
            icon: 'success',
            title: 'Tugas tambahan diperbarui',
            text: data.message,
            timer: 1600,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => window.location.reload());
    })
    .catch(err => {
        console.error(err);
        return showMessage({ icon: 'error', title: 'Gagal memperbarui', text: err.message || 'Terjadi kesalahan sistem.' });
    })
    .finally(() => {
        if (submitButton && document.body.contains(submitButton)) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
        }
    });
});
</script>
<?= $this->endSection() ?>
