# Audit Kesiapan Produksi — 9 Agustus 2026

## Kesimpulan

Kode aplikasi berada pada status **release candidate terverifikasi di lingkungan lokal**. Seluruh 225 tes (770 assertion), pemeriksaan sintaks PHP, dan pemeriksaan 15 berkas JavaScript lulus. Deployment produksi belum boleh dinyatakan selesai sebelum konfigurasi server production dan UAT terautentikasi diterapkan.

## Aturan Mapel Pilihan

- Batas yang mengikat adalah **4–5 mata pelajaran**.
- Total **20–25 JP hanya warning/referensi**, bukan alasan menolak simpan atau submit.
- Jumlah JP efektif tetap mengikuti `effective_weekly_hours` pada struktur kurikulum sehingga nilai custom tetap didukung.
- Draft boleh disimpan kapan saja, sedangkan submit mandiri hanya di dalam jendela periode pemilihan.

## Perbaikan yang Diterapkan

1. Menambahkan kolom presentasi jadwal guru `teacher_initial` dan `color_code`, backfill data lama, generator nilai untuk data baru/perubahan, serta assertion skema pada tes ekspor jadwal.
2. Mengaktifkan rute cetak dokumen pembagian tugas kolektif dan per guru yang sebelumnya tersedia di controller/UI tetapi tidak terdaftar di router.
3. Menambahkan perlindungan `auth`, `unit_access`, dan `password_change_required` untuk produk kegiatan rutin dan piket guru.
4. Memperbaiki enforcement periode submit mapel pilihan tanpa membatasi total JP custom.
5. Memperketat scope unit kegiatan rutin: guru, data, create/update/delete, dan aktivitas global mengikuti hak akses unit; aktivitas global hanya dapat dikelola superadmin.
6. Memperluas command gerbang produksi agar memeriksa tabel produk penugasan, mapel pilihan, kegiatan rutin, piket, seluruh tabel inti jadwal, dan kolom presentasi guru.

## Verifikasi

- PHPUnit: **225 tes, 770 assertion, lulus seluruhnya**. Warning tunggal: driver code coverage tidak tersedia.
- PHP 8.2: seluruh berkas yang diubah lulus syntax check.
- JavaScript: 15 berkas publik lulus `node --check`.
- Composer audit: tidak menemukan security advisory.
- Browser lokal melalui Apache: halaman masuk tampil tanpa error console; endpoint dokumen penugasan, kegiatan rutin, dan piket menolak guest dan mengarahkan ke login.
- Health check: HTTP 200; koneksi database dan direktori writable sehat.
- Database aktual: migrasi berhasil; 17 guru aktif memiliki initial dan warna valid.
- Backup sebelum migrasi: `backups/pre-teacher-presentation-20260809-154742.sql`.

## Gerbang Deployment yang Masih Wajib

Command `php spark akademia:scheduling-check` lulus pada skema, tabel, kolom, ekstensi PHP, dan kontrol aplikasi, tetapi secara benar memblokir lingkungan lokal development pada 10 konfigurasi production: environment, HTTPS base URL, secure requests, CSP, secure cookie, debug toolbar, display errors, allowed hosts, trusted proxy, dan nama session cookie. Nilai acuannya tersedia di `deploy/production.env.example`.

CSP masih memberi warning karena `unsafe-inline` pada script/style untuk kompatibilitas UI lama. Ini bukan error runtime, tetapi perlu dijadikan pekerjaan hardening terjadwal menuju nonce dan event listener.

## Keputusan Rilis

- **Kode dan database lokal:** lulus sebagai release candidate.
- **Deploy produksi final:** menunggu penerapan environment production/HTTPS di server target dan smoke/UAT terautentikasi untuk tiap peran pada data sekolah nyata.
- Klaim “tanpa bug sama sekali” tidak dapat dijamin secara absolut; hasil di atas adalah bukti regresi otomatis dan audit statis/dinamis yang tersedia saat ini.
