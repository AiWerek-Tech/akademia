<!DOCTYPE html>
<html lang="id" data-service-worker="<?= esc(base_url('sw.js'), 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Perbarui password akun WMVAA Akademia.">
    <meta name="theme-color" content="#2f2f88">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Akademia">
    <title>Amankan Akun | WMVAA Akademia</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/brand-mark.svg') ?>">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/pwa-icon-192.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= base_url('assets/css/auth.css?v=2.0.0') ?>" rel="stylesheet">
    <script src="<?= base_url('assets/js/lucide.min.js?v=1.0.1') ?>" defer></script>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-story" aria-label="Keamanan akun">
            <a class="auth-brand" href="<?= base_url('dashboard') ?>" aria-label="WMVAA Akademia">
                <img src="<?= base_url('assets/img/brand-mark.svg') ?>" alt="">
                <span>
                    <span class="auth-brand-name">WMVAA Akademia</span>
                    <span class="auth-brand-subtitle">Academic Planning Suite</span>
                </span>
            </a>

            <div class="auth-story-content">
                <span class="auth-eyebrow"><i data-lucide="shield-check"></i> Perlindungan akun</span>
                <h1>Satu langkah lagi untuk <span>mengamankan akun.</span></h1>
                <p class="auth-story-lead">
                    Gunakan password unik dan kuat. Sistem akan memeriksa persyaratannya secara langsung sebelum Anda melanjutkan.
                </p>
                <div class="auth-feature-grid">
                    <div class="auth-feature">
                        <i data-lucide="key-round"></i>
                        <strong>Minimal 12 karakter</strong>
                        <span>Lebih panjang berarti lebih sulit ditebak.</span>
                    </div>
                    <div class="auth-feature">
                        <i data-lucide="scan-text"></i>
                        <strong>Kombinasi lengkap</strong>
                        <span>Huruf besar, kecil, angka, dan simbol.</span>
                    </div>
                    <div class="auth-feature">
                        <i data-lucide="user-check"></i>
                        <strong>Khusus untuk Anda</strong>
                        <span>Jangan gunakan ulang password akun lain.</span>
                    </div>
                </div>
            </div>

            <div class="auth-story-footer">&copy; <?= date('Y') ?> WMVAA Akademia · Keamanan data adalah prioritas</div>
        </section>

        <section class="auth-main">
            <div class="auth-card">
                <div class="auth-mobile-brand" aria-hidden="true">
                    <img src="<?= base_url('assets/img/brand-mark.svg') ?>" alt="">
                    <span><strong>WMVAA Akademia</strong><span>Keamanan akun</span></span>
                </div>

                <div class="auth-mobile-welcome" aria-hidden="true">
                    <span class="auth-mobile-status"><span></span> Pengamanan akun pertama</span>
                </div>

                <header class="auth-card-header">
                    <h2><?= session()->get('must_change_username') ? 'Atur akun pribadi Anda' : 'Perbarui password' ?></h2>
                    <p><?= session()->get('must_change_username') ? 'Ganti username sementara dan buat password baru sebelum melanjutkan.' : 'Buat password baru yang memenuhi seluruh indikator keamanan berikut.' ?></p>
                </header>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <i data-lucide="alert-circle"></i>
                        <span><?= esc(session()->getFlashdata('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <i data-lucide="alert-circle"></i>
                        <span><?= esc(implode(' ', (array) session()->getFlashdata('errors'))) ?></span>
                    </div>
                <?php endif; ?>

                <div class="password-requirements" aria-label="Persyaratan password">
                    <span class="password-rule" data-password-rule="length">Minimal 12 karakter</span>
                    <span class="password-rule" data-password-rule="upper">Huruf besar</span>
                    <span class="password-rule" data-password-rule="lower">Huruf kecil</span>
                    <span class="password-rule" data-password-rule="number">Angka</span>
                    <span class="password-rule" data-password-rule="symbol">Simbol khusus</span>
                </div>

                <?php $isForcedChange = (bool) session()->get('must_change_password') || (bool) session()->get('must_change_username'); ?>
                <form action="<?= base_url('change-password') ?>" method="POST" id="changeForm">
                    <?= csrf_field() ?>

                    <?php if ((bool) session()->get('must_change_username')): ?>
                        <div class="auth-field">
                            <label class="auth-label" for="new_username">Username baru</label>
                            <div class="auth-input-wrap">
                                <i class="auth-input-icon" data-lucide="at-sign"></i>
                                <input class="auth-input" type="text" id="new_username" name="new_username" value="<?= esc(old('new_username')) ?>" placeholder="Contoh: saray.barusa" autocomplete="username" minlength="4" maxlength="50" pattern="[A-Za-z0-9._-]+" required autofocus>
                            </div>
                            <span class="auth-progress-label">Gunakan huruf, angka, titik, garis bawah, atau strip. Username harus berbeda dari username sementara.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isForcedChange): ?>
                        <div class="auth-field">
                            <label class="auth-label" for="current_password">Password saat ini</label>
                            <div class="auth-input-wrap">
                                <i class="auth-input-icon" data-lucide="lock"></i>
                                <input
                                    class="auth-input"
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    placeholder="Masukkan password saat ini"
                                    autocomplete="current-password"
                                    required
                                    autofocus
                                >
                                <button class="auth-password-toggle" type="button" data-password-toggle="current_password" aria-label="Tampilkan password saat ini" aria-pressed="false">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="auth-field">
                        <label class="auth-label" for="new_password">Password baru</label>
                        <div class="auth-input-wrap">
                            <i class="auth-input-icon" data-lucide="lock-keyhole"></i>
                            <input
                                class="auth-input"
                                type="password"
                                id="new_password"
                                name="new_password"
                                placeholder="Masukkan password baru"
                                autocomplete="new-password"
                                minlength="12"
                                required
                                <?= $isForcedChange && !session()->get('must_change_username') ? 'autofocus' : '' ?>
                            >
                            <button class="auth-password-toggle" type="button" data-password-toggle="new_password" aria-label="Tampilkan password baru" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                        <div class="auth-progress" data-password-progress data-score="0" aria-hidden="true">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                        <span class="auth-progress-label" aria-live="polite">Belum diisi</span>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="confirm_password">Konfirmasi password baru</label>
                        <div class="auth-input-wrap">
                            <i class="auth-input-icon" data-lucide="badge-check"></i>
                            <input
                                class="auth-input"
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Ulangi password baru"
                                autocomplete="new-password"
                                minlength="12"
                                required
                            >
                            <button class="auth-password-toggle" type="button" data-password-toggle="confirm_password" aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">
                        <span><?= session()->get('must_change_username') ? 'Simpan username dan password' : 'Perbarui dan lanjutkan' ?></span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </form>

                <div class="auth-security-note">
                    <i data-lucide="info"></i>
                    <span>Setelah berhasil, gunakan username dan password baru untuk login berikutnya.</span>
                </div>
            </div>
        </section>
    </main>

    <script src="<?= base_url('assets/js/auth-ui.js?v=2.0.0') ?>"></script>
    <script src="<?= base_url('assets/js/pwa.js?v=1.1.0') ?>"></script>
</body>
</html>
