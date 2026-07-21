# WMVAA Akademia — Perencanaan Akademik Terpadu SMP–SMA

[![CodeIgniter](https://img.shields.io/badge/Framework-CodeIgniter%204.7.4-orange.svg)](https://codeigniter.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%207.4%20Compat-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-blue.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**WMVAA Akademia** adalah subsistem Web Admin Panel dalam ekosistem **WMVAA HUB**, yang dikembangkan khusus untuk sekolah **SMP & SMA Advent Sogokmo**. Aplikasi ini dibangun menggunakan framework **CodeIgniter 4** dengan arsitektur terpadu berbasis multi-unit (`unit_id`) untuk menyatukan administrasi data akademik SMP dan SMA tanpa adanya duplikasi tabel.

---

## 🗺️ Arsitektur Sistem

Ekosistem WMVAA HUB berjalan dengan arsitektur terpadu:
- **Database Utama**: Google Spreadsheet (Sinkronisasi API) & Database Relasional Lokal (MySQL/MariaDB) untuk pemrosesan admin lokal yang cepat.
- **Backend API**: Google Apps Script API.
- **Penyimpanan Berkas**: Google Drive.
- **Mobile Client**: Aplikasi Kodular (Offline-First) untuk Guru, Siswa, dan Orang Tua.
- **Web Admin Panel**: Web App CodeIgniter 4 (aplikasi di repositori ini) yang mengelola kontrol penuh atas master data sekolah.

---

## 🚀 Fitur Utama (Milestone 1 & 2)

### 1. Fondasi Sistem & Keamanan (Milestone 1)
*   **Multi-Unit Tenant**: Mendukung unit **SMP** dan **SMA** secara berdampingan.
*   **Tahun Pelajaran & Periode Akademik**: Manajemen siklus pendaftaran dan semester dengan state workflow control (`DRAFT` $\rightarrow$ `VALIDATED` $\rightarrow$ `REVIEWED` $\rightarrow$ `APPROVED` $\rightarrow$ `LOCKED`).
*   **Manajemen Pengguna & RBAC**: Kontrol akses berbasis peran (Role-Based Access Control) yang ketat (Super Admin, Kepala Sekolah, Wakasek Kurikulum, Admin Unit, Guru, Tata Usaha).
*   **Audit Trail Log**: Pencatatan otomatis untuk setiap aksi modifikasi data (`CREATE`, `UPDATE`, `DELETE`, `MERGE`, dsb.).
*   **Optimistic Locking**: Perlindungan dari konflik penimpaan data menggunakan `revision_number` pada seluruh tabel master.

### 2. Master Data Akademik Terpadu (Milestone 2)
*   **Master Guru Global**: Data kepegawaian guru terpusat dengan relasi multi-unit, riwayat kualifikasi akademik, serta status verifikasi kelengkapan profil (*Profile Completeness Evaluation*).
*   **Pendeteksi Duplikasi Guru**: Analisis kemiripan nama dan data identitas menggunakan metrik perbandingan kemiripan string (*Levenshtein Distance* & *Soundex Algorithm*) secara real-time.
*   **Penggabungan Guru (Merge Tool)**: Utilitas interaktif untuk menggabungkan data guru duplikat menjadi satu record tanpa merusak integritas relasi tabel.
*   **Master Mata Pelajaran**: Manajemen mapel global dengan alias nama dan kontrol ketersediaan mapel per unit (SMP/SMA).
*   **Tingkat Kelas & Fase**: Pengaturan tingkat kelas VII–IX (Fase D) dan X–XII (Fase E & F).
*   **Rombongan Belajar (Rombel)**: Pengaturan kelas per periode akademik, wali kelas (homeroom teacher), kapasitas, dan ruang kelas.
*   **Copy Rombel Antar Periode**: Memindahkan konfigurasi rombel dari semester ganjil ke genap secara instan dengan opsi penyalinan wali kelas.
*   **Master Ruang Sekolah**: Pengelolaan ruang kelas, laboratorium, perpustakaan, kapel, lapangan, beserta kapasitas dan fasilitasnya.
*   **Pipeline Impor Data Massal (Staging)**: Import data massal dari Excel/CSV dengan proses validasi pra-commit di halaman Review Batch sebelum dimasukkan ke database utama.
*   **Ekspor Master Data**: Download instan data master ke dalam format Excel (.xlsx).

---

## 🛠️ Persyaratan Server & Instalasi

### Persyaratan Sistem
*   **PHP**: Versi `8.2` (Lingkungan lokal) dengan kompatibilitas kode fallback hingga `7.4`.
*   **Ekstensi PHP**: `intl`, `mbstring`, `mysqli`, `curl`, `json`.
*   **Database**: MySQL atau MariaDB.
*   **Composer**: Dependency Manager.

### Instalasi Lokal
1.  **Clone Repositori**:
    ```bash
    git clone https://github.com/AiWerek-Tech/akademia.git
    cd akademia
    ```
2.  **Install Dependencies**:
    ```bash
    composer install
    ```
3.  **Konfigurasi Environment**:
    Salin file `.env.example` menjadi `.env` lalu sesuaikan konfigurasi database Anda:
    ```ini
    CI_ENVIRONMENT = development
    app.baseURL = 'http://localhost/akademia/public/'
    
    database.default.hostname = localhost
    database.default.database = wmvaa_akademia
    database.default.username = root
    database.default.password = 
    database.default.DBDriver = MySQLi
    ```
4.  **Jalankan Migrasi Database**:
    Jalankan perintah berikut untuk membuat seluruh struktur tabel master:
    ```bash
    php spark migrate
    ```
5.  **Jalankan Seeder Awal**:
    Masukkan data default unit sekolah, role, permission, grade tingkat, dan room type bawaan:
    ```bash
    php spark db:seed CoreSeeder
    php spark db:seed Milestone2MasterSeeder
    ```
6.  **Buat Akun Administrator**:
    Jalankan perintah interaktif CLI untuk membuat user Super Admin pertama Anda:
    ```bash
    php spark akademia:create-admin
    ```
    Seeder tidak membuat kredensial administrator bersama. Buat akun awal
    menggunakan command interaktif di atas dan simpan password melalui secret
    manager yang sesuai dengan lingkungan deployment.

7.  **Jalankan Aplikasi**:
    ```bash
    php spark serve
    ```
    Aplikasi dapat diakses melalui browser pada alamat `http://localhost:8080`.

---

## 🧪 Pengujian & Penjaminan Mutu

Kami menyediakan suite pengujian terintegrasi menggunakan **PHPUnit** untuk memastikan stabilitas kode.

Untuk menjalankan seluruh rangkaian test:
```bash
vendor/bin/phpunit
```

Pengujian mencakup:
- Keamanan & otentikasi role-based access.
- Validasi rentang tanggal tahun pelajaran & periode akademik.
- Deteksi duplikasi kemiripan string guru (*Levenshtein*).
- Validasi data ruang dan rombel.
- Fungsi penyalinan rombel (*copy classroom between periods*).

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah lisensi MIT - lihat file [LICENSE](LICENSE) untuk detailnya.
