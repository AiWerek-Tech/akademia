<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Copy Rombongan Belajar Antar Periode</h4>
        <p class="text-muted fs-7 mb-0">Duplikasi struktur rombel dari satu periode akademik ke periode akademik berikutnya</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('classrooms/copy-period') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Periode Asal (Sumber) <span class="text-danger">*</span></label>
                    <select name="source_period_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Periode Asal --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('source_period_id') == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Periode Tujuan (Target) <span class="text-danger">*</span></label>
                    <select name="target_period_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Periode Tujuan --</option>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('target_period_id') == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info border-0 rounded-3 fs-7 mb-0">
                        <strong>Catatan Penting:</strong>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Hanya rombel yang aktif yang akan diduplikasi.</li>
                            <li>Wali kelas dan Ruang default tetap akan dipertahankan jika guru/ruang tersebut masih tersedia.</li>
                            <li>Data siswa di dalam rombel <strong>tidak akan ikut dipindahkan</strong> (hanya wadah rombel/kelasnya saja).</li>
                            <li>Sistem akan mendeteksi dan melewati (skip) kode rombel yang sudah ada di periode tujuan untuk menghindari duplikasi data.</li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('classrooms') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Proses Copy</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
