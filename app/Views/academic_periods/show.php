<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('academic-periods') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Detail Periode Akademik</h4>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Details -->
    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-slate-800 mb-0">Informasi Umum</h5>
                    <?php if ((int)$period['is_active'] === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fw-semibold fs-8 d-inline-flex align-items-center gap-1">
                            <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Aktif Secara Global
                        </span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted px-3 py-1 rounded-pill fw-semibold fs-8">Non-Aktif</span>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Tahun Pelajaran</span>
                        <span class="fw-bold text-slate-800 fs-5"><?= esc($period['year_name']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Semester</span>
                        <span class="fw-bold text-slate-800 fs-5">
                            Semester <?= (int)$period['semester_number'] === 1 ? '1 (Ganjil)' : '2 (Genap)' ?>
                        </span>
                    </div>
                    
                    <div class="col-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Tanggal Mulai</span>
                        <span class="fw-medium text-slate-700"><?= date('d F Y', strtotime($period['start_date'])) ?></span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Tanggal Selesai</span>
                        <span class="fw-medium text-slate-700"><?= date('d F Y', strtotime($period['end_date'])) ?></span>
                    </div>
                    
                    <div class="col-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Status Alur Kerja</span>
                        <?php 
                        $badgeClass = 'bg-secondary';
                        if ($period['workflow_status'] === 'APPROVED') $badgeClass = 'bg-success';
                        elseif ($period['workflow_status'] === 'LOCKED') $badgeClass = 'bg-dark';
                        elseif ($period['workflow_status'] === 'VALIDATED') $badgeClass = 'bg-info text-white';
                        elseif ($period['workflow_status'] === 'REVIEWED') $badgeClass = 'bg-warning text-dark';
                        ?>
                        <span class="badge <?= $badgeClass ?> px-3 py-1 rounded-pill fw-semibold fs-8 mt-1">
                            <?= $period['workflow_status'] ?>
                        </span>
                    </div>
                    <div class="col-6">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold">Revisi Saat Ini</span>
                        <span class="fw-medium text-slate-700">#<?= esc($period['revision_number']) ?></span>
                    </div>
                </div>

                <hr class="my-4 text-slate-200">

                <div class="mb-3">
                    <span class="text-muted fs-8 d-block text-uppercase fw-semibold mb-2">Riwayat Audit Pembuat & Perubahan</span>
                    <div class="bg-light p-3 rounded-3 fs-8 text-slate-600">
                        <div class="mb-1"><strong>Dibuat oleh:</strong> <?= esc($period['creator_name'] ?? 'System') ?> pada <?= date('d-m-Y H:i', strtotime($period['created_at'])) ?></div>
                        <?php if ($period['updated_by']): ?>
                            <div class="mb-1"><strong>Diperbarui oleh:</strong> <?= esc($period['updater_name']) ?> pada <?= date('d-m-Y H:i', strtotime($period['updated_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($period['approved_by']): ?>
                            <div class="mb-1"><strong>Disetujui oleh:</strong> <?= esc($period['approver_name']) ?> pada <?= date('d-m-Y H:i', strtotime($period['approved_at'])) ?></div>
                        <?php endif; ?>
                        <?php if ($period['locked_by']): ?>
                            <div><strong>Dikunci oleh:</strong> <?= esc($period['locker_name']) ?> pada <?= date('d-m-Y H:i', strtotime($period['locked_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($period['notes']): ?>
                    <div class="mb-0">
                        <span class="text-muted fs-8 d-block text-uppercase fw-semibold mb-1">Catatan Terakhir</span>
                        <div class="border-start border-3 border-primary ps-3 py-1 fs-7 italic text-slate-700">
                            <?= esc($period['notes']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Workflow Actions -->
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold text-slate-800 mb-3 d-flex align-items-center gap-2">
                    <i data-lucide="git-branch" class="text-primary" style="width: 20px; height: 20px;"></i>
                    Tindakan Alur Kerja
                </h5>
                <p class="text-muted fs-8 mb-4">Lakukan transisi status pada periode akademik ini sesuai wewenang peran Anda.</p>

                <div class="d-grid gap-3">
                    <!-- Edit Action (Available in Draft) -->
                    <?php if (in_array($period['workflow_status'], ['DRAFT', 'VALIDATED', 'REVIEWED'], true) && has_permission('academic_periods.manage')): ?>
                        <a href="<?= base_url('academic-periods/' . $period['uuid'] . '/edit') ?>" class="btn btn-outline-secondary rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2">
                            <i data-lucide="pencil" style="width: 18px; height: 18px;"></i>
                            <span>Edit Metadata Periode</span>
                        </a>
                    <?php endif; ?>

                    <!-- Workflow transitions -->
                    <?php if ($period['workflow_status'] === 'DRAFT' && has_permission('academic_periods.validate')): ?>
                        <button type="button" class="btn btn-info text-white rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2" onclick="openTransitionModal('VALIDATED', 'Validasi Periode')">
                            <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                            <span>Validasi (DRAFT &rarr; VALIDATED)</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($period['workflow_status'] === 'VALIDATED' && has_permission('academic_periods.review')): ?>
                        <button type="button" class="btn btn-warning rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2" onclick="openTransitionModal('REVIEWED', 'Review Periode')">
                            <i data-lucide="eye" style="width: 18px; height: 18px;"></i>
                            <span>Review (VALIDATED &rarr; REVIEWED)</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($period['workflow_status'] === 'REVIEWED' && has_permission('academic_periods.approve')): ?>
                        <button type="button" class="btn btn-success rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2" onclick="openTransitionModal('APPROVED', 'Setujui Periode')">
                            <i data-lucide="check-check" style="width: 18px; height: 18px;"></i>
                            <span>Setujui (REVIEWED &rarr; APPROVED)</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($period['workflow_status'] === 'APPROVED' && has_permission('academic_periods.lock')): ?>
                        <button type="button" class="btn btn-dark rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2" onclick="openTransitionModal('LOCKED', 'Kunci Periode')">
                            <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
                            <span>Kunci & Tutup Perencanaan (APPROVED &rarr; LOCKED)</span>
                        </button>
                    <?php endif; ?>

                    <!-- Archive Option -->
                    <?php if (in_array($period['workflow_status'], ['DRAFT', 'VALIDATED', 'REVIEWED', 'APPROVED'], true) && has_permission('academic_periods.manage')): ?>
                        <button type="button" class="btn btn-outline-danger rounded-3 text-start px-3 py-2.5 d-flex align-items-center gap-2" onclick="openTransitionModal('ARCHIVED', 'Arsipkan Periode')">
                            <i data-lucide="archive" style="width: 18px; height: 18px;"></i>
                            <span>Arsipkan Periode</span>
                        </button>
                    <?php endif; ?>

                    <?php if ($period['workflow_status'] === 'LOCKED' || $period['workflow_status'] === 'ARCHIVED'): ?>
                        <div class="alert alert-secondary rounded-3 text-center fs-8 mb-0 d-flex align-items-center justify-content-center gap-2" role="alert">
                            <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                            Periode dalam status terminal (LOCKED/ARCHIVED) tidak dapat diubah alur kerjanya lagi.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Workflow Transition Modal -->
<div class="modal fade" id="transitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 bg-slate-900 text-white" style="background: #1e293b;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="transitionModalTitle">Transisi Alur Kerja</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transitionForm" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="revision_number" value="<?= esc($period['revision_number']) ?>">
                
                <div class="modal-body py-3">
                    <p class="fs-8 text-slate-300">Harap masukkan catatan atau justifikasi untuk transisi status alur kerja ini. Catatan akan disimpan secara permanen di log audit sistem.</p>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-semibold fs-8 text-slate-400">Catatan / Keterangan</label>
                        <textarea class="form-control rounded-3 bg-slate-800 border-slate-700 text-white" id="notes" name="notes" rows="3" placeholder="Masukkan catatan transisi..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-light rounded-3 px-3 py-2 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 btn-sm fw-bold">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openTransitionModal(targetStatus, title) {
        const form = document.getElementById('transitionForm');
        const modalTitle = document.getElementById('transitionModalTitle');
        
        form.action = `<?= base_url('academic-periods/' . $period['uuid']) ?>/transition/` + targetStatus;
        modalTitle.textContent = title;
        
        const modal = new bootstrap.Modal(document.getElementById('transitionModal'));
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });
</script>
<?= $this->endSection() ?>
