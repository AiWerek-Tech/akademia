<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
                    <i data-lucide="map-pin" class="text-primary" style="width: 24px; height: 24px;"></i>
                    Master Ruang Kelas / Lab
                </h4>
                <p class="text-muted fs-7 mb-0">Manajemen ruang kelas, lab komputer, perpustakaan, dan fasilitas belajar lainnya</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (has_permission('rooms.import')): ?>
                    <a href="<?= base_url('imports/master/template/rooms') ?>" class="btn btn-outline-secondary rounded-3 btn-sm px-3 d-flex align-items-center gap-2" title="Unduh template resmi yang sama dengan halaman Import Master">
                        <i data-lucide="file-spreadsheet" style="width:16px;height:16px"></i><span>Template</span>
                    </a>
                    <a href="<?= base_url('imports/master?type=ROOMS') ?>" class="btn btn-outline-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2"><i data-lucide="upload" style="width:16px;height:16px"></i><span>Import Excel</span></a>
                <?php endif; ?>
                <?php if (has_permission('rooms.export')): ?>
                    <a href="<?= base_url('rooms/export?unit_id=' . ($filters['unit_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Data</span>
                    </a>
                <?php endif; ?>
                <?php if (has_permission('rooms.manage')): ?>
                    <a href="<?= base_url('rooms/create') ?>" class="btn btn-primary rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                        <span>Tambah Ruang</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= base_url('rooms') ?>" class="row g-3 align-items-center">
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
                <select name="room_type_id" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Jenis Ruang --</option>
                    <?php foreach ($roomTypes as $roomType): ?>
                        <option value="<?= esc($roomType['id']) ?>" <?= (string)($filters['room_type_id'] ?? '') === (string)$roomType['id'] ? 'selected' : '' ?>><?= esc($roomType['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm rounded-3" placeholder="Cari Kode atau Nama Ruangan..." value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 w-100">Filter</button>
                <a href="<?= base_url('rooms') ?>" class="btn btn-light btn-sm rounded-3">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <?php
        $currentPage = (int)($pager->getCurrentPage() ?? 1);
        $perPageVal = $perPage ?? '10';
        $itemsPerPage = $perPageVal === 'all' ? (count($rooms) ?: 1) : (int)$perPageVal;
        $totalRecords = $pager->getTotal() ?? count($rooms);
        $startNo = empty($rooms) ? 0 : (($currentPage - 1) * $itemsPerPage) + 1;
        $endNo = empty($rooms) ? 0 : min($startNo + count($rooms) - 1, $totalRecords);
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
                        return base_url('rooms') . '?' . http_build_query(array_filter($p, fn($v) => $v !== null && $v !== ''));
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
                        <th>Nama Ruangan</th>
                        <th>Unit</th>
                        <th>Jenis Ruang</th>
                        <th>Kapasitas</th>
                        <th>Lokasi/Lantai</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">Belum ada data ruangan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $idx => $r): ?>
                            <tr>
                                <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $startNo + $idx ?></td>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($r['code']) ?></span></td>
                                <td><span class="fw-bold text-slate-800"><?= esc($r['name']) ?></span></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($r['unit_name'] ?: 'Bersama') ?></span></td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill fs-8" title="<?= esc($r['room_type_code']) ?>"><?= esc($r['room_type_name']) ?></span>
                                </td>
                                <td><?= esc($r['capacity']) ?> Kursi</td>
                                <td><?= esc($r['location'] ?: '-') ?></td>
                                <td>
                                    <span class="badge <?= (int)$r['is_active'] === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-muted' ?> px-2.5 py-1 rounded-pill fs-8">
                                        <?= (int)$r['is_active'] === 1 ? 'Aktif' : 'Non-Aktif' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (has_permission('rooms.manage')): ?>
                                        <a href="<?= base_url('rooms/' . $r['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($perPageVal !== 'all' && !empty($rooms)): ?>
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
