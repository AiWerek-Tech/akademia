<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php $allAssignmentVersionsOfficial = !empty($assignmentVersions) && count(array_filter($assignmentVersions, static fn (array $version): bool => in_array(strtoupper((string) $version['workflow_status']), ['APPROVED','LOCKED'], true))) === count($assignmentVersions); ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="bar-chart-2" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Rincian Beban Kerja Saya
                </h4>
                <p class="text-muted fs-7 mb-0">
                    Portal Guru &mdash; <?= esc($teacherInfo['full_name'] ?? session()->get('full_name')) ?> &middot; <?= esc($unitScope['label'] ?? 'Unit Aktif') ?>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('teacher_assignment_document.view') && $assignmentDocumentAvailable): ?>
                    <a href="<?= base_url('portal/assignment-document') ?>" target="_blank" class="btn btn-primary btn-sm rounded-3 px-3"><i data-lucide="file-signature" style="width:16px"></i> <?= $allAssignmentVersionsOfficial ? 'Cetak SK Saya' : 'Pratinjau SK Saya' ?></a>
                <?php endif; ?>
                <?php if (has_permission('teacher_duty_schedule.view')): ?>
                    <a href="<?= base_url('portal/duty-schedule') ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3"><i data-lucide="shield-check" style="width:16px"></i> Piket Saya</a>
                <?php endif; ?>
                <a href="<?= base_url('portal/schedule') ?>" class="btn btn-outline-primary btn-sm rounded-3 px-3 d-inline-flex align-items-center gap-1">
                    <i data-lucide="calendar-days" style="width: 16px; height: 16px;"></i> Jadwal Mengajar Saya
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!$teacherInfo): ?>
    <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
        <i data-lucide="alert-triangle" class="mb-2 text-warning" style="width: 36px; height: 36px;"></i>
        <h5 class="fw-bold text-dark">Akun Belum Ditautkan ke Profil Guru</h5>
        <p class="text-muted fs-7 mb-0">Akun pengguna Anda belum dihubungkan dengan data profil Guru di sistem.</p>
    </div>
<?php else: ?>
    <?php $unofficialVersions = array_values(array_filter($assignmentVersions ?? [], static fn (array $version): bool => !in_array(strtoupper((string) $version['workflow_status']), ['APPROVED','LOCKED'], true))); ?>
    <?php if ($unofficialVersions): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4">
            <i data-lucide="file-clock" style="width:22px;height:22px;flex:none"></i>
            <div><strong>Data pembagian tugas belum resmi</strong><div class="fs-8 mt-1"><?php foreach ($unofficialVersions as $index => $version): ?><?= $index ? ' · ' : '' ?><?= esc(($version['unit_code'] ?? '') . ' ' . $version['code']) ?>: <strong><?= esc($version['workflow_status']) ?></strong><?php endforeach; ?>. Rincian di bawah adalah data terkini dan SK akan berstatus resmi setelah disetujui atau dikunci.</div></div>
        </div>
    <?php endif; ?>
    <!-- Workload Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <span class="fs-8 text-uppercase fw-semibold text-muted">Jam Tatap Muka (JTM)</span>
                <h1 class="fw-bold text-primary mb-0 mt-2"><?= esc($workloadSnapshot['teaching_assigned_hours'] ?? array_sum(array_column($assignments, 'weekly_hours'))) ?></h1>
                <span class="fs-9 text-muted">JP / Minggu</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <span class="fs-8 text-uppercase fw-semibold text-muted">Ekuivalensi Tugas Tambahan</span>
                <h1 class="fw-bold text-info mb-0 mt-2"><?= esc($workloadSnapshot['additional_duty_hours'] ?? array_sum(array_column($duties, 'equivalent_hours'))) ?></h1>
                <span class="fs-9 text-muted">Jam / Minggu</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-primary text-white">
                <span class="fs-8 text-uppercase fw-semibold text-white-50">Total Jam Kerja Mengajar (JKM)</span>
                <h1 class="fw-bold text-white mb-0 mt-2">
                    <?= esc($workloadSnapshot['total_workload_hours'] ?? (array_sum(array_column($assignments, 'weekly_hours')) + array_sum(array_column($duties, 'equivalent_hours')))) ?>
                </h1>
                <span class="fs-9 text-white-50">Jam Kumulatif</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <span class="fs-8 text-uppercase fw-semibold text-muted">Status Beban Mengajar</span>
                <div class="mt-2">
                    <?php $workloadStatus = (string) ($workloadSnapshot['status'] ?? 'NO_POLICY'); ?>
                    <span class="badge bg-<?= in_array($workloadStatus, ['WITHIN_TARGET', 'NO_POLICY'], true) ? 'success' : 'warning' ?> bg-opacity-10 text-<?= in_array($workloadStatus, ['WITHIN_TARGET', 'NO_POLICY'], true) ? 'success' : 'warning' ?> px-3 py-2 rounded-pill fw-bold fs-7">
                        <i data-lucide="<?= in_array($workloadStatus, ['WITHIN_TARGET', 'NO_POLICY'], true) ? 'check-circle' : 'alert-circle' ?>" style="width: 14px; height: 14px;" class="me-1"></i> <?= esc(str_replace('_', ' ', $workloadStatus === 'NO_POLICY' ? 'TERCATAT' : $workloadStatus)) ?>
                    </span>
                </div>
                <span class="fs-9 text-muted mt-2 d-block">Rentang kebijakan hanya peringatan, bukan pembatas.</span>
            </div>
        </div>
    </div>

    <!-- Detailed Assignments Table -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                        <i data-lucide="book-open" class="text-primary" style="width: 20px; height: 20px;"></i>
                        Beban Jam Mengajar Per Rombel
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Mata Pelajaran</th>
                                    <th>Kelas</th>
                                    <?php if (!empty($unitScope['isAll'])): ?><th>Unit</th><?php endif; ?>
                                    <th class="text-center">Jam Tatap Muka</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                    <tr><td colspan="<?= !empty($unitScope['isAll']) ? 4 : 3 ?>" class="text-center py-4 text-muted">Belum ada penugasan mengajar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-slate-800"><?= esc($a['subject_name']) ?></td>
                                            <td><span class="badge bg-light text-dark border px-2 py-1"><?= esc($a['classroom_name']) ?></span></td>
                                            <?php if (!empty($unitScope['isAll'])): ?><td><span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= esc($a['unit_code'] ?? '-') ?></span></td><?php endif; ?>
                                            <td class="text-center fw-bold text-primary"><?= esc($a['weekly_hours']) ?> JP</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
                        <i data-lucide="briefcase" class="text-primary" style="width: 20px; height: 20px;"></i>
                        Tugas Tambahan Terdaftar
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Nama Tugas Tambahan</th>
                                    <?php if (!empty($unitScope['isAll'])): ?><th>Unit</th><?php endif; ?>
                                    <th class="text-center">Ekuivalensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($duties)): ?>
                                    <tr><td colspan="<?= !empty($unitScope['isAll']) ? 3 : 2 ?>" class="text-center py-4 text-muted">Tidak ada tugas tambahan terdaftar.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($duties as $d): ?>
                                        <tr>
                                            <td class="ps-3 fw-semibold"><?= esc($d['duty_name']) ?></td>
                                            <?php if (!empty($unitScope['isAll'])): ?><td><span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= esc($d['unit_code'] ?? '-') ?></span></td><?php endif; ?>
                                            <td class="text-center fw-bold text-info">+<?= esc($d['equivalent_hours']) ?> Jam</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
