# Audit Produksi — Kalender Pendidikan Otomatis

Tanggal audit: 11 Agustus 2026

## Sumber kebijakan hari operasional

Jumlah dan pilihan hari sekolah mempunyai satu sumber kebenaran melalui menu **Sistem → Operasional Akademik**.

- Mode bawaan `CURRICULUM` membaca `teaching_days_per_week` dan `selected_day_codes_json` dari struktur kurikulum yang disetujui/terkunci untuk setiap unit.
- Mode `CUSTOM` merupakan override global per unit dan tahun ajaran. Mode ini dipakai hanya bila sekolah memang ingin kebijakan operasional berbeda dari struktur kurikulum.
- Kalender menyimpan snapshot hari, sumber kebijakan, dan revisi pengaturan agar hasil cetak dapat diaudit.
- Kalender gabungan SMP–SMA hanya dapat dibuat bila kedua unit memiliki kebijakan hari yang sama. Konflik tidak disatukan secara diam-diam.
- Target HES/HEB Dinas tidak lagi diisi otomatis dari pola enam hari. Angka target hanya menjadi pembanding opsional bila pengguna mengisinya atau mengaktifkan perbandingan resmi.

Data aktif saat audit: SMP dan SMA sama-sama Senin–Jumat (5 hari). Kalender 2026/2027 berstatus `VALID`, bersumber `CURRICULUM_ALL_UNITS`, tanpa warning target enam hari.

## Editor dan CRUD

Editor dipisahkan menjadi empat workspace agar tidak membentuk satu halaman panjang:

1. **Ringkasan** — metrik, status validasi, sumber hari, dan tindakan utama.
2. **Aturan** — pencarian, pagination, tambah, edit, dan hapus aturan rentang.
3. **Kalender** — matriks dengan area gulir internal, header lengket, edit tanggal, serta reset override manual.
4. **Program** — tambah, edit, hapus program dan daftar hari libur.

Semua mutasi memakai POST, CSRF, permission `academic_calendar.manage`, validasi scope unit, validasi status DRAFT, whitelist kategori/sumber, batas panjang teks, dan validasi rentang tanggal.

## Integrasi lintas modul

- **Struktur kurikulum:** menjadi sumber kebijakan hari operasional.
- **Penyusunan jadwal:** slot hari mengikuti kebijakan pusat. Slot pada hari yang dinonaktifkan dibersihkan hanya bila belum dipakai; jika sudah berisi jadwal sistem menghentikan proses dan meminta rekonsiliasi agar data tidak hilang.
- **Presensi dan jurnal:** sesi tidak dapat dibuat pada tanggal non-efektif dari kalender aktif. Jurnal pembelajaran tersimpan dalam sesi presensi sehingga mengikuti kontrol tanggal yang sama.
- **Dashboard guru/wali kelas:** agenda hari ini disaring dengan kalender aktif dan menampilkan alasan bila hari non-efektif.
- **Kalender aktif:** pengubahan pengaturan tidak mengubah kalender aktif secara diam-diam; kalender aktif ditandai perlu sinkronisasi, sedangkan kalender DRAFT dibangun ulang.

## Validasi dan aturan prioritas

Prioritas perhitungan adalah kebijakan hari operasional, aturan Dinas/sekolah, hari libur/cuti, lalu override manual per tanggal. Generate ulang mempertahankan override manual. Kalender `ERROR` tidak dapat diaktifkan; `WARNING` tetap dapat diaktifkan sesuai kebijakan awal, tetapi perbedaan enam hari Dinas dan lima hari WMVAA tidak lagi memunculkan warning palsu ketika target resmi tidak dipilih sebagai pembanding.

## Verifikasi teknis

- Migrasi `CreateAcademicOperatingSettings` dan `SyncCalendarsWithOperatingSettings` berhasil diterapkan.
- Rute pengaturan dan seluruh CRUD kalender terdaftar dengan filter auth, unit scope, password-change, permission, CSRF, dan secure headers.
- Jalur konfigurasi profil hari lama ditutup agar tidak menjadi sumber konfigurasi kedua.
- Tes terarah generator, database, penjadwalan, kurikulum, dashboard, presensi, dan kontrak CRUD: **25 tes / 144 assertion**, lulus.
- Suite unit dan security: **65 tes / 222 assertion**, lulus.
- Satu-satunya peringatan runner adalah extension code coverage yang tidak tersedia pada PHP CLI; bukan kegagalan aplikasi.

## Prosedur operasional

1. Finalisasi hari sekolah pada Struktur Kurikulum atau gunakan override di Operasional Akademik.
2. Generate kalender sebagai DRAFT dan tinjau tab Ringkasan.
3. Kelola aturan, tanggal manual, serta program dari tab masing-masing.
4. Pastikan status `VALID` atau review seluruh warning yang disengaja.
5. Aktifkan kalender. Setelah aktif, kalender menjadi kontrol tanggal untuk operasi harian.
