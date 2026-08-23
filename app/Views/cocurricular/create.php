<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
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
    <h1 class="h3 fw-bold text-gray-900 mb-1">Buat Program Kokurikuler</h1>
    <p class="text-muted mb-4">Lengkapi desain program: tujuan, dimensi profil lulusan, pemetaan lintas disiplin, guru, kelas, mitra, dan sumber daya.</p>

    <form method="POST" action="<?= base_url('cocurricular') ?>">
        <?= csrf_field() ?>
        <?= $this->include('cocurricular/_form') ?>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary shadow-sm px-4"><i data-lucide="save" class="w-4 h-4 me-1"></i> Simpan Program</button>
            <a href="<?= base_url('cocurricular') ?>" class="btn btn-outline-secondary shadow-sm">Batal</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>