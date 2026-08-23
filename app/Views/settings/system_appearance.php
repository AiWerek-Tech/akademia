<?= $this->extend('layouts/admin') ?>
<?= $this->section('main_content') ?>
<div class="container-fluid px-0 px-md-3" style="max-width:1100px">
    <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= base_url('settings/appearance') ?>">Pengaturan</a></li><li class="breadcrumb-item active">Tampilan & Tema</li></ol></nav>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success border-0 rounded-4 mb-4"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div><h1 class="h3 fw-bold text-gray-900 mb-1">Tampilan & Tema</h1><p class="text-muted mb-0">Sesuaikan tampilan aplikasi, email, dan keamanan sistem.</p></div>
    </div>

    <ul class="nav nav-pills mb-4 gap-2" id="settingsTabs">
        <li class="nav-item"><button class="nav-link active rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-appearance"><i data-lucide="palette" class="w-4 h-4 me-1 d-inline-block"></i> Tampilan</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-email"><i data-lucide="mail" class="w-4 h-4 me-1 d-inline-block"></i> Email</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-security"><i data-lucide="shield" class="w-4 h-4 me-1 d-inline-block"></i> Keamanan</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: Appearance -->
        <div class="tab-pane fade show active" id="tab-appearance">
            <form method="POST" action="<?= base_url('settings/appearance/save') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Identitas Aplikasi</h6>
                            <div class="mb-3"><label class="form-label fw-semibold">Nama Aplikasi</label><input class="form-control rounded-3" name="app_name" value="<?= esc($appearance['app_name'] ?? 'IALOS Education') ?>"></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Tagline</label><input class="form-control rounded-3" name="app_tagline" value="<?= esc($appearance['app_tagline'] ?? 'ACADEMIC SUITE') ?>"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Tema & Warna</h6>
                            <div class="mb-3"><label class="form-label fw-semibold">Mode Tema</label><select class="form-select rounded-3" name="theme">
                                <option value="light" <?= ($appearance['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>☀️ Terang (Light)</option>
                                <option value="dark" <?= ($appearance['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>🌙 Gelap (Dark)</option>
                                <option value="auto" <?= ($appearance['theme'] ?? '') === 'auto' ? 'selected' : '' ?>>🔄 Ikuti Sistem (Auto)</option>
                            </select></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Warna Utama</label><div class="input-group"><input type="color" class="form-control form-control-color" name="primary_color" value="<?= esc($appearance['primary_color'] ?? '#6366f1') ?>"><input class="form-control rounded-end-3" value="<?= esc($appearance['primary_color'] ?? '#6366f1') ?>" readonly></div></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Tipografi</h6>
                            <div class="mb-3"><label class="form-label fw-semibold">Font Family</label><select class="form-select rounded-3" name="font_family">
                                <?php foreach (['Inter','Poppins','Roboto','Nunito','Plus Jakarta Sans','Open Sans','Geeza Pro'] as $font): ?>
                                    <option value="<?= $font ?>" style="font-family:<?= $font ?>" <?= ($appearance['font_family'] ?? 'Inter') === $font ? 'selected' : '' ?>><?= $font ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Ukuran Font (px)</label><input type="range" class="form-range" name="font_size" min="12" max="18" value="<?= esc($appearance['font_size'] ?? '14') ?>" oninput="this.nextElementSibling.textContent=this.value+'px'"><small class="text-muted"><?= esc($appearance['font_size'] ?? '14') ?>px</small></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Tampilan</h6>
                            <div class="form-check form-switch border rounded-3 p-3 ps-5 mb-2"><input class="form-check-input" type="checkbox" name="sidebar_compact" value="1" <?= !empty($appearance['sidebar_compact']) ? 'checked' : '' ?>><span class="fw-semibold">Sidebar Kompak</span><small class="d-block text-muted">Tampilkan sidebar dalam mode minimalis</small></div>
                            <div class="form-check form-switch border rounded-3 p-3 ps-5 mb-2"><input class="form-check-input" type="checkbox" name="show_breadcrumbs" value="1" <?= !empty($appearance['show_breadcrumbs']) ? 'checked' : '' ?>><span class="fw-semibold">Tampilkan Breadcrumb</span><small class="d-block text-muted">Navigasi path di atas konten</small></div>
                            <div class="mb-3 mt-3"><label class="form-label fw-semibold">Baris per Halaman Tabel</label><select class="form-select rounded-3" name="table_page_size">
                                <?php foreach ([10,15,20,25,50,100] as $ps): ?>
                                    <option value="<?= $ps ?>" <?= ($appearance['table_page_size'] ?? '20') == $ps ? 'selected' : '' ?>><?= $ps ?> baris</option>
                                <?php endforeach; ?>
                            </select></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Format</h6>
                            <div class="mb-3"><label class="form-label fw-semibold">Format Tanggal</label><select class="form-select rounded-3" name="date_format">
                                <?php foreach (['d M Y','d/m/Y','Y-m-d','D, d M Y'] as $df): ?>
                                    <option value="<?= $df ?>" <?= ($appearance['date_format'] ?? 'd M Y') === $df ? 'selected' : '' ?>><?= $df ?> → <?= date($df) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                            <div class="mb-3"><label class="form-label fw-semibold">Format Waktu</label><select class="form-select rounded-3" name="time_format">
                                <?php foreach (['H:i','h:i A','H:i:s'] as $tf): ?>
                                    <option value="<?= $tf ?>" <?= ($appearance['time_format'] ?? 'H:i') === $tf ? 'selected' : '' ?>><?= $tf ?> → <?= date($tf) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Tampilan</button></div>
            </form>
        </div>

        <!-- TAB 2: Email -->
        <div class="tab-pane fade" id="tab-email">
            <form method="POST" action="<?= base_url('settings/appearance/save-email') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <div class="card-header bg-white border-bottom rounded-top-4"><h6 class="mb-0 fw-semibold">Konfigurasi SMTP Email</h6></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold">SMTP Host</label><input class="form-control rounded-3" name="smtp_host" value="<?= esc($email['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Port</label><input class="form-control rounded-3" name="smtp_port" type="number" value="<?= esc($email['smtp_port'] ?? '587') ?>"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold">Username</label><input class="form-control rounded-3" name="smtp_user" value="<?= esc($email['smtp_user'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Nama Pengirim</label><input class="form-control rounded-3" name="smtp_from_name" value="<?= esc($email['smtp_from_name'] ?? 'IALOS Education') ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold">Email Pengirim</label><input class="form-control rounded-3" name="smtp_from_email" type="email" value="<?= esc($email['smtp_from_email'] ?? '') ?>"></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Email</button></div>
            </form>
        </div>

        <!-- TAB 3: Security -->
        <div class="tab-pane fade" id="tab-security">
            <form method="POST" action="<?= base_url('settings/appearance/save-security') ?>" class="card border-0 shadow-sm rounded-4">
                <?= csrf_field() ?>
                <div class="card-header bg-white border-bottom rounded-top-4"><h6 class="mb-0 fw-semibold">Pengaturan Keamanan</h6></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label fw-semibold">Session Timeout (detik)</label><input class="form-control rounded-3" name="session_timeout" type="number" value="<?= esc($security['session_timeout'] ?? '7200') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Max Login Attempts</label><input class="form-control rounded-3" name="max_login_attempts" type="number" value="<?= esc($security['max_login_attempts'] ?? '5') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Lockout Duration (detik)</label><input class="form-control rounded-3" name="lockout_duration" type="number" value="<?= esc($security['lockout_duration'] ?? '900') ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Min Panjang Password</label><input class="form-control rounded-3" name="password_min_length" type="number" value="<?= esc($security['password_min_length'] ?? '8') ?>"></div>
                        <div class="col-md-8 d-flex align-items-end"><div class="form-check form-switch border rounded-3 p-3 ps-5 w-100"><input class="form-check-input" type="checkbox" name="require_2fa" value="1" <?= !empty($security['require_2fa']) ? 'checked' : '' ?>><span class="fw-semibold">Wajibkan 2FA untuk Admin</span><small class="d-block text-muted">Autentikasi dua faktor wajib untuk peran administrator</small></div></div>
                    </div>
                </div>
                <div class="card-footer bg-white border-top rounded-bottom-4 p-4"><button class="btn btn-primary rounded-pill px-4"><i data-lucide="save" class="w-4 h-4 me-1 d-inline-block"></i> Simpan Keamanan</button></div>
            </form>
        </div>


    </div>
</div>
<?= $this->endSection() ?>
