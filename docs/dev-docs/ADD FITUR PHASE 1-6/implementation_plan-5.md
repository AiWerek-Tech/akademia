# Rencana Implementasi: Peningkatan Fitur Cerdas & Modernisasi UI/UX Fase 8 (Ekstrakurikuler & Karakter)

Dokumen ini menguraikan arsitektur, alur kerja cerdas, dan penyempurnaan UI/UX modern untuk modul **Fase 8 (Extracurricular & Character Engine)** di platform WMVAA Akademia.

---

## 1. Latar Belakang & Kebutuhan Desain

Sesuai blueprint dokumen kurikulum (*`08_COCURRICULAR_EXTRACURRICULAR_CHARACTER.md`*), modul ekstrakurikuler mencakup:
- Pengelolaan program ekskul (Klub, Olahraga, Seni, Kepanduan/Pathfinder, Olimpiade Akademik, Pengabdian).
- Manajemen anggota, jadwal latihan, dan validasi bentrok jadwal.
- Presensi kehadiran sesi latihan.
- Penilaian kompetensi & pencatatan prestasi/penghargaan (*achievements/honors*).
- Draf narasi kualitatif rapor ekstrakurikuler per siswa.
- Evaluasi tahunan program berbasis 4 pilar mutu (*Input, Process, Output, Outcome*).

---

## 2. Fitur Cerdas & Modern yang Akan Diimplementasikan

### 🧭 A. Visual Analytics & Attendance Heatmap (ApexCharts)
- **ApexCharts Trend Chart (`sessions.php` & `reports.php`):** Grafik tren tingkat kehadiran anggota (%) per sesi latihan sepanjang semester.
- **ApexCharts Donut Demografi (`index.php` & `reports.php`):** Distribusi sebaran anggota per kelas/tingkat dan proporsi predikat capaian.
- **Top KPI Cards:** Total Jam Latihan Terlaksana, % Rata-rata Kehadiran Anggota, Jumlah Penghargaan/Prestasi Siswa, dan Indeks Kesehatan Program.

### ✍️ B. ✨ Asisten Draf Narasi Rapor Ekstrakurikuler Otomatis
- **Generator Narasi Rapor Cerdas (`ExtracurricularService::generateStudentNarrative()`):**
  - Mengagregasi persentase kehadiran, kompetensi yang dikuasai, peran siswa (*Leader / Member*), dan rekam jejak prestasi (*achievements*).
  - Menyusun paragraf deskriptif kualitatif rapor otomatis (contoh: *"Ananda sangat aktif mengikuti kegiatan Pramuka dengan kehadiran 95%, mampu memimpin regu, dan meraih tanda kecakapan Pionering"*).
- **1-Click Clipboard Copy & Bulk Export:** Tombol salin per siswa dengan notifikasi toast visual yang halus serta tombol salin seluruh narasi anggota ekskul.

### ⚡ C. Productivity & Workflow Tools di Ruang Kerja Pembina
- **Quick Attendance Actions (`attendance.php`):**
  - Tombol `Setel Semua Hadir (PRESENT)` dan `Setel Semua Izin (EXCUSED)` untuk absensi instan satu regu/kelompok.
  - Live indicator persentase kehadiran sesi.
  - Filter pencarian nama siswa cepat saat presensi.
- **Ekspor CSV Rekap:** Tombol unduh data anggota dan lembar nilai/presensi ke file spreadsheet UTF-8 BOM.
- **Pencarian & Penyaringan Instan:** Di halaman Anggota (`members.php`), Sesi (`sessions.php`), dan Kompetensi (`competencies.php`).

### 🎖️ D. Dukungan Kepanduan / Pathfinder & Rekam Prestasi
- Kategori badge / tingkat kecakapan pada pencatatan kompetensi & prestasi.
- Highlight lencana / penghargaan khusus pada lembar laporan profil siswa.

### 📈 E. Scorecard Mutu & Evaluasi Tahunan Ekstra (IPOO)
- Visual scorecard 4 pilar (*Input, Process, Output, Outcome*) dengan rating 1–5 dan status kesehatan program.

---

## 3. Rincian Perubahan File

### Service Layer & Controller
- `[MODIFY]` [`app/Services/ExtracurricularService.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/ExtracurricularService.php):
  - Tambahkan method `generateStudentNarrative(int $programId, int $studentId): string`
  - Tambahkan method `calculateAttendanceStats(int $programId): array`
  - Tambahkan method `calculateIpooHealth(int $programId): array`
- `[MODIFY]` [`app/Controllers/ExtracurricularController.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/ExtracurricularController.php):
  - Tambahkan method AJAX `studentNarrative(int $programId, int $studentId)`
  - Teruskan data visual analytics & IPOO health ke `reports()`, `sessions()`, dan `evaluations()`
- `[MODIFY]` [`app/Config/Routes.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php):
  - Daftarkan route AJAX `GET extracurricular/(:num)/student-narrative/(:num)`

### View Layer Modernization
- `[MODIFY]` [`app/Views/extracurricular/index.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/index.php): Upgrade KPI cards, badge kategori warna-warni, search filter interaktif.
- `[MODIFY]` [`app/Views/extracurricular/attendance.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/attendance.php): Quick-action `Setel Semua Hadir`, live counter %, filter siswa.
- `[MODIFY]` [`app/Views/extracurricular/members.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/members.php): Filter pencarian, badge status peran, ekspor CSV anggota.
- `[MODIFY]` [`app/Views/extracurricular/sessions.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/sessions.php): ApexCharts tren kehadiran sesi, filter tanggal, deteksi visual konflik jadwal.
- `[MODIFY]` [`app/Views/extracurricular/reports.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/reports.php): ApexCharts predikat donut chart, ✨ Asisten Narasi Rapor Rapor dengan 1-click clipboard copy, filter siswa, ekspor CSV.
- `[MODIFY]` [`app/Views/extracurricular/student_report.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/student_report.php): Kartu profil siswa, badge pencapaian prestasi, tombol cetak sertifikat / lembar laporan resmi.
- `[MODIFY]` [`app/Views/extracurricular/evaluations.php`](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/extracurricular/evaluations.php): Scorecard IPOO 4 pilar mutu dengan indikator status kesehatan.

---

## 4. Rencana Verifikasi & Pengujian

### Automated Tests
- Menjalankan unit test dan route security test via PHPUnit:
  ```powershell
  & "E:\xampp\php82\php.exe" vendor/bin/phpunit tests/database/CocurricularEngineTest.php tests/Security/CocurricularRouteSecurityTest.php tests/Feature/SmartAnalyticsRoutesTest.php --no-coverage
  ```
- Menambahkan test case baru untuk `generateStudentNarrative` dan `calculateAttendanceStats`.

### Manual Verification
- Uji navigasi halaman:
  1. Halaman Index Ekstrakurikuler: KPI cards & filter
  2. Halaman Sesi & Presensi: Fitur *Setel Semua Hadir* dan live progress
  3. Halaman Anggota: Tambah & saring anggota
  4. Halaman Laporan: Visual ApexCharts dan *1-Click Copy* narasi rapor ekstrakurikuler
  5. Halaman Evaluasi IPOO: Scorecard 4 pilar mutu
