# Hasil Browser Scheduling

Playwright dengan Microsoft Edge headless pada clone UAT anonim dan konfigurasi CSP production-like.

- Role: super_admin, admin_smp, admin_sma, wakasek_kurikulum, kepala_sekolah, guru.
- Viewport: 1920×1080, 1366×768, 390×844.
- 162 skenario: 18 login, 144 akses halaman.
- 18/18 login berhasil; 93 authorized page menghasilkan 2xx; 51 unauthorized/cross-unit menghasilkan 403.
- Tidak ada 400/404/500 tak terduga, CSP violation, atau network failure.
- Halaman: list, editor, conflict audit, laporan kelas/guru/unit/multi-unit, dan teacher portal.
- Screenshot tersimpan di `E:\backup\scheduling-browser`; tidak dilacak Git.
- Enam akun QA hanya dibuat di clone dan cleanup akhir memverifikasi sisa akun = 0.
