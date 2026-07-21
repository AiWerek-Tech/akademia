<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Edit Ruangan</h4>
        <p class="text-muted fs-7 mb-0">Ubah identitas, tipe, kapasitas, atau status aktif ruangan</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('rooms/' . $room['uuid']) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Unit Pengelola</label>
                    <input type="text" class="form-control rounded-3" value="<?= esc($room['unit_name']) ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Jenis Ruangan <span class="text-danger">*</span></label>
                    <select name="type" class="form-select rounded-3" required>
                        <option value="TEORITIS" <?= $room['type'] === 'TEORITIS' ? 'selected' : '' ?>>Teoritis / Kelas Belajar</option>
                        <option value="LABORATORIUM" <?= $room['type'] === 'LABORATORIUM' ? 'selected' : '' ?>>Laboratorium / Ruang Praktikum</option>
                        <option value="PERPUSTAKAAN" <?= $room['type'] === 'PERPUSTAKAAN' ? 'selected' : '' ?>>Perpustakaan</option>
                        <option value="LAINNYA" <?= $room['type'] === 'LAINNYA' ? 'selected' : '' ?>>Lainnya / Kantor / Aula</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Kursi <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control rounded-3" value="<?= esc($room['capacity']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Ruang <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" value="<?= esc($room['code']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= esc($room['name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Lokasi / Lantai / Gedung</label>
                    <input type="text" name="location" class="form-control rounded-3" value="<?= esc($room['location']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Status Aktif</label>
                    <select name="is_active" class="form-select rounded-3">
                        <option value="1" <?= (int)$room['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (int)$room['is_active'] === 0 ? 'selected' : '' ?>>Non-Aktif</option>
                    </select>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('rooms') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Perbarui Ruangan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
