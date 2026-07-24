<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <a href="<?= base_url('imports/master') ?>" class="text-decoration-none fs-8 d-inline-flex align-items-center gap-1 mb-2"><i data-lucide="arrow-left" style="width:14px"></i> Kembali ke Import Master</a>
        <h4 class="fw-bold text-slate-800 mb-1">Review Batch #<?= esc($batch['id']) ?></h4>
        <p class="text-muted fs-7 mb-0"><?= esc($typeLabel) ?> • <?= esc($batch['source_filename']) ?> • <?= number_format((int)$batch['source_size'] / 1024, 1) ?> KB</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('imports/master/template/' . strtolower($batch['import_type'])) ?>" class="btn btn-outline-success btn-sm rounded-3"><i data-lucide="download" style="width:14px"></i> Template terbaru</a>
        <?php if ($batch['status'] === 'VALIDATED' && $actionableRows > 0): ?>
            <form method="POST" action="<?= base_url('imports/master/' . $batch['uuid'] . '/apply') ?>" id="applyBatchForm"
                  data-confirm="Terapkan <?= esc($actionableRows) ?> baris yang siap ke database utama? Seluruh data akan diproses dalam satu transaksi."
                  data-confirm-title="Terapkan data import?"
                  data-confirm-button="Terapkan sekarang"
                  data-loading-text="Menerapkan data...">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm rounded-3" id="applyBatchButton"><i data-lucide="database-zap" style="width:14px"></i> Terapkan <?= esc($actionableRows) ?> Baris</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?><div class="alert alert-success border-0 rounded-3"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger border-0 rounded-3"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total', $batch['total_rows'], 'slate-800', 'rows-3'],
        ['Siap diterapkan', $actionableRows, 'primary', 'database'],
        ['Valid', $batch['valid_rows'], 'success', 'circle-check'],
        ['Warning', $batch['warning_rows'], 'warning', 'triangle-alert'],
        ['Error', $batch['error_rows'], 'danger', 'circle-x'],
    ] as $card): ?>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3"><div class="d-flex justify-content-between align-items-start"><div><span class="text-muted fs-8 d-block"><?= esc($card[0]) ?></span><strong class="fs-4 text-<?= esc($card[2]) ?>"><?= esc($card[1]) ?></strong></div><i data-lucide="<?= esc($card[3]) ?>" class="text-<?= esc($card[2]) ?>" style="width:20px"></i></div></div></div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ((int)$batch['error_rows'] > 0): ?>
    <div class="alert alert-warning border-0 rounded-3 d-flex gap-2"><i data-lucide="info" class="flex-shrink-0" style="width:18px"></i><div><strong>Baris error tidak akan diterapkan.</strong> Perbaiki file sumber berdasarkan pesan di bawah, lalu upload sebagai file baru. Baris valid tetap dapat diterapkan.</div></div>
<?php elseif ((int)$batch['warning_rows'] > 0): ?>
    <div class="alert alert-info border-0 rounded-3 d-flex gap-2"><i data-lucide="info" class="flex-shrink-0" style="width:18px"></i><div>Warning UPDATE akan memperbarui data yang cocok. Kandidat duplikat guru yang membutuhkan MERGE otomatis ditahan dan tidak diterapkan.</div></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h5 class="fw-bold text-slate-800 mb-1">Pratinjau Data Staging</h5><p class="text-muted fs-8 mb-0">Nomor baris mengikuti posisi asli pada file Excel.</p></div>
        <div class="btn-group btn-group-sm" role="group">
            <?php foreach (['' => 'Semua', 'VALID' => 'Valid', 'WARNING' => 'Warning', 'ERROR' => 'Error'] as $value => $label): ?>
                <a href="<?= current_url() . ($value !== '' ? '?status=' . $value : '') ?>" class="btn <?= $statusFilter === $value ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light position-sticky top-0"><tr class="text-uppercase text-muted fs-9"><th class="ps-4">Baris</th><?php foreach ($reviewColumns as $label): ?><th><?= esc($label) ?></th><?php endforeach; ?><th>Validasi</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="<?= count($reviewColumns) + 3 ?>" class="text-center text-muted py-5">Tidak ada baris untuk filter ini.</td></tr><?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php $raw = json_decode($row['raw_data_json'], true) ?: []; $messages = json_decode($row['validation_messages_json'], true) ?: []; ?>
                    <tr class="<?= $row['validation_status'] === 'ERROR' ? 'table-danger' : ($row['validation_status'] === 'WARNING' ? 'table-warning' : '') ?>">
                        <td class="ps-4"><span class="font-monospace fw-bold"><?= esc($row['row_number']) ?></span></td>
                        <?php foreach ($reviewColumns as $key => $label): ?>
                            <td><span class="d-inline-block text-truncate" style="max-width:190px" title="<?= esc((string)($raw[$key] ?? '')) ?>"><?= esc(($raw[$key] ?? '') !== '' ? (string)$raw[$key] : '—') ?></span></td>
                        <?php endforeach; ?>
                        <td style="min-width:260px">
                            <span class="badge rounded-pill <?= $row['validation_status'] === 'VALID' ? 'bg-success' : ($row['validation_status'] === 'WARNING' ? 'bg-warning text-dark' : 'bg-danger') ?> mb-1"><?= esc($row['validation_status']) ?></span>
                            <?php if ($messages): ?><ul class="small mb-0 ps-3"><?php foreach ($messages as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul><?php else: ?><span class="small text-success d-block">Data siap diterapkan.</span><?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= esc($row['admin_decision'] ?: $row['proposed_action']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 p-4"><?= $pager->links() ?></div>
</div>

<?= $this->endSection() ?>
