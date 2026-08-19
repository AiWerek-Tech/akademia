<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$swotItems = array_filter($rows, fn($r) => strtoupper($r['context_type'] ?? '') === 'SWOT');
$internalItems = array_filter($rows, fn($r) => strtoupper($r['context_type'] ?? '') === 'INTERNAL');
$externalItems = array_filter($rows, fn($r) => strtoupper($r['context_type'] ?? '') === 'EXTERNAL');
$dataQualityItems = array_filter($rows, fn($r) => strtoupper($r['context_type'] ?? '') === 'DATA_QUALITY');
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="building" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count($internalItems) ?></div>
                    <div class="small text-muted">Konteks Internal</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="globe" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-info"><?= count($externalItems) ?></div>
                    <div class="small text-muted">Konteks Eksternal</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                    <i data-lucide="compass" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-warning"><?= count($swotItems) ?></div>
                    <div class="small text-muted">Analisis SWOT</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="database" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= count($dataQualityItems) ?></div>
                    <div class="small text-muted">Mutu & Rapor Pendidikan</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Analisis Karakteristik Satuan Pendidikan</h5>
                <p class="small text-muted mb-0">Snapshot lingkungan sosial budaya, peserta didik, guru, sarana, dan nilai kekhasan sekolah.</p>
            </div>
            <?php if ($canManage): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createContextModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Snapshot Konteks
                </button>
            <?php endif; ?>
        </div>

        <!-- Categorized Grid -->
        <div class="row g-4">
            <?php foreach ($rows as $row): ?>
                <?php
                $type = strtoupper($row['context_type']);
                $typeColor = match($type) {
                    'INTERNAL' => 'primary',
                    'EXTERNAL' => 'info',
                    'SWOT' => 'warning',
                    'DATA_QUALITY' => 'success',
                    default => 'secondary'
                };
                ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-hover transition-all" style="background: #fafafa; border-left: 4px solid var(--bs-<?= $typeColor ?>) !important;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-<?= $typeColor ?> bg-opacity-10 text-<?= $typeColor ?> border border-<?= $typeColor ?>-subtle rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($type) ?>
                                    </span>
                                    <?php if (!empty($row['source_reference'])): ?>
                                        <span class="small text-muted text-truncate" style="max-width: 180px;" title="<?= esc($row['source_reference']) ?>">
                                            <i data-lucide="paperclip" class="me-1" style="width: 12px; height: 12px;"></i><?= esc($row['source_reference']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= esc($row['title']) ?></h5>
                                <p class="small text-muted mb-0 lh-base"><?= nl2br(esc($row['summary'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i data-lucide="building-2" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                    Belum ada snapshot karakteristik satuan pendidikan yang dicatat.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Create Context Snapshot -->
<div class="modal fade" id="createContextModal" tabindex="-1" aria-labelledby="createContextModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createContextModalLabel">Tambah Snapshot Karakteristik Sekolah</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/context') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategori Konteks</label>
                            <select class="form-select rounded-3" name="context_type" required>
                                <option value="INTERNAL">INTERNAL (SDM, Fasilitas, Murid)</option>
                                <option value="EXTERNAL">EXTERNAL (Geografis, Sosial Budaya, Mitra)</option>
                                <option value="SWOT">SWOT (Kekuatan, Kelemahan, Peluang, Ancaman)</option>
                                <option value="DATA_QUALITY">DATA_QUALITY (Rapor Pendidikan, Asesmen)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Karakteristik / Analisis</label>
                            <input class="form-control rounded-3" name="title" placeholder="Contoh: Kondisi Geografis & Kekhasan Nilai Pendidikan Karakter" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Referensi Sumber Data / Dokumen</label>
                            <input class="form-control rounded-3" name="source_reference" placeholder="Contoh: Rapor Pendidikan 2025, Notula Rapat Kerja Kurikulum">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Ringkasan Narasi Berbasis Bukti</label>
                            <textarea class="form-control rounded-3" rows="4" name="summary" placeholder="Uraikan karakteristik dan analisis konteks sekolah secara mendalam..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Snapshot</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
