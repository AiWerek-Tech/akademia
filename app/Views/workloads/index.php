<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Dashboard Beban Kerja Guru</h1>
            <p class="text-muted small mb-0">Pantau beban kerja guru, kekurangan (underload), dan kelebihan (overload) jam mengajar</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (has_permission('workloads.recalculate') && $version): ?>
                <form method="post" action="<?= base_url('workloads/recalculate') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="assignment_version_id" value="<?= $version['id'] ?>">
                    <input type="hidden" name="academic_period_id" value="<?= $selected_period_id ?>">
                    <input type="hidden" name="unit_id" value="<?= $selected_unit_id ?>">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-clockwise"></i> Hitung Ulang Beban Kerja
                    </button>
                </form>
            <?php endif; ?>
            <?php if (has_permission('workloads.export')): ?>
                <a href="<?= base_url('workloads/export') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Laporan
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Switcher / Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small text-muted">Periode Akademik</label>
                    <select name="academic_period_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($selected_period_id == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted">Unit Sekolah</label>
                    <select name="unit_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Unit</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($selected_unit_id == $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Snapshots List -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 small fw-bold text-uppercase text-secondary">Rincian Jam Mengajar & Tugas Tambahan</h5>
            <?php if ($version): ?>
                <span class="badge bg-light text-dark">Versi Acuan: <?= esc($version['code']) ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Guru</th>
                            <th>Status / Tipe</th>
                            <th>Jam Mengajar</th>
                            <th>Jam Tugas Tambahan</th>
                            <th>Total Beban</th>
                            <th>Kebijakan Target</th>
                            <th>Status Beban</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($snapshots)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada perhitungan beban kerja. Silakan lakukan perhitungan ulang.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($snapshots as $s): ?>
                                <tr>
                                    <td class="fw-bold"><?= esc($s['full_name']) ?></td>
                                    <td><?= esc($s['employment_status'] ?? '-') ?> / <?= esc($s['employment_type'] ?? '-') ?></td>
                                    <td><?= esc($s['teaching_workload_hours']) ?> JP</td>
                                    <td><?= esc($s['additional_duty_hours']) ?> JP</td>
                                    <td class="fw-bold"><?= esc($s['total_workload_hours']) ?> JP</td>
                                    <td>
                                        <?php if ($s['policy_minimum'] !== null): ?>
                                            Min: <?= esc($s['policy_minimum']) ?> | Max: <?= esc($s['policy_maximum']) ?> JP
                                        <?php else: ?>
                                            <span class="text-muted small">Tidak ada kebijakan khusus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $sBadge = match ($s['status']) {
                                            'WITHIN_TARGET' => 'bg-success',
                                            'UNDERLOAD'     => 'bg-warning text-dark',
                                            'OVERLOAD'      => 'bg-danger',
                                            default         => 'bg-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $sBadge ?>"><?= esc($s['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
