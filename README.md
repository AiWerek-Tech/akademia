# WMVAA Academia — Sistem Akademik Terpadu SMP–SMA (IALOS Education)

[![CodeIgniter](https://img.shields.io/badge/Framework-CodeIgniter%204.7.4-orange.svg)](https://codeigniter.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%207.4%20Compat-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-blue.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**WMVAA Academia** adalah subsistem Web Admin Panel dalam ekosistem **WMVAA HUB**, yang dikembangkan khusus untuk sekolah **SMP & SMA Advent Sogokmo**. Aplikasi ini dibangun menggunakan framework **CodeIgniter 4** dengan arsitektur modular-monolith dan multi-unit (`unit_id`) untuk menyatukan administrasi akademik SMP dan SMA tanpa duplikasi tabel.

Di dalamnya terintegrasi **IALOS Education** sebagai bounded context untuk perencanaan kurikulum dan eksekusi pembelajaran harian — mulai dari fondasi pendidikan (education foundation), model operasional KSP digital, engine paket pembelajaran, perencanaan RPP pembelajaran mendalam, hingga ruang mengajar harian guru.

---

## 🗺️ Arsitektur Aplikasi

Aplikasi ini adalah **modular monolith** berbasis CodeIgniter 4 (PHP 8.2) dengan pemisahan lapisan yang tegas:

- **Bounded Context IALOS Education**: Education Foundation, Digital KSP, Subject Learning Pack, Deep Learning Lesson Plan, dan Daily Teaching Workspace hidup dalam satu aplikasi yang sama — bukan aplikasi terpisah — dan berbagi fondasi autentikasi, RBAC, audit, serta layout.
- **Pola Lapisan (Layered)**: `Controllers` → `Services` → `Models` → Database. Seluruh logika bisnis berada pada lapisan `Services` (80+ engine service) sehingga controller tetap tipis dan mudah diuji.
- **Multi-Unit Tenant**: skema data menyatu untuk unit SMP & SMA dengan pemisahan data berbasis `unit_id` tanpa duplikasi tabel.
- **Keamanan Berlapis**: autentikasi session, filter RBAC `permission`, filter `unit_access`, CSRF, honeypot, secure headers, audit trail log, dan optimistic locking (`revision_number`) untuk setiap mutasi data.
- **Database**: MySQL/MariaDB yang dikelola via **migration & seeder**, dilengkapi pipeline impor massal Excel/CSV (staging + validasi pra-commit) dan ekspor `.xlsx`.
- **Progressive Web App (PWA)**: manifest, service worker, dan fallback offline sehingga aplikasi dapat di-install dan digunakan secara offline-first oleh guru.
- **Generator Dokumen**: ekspor RPP & dokumen KSP ke **PDF** (Dompdf) dan **DOCX** (PhpWord).
- **Scheduler Engine**: generator jadwal deterministik dengan deteksi konflik, substitusi guru, dan optimasi berbasis skor.

---

## 🚀 Fitur Utama (Milestone 1–5 / IALOS Phase 1–5)

### Fondasi Sistem & Keamanan (Milestone 1)
*   **Multi-Unit Tenant**: Mendukung unit **SMP** dan **SMA** secara berdampingan.
*   **Tahun Pelajaran & Periode Akademik**: Manajemen siklus pendaftaran dan semester dengan state workflow control (`DRAFT` → `VALIDATED` → `REVIEWED` → `APPROVED` → `LOCKED`).
*   **Manajemen Pengguna & RBAC**: Kontrol akses berbasis peran yang ketat (Super Admin, Kepala Sekolah, Wakasek Kurikulum, Admin Unit, Guru, Tata Usaha).
*   **Audit Trail Log & Optimistic Locking**: Pencatatan setiap aksi modifikasi data plus perlindungan konflik penimpaan via `revision_number`.

### Master Data Akademik Terpadu (Milestone 2)
*   **Master Guru Global** dengan relasi multi-unit, riwayat kualifikasi, dan evaluasi kelengkapan profil.
*   **Pendeteksi Duplikasi Guru** (*Levenshtein Distance* & *Soundex Algorithm*) serta **Merge Tool** untuk menggabungkan data duplikat.
*   **Master Mata Pelajaran** global dengan alias dan ketersediaan per unit.
*   **Tingkat Kelas & Fase** (VII–IX Fase D, X–XII Fase E & F), **Rombongan Belajar**, **Copy Rombel Antar Periode**, dan **Master Ruang Sekolah**.
*   **Pipeline Impor Data Massal** (validasi pra-commit) dan **Ekspor Master Data** (.xlsx).

### IALOS Phase 1 — Education Foundation
*   Registry regulasi resmi, sumber kurikulum & buku teks, dan 8 Dimensi Profil Lulusan.
*   Capaian Pembelajaran (CP) & Elemen dengan Phase Explorer (Fase A–F).
*   Tujuan Pembelajaran (TP) dengan lineage Nasional → Sekolah → Guru, adaptasi modal, dan kriteria ketercapaian.
*   Alur Tujuan Pembelajaran (ATP) dengan workflow status timeline dan kloning revisi.
*   Analisis **Coverage & Kesenjangan Kurikulum** serta Paket Pembelajaran awal dengan staging import JSON.

### IALOS Phase 2 — Digital KSP Operating Model
*   Interactive **9-Section Readiness Grid** dengan completion indicator otomatis.
*   Karakteristik Satuan Pendidikan dengan visualisasi SWOT 4-kuadran.
*   Visi, Misi & Tujuan dengan Strategic Hierarchy View.
*   Pengorganisasian Pembelajaran dengan 3-Pillar Tabbed View (Intra, Koku P5, Ekstra) dan kalkulator jam belajar tahunan.
*   Evaluasi & Tindak Lanjut Perbaikan, Evidence & Provenance Vault (SHA-256), serta **Document Generator (DOCX & PDF)** yang immutable.

### IALOS Phase 3 — Subject Learning Pack Engine
*   Skema 20+ tabel anak dengan mutasi aman (OCC `revision_number`).
*   Validasi DAG prasyarat lintas paket dan import staging 21 tipe entitas.
*   Evaluasi 3 Dimensi Pembelajaran Mendalam (*Understand, Apply, Reflect*) dan aggregate tree untuk Phase 4.

### IALOS Phase 4 — Deep Learning Lesson Plan Engine
*   Model RPP/Modul Ajar harian (*living lesson plan*) terhubung ke jadwal dan paket belajar Phase 3.
*   Studio RPP terpadu: *Ringkasan*, *Desain*, *Tahapan (3D)*, *Aktivitas & Sumber Daya (Plugged/Unplugged)*, *Asesmen & Rubrik Bertingkat*.
*   Workflow transisi `DRAFT → READY → IN_PROGRESS → COMPLETED → REFLECTED` dengan OCC.
*   Ekspor RPP ke **PDF & DOCX**.

### IALOS Phase 5 — Daily Teaching Workspace & Execution Engine
*   **Today Dashboard**: agenda mengajar harian dengan status kelas real-time dan attention box (murid butuh intervensi & refleksi tertunda).
*   **Interactive Teaching Mode**: timer live, checklist alur belajar 3 Dimensi, dan rekomendasi adaptif.
*   **Presensi Cepat Terintegrasi** (H/T/I/S/A) langsung tersimpan ke operational journal.
*   **Radar Miskonsepsi & Observasi Formatif** untuk intervensi/remedial siswa.
*   **Session Completion & Reflection Lifecycle** dengan deviasi RPP dan refleksi 5-bintang.

---

## 🛠️ Persyaratan Server & Instalasi

### Persyaratan Sistem
*   **PHP**: Versi `8.2` (lingkungan lokal) dengan kompatibilitas kode fallback hingga `7.4`.
*   **Ekstensi PHP**: `intl`, `mbstring`, `mysqli`, `curl`, `json`.
*   **Database**: MySQL atau MariaDB.
*   **Composer**: Dependency Manager.

### Instalasi Lokal
1.  **Clone Repositori**:
    ```bash
    git clone https://github.com/AiWerek-Tech/wmvaa-academia.git
    cd wmvaa-academia
    ```
2.  **Install Dependencies**:
    ```bash
    composer install
    ```
3.  **Konfigurasi Environment**:
    Salin file `.env.example` menjadi `.env` lalu sesuaikan konfigurasi database Anda:
    ```ini
    CI_ENVIRONMENT = development
    app.baseURL = 'http://localhost/wmvaa-academia/public/'

    database.default.hostname = localhost
    database.default.database = wmvaa_academia
    database.default.username = root
    database.default.password =
    database.default.DBDriver = MySQLi
    ```
4.  **Jalankan Migrasi Database**:
    ```bash
    php spark migrate
    ```
5.  **Jalankan Seeder Awal**:
    ```bash
    php spark db:seed CoreSeeder
    php spark db:seed Milestone2MasterSeeder
    ```
6.  **Buat Akun Administrator**:
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

Suite pengujian terintegrasi menggunakan **PHPUnit** untuk memastikan stabilitas kode.

Untuk menjalankan seluruh rangkaian test:
```bash
vendor/bin/phpunit
```

Pengujian mencakup:
- Keamanan & otentikasi role-based access (termasuk routing security per modul).
- Validasi rentang tanggal tahun pelajaran & periode akademik.
- Deteksi duplikasi kemiripan string guru (*Levenshtein*).
- Validasi data ruang dan rombel serta fungsi penyalinan rombel.
- Engine paket pembelajaran, RPP pembelajaran mendalam, dan ruang mengajar harian.

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah lisensi MIT - lihat file [LICENSE](LICENSE) untuk detailnya.