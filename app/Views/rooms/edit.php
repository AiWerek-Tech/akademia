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
            <input type="hidden" name="revision_number" value="<?= esc($room['revision_number']) ?>">

            <?php if (session('error')): ?><div class="alert alert-danger rounded-3"><?= esc(session('error')) ?></div><?php endif; ?>
            <?php if (session('errors')): ?><div class="alert alert-danger rounded-3"><ul class="mb-0"><?php foreach (session('errors') as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Unit Pengelola</label>
                    <select name="unit_id" class="form-select rounded-3">
                        <option value="">-- Tanpa unit khusus --</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= esc($unit['id']) ?>" <?= (string) old('unit_id', $room['unit_id']) === (string) $unit['id'] ? 'selected' : '' ?>><?= esc($unit['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Jenis Ruangan <span class="text-danger">*</span></label>
                    <select name="room_type_id" class="form-select rounded-3" required>
                        <?php foreach ($roomTypes as $roomType): ?>
                            <option value="<?= esc($roomType['id']) ?>" <?= (string) old('room_type_id', $room['room_type_id']) === (string) $roomType['id'] ? 'selected' : '' ?>><?= esc($roomType['name']) ?> (<?= esc($roomType['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kapasitas Maksimal Kursi <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control rounded-3" min="0" value="<?= esc(old('capacity', $room['capacity'])) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Kode Ruang <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control rounded-3 font-monospace text-uppercase" value="<?= esc(old('code', $room['code'])) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fs-8 fw-bold">Nama Ruangan <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="<?= esc(old('name', $room['name'])) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Lokasi / Lantai / Gedung</label>
                    <input type="text" name="location" class="form-control rounded-3" value="<?= esc(old('location', $room['location'])) ?>">
                </div>

                <div class="col-md-3"><label class="form-label fs-8 fw-bold">Lantai</label><input type="text" name="floor" class="form-control rounded-3" value="<?= esc(old('floor', $room['floor'])) ?>"></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="shared_between_units" value="0"><input class="form-check-input" type="checkbox" name="shared_between_units" value="1" id="sharedRoom" <?= old('shared_between_units', $room['shared_between_units']) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="sharedRoom">Dapat dipakai lintas unit</label></div></div>
                <div class="col-md-8"><label class="form-label fs-8 fw-bold">Fasilitas</label><input type="text" name="facilities_text" class="form-control rounded-3" value="<?= esc(old('facilities_text', implode(', ', json_decode($room['facilities_json'] ?? '[]', true) ?: []))) ?>" placeholder="Proyektor, AC, Papan Tulis"></div>

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
