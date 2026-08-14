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
        <form method="POST" action="<?= base_url('classrooms') ?>">
            <?= csrf_field() ?>
            <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
            <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Unit Sekolah <span class="text-danger">*</span></label>
                    <select name="unit_id" id="unit_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (string)old('unit_id', $activeUnitId ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Periode Akademik <span class="text-danger">*</span></label>
                    <select name="academic_period_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php foreach ($periods as $p): ?>
                            <?php
                            $semText = ((int)($p['semester_number'] ?? 0) === 1) ? 'Ganjil' : 'Genap';
                            $periodLabel = 'T.A. ' . esc($p['year_name'] ?? '') . ' - Semester ' . esc($p['semester_number'] ?? '') . ' (' . $semText . ')';
                            if (!empty(trim($p['name'] ?? ''))) {
                                $periodLabel = esc($p['name']) . ' (' . $periodLabel . ')';
                            }
                            ?>
                            <option value="<?= $p['id'] ?>" <?= old('academic_period_id', $activePeriodId) == $p['id'] ? 'selected' : '' ?>><?= $periodLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
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

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace" placeholder="Contoh: VIIA-26" value="<?= esc(old('code')) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Rombel <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Kelas VII A" value="<?= esc(old('name')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Siswa</label>
                    <input type="number" name="capacity" class="form-control rounded-3" value="<?= esc(old('capacity', 36)) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="form-select rounded-3">
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= old('homeroom_teacher_id') == $t['id'] ? 'selected' : '' ?>><?= esc($t['full_name']) ?><?= !empty($t['employee_number']) ? ' — ' . esc($t['employee_number']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Ruang Default Rombel</label>
                    <select name="default_room_id" class="form-select rounded-3">
                        <option value="">-- Pilih Ruangan --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= old('default_room_id') == $r['id'] ? 'selected' : '' ?>><?= esc($r['name']) ?> (<?= esc($r['room_type_name']) ?>)</option>
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
