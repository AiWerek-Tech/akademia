<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h5 class="fw-bold mb-1">KSP Digital Regulation Compliance Checker</h5>
            <p class="text-muted small mb-0">Evaluasi otomatis pemenuhan regulasi nasional (Permendikdasmen 13/2025, Pedoman Pembelajaran Mendalam 126/P/2025) terhadap data KSP.</p>
        </div>
        <?php if (has_permission('ksp.review')): ?>
            <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/compliance') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i data-lucide="play-circle" class="me-1" style="width: 18px; height: 18px;"></i> Jalankan Preview Kepatuhan
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($preview): ?>
    <?php
    $run = $preview['run'];
    $overall = strtoupper($run['overall_status'] ?? 'NOT_APPLICABLE');
    $statusColor = match($overall) {
        'PASS' => 'success',
        'FAIL' => 'danger',
        'WARNING' => 'warning',
        default => 'secondary'
    };
    $statusTitle = match($overall) {
        'PASS' => 'KSP Telah Patuh Regulasi Nasional (Compliant)',
        'FAIL' => 'Terdapat Pelanggaran Aturan Wajib (Blocker / Non-Compliant)',
        'WARNING' => 'Terdapat Catatan Panduan Rekomendasi (Warning)',
        default => 'Evaluasi Belum Dapat Ditentukan (Not Applicable)'
    };
    ?>

    <!-- Status Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, rgba(var(--bs-<?= $statusColor ?>-rgb), 0.1) 0%, rgba(var(--bs-<?= $statusColor ?>-rgb), 0.02) 100%); border-left: 6px solid var(--bs-<?= $statusColor ?>) !important;">
        <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-<?= $statusColor ?> text-white rounded-pill px-3 py-1 font-monospace fs-6">
                        STATUS: <?= esc($overall) ?>
                    </span>
                    <span class="small text-muted font-monospace">Revision: <?= esc($run['source_revision']) ?></span>
                </div>
                <h4 class="fw-bold text-dark mb-1"><?= esc($statusTitle) ?></h4>
                <div class="small text-muted">Dievaluasi pada: <?= esc($run['generated_at']) ?></div>
            </div>
            <div class="text-md-end">
                <span class="fs-3 fw-bold font-monospace text-<?= $statusColor ?>"><?= (int)$run['pass_count'] ?> / <?= (int)$run['total_count'] ?></span>
                <div class="small text-muted">Aturan Terpenuhi</div>
            </div>
        </div>
    </div>

    <!-- 4 Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                        <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-success"><?= (int)$run['pass_count'] ?></div>
                        <div class="small text-muted">Lulus (Pass)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                        <i data-lucide="alert-triangle" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-warning"><?= (int)$run['warning_count'] ?></div>
                        <div class="small text-muted">Peringatan (Warning)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3">
                        <i data-lucide="x-circle" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-danger"><?= (int)$run['fail_count'] ?></div>
                        <div class="small text-muted">Gagal (Fail / Blocker)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary p-3">
                        <i data-lucide="slash" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-secondary"><?= (int)$run['not_applicable_count'] ?></div>
                        <div class="small text-muted">Tidak Berlaku</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rules Breakdown Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Rincian Evaluasi Seluruh Aturan Regulasi</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Kode Aturan & Severity</th>
                            <th>Klasifikasi Aturan</th>
                            <th>Rujukan Regulasi</th>
                            <th>Hasil Evaluasi</th>
                            <th>Ekspektasi vs Aktual</th>
                            <th class="pe-4">Rekomendasi Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['results'] as $row): ?>
                            <?php
                            $resSt = strtoupper($row['result_status'] ?? 'NOT_APPLICABLE');
                            $resBadge = match($resSt) {
                                'PASS' => 'bg-success text-white',
                                'FAIL' => 'bg-danger text-white',
                                'WARNING' => 'bg-warning text-dark',
                                default => 'bg-secondary text-white'
                            };
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold font-monospace text-dark"><?= esc($row['rule_code']) ?></div>
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                        <?= esc($row['severity']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small fw-semibold text-muted"><?= esc($row['rule_class']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-primary"><?= esc($row['regulation_version']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?= esc($row['source_reference']) ?>">
                                        <?= esc($row['source_reference']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $resBadge ?> rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($resSt) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-success"><span class="text-muted">Expected:</span> <?= esc($row['expected']) ?></div>
                                        <div class="<?= $resSt === 'FAIL' ? 'text-danger fw-bold' : 'text-dark' ?>"><span class="text-muted">Actual:</span> <?= esc($row['actual']) ?></div>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <div class="small text-muted" style="max-width: 280px;">
                                        <?= esc($row['suggested_action']) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body text-center text-muted py-5">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-4 d-inline-flex mb-3">
                <i data-lucide="shield-alert" style="width: 40px; height: 40px;"></i>
            </div>
            <h5 class="fw-bold text-dark">Belum Ada Riwayat Kepatuhan</h5>
            <p class="small text-muted mb-3" style="max-width: 450px; margin: 0 auto;">Jalankan evaluasi kepatuhan untuk memverifikasi kesesuaian dokumen KSP dengan regulasi nasional aktif.</p>
            <?php if (has_permission('ksp.review')): ?>
                <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/compliance') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i data-lucide="play" class="me-1" style="width: 16px; height: 16px;"></i> Jalankan Preview Sekarang
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
