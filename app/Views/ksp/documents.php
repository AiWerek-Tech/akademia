<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$isReadyToExport = in_array(strtoupper($version['status']), ['APPROVED', 'LOCKED'], true);
$docxCount = count(array_filter($rows, fn($r) => strtoupper($r['format']) === 'DOCX'));
$pdfCount = count(array_filter($rows, fn($r) => strtoupper($r['format']) === 'PDF'));
?>

<div class="row g-4 mb-4">
    <!-- Generator Trigger Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-3 bg-primary text-white p-2">
                    <i data-lucide="printer" style="width: 20px; height: 20px;"></i>
                </div>
                <h5 class="fw-bold mb-0 text-white">KSP Document Generator</h5>
            </div>
            
            <p class="text-white text-opacity-75 small mb-4">
                Hasilkan dokumen Kurikulum Satuan Pendidikan resmi dalam format Microsoft Word (.docx) atau PDF dari snapshot data terstruktur yang telah disetujui (APPROVED/LOCKED).
            </p>

            <?php if ($isReadyToExport && has_permission('ksp.export')): ?>
                <div class="d-flex gap-3 flex-wrap">
                    <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/documents') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="format" value="DOCX">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm">
                            <i data-lucide="file-type-2" style="width: 18px; height: 18px;"></i>
                            <span>Generate DOCX</span>
                        </button>
                    </form>

                    <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/documents') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="format" value="PDF">
                        <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm">
                            <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                            <span>Generate PDF</span>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 d-flex align-items-center gap-3">
                    <i data-lucide="lock" class="text-warning" style="width: 24px; height: 24px;"></i>
                    <div class="small text-white text-opacity-75">
                        Generator dokumen aktif setelah status KSP mencapai <strong>APPROVED</strong> atau <strong>LOCKED</strong>.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Summary Card -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h5 class="fw-bold mb-3">Arsip Riwayat Dokumen</h5>
            <div class="row g-3">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                                <i data-lucide="file-type-2" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-primary"><?= $docxCount ?></div>
                                <div class="small text-muted">Dokumen DOCX</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3">
                                <i data-lucide="file-text" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <div class="fs-4 fw-bold text-danger"><?= $pdfCount ?></div>
                                <div class="small text-muted">Dokumen PDF</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="small text-muted mt-3">
                <i data-lucide="shield-check" class="me-1 text-success" style="width: 14px; height: 14px;"></i>
                Setiap berkas yang dihasilkan bersifat <em>immutable</em> dan diverifikasi menggunakan hash SHA-256 integritas tinggi.
            </div>
        </div>
    </div>
</div>

<!-- Historical Outputs Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-4">Daftar Dokumen KSP yang Telah Di-Generate</h5>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Format Berkas</th>
                        <th>Revisi Sumber KSP</th>
                        <th>Waktu & Pembuat</th>
                        <th>Integritas Hash (SHA-256)</th>
                        <th class="text-end pe-4">Unduh Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $fmt = strtoupper($row['format']);
                        $fmtBadge = $fmt === 'PDF' ? 'bg-danger text-white' : 'bg-primary text-white';
                        $fmtIcon = $fmt === 'PDF' ? 'file-text' : 'file-type-2';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge <?= $fmtBadge ?> rounded-pill px-3 py-1 font-monospace">
                                    <i data-lucide="<?= $fmtIcon ?>" class="me-1" style="width: 13px; height: 13px;"></i><?= esc($fmt) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-muted border font-monospace px-2 py-1 small">
                                    <?= esc($row['source_revision']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small text-dark fw-semibold"><?= esc($row['generated_at']) ?></div>
                                <div class="small text-muted"><?= esc($row['generator_name'] ?? 'System') ?></div>
                            </td>
                            <td>
                                <div class="small font-monospace text-truncate text-muted" style="max-width: 180px;" title="<?= esc($row['document_hash']) ?>">
                                    <?= esc($row['document_hash']) ?>
                                </div>
                                <button type="button" class="btn btn-link p-0 small text-primary text-decoration-none btn-copy-doc-hash" data-hash="<?= esc($row['document_hash']) ?>" style="font-size: 0.75rem;">
                                    <i data-lucide="copy" style="width: 11px; height: 11px;"></i> Salin Hash
                                </button>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (has_permission('ksp.export')): ?>
                                    <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="<?= base_url('education/ksp/'.$version['uuid'].'/documents/'.$row['uuid'].'/download') ?>">
                                        <i data-lucide="download" class="me-1" style="width: 14px; height: 14px;"></i> Unduh Dokumen
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i data-lucide="file-output" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                                Belum ada dokumen KSP final yang di-generate.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-copy-doc-hash').forEach(btn => {
    btn.addEventListener('click', function() {
        const hash = this.dataset.hash;
        navigator.clipboard.writeText(hash).then(() => {
            alert('Hash SHA-256 dokumen berhasil disalin:\n' + hash);
        });
    });
});
</script>
<?= $this->endSection() ?>
