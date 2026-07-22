<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="d-flex justify-content-between align-items-start mb-4"><div><h4 class="fw-bold mb-1 text-slate-800">Tambah Tingkat Kelas</h4><p class="text-muted fs-7 mb-0">Buat tingkat baru pada unit sekolah yang Anda kelola.</p></div></div>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
<form method="POST" action="<?= base_url('grade-levels/store') ?>"><?= csrf_field() ?>
<?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
<?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-3">
<div class="col-md-4"><label class="form-label fw-bold">Unit Sekolah <span class="text-danger">*</span></label><select name="unit_id" class="form-select rounded-3" required><option value="">-- Pilih Unit --</option><?php foreach ($units as $unit): ?><option value="<?= esc($unit['id']) ?>" <?= (string) old('unit_id') === (string) $unit['id'] ? 'selected' : '' ?>><?= esc($unit['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label fw-bold">Nomor Tingkat <span class="text-danger">*</span></label><input type="number" min="1" name="grade_number" class="form-control rounded-3" value="<?= esc(old('grade_number')) ?>" required></div>
<div class="col-md-3"><label class="form-label fw-bold">Kode <span class="text-danger">*</span></label><input name="code" maxlength="20" class="form-control rounded-3 text-uppercase font-monospace" placeholder="Contoh: VII" value="<?= esc(old('code')) ?>" required></div>
<div class="col-md-3"><label class="form-label fw-bold">Fase <span class="text-danger">*</span></label><select name="phase" class="form-select rounded-3" required><option value="">-- Pilih Fase --</option><?php foreach (['A','B','C','D','E','F'] as $phase): ?><option value="<?= $phase ?>" <?= old('phase') === $phase ? 'selected' : '' ?>>Fase <?= $phase ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label fw-bold">Nama Tingkat <span class="text-danger">*</span></label><input name="name" maxlength="50" class="form-control rounded-3" placeholder="Contoh: Kelas VII" value="<?= esc(old('name')) ?>" required></div>
<div class="col-md-3"><label class="form-label fw-bold">Urutan Tampilan</label><input type="number" min="0" name="sort_order" class="form-control rounded-3" value="<?= esc(old('sort_order')) ?>"></div>
<div class="col-md-3"><label class="form-label fw-bold">Status</label><select name="is_active" class="form-select rounded-3"><option value="1">Aktif</option><option value="0" <?= old('is_active') === '0' ? 'selected' : '' ?>>Nonaktif</option></select></div>
<div class="col-12 d-flex justify-content-end gap-2 mt-4"><a href="<?= base_url('grade-levels') ?>" class="btn btn-light rounded-3 px-4">Batal</a><button class="btn btn-primary rounded-3 px-4">Simpan Tingkat</button></div>
</div></form></div></div>
<?= $this->endSection() ?>
