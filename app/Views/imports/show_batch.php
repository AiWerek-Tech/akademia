<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 text-slate-800">Review Batch Import</h4>
                <p class="text-muted fs-7 mb-0">Detail validasi baris data dari file <strong><?= esc($batch['source_filename']) ?></strong></p>
            </div>
            <div>
                <a href="<?= base_url('imports/master') ?>" class="btn btn-light btn-sm rounded-3">Kembali ke Riwayat</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-1">Total Baris</h6>
            <h3 class="fw-extrabold text-slate-800 mb-0"><?= esc($batch['total_rows']) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-1">Lolos Validasi (Valid)</h6>
            <h3 class="fw-extrabold text-success mb-0">
                <?= esc($batch['valid_rows'] ?? 0) ?>
            </h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-1">Warning / Duplikat</h6>
            <h3 class="fw-extrabold text-warning mb-0">
                <?= esc($batch['warning_rows'] ?? 0) ?>
            </h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3">
            <h6 class="text-muted fs-8 fw-bold text-uppercase mb-1">Gagal Validasi (Error)</h6>
            <h3 class="fw-extrabold text-danger mb-0">
                <?= esc($batch['error_rows'] ?? 0) ?>
            </h3>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold text-slate-800 mb-0">Pratinjau Baris Staging</h5>
            <?php if ($batch['status'] === 'VALIDATED'): ?>
                <div class="d-flex gap-2">
                    <form method="POST" action="<?= base_url('imports/master/' . $batch['uuid'] . '/apply') ?>" onsubmit="return confirm('Apakah Anda yakin ingin memproses baris yang lolos validasi ke database utama?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary rounded-3 px-4">Commit Data Valid</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold">
                        <th style="width: 50px;">No</th>
                        <th>Data Mentah (JSON)</th>
                        <th>Rencana Aksi</th>
                        <th>Kesalahan Validasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= esc($row['row_number']) ?></td>
                            <td>
                                <pre class="font-monospace fs-8 p-2 bg-light rounded mb-0" style="max-height: 120px; max-width: 400px; overflow-y: auto; overflow-x: auto; white-space: pre-wrap;"><?= esc(json_encode(json_decode($row['raw_data_json']), JSON_PRETTY_PRINT)) ?></pre>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-dark px-2.5 py-1 rounded-pill fs-8">
                                    <?= esc($row['proposed_action']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $messages = json_decode($row['validation_messages_json'], true) ?? [];
                                    if (empty($messages)): 
                                ?>
                                    <span class="text-success fs-8"><i data-lucide="check-circle-2" class="d-inline-block text-success me-1" style="width: 14px; height: 14px;"></i> Valid</span>
                                <?php else: ?>
                                    <div class="text-danger fs-8">
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($messages as $msg): ?>
                                                <li><?= esc($msg) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['validation_status'] === 'VALID'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-2.5 py-1 rounded-pill fs-8">Valid</span>
                                <?php elseif ($row['validation_status'] === 'WARNING'): ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-2.5 py-1 rounded-pill fs-8">Warning</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-2.5 py-1 rounded-pill fs-8">Error</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?= $pager->links() ?></div>
    </div>
</div>
<?= $this->endSection() ?>
