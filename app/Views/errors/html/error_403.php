<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Akses ditolak</title>
</head>
<body>
    <main>
        <h1>403 — Akses ditolak</h1>
        <p><?= esc($message ?? 'Anda tidak memiliki izin untuk membuka halaman ini.') ?></p>
        <p><a href="<?= base_url('dashboard') ?>">Kembali ke dashboard</a></p>
    </main>
</body>
</html>
