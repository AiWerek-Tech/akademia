<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="door-open" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Master Kelas / Rombel
                </h4>
                <p class="text-muted fs-7 mb-0">Manajemen rombongan belajar aktif pada periode dan unit terpilih</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('classrooms.manage')): ?>
                    <a href="<?= base_url('classrooms/copy-period') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="copy" style="width: 16px; height: 16px;"></i>
                        <span>Copy Antar Periode</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('classrooms.export')): ?>
                    <a href="<?= base_url('classrooms/export?unit_id=' . ($filters['unit_id'] ?? '') . '&academic_period_id=' . ($filters['academic_period_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Excel</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('classrooms.manage')): ?>
                    <a href="<?= base_url('classrooms/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                        <span>Tambah Rombel</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('classrooms') ?>" class="row g-3 align-items-center">
            <div class="col-md-3">
                <select name="unit_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Unit --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string)($filters['unit_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="academic_period_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Periode --</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (string)($filters['academic_period_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>>
                            <?= esc($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="grade_level_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Tingkat --</option>
                    <?php foreach ($gradeLevels as $gl): ?>
                        <option value="<?= $gl['id'] ?>" <?= (string)($filters['grade_level_id'] ?? '') === (string)$gl['id'] ? 'selected' : '' ?>>
                            Tingkat <?= esc($gl['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm rounded-3" placeholder="Kode/Nama Rombel..." value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100">Filter</button>
                <a href="<?= base_url('classrooms') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
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
                        <th>Nama Kelas/Rombel</th>
                        <th>Unit</th>
                        <th>Tingkat</th>
                        <th>Periode</th>
                        <th>Kapasitas</th>
                        <th>Wali Kelas</th>
                        <th>Ruang Default</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classrooms)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Belum ada data kelas/rombongan belajar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($classrooms as $c): ?>
                            <tr>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($c['code']) ?></span></td>
                                <td>
                                    <span class="fw-bold text-slate-800 d-block"><?= esc($c['name']) ?></span>
                                    <span class="fs-8 text-muted">Major: <?= esc($c['major'] ?: '-') ?></span>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($c['unit_code']) ?></span></td>
                                <td>Tingkat <?= esc($c['grade_name']) ?></td>
                                <td><span class="fw-semibold text-slate-700"><?= esc($c['period_name']) ?></span></td>
                                <td><?= esc($c['capacity']) ?> Siswa</td>
                                <td><strong><?= esc($c['homeroom_teacher_name'] ?: 'Belum ditentukan') ?></strong></td>
                                <td><span class="font-monospace fs-8 text-primary"><?= esc($c['room_name'] ?: 'Belum ditentukan') ?></span></td>
                                <td class="text-end">
                                    <?php if (has_permission('classrooms.manage')): ?>
                                        <a href="<?= base_url('classrooms/' . $c['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links() ?></div>
    </div>
</div>
<?= $this->endSection() ?>
