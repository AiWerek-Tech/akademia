<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:800px">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('quality/reflections') ?>">Refleksi</a></li><li class="breadcrumb-item active"><?= ($isEdit ?? false) ? 'Edit' : 'Baru' ?></li></ol></nav>
        <h1 class="h3 fw-bold text-gray-900 mb-1"><?= ($isEdit ?? false) ? 'Edit Refleksi' : 'Buat Refleksi Baru' ?></h1>
        <p class="text-muted mb-0">Refleksi post-lesson, periodik, atau tahunan.</p>
    </div>

    <form method="POST" action="<?= base_url('quality/reflection/create') ?>" class="card border-0 shadow-sm rounded-4">
        <?= csrf_field() ?>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Guru <span class="text-danger">*</span></label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Pilih Guru</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Tipe Refleksi</label>
                    <select name="reflection_type" class="form-select">
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type ?>"><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select">
                        <option value="">— Pilih —</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="classroom_id" class="form-select">
                        <option value="">— Pilih —</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Yang Berjalan Baik</label>
                    <textarea name="what_went_well" class="form-control" rows="3" placeholder="Apa yang sudah berjalan dengan baik dalam sesi ini?"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Yang Perlu Diperbaiki</label>
                    <textarea name="what_to_improve" class="form-control" rows="3" placeholder="Apa yang perlu diperbaiki untuk sesi berikutnya?"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Langkah Selanjutnya</label>
                    <textarea name="next_steps" class="form-control" rows="3" placeholder="Tindakan konkret yang akan dilakukan..."></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 p-4 d-flex gap-2 justify-content-end">
            <a href="<?= base_url('quality/reflections') ?>" class="btn btn-outline-secondary rounded-pill">Batal</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
