<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>
<?php use App\Services\KspEvidenceService; ?>

<?php
$totalEvidence = count($rows);
$fileCount = count(array_filter($rows, fn($r) => !empty($r['storage_reference'])));
$linkCount = count(array_filter($rows, fn($r) => empty($r['storage_reference']) && !empty($r['reference_uri'])));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="paperclip" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalEvidence ?></div>
                    <div class="small text-muted">Total Bukti (Evidence)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="file-check" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $fileCount ?></div>
                    <div class="small text-muted">Berkas Unggahan (Hash SHA-256)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="link-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-info"><?= $linkCount ?></div>
                    <div class="small text-muted">Referensi Tautan / Notula</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Evidence & Provenance Vault</h5>
                <p class="small text-muted mb-0">Index bukti fisik, berkas notula rapat, dan dokumen pengesahan pendukung KSP.</p>
            </div>
            <?php if ($canManage): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#uploadEvidenceModal">
                    <i data-lucide="upload-cloud" class="me-1" style="width: 16px; height: 16px;"></i> Unggah Bukti / Tautkan
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Jenis & Target</th>
                        <th>Deskripsi Bukti</th>
                        <th>Sumber & Tanggal</th>
                        <th>Integritas Berkas / Hash</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace small mb-1">
                                    <?= esc($row['evidence_type']) ?>
                                </span>
                                <div class="small text-muted">
                                    <i data-lucide="tag" class="me-1" style="width: 12px; height: 12px;"></i>Target: <?= esc($row['target_type']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark mb-1" style="max-width: 320px;"><?= esc($row['description']) ?></div>
                            </td>
                            <td>
                                <div class="small text-dark fw-medium"><?= esc($row['source_name']) ?></div>
                                <div class="small text-muted"><?= esc($row['evidence_date']) ?> · <?= esc($row['creator_name'] ?? 'System') ?></div>
                            </td>
                            <td>
                                <?php if ($row['storage_reference']): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-3 bg-success bg-opacity-10 text-success p-2">
                                            <i data-lucide="file-check-2" style="width: 16px; height: 16px;"></i>
                                        </div>
                                        <div>
                                            <div class="small font-monospace text-truncate text-muted" style="max-width: 160px;" title="<?= esc($row['file_hash']) ?>">
                                                <?= esc($row['file_hash']) ?>
                                            </div>
                                            <button type="button" class="btn btn-link p-0 small text-primary text-decoration-none btn-copy-hash" data-hash="<?= esc($row['file_hash']) ?>" style="font-size: 0.75rem;">
                                                <i data-lucide="copy" style="width: 11px; height: 11px;"></i> Salin Hash SHA-256
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-muted font-monospace text-truncate d-block" style="max-width: 200px;">
                                        <?= esc($row['reference_uri']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($row['storage_reference']): ?>
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="<?= base_url('education/ksp/'.$version['uuid'].'/evidence/'.$row['uuid'].'/download') ?>">
                                        <i data-lucide="download" class="me-1" style="width: 14px; height: 14px;"></i> Unduh
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Tautan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i data-lucide="paperclip" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                                Belum ada bukti atau dokumen pendukung yang ditautkan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Upload / Attach Evidence -->
<div class="modal fade" id="uploadEvidenceModal" tabindex="-1" aria-labelledby="uploadEvidenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="upload-cloud" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="uploadEvidenceModalLabel">Unggah & Tautkan Bukti (Evidence)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data" action="<?= base_url('education/ksp/'.$version['uuid'].'/evidence') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Target Bukti</label>
                            <select class="form-select rounded-3" name="target_type" required>
                                <?php foreach (KspEvidenceService::TARGETS as $type): ?>
                                    <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Objek Target Rekaman</label>
                            <select class="form-select rounded-3" name="target_uuid" required>
                                <?php foreach ($targets as $target): ?>
                                    <option value="<?= esc($target['uuid']) ?>"><?= esc($target['type'].' · '.$target['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Klasifikasi Bukti</label>
                            <select class="form-select rounded-3" name="evidence_type">
                                <?php foreach ($evidenceTypes as $type): ?>
                                    <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nama Sumber / Notula / SK</label>
                            <input class="form-control rounded-3" name="source" placeholder="Contoh: SK Tim Pengembang Kurikulum 2026" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tanggal Bukti</label>
                            <input type="date" class="form-control rounded-3" name="date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unggah Berkas Fisik (Maks 10 MB)</label>
                            <input class="form-control rounded-3" type="file" name="evidence_file">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Atau URL Referensi (Jika Tidak Mengunggah Berkas)</label>
                            <input class="form-control rounded-3" name="reference" placeholder="https://drive.google.com/...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi / Uraian Bukti</label>
                            <textarea class="form-control rounded-3" rows="3" name="description" placeholder="Jelaskan signifikansi bukti ini terhadap bagian KSP yang ditautkan..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Evidence</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.btn-copy-hash').forEach(btn => {
    btn.addEventListener('click', function() {
        const hash = this.dataset.hash;
        navigator.clipboard.writeText(hash).then(() => {
            alert('Hash SHA-256 berhasil disalin:\n' + hash);
        });
    });
});
</script>
<?= $this->endSection() ?>
