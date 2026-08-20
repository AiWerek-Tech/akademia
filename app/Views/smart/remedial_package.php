<?= $this->extend('layouts/admin') ?>

<?= $this->section('additional_css') ?>
<style>
.remedial-card { border-radius: 16px; border: none; }
.scaffold-step-card {
    border-left: 4px solid #6366f1; border-radius: 12px;
    background: #fff; padding: 16px; margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.scaffold-step-num {
    width: 32px; height: 32px; border-radius: 50%;
    background: #6366f1; color: #fff; display: flex;
    align-items: center; justify-content: center; font-weight: 700; font-size: .85rem;
}
@media print {
    .sidebar, .navbar, .btn-print, form, .no-print { display: none !important; }
    .layout-wrapper { margin: 0; padding: 0; }
}
</style>
<?= $this->endSection() ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-gradient px-3 py-2 rounded-pill fw-semibold text-xs" style="background: linear-gradient(135deg, #ef4444, #f59e0b); color: #fff;">
                    <i data-lucide="heart-handshake" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Smart Remediation
                </span>
            </div>
            <h1 class="h3 fw-bold text-gray-900 mt-2 mb-1">Paket Remedial Terarah</h1>
            <p class="text-muted mb-0">Paket intervensi otomatis berbasis kriteria yang belum tercapai, memadukan re-eksplanasi konsep, scaffolding, dan exit-check.</p>
        </div>
        <?php if ($package): ?>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary shadow-sm btn-print">
                <i data-lucide="printer" class="w-3.5 h-3.5 me-1"></i> Cetak Lembar Remedial
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$package): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                <i data-lucide="file-question" class="w-12 h-12 text-muted mb-3 d-inline-block" style="width:48px;height:48px;"></i><br>
                <span class="fw-semibold">Parameter Siswa & TP Tidak Lengkap</span><br>
                <small>Buka menu <a href="<?= base_url('smart/mastery-heatmap') ?>">Mastery Heatmap</a> atau <a href="<?= base_url('mastery') ?>">Mastery TP</a> lalu klik intervensi remedial pada siswa yang membutuhkan pendampingan.</small>
            </div>
        </div>
    <?php else: ?>
        <?php
        $student = $package['student'];
        $objective = $package['objective'];
        $mastery = $package['mastery'];
        $concepts = $package['concepts'];
        $steps = $package['scaffoldingSteps'];
        ?>

        <!-- Student & Objective Info Header -->
        <div class="card remedial-card shadow-sm bg-white mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold">Target Siswa & Mata Pelajaran</div>
                        <h4 class="fw-bold text-gray-900 mb-1"><?= esc($student['full_name'] ?? 'Siswa') ?></h4>
                        <div class="text-sm text-muted">
                            NIS/NISN: <?= esc($student['student_number'] ?? '—') ?> &bull; 
                            Mata Pelajaran: <strong><?= esc($objective['subject_name'] ?? 'Umum') ?></strong> (Fase <?= esc($objective['phase'] ?? 'E') ?>)
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold text-sm">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Status: <?= esc($mastery['result'] ?? 'NEEDS_SUPPORT') ?>
                        </span>
                        <div class="text-xs text-muted mt-1">Kode Paket: <?= esc($package['package_code']) ?></div>
                    </div>
                </div>

                <hr class="my-3 text-muted opacity-25">

                <div>
                    <div class="text-xs text-muted text-uppercase tracking-wider fw-semibold mb-1">Target Tujuan Pembelajaran (TP)</div>
                    <div class="p-3 rounded-3 bg-light border">
                        <span class="badge bg-primary text-white me-2"><?= esc($objective['code'] ?? 'TP') ?></span>
                        <span class="fw-semibold text-gray-800"><?= esc($objective['name'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diagnostic & Essential Concepts -->
        <div class="card remedial-card shadow-sm bg-white mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0">
                    <i data-lucide="lightbulb" class="w-4 h-4 me-2 text-warning d-inline-block"></i>Konsep Esensial & Penanganan Miskonsepsi
                </h6>
            </div>
            <div class="card-body p-4">
                <?php foreach ($concepts as $c): ?>
                    <div class="mb-3 p-3 rounded-3" style="background:#f8fafc; border-left: 4px solid #f59e0b;">
                        <h6 class="fw-bold text-gray-900 mb-1"><?= esc($c['concept_name']) ?></h6>
                        <p class="text-sm text-muted mb-2"><?= esc($c['explanation']) ?></p>
                        <?php if (!empty($c['misconception'])): ?>
                            <div class="p-2 rounded bg-danger-subtle text-danger text-xs mb-1">
                                <strong>Miskonsepsi Siswa:</strong> <?= esc($c['misconception']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($c['misconception_remedy'])): ?>
                            <div class="p-2 rounded bg-success-subtle text-success text-xs">
                                <strong>Solusi Pedagogis:</strong> <?= esc($c['misconception_remedy']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3-Stage Scaffolding Plan -->
        <div class="card remedial-card shadow-sm bg-white mb-4">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold text-gray-800 mb-0">
                    <i data-lucide="list-ordered" class="w-4 h-4 me-2 text-indigo d-inline-block"></i>Alur Aktivitas Remedial 3 Tahap
                </h6>
            </div>
            <div class="card-body p-4">
                <?php foreach ($steps as $step): ?>
                    <div class="scaffold-step-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="scaffold-step-num"><?= $step['step'] ?></div>
                            <div class="flex-grow-1">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-1 mb-1">
                                    <h6 class="fw-bold text-gray-900 mb-0"><?= esc($step['title']) ?></h6>
                                    <span class="badge bg-light text-muted border"><?= esc($step['duration']) ?> &bull; <?= esc($step['type']) ?></span>
                                </div>
                                <p class="text-sm text-muted mb-2"><?= esc($step['description']) ?></p>
                                <div class="text-xs text-indigo fw-semibold">
                                    <i data-lucide="check-square" class="w-3.5 h-3.5 me-1 d-inline-block"></i> Bukti/Luaran: <?= esc($step['deliverable']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Verification Sign-off Box (for print) -->
        <div class="card remedial-card shadow-sm bg-white mb-4">
            <div class="card-body p-4">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="text-xs text-muted mb-5">Siswa Penerima Remedial</div>
                        <div class="fw-bold text-gray-800">( <?= esc($student['full_name'] ?? '...........................') ?> )</div>
                    </div>
                    <div class="col-6">
                        <div class="text-xs text-muted mb-5">Guru Pembimbing / Pengampu</div>
                        <div class="fw-bold text-gray-800">( .................................................... )</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>
