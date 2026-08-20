<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$scopeTitles = [
    'teacher'   => 'Jadwal Mengajar Saya',
    'classroom' => 'Jadwal Berdasarkan Kelas',
    'grade'     => 'Jadwal Berdasarkan Jenjang',
    'all'       => 'Semua Jadwal Unit',
];
$scopeTitle = $scopeTitles[$scope] ?? $scopeTitles['teacher'];
$scheduleVersions = $scheduleVersions ?? ($scheduleVersion ? [$scheduleVersion] : []);
$previewVersions = array_values(array_filter($scheduleVersions, static fn (array $version): bool => !in_array(strtoupper((string) $version['workflow_status']), ['APPROVED', 'LOCKED'], true)));
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="calendar-days" class="text-primary" style="width: 24px; height: 24px;"></i>
                    <?= esc($scopeTitle) ?>
                </h4>
                <p class="text-muted fs-7 mb-0">
                    Portal Guru &mdash; <?= esc($teacherInfo['full_name'] ?? session()->get('full_name')) ?> &middot; <?= esc($unitScope['label'] ?? 'Unit aktif') ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 no-print">
                <?php if (!empty($isManagement) && !empty($teachersList)): ?>
                    <form method="get" action="<?= base_url('portal/schedule') ?>" class="d-inline-flex align-items-center gap-1 me-2">
                        <input type="hidden" name="scope" value="<?= esc($scope) ?>">
                        <label class="fs-8 fw-semibold text-muted text-nowrap d-none d-md-inline" for="supervisionTeacherSelect">Guru:</label>
                        <select id="supervisionTeacherSelect" name="teacher_id" class="form-select form-select-sm rounded-3 shadow-sm" onchange="this.form.submit()">
                            <?php foreach ($teachersList as $t): ?>
                                <option value="<?= (int) $t['id'] ?>" <?= ((int) ($currentTeacherId ?? 0) === (int) $t['id']) ? 'selected' : '' ?>>
                                    <?= esc($t['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                <a href="<?= base_url('portal/workload' . (!empty($currentTeacherId) ? '?teacher_id=' . $currentTeacherId : '')) ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3 d-inline-flex align-items-center gap-1">
                    <i data-lucide="bar-chart-2" style="width: 16px; height: 16px;"></i> Beban Mengajar
                </a>
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm rounded-3 px-3"><i data-lucide="printer" style="width:16px"></i> Cetak</button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap gap-2 mb-3" role="navigation" aria-label="Pilihan tampilan jadwal">
            <?php foreach ($scopeTitles as $scopeCode => $label): ?>
                <?php if (!in_array($scopeCode, $availableScopes ?? ['teacher'], true)) continue; ?>
                <a href="<?= base_url('portal/schedule?scope=' . $scopeCode) ?>"
                   class="btn btn-sm rounded-3 <?= $scope === $scopeCode ? 'btn-primary' : 'btn-light border' ?>">
                    <?= esc($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($scope === 'classroom' || $scope === 'grade'): ?>
            <form method="get" action="<?= base_url('portal/schedule') ?>" class="row g-2 align-items-end">
                <input type="hidden" name="scope" value="<?= esc($scope) ?>">
                <?php if ($scope === 'classroom'): ?>
                    <div class="col-md-9">
                        <label class="form-label fs-8 fw-semibold" for="scheduleClassroom">Kelas/Rombel</label>
                        <select class="form-select rounded-3" id="scheduleClassroom" name="classroom_id">
                            <?php foreach ($classrooms as $classroom): ?>
                                <option value="<?= (int) $classroom['id'] ?>" <?= (int) $selectedClassroomId === (int) $classroom['id'] ? 'selected' : '' ?>>
                                    <?= esc($classroom['grade_name'] . ' · ' . $classroom['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="col-md-9">
                        <label class="form-label fs-8 fw-semibold" for="scheduleGrade">Jenjang</label>
                        <select class="form-select rounded-3" id="scheduleGrade" name="grade_level_id">
                            <?php foreach ($gradeLevels as $grade): ?>
                                <option value="<?= (int) $grade['id'] ?>" <?= (int) $selectedGradeLevelId === (int) $grade['id'] ? 'selected' : '' ?>>
                                    <?= esc($grade['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <button class="btn btn-primary rounded-3 w-100" type="submit">Tampilkan</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$teacherInfo && $scope === 'teacher'): ?>
    <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
        <i data-lucide="alert-triangle" class="mb-2 text-warning" style="width: 36px; height: 36px;"></i>
        <h5 class="fw-bold text-dark">Akun Belum Ditautkan ke Profil Guru</h5>
        <p class="text-muted fs-7 mb-0">Akun pengguna Anda belum dihubungkan dengan data profil Guru di sistem. Silakan hubungi Administrator untuk melakukan tautan akun (`teacher_id`).</p>
    </div>
<?php else: ?>
    <?php if ($previewVersions): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4 no-print">
            <i data-lucide="file-clock" style="width:22px;height:22px;flex:none"></i>
            <div><strong>Pratinjau jadwal &mdash; belum resmi</strong><div class="fs-8 mt-1"><?php foreach ($previewVersions as $index => $previewVersion): ?><?= $index ? ' · ' : '' ?><?= esc(($previewVersion['unit_code'] ?? '') . ' ' . ($previewVersion['code'] ?? $previewVersion['name'])) ?>: <strong><?= esc($previewVersion['workflow_status']) ?></strong><?php endforeach; ?>. Data dapat diperiksa, tetapi baru menjadi jadwal resmi setelah disetujui atau dikunci.</div></div>
        </div>
    <?php endif; ?>
    <!-- Summary Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 text-primary">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-8 fw-semibold text-uppercase d-block text-muted">Total Entri Jadwal</span>
                        <h2 class="fw-bold mb-0 text-primary mt-1"><?= $totalJp ?> <span class="fs-6 text-muted">JP/minggu</span></h2>
                    </div>
                    <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-success bg-opacity-10 text-success">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-8 fw-semibold text-uppercase d-block text-muted">Mata Pelajaran</span>
                        <h2 class="fw-bold mb-0 text-success mt-1"><?= $subjectCount ?> <span class="fs-6 text-muted">Mapel</span></h2>
                    </div>
                    <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-info bg-opacity-10 text-info">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fs-8 fw-semibold text-uppercase d-block text-muted">Kelas Tercakup</span>
                        <h2 class="fw-bold mb-0 text-info mt-1"><?= $classroomCount ?> <span class="fs-6 text-muted">Rombel</span></h2>
                    </div>
                    <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i data-lucide="door-open" style="width: 24px; height: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($scope === 'teacher'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                <i data-lucide="list-checks" class="text-primary" style="width: 20px; height: 20px;"></i>
                Daftar Penugasan Mengajar (T.A <?= esc($activePeriod['year_name'] ?? '-') ?>)
            </h5>
        </div>
        <div class="card-body p-4">
            <?php if (empty($assignments)): ?>
                <p class="text-muted fs-7 mb-0">Belum ada penugasan mengajar untuk periode aktif ini.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-7">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Mata Pelajaran</th>
                                <th>Kelas / Rombel</th>
                                <?php if (!empty($unitScope['isAll'])): ?><th>Unit</th><?php endif; ?>
                                <th class="text-center">Alokasi JP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-slate-800"><?= esc($a['subject_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-dark fw-medium px-2 py-1 rounded-pill"><?= esc($a['classroom_name'] ?? '-') ?></span></td>
                                    <?php if (!empty($unitScope['isAll'])): ?><td><span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= esc($a['unit_code'] ?? '-') ?></span></td><?php endif; ?>
                                    <td class="text-center fw-bold text-primary"><?= esc($a['weekly_hours'] ?? 0) ?> JP</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timetable Grid -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                <i data-lucide="grid" class="text-primary" style="width: 20px; height: 20px;"></i>
                Jadwal Mingguan <?= $scheduleIsOfficial ? 'Resmi' : 'Pratinjau' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <?php if (!$scheduleVersion): ?>
                <div class="text-center py-5 text-muted">
                    <i data-lucide="calendar-x" class="mb-2 text-muted opacity-50" style="width: 40px; height: 40px;"></i>
                    <p class="mb-0 fs-7">Belum ada versi jadwal untuk periode dan unit aktif.</p>
                </div>
            <?php elseif (empty($scheduleGrid)): ?>
                <div class="text-center py-5 text-muted">
                    <i data-lucide="calendar-search" class="mb-2 opacity-50" style="width: 40px; height: 40px;"></i>
                    <p class="mb-0 fs-7">Tidak ada entri jadwal untuk pilihan tampilan ini.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 fs-8">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 60px;">Jam</th>
                                <?php foreach ($days as $day): ?>
                                    <th><?= esc($day['day_name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($slot = 1; $slot <= $maxSlot; $slot++): ?>
                                <tr>
                                    <td class="text-center fw-bold bg-light"><?= $slot ?></td>
                                    <?php foreach ($days as $day): ?>
                                        <?php
                                            $dayNum = $day['day_number'];
                                            $cellEntries = $scheduleGrid[$dayNum][$slot] ?? [];
                                        ?>
                                        <td class="align-top p-2" style="min-width: 130px; height: 60px;">
                                            <?php foreach ($cellEntries as $ent): ?>
                                                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-20 rounded-3 p-2 mb-1">
                                                    <div class="fw-bold text-primary fs-8"><?= esc($ent['subject_name']) ?></div>
                                                    <?php if (!empty($unitScope['isAll'])): ?><span class="badge bg-info-subtle text-info-emphasis rounded-pill mb-1"><?= esc($ent['unit_code'] ?? '-') ?></span><?php endif; ?>
                                                    <?php if (!empty($ent['is_substitution_assignment'])): ?><div class="badge bg-warning-subtle text-warning-emphasis rounded-pill mb-1"><i data-lucide="user-round-check" style="width:10px"></i> Menggantikan <?= esc($ent['substitution_owner_name'] ?? 'guru') ?></div><?php endif; ?>
                                                    <div class="fs-9 text-slate-600">
                                                        <i data-lucide="door-open" style="width: 10px; height: 10px;"></i> <?= esc($ent['classroom_name']) ?>
                                                        <?php if ($scope !== 'teacher' && !empty($ent['teacher_name'])): ?>
                                                            <br><i data-lucide="user" style="width: 10px; height: 10px;"></i> <?= esc($ent['teacher_name']) ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($ent['room_name'])): ?>
                                                            | <i data-lucide="building-2" style="width: 10px; height: 10px;"></i> <?= esc($ent['room_name']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<style>@media print{.sidebar,.app-navbar,.app-footer,.no-print,.card:first-of-type{display:none!important}.main-container{margin:0!important}.content-body{padding:0!important}.card{box-shadow:none!important}body{background:#fff!important}}</style>
<?= $this->endSection() ?>
