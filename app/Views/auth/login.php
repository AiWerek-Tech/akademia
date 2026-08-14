<!DOCTYPE html>
<html lang="id" data-service-worker="<?= esc(base_url('sw.js'), 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Masuk ke WMVAA Akademia untuk mengelola master data, kurikulum, beban mengajar, dan jadwal sekolah.">
    <meta name="theme-color" content="#2f2f88">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Akademia">
    <title>Masuk | WMVAA Akademia</title>
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
        <section class="auth-story" aria-label="Tentang WMVAA Akademia">
            <a class="auth-brand" href="<?= base_url('login') ?>" aria-label="WMVAA Akademia">
                <img src="<?= base_url('assets/img/brand-mark.svg') ?>" alt="">
                <span>
                    <span class="auth-brand-name">WMVAA Akademia</span>
                    <span class="auth-brand-subtitle">Academic Planning Suite</span>
                </span>
            </a>

            <div class="auth-story-content">
                <span class="auth-eyebrow"><i data-lucide="sparkles"></i> Sistem akademik terpadu</span>
                <h1>Rencanakan sekolah dengan <span>lebih cerdas.</span></h1>
                <p class="auth-story-lead">
                    Satukan data master, struktur kurikulum, pembagian beban mengajar, dokumen SK,
                    dan jadwal otomatis dalam satu ruang kerja yang aman.
                </p>
                <div class="auth-feature-grid">
                    <div class="auth-feature">
                        <i data-lucide="table-2"></i>
                        <strong>Data terintegrasi</strong>
                        <span>Satu sumber data untuk seluruh proses akademik.</span>
                    </div>
                    <div class="auth-feature">
                        <i data-lucide="wand-2"></i>
                        <strong>Otomasi cerdas</strong>
                        <span>Kurangi pekerjaan berulang dan risiko kesalahan.</span>
                    </div>
                    <div class="auth-feature">
                        <i data-lucide="shield-check"></i>
                        <strong>Aman & terlacak</strong>
                        <span>Akses berbasis peran dengan jejak perubahan.</span>
                    </div>
                </div>
            </div>

            <div class="auth-story-footer">&copy; <?= date('Y') ?> WMVAA Akademia · Dibangun untuk SMP dan SMA</div>
        </section>

        <section class="auth-main">
            <div class="auth-card">
                <div class="auth-mobile-brand" aria-hidden="true">
                    <img src="<?= base_url('assets/img/brand-mark.svg') ?>" alt="">
                    <span><strong>WMVAA Akademia</strong><span>Academic Planning Suite</span></span>
                </div>

                <div class="auth-mobile-welcome" aria-hidden="true">
                    <span class="auth-mobile-status"><span></span> Portal akademik sekolah</span>
                </div>

                <header class="auth-card-header">
                    <h2>Selamat datang kembali</h2>
                    <p>Masukkan akun Anda untuk melanjutkan pekerjaan akademik.</p>
                </header>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <i data-lucide="alert-circle"></i>
                        <span><?= esc(session()->getFlashdata('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="auth-alert auth-alert-success" role="status">
                        <i data-lucide="circle-check"></i>
                        <span><?= esc(session()->getFlashdata('success')) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('login') ?>" method="POST" id="loginForm">
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label class="auth-label" for="username">Username</label>
                        <div class="auth-input-wrap">
                            <i class="auth-input-icon" data-lucide="user-round"></i>
                            <input
                                class="auth-input"
                                type="text"
                                id="username"
                                name="username"
                                value="<?= esc(old('username')) ?>"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                autocapitalize="none"
                                spellcheck="false"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label" for="password">Password</label>
                        <div class="auth-input-wrap">
                            <i class="auth-input-icon" data-lucide="lock-keyhole"></i>
                            <input
                                class="auth-input"
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button class="auth-password-toggle" type="button" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit" id="btnLogin">
                        <span id="loginBtnText">Masuk ke Akademia</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </form>

                <div class="auth-security-note">
                    <i data-lucide="shield-check"></i>
                    <span>Sesi Anda dilindungi. Jangan membagikan password dan selalu keluar setelah memakai perangkat bersama.</span>
                </div>
                <button type="button" class="auth-install" data-pwa-install hidden>
                    <i data-lucide="download"></i>
                    <span><strong>Instal WMVAA Akademia</strong><small>Akses cepat dari layar utama perangkat</small></span>
                    <i data-lucide="chevron-right"></i>
                </button>
                <div class="auth-footer">Butuh bantuan akses? Hubungi administrator sekolah.</div>
            </div>
        </section>
    </main>

    <script src="<?= base_url('assets/js/auth-ui.js?v=2.0.0') ?>"></script>
    <script src="<?= base_url('assets/js/pwa.js?v=1.1.0') ?>"></script>
</body>
</html>
