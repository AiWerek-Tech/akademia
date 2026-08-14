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
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('grade_levels.import')): ?>
                    <a href="<?= base_url('imports/master/template/grade_levels') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3 d-flex align-items-center gap-2" title="Unduh template resmi yang sama dengan halaman Import Master">
                        <i data-lucide="file-spreadsheet" style="width:16px;height:16px"></i><span>Template</span>
                    </a>
                    <a href="<?= base_url('imports/master?type=GRADE_LEVELS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2"><i data-lucide="upload" style="width:16px;height:16px"></i><span>Import Excel</span></a>
                <?php endif; ?>
                <?php if (has_permission('grade_levels.manage')): ?>
                    <a href="<?= base_url('grade-levels/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="plus" style="width:16px;height:16px"></i><span>Tambah Tingkat</span>
                    </a>
                <?php endif; ?>
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
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="fs-8 text-muted">
                Total Data: <span class="fw-bold text-dark"><?= count($gradeLevels) ?></span> tingkat kelas
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light rounded-3">
                        <th class="text-center ps-3" style="width: 55px;">No.</th>
                        <th>Kode</th>
                        <th>Nama Tingkat</th>
                        <th>Unit Sekolah</th>
                        <th>Fase</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gradeLevels)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada data tingkat kelas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gradeLevels as $idx => $gl): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
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
                                <td class="text-end pe-3">
                                    <?php if (has_permission('grade_levels.manage')): ?>
                                        <a href="<?= base_url('grade-levels/' . $gl['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3">Edit</a>
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
