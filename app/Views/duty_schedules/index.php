<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<style>
    .duty-day-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .duty-day-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.08) !important;
    }
    .teacher-item-card {
        transition: all 0.2s ease;
        border: 1px solid #edf2f7;
    }
    .teacher-item-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background-color: #fafafa !important;
    }
    .avatar-initial {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .day-header-senin { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); }
    .day-header-selasa { background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); }
    .day-header-rabu { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); }
    .day-header-kamis { background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); }
    .day-header-jumat { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
</style>

<!-- HEADER PAGE & TOP TOOLBAR -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-dark">Jadwal Piket Guru Sekolah</h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fs-9 fw-semibold">
                Satu Atap (SMP & SMA)
            </span>
        </div>
        <p class="text-muted fs-8 mb-0">
            Pengelolaan & Optimasi Roster Guru Piket Harian · Periode Akademik <strong><?= esc($selectedYear['name'] ?? '-') ?></strong>
        </p>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="get" action="<?= base_url('duty-schedules') ?>" class="d-inline-flex">
            <select name="academic_year_id" class="form-select form-select-sm rounded-3 fw-semibold border-secondary-subtle bg-white shadow-2xs" onchange="this.form.submit()">
                <?php foreach ($years as $yr): ?>
                    <option value="<?= $yr['id'] ?>" <?= (int)$yr['id'] === (int)$selectedYear['id'] ? 'selected' : '' ?>>
                        T.A. <?= esc($yr['name']) ?> <?= (int)($yr['is_active'] ?? 0) === 1 ? '(Aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= base_url('duty-schedules/print?academic_year_id=' . $selectedYear['id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-3 bg-white shadow-2xs">
            <i data-lucide="printer" class="me-1.5" style="width:15px;height:15px;"></i> Cetak Dokumen
        </a>

        <?php if (has_permission('duty_schedules.manage') || has_permission('schedules.manage') || is_super_admin()): ?>
            <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#generateModal">
                <i data-lucide="sparkles" class="me-1.5" style="width:15px;height:15px;"></i> Generasi Otomatis
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 bg-white shadow-2xs" data-bs-toggle="modal" data-bs-target="#addManualModal">
                <i data-lucide="plus" class="me-1" style="width:15px;height:15px;"></i> Tambah Manual
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- KPI SUMMARY STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-2xs rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2.5 bg-indigo-subtle text-indigo rounded-3">
                    <i data-lucide="shield-check" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <span class="d-block text-muted fs-9 fw-semibold text-uppercase tracking-wider">Total Penugasan</span>
                    <h5 class="fw-bold mb-0 text-dark"><?= $totalDutyAssignments ?> <span class="fs-8 fw-normal text-muted">Guru</span></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-2xs rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2.5 bg-blue-subtle text-blue rounded-3">
                    <i data-lucide="building-2" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <span class="d-block text-muted fs-9 fw-semibold text-uppercase tracking-wider">Cakupan Unit</span>
                    <h5 class="fw-bold mb-0 text-dark">SMP & SMA</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-2xs rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2.5 bg-emerald-subtle text-emerald rounded-3">
                    <i data-lucide="users" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <span class="d-block text-muted fs-9 fw-semibold text-uppercase tracking-wider">Guru Terdaftar</span>
                    <h5 class="fw-bold mb-0 text-dark"><?= count($teachers) ?> <span class="fs-8 fw-normal text-muted">Guru Aktif</span></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-2xs rounded-4 bg-white p-3 h-100">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2.5 bg-amber-subtle text-amber rounded-3">
                    <i data-lucide="sparkles" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <span class="d-block text-muted fs-9 fw-semibold text-uppercase tracking-wider">Prioritas Sistem</span>
                    <h5 class="fw-bold mb-0 text-dark fs-7">0 JP (Jam Kosong)</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- INFORMASI ATURAN KHUSUS & BEBAN MENGAJAR BANNER -->
<div class="card border-0 shadow-2xs rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-body p-3.5">
        <div class="d-flex align-items-start gap-3">
            <div class="p-2.5 bg-primary bg-opacity-10 text-primary rounded-3 mt-0.5">
                <i data-lucide="info" style="width:20px;height:20px;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h6 class="fw-bold mb-0 text-slate-800 fs-7">Ketentuan & Algoritma Penjadwalan Piket (Sekolah Satu Atap)</h6>
                    <span class="badge text-bg-light border text-secondary fs-9 rounded-pill">Aturan Otomatis Terintegrasi</span>
                </div>
                <div class="row g-3 fs-8 text-secondary">
                    <div class="col-md-6">
                        <div class="p-2.5 bg-light rounded-3 border border-light-subtle">
                            <strong class="d-block text-dark mb-1"><i data-lucide="user-check" class="me-1 inline-block" style="width:14px;height:14px;"></i> Catatan Khusus Hari Guru:</strong>
                            <div class="d-flex flex-wrap gap-2 mt-1.5">
                                <span class="badge bg-white border text-dark fs-9">
                                    <strong class="text-primary">Anike Wetipo</strong>: Senin, Selasa, Jumat
                                </span>
                                <span class="badge bg-white border text-dark fs-9">
                                    <strong class="text-primary">Arike Siep</strong>: Senin, Kamis, Jumat
                                </span>
                                <span class="badge bg-white border text-dark fs-9">
                                    <strong class="text-primary">Natalia Tabuni</strong>: Selasa, Rabu
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2.5 bg-light rounded-3 border border-light-subtle h-100">
                            <strong class="d-block text-dark mb-1"><i data-lucide="cpu" class="me-1 inline-block" style="width:14px;height:14px;"></i> Logika Optimasi Beban:</strong>
                            <p class="mb-0 fs-9 leading-relaxed text-muted">
                                Sistem menghitung jam mengajar (JP) guru secara real-time dari jadwal SMP & SMA. Guru dengan <span class="badge text-bg-success-subtle text-success fw-bold">0 JP (Jam Kosong)</span> atau JP terendah pada hari tersebut diprioritaskan bertugas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MATRIKS 5 HARI (SENIN S/D JUMAT) IN A 5-COLUMN HORIZONTAL GRID -->
<div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <?php
    $dayHeaderClasses = [
        1 => 'day-header-senin',
        2 => 'day-header-selasa',
        3 => 'day-header-rabu',
        4 => 'day-header-kamis',
        5 => 'day-header-jumat',
    ];
    $avatarBgColors = ['#e0e7ff', '#bae6fd', '#ccfbf1', '#f3e8ff', '#dcfce7'];
    $avatarTextColors = ['#3730a3', '#0369a1', '#0f766e', '#6b21a8', '#166534'];
    ?>

    <?php foreach ($matrix as $dayNum => $dayData): ?>
        <?php
        $headerClass = $dayHeaderClasses[$dayNum] ?? 'bg-primary';
        $dutyCount = count($dayData['duties']);
        ?>
        <div class="col">
            <div class="card border-0 shadow-2xs rounded-4 h-100 duty-day-card bg-white overflow-hidden">
                <!-- DAY CARD HEADER -->
                <div class="card-header border-0 py-3 px-3 <?= $headerClass ?> text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-1.5">
                        <i data-lucide="calendar" style="width:16px;height:16px;"></i>
                        <h6 class="fw-bold mb-0 fs-7">Hari <?= esc($dayData['day_name']) ?></h6>
                    </div>
                    <span class="badge bg-white bg-opacity-25 text-white rounded-pill fs-9 fw-bold px-2 py-0.5 backdrop-blur">
                        <?= $dutyCount ?> Guru
                    </span>
                </div>

                <!-- DAY CARD BODY -->
                <div class="card-body p-2.5">
                    <?php if (empty($dayData['duties'])): ?>
                        <div class="text-center py-5 text-muted">
                            <div class="p-3 bg-light rounded-circle d-inline-block mb-2">
                                <i data-lucide="user-minus" class="text-secondary opacity-50" style="width:22px;height:22px;"></i>
                            </div>
                            <small class="d-block text-muted fs-8 font-semibold">Belum Ada Guru Piket</small>
                            <small class="text-muted fs-9">Klik Tambah Manual</small>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($dayData['duties'] as $idx => $duty): ?>
                                <?php
                                // Generate initials
                                $words = explode(' ', trim($duty['teacher_name']));
                                $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                $colorIdx = $idx % count($avatarBgColors);
                                $bgColor = $avatarBgColors[$colorIdx];
                                $textColor = $avatarTextColors[$colorIdx];
                                $isConstrained = in_array(mb_strtolower(trim($duty['teacher_name'])), ['anike wetipo', 'arike siep', 'natalia tabuni'], true) || count($duty['allowed_days'] ?? []) < 5;
                                ?>
                                <div class="p-2.5 rounded-3 bg-white teacher-item-card position-relative">
                                    <div class="d-flex align-items-start gap-2">
                                        <!-- AVATAR INITIAL -->
                                        <div class="avatar-initial" style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;">
                                            <?= esc($initials) ?>
                                        </div>

                                        <div class="flex-grow-1 min-w-0">
                                            <strong class="fs-8 text-dark d-block lh-sm text-truncate" title="<?= esc($duty['teacher_name']) ?>">
                                                <?= esc($duty['teacher_name']) ?>
                                            </strong>
                                            <small class="text-muted fs-9 font-monospace d-block">
                                                <?= esc($duty['teacher_code'] ?: $duty['nip'] ?: 'NIP -') ?>
                                            </small>
                                        </div>

                                        <?php if (has_permission('duty_schedules.manage') || has_permission('schedules.manage') || is_super_admin()): ?>
                                            <form method="post" action="<?= base_url('duty-schedules/' . $duty['id'] . '/delete?academic_year_id=' . $selectedYear['id']) ?>" onsubmit="return confirm('Hapus tugas piket <?= esc($duty['teacher_name']) ?> hari <?= esc($dayData['day_name']) ?>?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-link text-danger-subtle hover-text-danger p-0 border-0 fs-8" title="Hapus Penugasan">
                                                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <!-- BADGES SECTION -->
                                    <div class="mt-2.5 d-flex flex-wrap align-items-center gap-1.5 fs-9">
                                        <?php if ($duty['teaching_load_today'] == 0): ?>
                                            <span class="badge bg-emerald-subtle text-emerald border border-emerald-subtle rounded-pill px-2 py-0.5 fw-semibold" title="Beban mengajar hari ini 0 JP">
                                                <i data-lucide="check-circle" style="width:10px;height:10px;" class="me-0.5"></i> 0 JP (Kosong)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-blue-subtle text-blue border border-blue-subtle rounded-pill px-2 py-0.5 fw-semibold">
                                                <i data-lucide="zap" style="width:10px;height:10px;" class="me-0.5"></i> <?= esc($duty['teaching_load_today']) ?> JP Mengajar
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($isConstrained): ?>
                                            <span class="badge bg-amber-subtle text-amber border border-amber-subtle rounded-pill px-1.5 py-0.5" title="Guru dengan kriteria hari khusus">
                                                ★ Khusus
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($duty['notes'])): ?>
                                        <div class="mt-1.5 pt-1.5 border-top border-light-subtle">
                                            <small class="text-muted fs-9 d-block text-truncate" title="<?= esc($duty['notes']) ?>">
                                                <i data-lucide="message-square" style="width:10px;height:10px;" class="me-0.5 text-secondary"></i> <?= esc($duty['notes']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- GURU YANG BELUM DIMASUKKAN DALAM JADWAL PIKET -->
<div class="card border-0 shadow-2xs rounded-4 bg-white mb-4 overflow-hidden">
    <div class="card-header bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3">
                <i data-lucide="user-x" style="width:18px;height:18px;"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark fs-7">Guru Belum Terdaftar Piket</h6>
                <small class="text-muted fs-8">Daftar guru aktif yang belum dimasukkan ke jadwal piket minggu ini</small>
            </div>
        </div>
        <span class="badge <?= empty($unassignedTeachers) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning border border-warning-subtle' ?> rounded-pill px-3 py-1 fs-8 fw-semibold">
            <?= count($unassignedTeachers ?? []) ?> Guru Belum Dijadwalkan
        </span>
    </div>

    <div class="card-body p-4">
        <?php if (empty($unassignedTeachers)): ?>
            <div class="text-center py-4 text-emerald">
                <div class="p-3 bg-emerald-subtle text-emerald rounded-circle d-inline-block mb-2">
                    <i data-lucide="check-circle-2" style="width:28px;height:28px;"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark fs-7">Seluruh Guru Telah Terdaftar Piket!</h6>
                <p class="text-muted fs-8 mb-0">Semua guru aktif dalam sistem telah berhasil dialokasikan pada jadwal piket T.A. <?= esc($selectedYear['name']) ?>.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                <?php foreach ($unassignedTeachers as $uTch): ?>
                    <?php
                    $uWords = explode(' ', trim($uTch['full_name']));
                    $uInitials = strtoupper(substr($uWords[0], 0, 1) . (isset($uWords[1]) ? substr($uWords[1], 0, 1) : ''));
                    $service = new \App\Services\TeacherDutyScheduleService();
                    $allowedDays = $service->getTeacherAllowedDays($uTch['full_name']);
                    $allowedNames = array_map(fn($d) => \App\Services\TeacherDutyScheduleService::DAYS_MAP[$d], $allowedDays);
                    ?>
                    <div class="col">
                        <div class="p-3 rounded-3 border bg-white d-flex align-items-center justify-content-between gap-3 hover-shadow transition">
                            <div class="d-flex align-items-center gap-2.5 min-w-0">
                                <div class="avatar-initial bg-secondary-subtle text-secondary">
                                    <?= esc($uInitials) ?>
                                </div>
                                <div class="min-w-0">
                                    <strong class="fs-8 text-dark d-block lh-sm text-truncate" title="<?= esc($uTch['full_name']) ?>">
                                        <?= esc($uTch['full_name']) ?>
                                    </strong>
                                    <small class="text-muted fs-9 font-monospace d-block">
                                        <?= esc($uTch['code'] ?: $uTch['nip'] ?: 'NIP -') ?>
                                    </small>
                                    <small class="text-primary fs-9 d-block mt-0.5" title="Hari yang diperbolehkan piket">
                                        <i data-lucide="clock" style="width:10px;height:10px;" class="me-0.5"></i> Allowed: <?= implode(', ', $allowedNames) ?>
                                    </small>
                                </div>
                            </div>

                            <?php if (has_permission('duty_schedules.manage') || has_permission('schedules.manage') || is_super_admin()): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 fs-9 fw-semibold px-2.5 py-1 text-nowrap flex-shrink-0" onclick="openAddManualForTeacher(<?= $uTch['id'] ?>)">
                                    <i data-lucide="plus" style="width:12px;height:12px;" class="me-0.5"></i> Tambah
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- GURU DENGAN TUGAS PIKET GANDA (DOUBLE > 1 HARI) -->
<div class="card border-0 shadow-2xs rounded-4 bg-white mb-4 overflow-hidden">
    <div class="card-header bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-purple bg-opacity-10 text-purple rounded-3">
                <i data-lucide="layers" style="width:18px;height:18px;"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark fs-7">Guru Tugas Piket Ganda (Double / Multi-Hari)</h6>
                <small class="text-muted fs-8">Daftar guru yang dijadwalkan piket lebih dari 1 kali (2x atau lebih) dalam seminggu</small>
            </div>
        </div>
        <span class="badge <?= empty($multipleDutyTeachers) ? 'bg-secondary-subtle text-secondary' : 'bg-purple-subtle text-purple border border-purple-subtle' ?> rounded-pill px-3 py-1 fs-8 fw-semibold">
            <?= count($multipleDutyTeachers ?? []) ?> Guru Piket Ganda
        </span>
    </div>

    <div class="card-body p-4">
        <?php if (empty($multipleDutyTeachers)): ?>
            <div class="text-center py-4 text-muted">
                <div class="p-3 bg-light rounded-circle d-inline-block mb-2">
                    <i data-lucide="check-circle" style="width:28px;height:28px;" class="text-secondary opacity-50"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark fs-7">Tidak Ada Tugas Piket Ganda</h6>
                <p class="text-muted fs-8 mb-0">Semua guru piket terdaftar tepat 1 kali pada minggu akademik T.A. <?= esc($selectedYear['name']) ?>.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                <?php foreach ($multipleDutyTeachers as $mItem): ?>
                    <?php
                    $mWords = explode(' ', trim($mItem['teacher_name']));
                    $mInitials = strtoupper(substr($mWords[0], 0, 1) . (isset($mWords[1]) ? substr($mWords[1], 0, 1) : ''));
                    ?>
                    <div class="col">
                        <div class="p-3 rounded-3 border bg-white d-flex align-items-center justify-content-between gap-3 hover-shadow transition">
                            <div class="d-flex align-items-center gap-2.5 min-w-0">
                                <div class="avatar-initial bg-purple-subtle text-purple">
                                    <?= esc($mInitials) ?>
                                </div>
                                <div class="min-w-0">
                                    <strong class="fs-8 text-dark d-block lh-sm text-truncate" title="<?= esc($mItem['teacher_name']) ?>">
                                        <?= esc($mItem['teacher_name']) ?>
                                    </strong>
                                    <small class="text-muted fs-9 font-monospace d-block">
                                         <?= esc(($mItem['teacher_code'] ?? '') ?: 'NIP -') ?>
                                    </small>
                                    <small class="text-purple fs-9 fw-semibold d-block mt-0.5" title="Hari bertugas piket">
                                        <i data-lucide="calendar" style="width:10px;height:10px;" class="me-0.5"></i> <?= esc(implode(', ', $mItem['days'])) ?>
                                    </small>
                                </div>
                            </div>

                            <span class="badge bg-purple text-white rounded-pill px-2.5 py-1 fs-9 fw-bold flex-shrink-0" title="Total penugasan piket minggu ini">
                                <?= $mItem['count'] ?>x Piket
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER ACTION: KOSONGKAN JADWAL -->
<?php if (has_permission('duty_schedules.manage') || has_permission('schedules.manage') || is_super_admin()): ?>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top border-light-subtle mb-4">
        <small class="text-muted fs-8">
            <i data-lucide="shield" class="me-1 inline-block" style="width:14px;height:14px;"></i> Terintegrasi dengan Sistem Penugasan Mengajar & Jadwal Pelajaran IALOS Education
        </small>

        <form method="post" action="<?= base_url('duty-schedules/clear') ?>" onsubmit="return confirm('Apakah Anda yakin ingin MENGOSONGKAN SELURUH Jadwal Piket T.A. <?= esc($selectedYear['name']) ?>?')">
            <?= csrf_field() ?>
            <input type="hidden" name="academic_year_id" value="<?= $selectedYear['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 px-3">
                <i data-lucide="trash-2" class="me-1" style="width:14px;height:14px;"></i> Kosongkan Seluruh Jadwal Piket
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- MODAL GENERASI OTOMATIS -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="post" action="<?= base_url('duty-schedules/generate') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="academic_year_id" value="<?= $selectedYear['id'] ?>">

                <div class="modal-header border-0 bg-primary text-white p-4">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-1 d-flex align-items-center gap-2">
                            <i data-lucide="sparkles" style="width:20px;height:20px;"></i> Generasi Otomatis Jadwal Piket
                        </h5>
                        <p class="mb-0 fs-8 text-white-50">Tahun Pelajaran <?= esc($selectedYear['name']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-secondary fs-8 mb-3 leading-relaxed">
                        Algoritma cerdas akan mengkalkulasi beban mengajar guru (0 JP / jam kosong & JP terendah) dari jadwal SMP & SMA serta menerapkan aturan khusus hari guru secara otomatis.
                    </p>

                    <div class="mb-3.5">
                        <label class="form-label fw-semibold fs-8 text-dark d-block">Metode Penentuan Kuota Harian</label>

                        <div class="form-check p-3 rounded-3 border mb-2 bg-light-subtle" onclick="document.getElementById('modeAuto').click();">
                            <input class="form-check-input mt-0.5" type="radio" name="quota_mode" id="modeAuto" value="auto" checked onchange="toggleQuotaInput()">
                            <label class="form-check-label ms-1" for="modeAuto">
                                <strong class="d-block text-dark fs-8">Otomatis Proporsional (Rekomendasi)</strong>
                                <small class="text-muted fs-9 d-block mt-0.5 leading-relaxed">
                                    Sistem otomatis menghitung kuota ideal per hari dari total guru aktif & hari sekolah (kurikulum) agar seluruh guru teralokasi secara adil.
                                </small>
                            </label>
                        </div>

                        <div class="form-check p-3 rounded-3 border bg-light-subtle" onclick="document.getElementById('modeManual').click();">
                            <input class="form-check-input mt-0.5" type="radio" name="quota_mode" id="modeManual" value="manual" onchange="toggleQuotaInput()">
                            <label class="form-check-label ms-1" for="modeManual">
                                <strong class="d-block text-dark fs-8">Kuota Manual per Hari</strong>
                                <small class="text-muted fs-9 d-block mt-0.5 leading-relaxed">
                                    Tentukan sendiri jumlah target guru piket per hari secara tetap.
                                </small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3.5 d-none" id="manualQuotaContainer">
                        <label class="form-label fw-semibold fs-8 text-dark">Target Kuota Guru Piket per Hari</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-secondary-subtle"><i data-lucide="users" style="width:16px;height:16px;"></i></span>
                            <input type="number" name="quota_per_day" class="form-control rounded-end-3" value="3" min="1" max="10">
                        </div>
                        <small class="text-muted fs-9 mt-1 d-block">Standar 3 - 5 guru per hari (Senin s/d Jumat).</small>
                    </div>

                    <div class="mb-3.5">
                        <label class="form-label fw-semibold fs-8 text-dark d-block">Filter Jabatan yang Diikutsertakan Piket</label>
                        <p class="text-muted fs-9 mb-2">Centang jabatan yang <strong>BOLEH</strong> bertugas piket. Jabatan yang <strong>TIDAK DICENTANG</strong> akan otomatis dikecualikan dari jadwal piket.</p>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 border bg-light-subtle d-flex align-items-center">
                                    <input class="form-check-input me-2 mt-0" type="checkbox" name="included_positions[]" value="HEADMASTER" id="posHeadmaster">
                                    <label class="form-check-label fs-8 text-dark fw-semibold cursor-pointer" for="posHeadmaster">
                                        Kepala Sekolah
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 border bg-light-subtle d-flex align-items-center">
                                    <input class="form-check-input me-2 mt-0" type="checkbox" name="included_positions[]" value="VICE_PRINCIPAL" id="posVice" checked>
                                    <label class="form-check-label fs-8 text-dark fw-semibold cursor-pointer" for="posVice">
                                        Wakil Kepala Sekolah
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 border bg-light-subtle d-flex align-items-center">
                                    <input class="form-check-input me-2 mt-0" type="checkbox" name="included_positions[]" value="CHAPLAIN" id="posChaplain">
                                    <label class="form-check-label fs-8 text-dark fw-semibold cursor-pointer" for="posChaplain">
                                        Chaplain / BK
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded-3 border bg-light-subtle d-flex align-items-center">
                                    <input class="form-check-input me-2 mt-0" type="checkbox" name="included_positions[]" value="HOMEROOM_TEACHER" id="posHomeroom" checked>
                                    <label class="form-check-label fs-8 text-dark fw-semibold cursor-pointer" for="posHomeroom">
                                        Wali Kelas
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert bg-amber-subtle text-amber border border-amber-subtle rounded-3 fs-8 p-3 mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i data-lucide="alert-triangle" style="width:16px;height:16px;" class="mt-0.5 flex-shrink-0"></i>
                            <div>
                                <strong>Perhatian:</strong> Generasi otomatis akan memperbarui & menggantikan penugasan piket yang ada saat ini untuk T.A. <?= esc($selectedYear['name']) ?>.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 btn-sm px-4 fw-semibold">
                        <i data-lucide="sparkles" class="me-1.5" style="width:14px;height:14px;"></i> Jalankan Generasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH MANUAL -->
<div class="modal fade" id="addManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="post" action="<?= base_url('duty-schedules/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="academic_year_id" value="<?= $selectedYear['id'] ?>">

                <div class="modal-header border-0 bg-dark text-white p-4">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-1 d-flex align-items-center gap-2">
                            <i data-lucide="plus-circle" style="width:20px;height:20px;"></i> Tambah Guru Piket Manual
                        </h5>
                        <p class="mb-0 fs-8 text-white-50">Tahun Pelajaran <?= esc($selectedYear['name']) ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-8 text-dark">Pilih Guru</label>
                        <select name="teacher_id" class="form-select rounded-3 fs-8" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($teachers as $tch): ?>
                                <option value="<?= $tch['id'] ?>"><?= esc($tch['full_name']) ?> <?= $tch['code'] ? '(' . esc($tch['code']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-8 text-dark">Pilih Hari Tugas</label>
                        <select name="day_of_week" class="form-select rounded-3 fs-8" required>
                            <option value="">-- Pilih Hari --</option>
                            <?php foreach ($daysMap as $dNum => $dName): ?>
                                <option value="<?= $dNum ?>">Hari <?= esc($dName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-8 text-dark">Peran / Tugas</label>
                        <input type="text" name="duty_role" class="form-control rounded-3 fs-8" value="GURU_PIKET" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold fs-8 text-dark">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" class="form-control rounded-3 fs-8" placeholder="Contoh: Koordinator Piket Pagi">
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-3 btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 btn-sm px-4 fw-semibold">
                        <i data-lucide="check" class="me-1.5" style="width:14px;height:14px;"></i> Simpan Penugasan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleQuotaInput() {
    const modeManual = document.getElementById('modeManual');
    const container = document.getElementById('manualQuotaContainer');
    if (modeManual && container) {
        if (modeManual.checked) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }
}

function openAddManualForTeacher(teacherId) {
    const modalEl = document.getElementById('addManualModal');
    if (!modalEl) return;
    const selectEl = modalEl.querySelector('select[name="teacher_id"]');
    if (selectEl) {
        selectEl.value = teacherId;
    }
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();
}
</script>
<?= $this->endSection() ?>
