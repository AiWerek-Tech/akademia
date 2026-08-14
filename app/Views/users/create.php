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
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="<?= base_url('users') ?>" method="POST" id="formCreateUser" autocomplete="off">
                    <?= csrf_field() ?>

                    <!-- Mode Switcher Header -->
                    <div class="bg-light p-3 rounded-4 border mb-4">
                        <label class="form-label fw-bold text-slate-800 d-block mb-2">Tipe Akun Pengguna <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check card-radio p-3 border rounded-3 bg-white <?= !empty($selectedTeacherId) || old('teacher_id') ? 'border-primary shadow-sm' : '' ?>" id="card_mode_teacher">
                                    <input class="form-check-input me-2" type="radio" name="account_mode" id="mode_teacher" value="teacher" <?= !empty($selectedTeacherId) || old('teacher_id') || empty(old('account_mode')) ? 'checked' : (old('account_mode') === 'teacher' ? 'checked' : '') ?>>
                                    <label class="form-check-label fw-semibold text-slate-800 cursor-pointer d-flex align-items-center gap-2" for="mode_teacher">
                                        <i data-lucide="graduation-cap" class="text-primary" style="width: 20px; height: 20px;"></i>
                                        <div>
                                            <span>Akun dari Master Guru</span>
                                            <span class="d-block text-muted fs-9 fw-normal">Otomatis isi data nama & unit dari Master Guru</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check card-radio p-3 border rounded-3 bg-white <?= old('account_mode') === 'custom' ? 'border-primary shadow-sm' : '' ?>" id="card_mode_custom">
                                    <input class="form-check-input me-2" type="radio" name="account_mode" id="mode_custom" value="custom" <?= old('account_mode') === 'custom' && empty($selectedTeacherId) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold text-slate-800 cursor-pointer d-flex align-items-center gap-2" for="mode_custom">
                                        <i data-lucide="user-cog" class="text-secondary" style="width: 20px; height: 20px;"></i>
                                        <div>
                                            <span>Pengguna Baru (Non-Guru / Admin)</span>
                                            <span class="d-block text-muted fs-9 fw-normal">Input manual untuk Admin, Kepsek, Staff TU, Yayasan</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Selector Section (Visible when mode_teacher) -->
                    <div id="section_teacher_select" class="mb-4">
                        <label for="teacher_id_select" class="form-label fw-bold text-slate-800">
                            Pilih Data Guru dari Master <span class="text-danger">*</span>
                        </label>
                        <select class="form-select rounded-3 form-select-lg border-primary border-opacity-50" id="teacher_id_select" name="teacher_id">
                            <option value="">-- Klik untuk memilih Guru dari Master Data --</option>
                            <?php if (!empty($teachers)): ?>
                                <?php foreach ($teachers as $t): ?>
                                    <?php
                                        $hasAccount = !empty($t['linked_user_id']);
                                        $isSelected = (string)($selectedTeacherId ?? old('teacher_id')) === (string)$t['id'];
                                    ?>
                                    <option value="<?= $t['id'] ?>"
                                            data-name="<?= esc($t['full_name']) ?>"
                                            data-email="<?= esc($t['email'] ?? '') ?>"
                                            data-nip="<?= esc($t['nip'] ?? $t['employee_number'] ?? '') ?>"
                                            data-unit="<?= esc($t['primary_unit_id'] ?? '') ?>"
                                            data-homeroom-id="<?= esc($t['homeroom_classroom_id'] ?? '') ?>"
                                            data-homeroom-name="<?= esc($t['homeroom_classroom_name'] ?? '') ?>"
                                            <?= $hasAccount && !$isSelected ? 'disabled' : '' ?>
                                            <?= $isSelected ? 'selected' : '' ?>>
                                        <?= esc($t['full_name']) ?>
                                        <?php if (!empty($t['nip'])): ?>(NIP: <?= esc($t['nip']) ?>)<?php endif; ?>
                                        <?php if (!empty($t['homeroom_classroom_name'])): ?> [Wali Kelas <?= esc($t['homeroom_classroom_name']) ?>]<?php endif; ?>
                                        <?= $hasAccount ? ' [SUDAH PUNYA AKUN]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text fs-9 text-muted" id="teacher_select_help">
                            Pilih guru di atas untuk mengisi otomatis Nama Lengkap, Email, Username, Peran (Guru/Wali Kelas), dan Unit Sekolah.
                        </div>
                    </div>

                    <div id="sync_alert" class="alert alert-success rounded-3 border-0 py-2 px-3 mb-4 d-none fs-8 align-items-center gap-2">
                        <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                        <span id="sync_alert_text">Data Nama, Username, Role, dan Unit Sekolah berhasil disinkronkan dari Master Guru!</span>
                    </div>

                    <!-- User Account Detail Fields -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="full_name" name="full_name" required value="<?= esc(old('full_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Alamat Email <span class="text-muted fw-normal">(Opsional)</span></label>
                            <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="opsional: nama@sekolah.sch.id">
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label fw-semibold">Username Login <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" required value="<?= esc(old('username')) ?>" placeholder="contoh: nip/budi.santoso">
                            <div class="form-text fs-9">Digunakan oleh pengguna untuk masuk ke dalam sistem.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Kata Sandi Awal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-3" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary rounded-end-3 fs-8" id="btnGenPassword" title="Generate Kata Sandi Acak">
                                    <i data-lucide="key" style="width: 14px; height: 14px;"></i> Acak Sandi
                                </button>
                            </div>
                            <div class="form-text fs-9">Min. 12 karakter, huruf besar, huruf kecil, angka, dan simbol.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label fw-semibold">Status Akun <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="is_active" name="is_active" required>
                                <option value="1" <?= old('is_active', '1') == '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= old('is_active') == '0' ? 'selected' : '' ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="must_change_password" class="form-label fw-semibold">Wajib Ganti Password <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="must_change_password" name="must_change_password" required>
                                <option value="1" <?= old('must_change_password', '1') == '1' ? 'selected' : '' ?>>Ya (Saat login pertama kali)</option>
                                <option value="0" <?= old('must_change_password') == '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="must_change_username" class="form-label fw-semibold">Wajib Ganti Username <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" id="must_change_username" name="must_change_username" required>
                                <option value="1" <?= old('must_change_username', '1') == '1' ? 'selected' : '' ?>>Ya (Saat login pertama kali)</option>
                                <option value="0" <?= old('must_change_username') == '0' ? 'selected' : '' ?>>Tidak</option>
                            </select>
                            <div class="form-text fs-9">Pengguna dapat memilih username pribadi yang unik bersama password baru.</div>
                        </div>
                    </div>

                    <!-- Roles & Units Assignment -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Peran Pengguna (Roles) <span class="text-danger">*</span></label>
                            <div class="bg-light p-3 rounded-3 border" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($roles as $role): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input role-checkbox" type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>" data-code="<?= esc($role['code']) ?>" <?= is_array(old('roles')) && in_array($role['id'], old('roles')) ? 'checked' : '' ?>>
                                        <label class="form-check-label fs-7" for="role_<?= $role['id'] ?>">
                                            <span class="fw-medium"><?= esc($role['name']) ?></span> <span class="text-muted fs-9">(<?= esc($role['code']) ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Akses Unit Sekolah <span class="text-danger">*</span></label>
                            <div class="bg-light p-3 rounded-3 border" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($units as $unit): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input unit-checkbox" type="checkbox" name="units[]" value="<?= $unit['id'] ?>" id="unit_<?= $unit['id'] ?>" data-unit-id="<?= $unit['id'] ?>" <?= is_array(old('units')) && in_array($unit['id'], old('units')) ? 'checked' : '' ?>>
                                        <label class="form-check-label fs-7" for="unit_<?= $unit['id'] ?>">
                                            <span class="fw-medium"><?= esc($unit['name']) ?></span> <span class="text-muted fs-9">(<?= esc($unit['code']) ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Optional Classroom Linkage for Wali Kelas -->
                    <div class="row g-3 mb-4" id="section_classroom">
                        <div class="col-md-12">
                            <label for="classroom_id" class="form-label fw-semibold">Tautkan ke Kelas / Rombel (Khusus Wali Kelas)</label>
                            <select class="form-select rounded-3" id="classroom_id" name="classroom_id">
                                <option value="">-- Bukan Wali Kelas / Pilih Kelas Jika Berlaku --</option>
                                <?php if (!empty($classrooms)): ?>
                                    <?php foreach ($classrooms as $classroom): ?>
                                        <option value="<?= $classroom['id'] ?>"
                                                data-homeroom-teacher-id="<?= esc($classroom['homeroom_teacher_id'] ?? '') ?>"
                                                <?= old('classroom_id') == $classroom['id'] ? 'selected' : '' ?>>
                                            <?= esc($classroom['name']) ?> (<?= esc($classroom['code']) ?>)
                                            <?php if (!empty($classroom['homeroom_teacher_name'])): ?>
                                                - Wali Kelas: <?= esc($classroom['homeroom_teacher_name']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const modeTeacherRadio = document.getElementById('mode_teacher');
    const modeCustomRadio = document.getElementById('mode_custom');
    const sectionTeacherSelect = document.getElementById('section_teacher_select');
    const teacherSelect = document.getElementById('teacher_id_select');
    const inputFullName = document.getElementById('full_name');
    const inputEmail = document.getElementById('email');
    const inputUsername = document.getElementById('username');
    const inputPassword = document.getElementById('password');
    const btnGenPassword = document.getElementById('btnGenPassword');
    const syncAlert = document.getElementById('sync_alert');
    const syncAlertText = document.getElementById('sync_alert_text');
    const cardModeTeacher = document.getElementById('card_mode_teacher');
    const cardModeCustom = document.getElementById('card_mode_custom');
    const classroomSelect = document.getElementById('classroom_id');

    function toggleMode() {
        if (modeTeacherRadio.checked) {
            sectionTeacherSelect.classList.remove('d-none');
            cardModeTeacher.classList.add('border-primary', 'shadow-sm');
            cardModeCustom.classList.remove('border-primary', 'shadow-sm');
            if (teacherSelect.value) {
                fillTeacherData();
            }
        } else {
            sectionTeacherSelect.classList.add('d-none');
            teacherSelect.value = '';
            cardModeCustom.classList.add('border-primary', 'shadow-sm');
            cardModeTeacher.classList.remove('border-primary', 'shadow-sm');
            syncAlert.classList.add('d-none');
            syncAlert.classList.remove('d-flex');
        }
    }

    modeTeacherRadio.addEventListener('change', toggleMode);
    modeCustomRadio.addEventListener('change', toggleMode);

    teacherSelect.addEventListener('change', function() {
        if (this.value) {
            fillTeacherData();
        } else {
            syncAlert.classList.add('d-none');
            syncAlert.classList.remove('d-flex');
        }
    });

    function fillTeacherData() {
        const selectedOpt = teacherSelect.options[teacherSelect.selectedIndex];
        if (!selectedOpt || !selectedOpt.value) return;

        const name = selectedOpt.getAttribute('data-name') || '';
        const email = selectedOpt.getAttribute('data-email') || '';
        const nip = selectedOpt.getAttribute('data-nip') || '';
        const unitId = selectedOpt.getAttribute('data-unit') || '';
        const homeroomId = selectedOpt.getAttribute('data-homeroom-id') || '';
        const homeroomName = selectedOpt.getAttribute('data-homeroom-name') || '';

        inputFullName.value = name;
        if (email) inputEmail.value = email;

        // Suggest username from NIP or email or slugified name
        if (nip) {
            inputUsername.value = nip.toLowerCase().replace(/\s+/g, '');
        } else if (email && email.includes('@')) {
            inputUsername.value = email.split('@')[0].toLowerCase();
        } else if (name) {
            inputUsername.value = name.toLowerCase().replace(/[^a-z0-9]/g, '.').replace(/\.+/g, '.').replace(/^\.|\.$/g, '');
        }

        // Auto check "guru" role
        document.querySelectorAll('.role-checkbox').forEach(cb => {
            if (cb.getAttribute('data-code') === 'guru') {
                cb.checked = true;
            }
        });

        // Smart Wali Kelas Detection
        if (homeroomId) {
            document.querySelectorAll('.role-checkbox').forEach(cb => {
                if (cb.getAttribute('data-code') === 'wali_kelas') {
                    cb.checked = true;
                }
            });
            if (classroomSelect) {
                classroomSelect.value = homeroomId;
            }
        }

        // Auto check unit matching primary_unit_id
        if (unitId) {
            document.querySelectorAll('.unit-checkbox').forEach(cb => {
                if (cb.getAttribute('data-unit-id') === unitId) {
                    cb.checked = true;
                }
            });
        }

        if (syncAlertText) {
            if (homeroomName) {
                syncAlertText.textContent = `Data Guru & Wali Kelas (${homeroomName}) berhasil disinkronkan otomatis dari Master Guru!`;
            } else {
                syncAlertText.textContent = `Data Nama, Username, Role, dan Unit Sekolah berhasil disinkronkan otomatis dari Master Guru!`;
            }
        }

        syncAlert.classList.remove('d-none');
        syncAlert.classList.add('d-flex');
    }

    // Password Random Generator
    function generatePassword() {
        const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*";
        let pass = "Wmvaa@";
        for (let i = 0; i < 8; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        inputPassword.value = pass;
    }

    btnGenPassword.addEventListener('click', generatePassword);

    // Initial trigger & password auto-generate if empty
    toggleMode();
    if (!inputPassword.value) {
        generatePassword();
    }
    if (teacherSelect.value) {
        fillTeacherData();
    }
});
</script>
<?= $this->endSection() ?>
