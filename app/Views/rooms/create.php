<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Tambah Ruangan</h4>
        <p class="text-muted fs-7 mb-0">Pendaftaran ruangan baru dalam fasilitas operasional unit sekolah</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('rooms/store') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Unit Pengelola <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= old('unit_id') == $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Jenis Ruangan <span class="text-danger">*</span></label>
                    <select name="type" class="form-select rounded-3" required>
                        <option value="TEORITIS" <?= old('type') === 'TEORITIS' ? 'selected' : '' ?>>Teoritis / Kelas Belajar</option>
                        <option value="LABORATORIUM" <?= old('type') === 'LABORATORIUM' ? 'selected' : '' ?>>Laboratorium / Ruang Praktikum</option>
                        <option value="PERPUSTAKAAN" <?= old('type') === 'PERPUSTAKAAN' ? 'selected' : '' ?>>Perpustakaan</option>
                        <option value="LAINNYA" <?= old('type') === 'LAINNYA' ? 'selected' : '' ?>>Lainnya / Kantor / Aula</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Kursi <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control rounded-3" value="<?= old('capacity', 36) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Ruang <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" placeholder="Contoh: R-VII-A" value="<?= old('code') ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Ruang Belajar Kelas VII A" value="<?= old('name') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Lokasi / Lantai / Gedung</label>
                    <input type="text" name="location" class="form-control rounded-3" placeholder="Contoh: Gedung A Lantai 2" value="<?= old('location') ?>">
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('rooms') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Ruangan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
