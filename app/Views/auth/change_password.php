<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbarui Password | WMVAA Akademia</title>
    
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
            --accent-color: #ef4444; /* Warning/Danger tone */
            --accent-hover: #dc2626;
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
            max-width: 480px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
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
            text-align: center;
        }

        .brand-tagline {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 2rem;
            text-align: center;
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
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
            color: #fff;
        }

        .form-floating > label {
            color: #64748b;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--accent-color);
        }

        .btn-submit {
            background-color: #3b82f6;
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

        .btn-submit:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .requirements-box {
            background: rgba(15, 23, 42, 0.4);
            border-radius: 12px;
            padding: 1rem;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .requirements-box ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        .requirements-box li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center">
            <div class="brand-logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1 class="brand-name">Perbarui Password</h1>
            <p class="brand-tagline">Anda wajib mengganti password demi alasan keamanan akun sebelum mengakses sistem.</p>
        </div>

        <div class="requirements-box">
            <strong>Persyaratan Password Baru:</strong>
            <ul class="mt-1">
                <li>Minimal 12 karakter</li>
                <li>Harus memiliki huruf besar (A-Z)</li>
                <li>Harus memiliki huruf kecil (a-z)</li>
                <li>Harus memiliki angka (0-9)</li>
                <li>Harus memiliki simbol khusus (@, #, $, !, %, *, dll.)</li>
            </ul>
        </div>

        <form action="<?= base_url('change-password') ?>" method="POST" autocomplete="off" id="changeForm">
            <?= csrf_field() ?>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Password Saat Ini" required>
                <label for="current_password"><i class="bi bi-key-fill me-2"></i>Password Saat Ini</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Password Baru" required>
                <label for="new_password"><i class="bi bi-lock-fill me-2"></i>Password Baru</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                <label for="confirm_password"><i class="bi bi-check-circle-fill me-2"></i>Konfirmasi Password Baru</label>
            </div>

            <button type="submit" class="btn btn-submit" id="btnSubmit">
                <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true" id="submitSpinner"></span>
                <span id="submitBtnText">Perbarui & Masuk</span>
            </button>
        </form>
    </div>

    <script>
        // Form loading
        document.getElementById('changeForm').addEventListener('submit', function() {
            document.getElementById('submitSpinner').classList.remove('d-none');
            document.getElementById('btnSubmit').setAttribute('disabled', 'true');
            document.getElementById('submitBtnText').textContent = 'Memperbarui...';
        });

        // SweetAlert2 Alerts
        <?php if (session()->getFlashdata('error')): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '<?= session()->getFlashdata('error') ?>',
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff'
            });
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <?php 
                $errStr = implode('\n', session()->getFlashdata('errors'));
            ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Validasi',
                text: '<?= $errStr ?>',
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff'
            });
        <?php endif; ?>
    </script>
</body>
</html>
