<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?= view('ksp/_header', compact('version')) ?>

<?php
$visions = array_filter($rows, fn($r) => strtoupper($r['statement_type']) === 'VISION');
$missions = array_filter($rows, fn($r) => strtoupper($r['statement_type']) === 'MISSION');
$goals = array_filter($rows, fn($r) => strtoupper($r['statement_type']) === 'GOAL');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Visi, Misi & Tujuan Satuan Pendidikan</h4>
        <p class="small text-muted mb-0">Penyelarasan arah strategis sekolah dengan Profil Lulusan dan tujuan terukur.</p>
    </div>
    <?php if ($canManage): ?>
        <button type="button" class="btn btn-primary rounded-pill px-4 btn-sm" data-bs-toggle="modal" data-bs-target="#createGoalModal">
            <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Tambah Pernyataan
        </button>
    <?php endif; ?>
</div>

<!-- 1. VISION HERO BANNER -->
<?php if (!empty($visions)): ?>
    <?php foreach ($visions as $vision): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
            <div class="card-body p-4 p-lg-5 text-white position-relative">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-indigo rounded-pill px-3 py-1 font-monospace" style="background-color: #6366f1;">
                        <i data-lucide="sparkles" class="me-1" style="width: 13px; height: 13px;"></i>VISI SEKOLAH
                    </span>
                </div>
                <h3 class="fw-bold mb-0 lh-base text-white" style="max-width: 900px;">
                    “<?= esc($vision['statement']) ?>”
                </h3>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light p-4 text-center text-muted">
        <i data-lucide="telescope" class="d-block mx-auto mb-2 text-muted" style="width: 36px; height: 36px;"></i>
        <h6 class="fw-bold text-dark">Visi Sekolah Belum Ditetapkan</h6>
        <p class="small text-muted mb-0">Klik tombol "Tambah Pernyataan" untuk mendefinisikan Visi Satuan Pendidikan.</p>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- 2. MISSION LIST -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2">
                    <i data-lucide="compass" style="width: 20px; height: 20px;"></i>
                </div>
                <h5 class="fw-bold mb-0">Misi Sekolah (<?= count($missions) ?>)</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php $mIdx = 1; foreach ($missions as $mission): ?>
                    <div class="list-group-item px-0 py-3 border-bottom d-flex gap-3 align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.85rem;">
                            <?= $mIdx++ ?>
                        </div>
                        <div class="small text-dark fw-medium lh-base pt-1">
                            <?= nl2br(esc($mission['statement'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($missions)): ?>
                    <div class="text-center text-muted py-5">
                        <i data-lucide="compass" class="d-block mx-auto mb-2 text-muted" style="width: 32px; height: 32px;"></i>
                        Misi sekolah belum disusun.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. MEASURABLE GOALS -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-3 bg-success bg-opacity-10 text-success p-2">
                    <i data-lucide="target" style="width: 20px; height: 20px;"></i>
                </div>
                <h5 class="fw-bold mb-0">Tujuan Strategis & Terukur (<?= count($goals) ?>)</h5>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($goals as $goal): ?>
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1 font-monospace small">
                                TUJUAN
                            </span>
                            <?php if (!empty($goal['target_value'])): ?>
                                <span class="badge bg-white text-dark border rounded-pill px-2 py-1 small">
                                    <i data-lucide="flag" class="me-1 text-primary" style="width: 12px; height: 12px;"></i>Target: <?= esc($goal['target_value']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-dark fw-medium lh-base">
                            <?= nl2br(esc($goal['statement'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($goals)): ?>
                    <div class="text-center text-muted py-5">
                        <i data-lucide="target" class="d-block mx-auto mb-2 text-muted" style="width: 32px; height: 32px;"></i>
                        Tujuan strategis belum disusun.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Create Goal / Vision / Mission -->
<div class="modal fade" id="createGoalModal" tabindex="-1" aria-labelledby="createGoalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-light rounded-top-4 p-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-primary text-white p-2">
                        <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h5 class="modal-title fw-bold" id="createGoalModalLabel">Tambah Visi, Misi, atau Tujuan</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="<?= base_url('education/ksp/'.$version['uuid'].'/vision-goals') ?>">
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis Pernyataan</label>
                            <select class="form-select rounded-3" name="statement_type" required>
                                <option value="VISION">VISION (Visi Sekolah)</option>
                                <option value="MISSION">MISSION (Misi Sekolah)</option>
                                <option value="GOAL" selected>GOAL (Tujuan Strategis)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Target Nilai / Indikator (Khusus Goal)</label>
                            <input class="form-control rounded-3" name="target_value" placeholder="Contoh: 100% siswa mencapai KKTP, 80% lulus PTN">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Isi Teks Pernyataan</label>
                            <textarea class="form-control rounded-3" rows="4" name="statement" placeholder="Tuliskan pernyataan visi, butir misi, atau tujuan strategis..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Pernyataan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
