<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Edit Rombongan Belajar</h4>
        <p class="text-muted fs-7 mb-0">Perbarui identitas, wali kelas, atau kapasitas rombel</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('classrooms/' . $classroom['uuid']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="revision_number" value="<?= esc($classroom['revision_number']) ?>">
            <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
            <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Unit Sekolah</label>
                    <input type="text" class="form-control rounded-3" value="<?= esc($classroom['unit_name'] ?? '-') ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Periode Akademik</label>
                    <input type="text" class="form-control rounded-3" value="<?= esc($classroom['period_name'] ?? '-') ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Tingkat Kelas</label>
                    <input type="text" class="form-control rounded-3" value="<?= esc($classroom['grade_name'] ?? '-') ?>" disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace" value="<?= esc($classroom['code']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= esc($classroom['name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Siswa</label>
                    <input type="number" name="capacity" class="form-control rounded-3" value="<?= esc($classroom['capacity']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="form-select rounded-3">
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= (int)$classroom['homeroom_teacher_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?><?= !empty($t['employee_number']) ? ' — ' . esc($t['employee_number']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Ruang Default Rombel</label>
                    <select name="default_room_id" class="form-select rounded-3">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= (int)$classroom['default_room_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?> (<?= esc($r['room_type_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Status Aktif</label>
                    <select name="is_active" class="form-select rounded-3">
                        <option value="1" <?= (int)$classroom['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (int)$classroom['is_active'] === 0 ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('classrooms') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Perbarui Rombel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
