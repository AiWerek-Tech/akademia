<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800 d-flex align-items-center gap-2">
            <i data-lucide="copy-check" class="text-warning" style="width: 24px; height: 24px;"></i>
            Review Duplikasi Master Data
        </h4>
        <p class="text-muted fs-7 mb-0">Deteksi dan resolusi potensi data ganda guru/master secara aman</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="d-flex gap-2">
            <a href="<?= base_url('duplicates?status=OPEN') ?>" class="btn btn-sm rounded-3 <?= $selectedStatus === 'OPEN' ? 'btn-warning' : 'btn-light' ?>">OPEN</a>
            <a href="<?= base_url('duplicates?status=RESOLVED') ?>" class="btn btn-sm rounded-3 <?= $selectedStatus === 'RESOLVED' ? 'btn-success' : 'btn-light' ?>">RESOLVED</a>
            <a href="<?= base_url('duplicates?status=ALL') ?>" class="btn btn-sm rounded-3 <?= $selectedStatus === 'ALL' ? 'btn-primary' : 'btn-light' ?>">SEMUA</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-uppercase text-muted fs-8 fw-bold">
                        <th>Entity</th>
                        <th>Confidence Score</th>
                        <th>Alasan Kemiripan</th>
                        <th>Status</th>
                        <th>Keputusan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groups)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada kasus duplikasi yang terdeteksi.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill"><?= esc($g['entity_type']) ?></span></td>
                                <td>
                                    <span class="fw-bold text-danger fs-7"><?= esc($g['confidence_score']) ?>%</span>
                                </td>
                                <td>
                                    <?php $reasons = json_decode($g['match_reasons_json'] ?? '[]', true); ?>
                                    <?php if (!empty($reasons)): ?>
                                        <ul class="mb-0 ps-3 fs-8">
                                            <?php foreach ($reasons as $r): ?>
                                                <li><?= esc($r) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted fs-8">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $g['status'] === 'OPEN' ? 'bg-warning text-dark' : 'bg-success' ?> px-2.5 py-1 rounded-pill fs-8">
                                        <?= esc($g['status']) ?>
                                    </span>
                                </td>
                                <td><span class="fw-semibold fs-8"><?= esc($g['decision']) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= base_url('duplicates/' . $g['uuid']) ?>" class="btn btn-sm btn-outline-primary rounded-3">
                                        Review Side-by-Side
                                    </a>
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
