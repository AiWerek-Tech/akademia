# WMVAA Akademia — Application Overview

## 1. Application Identity

| Property | Value |
|---|---|
| **Nama Aplikasi** | WMVAA Akademia |
| **Nama Branding UI** | IALOS Education (Integrated Academic Learning Operating System) |
| **Deskripsi** | Sistem Perencanaan Akademik Terpadu untuk jenjang SMP dan SMA |
| **Developer** | WMVAA |
| **Versi Saat Ini** | 2.0.0 |
| **Lisensi** | MIT |
| **Lokasi Deploy** | `E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia` |
| **URL Base** | `http://localhost:8080/` (development) |
| **Bahasa UI** | Bahasa Indonesia (`id-ID`) |
| **Arah Layout** | Left-to-Right (`ltr`) |

---

## 2. Tech Stack

### 2.1 Backend

| Komponen | Teknologi | Versi |
|---|---|---|
| **Bahasa Pemrograman** | PHP | ^8.2 (Target: 8.2.20) |
| **Framework** | CodeIgniter 4 (CI4) | ^4.7.4 |
| **Database** | MySQL / MariaDB | via MySQLi driver |
| **Composer** | Composer | 2.9.7 |
| **Testing** | PHPUnit | ^10.5 |

### 2.2 Library Tambahan (Composer)

| Package | Versi | Fungsi |
|---|---|---|
| `dompdf/dompdf` | ^3.1.6 | Generate PDF dokumen (Rapor, SK, RPP) |
| `phpoffice/phpspreadsheet` | ^5.9 | Import/Export Excel (.xlsx, .xls, .csv) |
| `phpoffice/phpword` | ^1.4.0 | Generate dokumen Word (.docx) |

### 2.3 Development Dependencies

| Package | Versi | Fungsi |
|---|---|---|
| `fakerphp/faker` | ^1.9 | Generate data dummy untuk testing |
| `mikey179/vfsstream` | ^1.6 | Virtual filesystem untuk unit test file |
| `phpunit/phpunit` | ^10.5 | Framework testing |

### 2.4 Frontend / UI

| Komponen | Sumber | Versi |
|---|---|---|
| **CSS Framework** | Bootstrap 5 (CDN) | 5.3.3 |
| **Icon Library** | Lucide Icons (CDN + local fallback) | 0.309.0 |
| **Icon Font** | Bootstrap Icons (CDN) | 1.11.3 |
| **Alerts & Modals** | SweetAlert2 (CDN) | 11.x |
| **Fonts** | Google Fonts (CDN) | Poppins, Inter, Plus Jakarta Sans, Nunito, Roboto, Open Sans |

### 2.5 Runtime Environment

| Aspek | Detail |
|---|---|
| **PHP Runtime** | `E:\xampp\php82\php.exe` via CGI handler `application/x-httpd-php82` |
| **Web Server** | Apache (XAMPP) |
| **PHP Isolation** | CGI handler mapping untuk memisahkan project PHP 8.2 dari legacy PHP 7.4 |
| **Timezone** | UTC (konfigurasi default, dapat diubah) |
| **Character Set** | UTF-8 |

---

## 3. Scope Operasional

### 3.1 Jenjang Pendidikan

Aplikasi melayani dua jenjang dalam satu codebase:

- **SMP** (Sekolah Menengah Pertama)
- **SMA** (Sekolah Menengah Atas)

Data kedua jenjang dipisahkan pada level database menggunakan kolom `unit_id` pada setiap tabel operasional.

### 3.2 Multi-Unit Support

Sekolah dengan beberapa unit (contoh: SMP dan SMA dalam satu yayasan) dapat mengelola semua unit dari satu instalasi aplikasi. Akses pengguna dibatasi berdasarkan `user_unit_access`.

### 3.3 Fitur Utama (High Level)

