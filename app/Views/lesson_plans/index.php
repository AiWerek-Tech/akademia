<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<?php $pageTitle = 'Rencana Pembelajaran'; $pageIcon = 'book-open'; $pageDescription = 'Daftar rencana pembelajaran untuk unit aktif.'; ?>
<?= view('education_foundation/_page_header', compact('pageTitle', 'pageIcon', 'pageDescription')) ?>

<?php if (has_permission('lesson_plans.manage')): ?>
<div class="mb-4">
    <a href="<?= base_url('lesson-plans/create') ?>" class="btn btn-primary">
        <i data-lucide="plus" class="me-1" style="width:16px"></i>Buat Rencana Baru
    </a>
</div>
<?php endif ?>

<div class="row g-3">
    <?php foreach ($plans as $plan): ?>
        <div class="col-xl-6">
            <article class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1"><?= esc($plan['session_label'] ?? 'Pertemuan ' . (int) $plan['session_number']) ?></h6>
                            <p class="small text-muted mb-0">
                                <?= esc($plan['subject_name'] ?? '-') ?> · <?= esc($plan['grade_name'] ?? '-') ?>
                            </p>
                        </div>
                        <span class="badge text-bg-primary align-self-start"><?= esc($plan['status']) ?></span>
                    </div>
                    <p class="small text-muted mt-2">
                        <?= esc($plan['teacher_name'] ?? '-') ?> · <?= esc($plan['date']) ?>
                    </p>
                    <a class="btn btn-sm btn-primary" href="<?= base_url('lesson-plans/' . $plan['uuid']) ?>">
                        <i data-lucide="layout-dashboard" class="me-1" style="width:15px"></i>Buka
                    </a>
                </div>
            </article>
        </div>
    <?php endforeach ?>
    <?php if (empty($plans)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center text-muted p-5">Belum ada rencana pembelajaran.</div>
            </div>
        </div>
    <?php endif ?>
</div>

<?= $this->endSection() ?>
