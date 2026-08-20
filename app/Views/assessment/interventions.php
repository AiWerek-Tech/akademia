<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">
                    <i data-lucide="heart-handshake" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Intervensi Belajar
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Intervensi Belajar</h1>
            <p class="text-muted mb-0">Kelola program remedial, penguatan, dan pengayaan yang direkomendasikan dari data mastery.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= base_url('interventions') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Kelas</label>
                    <select name="classroom_id" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) ($filters['classroom_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Tipe</label>
                    <select name="intervention_type" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua Tipe</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $filters['intervention_type'] === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm w-100">Filter</button>
                    <a href="<?= base_url('interventions') ?>" class="btn btn-sm btn-outline-secondary shadow-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Manual create (Enrichment & co) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="text-xs fw-semibold text-muted text-uppercase tracking-wider mb-2">Buat Intervensi Manual</div>
            <form method="POST" action="<?= base_url('interventions/create') ?>" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Siswa</label>
                    <select name="student_id" class="form-select form-select-sm shadow-sm" required>
                        <option value="">— Pilih siswa —</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['classroom_name'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">TP</label>
                    <select name="objective_id" class="form-select form-select-sm shadow-sm" required>
                        <option value="">— Pilih TP —</option>
                        <?php foreach ($tps as $tp): ?>
                            <option value="<?= $tp['id'] ?>"><?= esc($tp['tp_code'] ?? $tp['id']) ?> — <?= esc($tp['tp']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Tipe</label>
                    <select name="intervention_type" class="form-select form-select-sm shadow-sm">
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>"><?= esc($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-xs text-muted fw-semibold mb-1">Rencana Kegiatan</label>
                    <input type="text" name="planned_activity" class="form-control form-control-sm shadow-sm" placeholder="mis. Program pengayaan mandiri" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm w-100"><i data-lucide="plus" class="w-4 h-4"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Form Wrapper -->
    <form id="bulkForm" method="POST" action="<?= base_url('interventions/bulk-status') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="status" id="bulkStatusInput" value="APPROVED">

        <!-- Floating / Sticky Bulk Action Bar -->
        <div id="bulkActionBar" class="card border-0 shadow-lg rounded-4 mb-3 bg-dark text-white d-none" style="position: sticky; top: 16px; z-index: 1020;">
            <div class="card-body py-2 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="check-square" class="text-primary w-5 h-5"></i>
                    <span class="fw-semibold text-sm"><span id="selectedCount">0</span> intervensi terpilih</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" onclick="submitBulk('APPROVED')">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Setujui Massal
                    </button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm d-flex align-items-center gap-1" onclick="submitBulk('CANCELLED')">
                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Batalkan Massal
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="deselectAll()">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-xs text-muted text-uppercase tracking-wider">
                        <tr>
                            <th class="ps-3 py-3" style="width:40px;">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll(this)" title="Pilih Semua">
                            </th>
                            <th class="px-3 py-3">Siswa</th>
                            <th class="px-3 py-3">TP</th>
                            <th class="px-3 py-3">Tipe</th>
                            <th class="px-3 py-3">Rencana</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Hasil</th>
                            <th class="px-3 py-3 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($interventions === []): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">Belum ada intervensi.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($interventions as $intervention): ?>
                            <?php
                            $statusBadge = [
                                'RECOMMENDED' => 'bg-warning-subtle text-warning',
                                'APPROVED'    => 'bg-info-subtle text-info',
                                'COMPLETED'   => 'bg-success-subtle text-success',
                                'CANCELLED'   => 'bg-secondary-subtle text-secondary',
                            ];
                            $typeBadge = [
                                'REMEDIAL'      => 'bg-danger-subtle text-danger',
                                'REINFORCEMENT' => 'bg-warning-subtle text-warning',
                                'ENRICHMENT'    => 'bg-primary-subtle text-primary',
                            ];
                            $canSelect = in_array($intervention['status'], ['RECOMMENDED', 'APPROVED'], true);
                            ?>
                            <tr>
                                <td class="ps-3 py-3">
                                    <?php if ($canSelect): ?>
                                        <input type="checkbox" name="ids[]" value="<?= (int) $intervention['id'] ?>" class="form-check-input row-checkbox" onchange="updateBulkBar()">
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="fw-semibold text-gray-900"><?= esc($intervention['full_name'] ?? '-') ?></div>
                                    <div class="text-xs text-muted"><?= esc($intervention['classroom_name'] ?? '') ?></div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="badge bg-light text-dark rounded-pill"><?= esc($intervention['tp_code'] ?? '-') ?></span>
                                    <?php if (! empty($intervention['criterion_text'])): ?>
                                        <div class="text-xs text-muted mt-1">Kriteria: <?= esc($intervention['criterion_text']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="badge <?= $typeBadge[$intervention['intervention_type']] ?? 'bg-light' ?> rounded-pill"><?= esc($intervention['intervention_type']) ?></span>
                                </td>
                                <td class="px-3 py-3 text-muted small" style="max-width:280px"><?= esc($intervention['planned_activity']) ?></td>
                                <td class="px-3 py-3">
                                    <span class="badge <?= $statusBadge[$intervention['status']] ?? 'bg-light' ?> rounded-pill"><?= esc($intervention['status']) ?></span>
                                </td>
                                <td class="px-3 py-3 text-muted small" style="max-width:220px">
                                    <?php if ($intervention['status'] === 'COMPLETED'): ?>
                                        <?= esc($intervention['outcome'] ?? 'Selesai') ?>
                                    <?php else: ?>
                                        <form method="POST" action="<?= base_url('interventions/' . $intervention['id']) ?>" class="d-flex gap-1">
                                            <?= csrf_field() ?>
                                            <input type="text" name="outcome" class="form-control form-control-sm" placeholder="Catatan hasil...">
                                            <input type="hidden" name="status" value="COMPLETED">
                                            <button type="submit" class="btn btn-sm btn-outline-success shadow-sm" title="Tandai selesai"><i data-lucide="check-check" class="w-3.5 h-3.5"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <?php if ($intervention['status'] === 'RECOMMENDED'): ?>
                                            <form method="POST" action="<?= base_url('interventions/' . $intervention['id']) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="APPROVED">
                                                <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm" title="Setujui"><i data-lucide="check" class="w-3.5 h-3.5"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (in_array($intervention['status'], ['RECOMMENDED', 'APPROVED'], true)): ?>
                                            <form method="POST" action="<?= base_url('interventions/' . $intervention['id']) ?>" onsubmit="return confirm('Batalkan intervensi ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="CANCELLED">
                                                <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm" title="Batalkan"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const bar = document.getElementById('bulkActionBar');
    const countEl = document.getElementById('selectedCount');
    countEl.textContent = checked.length;
    if (checked.length > 0) {
        bar.classList.remove('d-none');
    } else {
        bar.classList.add('d-none');
        const master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
    }
}

function deselectAll() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    const master = document.getElementById('selectAllCheckbox');
    if (master) master.checked = false;
    updateBulkBar();
}

function submitBulk(status) {
    const count = document.querySelectorAll('.row-checkbox:checked').length;
    if (count === 0) return;
    const label = status === 'APPROVED' ? 'menyetujui' : 'membatalkan';
    if (confirm(`Apakah Anda yakin ingin ${label} ${count} intervensi terpilih secara massal?`)) {
        document.getElementById('bulkStatusInput').value = status;
        document.getElementById('bulkForm').submit();
    }
}
</script>
<?= $this->endSection() ?>