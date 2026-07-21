<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Tambah Rombongan Belajar (Rombel)</h4>
        <p class="text-muted fs-7 mb-0">Pendaftaran kelas/rombongan belajar baru dalam periode akademik aktif</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('classrooms/store') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Unit Sekolah <span class="text-danger">*</span></label>
                    <select name="unit_id" id="unit_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= old('unit_id') == $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Periode Akademik <span class="text-danger">*</span></label>
                    <select name="academic_period_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('academic_period_id') == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Tingkat Kelas <span class="text-danger">*</span></label>
                    <select name="grade_level_id" id="grade_level_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Tingkat --</option>
                        <?php foreach ($gradeLevels as $gl): ?>
                            <option value="<?= $gl['id'] ?>" data-unit="<?= $gl['unit_id'] ?>" <?= old('grade_level_id') == $gl['id'] ? 'selected' : '' ?>>
                                <?= esc($gl['name']) ?> (<?= esc($gl['unit_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Jurusan / Peminatan</label>
                    <select name="major" class="form-select rounded-3">
                        <option value="">Umum (Tanpa Peminatan)</option>
                        <option value="MIPA" <?= old('major') === 'MIPA' ? 'selected' : '' ?>>MIPA (Matematika & IPA)</option>
                        <option value="IPS" <?= old('major') === 'IPS' ? 'selected' : '' ?>>IPS (Ilmu Pengetahuan Sosial)</option>
                        <option value="BAHASA" <?= old('major') === 'BAHASA' ? 'selected' : '' ?>>Bahasa & Budaya</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace" placeholder="Contoh: VIIA-26" value="<?= old('code') ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Kelas VII A" value="<?= old('name') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Siswa</label>
                    <input type="number" name="capacity" class="form-control rounded-3" value="<?= old('capacity', 36) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="form-select rounded-3">
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= old('homeroom_teacher_id') == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Ruang Default Rombel</label>
                    <select name="default_room_id" class="form-select rounded-3">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= old('default_room_id') == $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?> (<?= esc($r['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('classrooms') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Rombel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('unit_id');
    const gradeSelect = document.getElementById('grade_level_id');
    const gradeOptions = Array.from(gradeSelect.options);

    function filterGrades() {
        const selectedUnit = unitSelect.value;
        gradeSelect.innerHTML = '';
        gradeOptions.forEach(opt => {
            if (!opt.value || !selectedUnit || opt.getAttribute('data-unit') === selectedUnit) {
                gradeSelect.appendChild(opt);
            }
        });
    }

    unitSelect.addEventListener('change', filterGrades);
    if (unitSelect.value) {
        filterGrades();
    }
});
</script>
<?= $this->endSection() ?>