1. **Manajemen Master Data** — Guru, Siswa, Mata Pelajaran, Rombel, Ruang
2. **Struktur Kurikulum** — Versi kurikulum, struktur, matrix, planning settings
3. **IALOS Education** — CP, Elemen, Tujuan Pembelajaran, ATP, Coverage, Paket Pembelajaran, KSP Digital
4. **Penugasan Mengajar** — SK Pembagian Tugas, Matrix Penugasan, Beban Kerja
5. **Penjadwalan Otomatis** — Generator jadwal deterministik, konflik deteksi, substitusi guru
6. **Rencana Pembelajaran (RPP)** — Desain pembelajaran, tahapan, aktivitas, asesmen
7. **Ruang Mengajar Harian** — Workspace harian guru, observasi, refleksi, presensi
8. **Penilaian & Mastery** — Gradebook, mastery tracking, intervensi, sumatif, narasi rapor
9. **Kokurikuler & Ekstrakurikuler** — Program co/ekskul, kebiasaan 7KAIH, evaluasi
10. **Rapor & Portofolio** — Laporan semester, portofolio siswa, narasi, cetak rapor
11. **Kualitas & AI Copilot** — Refleksi guru, supervisi, evaluasi KSP, AI draft assistant
12. **Pemilihan Mata Pelajaran** — Sistem elektif fase F, approval workflow
13. **Kalender Pendidikan** — Rule engine, event management, cetak kalender
14. **Universal Sync** — Mobile sync API (Kodular), delta sync, file upload gateway
15. **Smart Analytics** — Lineage graph, mastery heatmap, reflection trends, remedial package
16. **System Management** — Settings, appearance, database manager, audit log, diagnostics

---

## 4. Environment Configuration

### 4.1 File Environment

Konfigurasi environment menggunakan file `.env` (tidak di-track di git):

```env
CI_ENVIRONMENT = production

app.baseURL = 'https://app.wmvaa.id/'
app.forceGlobalSecureRequests = true
app.CSPEnabled = true
cookie.secure = true

database.default.hostname = localhost
database.default.database = wmvaa_akademia
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

### 4.2 Allowed Hostnames (Production)

Dalam produksi, harus mengatur `app.allowedHostnamesCSV` di `.env` untuk mencegah host header injection.

### 4.3 Proxy Support

Aplikasi mendukung reverse proxy dengan whitelist IP melalui `app.proxyIPsJSON` di `.env`.

---

## 5. Feature Flags

Aplikasi menggunakan tabel `feature_flags` untuk mengaktifkan/menonaktifkan fitur secara dinamis:

| Flag Code | Deskripsi | Default |
|---|---|---|
| `ialos_phase7_cocurricular` | Mengaktifkan modul Kokurikuler | - |
| `ialos_7kahi` | Mengaktifkan fitur 7KAIH (Kebiasaan) | - |

Feature flags dikelola melalui `FeatureFlagService` yang menggunakan caching in-memory.

---

## 6. Project Directory Structure

```
wmvaa-akademia/
├── app/
│   ├── Commands/           # CLI commands
│   ├── Config/             # Application configuration
│   ├── Controllers/        # HTTP controllers (60+ files)
│   │   └── Api/            # REST API controllers
│   ├── Database/
│   │   ├── Migrations/     # 60+ migration files
│   │   └── Seeds/          # 18 seeder files
│   ├── Exceptions/         # Custom exception handlers
│   ├── Filters/            # Request filters (7 files)
│   ├── Helpers/            # Custom helper functions
│   ├── Language/           # Language files
│   ├── Libraries/          # Custom libraries
│   ├── Models/             # Eloquent-style models (100+ files)
│   ├── Services/           # Business logic services (100+ files)
│   ├── ThirdParty/         # Third-party integrations
│   └── Views/              # View templates (organized by module)
├── builds/                 # Build artifacts
├── deploy/                 # Deployment scripts
├── docs/                   # Documentation
├── public/                 # Web root
│   ├── assets/
│   │   ├── css/            # 15 CSS files
│   │   ├── js/             # 17 JS files
│   │   ├── img/            # Images & icons
│   │   └── vendor/         # Vendor assets
│   ├── uploads/            # User uploads
│   ├── sw.js               # Service Worker
│   ├── manifest.webmanifest # PWA Manifest
│   └── offline.html        # Offline fallback page
├── tests/                  # Test suite
│   ├── database/           # 79 database/integration tests
│   ├── Feature/            # Feature tests
│   ├── Security/           # Security tests
│   └── unit/               # Unit tests
├── tools/                  # Utility scripts
├── vendor/                 # Composer dependencies
└── writable/               # Writable directory (cache, logs, uploads)
```
