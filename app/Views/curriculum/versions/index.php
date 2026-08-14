<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php
$activeCount = count(array_filter($versions, static fn ($item) => (int) $item['is_active'] === 1));
?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <div class="text-primary fw-semibold small text-uppercase mb-1">Perencanaan</div>
            <h1 class="h3 mb-1 text-gray-800">Struktur Kurikulum</h1>
            <p class="text-muted mb-0">Atur mata pelajaran dan jam pelajaran, lalu aktifkan kurikulum dengan satu klik.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (has_permission('curriculum.import')): ?>
                <a href="<?= base_url('curriculum/imports') ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-arrow-up me-1"></i> Import Excel</a>
            <?php endif; ?>
            <?php if (has_permission('curriculum.manage')): ?>
                <a href="<?= base_url('curriculum/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Buat Kurikulum</a>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $type): ?>
        <?php if ($message = session()->getFlashdata($key)): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert"><?= esc($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Total kurikulum</div><div class="fs-3 fw-bold"><?= count($versions) ?></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Sedang aktif</div><div class="fs-3 fw-bold text-success"><?= $activeCount ?></div></div></div></div>
        <div class="col-xl-6"><div class="alert alert-primary border-0 h-100 mb-0 d-flex align-items-center"><i class="bi bi-lightbulb fs-4 me-3"></i><div><strong>Alur singkat:</strong> buat kurikulum, isi manual atau import Excel, kemudian klik <strong>Aktifkan</strong>.</div></div></div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-lg-5"><label class="form-label fw-semibold">Cari kurikulum</label><input type="search" name="search" class="form-control" placeholder="Kode atau nama kurikulum" value="<?= esc($filters['search'] ?? '') ?>"></div>
                <div class="col-md-4 col-lg-3"><label class="form-label fw-semibold">Periode</label><select name="academic_period_id" class="form-select"><option value="">Semua periode</option><?php foreach ($periods as $period): ?><option value="<?= (int) $period['id'] ?>" <?= (string) ($filters['academic_period_id'] ?? '') === (string) $period['id'] ? 'selected' : '' ?>><?= esc(($period['year_name'] ?? '') . ' · ' . $period['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4 col-lg-2"><label class="form-label fw-semibold">Status</label><select name="is_active" class="form-select"><option value="">Semua</option><option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktif</option><option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Belum aktif</option></select></div>
                <div class="col-md-4 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Cari</button><a href="<?= base_url('curriculum') ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a></div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="text-center ps-3" style="width: 55px;">No.</th><th>Kurikulum</th><th>Periode</th><th>Status</th><th>Dibuat oleh</th><th class="text-end pe-4">Aksi</th></tr></thead>
                <tbody>
                <?php if ($versions === []): ?>
                    <tr><td colspan="6" class="text-center py-5"><i class="bi bi-journal-x fs-1 text-muted"></i><h5 class="mt-3">Belum ada kurikulum</h5><p class="text-muted">Buat kurikulum pertama untuk mulai menyusun mata pelajaran.</p></td></tr>
                <?php else: foreach ($versions as $idx => $version): ?>
                    <tr>
                        <td class="text-center fw-semibold text-secondary fs-8 ps-3"><?= $idx + 1 ?></td>
                        <td><div class="fw-semibold"><?= esc($version['name']) ?></div><div class="small text-muted"><?= esc($version['code']) ?> · Revisi <?= (int) $version['revision_number'] ?></div></td>
                        <td><?= esc(($version['year_name'] ?? '') . ' · ' . ($version['period_name'] ?? '-')) ?></td>
                        <td><?php if ((int) $version['is_active'] === 1): ?><span class="badge rounded-pill bg-success"><i class="bi bi-check-circle me-1"></i>Aktif</span><?php else: ?><span class="badge rounded-pill bg-light text-dark border">Belum aktif</span><?php endif; ?></td>
                        <td><?= esc($version['creator_name'] ?? '-') ?></td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                <a href="<?= base_url('curriculum/' . $version['uuid']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-grid-3x3-gap me-1"></i>Kelola struktur</a>
                                <?php if (has_permission('curriculum.manage')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $version['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</button>
                                <?php endif; ?>
                                <?php if ((int) $version['is_active'] !== 1 && (has_permission('curriculum.manage') || has_permission('curriculum.approve'))): ?>
                                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/activate') ?>" method="post" data-confirm="Aktifkan kurikulum ini untuk periode <?= esc($version['period_name'] ?? '') ?>?" data-confirm-title="Aktifkan kurikulum?" data-confirm-button="Aktifkan"><?= csrf_field() ?><button class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Aktifkan</button></form>
                                <?php endif; ?>
                            </div>

                            <!-- Modal Edit Kurikulum -->
                            <?php if (has_permission('curriculum.manage')): ?>
                            <div class="modal fade text-start" id="editModal<?= $version['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 rounded-4 shadow">
                                        <form action="<?= base_url('curriculum/' . $version['uuid'] . '/update') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <div class="modal-header border-0 px-4 pt-4">
                                                <div>
                                                    <h5 class="modal-title fw-bold">Edit Versi Kurikulum</h5>
                                                    <div class="text-muted small">Ubah nama, kode, periode, atau status kurikulum.</div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <div class="row g-3">
                                                    <div class="col-md-8">
                                                        <label class="form-label fw-semibold">Nama Kurikulum <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="name" required value="<?= esc($version['name']) ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control text-uppercase" name="code" required value="<?= esc($version['code']) ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Periode Akademik <span class="text-danger">*</span></label>
                                                        <select name="academic_period_id" class="form-select" required>
                                                            <?php foreach ($periods as $p): ?>
                                                                <option value="<?= (int)$p['id'] ?>" <?= (int)$version['academic_period_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                                                    <?= esc(($p['year_name'] ?? '') . ' · ' . $p['name']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label fw-semibold">Status Alur Kerja</label>
                                                        <select name="workflow_status" class="form-select">
                                                            <option value="DRAFT" <?= strtoupper($version['workflow_status']) === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                                                            <option value="REVIEW" <?= strtoupper($version['workflow_status']) === 'REVIEW' ? 'selected' : '' ?>>REVIEW</option>
                                                            <option value="APPROVED" <?= strtoupper($version['workflow_status']) === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                                                            <option value="LOCKED" <?= strtoupper($version['workflow_status']) === 'LOCKED' ? 'selected' : '' ?>>LOCKED</option>
                                                            <option value="ARCHIVED" <?= strtoupper($version['workflow_status']) === 'ARCHIVED' ? 'selected' : '' ?>>ARCHIVED</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label fw-semibold">Status Keaktifan</label>
                                                        <select name="is_active" class="form-select">
                                                            <option value="1" <?= (int)$version['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                            <option value="0" <?= (int)$version['is_active'] === 0 ? 'selected' : '' ?>>Belum aktif</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Deskripsi / Catatan</label>
                                                        <textarea class="form-control" name="description" rows="3"><?= esc($version['description'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 px-4 pb-4">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
