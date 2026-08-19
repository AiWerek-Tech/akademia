<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Sumber Kurikulum'; 
$pageIcon = 'library'; 
$pageDescription = 'Provenance, referensi resmi, dan dokumen sumber materi kurikulum satuan pendidikan.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count($rows) ?></div>
                    <div class="small text-muted">Total Sumber Resmi</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count(array_filter($rows, fn($r) => strtoupper($r['status'] ?? '') === 'ACTIVE')) ?></div>
                    <div class="small text-muted">Sumber Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="award" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count(array_unique(array_filter(array_column($rows, 'issuer')))) ?></div>
                    <div class="small text-muted">Penerbit Dokumen</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="sourceSearchInput" class="form-control rounded-pill ps-5" placeholder="Cari kode, sumber, atau penerbit...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 10px; left: 18px; width: 18px; height: 18px;"></i>
            </div>
            <?php if (has_permission('curriculum_sources.manage')): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createSourceModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 18px; height: 18px;"></i> Tambah Sumber Resmi
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="sourceTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kode Sumber</th>
                        <th>Judul & Deskripsi Sumber</th>
                        <th>Penerbit Resmi</th>
                        <th>Jenis Sumber</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="source-row">
                            <td class="ps-4">
                                <span class="badge bg-primary bg-opacity-10 text-primary font-monospace px-3 py-2 rounded-pill">
                                    <?= esc($row['code']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($row['title']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill">
                                    <i data-lucide="building" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= esc($row['issuer'] ?: 'Puskurjar / BSKAP') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">
                                    <?= esc($row['source_type'] ?: 'OFFICIAL_TEXTBOOK') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= in_array(strtoupper($row['status'] ?? ''), ['ACTIVE', 'PUBLISHED']) ? 'bg-success bg-opacity-10 text-success border border-success-subtle' : 'bg-secondary bg-opacity-10 text-secondary' ?> rounded-pill px-3 py-1">
                                    <?= esc($row['status'] ?? 'ACTIVE') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i data-lucide="folder-open" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                                Belum ada sumber kurikulum yang terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (has_permission('curriculum_sources.manage')): ?>
<div class="modal fade" id="createSourceModal" tabindex="-1" aria-labelledby="createSourceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createSourceModalLabel">Tambah Sumber Kurikulum Resmi</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('references/curriculum-sources') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Sumber</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: BUKU-GURU-INF-X" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Sumber</label>
                            <input class="form-control rounded-3" name="title" placeholder="Nama buku teks, panduan guru, atau modul ajar resmi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jenis Sumber</label>
                            <select class="form-select rounded-3" name="source_type" required>
                                <option value="OFFICIAL_TEXTBOOK">Buku Teks Resmi (Kemendikbudristek)</option>
                                <option value="TEACHER_GUIDE">Buku Panduan Guru</option>
                                <option value="CURRICULUM_FRAMEWORK">Kerangka Kurikulum Nasional</option>
                                <option value="SCHOOL_SUPPLEMENT">Suplemen Satuan Pendidikan</option>
                                <option value="EXTERNAL_REFERENCE">Referensi Eksternal Terakreditasi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Penerbit / Institusi</label>
                            <input class="form-control rounded-3" name="issuer" placeholder="Pusat Kurikulum dan Perbukuan / BSKAP" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Sumber</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('sourceSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#sourceTable tbody tr.source-row').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
<?= $this->endSection() ?>
