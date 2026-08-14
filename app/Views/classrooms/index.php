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
                <?php if (has_permission('classrooms.import')): ?>
                    <a href="<?= base_url('imports/master/template/classrooms') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3 d-flex align-items-center gap-2" title="Unduh template resmi yang sama dengan halaman Import Master">
                        <i data-lucide="file-spreadsheet" style="width:16px;height:16px"></i><span>Template</span>
                    </a>
                    <a href="<?= base_url('imports/master?type=CLASSROOMS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2"><i data-lucide="upload" style="width:16px;height:16px"></i><span>Import Excel</span></a>
                <?php endif; ?>
                <?php if (has_permission('classrooms.manage')): ?>
                    <a href="<?= base_url('classrooms/promote') ?>" class="btn btn-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2 shadow-sm">
                        <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                        <span>Kenaikan Kelas Otomatis</span>
                    </a>
                    <a href="<?= base_url('classrooms/copy-period') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="copy" style="width: 16px; height: 16px;"></i>
                        <span>Copy Antar Periode</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('classrooms.export')): ?>
                    <a href="<?= base_url('classrooms/export?unit_id=' . ($filters['unit_id'] ?? '') . '&academic_period_id=' . ($filters['academic_period_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Data</span>
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
            <?php if (!empty($filters['per_page'])): ?>
                <input type="hidden" name="per_page" value="<?= esc($filters['per_page']) ?>">
            <?php endif; ?>
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
                        <?php
                        $semText = ((int)($p['semester_number'] ?? 0) === 1) ? 'Ganjil' : 'Genap';
                        $periodLabel = 'T.A. ' . esc($p['year_name'] ?? '') . ' - Semester ' . esc($p['semester_number'] ?? '') . ' (' . $semText . ')';
                        if (!empty(trim($p['name'] ?? ''))) {
                            $periodLabel = esc($p['name']) . ' (' . $periodLabel . ')';
                        }
                        ?>
                        <option value="<?= $p['id'] ?>" <?= (string)($filters['academic_period_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>>
                            <?= $periodLabel ?>
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
        <?php
        $currentPage = (int)($pager->getCurrentPage() ?? 1);
        $perPageVal = $perPage ?? '10';
        $itemsPerPage = $perPageVal === 'all' ? (count($classrooms) ?: 1) : (int)$perPageVal;
        $totalRecords = $pager->getTotal() ?? count($classrooms);
        $startNo = empty($classrooms) ? 0 : (($currentPage - 1) * $itemsPerPage) + 1;
        $endNo = empty($classrooms) ? 0 : min($startNo + count($classrooms) - 1, $totalRecords);
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="fs-8 text-muted">
                Menampilkan <span class="fw-bold text-dark"><?= $startNo ?></span> – <span class="fw-bold text-dark"><?= $endNo ?></span> dari <span class="fw-bold text-dark"><?= $totalRecords ?></span> data
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="fs-8 text-muted mb-0">Tampilkan:</label>
                <select class="form-select form-select-sm rounded-3 py-1 ps-2 pe-4 fs-8 border-light-subtle" onchange="location = this.value;" style="width: auto;">
                    <?php
                    $queryParams = $filters;
                    unset($queryParams['per_page']);
                    $buildUrl = function($pp) use ($queryParams) {
                        $p = array_merge($queryParams, ['per_page' => $pp]);
                        return base_url('classrooms') . '?' . http_build_query(array_filter($p, fn($v) => $v !== null && $v !== ''));
                    };
                    ?>
                    <option value="<?= $buildUrl(10) ?>" <?= $perPageVal === '10' ? 'selected' : '' ?>>10 baris</option>
                    <option value="<?= $buildUrl(20) ?>" <?= $perPageVal === '20' ? 'selected' : '' ?>>20 baris</option>
                    <option value="<?= $buildUrl(50) ?>" <?= $perPageVal === '50' ? 'selected' : '' ?>>50 baris</option>
                    <option value="<?= $buildUrl('all') ?>" <?= $perPageVal === 'all' ? 'selected' : '' ?>>Semua</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light rounded-3">
                        <th class="text-center ps-3" style="width: 55px;">No.</th>
                        <th>Kode</th>
                        <th>Nama Kelas/Rombel</th>
                        <th>Unit</th>
                        <th>Tingkat</th>
                        <th>Periode</th>
                        <th>Kapasitas</th>
                        <th>Wali Kelas</th>
                        <th>Ruang Default</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classrooms)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">Belum ada data kelas/rombongan belajar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($classrooms as $idx => $c): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $startNo + $idx ?></td>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($c['code']) ?></span></td>
                                <td>
                                    <span class="fw-bold text-slate-800 d-block"><?= esc($c['name']) ?></span>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($c['unit_code']) ?></span></td>
                                <td>Tingkat <?= esc($c['grade_name']) ?></td>
                                <td><span class="fw-semibold text-slate-700"><?= esc($c['period_name']) ?></span></td>
                                <td>
                                    <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle rounded-2">
                                        <i data-lucide="users" class="me-1" style="width:12px;height:12px;"></i><?= (int) ($c['total_students'] ?? 0) ?> / <?= esc($c['capacity'] ?: '∞') ?> Siswa
                                    </span>
                                </td>
                                <td><strong><?= esc($c['homeroom_teacher_name'] ?: 'Belum ditentukan') ?></strong></td>
                                <td><span class="font-monospace fs-8 text-primary"><?= esc($c['room_name'] ?: 'Belum ditentukan') ?></span></td>
                                <td class="text-end pe-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?= base_url('classrooms/' . $c['uuid'] . '/students') ?>" class="btn btn-sm btn-outline-success rounded-3 px-2 py-1" title="Atur Siswa Rombel">
                                            <i data-lucide="users" style="width:14px;height:14px;"></i> Siswa (<?= (int)($c['total_students'] ?? 0) ?>)
                                        </a>
                                        <?php if (has_permission('classrooms.manage')): ?>
                                            <a href="<?= base_url('classrooms/' . $c['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($perPageVal !== 'all' && !empty($classrooms)): ?>
            <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top flex-wrap gap-2">
                <div class="fs-8 text-muted">
                    Halaman <span class="fw-bold text-dark"><?= $currentPage ?></span> dari <span class="fw-bold text-dark"><?= max(1, ceil($totalRecords / $itemsPerPage)) ?></span>
                </div>
                <div class="pagination-container fs-8">
                    <?= $pager->links() ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
