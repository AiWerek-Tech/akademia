<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('academic-periods') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Tambah Periode Akademik Baru</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-8 col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('academic-periods') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="academic_year_id" class="form-label fw-semibold">Tahun Pelajaran <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="academic_year_id" name="academic_year_id" required>
                            <option value="">-- Pilih Tahun Pelajaran --</option>
                            <?php foreach ($years as $yr): ?>
                                <option value="<?= $yr['id'] ?>" <?= old('academic_year_id') == $yr['id'] ? 'selected' : '' ?>>
                                    <?= esc($yr['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="semester_number" class="form-label fw-semibold">Semester <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" id="semester_number" name="semester_number" required>
                            <option value="">-- Pilih Semester --</option>
                            <option value="1" <?= old('semester_number') == '1' ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
                            <option value="2" <?= old('semester_number') == '2' ? 'selected' : '' ?>>Semester 2 (Genap)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="start_date" class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" id="start_date" name="start_date" value="<?= old('start_date') ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="end_date" class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control rounded-3" id="end_date" name="end_date" value="<?= old('end_date') ?>" required>
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('academic-periods') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Periode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
