<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Tambah Guru Baru</h4>
        <p class="text-muted fs-7 mb-0">Input data master identitas dan kepegawaian guru global</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('teachers') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Gelar Depan</label>
                    <input type="text" name="title_prefix" class="form-control rounded-3" placeholder="misal: Drs., Dr." value="<?= esc(old('title_prefix')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control rounded-3" placeholder="Nama tanpa gelar" value="<?= esc(old('full_name')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Gelar Belakang</label>
                    <input type="text" name="degree_suffix" class="form-control rounded-3" placeholder="misal: S.Pd, M.Pd" value="<?= esc(old('degree_suffix')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">NIP (ASN/PNS)</label>
                    <input type="text" name="nip" class="form-control rounded-3" placeholder="Nomor Induk Pegawai" value="<?= esc(old('nip')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">NIK (KTP)</label>
                    <input type="text" name="nik" class="form-control rounded-3" placeholder="Nomor Induk Kependudukan" value="<?= esc(old('nik')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Nomor Pegawai / NIPG</label>
                    <input type="text" name="employee_number" class="form-control rounded-3" placeholder="Identitas internal sekolah" value="<?= esc(old('employee_number')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Jenis Kelamin</label>
                    <select name="gender" class="form-select rounded-3">
                        <option value="">-- Pilih --</option>
                        <option value="LAKI_LAKI" <?= old('gender') === 'LAKI_LAKI' ? 'selected' : '' ?>>Laki-Laki</option>
                        <option value="PEREMPUAN" <?= old('gender') === 'PEREMPUAN' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Tempat Lahir</label>
                    <input type="text" name="birth_place" class="form-control rounded-3" value="<?= esc(old('birth_place')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control rounded-3" value="<?= esc(old('birth_date')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Status Kepegawaian <span class="text-danger">*</span></label>
                    <select name="employment_status" class="form-select rounded-3" required>
                        <option value="GURU_TETAP">Guru Tetap Yayasan (GTY)</option>
                        <option value="GURU_HONORER">Guru Honorer / DPK</option>
                        <option value="PNS_DPK">PNS DPK</option>
                        <option value="PART_TIME">Guru Paruh Waktu</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Unit Sekolah Utama <span class="text-danger">*</span></label>
                    <select name="primary_unit_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Unit Utama --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (string)old('primary_unit_id', $activeUnitId ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                                <?= esc($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Telepon / Whatsapp</label>
                    <input type="text" name="phone" class="form-control rounded-3" placeholder="08xxxxxxxxxx" value="<?= esc(old('phone')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Email</label>
                    <input type="email" name="email" class="form-control rounded-3" placeholder="guru@wmvaa.sch.id" value="<?= esc(old('email')) ?>">
                </div>

                <div class="col-12 mt-2">
                    <div class="card border rounded-3 bg-light p-3">
                        <label class="form-label fs-8 fw-bold text-dark mb-1">
                            <i class="bi bi-buildings me-1 text-primary"></i> Penugasan Unit Sekolah Tambahan (Sekolah Kedua / Lintas Unit)
                        </label>
                        <small class="text-muted d-block mb-2">Centang unit sekolah di mana guru ini juga mengajar selain dari Unit Sekolah Utama.</small>
                        <div class="row g-2">
                            <?php foreach ($units as $u): ?>
                                <div class="col-md-4">
                                    <div class="form-check p-2 rounded bg-white border">
                                        <input class="form-check-input me-2" type="checkbox" name="additional_units[]" value="<?= $u['id'] ?>" id="create_unit_cb_<?= $u['id'] ?>">
                                        <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="create_unit_cb_<?= $u['id'] ?>">
                                            <?= esc($u['name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Alamat Tempat Tinggal</label>
                    <textarea name="address" class="form-control rounded-3" rows="2"><?= esc(old('address')) ?></textarea>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('teachers') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Simpan Data Guru</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
