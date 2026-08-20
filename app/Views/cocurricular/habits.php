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
        <span class="badge bg-purple-subtle text-purple rounded-pill fw-semibold text-xs text-uppercase tracking-wider">Phase 7 · Opsional</span>
    </div>
    <h1 class="h3 fw-bold text-gray-900 mb-1">Gerakan 7 Kebiasaan Anak Indonesia Hebat</h1>
    <p class="text-muted mb-4">Definisikan kebiasaan, tantangan mingguan, dan pantau check-in siswa per pekan.</p>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i data-lucide="plus" class="w-4 h-4 me-1 text-purple"></i> Tambah Kebiasaan</h6>
            <form method="POST" action="<?= base_url('cocurricular/habits') ?>" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Kode</label><input name="code" class="form-control form-control-sm shadow-sm" placeholder="mis. BANGUN_PAGI"></div>
                <div class="col-md-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Nama <span class="text-danger">*</span></label><input name="name" class="form-control form-control-sm shadow-sm" required></div>
                <div class="col-md-3"><label class="form-label text-xs text-muted fw-semibold mb-1">Tantangan Mingguan</label><input name="weekly_challenge" class="form-control form-control-sm shadow-sm"></div>
                <div class="col-md-2"><label class="form-label text-xs text-muted fw-semibold mb-1">Ikon (Lucide)</label><input name="icon" class="form-control form-control-sm shadow-sm" placeholder="mis. sunrise"></div>
                <div class="col-md-1"><label class="form-label text-xs text-muted fw-semibold mb-1">Urutan</label><input type="number" name="sort_order" value="<?= count($habits) + 1 ?>" class="form-control form-control-sm shadow-sm"></div>
                <div class="col-md-1"><button class="btn btn-primary btn-sm shadow-sm w-100"><i data-lucide="plus" class="w-3 h-3"></i></button></div>
                <div class="col-12"><label class="form-label text-xs text-muted fw-semibold mb-1">Deskripsi</label><textarea name="description" rows="2" class="form-control form-control-sm shadow-sm"></textarea></div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <?php if ($habits === []): ?>
            <div class="col-12"><div class="card border-0 shadow-sm rounded-4"><div class="card-body text-center text-muted py-5"><i data-lucide="smile" class="w-8 h-8 mb-2 d-inline-block"></i><br>Belum ada kebiasaan terdaftar.</div></div></div>
        <?php else: ?>
            <?php foreach ($habits as $h): ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i data-lucide="<?= esc($h['icon'] ?? 'smile') ?>" class="w-6 h-6 text-purple"></i>
                                    <h6 class="fw-bold mb-0"><?= esc($h['name']) ?></h6>
                                </div>
                                <span class="badge <?= (int) $h['enabled'] === 1 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= (int) $h['enabled'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                            </div>
                            <div class="text-xs text-muted mb-2"><?= esc($h['code']) ?></div>
                            <p class="small text-muted mb-2"><?= nl2br(esc($h['description'] ?? '-')) ?></p>
                            <?php if ($h['weekly_challenge']): ?><div class="small bg-light rounded-3 p-2 mb-3"><i data-lucide="flag" class="w-3 h-3 me-1 text-primary"></i><?= esc($h['weekly_challenge']) ?></div><?php endif; ?>
                            <div class="d-flex gap-2">
                                <form method="POST" action="<?= base_url('cocurricular/habits/' . (int) $h['id'] . '/update') ?>" class="flex-grow-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="name" value="<?= esc($h['name']) ?>">
                                    <input type="hidden" name="description" value="<?= esc($h['description'] ?? '') ?>">
                                    <input type="hidden" name="weekly_challenge" value="<?= esc($h['weekly_challenge'] ?? '') ?>">
                                    <input type="hidden" name="icon" value="<?= esc($h['icon'] ?? '') ?>">
                                    <input type="hidden" name="sort_order" value="<?= (int) $h['sort_order'] ?>">
                                    <input type="hidden" name="enabled" value="<?= (int) $h['enabled'] === 1 ? 0 : 1 ?>">
                                    <button class="btn btn-sm btn-outline-<?= (int) $h['enabled'] === 1 ? 'secondary' : 'success' ?> shadow-sm w-100"><?= (int) $h['enabled'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                </form>
                                <form method="POST" action="<?= base_url('cocurricular/habits/' . (int) $h['id'] . '/delete') ?>" onsubmit="return confirm('Hapus kebiasaan ini?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger shadow-sm"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>