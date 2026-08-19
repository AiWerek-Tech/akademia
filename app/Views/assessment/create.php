<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-0 px-md-3">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('assessment') ?>" class="text-decoration-none">Assessment</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Buat</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Buat Assessment</h1>
            <p class="text-muted mb-0">Susun assessment, hubungkan ke TP, dan tetapkan kriteria penilaian.</p>
        </div>
    </div>

    <?= $this->include('assessment/_form', [
        'formAction' => base_url('assessment'),
        'submitLabel' => 'Simpan Assessment',
        'assessment' => null,
        'classrooms' => $classrooms,
        'subjects' => $subjects,
        'tps' => $tps,
        'teachers' => $teachers,
        'types' => $types,
        'forms' => $forms,
        'is_management' => $is_management,
    ]) ?>
</div>
<?= $this->endSection() ?>