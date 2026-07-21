<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= base_url('curriculum') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Kurikulum</a>
            <h1 class="h3 mb-0 text-gray-800 mt-1"><?= esc($version['name']) ?> (<?= esc($version['code']) ?>)</h1>
            <p class="text-muted small mb-0">Periode: <?= esc($version['period_name'] ?? '-') ?> | Revisi: v<?= $version['revision_number'] ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('curriculum/' . $version['uuid'] . '/reconciliation') ?>" class="btn btn-outline-info">
                <i class="bi bi-calculator me-1"></i> Rekonsiliasi JP
            </a>
            <?php if (has_permission('curriculum.export')): ?>
                <a href="<?= base_url('curriculum/' . $version['uuid'] . '/export') ?>" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Ekspor Excel
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Workflow Status & Actions Bar -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted me-2">Status Workflow:</span>
                <span class="badge bg-primary fs-6"><?= $version['workflow_status'] ?></span>
                <?php if ((int)$version['is_active'] === 1): ?>
                    <span class="badge bg-success ms-2"><i class="bi bi-check-circle me-1"></i> Versi Aktif</span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ($version['workflow_status'] === 'DRAFT' && has_permission('curriculum.validate')): ?>
                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/workflow/VALIDATE') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-info text-dark"><i class="bi bi-check2-square me-1"></i> Validasi Engine</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'VALIDATED' && has_permission('curriculum.review')): ?>
                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/workflow/REVIEWED') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-eye me-1"></i> Tandai Reviewed</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'REVIEWED' && has_permission('curriculum.approve')): ?>
                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/workflow/APPROVED') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-all me-1"></i> Setujui (Approve)</button>
                    </form>
                <?php endif; ?>

                <?php if ($version['workflow_status'] === 'APPROVED' && has_permission('curriculum.lock')): ?>
                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/workflow/LOCKED') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-lock me-1"></i> Kunci (Lock)</button>
                    </form>
                <?php endif; ?>

                <?php if (in_array($version['workflow_status'], ['APPROVED', 'LOCKED'], true) && (int)$version['is_active'] !== 1 && has_permission('curriculum.approve')): ?>
                    <form action="<?= base_url('curriculum/' . $version['uuid'] . '/workflow/ACTIVATE') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-star me-1"></i> Aktifkan Versi Ini</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Validation Summary Card -->
    <?php if (!empty($validation['results'])): ?>
        <div class="card border-warning shadow-sm mb-4">
            <div class="card-header bg-warning bg-opacity-10 fw-bold">
                <i class="bi bi-exclamation-triangle me-1"></i> Hasil Validasi Engine (<?= $validation['total_results'] ?> Catatan)
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($validation['results'], 0, 5) as $res): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?= $res['severity'] === 'ERROR' ? 'danger' : ($res['severity'] === 'BLOCKER' ? 'dark' : 'warning text-dark') ?> me-2"><?= $res['severity'] ?></span>
                                <span class="fw-bold me-2">[<?= $res['validation_code'] ?>]</span>
                                <?= esc($res['message']) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Structure Matrix -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-gray-800"><i class="bi bi-grid-3x3-gap me-2"></i> Matriks Struktur Kurikulum</h5>
            <?php if (!in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true) && has_permission('curriculum.manage')): ?>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStructureModal">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Mapel
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Unit</th>
                            <th>Tingkat</th>
                            <th>Scope / Override</th>
                            <th>Kode Mapel</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Kategori</th>
                            <th>Jam Efektif</th>
                            <th>Sumber JP</th>
                            <th>Rapor</th>
                            <th>Beban</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($structures)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Belum ada struktur mata pelajaran pada versi ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($structures as $s): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= esc($s['unit_code'] ?? '-') ?></span></td>
                                    <td><?= esc($s['grade_code'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($s['classroom_name'])): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-pencil-square me-1"></i> Override: <?= esc($s['classroom_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">Default Tingkat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= esc($s['subject_code'] ?? '-') ?></td>
                                    <td><?= esc($s['subject_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-info text-dark"><?= esc($s['category']) ?></span></td>
                                    <td class="fw-bold text-primary"><?= number_format((float)$s['effective_weekly_hours'], 1) ?> JP</td>
                                    <td><span class="badge bg-secondary"><?= esc($s['effective_source']) ?></span></td>
                                    <td><?= (int)$s['counts_in_report'] === 1 ? 'Ya' : 'Tidak' ?></td>
                                    <td><?= (int)$s['counts_as_teaching_load'] === 1 ? 'Ya' : 'Tidak' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Structure -->
<?php if (!in_array($version['workflow_status'], ['LOCKED', 'ARCHIVED'], true) && has_permission('curriculum.manage')): ?>
<div class="modal fade" id="addStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= base_url('curriculum/' . $version['uuid'] . '/structures') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mata Pelajaran ke Kurikulum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Unit Sekolah <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <option value="">-- Pilih Unit --</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= esc($u['code']) ?> - <?= esc($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select name="grade_level_id" class="form-select" required>
                                <option value="">-- Pilih Tingkat --</option>
                                <?php foreach ($grades as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= esc($g['code']) ?> - <?= esc($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Classroom Override (Opsional)</label>
                            <select name="classroom_id" class="form-select">
                                <option value="">-- Default Tingkat (Semua Kelas) --</option>
                                <?php foreach ($classrooms as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>">[<?= esc($sub['code']) ?>] <?= esc($sub['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kategori Kegiatan</label>
                            <select name="category" class="form-select">
                                <option value="INTRAKURIKULER">INTRAKURIKULER</option>
                                <option value="MUATAN_LOKAL">MUATAN_LOKAL</option>
                                <option value="KOKURIKULER">KOKURIKULER</option>
                                <option value="EKSTRAKURIKULER">EKSTRAKURIKULER</option>
                                <option value="KEGIATAN_TETAP">KEGIATAN_TETAP</option>
                                <option value="PENGEMBANGAN_DIRI">PENGEMBANGAN_DIRI</option>
                                <option value="OTHER">OTHER</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sumber Jam Efektif <span class="text-danger">*</span></label>
                            <select name="effective_source" class="form-select" required>
                                <option value="OFFICIAL">OFFICIAL (Resmi)</option>
                                <option value="CUSTOM">CUSTOM (Penyesuaian)</option>
                                <option value="MANUAL">MANUAL (Manual)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Resmi (OFFICIAL)</label>
                            <input type="number" step="0.5" min="0" name="official_weekly_hours" class="form-control" placeholder="Misal: 4">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Custom / Manual</label>
                            <input type="number" step="0.5" min="0" name="custom_weekly_hours" class="form-control" placeholder="Misal: 2">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Alasan Penyesuaian (Wajib untuk CUSTOM/MANUAL)</label>
                            <input type="text" name="adjustment_reason" class="form-control" placeholder="Misal: Tambahan jam lokal sekolah">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mapel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
