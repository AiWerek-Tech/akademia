# Audit Backend Role & Kesiapan Produksi — 11 Agustus 2026

## Ruang lingkup

Audit mencakup autentikasi, RBAC, cakupan unit SMP/SMA, dashboard per persona,
portal Guru/Wali Kelas/Siswa, akun operasional dan eksekutif, absensi, kalender
pendidikan, data peserta didik, pemilihan mapel, dokumen penugasan, serta gate
deployment.

## Perbaikan yang diterapkan

- Memisahkan absensi personal Guru/Wali Kelas dari monitoring eksekutif.
- Mewajibkan ownership guru pada pembuatan, perubahan, cetak, dan penghapusan
  jurnal/absensi; substitusi sementara tetap dihormati berdasarkan tanggal.
- Membatasi monitoring absensi dan kalender pendidikan ke unit yang dapat
  diakses akun.
- Mengubah seluruh aksi mutasi yang ditemukan menjadi POST + CSRF.
- Menambah izin `students.manage` dan memisahkannya dari `classrooms.manage`.
- Memvalidasi unit, tahun ajaran, rombel, akun siswa, status aktif, serta
  hubungan antar-record sebelum data siswa disimpan.
- Memperbaiki sinkronisasi siswa mapel pilihan agar memakai kode role `siswa`,
  unit akses yang benar, dan transaksi import yang dapat di-rollback.
- Membatasi delegasi role: admin unit hanya dapat membuat role operasional;
  Tata Usaha/Wakasek hanya dapat mengelola akun personal di bawahnya;
  Super Admin tetap memiliki kontrol penuh.
- Memisahkan izin reset password dari izin edit pengguna. Password sementara
  berupa angka acak yang mudah diketik dan wajib diganti saat login berikutnya.
- Melengkapi baseline Tata Usaha dan Siswa untuk dashboard, periode, kalender,
  data siswa, dan layanan akun yang sesuai.
- Menyembunyikan kontrol edit pada halaman read-only berdasarkan permission
  aktual, bukan hanya nama username.
- Membatasi statistik pengguna dashboard ke gabungan unit yang dapat diakses.
- Membersihkan template environment hosting dari kredensial yang terisi.
- Memperluas `akademia:scheduling-check` menjadi gate schema, RBAC, linkage
  akun, konfigurasi HTTPS, dan kesiapan portal role.

## Verifikasi

- Migrasi aplikasi: sampai batch 34 berhasil.
- PHP lint seluruh `app/`: lulus.
- Audit route mutasi via GET: tidak ditemukan.
- Unit + security: 62 tes, 200 assertion, lulus.
- Regresi database RBAC/manajemen akun: 18 tes, 83 assertion, lulus.
- Regresi database cakupan unit/mapel pilihan/provisioning: 12 tes,
  74 assertion, lulus.
- Baseline permission operasional: 4 tes, 25 assertion, lulus.
- Audit jadwal SMA versi terkunci: 0 konflik.
- Audit jadwal SMP versi terkunci: 0 konflik kritis; 3 advisory jarak
  pertemuan yang tidak mengubah kelayakan jadwal.
- Gate schema/RBAC/data lokal: 0 kegagalan.

## Gate deployment

Pemeriksaan lokal tetap sengaja berstatus blocked untuk 10 syarat hosting:
environment production, HTTPS base URL, force HTTPS, CSP, secure cookie,
toolbar/display errors, allowed hostname, nama cookie sesi, dan kredensial DB
hosting. Nilai production tersedia sebagai template di
`deploy/production.env.example` dan harus diisi di server, kemudian jalankan:

```bash
php spark migrate --all
php spark akademia:scheduling-check
```

Deployment hanya boleh dibuka ke pengguna apabila perintah kedua berakhir
dengan `PASSED` pada server HTTPS sebenarnya.
