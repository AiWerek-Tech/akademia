<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:800px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality/reflections') ?>">Refleksi</a></li><li class="breadcrumb-item active">Detail</li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1">Refleksi — <?= esc($reflection['teacher_name'] ?? '') ?></h1>
        <p class="text-muted mb-0">
            <?= $reflection['reflection_type'] ?> · <?= esc($reflection['subject_name'] ?? '—') ?> · <?= esc($reflection['classroom_name'] ?? '—') ?> ·
            <?php $sc = $reflection['status'] === 'PUBLISHED' ? 'success' : 'secondary'; ?>
            <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> rounded-pill"><?= $reflection['status'] ?></span>
        </p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-muted small text-uppercase mb-2">Yang Berjalan Baik</h6>
            <div class="p-3 rounded-3 bg-success-subtle mb-3">
                <p class="mb-0"><?= nl2br(esc($reflection['what_went_well'] ?? '—')) ?></p>
            </div>
            <h6 class="fw-bold text-muted small text-uppercase mb-2">Yang Perlu Diperbaiki</h6>
            <div class="p-3 rounded-3 bg-warning-subtle mb-3">
                <p class="mb-0"><?= nl2br(esc($reflection['what_to_improve'] ?? '—')) ?></p>
            </div>
            <h6 class="fw-bold text-muted small text-uppercase mb-2">Langkah Selanjutnya</h6>
            <div class="p-3 rounded-3 bg-info-subtle">
                <p class="mb-0"><?= nl2br(esc($reflection['next_steps'] ?? '—')) ?></p>
            </div>
        </div>
    </div>

    <?php if ($reflection['ai_draft']): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-info">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2"><i data-lucide="bot" class="text-info me-1" style="width:18px;height:18px"></i> Draft AI</h6>
                <p class="mb-0"><?= nl2br(esc($reflection['ai_draft'])) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($reflection['status'] === 'DRAFT'): ?>
        <form method="POST" action="<?= base_url('quality/reflection/' . $reflection['id'] . '/publish') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success rounded-pill px-4" onclick="return confirm('Yakin ingin mempublikasikan refleksi ini?')">
                <i data-lucide="check-circle" class="w-4 h-4 me-1 d-inline-block"></i> Publikasikan
            </button>
        </form>
    <?php endif; ?>
    <a href="<?= base_url('quality/reflections') ?>" class="btn btn-outline-secondary rounded-pill ms-2">Kembali</a>
</div>
<?= $this->endSection() ?>
