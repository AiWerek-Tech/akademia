<?= $this->extend('layouts/admin') ?>

<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1000px">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="<?= base_url('settings') ?>">Pengaturan</a></li><li class="breadcrumb-item active">Aplikasi</li></ol></nav>
            <h1 class="h3 fw-bold text-gray-900 mb-1">Pengaturan Aplikasi</h1>
            <p class="text-muted mb-0">Konfigurasi umum aplikasi, mode maintenance, dan pengaturan registrasi.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <ul class="nav nav-tabs mb-4" id="appSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">Umum</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">Maintenance</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="registration-tab" data-bs-toggle="tab" data-bs-target="#registration" type="button" role="tab">Registrasi</button>
                </li>
            </ul>

            <div class="tab-content" id="appSettingsTabContent">
                <!-- General Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form method="POST" action="<?= base_url('settings/application/save') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Zona Waktu Default</label>
                                <select name="app_timezone" class="form-select">
                                    <?php
                                    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
                                    $currentTz = $application['app_timezone'] ?? 'Asia/Jayapura';
                                    foreach ($timezones as $tz): ?>
                                        <option value="<?= esc($tz) ?>" <?= $tz === $currentTz ? 'selected' : '' ?>><?= esc($tz) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bahasa Default</label>
                                <select name="default_language" class="form-select">
                                    <option value="id" <?= ($application['default_language'] ?? 'id') === 'id' ? 'selected' : '' ?>>Indonesia (id)</option>
                                    <option value="en" <?= ($application['default_language'] ?? '') === 'en' ? 'selected' : '' ?>>English (en)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Format Tanggal</label>
                                <input type="text" name="date_format" class="form-control" value="<?= esc($application['date_format'] ?? 'd/m/Y') ?>" placeholder="d/m/Y">
                                <div class="form-text">Format PHP date(). Contoh: d/m/Y, Y-m-d, d F Y</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Format Waktu</label>
                                <input type="text" name="time_format" class="form-control" value="<?= esc($application['time_format'] ?? 'H:i') ?>" placeholder="H:i">
                                <div class="form-text">Format PHP date(). Contoh: H:i, h:i A</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Mata Uang</label>
                                <input type="text" name="currency" class="form-control" value="<?= esc($application['currency'] ?? 'IDR') ?>" placeholder="IDR">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Format Angka</label>
                                <select name="number_format" class="form-select">
                                    <option value="id_ID" <?= ($application['number_format'] ?? 'id_ID') === 'id_ID' ? 'selected' : '' ?>>Indonesia (1.234,56)</option>
                                    <option value="en_US" <?= ($application['number_format'] ?? '') === 'en_US' ? 'selected' : '' ?>>US (1,234.56)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Pengaturan Umum</button>
                    </form>
                </div>

                <!-- Maintenance Tab -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                    <form method="POST" action="<?= base_url('settings/application/save-maintenance') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?= ($maintenance['mode'] ?? '') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="maintenance_mode">Aktifkan Mode Maintenance</label>
                                </div>
                                <div class="form-text">Saat aktif, hanya IP yang diizinkan dan super admin yang bisa mengakses aplikasi.</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Pesan Maintenance</label>
                                <textarea name="maintenance_message" class="form-control" rows="3" placeholder="Aplikasi sedang dalam perawatan. Silakan coba lagi nanti."><?= esc($maintenance['message'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">IP Diizinkan (pisahkan dengan koma)</label>
                                <input type="text" name="allowed_ips" class="form-control" value="<?= esc($maintenance['allowed_ips'] ?? '') ?>" placeholder="192.168.1.100, 10.0.0.5">
                                <div class="form-text">IP ini tetap bisa akses saat maintenance mode aktif. Super admin selalu diizinkan.</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning rounded-pill px-4">Simpan Maintenance</button>
                    </form>
                </div>

                <!-- Registration Tab -->
                <div class="tab-pane fade" id="registration" role="tabpanel">
                    <form method="POST" action="<?= base_url('settings/application/save-registration') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_registration" id="allow_registration" value="1" <?= ($registration['allow_registration'] ?? '') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="allow_registration">Izinkan Registrasi Publik</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="require_approval" id="require_approval" value="1" <?= ($registration['require_approval'] ?? '') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="require_approval">Butuh Persetujuan Admin</label>
                                </div>
                                <div class="form-text">User baru harus disetujui admin sebelum bisa login.</div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Peran Default User Baru</label>
                                <select name="default_role" class="form-select">
                                    <?php
                                    $roles = \Config\Database::connect()->table('roles')->where('is_active', 1)->orderBy('name', 'ASC')->get()->getResultArray();
                                    $currentRole = $registration['default_role'] ?? '';
                                    foreach ($roles as $role): ?>
                                        <option value="<?= esc($role['code']) ?>" <?= $role['code'] === $currentRole ? 'selected' : '' ?>><?= esc($role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info rounded-pill px-4">Simpan Registrasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>