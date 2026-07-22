<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= base_url('duties') ?>">Tugas Tambahan</a></li>
                <li class="breadcrumb-item active">Berikan Tugas</li>
            </ol>
        </nav>
        <h1 class="h3 mb-0 text-gray-800">Berikan Tugas Tambahan Baru</h1>
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
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="post" action="<?= base_url('duties') ?>">
                <?= csrf_field() ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Versi Penugasan Target <span class="text-danger">*</span></label>
                        <select name="assignment_version_id" class="form-select" required>
                            <option value="">-- Pilih Versi Penugasan --</option>
                            <?php foreach ($versions as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= old('assignment_version_id') == $v['id'] ? 'selected' : '' ?>>
                                    <?= esc($v['name']) ?> (<?= esc($v['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Unit Scope (Opsional)</label>
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
                        <label class="form-label fw-bold small">Guru Pengampu <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-select select2" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= old('teacher_id') == $t['id'] ? 'selected' : '' ?>>
                                    <?= esc($t['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Jenis Tugas Tambahan <span class="text-danger">*</span></label>
                        <select name="duty_type_id" class="form-select" required>
                            <option value="">-- Pilih Jenis Tugas --</option>
                            <?php foreach ($types as $ty): ?>
                                <option value="<?= $ty['id'] ?>" <?= old('duty_type_id') == $ty['id'] ? 'selected' : '' ?>>
                                    <?= esc($ty['name']) ?> (<?= esc($ty['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Judul Khusus / Jabatan (Opsional)</label>
                        <input type="text" name="title_override" class="form-control" placeholder="Misal: Wali Kelas 10-MIPA-1" value="<?= esc(old('title_override')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Alokasi Beban Jam (JP) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="workload_hours" class="form-control" placeholder="Contoh: 12" value="<?= esc(old('workload_hours', 12)) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nomor SK / Referensi Dokumen</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Misal: SK/001/2026" value="<?= esc(old('reference_number')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Catatan Tambahan</label>
                        <input type="text" name="notes" class="form-control" value="<?= esc(old('notes')) ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= base_url('duties') ?>" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Tugas Tambahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
