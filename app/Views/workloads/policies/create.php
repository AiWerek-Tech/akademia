<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('workloads/policies') ?>">Kebijakan Beban Kerja</a></li>
                <li class="breadcrumb-item active">Buat Kebijakan</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0 text-gray-800">Buat Kebijakan Beban Kerja Baru</h1>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="<?= base_url('workloads/policies') ?>">
                <?= csrf_field() ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Periode Akademik <span class="text-danger">*</span></label>
                        <select name="academic_period_id" class="form-select" required>
                            <option value="">-- Pilih Periode --</option>
                            <?php foreach ($periods as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= old('academic_period_id') == $p['id'] ? 'selected' : '' ?>>
                                    <?= esc($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Unit Sekolah (Opsional)</label>
                        <select name="unit_id" class="form-select">
                            <option value="">Global (Semua Unit)</option>
                            <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= old('unit_id') == $u['id'] ? 'selected' : '' ?>>
                                    <?= esc($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Status Kepegawaian (Opsional)</label>
                        <input type="text" name="employment_status" class="form-control" placeholder="Contoh: GURU_TETAP, GURU_HONORER" value="<?= esc(old('employment_status')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tipe Pekerjaan (Opsional)</label>
                        <input type="text" name="employment_type" class="form-control" placeholder="Contoh: FULL_TIME, PART_TIME" value="<?= esc(old('employment_type')) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Jam Mengajar Minimum <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="minimum_teaching_hours" class="form-control" placeholder="Contoh: 18" value="<?= esc(old('minimum_teaching_hours')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Target Total Jam</label>
                        <input type="number" step="0.5" name="target_total_hours" class="form-control" placeholder="Contoh: 24" value="<?= esc(old('target_total_hours')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Jam Total Maksimum <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="maximum_total_hours" class="form-control" placeholder="Contoh: 30" value="<?= esc(old('maximum_total_hours')) ?>" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Prioritas Kebijakan</label>
                        <input type="number" name="priority" class="form-control" placeholder="Semakin tinggi semakin diprioritaskan" value="<?= esc(old('priority', 0)) ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('workloads/policies') ?>" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Kebijakan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
