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

            <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
            <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

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
                    <select name="room_type_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Jenis Ruang --</option>
                        <?php foreach ($roomTypes as $roomType): ?>
                            <option value="<?= esc($roomType['id']) ?>" <?= (string) old('room_type_id') === (string) $roomType['id'] ? 'selected' : '' ?>><?= esc($roomType['name']) ?> (<?= esc($roomType['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Kursi <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control rounded-3" min="0" value="<?= esc(old('capacity', 36)) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Ruang <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" placeholder="Contoh: R-VII-A" value="<?= esc(old('code')) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Ruang Belajar Kelas VII A" value="<?= esc(old('name')) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Lokasi / Gedung</label>
                    <input type="text" name="location" class="form-control rounded-3" placeholder="Contoh: Gedung A Lantai 2" value="<?= esc(old('location')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Lantai</label>
                    <input type="text" name="floor" class="form-control rounded-3" placeholder="Contoh: 2" value="<?= esc(old('floor')) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="shared_between_units" value="0">
                        <input class="form-check-input" type="checkbox" name="shared_between_units" value="1" id="sharedRoom" <?= old('shared_between_units') ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="sharedRoom">Dapat dipakai lintas unit</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Fasilitas</label>
                    <input type="text" name="facilities_text" class="form-control rounded-3" placeholder="Pisahkan dengan koma, contoh: Proyektor, AC, Papan Tulis" value="<?= esc(old('facilities_text')) ?>">
                    <div class="form-text">Fasilitas akan tersimpan sebagai daftar dan tampil konsisten pada ekspor maupun impor.</div>
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
