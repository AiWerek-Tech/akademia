<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | WMVAA Akademia</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --font-primary: 'Plus Jakarta Sans', sans-serif;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(255, 255, 255, 0.12);
        }

        body {
            font-family: var(--font-primary);
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            margin: 0;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .brand-logo i {
            font-size: 2.25rem;
            color: var(--accent-color);
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
            color: #fff;
        }

        .brand-tagline {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .form-floating > .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: #fff;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .form-floating > .form-control:focus {
            background-color: rgba(15, 23, 42, 0.8);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            color: #fff;
        }

        .form-floating > label {
            color: #64748b;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--accent-color);
        }

        .btn-login {
            background-color: var(--accent-color);
            border: none;
            color: white;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .btn-login:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            font-size: 0.75rem;
            color: #475569;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-logo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h1 class="brand-name">WMVAA Akademia</h1>
        <p class="brand-tagline">Perencanaan Akademik Terpadu SMP–SMA</p>

        <form action="<?= base_url('login') ?>" method="POST" autocomplete="off" id="loginForm">
            <?= csrf_field() ?>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="<?= esc(old('username')) ?>">
                <label for="username"><i class="bi bi-person me-2"></i>Username</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
            </div>

            <button type="submit" class="btn btn-login" id="btnLogin">
                <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true" id="loginSpinner"></span>
                <span id="loginBtnText">Masuk Ke Sistem</span>
            </button>
        </form>

        <p class="footer-text">&copy; 2026 WMVAA Akademia. All rights reserved.</p>
    </div>

    <script>
        // Form loading indicator
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginSpinner').classList.remove('d-none');
            document.getElementById('btnLogin').setAttribute('disabled', 'true');
            document.getElementById('loginBtnText').textContent = 'Memverifikasi...';
        });

        // SweetAlert2 Alerts
        <?php if (session()->getFlashdata('error')): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Masuk',
                text: <?= json_encode(session()->getFlashdata('error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff'
            });
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: <?= json_encode(session()->getFlashdata('success'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff'
            });
        <?php endif; ?>
    </script>
</body>
</html>
