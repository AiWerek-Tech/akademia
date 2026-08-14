<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="book-open" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Master Mata Pelajaran Global
                </h4>
                <p class="text-muted fs-7 mb-0">Master data mata pelajaran global terpadu unit SMP dan SMA</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('subjects.import')): ?>
                    <a href="<?= base_url('imports/master/template/subjects') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3 d-flex align-items-center gap-2" title="Unduh template resmi yang sama dengan halaman Import Master">
                        <i data-lucide="file-spreadsheet" style="width:16px;height:16px"></i><span>Template</span>
                    </a>
                    <a href="<?= base_url('imports/master?type=SUBJECTS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2"><i data-lucide="upload" style="width:16px;height:16px"></i><span>Import Excel</span></a>
                <?php endif; ?>
                <?php if (has_permission('subjects.export')): ?>
                    <a href="<?= base_url('subjects/export?unit_id=' . ($filters['unit_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Data</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('subjects.manage')): ?>
                    <a href="<?= base_url('subjects/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                        <span>Tambah Mapel</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('subjects') ?>" class="row g-3 align-items-center">
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
                <select name="category" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($filters['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm rounded-3" placeholder="Cari Kode atau Nama Mata Pelajaran..." value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100">Filter</button>
                <a href="<?= base_url('subjects') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?php
        $currentPage = (int)($pager->getCurrentPage() ?? 1);
        $perPageVal = $perPage ?? '10';
        $itemsPerPage = $perPageVal === 'all' ? (count($subjects) ?: 1) : (int)$perPageVal;
        $totalRecords = $pager->getTotal() ?? count($subjects);
        $startNo = empty($subjects) ? 0 : (($currentPage - 1) * $itemsPerPage) + 1;
        $endNo = empty($subjects) ? 0 : min($startNo + count($subjects) - 1, $totalRecords);
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
                        return base_url('subjects') . '?' . http_build_query(array_filter($p, fn($v) => $v !== null && $v !== ''));
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
                        <th>Nama Mata Pelajaran</th>
                        <th>Kategori</th>
                        <th>Ketersediaan Unit</th>
                        <th>Masuk Rapor</th>
                        <th>Hitung Beban</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Belum ada data mata pelajaran.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $idx => $s): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $startNo + $idx ?></td>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($s['code']) ?></span></td>
                                <td>
                                    <span class="fw-bold text-slate-800 d-block"><?= esc($s['name']) ?></span>
                                    <span class="fs-8 text-muted">Alias: <?= !empty($s['aliases']) ? esc(implode(', ', array_column($s['aliases'], 'alias_name'))) : '-' ?></span>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($s['category']) ?></span></td>
                                <td>
                                    <?php if (!empty($s['unit_availabilities'])): ?>
                                        <?php foreach ($s['unit_availabilities'] as $ua): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info px-2 py-0.5 rounded-pill fs-9 me-1"><?= esc($ua['unit_code'] ?? $ua['unit_name'] ?? ('Unit #' . $ua['unit_id'])) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark fs-9">Lintas Unit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= (int)$s['counts_in_report'] === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-muted' ?> px-2 py-0.5 rounded-pill fs-9">
                                        <?= (int)$s['counts_in_report'] === 1 ? 'Ya' : 'Tidak' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= (int)$s['counts_as_teaching_load'] === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-muted' ?> px-2 py-0.5 rounded-pill fs-9">
                                        <?= (int)$s['counts_as_teaching_load'] === 1 ? 'Ya' : 'Tidak' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (has_permission('subjects.manage')): ?>
                                        <a href="<?= base_url('subjects/' . $s['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($perPageVal !== 'all' && !empty($subjects)): ?>
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
