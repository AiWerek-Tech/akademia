<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold mb-1 text-slate-800">Edit Data Guru</h4>
        <p class="text-muted fs-7 mb-0"><?= esc($teacher['full_name']) ?></p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="POST" action="<?= base_url('teachers/' . $teacher['uuid']) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="revision_number" value="<?= esc($teacher['revision_number']) ?>">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Gelar Depan</label>
                    <input type="text" name="title_prefix" class="form-control rounded-3" value="<?= esc($teacher['title_prefix']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control rounded-3" value="<?= esc($teacher['full_name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8 fw-bold">Gelar Belakang</label>
                    <input type="text" name="degree_suffix" class="form-control rounded-3" value="<?= esc($teacher['degree_suffix']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">NIP (ASN/PNS)</label>
                    <input type="text" name="nip" class="form-control rounded-3" value="<?= esc($teacher['nip']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">NIK (KTP)</label>
                    <input type="text" name="nik" class="form-control rounded-3" value="<?= esc($teacher['nik']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Nomor Pegawai / NIPG</label>
                    <input type="text" name="employee_number" class="form-control rounded-3" value="<?= esc($teacher['employee_number']) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Jenis Kelamin</label>
                    <select name="gender" class="form-select rounded-3">
                        <option value="">-- Pilih --</option>
                        <option value="LAKI_LAKI" <?= $teacher['gender'] === 'LAKI_LAKI' ? 'selected' : '' ?>>Laki-Laki</option>
                        <option value="PEREMPUAN" <?= $teacher['gender'] === 'PEREMPUAN' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Tempat Lahir</label>
                    <input type="text" name="birth_place" class="form-control rounded-3" value="<?= esc($teacher['birth_place']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-8 fw-bold">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control rounded-3" value="<?= esc($teacher['birth_date']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Status Kepegawaian <span class="text-danger">*</span></label>
                    <select name="employment_status" class="form-select rounded-3" required>
                        <option value="GURU_TETAP" <?= $teacher['employment_status'] === 'GURU_TETAP' ? 'selected' : '' ?>>Guru Tetap Yayasan (GTY)</option>
                        <option value="GURU_HONORER" <?= $teacher['employment_status'] === 'GURU_HONORER' ? 'selected' : '' ?>>Guru Honorer / DPK</option>
                        <option value="PNS_DPK" <?= $teacher['employment_status'] === 'PNS_DPK' ? 'selected' : '' ?>>PNS DPK</option>
                        <option value="PART_TIME" <?= $teacher['employment_status'] === 'PART_TIME' ? 'selected' : '' ?>>Guru Paruh Waktu</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Unit Sekolah Utama <span class="text-danger">*</span></label>
                    <select name="primary_unit_id" class="form-select rounded-3" required>
                        <option value="">-- Pilih Unit Utama --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (string)$teacher['primary_unit_id'] === (string)$u['id'] ? 'selected' : '' ?>>
                                <?= esc($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Telepon / Whatsapp</label>
                    <input type="text" name="phone" class="form-control rounded-3" value="<?= esc($teacher['phone']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-8 fw-bold">Email</label>
                    <input type="email" name="email" class="form-control rounded-3" value="<?= esc($teacher['email']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label fs-8 fw-bold">Alamat Tempat Tinggal</label>
                    <textarea name="address" class="form-control rounded-3" rows="2"><?= esc($teacher['address']) ?></textarea>
                </div>

                <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                    <a href="<?= base_url('teachers/' . $teacher['uuid']) ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Perbarui Data Guru</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
