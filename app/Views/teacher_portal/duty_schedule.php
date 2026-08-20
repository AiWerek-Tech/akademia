<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php $dayIcons = [1 => 'sun', 2 => 'cloud-sun', 3 => 'calendar-check', 4 => 'shield-check', 5 => 'sparkles']; ?>
<section aria-labelledby="duty-title">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
        <div>
            <span class="badge bg-primary-subtle text-primary rounded-pill mb-2">Portal Personal Guru</span>
            <h3 id="duty-title" class="fw-bold mb-1">Jadwal Piket Saya</h3>
            <p class="text-muted mb-0"><?= esc($academicYear['name'] ?? 'Tahun pelajaran belum tersedia') ?> &middot; <?= esc($unitScope['label'] ?? 'Unit aktif') ?> &middot; hanya menampilkan tugas milik akun ini.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($isManagement) && !empty($teachersList)): ?>
                <form method="get" action="<?= base_url('portal/duty-schedule') ?>" class="d-inline-flex align-items-center gap-1 me-2">
                    <label class="fs-8 fw-semibold text-muted text-nowrap d-none d-md-inline" for="supervisionDutySelect">Guru:</label>
                    <select id="supervisionDutySelect" name="teacher_id" class="form-select form-select-sm rounded-3 shadow-sm" onchange="this.form.submit()">
                        <?php foreach ($teachersList as $t): ?>
                            <option value="<?= (int) $t['id'] ?>" <?= ((int) ($currentTeacherId ?? 0) === (int) $t['id']) ? 'selected' : '' ?>>
                                <?= esc($t['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a href="<?= base_url('portal/workload' . (!empty($currentTeacherId) ? '?teacher_id=' . $currentTeacherId : '')) ?>" class="btn btn-light border rounded-3">Beban Kerja</a>
            <button type="button" class="btn btn-primary rounded-3" onclick="window.print()"><i data-lucide="printer" class="me-1" style="width:16px"></i>Cetak</button>
        </div>
    </div>

    <?php if (!$teacherInfo): ?>
        <div class="alert alert-warning border-0 rounded-4 p-4">Akun belum ditautkan ke profil guru. Hubungi superadmin untuk mengatur profil guru pada akun Anda.</div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 duty-print-header">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between gap-3">
                <div><div class="text-muted small text-uppercase fw-semibold">Nama Guru</div><h4 class="fw-bold mb-1"><?= esc($teacherInfo['full_name']) ?></h4><span class="text-muted">NIP/NIK: <?= esc($teacherInfo['nip'] ?: ($teacherInfo['employee_number'] ?: '-')) ?></span></div>
                <div class="text-md-end"><div class="text-muted small text-uppercase fw-semibold">Total Hari Piket</div><div class="display-6 fw-bold text-primary"><?= count($duties) ?></div></div>
            </div>
        </div>

        <?php if (!$duties): ?>
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-5 text-center"><i data-lucide="calendar-x" class="text-muted mb-3" style="width:44px;height:44px"></i><h5 class="fw-bold">Belum ada jadwal piket</h5><p class="text-muted mb-0">Anda belum mendapat penugasan piket pada tahun pelajaran ini.</p></div></div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($duties as $duty): ?>
                    <div class="col-md-6 col-xl-4"><article class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4"><div class="d-flex align-items-center gap-3 mb-3"><span class="bg-primary bg-opacity-10 text-primary rounded-3 p-3"><i data-lucide="<?= esc($dayIcons[(int)$duty['day_of_week']] ?? 'calendar-check') ?>"></i></span><div><div class="text-muted small">Hari piket</div><h4 class="fw-bold mb-0"><?= esc($duty['day_name']) ?></h4></div></div><span class="badge bg-success-subtle text-success rounded-pill"><?= esc(str_replace('_', ' ', $duty['duty_role'])) ?></span><?php if (!empty($duty['notes'])): ?><p class="text-muted small mt-3 mb-0"><?= esc($duty['notes']) ?></p><?php endif; ?></div></article></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<style>@media print{.sidebar,.app-navbar,.app-footer,.no-print{display:none!important}.main-container{margin:0!important}.content-body{padding:0!important}.card{box-shadow:none!important;border:1px solid #dbe3ee!important}body{background:#fff!important}}</style>
<?= $this->endSection() ?>
