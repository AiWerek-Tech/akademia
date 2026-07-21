<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Edit Tingkat Kelas</h4>
        <p class="text-muted fs-7 mb-0">Sesuaikan nama, fase, atau urutan tingkat kelas</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('grade-levels/' . $gradeLevel['uuid']) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Tingkat (Immutable)</label>
                    <input type="text" class="form-control rounded-3" value="<?= esc($gradeLevel['code']) ?>" disabled>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Tampilan Tingkat <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= esc($gradeLevel['name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Fase Kurikulum Merdeka <span class="text-danger">*</span></label>
                    <select name="phase" class="form-select rounded-3" required>
                        <option value="D" <?= $gradeLevel['phase'] === 'D' ? 'selected' : '' ?>>Fase D (SMP Kelas 7-9)</option>
                        <option value="E" <?= $gradeLevel['phase'] === 'E' ? 'selected' : '' ?>>Fase E (SMA Kelas 10)</option>
                        <option value="F" <?= $gradeLevel['phase'] === 'F' ? 'selected' : '' ?>>Fase F (SMA Kelas 11-12)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Urutan Tampilan</label>
                    <input type="number" name="sort_order" class="form-control rounded-3" value="<?= esc($gradeLevel['sort_order']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Status Aktif</label>
                    <select name="is_active" class="form-select rounded-3">
                        <option value="1" <?= (int)$gradeLevel['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (int)$gradeLevel['is_active'] === 0 ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('grade-levels') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Perbarui Tingkat</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
