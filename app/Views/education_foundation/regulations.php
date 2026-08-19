<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Regulasi Pendidikan'; 
$pageIcon = 'landmark'; 
$pageDescription = 'Registry regulasi resmi nasional dan payung hukum kurikulum satuan pendidikan.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalCount = count($rows);
$activeCount = count(array_filter($rows, fn($r) => strtoupper($r['status'] ?? '') === 'ACTIVE' || strtoupper($r['status'] ?? '') === 'PUBLISHED'));
$legalCount = count(array_filter($rows, fn($r) => strtoupper($r['regulation_type'] ?? '') === 'LEGAL_REQUIRED'));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="book-marked" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalCount ?></div>
                    <div class="small text-muted">Total Regulasi</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="shield-check" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $activeCount ?></div>
                    <div class="small text-muted">Regulasi Aktif</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3">
                    <i data-lucide="scale" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $legalCount ?></div>
                    <div class="small text-muted">Legal Mandatory</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="building-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= count(array_unique(array_filter(array_column($rows, 'authority')))) ?></div>
                    <div class="small text-muted">Otoritas Penerbit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="regSearchInput" class="form-control rounded-pill ps-5" placeholder="Cari kode, judul, atau otoritas...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 10px; left: 18px; width: 18px; height: 18px;"></i>
            </div>
            <?php if (has_permission('regulations.manage')): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createRegModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 18px; height: 18px;"></i> Tambah Regulasi
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="regTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kode & Referensi</th>
                        <th>Judul Regulasi</th>
                        <th>Otoritas Penerbit</th>
                        <th>Jenis Payung Hukum</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="reg-row">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold font-monospace text-primary"><?= esc($row['code']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= esc($row['title']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 rounded-pill">
                                    <i data-lucide="building" class="me-1 text-muted" style="width: 13px; height: 13px;"></i><?= esc($row['authority'] ?: 'Kemendikbudristek') ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtoupper($row['regulation_type'] ?? '') === 'LEGAL_REQUIRED'): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 rounded-pill">
                                        <i data-lucide="alert-octagon" class="me-1" style="width: 13px; height: 13px;"></i>Legal Required
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle px-2 py-1 rounded-pill">
                                        <i data-lucide="info" class="me-1" style="width: 13px; height: 13px;"></i><?= esc($row['regulation_type'] ?? 'Guideline') ?>
                                    </span>
                                <?php endif; ?>
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
                                Belum ada regulasi resmi yang terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (has_permission('regulations.manage')): ?>
<div class="modal fade" id="createRegModal" tabindex="-1" aria-labelledby="createRegModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createRegModalLabel">Tambah Regulasi Pendidikan</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('references/regulations') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode Regulasi</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: PERMENDIKDASMEN-13-2025" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Regulasi</label>
                            <input class="form-control rounded-3" name="title" placeholder="Nama lengkap peraturan / keputusan menteri" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Otoritas Penerbit</label>
                            <input class="form-control rounded-3" name="authority" placeholder="Kemendikdasmen / BSKAP" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jenis Payung Hukum</label>
                            <select class="form-select rounded-3" name="regulation_type" required>
                                <option value="LEGAL_REQUIRED">LEGAL_REQUIRED (Wajib Hukum)</option>
                                <option value="GUIDELINE">GUIDELINE (Panduan Teknis)</option>
                                <option value="SCHOOL_POLICY">SCHOOL_POLICY (Kebijakan Satuan)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Regulasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('regSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#regTable tbody tr.reg-row').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
<?= $this->endSection() ?>
