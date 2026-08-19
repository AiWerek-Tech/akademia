<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$today = date('Y-m-d');
$totalEvals = count($rows);
$totalActions = count($actions);
$completedActions = count(array_filter($actions, fn($a) => strtoupper($a['status'] ?? '') === 'COMPLETED'));
$inProgressActions = count(array_filter($actions, fn($a) => strtoupper($a['status'] ?? '') === 'IN_PROGRESS' || strtoupper($a['status'] ?? '') === 'OPEN'));
$overdueActions = count(array_filter($actions, fn($a) => !empty($a['due_date']) && $a['due_date'] < $today && strtoupper($a['status'] ?? '') !== 'COMPLETED'));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="clipboard-check" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalEvals ?></div>
                    <div class="small text-muted">Evaluasi Periodik</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 text-info p-3">
                    <i data-lucide="activity" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-info"><?= $inProgressActions ?></div>
                    <div class="small text-muted">Aksi Sedang Berjalan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-3">
                    <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success"><?= $completedActions ?></div>
                    <div class="small text-muted">Aksi Selesai</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3">
                    <i data-lucide="alert-circle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-danger"><?= $overdueActions ?></div>
                    <div class="small text-muted">Aksi Lewat Jatuh Tempo</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Evaluasi Kurikulum & Rencana Tindak Lanjut</h5>
                <p class="small text-muted mb-0">Siklus perbaikan berkelanjutan berbasis temuan bukti empiris dan akar masalah.</p>
            </div>
            <?php if ($canManage): ?>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-3 btn-sm" data-bs-toggle="modal" data-bs-target="#createEvalModal">
                        <i data-lucide="plus" class="me-1" style="width: 14px; height: 14px;"></i> Catat Evaluasi
                    </button>
                    <?php if (!empty($rows)): ?>
                        <button type="button" class="btn btn-primary rounded-pill px-3 btn-sm" data-bs-toggle="modal" data-bs-target="#createActionModal">
                            <i data-lucide="sparkles" class="me-1" style="width: 14px; height: 14px;"></i> Tambah Tindak Lanjut
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 1. EVALUATION CARDS -->
        <h6 class="fw-bold text-muted text-uppercase small mb-3" style="letter-spacing: 0.5px;">
            <i data-lucide="list-checks" class="me-1 text-primary" style="width: 16px; height: 16px;"></i>Daftar Catatan Evaluasi Periodik
        </h6>

        <div class="row g-3 mb-5">
            <?php foreach ($rows as $row): ?>
                <div class="col-md-6">
                    <div class="card border rounded-4 h-100 p-3 card-hover transition-all bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1 font-monospace small">
                                <?= esc($row['evaluation_period']) ?>
                            </span>
                            <?php if (!empty($row['target_value'])): ?>
                                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">
                                    Target: <?= esc($row['target_value']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2"><?= esc($row['objective']) ?></h5>
                        
                        <?php if (!empty($row['finding'])): ?>
                            <div class="small text-muted mb-2 p-2 bg-light rounded-3">
                                <strong>Temuan:</strong> <?= nl2br(esc($row['finding'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($row['root_cause'])): ?>
                            <div class="small text-danger mb-2 p-2 bg-danger bg-opacity-10 rounded-3">
                                <strong>Akar Masalah:</strong> <?= nl2br(esc($row['root_cause'])) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($canManage): ?>
                            <div class="pt-2 border-top text-end mt-2">
                                <button type="button" class="btn btn-sm btn-link p-0 text-primary text-decoration-none fw-semibold btn-add-action-to-eval"
                                        data-bs-toggle="modal" data-bs-target="#createActionModal"
                                        data-eval-uuid="<?= esc($row['uuid']) ?>">
                                    <i data-lucide="plus-circle" class="me-1" style="width: 14px; height: 14px;"></i> Tambah Aksi Terkait
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <div class="col-12 text-center text-muted py-4">
                    Belum ada data evaluasi periodik yang dicatat.
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. IMPROVEMENT ACTIONS TABLE -->
        <h6 class="fw-bold text-muted text-uppercase small mb-3" style="letter-spacing: 0.5px;">
            <i data-lucide="check-square" class="me-1 text-success" style="width: 16px; height: 16px;"></i>Tindak Lanjut & Rencana Peningkatan (<?= count($actions) ?>)
        </h6>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nama Tindak Lanjut</th>
                        <th>Objek Evaluasi</th>
                        <th>Penanggung Jawab (Owner)</th>
                        <th>Jatuh Tempo</th>
                        <th>Indikator Keberhasilan</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actions as $action): ?>
                        <?php
                        $st = strtoupper($action['status'] ?? 'OPEN');
                        $isOverdue = (!empty($action['due_date']) && $action['due_date'] < $today && $st !== 'COMPLETED');
                        $stBadge = match($st) {
                            'COMPLETED' => 'bg-success text-white',
                            'IN_PROGRESS' => 'bg-info text-white',
                            'CANCELLED' => 'bg-secondary text-white',
                            default => 'bg-warning text-dark'
                        };
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= esc($action['title']) ?></div>
                                <?php if (!empty($action['description'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;"><?= esc($action['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                    <?= esc($action['evaluation_objective']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small fw-semibold text-dark">
                                    <i data-lucide="user" class="me-1 text-muted" style="width: 12px; height: 12px;"></i><?= esc($action['owner_name'] ?: ucwords(str_replace('_', ' ', $action['owner_role_code'] ?: '-'))) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small <?= $isOverdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= esc($action['due_date'] ?: '-') ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger text-white rounded-pill ms-1" style="font-size: 0.65rem;">Overdue</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-dark"><?= esc($action['success_indicator'] ?: '-') ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $stBadge ?> rounded-pill px-3 py-1 font-monospace small">
                                    <?= esc($st) ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($canManage): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-edit-action"
                                            data-bs-toggle="modal" data-bs-target="#editActionModal"
                                            data-action-uuid="<?= esc($action['uuid']) ?>"
                                            data-action-title="<?= esc($action['title']) ?>"
                                            data-action-status="<?= esc($st) ?>"
                                            data-action-revision="<?= (int)$action['revision_number'] ?>">
                                        Update
                                    </button>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($actions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i data-lucide="check-circle" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
                                Belum ada rencana tindak lanjut perbaikan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Create Evaluation -->
<div class="modal fade" id="createEvalModal" tabindex="-1" aria-labelledby="createEvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createEvalModalLabel">Catat Evaluasi Kurikulum Periodik</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/evaluation') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Periode Evaluasi</label>
                            <input class="form-control rounded-3" name="evaluation_period" placeholder="Contoh: Semester Ganjil 2026/2027" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Objek / Fokus Evaluasi</label>
                            <input class="form-control rounded-3" name="objective" placeholder="Contoh: Capaian Literasi & Keterampilan Pemecahan Masalah" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Target yang Diharapkan</label>
                            <input class="form-control rounded-3" name="target_value" placeholder="Contoh: 85% siswa mencapai kriteria ketuntasan minimal">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Temuan Berbasis Bukti</label>
                            <textarea class="form-control rounded-3" rows="3" name="finding" placeholder="Deskripsikan data fakta dan observasi..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Analisis Akar Masalah (Root Cause)</label>
                            <textarea class="form-control rounded-3" rows="3" name="root_cause" placeholder="Jelaskan faktor mendasar yang mempengaruhi..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Evaluasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Create Action -->
<div class="modal fade" id="createActionModal" tabindex="-1" aria-labelledby="createActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createActionModalLabel">Tambah Tindak Lanjut Perbaikan</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="actionSubmitForm" method="post" action="<?= !empty($rows) ? base_url('education/ksp/'.$version['uuid'].'/evaluation/'.$rows[0]['uuid'].'/actions') : '' ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Pilih Objek Evaluasi Rujukan</label>
                            <select class="form-select rounded-3" id="modalEvalSelector" required>
                                <?php foreach ($rows as $row): ?>
                                    <option value="<?= esc($row['uuid']) ?>"><?= esc($row['evaluation_period'].' — '.$row['objective']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Judul Rencana Aksi</label>
                            <input class="form-control rounded-3" name="title" placeholder="Contoh: Penguatan Program Remediasi & Pendampingan Sejawat" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal Jatuh Tempo</label>
                            <input type="date" class="form-control rounded-3" name="due_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Penanggung Jawab (Owner)</label>
                            <input class="form-control rounded-3" name="owner_role_code" value="wakasek_kurikulum" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status Awal</label>
                            <select class="form-select rounded-3" name="status">
                                <option value="OPEN">OPEN</option>
                                <option value="IN_PROGRESS">IN_PROGRESS</option>
                                <option value="COMPLETED">COMPLETED</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Indikator Keberhasilan (Success Indicator)</label>
                            <input class="form-control rounded-3" name="success_indicator" placeholder="Contoh: Kenaikan rerata nilai asesmen formatif sebesar minimal 15%" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Deskripsi Langkah Konkret</label>
                            <textarea class="form-control rounded-3" rows="3" name="description" placeholder="Langkah-langkah strategis pelaksanaan aksi..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Tindak Lanjut</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Action Status -->
<div class="modal fade" id="editActionModal" tabindex="-1" aria-labelledby="editActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div>
                    <h5 class="modal-title fw-bold" id="editActionModalLabel">Perbarui Status Tindak Lanjut</h5>
                    <div class="small text-muted" id="editActionTitleLabel">Aksi: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editActionForm" method="post" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="editActionRevision" name="revision_number" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status Aksi</label>
                        <select class="form-select rounded-3" id="editActionStatusSelect" name="status" required>
                            <option value="OPEN">OPEN (Belum Dimulai)</option>
                            <option value="IN_PROGRESS">IN_PROGRESS (Sedang Berjalan)</option>
                            <option value="COMPLETED">COMPLETED (Selesai)</option>
                            <option value="CANCELLED">CANCELLED (Dibatalkan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('modalEvalSelector')?.addEventListener('change', function() {
    const form = document.getElementById('actionSubmitForm');
    form.action = '<?= base_url('education/ksp/'.$version['uuid'].'/evaluation/') ?>/' + encodeURIComponent(this.value) + '/actions';
});

document.querySelectorAll('.btn-add-action-to-eval').forEach(btn => {
    btn.addEventListener('click', function() {
        const evalUuid = this.dataset.evalUuid;
        const selector = document.getElementById('modalEvalSelector');
        if (selector) {
            selector.value = evalUuid;
            const form = document.getElementById('actionSubmitForm');
            form.action = '<?= base_url('education/ksp/'.$version['uuid'].'/evaluation/') ?>/' + encodeURIComponent(evalUuid) + '/actions';
        }
    });
});

document.querySelectorAll('.btn-edit-action').forEach(btn => {
    btn.addEventListener('click', function() {
        const uuid = this.dataset.actionUuid;
        const title = this.dataset.actionTitle;
        const status = this.dataset.actionStatus;
        const rev = this.dataset.actionRevision;

        document.getElementById('editActionTitleLabel').textContent = title;
        document.getElementById('editActionStatusSelect').value = status;
        document.getElementById('editActionRevision').value = rev;
        document.getElementById('editActionForm').action = '<?= base_url('education/ksp/'.$version['uuid'].'/improvement-actions/') ?>/' + encodeURIComponent(uuid);
    });
});
</script>
<?php endif; ?>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
