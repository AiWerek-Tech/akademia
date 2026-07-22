<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Penugasan Mengajar</h1>
            <p class="text-muted small mb-0">Kelola distribusi penugasan guru ke kelas-kelas serta monitoring beban kerja</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (has_permission('assignments.import')): ?>
                <a href="<?= base_url('assignments/imports') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-upload me-1"></i> Impor Staging
                </a>
            <?php endif; ?>
            <?php if (has_permission('assignments.manage')): ?>
                <a href="<?= base_url('assignments/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Buat Versi Penugasan
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

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Periode Akademik</label>
                    <select name="academic_period_id" class="form-select">
                        <option value="">Semua Periode</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($filters['academic_period_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= esc($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status Workflow</label>
                    <select name="workflow_status" class="form-select">
                        <option value="">Semua Status</option>
                        <?php foreach (['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED', 'LOCKED', 'ARCHIVED', 'REJECTED'] as $st): ?>
                            <option value="<?= esc($st) ?>" <?= ($filters['workflow_status'] == $st) ? 'selected' : '' ?>><?= esc($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Cari Kode / Nama</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= esc($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Versi</th>
                            <th>Nama Versi</th>
                            <th>Periode Akademik</th>
                            <th>Kurikulum Target</th>
                            <th>Revisi</th>
                            <th>Status Workflow</th>
                            <th>Status Aktif</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($versions)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada versi penugasan yang terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($versions as $v): ?>
                                <tr>
                                    <td class="fw-bold"><?= esc($v['code']) ?></td>
                                    <td><?= esc($v['name']) ?></td>
                                    <td><?= esc($v['period_name'] ?? '-') ?></td>
                                    <td><?= esc($v['curriculum_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary">v<?= esc($v['revision_number']) ?></span></td>
                                    <td>
                                        <?php
                                        $badgeClass = match ($v['workflow_status']) {
                                            'DRAFT'     => 'bg-secondary',
                                            'VALIDATED' => 'bg-info text-dark',
                                            'REVIEWED'  => 'bg-primary',
                                            'APPROVED'  => 'bg-success',
                                            'LOCKED'    => 'bg-dark',
                                            'REJECTED'  => 'bg-danger',
                                            default     => 'bg-light text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= esc($v['workflow_status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ((int)$v['is_active'] === 1): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Non-aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= base_url('assignments/' . $v['uuid']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i> Detail / Matriks
                                        </a>
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
