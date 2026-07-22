<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <a href="<?= base_url('curriculum/' . $version['uuid']) ?>" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Kembali ke Versi Kurikulum</a>
        <h1 class="h3 mb-0 text-gray-800 mt-1">Dashboard Rekonsiliasi JP Kurikulum</h1>
        <p class="text-muted small mb-0">Versi: <?= esc($version['name']) ?> (<?= esc($version['code']) ?>)</p>
    </div>

    <!-- Grand Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary bg-opacity-10 text-primary">
                <div class="card-body">
                    <div class="small text-uppercase fw-bold">Grand Total Official</div>
                    <div class="fs-3 fw-bold mt-1"><?= number_format((float)$recon['summary']['grand_official'], 1) ?> JP</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info bg-opacity-10 text-info">
                <div class="card-body">
                    <div class="small text-uppercase fw-bold">Grand Total Custom</div>
                    <div class="fs-3 fw-bold mt-1"><?= number_format((float)$recon['summary']['grand_custom'], 1) ?> JP</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success bg-opacity-10 text-success">
                <div class="card-body">
                    <div class="small text-uppercase fw-bold">Grand Total Efektif</div>
                    <div class="fs-3 fw-bold mt-1"><?= number_format((float)$recon['summary']['grand_effective'], 1) ?> JP</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-danger bg-opacity-10 text-danger">
                <div class="card-body">
                    <div class="small text-uppercase fw-bold">Total Error / Unresolved</div>
                    <div class="fs-3 fw-bold mt-1"><?= $recon['summary']['grand_errors'] ?> Issue</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Reconciliation per Grade -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-gray-800"><i class="bi bi-calculator me-2"></i> Rekonsiliasi Jam Mingguan per Tingkat</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Unit</th>
                            <th>Tingkat Kelas</th>
                            <th>Mapel</th>
                            <th>Total Official</th>
                            <th>Total Custom</th>
                            <th>Total Manual</th>
                            <th>Total Efektif</th>
                            <th>Warning</th>
                            <th>Error</th>
                            <th>Status Rekonsiliasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recon['reconciliation'] as $r): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= esc($r['unit_code']) ?></span></td>
                                <td class="fw-bold"><?= esc($r['grade_code']) ?> - <?= esc($r['grade_name']) ?></td>
                                <td><?= $r['subject_count'] ?> Mapel</td>
                                <td><?= number_format((float)$r['total_official'], 1) ?> JP</td>
                                <td><?= number_format((float)$r['total_custom'], 1) ?> JP</td>
                                <td><?= number_format((float)$r['total_manual'], 1) ?> JP</td>
                                <td class="fw-bold text-primary"><?= number_format((float)$r['total_effective'], 1) ?> JP</td>
                                <td><span class="badge bg-warning text-dark"><?= $r['warning_count'] ?></span></td>
                                <td><span class="badge bg-danger"><?= $r['error_count'] ?></span></td>
                                <td>
                                    <?php
                                    $stBadge = match ($r['status']) {
                                        'MATCHED'            => 'bg-success',
                                        'DIFFERENT_ACCEPTED' => 'bg-info text-dark',
                                        'NEEDS_REVIEW'       => 'bg-warning text-dark',
                                        'ERROR'              => 'bg-danger',
                                        default              => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $stBadge ?>"><?= $r['status'] ?></span>
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
