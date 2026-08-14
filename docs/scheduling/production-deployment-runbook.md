# Runbook Deployment Produksi Penjadwalan

## Sebelum Maintenance Window

1. Bekukan perubahan master guru, rombel, mapel, kurikulum, penugasan, kegiatan rutin, ruangan, dan slot.
2. Ambil backup database penuh serta arsip source dan `.env`; uji restore pada database terpisah.
3. Pastikan `scheduling.teamTeachingEnabled=false`. Bila sekolah membutuhkan team teaching, deployment diblokir sampai junction `schedule_entry_teachers` dan kontrak end-to-end selesai.
4. Jalankan full PHPUnit suite sampai selesai tanpa failure. Warning coverage boleh diselesaikan dengan memasang driver atau mematikan konfigurasi coverage lokal.
5. Jalankan migrasi fresh/rollback/fresh pada database disposable dengan versi MariaDB yang sama seperti produksi.

## Konfigurasi Server

- Set `CI_ENVIRONMENT=production`.
- Set `app.baseURL` ke origin HTTPS final dan pastikan reverse proxy meneruskan skema/host dengan benar.
- Aktifkan `app.forceGlobalSecureRequests`, secure cookie, CSP, dan nonaktifkan debug toolbar.
- Batasi host yang diterima dan simpan secret hanya di environment server.
- Pastikan test database berbeda dari database produksi.
- Pastikan `writable/cache`, `writable/logs`, `writable/session`, dan `writable/uploads` dapat ditulis oleh user layanan, bukan publik.
- Mulai dari `deploy/production.env.example`; isi hostname/proxy/secrets hanya pada server atau secret manager.
- `app.allowedHostnamesCSV` berisi hostname eksplisit dan `app.proxyIPsJSON` berisi mapping proxy CIDR/IP ke forwarded header.

## Gate Wajib

Jalankan:

```bash
php spark akademia:scheduling-check
composer validate --strict
php vendor/bin/phpunit --no-coverage
```

Deployment tidak boleh dilanjutkan bila command pertama atau PHPUnit gagal.

Gate boleh menampilkan WARNING CSP `unsafe-inline` selama compatibility bridge masih digunakan, tetapi risiko harus disetujui dan masuk backlog nonce/event-listener. FAIL selalu menghentikan deployment.

## UAT Data Sekolah

1. Buat versi DRAFT khusus UAT dari periode dan unit yang benar.
2. Verifikasi kebutuhan mengajar terhadap SK pembagian tugas: guru, guru kedua, mapel, rombel, dan total JP.
3. Buat kandidat dua kali dari input yang sama dan bandingkan hasil deterministik.
4. Pastikan kandidat parsial tidak dapat diterapkan.
5. Terapkan kandidat lengkap, audit konflik, lalu uji edit manual, undo operasional melalui edit balik, impor, dan laporan kelas/guru/unit.
6. Uji dua pengguna yang mengedit revisi sama; pengguna kedua harus menerima penolakan revisi basi tanpa kehilangan data.
7. Uji satu guru lintas SMP/SMA pada slot yang nomor JP-nya berbeda tetapi waktunya beririsan.
8. Finalisasi hanya jika jam terpenuhi dan tidak ada konflik kritis.

## Deployment dan Rollback

1. Aktifkan maintenance mode dan hentikan worker/cron yang menulis jadwal.
2. Deploy artefak terverifikasi, jalankan migrasi, clear cache yang aman, lalu jalankan gate produksi lagi.
3. Lakukan smoke test login, daftar jadwal, editor read-only, pembuatan kandidat pada versi QA, laporan, dan logout.
4. Buka akses bertahap untuk tim kurikulum sebelum seluruh pengguna.
5. Jika migrasi, gate, atau smoke test gagal: tetap dalam maintenance mode, hentikan proses penulis, restore source dan database dari pasangan backup yang sama, lalu verifikasi revision/history sebelum membuka layanan.

Apply kandidat tidak pernah otomatis. Operator harus meninjau kandidat lengkap, menyertakan revision terkini, lalu menekan apply secara eksplisit. Jadwal tidak boleh keluar dari DRAFT bila blocker/UNMET_HOURS masih ada.

## Observabilitas 24 Jam Pertama

- Pantau HTTP 4xx/5xx, exception transaksi/deadlock, waktu generator, kandidat ditolak, konflik kritis, revision mismatch, dan kegagalan ekspor/impor.
- Jangan menghapus revision history atau conflict history untuk merapikan dashboard.
- Simpan bukti UAT dan hasil command gate bersama ID commit/artefak yang benar-benar dideploy.
