<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="layers" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Tingkat Kelas & Fase Pendidikan
                </h4>
                <p class="text-muted fs-7 mb-0">Pengaturan tingkat kelas (Grade) dan Fase Kurikulum Merdeka per Unit</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('grade-levels') ?>" class="row g-3 align-items-center">
            <div class="col-md-4">
                <select name="unit_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Unit --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string)($selectedUnitId ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold">
                        <th>Kode</th>
                        <th>Nama Tingkat</th>
                        <th>Unit Sekolah</th>
                        <th>Fase</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gradeLevels)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data tingkat kelas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gradeLevels as $gl): ?>
                            <tr>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($gl['code']) ?></span></td>
                                <td><span class="fw-bold text-slate-800"><?= esc($gl['name']) ?></span></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($gl['unit_name']) ?></span></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info px-2.5 py-1 rounded-pill fs-8">Fase <?= esc($gl['phase']) ?></span></td>
                                <td><?= esc($gl['sort_order']) ?></td>
                                <td>
                                    <span class="badge <?= (int)$gl['is_active'] === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-muted' ?> px-2.5 py-1 rounded-pill fs-8">
                                        <?= (int)$gl['is_active'] === 1 ? 'Aktif' : 'Non-Aktif' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (has_permission('grade_levels.manage')): ?>
                                        <a href="<?= base_url('grade-levels/' . $gl['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3">Edit</a>
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
<?= $this->endSection() ?>
