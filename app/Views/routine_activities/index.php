<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fs-8 fw-semibold">
                    <i class="bi bi-clock-history me-1"></i>Master Data Akademik
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fs-8 fw-semibold">
                    <i class="bi bi-shield-check me-1"></i>Sistem Terpisah 100%
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-1">Master Kegiatan Rutin Sekolah</h3>
            <p class="text-muted small mb-0">Kelola agenda rutin tetap (Upacara, Istirahat, Kerohanian, WORKED, Apel) tanpa mengotori Master Mata Pelajaran Akademik.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (has_permission('curriculum.manage')): ?>
                <button type="button" class="btn btn-primary rounded-3 px-3 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#createActivityModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Tambah Kegiatan Rutin</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Summary Cards -->
    <?php
    $totalCount = count($activities);
    $lockedCount = count(array_filter($activities, fn($a) => (int)($a['is_locked_slot'] ?? 0) === 1));
    $teachingLoadCount = count(array_filter($activities, fn($a) => (int)($a['counts_as_teaching_load'] ?? 0) === 1));
    $worshipCount = count(array_filter($activities, fn($a) => ($a['activity_type'] ?? '') === 'WORSHIP'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary-subtle text-primary rounded-4">
                        <i class="bi bi-calendar2-week fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Kegiatan Rutin</div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalCount ?> <span class="fs-7 fw-normal text-muted">agenda</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger-subtle text-danger rounded-4">
                        <i class="bi bi-lock-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Fixed Slot (Terkunci)</div>
                        <div class="fs-4 fw-bold text-danger"><?= $lockedCount ?> <span class="fs-7 fw-normal text-muted">slot jadwal</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success-subtle text-success rounded-4">
                        <i class="bi bi-briefcase-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Hitung Beban Mengajar</div>
                        <div class="fs-4 fw-bold text-success"><?= $teachingLoadCount ?> <span class="fs-7 fw-normal text-muted">kegiatan</span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-purple-subtle text-purple rounded-4" style="background-color: #f3e8ff; color: #7c3aed;">
                        <i class="bi bi-heart-fill fs-3"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Kegiatan Kerohanian</div>
                        <div class="fs-4 fw-bold" style="color: #7c3aed;"><?= $worshipCount ?> <span class="fs-7 fw-normal text-muted">agenda</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Unit Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="get" action="<?= base_url('routine-activities') ?>" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label small fw-bold text-muted mb-0"><i class="bi bi-funnel me-1"></i>Filter Unit Sekolah:</label>
                </div>
                <div class="col-md-3">
                    <select name="unit_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                        <?php if (is_super_admin()): ?><option value="">-- Semua Unit Sekolah --</option><?php endif; ?>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (int)$unit_id === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($unit_id)): ?>
                    <div class="col-auto">
                        <a href="<?= base_url('routine-activities') ?>" class="btn btn-sm btn-outline-secondary rounded-3">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Activity List Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Daftar Kegiatan Rutin & Fixed Agenda</h5>
            <span class="badge text-bg-light border text-secondary fs-8">Otomatis Siap untuk Slot Terkunci Grid Jadwal</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light border-bottom">
                        <th class="ps-4">No.</th>
                        <th>Nama Kegiatan</th>
                        <th>Kode</th>
                        <th class="text-center">Durasi & Waktu</th>
                        <th class="text-center">Tipe Kegiatan</th>
                        <th class="text-center">Beban & Penanggung Jawab</th>
                        <th class="text-center">Warna Grid</th>
                        <th class="text-center">Kunci Slot (Fixed Slot & Posisi)</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada kegiatan rutin yang ditambahkan. Klik tombol "Tambah Kegiatan Rutin" di atas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $idx => $act): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary fs-8"><?= $idx + 1 ?>.</td>
                                <td>
                                    <strong class="text-dark d-block"><?= esc($act['name']) ?></strong>
                                    <?php if (!empty($act['notes'])): ?>
                                        <small class="text-muted"><?= esc($act['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge text-bg-light border text-dark font-monospace"><?= esc($act['code']) ?></span>
                                </td>
                                <td class="text-center fw-bold">
                                    <?php if (($act['duration_mode'] ?? 'STANDARD_JP') === 'CUSTOM_MINUTES' && !empty($act['duration_minutes'])): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1">
                                            <i class="bi bi-stopwatch me-1"></i>⏱️ <?= (int)$act['duration_minutes'] ?> Menit
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                            📚 <?= esc($act['default_duration_jp']) ?> JP
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $typeMap = [
                                        'FIXED_ROUTINE'    => ['bg-info-subtle text-info-emphasis', 'Rutin Tetap'],
                                        'FLEXIBLE_ROUTINE' => ['bg-secondary-subtle text-secondary', 'Rutin Fleksibel'],
                                        'BREAK'            => ['bg-warning-subtle text-warning-emphasis', 'Istirahat'],
                                        'WORSHIP'          => ['bg-success-subtle text-success-emphasis', 'Kerohanian'],
                                        'CEREMONY'         => ['bg-danger-subtle text-danger-emphasis', 'Upacara / Apel'],
                                    ];
                                    $tInfo = $typeMap[$act['activity_type']] ?? ['bg-light text-dark', $act['activity_type']];
                                    ?>
                                    <span class="badge <?= $tInfo[0] ?> rounded-pill px-3 py-1 fw-bold"><?= $tInfo[1] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)($act['counts_as_teaching_load'] ?? 0) === 1): ?>
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-bold fs-9">
                                                <i class="bi bi-briefcase-fill me-1"></i>+<?= esc($act['default_duration_jp']) ?> JP Beban Guru
                                            </span>
                                            <?php if (($act['assignment_strategy'] ?? '') === 'ROLE_BASED'): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill fs-9">
                                                    👥 <?= esc($act['assignment_role_default'] ?: 'Wali Kelas') ?> (Dinamis Rombel)
                                                </span>
                                            <?php elseif (($act['assignment_strategy'] ?? '') === 'SPECIFIC_TEACHER' && !empty($act['specific_teacher_id'])): ?>
                                                <?php
                                                $assignedTeacher = array_filter($teachers, fn($t) => (int)$t['id'] === (int)$act['specific_teacher_id']);
                                                $tName = !empty($assignedTeacher) ? current($assignedTeacher)['full_name'] : 'Guru #' . $act['specific_teacher_id'];
                                                ?>
                                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill fs-9" style="background-color: #f3e8ff; color: #6b21a8;">
                                                    👤 <?= esc($tName) ?> (Guru Langsung)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill fs-9">
                                                    👤 <?= esc($act['assignment_role_default'] ?: 'Pengampu') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 fs-9">
                                            <i class="bi bi-cup-hot me-1"></i>Tanpa Beban Guru
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1.5 px-2 py-1 rounded border bg-light">
                                        <span class="d-inline-block rounded-circle" style="width: 14px; height: 14px; background-color: <?= esc($act['color_label'] ?: '#6c757d') ?>;"></span>
                                        <small class="font-monospace text-muted fs-9"><?= esc($act['color_label'] ?: '#6c757d') ?></small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$act['is_locked_slot'] === 1): ?>
                                        <?php
                                        $dayMap = ['ALL_DAYS' => '📅 Setiap Hari', 'MONDAY' => 'Senin', 'TUESDAY' => 'Selasa', 'WEDNESDAY' => 'Rabu', 'THURSDAY' => 'Kamis', 'FRIDAY' => 'Jumat'];
                                        $dKey = strtoupper((string)($act['default_day'] ?? ''));
                                        $dayName = ($dKey === 'ALL_DAYS' || $dKey === '') ? '📅 Setiap Hari' : ($dayMap[$dKey] ?? esc($act['default_day']));

                                        $zone = $act['placement_zone'] ?? 'ACADEMIC_JP';
                                        $seq  = (int)($act['placement_sequence'] ?? 1);
                                        $startP = !empty($act['locked_period_start']) ? (int)$act['locked_period_start'] : 1;
                                        $endP   = !empty($act['locked_period_end']) ? (int)$act['locked_period_end'] : $startP;

                                        $posText = '';
                                        if ($zone === 'PRE_ACADEMIC') {
                                            $posText = "🌅 Pra-JP (#Urutan {$seq})";
                                        } elseif ($zone === 'INTERMISSION_BREAK') {
                                            $posText = "☕ Sela JP (#Setelah Jam {$seq})";
                                        } elseif ($zone === 'POST_ACADEMIC') {
                                            $posText = "🌇 Pasca-JP (#Urutan {$seq})";
                                        } else {
                                            if ($endP > $startP) {
                                                $posText = "📚 Jam ke-{$startP} s.d. {$endP}";
                                            } else {
                                                $posText = "📚 Jam ke-{$startP}";
                                            }
                                        }
                                        ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold fs-9">
                                            <i class="bi bi-lock-fill me-1"></i>🔒 <?= $dayName ?> (<?= $posText ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 fs-9">Slot Fleksibel</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if (has_permission('curriculum.manage') && (! empty($act['unit_id']) || is_super_admin())): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-1 me-1 btn-edit-activity"
                                                data-id="<?= $act['id'] ?>"
                                                data-unit-id="<?= $act['unit_id'] ?>"
                                                data-code="<?= esc($act['code']) ?>"
                                                data-name="<?= esc($act['name']) ?>"
                                                data-short-name="<?= esc($act['short_name'] ?? '') ?>"
                                                data-activity-type="<?= esc($act['activity_type']) ?>"
                                                data-duration-mode="<?= esc($act['duration_mode'] ?? 'STANDARD_JP') ?>"
                                                data-duration-jp="<?= esc($act['default_duration_jp']) ?>"
                                                data-duration-minutes="<?= esc($act['duration_minutes'] ?? '') ?>"
                                                data-color="<?= esc($act['color_label']) ?>"
                                                data-locked="<?= $act['is_locked_slot'] ?>"
                                                data-day="<?= esc($act['default_day'] ?? '') ?>"
                                                data-period-start="<?= esc($act['locked_period_start'] ?? $act['default_period_number'] ?? '') ?>"
                                                data-placement-zone="<?= esc($act['placement_zone'] ?? 'ACADEMIC_JP') ?>"
                                                data-placement-sequence="<?= esc($act['placement_sequence'] ?? '1') ?>"
                                                data-teaching-load="<?= $act['counts_as_teaching_load'] ?? 0 ?>"
                                                data-strategy="<?= esc($act['assignment_strategy'] ?? 'NONE') ?>"
                                                data-role-default="<?= esc($act['assignment_role_default'] ?? '') ?>"
                                                data-specific-teacher="<?= esc($act['specific_teacher_id'] ?? '') ?>"
                                                data-notes="<?= esc($act['notes'] ?? '') ?>"
                                                data-bs-toggle="modal" data-bs-target="#editActivityModal"
                                                title="Edit Kegiatan">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-1 btn-delete-activity"
                                                data-id="<?= $act['id'] ?>"
                                                data-name="<?= esc($act['name']) ?>"
                                                title="Hapus Kegiatan">
                                            <i class="bi bi-trash"></i>
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

<!-- MODAL: TAMBAH KEGIATAN RUTIN -->
<?php if (has_permission('curriculum.manage')): ?>
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form id="createActivityForm" method="post" action="<?= base_url('routine-activities') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 py-3 px-4 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-white bg-opacity-20 rounded-3">
                            <i class="bi bi-plus-circle-fill fs-5 text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold fs-6 text-white mb-0">Tambah Kegiatan Rutin Sekolah</h5>
                            <small class="text-white text-opacity-75 fs-9">Kelola agenda rutin tetap & slot terkunci jadwal</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light-subtle">

                    <!-- SECTION 1: INFORMASI UTAMA -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="fw-bold text-primary small mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-info-circle-fill"></i> 1. Informasi Utama Kegiatan
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted mb-1">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Upacara Bendera / Chapel Worship / Istirahat" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Kode Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" placeholder="UPACARA" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Tipe Kegiatan <span class="text-danger">*</span></label>
                                <select name="activity_type" class="form-select rounded-3" required>
                                    <option value="CEREMONY">Upacara / Apel / Bendera</option>
                                    <option value="WORSHIP">Kerohanian (Chapel, Worship, Doa 777, SID)</option>
                                    <option value="BREAK">Istirahat / Makan</option>
                                    <option value="FIXED_ROUTINE" selected>Rutin Tetap Lainnya</option>
                                    <option value="FLEXIBLE_ROUTINE">Rutin Fleksibel</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Unit Sekolah</label>
                                <select name="unit_id" class="form-select rounded-3">
                                    <?php if (is_super_admin()): ?><option value="">-- Berlaku untuk Semua Unit --</option><?php endif; ?>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= (int)$unit_id === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: DURASI & WAKTU -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="fw-bold text-primary small mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-clock-history"></i> 2. Pengaturan Durasi & Sistem Waktu
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Mode Durasi Waktu <span class="text-danger">*</span></label>
                                <select name="duration_mode" id="createDurationMode" class="form-select rounded-3">
                                    <option value="STANDARD_JP" selected>📚 Standar Jam Pelajaran (JP)</option>
                                    <option value="CUSTOM_MINUTES">⏱️ Durasi Menit Spesifik (Kustom)</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="createJpBox">
                                <label class="form-label small fw-bold text-muted mb-1">Durasi JP (Jumlah Jam) <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0.5" name="default_duration_jp" class="form-control rounded-3" value="1.0" required>
                            </div>
                            <div class="col-md-6" id="createMinutesBox" style="display: none;">
                                <label class="form-label small fw-bold text-muted mb-1">Durasi Menit Spesifik (Menit)</label>
                                <input type="number" min="5" max="180" name="duration_minutes" class="form-control rounded-3" placeholder="Contoh: 15 / 10 / 20">
                                <div class="form-text fs-9">Misal: Istirahat 15 Menit, Apel 10 Menit.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: FIXED SLOT & PLACEMENT ZONES -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                <i class="bi bi-lock-fill text-danger"></i> 3. Penguncian Grid Jadwal & Zona Posisi (Placement Engine)
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_locked_slot" id="createLockedSlot" value="1">
                                <label class="form-check-label fw-bold text-danger fs-8" for="createLockedSlot">Aktifkan Fixed Slot</label>
                            </div>
                        </div>
                        <div class="row g-3 pt-1">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Hari Terkunci</label>
                                <select name="default_day" class="form-select rounded-3">
                                    <option value="ALL_DAYS" selected>📅 Setiap Hari (Senin - Jumat)</option>
                                    <option value="MONDAY">Senin</option>
                                    <option value="TUESDAY">Selasa</option>
                                    <option value="WEDNESDAY">Rabu</option>
                                    <option value="THURSDAY">Kamis</option>
                                    <option value="FRIDAY">Jumat</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Zona Penempatan Grid</label>
                                <select name="placement_zone" id="createPlacementZone" class="form-select rounded-3">
                                    <option value="PRE_ACADEMIC">🌅 Pra-Akademik (Sebelum Jam ke-1 / Slot #)</option>
                                    <option value="ACADEMIC_JP" selected>📚 Jam Pelajaran Akademik (JP 1, 2, 3...)</option>
                                    <option value="INTERMISSION_BREAK">☕ Sela Jam Pelajaran / Istirahat (Slot #)</option>
                                    <option value="POST_ACADEMIC">🌇 Pasca-Akademik (Setelah Jam Akhir / Slot #)</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="createSeqJpBox">
                                <label class="form-label small fw-bold text-muted mb-1" id="createSeqLabel">Jam ke-N Terkunci</label>
                                <select name="locked_period_start" id="createLockedPeriodStart" class="form-select rounded-3">
                                    <option value="1">Jam ke-1</option>
                                    <option value="2">Jam ke-2</option>
                                    <option value="3">Jam ke-3</option>
                                    <option value="4">Jam ke-4</option>
                                    <option value="5">Jam ke-5</option>
                                    <option value="6">Jam ke-6</option>
                                    <option value="7">Jam ke-7</option>
                                    <option value="8">Jam ke-8</option>
                                    <option value="9">Jam ke-9</option>
                                    <option value="10">Jam ke-10</option>
                                </select>
                                <input type="hidden" name="placement_sequence" id="createPlacementSequence" value="1">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: BEBAN GURU & PENANGGUNG JAWAB -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                <i class="bi bi-briefcase-fill text-success"></i> 4. Beban Mengajar & Penanggung Jawab
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="counts_as_teaching_load" id="createTeachingLoad" value="1">
                                <label class="form-check-label fw-bold text-success fs-8" for="createTeachingLoad">Hitung Beban Mengajar (JP)</label>
                            </div>
                        </div>
                        <div class="row g-3 pt-1">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Strategi Penanggung Jawab</label>
                                <select name="assignment_strategy" id="createAssignmentStrategy" class="form-select rounded-3">
                                    <option value="NONE">-- Tanpa Penanggung Jawab --</option>
                                    <option value="ROLE_BASED">👥 Dinamis Berdasar Rombel (Wali Kelas)</option>
                                    <option value="SPECIFIC_TEACHER">👤 Pilih Guru Spesifik Langsung</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="createRoleBox">
                                <label class="form-label small fw-bold text-muted mb-1">Jabatan / Role Rombel</label>
                                <select name="assignment_role_default" class="form-select rounded-3">
                                    <option value="Wali Kelas">Wali Kelas (e.g. WORKED)</option>
                                    <option value="Chaplain">Chaplain / Guru Pembina</option>
                                    <option value="Guru Pengampu">Guru Pengampu</option>
                                    <option value="Guru Piket">Guru Piket / Petugas</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="createTeacherBox" style="display: none;">
                                <label class="form-label small fw-bold text-muted mb-1">Pilih Guru Penanggung Jawab</label>
                                <select name="specific_teacher_id" class="form-select rounded-3">
                                    <option value="">-- Pilih Guru Penanggung Jawab --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <?php $idCode = $t['employee_number'] ?? $t['nip'] ?? $t['teacher_initial'] ?? ''; ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?><?= $idCode !== '' ? ' (' . esc($idCode) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: TAMPILAN GRID & CATATAN -->
                    <div class="card border border-light-subtle rounded-3 p-3 bg-white shadow-2xs">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted mb-1">Warna Label Grid Jadwal</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="color_label" class="form-control form-control-color rounded-3" value="#5b5ce2">
                                    <span class="small text-muted fs-9">Pilih warna blok grid</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-muted mb-1">Keterangan / Catatan</label>
                                <textarea name="notes" class="form-control rounded-3" rows="1" placeholder="Catatan khusus..."></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        <i class="bi bi-check-circle-fill me-1"></i> Simpan Kegiatan Rutin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDIT KEGIATAN RUTIN -->
<div class="modal fade" id="editActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <form id="editActivityForm" method="post" action="">
                <?= csrf_field() ?>
                <div class="modal-header border-0 py-3 px-4 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-white bg-opacity-20 rounded-3">
                            <i class="bi bi-pencil-square fs-5 text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold fs-6 text-white mb-0">Edit Kegiatan Rutin Sekolah</h5>
                            <small class="text-white text-opacity-75 fs-9">Perbarui konfigurasi durasi, slot terkunci & penanggung jawab</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light-subtle">

                    <!-- SECTION 1: INFORMASI UTAMA -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="fw-bold text-primary small mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-info-circle-fill"></i> 1. Informasi Utama Kegiatan
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted mb-1">Nama Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="editActivityName" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Kode Kegiatan <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="editActivityCode" class="form-control rounded-3 font-monospace text-uppercase" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Tipe Kegiatan <span class="text-danger">*</span></label>
                                <select name="activity_type" id="editActivityType" class="form-select rounded-3" required>
                                    <option value="CEREMONY">Upacara / Apel / Bendera</option>
                                    <option value="WORSHIP">Kerohanian (Chapel, Worship, Doa 777, SID)</option>
                                    <option value="BREAK">Istirahat / Makan</option>
                                    <option value="FIXED_ROUTINE">Rutin Tetap Lainnya</option>
                                    <option value="FLEXIBLE_ROUTINE">Rutin Fleksibel</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Unit Sekolah</label>
                                <select name="unit_id" id="editActivityUnitId" class="form-select rounded-3">
                                    <?php if (is_super_admin()): ?><option value="">-- Berlaku untuk Semua Unit --</option><?php endif; ?>
                                    <?php foreach ($units as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: DURASI & WAKTU -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="fw-bold text-primary small mb-2 d-flex align-items-center gap-1.5">
                            <i class="bi bi-clock-history"></i> 2. Pengaturan Durasi & Sistem Waktu
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Mode Durasi Waktu <span class="text-danger">*</span></label>
                                <select name="duration_mode" id="editDurationMode" class="form-select rounded-3">
                                    <option value="STANDARD_JP">📚 Standar Jam Pelajaran (JP)</option>
                                    <option value="CUSTOM_MINUTES">⏱️ Durasi Menit Spesifik (Kustom)</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="editJpBox">
                                <label class="form-label small fw-bold text-muted mb-1">Durasi JP (Jumlah Jam) <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0.5" name="default_duration_jp" id="editActivityDuration" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-6" id="editMinutesBox" style="display: none;">
                                <label class="form-label small fw-bold text-muted mb-1">Durasi Menit Spesifik (Menit)</label>
                                <input type="number" min="5" max="180" name="duration_minutes" id="editDurationMinutes" class="form-control rounded-3" placeholder="Contoh: 15 / 10">
                                <div class="form-text fs-9">Misal: Istirahat 15 Menit, Apel 10 Menit.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: FIXED SLOT & PLACEMENT ZONES -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                <i class="bi bi-lock-fill text-danger"></i> 3. Penguncian Grid Jadwal & Zona Posisi (Placement Engine)
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_locked_slot" id="editLockedSlot" value="1">
                                <label class="form-check-label fw-bold text-danger fs-8" for="editLockedSlot">Aktifkan Fixed Slot</label>
                            </div>
                        </div>
                        <div class="row g-3 pt-1">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Hari Terkunci</label>
                                <select name="default_day" id="editDefaultDay" class="form-select rounded-3">
                                    <option value="ALL_DAYS">📅 Setiap Hari (Senin - Jumat)</option>
                                    <option value="MONDAY">Senin</option>
                                    <option value="TUESDAY">Selasa</option>
                                    <option value="WEDNESDAY">Rabu</option>
                                    <option value="THURSDAY">Kamis</option>
                                    <option value="FRIDAY">Jumat</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Zona Penempatan Grid</label>
                                <select name="placement_zone" id="editPlacementZone" class="form-select rounded-3">
                                    <option value="PRE_ACADEMIC">🌅 Pra-Akademik (Sebelum Jam ke-1 / Slot #)</option>
                                    <option value="ACADEMIC_JP">📚 Jam Pelajaran Akademik (JP 1, 2, 3...)</option>
                                    <option value="INTERMISSION_BREAK">☕ Sela Jam Pelajaran / Istirahat (Slot #)</option>
                                    <option value="POST_ACADEMIC">🌇 Pasca-Akademik (Setelah Jam Akhir / Slot #)</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="editSeqJpBox">
                                <label class="form-label small fw-bold text-muted mb-1" id="editSeqLabel">Jam ke-N Terkunci</label>
                                <select name="locked_period_start" id="editLockedPeriodStart" class="form-select rounded-3">
                                    <option value="1">Jam ke-1</option>
                                    <option value="2">Jam ke-2</option>
                                    <option value="3">Jam ke-3</option>
                                    <option value="4">Jam ke-4</option>
                                    <option value="5">Jam ke-5</option>
                                    <option value="6">Jam ke-6</option>
                                    <option value="7">Jam ke-7</option>
                                    <option value="8">Jam ke-8</option>
                                    <option value="9">Jam ke-9</option>
                                    <option value="10">Jam ke-10</option>
                                </select>
                                <input type="hidden" name="placement_sequence" id="editPlacementSequence" value="1">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: BEBAN GURU & PENANGGUNG JAWAB -->
                    <div class="card border border-light-subtle rounded-3 p-3 mb-3 bg-white shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                                <i class="bi bi-briefcase-fill text-success"></i> 4. Beban Mengajar & Penanggung Jawab
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="counts_as_teaching_load" id="editTeachingLoad" value="1">
                                <label class="form-check-label fw-bold text-success fs-8" for="editTeachingLoad">Hitung Beban Mengajar (JP)</label>
                            </div>
                        </div>
                        <div class="row g-3 pt-1">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Strategi Penanggung Jawab</label>
                                <select name="assignment_strategy" id="editAssignmentStrategy" class="form-select rounded-3">
                                    <option value="NONE">-- Tanpa Penanggung Jawab --</option>
                                    <option value="ROLE_BASED">👥 Dinamis Berdasar Rombel (Wali Kelas)</option>
                                    <option value="SPECIFIC_TEACHER">👤 Pilih Guru Spesifik Langsung</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="editRoleBox">
                                <label class="form-label small fw-bold text-muted mb-1">Jabatan / Role Rombel</label>
                                <select name="assignment_role_default" id="editRoleDefault" class="form-select rounded-3">
                                    <option value="Wali Kelas">Wali Kelas (e.g. WORKED)</option>
                                    <option value="Chaplain">Chaplain / Guru Pembina</option>
                                    <option value="Guru Pengampu">Guru Pengampu</option>
                                    <option value="Guru Piket">Guru Piket / Petugas</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="editTeacherBox" style="display: none;">
                                <label class="form-label small fw-bold text-muted mb-1">Pilih Guru Penanggung Jawab</label>
                                <select name="specific_teacher_id" id="editSpecificTeacherId" class="form-select rounded-3">
                                    <option value="">-- Pilih Guru Penanggung Jawab --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <?php $idCode = $t['employee_number'] ?? $t['nip'] ?? $t['teacher_initial'] ?? ''; ?>
                                        <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?><?= $idCode !== '' ? ' (' . esc($idCode) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: TAMPILAN GRID & CATATAN -->
                    <div class="card border border-light-subtle rounded-3 p-3 bg-white shadow-2xs">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted mb-1">Warna Label Grid Jadwal</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" name="color_label" id="editActivityColor" class="form-control form-control-color rounded-3" value="#5b5ce2">
                                    <span class="small text-muted fs-9">Pilih warna blok grid</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-muted mb-1">Keterangan / Catatan</label>
                                <textarea name="notes" id="editActivityNotes" class="form-control rounded-3" rows="1"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfTokenName = '<?= csrf_token() ?>';

    function getLiveCsrfToken() {
        const name = '<?= config('Security')->cookieName ?>' + '=';
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
        }
        const hiddenInput = document.querySelector('input[name="' + csrfTokenName + '"]');
        return hiddenInput ? hiddenInput.value : '<?= csrf_hash() ?>';
    }

    function syncCsrfTokenInDOM(newToken) {
        if (!newToken) return;
        document.querySelectorAll('input[name="' + csrfTokenName + '"]').forEach(inp => { inp.value = newToken; });
    }

    var showMessage = (options) => (typeof Swal !== 'undefined')
        ? Swal.fire(Object.assign({ confirmButtonText: 'Mengerti', buttonsStyling: false, customClass: { popup: 'rounded-4', confirmButton: 'btn btn-primary rounded-3 px-4' } }, options))
        : alert(options.title + '\n' + (options.text || ''));

    // Dynamic Placement Zone options updater
    function updatePlacementZoneUI(zoneSelect, labelEl, selectEl, seqHiddenEl) {
        if (!zoneSelect || !selectEl || !labelEl) return;
        const zone = zoneSelect.value;
        const currentVal = selectEl.value || '1';

        selectEl.innerHTML = '';
        if (zone === 'PRE_ACADEMIC') {
            labelEl.textContent = 'Urutan Pra-Akademik (#)';
            selectEl.innerHTML = `
                <option value="1">Urutan ke-1 (e.g. 06.30 - 07.00)</option>
                <option value="2">Urutan ke-2 (e.g. 07.00 - 07.20)</option>
                <option value="3">Urutan ke-3 (e.g. 07.20 - 07.30)</option>
                <option value="4">Urutan ke-4</option>
            `;
        } else if (zone === 'INTERMISSION_BREAK') {
            labelEl.textContent = 'Sela Jam ke-N (# Istirahat)';
            selectEl.innerHTML = `
                <option value="1">Setelah Jam ke-1</option>
                <option value="2">Setelah Jam ke-2</option>
                <option value="3">Setelah Jam ke-3</option>
                <option value="4">Setelah Jam ke-4</option>
                <option value="5" selected>Setelah Jam ke-5 (e.g. 10.25 - 10.40)</option>
                <option value="6">Setelah Jam ke-6</option>
                <option value="7">Setelah Jam ke-7</option>
            `;
        } else if (zone === 'POST_ACADEMIC') {
            labelEl.textContent = 'Urutan Pasca-Akademik (#)';
            selectEl.innerHTML = `
                <option value="1">Urutan ke-1 (e.g. 12.50 - 13.00 Absen Pulang)</option>
                <option value="2">Urutan ke-2 (e.g. 13.00 - 13.15 Afternoon Worship)</option>
                <option value="3">Urutan ke-3</option>
            `;
        } else {
            labelEl.textContent = 'Jam ke-N Terkunci';
            for (let i = 1; i <= 10; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = 'Jam ke-' + i;
                selectEl.appendChild(opt);
            }
        }

        if ([...selectEl.options].some(o => o.value == currentVal)) {
            selectEl.value = currentVal;
        }

        if (seqHiddenEl) seqHiddenEl.value = selectEl.value;
    }

    const createZone = document.getElementById('createPlacementZone');
    const createSeqLabel = document.getElementById('createSeqLabel');
    const createSeqSelect = document.getElementById('createLockedPeriodStart');
    const createSeqHidden = document.getElementById('createPlacementSequence');
    if (createZone && createSeqSelect) {
        createZone.addEventListener('change', () => updatePlacementZoneUI(createZone, createSeqLabel, createSeqSelect, createSeqHidden));
        createSeqSelect.addEventListener('change', () => { if (createSeqHidden) createSeqHidden.value = createSeqSelect.value; });
        updatePlacementZoneUI(createZone, createSeqLabel, createSeqSelect, createSeqHidden);
    }

    const editZone = document.getElementById('editPlacementZone');
    const editSeqLabel = document.getElementById('editSeqLabel');
    const editSeqSelect = document.getElementById('editLockedPeriodStart');
    const editSeqHidden = document.getElementById('editPlacementSequence');
    if (editZone && editSeqSelect) {
        editZone.addEventListener('change', () => updatePlacementZoneUI(editZone, editSeqLabel, editSeqSelect, editSeqHidden));
        editSeqSelect.addEventListener('change', () => { if (editSeqHidden) editSeqHidden.value = editSeqSelect.value; });
    }

    // Dynamic visibility toggles for duration mode
    function toggleDurationFields(modeSelect, jpBox, minutesBox) {
        if (!modeSelect || !jpBox || !minutesBox) return;
        const jpInput = jpBox.querySelector('input');
        const minInput = minutesBox.querySelector('input');

        if (modeSelect.value === 'CUSTOM_MINUTES') {
            jpBox.style.display = 'none';
            if (jpInput) jpInput.required = false;
            minutesBox.style.display = 'block';
            if (minInput) minInput.required = true;
        } else {
            jpBox.style.display = 'block';
            if (jpInput) jpInput.required = true;
            minutesBox.style.display = 'none';
            if (minInput) minInput.required = false;
        }
    }

    const createDurMode = document.getElementById('createDurationMode');
    const createJpBox = document.getElementById('createJpBox');
    const createMinBox = document.getElementById('createMinutesBox');
    if (createDurMode && createJpBox && createMinBox) {
        createDurMode.addEventListener('change', () => toggleDurationFields(createDurMode, createJpBox, createMinBox));
        toggleDurationFields(createDurMode, createJpBox, createMinBox);
    }

    const editDurMode = document.getElementById('editDurationMode');
    const editJpBox = document.getElementById('editJpBox');
    const editMinBox = document.getElementById('editMinutesBox');
    if (editDurMode && editJpBox && editMinBox) {
        editDurMode.addEventListener('change', () => toggleDurationFields(editDurMode, editJpBox, editMinBox));
    }

    // Dynamic visibility toggles for teacher assignment strategy
    function toggleStrategyFields(stratSelect, roleBox, teacherBox) {
        if (stratSelect.value === 'SPECIFIC_TEACHER') {
            roleBox.style.display = 'none';
            teacherBox.style.display = 'block';
        } else if (stratSelect.value === 'ROLE_BASED') {
            roleBox.style.display = 'block';
            teacherBox.style.display = 'none';
        } else {
            roleBox.style.display = 'none';
            teacherBox.style.display = 'none';
        }
    }

    const createStrat = document.getElementById('createAssignmentStrategy');
    const createRoleBox = document.getElementById('createRoleBox');
    const createTeacherBox = document.getElementById('createTeacherBox');
    if (createStrat && createRoleBox && createTeacherBox) {
        createStrat.addEventListener('change', () => toggleStrategyFields(createStrat, createRoleBox, createTeacherBox));
    }

    const editStrat = document.getElementById('editAssignmentStrategy');
    const editRoleBox = document.getElementById('editRoleBox');
    const editTeacherBox = document.getElementById('editTeacherBox');
    if (editStrat && editRoleBox && editTeacherBox) {
        editStrat.addEventListener('change', () => toggleStrategyFields(editStrat, editRoleBox, editTeacherBox));
    }

    // Edit Button Click Handler
    document.querySelectorAll('.btn-edit-activity').forEach(btn => {
        btn.addEventListener('click', function() {
            const id                = this.getAttribute('data-id');
            const unitId            = this.getAttribute('data-unit-id');
            const code              = this.getAttribute('data-code');
            const name              = this.getAttribute('data-name');
            const type              = this.getAttribute('data-activity-type');
            const durationMode      = this.getAttribute('data-duration-mode') || 'STANDARD_JP';
            const durationJp        = this.getAttribute('data-duration-jp');
            const durationMinutes   = this.getAttribute('data-duration-minutes');
            const color             = this.getAttribute('data-color');
            const locked            = this.getAttribute('data-locked');
            const day               = this.getAttribute('data-day');
            const periodStart       = this.getAttribute('data-period-start');
            const placementZone     = this.getAttribute('data-placement-zone') || 'ACADEMIC_JP';
            const placementSequence = this.getAttribute('data-placement-sequence') || '1';
            const teachingLoad      = this.getAttribute('data-teaching-load');
            const strategy          = this.getAttribute('data-strategy') || 'NONE';
            const roleDefault       = this.getAttribute('data-role-default');
            const specificTeacher   = this.getAttribute('data-specific-teacher');
            const notes             = this.getAttribute('data-notes');

            const form = document.getElementById('editActivityForm');
            if (form) form.action = '<?= base_url('routine-activities/') ?>' + id;

            if (document.getElementById('editActivityName')) document.getElementById('editActivityName').value = name;
            if (document.getElementById('editActivityCode')) document.getElementById('editActivityCode').value = code;
            if (document.getElementById('editActivityType')) document.getElementById('editActivityType').value = type;
            if (document.getElementById('editDurationMode')) document.getElementById('editDurationMode').value = durationMode;
            if (document.getElementById('editActivityDuration')) document.getElementById('editActivityDuration').value = durationJp;
            if (document.getElementById('editDurationMinutes')) document.getElementById('editDurationMinutes').value = durationMinutes || '';
            if (document.getElementById('editActivityUnitId')) document.getElementById('editActivityUnitId').value = unitId || '';
            if (document.getElementById('editActivityColor')) document.getElementById('editActivityColor').value = color || '#5b5ce2';
            if (document.getElementById('editLockedSlot')) document.getElementById('editLockedSlot').checked = parseInt(locked) === 1;
            if (document.getElementById('editDefaultDay')) document.getElementById('editDefaultDay').value = day || 'ALL_DAYS';

            if (document.getElementById('editPlacementZone')) {
                document.getElementById('editPlacementZone').value = placementZone;
                updatePlacementZoneUI(editZone, editSeqLabel, editSeqSelect, editSeqHidden);
            }
            if (document.getElementById('editLockedPeriodStart')) document.getElementById('editLockedPeriodStart').value = (placementZone === 'ACADEMIC_JP' ? periodStart : placementSequence) || '1';
            if (document.getElementById('editPlacementSequence')) document.getElementById('editPlacementSequence').value = placementSequence || '1';

            if (document.getElementById('editTeachingLoad')) document.getElementById('editTeachingLoad').checked = parseInt(teachingLoad) === 1;
            if (document.getElementById('editAssignmentStrategy')) document.getElementById('editAssignmentStrategy').value = strategy;
            if (document.getElementById('editRoleDefault')) document.getElementById('editRoleDefault').value = roleDefault || 'Wali Kelas';
            if (document.getElementById('editSpecificTeacherId')) document.getElementById('editSpecificTeacherId').value = specificTeacher || '';
            if (document.getElementById('editActivityNotes')) document.getElementById('editActivityNotes').value = notes || '';

            if (editDurMode && editJpBox && editMinBox) toggleDurationFields(editDurMode, editJpBox, editMinBox);
            if (editStrat && editRoleBox && editTeacherBox) toggleStrategyFields(editStrat, editRoleBox, editTeacherBox);
        });
    });

    // Handle Form Submit via AJAX
    ['createActivityForm', 'editActivityForm'].forEach(formId => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

            const formData = new FormData(form);
            const token = getLiveCsrfToken();
            formData.set(csrfTokenName, token);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': token
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.csrf_hash) syncCsrfTokenInDOM(data.csrf_hash);

                if (data.status === 'success') {
                    const modalEl = form.closest('.modal');
                    if (modalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }

                    showMessage({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    let errMsg = data.message || 'Gagal menyimpan data.';
                    if (data.errors) errMsg += '\n' + Object.values(data.errors).join('\n');
                    showMessage({ icon: 'error', title: 'Gagal!', text: errMsg });
                }
            })
            .catch(err => {
                console.error(err);
                showMessage({ icon: 'error', title: 'Kesalahan Sistem', text: 'Terjadi kesalahan koneksi atau server.' });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    });

    // Delete Button Handler
    document.querySelectorAll('.btn-delete-activity').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Kegiatan Rutin?',
                    html: `Apakah Anda yakin ingin menghapus <strong>"${name}"</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'rounded-4',
                        confirmButton: 'btn btn-danger rounded-3 px-4 me-2',
                        cancelButton: 'btn btn-light rounded-3 px-4'
                    },
                    buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) executeDelete(id);
                });
            } else if (confirm(`Apakah Anda yakin ingin menghapus "${name}"?`)) {
                executeDelete(id);
            }
        });
    });

    function executeDelete(id) {
        const formData = new FormData();
        const token = getLiveCsrfToken();
        formData.set(csrfTokenName, token);

        fetch('<?= base_url('routine-activities/') ?>' + id + '/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': token
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.csrf_hash) syncCsrfTokenInDOM(data.csrf_hash);

            if (data.status === 'success') {
                showMessage({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                showMessage({ icon: 'error', title: 'Gagal!', text: data.message });
            }
        })
        .catch(err => {
            console.error(err);
            showMessage({ icon: 'error', title: 'Kesalahan', text: 'Gagal menghapus data.' });
        });
    }
});
</script>
<?= $this->endSection() ?>
