<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('assignments/imports') ?>">Impor Penugasan</a></li>
                <li class="breadcrumb-item active">Rincian Staging</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Tinjau Data Staging Impor</h1>
                <p class="text-muted small mb-0">Berkas: <strong><?= esc($batch['source_filename']) ?></strong> | Status: <strong><?= esc($batch['status']) ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($batch['status'] === 'VALIDATED' && (int)$batch['error_rows'] === 0): ?>
                    <form method="post" action="<?= base_url('assignments/imports/' . $batch['uuid'] . '/apply') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success btn-sm">Terapkan Ke Database</button>
                    </form>
                <?php endif; ?>

                <?php if ($batch['status'] === 'APPLIED'): ?>
                    <form method="post" action="<?= base_url('assignments/imports/' . $batch['uuid'] . '/rollback') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-sm">Rollback Data</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 small fw-bold text-uppercase text-secondary">Statistik Baris Berkas</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3 border-end">
                    <h2 class="fw-bold mb-0 text-primary"><?= esc($batch['total_rows']) ?></h2>
                    <span class="text-muted small">Total Baris</span>
                </div>
                <div class="col-md-3 border-end">
                    <h2 class="fw-bold mb-0 text-success"><?= esc($batch['valid_rows']) ?></h2>
                    <span class="text-muted small">Baris Valid</span>
                </div>
                <div class="col-md-3 border-end">
                    <h2 class="fw-bold mb-0 text-warning"><?= esc($batch['warning_rows']) ?></h2>
                    <span class="text-muted small">Peringatan (Warnings)</span>
                </div>
                <div class="col-md-3">
                    <h2 class="fw-bold mb-0 text-danger"><?= esc($batch['error_rows']) ?></h2>
                    <span class="text-muted small">Baris Error</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Baris</th>
                            <th>Unit</th>
                            <th>Tingkat</th>
                            <th>Rombel</th>
                            <th>Kode Mapel</th>
                            <th>Identifier Guru</th>
                            <th>Jam (JP)</th>
                            <th>Status Validasi</th>
                            <th>Pesan Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= esc($row['row_number']) ?></td>
                                <td><?= esc($row['source_unit']) ?></td>
                                <td><?= esc($row['source_grade']) ?></td>
                                <td><?= esc($row['source_classroom']) ?></td>
                                <td><?= esc($row['source_subject']) ?></td>
                                <td><?= esc($row['source_teacher']) ?></td>
                                <td><?= esc($row['assigned_weekly_hours']) ?> JP</td>
                                <td>
                                    <?php
                                    $badge = match ($row['validation_status']) {
                                        'VALID'   => 'bg-success',
                                        'WARNING' => 'bg-warning text-dark',
                                        'ERROR'   => 'bg-danger',
                                        default   => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= esc($row['validation_status']) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $msgs = json_decode($row['validation_messages_json'], true) ?? [];
                                    if (empty($msgs)):
                                    ?>
                                        <span class="text-success"><i class="bi bi-check2-circle"></i> Bersih</span>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-0 text-danger">
                                            <?php foreach ($msgs as $m): ?>
                                                <li>• <?= esc($m) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
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
