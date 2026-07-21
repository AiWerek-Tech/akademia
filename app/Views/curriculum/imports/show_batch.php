<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= base_url('curriculum/imports') ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Kembali ke List Import</a>
            <h1 class="h3 mb-0 text-gray-800 mt-1">Detail Import Batch #<?= $batch['id'] ?></h1>
            <p class="text-muted small mb-0">File: <?= esc($batch['source_filename']) ?> | Versi Target: <?= esc($version['name'] ?? '-') ?></p>
        </div>
        <div>
            <?php if ($batch['status'] === 'VALIDATED'): ?>
                <form action="<?= base_url('curriculum/imports/' . $batch['uuid'] . '/apply') ?>" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success" onclick="return confirm('Terapkan batch import ini ke database kurikulum?')">
                        <i class="bi bi-check-circle me-1"></i> Terapkan ke Kurikulum (Apply)
                    </button>
                </form>
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

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Total Baris</div>
                    <div class="fs-4 fw-bold"><?= $batch['total_rows'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 text-success">
                <div class="card-body">
                    <div class="small">Valid Rows</div>
                    <div class="fs-4 fw-bold"><?= $batch['valid_rows'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 text-warning">
                <div class="card-body">
                    <div class="small">Warning Rows</div>
                    <div class="fs-4 fw-bold"><?= $batch['warning_rows'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10 text-danger">
                <div class="card-body">
                    <div class="small">Error Rows (Dilewati)</div>
                    <div class="fs-4 fw-bold"><?= $batch['error_rows'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rows Staging Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-gray-800"><i class="bi bi-table me-2"></i> Data Staging Import</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Baris</th>
                            <th>Unit</th>
                            <th>Tingkat</th>
                            <th>Kode Mapel</th>
                            <th>Kategori</th>
                            <th>Jam Efektif</th>
                            <th>Sumber</th>
                            <th>Status Validasi</th>
                            <th>Pesan Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php $msgs = json_decode($r['validation_messages_json'], true) ?? []; ?>
                            <tr>
                                <td>#<?= $r['row_number'] ?></td>
                                <td><span class="badge bg-secondary"><?= esc($r['source_unit']) ?></span></td>
                                <td><?= esc($r['source_grade']) ?></td>
                                <td class="fw-bold"><?= esc($r['source_subject']) ?></td>
                                <td><?= esc($r['category'] ?? '-') ?></td>
                                <td class="fw-bold text-primary"><?= number_format((float)($r['official_hours'] ?? 0), 1) ?> JP</td>
                                <td><?= esc($r['effective_source'] ?? 'OFFICIAL') ?></td>
                                <td>
                                    <span class="badge bg-<?= $r['validation_status'] === 'VALID' ? 'success' : ($r['validation_status'] === 'WARNING' ? 'warning text-dark' : 'danger') ?>">
                                        <?= $r['validation_status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($msgs)): ?>
                                        <small class="text-danger"><?= esc(implode('; ', $msgs)) ?></small>
                                    <?php else: ?>
                                        <small class="text-success"><i class="bi bi-check me-1"></i> Siap Di-import</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
