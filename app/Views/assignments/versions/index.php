<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">Penugasan Mengajar</h4>
            <p class="text-muted mb-0 small">Kelola distribusi penugasan guru ke rombel kelas serta pemantauan beban kerja mengajar.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (has_permission('assignments.import')): ?>
                <a href="<?= base_url('assignments/imports') ?>" class="btn btn-outline-primary rounded-3 px-3 py-2 fw-semibold">
                    <i class="bi bi-upload me-1"></i> Impor Staging
                </a>
            <?php endif; ?>
            <?php if (has_permission('assignments.manage')): ?>
                <a href="<?= base_url('assignments/create') ?>" class="btn btn-primary rounded-3 px-3 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Versi Penugasan
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div><?= esc(session()->getFlashdata('success')) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= esc(session()->getFlashdata('error')) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Stat Widgets -->
<?php
$totalCount = count($versions);
$activeCount = 0;
$draftCount = 0;
foreach ($versions as $vItem) {
    if ((int)($vItem['is_active'] ?? 0) === 1) $activeCount++;
    if (($vItem['workflow_status'] ?? '') === 'DRAFT') $draftCount++;
}
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-folder2-open fs-4"></i>
            </div>
            <div>
                <div class="small text-muted fw-semibold">Total Versi Penugasan</div>
                <div class="fs-4 fw-bold text-dark mt-0"><?= $totalCount ?> <span class="fs-8 font-normal text-muted">Versi</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-check-circle fs-4"></i>
            </div>
            <div>
                <div class="small text-muted fw-semibold">Versi Aktif Saat Ini</div>
                <div class="fs-4 fw-bold text-success mt-0"><?= $activeCount ?> <span class="fs-8 font-normal text-muted">Versi Aktif</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-row align-items-center gap-3">
            <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                <i class="bi bi-pencil-square fs-4"></i>
            </div>
            <div>
                <div class="small text-muted fw-semibold">Versi Draf / Proses</div>
                <div class="fs-4 fw-bold text-warning mt-0"><?= $draftCount ?> <span class="fs-8 font-normal text-muted">Draf</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Periode Akademik</label>
                <select name="period_id" class="form-select rounded-3">
                    <option value="">Semua Periode</option>
                    <?php foreach ($periods as $p): ?>
                        <?php
                        $pName = trim((string)($p['name'] ?? ''));
                        if ($pName === '') {
                            $semLabel = ((int)($p['semester_number'] ?? 1) === 1) ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
                            $pName = 'T.A ' . ($p['year_name'] ?? '') . ' · ' . $semLabel;
                        }
                        ?>
                        <option value="<?= $p['id'] ?>" <?= (($filters['period_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                            <?= esc($pName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Status Workflow</label>
                <select name="status" class="form-select rounded-3">
                    <option value="">Semua Status</option>
                    <?php foreach (['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED', 'ARCHIVED', 'REJECTED'] as $st): ?>
                        <option value="<?= esc($st) ?>" <?= (($filters['status'] ?? '') == $st) ? 'selected' : '' ?>><?= esc($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">Cari Kode / Nama</label>
                <input type="text" name="search" class="form-control rounded-3" placeholder="Contoh: 01/SMP-SGO..." value="<?= esc($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100 py-2 fw-semibold shadow-sm"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold bg-light border-bottom">
                        <th class="ps-4" style="min-width: 170px;">Kode Versi</th>
                        <th style="min-width: 250px;">Nama Versi Penugasan</th>
                        <th>Periode Akademik</th>
                        <th>Kurikulum Target</th>
                        <th class="text-center">Revisi</th>
                        <th class="text-center">Workflow</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4" style="min-width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($versions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada versi penugasan yang terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($versions as $v): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="font-monospace fw-bold text-primary"><?= esc($v['code']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= esc($v['name']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark"><?= esc($v['period_name'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($v['curriculum_name'])): ?>
                                        <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1 fw-semibold">
                                            <i class="bi bi-journal-bookmark me-1"></i><?= esc($v['curriculum_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 fw-bold">v<?= esc($v['revision_number']) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $st = $v['workflow_status'];
                                    $badgeClass = match ($st) {
                                        'DRAFT'     => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle',
                                        'VALIDATED' => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                        'REVIEWED'  => 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
                                        'APPROVED'  => 'bg-success-subtle text-success-emphasis border-success-subtle',
                                        'LOCKED'    => 'bg-dark bg-opacity-10 text-dark border',
                                        'REJECTED'  => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
                                        default     => 'bg-light text-dark border',
                                    };
                                    ?>
                                    <span class="badge rounded-pill border px-3 py-1 fw-bold <?= $badgeClass ?>"><?= esc($st) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$v['is_active'] === 1): ?>
                                        <span class="badge rounded-pill bg-success text-white px-3 py-1 fw-bold"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-light text-muted border px-3 py-1 fw-semibold">Non-aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1 justify-content-end">
                                        <a href="<?= base_url('assignments/' . $v['uuid']) ?>" class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1.5 fw-semibold shadow-sm">
                                            <i class="bi bi-eye me-1"></i> Detail / Matriks
                                        </a>
                                        <?php if (has_permission('assignments.manage')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-3 px-3 py-1.5 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#editAssignmentModal<?= $v['id'] ?>">
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Modal Edit Penugasan Mengajar -->
                                    <?php if (has_permission('assignments.manage')): ?>
                                    <div class="modal fade text-start" id="editAssignmentModal<?= $v['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content border-0 rounded-4 shadow">
                                                <form action="<?= base_url('assignments/' . $v['uuid'] . '/update') ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <div class="modal-header border-0 px-4 pt-4">
                                                        <div>
                                                            <h5 class="modal-title fw-bold">Edit Versi Penugasan Mengajar</h5>
                                                            <div class="text-muted small">Ubah nama versi, kode SK, periode, kurikulum target, atau status.</div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body px-4">
                                                        <div class="row g-3">
                                                            <div class="col-md-8">
                                                                <label class="form-label fw-semibold">Nama Versi Penugasan <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" name="name" required value="<?= esc($v['name']) ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label fw-semibold">Kode Versi / No SK <span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control font-monospace" name="code" required value="<?= esc($v['code']) ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Periode Akademik <span class="text-danger">*</span></label>
                                                                <select name="academic_period_id" class="form-select" required>
                                                                    <?php foreach ($periods as $p): ?>
                                                                        <?php
                                                                        $pName = trim((string)($p['name'] ?? ''));
                                                                        if ($pName === '') {
                                                                            $semLabel = ((int)($p['semester_number'] ?? 1) === 1) ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)';
                                                                            $pName = 'T.A ' . ($p['year_name'] ?? '') . ' · ' . $semLabel;
                                                                        }
                                                                        ?>
                                                                        <option value="<?= (int)$p['id'] ?>" <?= (int)$v['academic_period_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                                                            <?= esc($pName) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Kurikulum Target <span class="text-danger">*</span></label>
                                                                <select name="curriculum_version_id" class="form-select" required>
                                                                    <?php foreach ($curriculums as $c): ?>
                                                                        <option value="<?= (int)$c['id'] ?>" <?= (int)$v['curriculum_version_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                                                            <?= esc($c['name']) ?> (<?= esc($c['code']) ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Status Workflow</label>
                                                                <select name="workflow_status" class="form-select">
                                                                    <?php foreach (['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED', 'ARCHIVED', 'REJECTED'] as $st): ?>
                                                                        <option value="<?= $st ?>" <?= strtoupper($v['workflow_status']) === $st ? 'selected' : '' ?>><?= $st ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Status Keaktifan</label>
                                                                <select name="is_active" class="form-select">
                                                                    <option value="1" <?= (int)$v['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                                                    <option value="0" <?= (int)$v['is_active'] === 0 ? 'selected' : '' ?>>Non-aktif</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label fw-semibold">Deskripsi / Catatan SK</label>
                                                                <textarea class="form-control" name="description" rows="3"><?= esc($v['description'] ?? '') ?></textarea>
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
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
