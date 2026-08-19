<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Digital Kurikulum Satuan Pendidikan (KSP)'; 
$pageIcon = 'book-open-check'; 
$pageDescription = 'Susun Kurikulum Satuan Pendidikan (KSP) sebagai living document yang terhubung dengan unit sekolah dan periode akademik.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle','pageIcon','pageDescription')) ?>

<?php
$totalKsp = count($versions);
$approvedKsp = count(array_filter($versions, fn($r) => in_array(strtoupper($r['status'] ?? ''), ['APPROVED', 'LOCKED'])));
$draftKsp = count(array_filter($versions, fn($r) => strtoupper($r['status'] ?? '') === 'DRAFT'));
$reviewKsp = count(array_filter($versions, fn($r) => strtoupper($r['status'] ?? '') === 'REVIEW'));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalKsp ?></div>
                    <div class="small text-muted">Total Versi KSP</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary p-3">
                    <i data-lucide="file-edit" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $draftKsp ?></div>
                    <div class="small text-muted">Draft Penyusunan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3">
                    <i data-lucide="clock" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-warning"><?= $reviewKsp ?></div>
                    <div class="small text-muted">Dalam Review</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $approvedKsp ?></div>
                    <div class="small text-muted">Disetujui & Locked</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="kspSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari kode, judul, atau unit...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
            </div>
            <?php if (has_permission('ksp.manage')): ?>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createKspModal">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Buat Versi KSP Baru
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="kspTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Kode & Judul KSP</th>
                        <th>Unit Sekolah</th>
                        <th>Periode Akademik</th>
                        <th>Status Dokumen</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versions as $row): ?>
                        <tr class="ksp-row">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2">
                                        <i data-lucide="book-open" style="width: 18px; height: 18px;"></i>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-primary border font-monospace px-2 py-1 mb-1"><?= esc($row['code']) ?></span>
                                        <div class="fw-bold text-dark"><?= esc($row['title']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-1">
                                    <i data-lucide="school" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= esc($row['unit_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-1">
                                    <i data-lucide="calendar" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= esc($row['period_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $status = strtoupper($row['status'] ?? 'DRAFT');
                                $stBadge = match($status) {
                                    'APPROVED' => 'bg-success text-white',
                                    'LOCKED' => 'bg-dark text-white',
                                    'REVIEW' => 'bg-warning text-dark',
                                    default => 'bg-secondary bg-opacity-10 text-secondary border'
                                };
                                ?>
                                <span class="badge <?= $stBadge ?> rounded-pill px-3 py-1">
                                    <?= esc($status) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-primary rounded-pill px-3" href="<?= base_url('education/ksp/'.$row['uuid']) ?>">
                                    <i data-lucide="layout-dashboard" class="me-1" style="width: 14px; height: 14px;"></i> Buka Control
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($versions)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i data-lucide="folder-open" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                                Belum ada versi KSP yang dibuat.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (has_permission('ksp.manage')): ?>
<!-- Modal Create KSP -->
<div class="modal fade" id="createKspModal" tabindex="-1" aria-labelledby="createKspModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createKspModalLabel">Buat Versi KSP Baru</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('education/ksp') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unit Sekolah</label>
                            <select class="form-select rounded-3" name="unit_id" required>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= $unit['id'] ?>"><?= esc($unit['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Periode Akademik</label>
                            <select class="form-select rounded-3" name="academic_period_id" required>
                                <?php foreach ($periods as $period): ?>
                                    <option value="<?= $period['id'] ?>"><?= esc($period['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kode KSP</label>
                            <input class="form-control rounded-3" name="code" placeholder="Contoh: KSP-SMP-2026" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Dokumen KSP</label>
                            <input class="form-control rounded-3" name="title" placeholder="Contoh: Kurikulum Satuan Pendidikan SMP Tahun Ajaran 2026/2027" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Buat Dokumen KSP</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('kspSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#kspTable tbody tr.ksp-row').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
<?= $this->endSection() ?>
