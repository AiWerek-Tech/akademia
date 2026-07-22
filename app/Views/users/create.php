<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Tambah Pengguna Baru</h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('users') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" required value="<?= esc(old('username')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" required value="<?= esc(old('email')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="full_name" name="full_name" required value="<?= esc(old('full_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Kata Sandi Awal <span class="text-danger">*</span></label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" required>
                            <div class="form-text fs-9">Min. 12 karakter, huruf besar, huruf kecil, angka, dan simbol.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label fw-semibold">Status Akun <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="is_active" name="is_active" required>
                                <option value="1" <?= old('is_active') == '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= old('is_active') == '0' ? 'selected' : '' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="must_change_password" class="form-label fw-semibold">Wajib Ganti Password <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="must_change_password" name="must_change_password" required>
                                <option value="1" <?= old('must_change_password') == '1' ? 'selected' : '' ?>>Ya (Saat login pertama kali)</option>
                                <option value="0" <?= old('must_change_password') == '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Peran Pengguna (Roles) <span class="text-danger">*</span></label>
                            <div class="bg-light p-3 rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                                <?php foreach ($roles as $role): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>" <?= is_array(old('roles')) && in_array($role['id'], old('roles')) ? 'checked' : '' ?>>
                                        <label class="form-check-label fs-7" for="role_<?= $role['id'] ?>">
                                            <?= esc($role['name']) ?> (<?= esc($role['code']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Akses Unit Sekolah <span class="text-danger">*</span></label>
                            <div class="bg-light p-3 rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                                <?php foreach ($units as $unit): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="units[]" value="<?= $unit['id'] ?>" id="unit_<?= $unit['id'] ?>" <?= is_array(old('units')) && in_array($unit['id'], old('units')) ? 'checked' : '' ?>>
                                        <label class="form-check-label fs-7" for="unit_<?= $unit['id'] ?>">
                                            <?= esc($unit['name']) ?> (<?= esc($unit['code']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('users') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
<?= $this->endSection() ?>
