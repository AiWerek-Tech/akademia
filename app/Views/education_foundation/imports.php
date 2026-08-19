<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Staging Import Data Pendidikan'; 
$pageIcon = 'file-up'; 
$pageDescription = 'Validasi, preview, dan staging aman data kurikulum (CP, TP, ATP) sebelum diterapkan ke database utama.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalBatches = count($rows);
$readyBatches = count(array_filter($rows, fn($r) => strtoupper($r['status'] ?? '') === 'READY'));
$appliedBatches = count(array_filter($rows, fn($r) => strtoupper($r['status'] ?? '') === 'APPLIED'));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="layers" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalBatches ?></div>
                    <div class="small text-muted">Total Batch Import</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                    <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-warning"><?= $readyBatches ?></div>
                    <div class="small text-muted">Siap Diterapkan (Ready)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $appliedBatches ?></div>
                    <div class="small text-muted">Telah Diterapkan (Applied)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Riwayat Batch Staging</h5>
                <p class="small text-muted mb-0">Setiap data diverifikasi integritas skema dan perujukannya sebelum dieksekusi.</p>
            </div>
            <?php if (has_permission('learning_outcomes.manage')): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#stageImportModal">
                    <i data-lucide="upload-cloud" class="me-1" style="width: 18px; height: 18px;"></i> Stage Import Baru
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Berkas Sumber</th>
                        <th>Status Validasi</th>
                        <th>Baris Valid</th>
                        <th>Baris Error</th>
                        <th>Waktu Diunggah</th>
                        <th class="text-end pe-4">Aksi Eksekusi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                                        <i data-lucide="file-code" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold text-dark"><?= esc($row['source_filename']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $status = strtoupper($row['status'] ?? 'STAGED');
                                $badgeClass = match($status) {
                                    'APPLIED' => 'bg-success text-white',
                                    'READY' => 'bg-primary text-white',
                                    'ERROR', 'FAILED' => 'bg-danger text-white',
                                    default => 'bg-secondary bg-opacity-10 text-secondary border'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-1">
                                    <?= esc($status) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 font-monospace">
                                    <?= (int)$row['valid_rows'] ?> baris
                                </span>
                            </td>
                            <td>
                                <?php if ((int)$row['error_rows'] > 0): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 font-monospace">
                                        <?= (int)$row['error_rows'] ?> error
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-muted"><?= esc($row['created_at']) ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($row['status'] === 'READY' && has_permission('learning_outcomes.manage')): ?>
                                    <form method="post" action="<?= base_url('curriculum/education-imports/'.$row['uuid'].'/apply') ?>" onsubmit="return confirm('Apakah Anda yakin ingin menerapkan batch data ini ke database kurikulum utama?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                            <i data-lucide="check" class="me-1" style="width: 14px; height: 14px;"></i> Terapkan (Apply)
                                        </button>
                                    </form>
                                <?php elseif ($row['status'] === 'APPLIED'): ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                                        <i data-lucide="check-check" class="me-1 text-success" style="width: 13px; height: 13px;"></i>Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i data-lucide="folder-open" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                                Belum ada batch import data kurikulum.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (has_permission('learning_outcomes.manage')): ?>
<!-- Modal Stage Import -->
<div class="modal fade" id="stageImportModal" tabindex="-1" aria-labelledby="stageImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="upload-cloud" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="stageImportModalLabel">Stage Import Data Kurikulum (JSON)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('curriculum/education-imports') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unit Sekolah</label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= esc($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Versi Kurikulum</label>
                            <select class="form-select rounded-3" name="curriculum_version_id" required>
                                <?php foreach ($versions as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= esc($v['code'].' — '.$v['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama Berkas Sumber</label>
                            <input class="form-control rounded-3" name="source_filename" value="ialos-education-curriculum.json" required>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Payload Data JSON (Array of Objects)</label>
                                <span class="badge bg-light text-muted border small">Format: JSON Array</span>
                            </div>
                            <textarea class="form-control font-monospace rounded-3" rows="8" name="rows_json" placeholder='[
  {"entity_type": "CP", "code": "CP-INF-X", "phase": "E", "statement": "..."},
  {"entity_type": "TP", "code": "TP-INF-X-01", "statement": "..."}
]' required></textarea>
                            <div class="form-text small">Data akan divalidasi ke staging terlebih dahulu sebelum dapat di-apply ke database.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Validasi & Simpan ke Staging</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
