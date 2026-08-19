<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php 
$pageTitle = 'Rencana Pembelajaran (Lesson Plans)'; 
$pageIcon = 'book-open'; 
$pageDescription = 'Rencana pelaksanaan pembelajaran harian berbasis Deep Learning (Memahami, Mengaplikasi, Merefleksi) yang terintegrasi jadwal dan paket belajar.'; 
?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<?php
$totalPlans = count($plans);
$draftPlans = count(array_filter($plans, fn($p) => strtoupper($p['status'] ?? '') === 'DRAFT'));
$readyPlans = count(array_filter($plans, fn($p) => in_array(strtoupper($p['status'] ?? ''), ['READY', 'IN_PROGRESS'])));
$completedPlans = count(array_filter($plans, fn($p) => in_array(strtoupper($p['status'] ?? ''), ['COMPLETED', 'REFLECTED'])));
?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3">
                    <i data-lucide="book-open" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $totalPlans ?></div>
                    <div class="small text-muted">Total Rencana</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 text-secondary p-3">
                    <i data-lucide="edit-3" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold"><?= $draftPlans ?></div>
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
                    <div class="fs-4 fw-bold text-warning"><?= $readyPlans ?></div>
                    <div class="small text-muted">Siap / Berjalan</div>
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
                    <div class="fs-4 fw-bold text-success"><?= $completedPlans ?></div>
                    <div class="small text-muted">Selesai & Refleksi</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <input type="text" id="planSearchInput" class="form-control form-control-sm rounded-pill ps-4" placeholder="Cari rencana, mapel, atau guru...">
                <i data-lucide="search" class="position-absolute text-muted" style="top: 8px; left: 12px; width: 14px; height: 14px;"></i>
            </div>
            <?php if (has_permission('lesson_plans.manage')): ?>
                <a href="<?= base_url('lesson-plans/create') ?>" class="btn btn-sm btn-primary rounded-pill px-4">
                    <i data-lucide="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Buat Rencana Baru
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4" id="planGrid">
            <?php foreach ($plans as $plan): ?>
                <?php
                $st = strtoupper($plan['status'] ?? 'DRAFT');
                $stBadge = match($st) {
                    'READY' => 'bg-info text-white',
                    'IN_PROGRESS' => 'bg-warning text-dark',
                    'COMPLETED' => 'bg-success text-white',
                    'REFLECTED' => 'bg-dark text-white',
                    default => 'bg-secondary bg-opacity-10 text-secondary border'
                };
                ?>
                <div class="col-xl-6 plan-card-item">
                    <article class="card border-0 shadow-sm rounded-4 h-100 p-3 card-hover transition-all" style="background: #fafafa; border-top: 4px solid #3b82f6 !important;">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary font-monospace px-3 py-1 rounded-pill mb-1">
                                            Pertemuan #<?= (int)$plan['session_number'] ?>
                                        </span>
                                        <h5 class="fw-bold text-dark mb-1"><?= esc($plan['session_label'] ?? 'Pertemuan ' . (int)$plan['session_number']) ?></h5>
                                        <div class="small text-muted">
                                            <i data-lucide="book" class="me-1" style="width: 12px; height: 12px;"></i><?= esc($plan['subject_name'] ?? '-') ?> · <?= esc($plan['grade_name'] ?? '-') ?>
                                        </div>
                                    </div>
                                    <span class="badge <?= $stBadge ?> rounded-pill px-3 py-1 font-monospace small">
                                        <?= esc($st) ?>
                                    </span>
                                </div>

                                <div class="p-2 bg-white rounded-3 border my-3">
                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                        <span><i data-lucide="user" class="me-1" style="width: 12px; height: 12px;"></i><?= esc($plan['teacher_name'] ?? '-') ?></span>
                                        <span><i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i><?= esc($plan['date']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                <a class="btn btn-sm btn-primary rounded-pill px-3" href="<?= base_url('lesson-plans/' . $plan['uuid']) ?>">
                                    <i data-lucide="layout-dashboard" class="me-1" style="width: 14px; height: 14px;"></i> Buka Detail Rencana
                                </a>
                                <span class="small text-muted font-monospace">Rev <?= (int)$plan['revision_number'] ?></span>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>

            <?php if (empty($plans)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i data-lucide="book-open" class="d-block mx-auto mb-2 text-muted" style="width: 48px; height: 48px;"></i>
                    Belum ada rencana pembelajaran pada unit aktif.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('planSearchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.plan-card-item').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<style>
.transition-all { transition: all 0.25s ease-in-out; }
.card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
</style>
<?= $this->endSection() ?>
