<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('users') ?>" class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Kembali
            </a>
            <h4 class="fw-bold mb-0 text-slate-800">Edit Pengguna: <?= esc($user['username']) ?></h4>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('users/' . $user['uuid']) ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" required value="<?= esc(old('username', $user['username'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Alamat Email <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= esc(old('email', $user['email'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="full_name" name="full_name" required value="<?= esc(old('full_name', $user['full_name'])) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Kata Sandi Baru</label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Kosongkan jika tidak diubah">
                            <div class="form-text fs-9">Min. 12 karakter, huruf besar, huruf kecil, angka, dan simbol.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label fw-semibold">Status Akun <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="is_active" name="is_active" required>
                                <option value="1" <?= old('is_active', $user['is_active']) == '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= old('is_active', $user['is_active']) == '0' ? 'selected' : '' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="must_change_password" class="form-label fw-semibold">Wajib Ganti Password <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="must_change_password" name="must_change_password" required>
                                <option value="1" <?= old('must_change_password', $user['must_change_password']) == '1' ? 'selected' : '' ?>>Ya (Saat login berikutnya)</option>
                                <option value="0" <?= old('must_change_password', $user['must_change_password']) == '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="must_change_username" class="form-label fw-semibold">Wajib Ganti Username <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="must_change_username" name="must_change_username" required>
                                <option value="1" <?= old('must_change_username', $user['must_change_username'] ?? 0) == '1' ? 'selected' : '' ?>>Ya (Saat login berikutnya)</option>
                                <option value="0" <?= old('must_change_username', $user['must_change_username'] ?? 0) == '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Peran Pengguna (Roles) <span class="text-danger">*</span></label>
                            <div class="bg-light p-3 rounded-3 border" style="max-height: 180px; overflow-y: auto;">
                                <?php foreach ($roles as $role): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input role-checkbox" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>" data-code="<?= esc($role['code'], 'attr') ?>" <?= in_array($role['id'], old('roles', $userRoles)) ? 'checked' : '' ?>>
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
                                        <input class="form-check-input" type="checkbox" name="units[]" value="<?= $unit['id'] ?>" id="unit_<?= $unit['id'] ?>" <?= in_array($unit['id'], old('units', $userUnits)) ? 'checked' : '' ?>>
                                        <label class="form-check-label fs-7" for="unit_<?= $unit['id'] ?>">
                                            <?= esc($unit['name']) ?> (<?= esc($unit['code']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label fw-semibold">Tautkan ke Profile Guru (Role: Guru)</label>
                            <select class="form-select rounded-3" id="teacher_id" name="teacher_id">
                                <option value="">-- Bukan Guru / Tidak Ditautkan --</option>
                                <?php if (!empty($teachers)): ?>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= $teacher['id'] ?>" <?= old('teacher_id', $user['teacher_id'] ?? '') == $teacher['id'] ? 'selected' : '' ?>>
                                            <?= esc($teacher['full_name']) ?> (NIP: <?= esc($teacher['nip'] ?? '-') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text fs-9">Wajib diisi jika akun digunakan oleh Guru Pengajar untuk akses Portal Guru.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="classroom_id" class="form-label fw-semibold">Tautkan ke Kelas / Rombel (Role: Wali Kelas)</label>
                            <select class="form-select rounded-3" id="classroom_id" name="classroom_id">
                                <option value="">-- Bukan Wali Kelas / Tidak Ditautkan --</option>
                                <?php if (!empty($classrooms)): ?>
                                    <?php foreach ($classrooms as $classroom): ?>
                                        <option value="<?= $classroom['id'] ?>" <?= old('classroom_id', $user['classroom_id'] ?? '') == $classroom['id'] ? 'selected' : '' ?>>
                                            <?= esc($classroom['name']) ?> (<?= esc($classroom['code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="form-text fs-9">Wajib diisi jika akun bertindak sebagai Wali Kelas untuk mengelola mapel pilihan siswa.</div>
                        </div>
                    </div>

                    <hr class="my-4 text-slate-200">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= base_url('users') ?>" class="btn btn-light rounded-3 px-4">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2">
                            <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if(typeof lucide!=='undefined') lucide.createIcons();
    const roles = [...document.querySelectorAll('.role-checkbox')];
    const teacher = document.getElementById('teacher_id');
    const classroom = document.getElementById('classroom_id');
    const teacherSection = teacher?.closest('.col-md-6');
    const classroomSection = classroom?.closest('.col-md-6');
    function syncRoleRequirements(){
        const selected = roles.filter(r => r.checked).map(r => r.dataset.code);
        const needsTeacher = selected.includes('guru') || selected.includes('wali_kelas');
        const needsClassroom = selected.includes('wali_kelas');
        teacherSection?.classList.toggle('border-start', needsTeacher);
        teacherSection?.classList.toggle('border-primary', needsTeacher);
        classroomSection?.classList.toggle('border-start', needsClassroom);
        classroomSection?.classList.toggle('border-primary', needsClassroom);
        if (teacher) teacher.setAttribute('aria-required', needsTeacher ? 'true' : 'false');
        if (classroom) classroom.setAttribute('aria-required', needsClassroom ? 'true' : 'false');
    }
    roles.forEach(role => role.addEventListener('change', syncRoleRequirements));
    syncRoleRequirements();
});
</script>
<?= $this->endSection() ?>
