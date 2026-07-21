<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('settings/units') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Edit Profil Unit: <?= esc($unit['code']) ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('settings/units/' . $unit['uuid']) ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label fw-semibold">Nama Unit Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="name" name="name" value="<?= old('name', $unit['name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="short_name" class="form-label fw-semibold">Singkatan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="short_name" name="short_name" value="<?= old('short_name', $unit['short_name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="npsn" class="form-label fw-semibold">NPSN</label>
                            <input type="text" class="form-control rounded-3" id="npsn" name="npsn" value="<?= old('npsn', $unit['npsn']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="timezone" class="form-label fw-semibold">Zona Waktu <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="timezone" name="timezone" required>
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $tz === old('timezone', $unit['timezone']) ? 'selected' : '' ?>>
                                        <?= $tz ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control rounded-3" id="address" name="address" rows="3"><?= old('address', $unit['address']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Telepon Kantor</label>
                            <input type="text" class="form-control rounded-3" id="phone" name="phone" value="<?= old('phone', $unit['phone']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Resmi</label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= old('email', $unit['email']) ?>">
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('settings/units') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
