<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<?php
$selected = [
    'code'            => $program['code'] ?? '',
    'title'           => $program['title'] ?? '',
    'program_type'    => $program['program_type'] ?? 'COCURRICULAR',
    'theme'           => $program['theme'] ?? '',
    'rationale'       => $program['rationale'] ?? '',
    'objective'       => $program['objective'] ?? '',
    'annual_minutes'  => $program['annual_minutes'] ?? '',
    'delivery_model'  => $program['delivery_model'] ?? 'PROJECT',
    'start_date'      => $program['start_date'] ?? '',
    'end_date'        => $program['end_date'] ?? '',
    'description'     => $program['description'] ?? '',
    'ksp_version_id'  => $program['ksp_version_id'] ?? '',
    'dimension_ids'   => array_column($data['dimensions'], 'dimension_id'),
    'subject_ids'     => array_column($data['subjects'], 'subject_id'),
    'objective_ids'   => array_column($data['objectives'], 'learning_objective_id'),
    'teacher_ids'     => array_column($data['teachers'], 'teacher_id'),
    'classroom_ids'   => array_column($data['classes'], 'classroom_id'),
    'partners'        => $data['partners'],
    'resources'       => $data['resources'],
];
?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge bg-purple-subtle text-purple px-2.5 py-1.5 rounded-pill fw-semibold text-xs text-uppercase tracking-wider">Phase 7 Kokurikuler</span>
    </div>
    <h1 class="h3 fw-bold text-gray-900 mb-1">Ubah Program Kokurikuler</h1>
    <p class="text-muted mb-4">Desain hanya dapat diubah saat program masih berstatus DRAFT.</p>

    <form method="POST" action="<?= base_url('cocurricular/' . (int) $program['id']) ?>">
        <?= csrf_field() ?>
        <?= $this->include('cocurricular/_form') ?>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary shadow-sm px-4"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Perubahan</button>
            <a href="<?= base_url('cocurricular/' . (int) $program['id']) ?>" class="btn btn-outline-secondary shadow-sm">Batal</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>