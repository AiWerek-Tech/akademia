# Laporan Pengujian Milestone 1: Autentikasi, RBAC, dan Periode Akademik

Dokumen ini mendokumentasikan hasil pengujian otomatis untuk Milestone 1 sistem WMVAA Akademia.

## Ringkasan Eksekusi Pengujian

Seluruh pengujian unit dan database dieksekusi menggunakan PHPUnit 10.5.64 pada lingkungan PHP 8.2.20.

- **Total Pengujian**: 11
- **Total Assertions**: 36
- **Status Akhir**: **PASSED (100% Berhasil)**

---

## Cara Menjalankan Pengujian

Jalankan perintah berikut di direktori utama proyek (`public_html/app.wmvaa.id/wmvaa-akademia`):

```bash
E:\xampp\php82\php.exe vendor/bin/phpunit
```

---

## Rincian Kelas Pengujian

### 1. `CoreTablesTest` (`tests/database/CoreTablesTest.php`)
Menguji integrasi database, migrasi awal, dan keakuratan seeder data acuan.
- **Asersi**:
  - Memastikan unit sekolah `SMP` dan `SMA` terdaftar dan aktif.
  - Memastikan peran dasar terdaftar (`super_admin`, `kepala_sekolah`, `wakasek_kurikulum`, `guru`).
  - Memastikan wewenang hak akses (permissions) terdaftar.

### 2. `AuthTest` (`tests/database/AuthTest.php`)
Menguji logika otentikasi, pembatasan login (rate limiting), dan penguncian akun (lockout).
- **Asersi**:
  - Pendaftaran pengguna baru dan validasi hash password (`BCRYPT`).
  - Akumulasi kegagalan masuk (failed login attempts) per alamat IP.
  - Pembatasan/blokir otomatis setelah 5 kegagalan masuk dalam kurun waktu 15 menit.

### 3. `WorkflowTest` (`tests/database/WorkflowTest.php`)
Menguji mesin alur kerja (workflow engine) transisi periode akademik dan pencegahan pembaruan usang (optimistic locking).
- **Asersi**:
  - Transisi status dari `DRAFT` ke `VALIDATED` menggunakan `AcademicPeriodWorkflowService`.
  - Peningkatan nomor revisi record secara otomatis setelah pembaruan berhasil.
  - Pelemparan pengecualian (`RuntimeException`) apabila nomor revisi usang diajukan (simulasi pembaruan bersamaan oleh dua pengguna).

### 4. `HomeControllerTest` (`tests/unit/HomeControllerTest.php`)
Menguji controller halaman dashboard utama dan interseptor keamanan session.
- **Asersi**:
  - Pengalihan ke halaman login bagi pengguna tamu (unauthenticated).
  - Keberhasilan pemuatan dashboard utama setelah memuat session data pengguna terotentikasi.

---

## Hasil Output Konsol (PHPUnit)

```text
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.20
Configuration: E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\phpunit.xml.dist

...........                                                       11 / 11 (100%)

Time: 00:06.676, Memory: 18.00 MB

OK, but there were issues!
Tests: 11, Assertions: 36
```
