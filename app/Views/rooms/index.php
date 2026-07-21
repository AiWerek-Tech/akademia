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
                <?php if (has_permission('rooms.export')): ?>
                    <a href="<?= base_url('rooms/export?unit_id=' . ($filters['unit_id'] ?? '')) ?>" class="btn btn-outline-success rounded-3 btn-sm px-3 d-flex align-items-center gap-2">
                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                        <span>Export Excel</span>
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
                <select name="type" class="form-select form-select-sm rounded-3">
                    <option value="">-- Semua Jenis Ruang --</option>
                    <option value="TEORITIS" <?= ($filters['type'] ?? '') === 'TEORITIS' ? 'selected' : '' ?>>Teoritis / Kelas Biasa</option>
                    <option value="LABORATORIUM" <?= ($filters['type'] ?? '') === 'LABORATORIUM' ? 'selected' : '' ?>>Laboratorium / Praktek</option>
                    <option value="PERPUSTAKAAN" <?= ($filters['type'] ?? '') === 'PERPUSTAKAAN' ? 'selected' : '' ?>>Perpustakaan</option>
                    <option value="LAINNYA" <?= ($filters['type'] ?? '') === 'LAINNYA' ? 'selected' : '' ?>>Lainnya</option>
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
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold">
                        <th>Kode</th>
                        <th>Nama Ruangan</th>
                        <th>Unit</th>
                        <th>Jenis Ruang</th>
                        <th>Kapasitas</th>
                        <th>Lokasi/Lantai</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Belum ada data ruangan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                            <tr>
                                <td><span class="fw-bold font-monospace text-primary fs-7"><?= esc($r['code']) ?></span></td>
                                <td><span class="fw-bold text-slate-800"><?= esc($r['name']) ?></span></td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8"><?= esc($r['unit_name']) ?></span></td>
                                <td>
                                    <?php if ($r['type'] === 'TEORITIS'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill fs-8">Teoritis</span>
                                    <?php elseif ($r['type'] === 'LABORATORIUM'): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill fs-8">Laboratorium</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1 rounded-pill fs-8"><?= esc($r['type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($r['capacity']) ?> Kursi</td>
                                <td><?= esc($r['location'] ?: '-') ?></td>
                                <td>
                                    <span class="badge <?= (int)$r['is_active'] === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-light text-muted' ?> px-2.5 py-1 rounded-pill fs-8">
                                        <?= (int)$r['is_active'] === 1 ? 'Aktif' : 'Non-Aktif' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if (has_permission('rooms.manage')): ?>
                                        <a href="<?= base_url('rooms/' . $r['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary rounded-3">Edit</a>
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
