<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= base_url('classrooms') ?>" class="btn btn-sm btn-light border rounded-3"><i data-lucide="arrow-left" style="width:16px;height:16px;"></i></a>
            <h4 class="fw-bold mb-0"><i data-lucide="trending-up" class="me-2 text-success" style="width:24px;height:24px;"></i>Kenaikan Kelas & Perpindahan Rombel Otomatis</h4>
        </div>
        <p class="text-muted fs-7 mb-0">Sistem otomatisasi pemindahan & kenaikan tingkat kelas siswa saat pergantian Tahun Pelajaran.</p>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif ?>

<!-- Config Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 p-4">
        <h5 class="fw-bold mb-1"><i data-lucide="sliders" class="me-2 text-primary" style="width:18px;height:18px;"></i>Pengaturan Periode Kenaikan Kelas</h5>
        <small class="text-muted">Pilih Tahun Pelajaran Sumber (Asal) dan Tahun Pelajaran Target (Baru).</small>
    </div>
    <div class="card-body p-4 pt-0">
        <form method="get" action="<?= base_url('classrooms/promote') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold fs-8">Unit Sekolah <span class="text-danger">*</span></label>
                <select name="unit_id" class="form-select rounded-3" required>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$unitId === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-8">Tahun Pelajaran Asal (Lama) <span class="text-danger">*</span></label>
                <select name="source_year_id" class="form-select rounded-3" required>
                    <option value="">Pilih Tahun Asal...</option>
                    <?php foreach ($academicYears as $ay): ?>
                        <option value="<?= $ay['id'] ?>" <?= (int)$sourceYearId === (int)$ay['id'] ? 'selected' : '' ?>><?= esc($ay['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold fs-8">Tahun Pelajaran Target (Baru) <span class="text-danger">*</span></label>
                <select name="target_year_id" class="form-select rounded-3" required>
                    <option value="">Pilih Tahun Target...</option>
                    <?php foreach ($academicYears as $ay): ?>
                        <option value="<?= $ay['id'] ?>" <?= (int)$targetYearId === (int)$ay['id'] ? 'selected' : '' ?>><?= esc($ay['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100 rounded-3"><i data-lucide="eye" style="width:16px;height:16px;"></i> Cek</button>
            </div>
        </form>
    </div>
</div>

<?php if ($sourceYearId > 0 && $targetYearId > 0): ?>
<!-- Preview & Execute Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i data-lucide="play-circle" class="me-2 text-success" style="width:20px;height:20px;"></i>Pratinjau Kenaikan Kelas</h5>
            <small class="text-muted">Simulasi pemindahan siswa berdasarkan tingkat kelas asal ke tingkat kelas target.</small>
        </div>
        <form method="post" action="<?= base_url('classrooms/promote') ?>" onsubmit="return confirm('PERINGATAN: Sistem akan memproses kenaikan kelas untuk seluruh siswa aktif pada Tahun Pelajaran yang dipilih. Lanjutkan?')">
            <?= csrf_field() ?>
            <input type="hidden" name="unit_id" value="<?= esc($unitId) ?>">
            <input type="hidden" name="source_year_id" value="<?= esc($sourceYearId) ?>">
            <input type="hidden" name="target_year_id" value="<?= esc($targetYearId) ?>">
            <button type="submit" class="btn btn-success rounded-3 px-4 shadow-sm">
                <i data-lucide="zap" class="me-1" style="width:16px;height:16px;"></i> Eksekusi Kenaikan Kelas Otomatis
            </button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr class="text-uppercase text-muted fs-8 fw-bold bg-light">
                    <th class="ps-4">Tingkat Asal</th>
                    <th>Status / Tingkat Target</th>
                    <th>Jumlah Siswa Terpengaruh</th>
                    <th class="text-end pe-4">Keterangan Regulasi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($previewData === []): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data siswa aktif pada Tahun Pelajaran sumber.</td>
                    </tr>
                <?php endif ?>
                <?php foreach ($previewData as $row): ?>
                    <tr>
                        <td class="ps-4"><strong>Tingkat <?= esc($row['current_grade']) ?></strong></td>
                        <td>
                            <?php if ($row['is_graduate']): ?>
                                <span class="badge text-bg-success px-3 py-1 fs-8"><i data-lucide="award" class="me-1" style="width:12px;height:12px;"></i> <?= esc($row['next_grade']) ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 fs-8"><i data-lucide="arrow-up-right" class="me-1" style="width:12px;height:12px;"></i> <?= esc($row['next_grade']) ?></span>
                            <?php endif ?>
                        </td>
                        <td><span class="fw-bold text-dark fs-7"><?= esc($row['total_students']) ?> Siswa</span></td>
                        <td class="text-end pe-4">
                            <?php if ($row['is_graduate']): ?>
                                <small class="text-muted">Siswa akhir tingkat selesai masa studi (Lulus/Alumni).</small>
                            <?php else: ?>
                                <small class="text-muted">Siswa otomatis dipromosikan ke tingkat berikutnya & dipetakan ke Rombel target.</small>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
