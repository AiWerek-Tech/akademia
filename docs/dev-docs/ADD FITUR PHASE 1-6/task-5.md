# Task Checklist: WMVAA Akademia IALOS Modernization

## Phase 7: Kokurikuler & Karakter (7KAIH)
- [x] Schema & Migration Kokurikuler + 7KAIH (Tabel, Feature Flag & Seeder)
- [x] Cocurricular Engine Service & Controller
- [x] Modernisasi UI/UX: ApexCharts radar & donut, KPI cards, IPOO scorecard
- [x] ✨ Asisten Draf Narasi Rapor Kokurikuler (1-Klik Salin Clipboard & Export CSV)
- [x] Quick-Checkin 7KAIH & Visual Pedoman Dimensi Profil
- [x] Perbaikan Sidebar & Pengaktifan Flag `ialos_7kahi` pada DB Produksi
- [x] Unit & Feature Test Suite (100% Passed)

## Phase 8: Ekstrakurikuler & Karakter (Pathfinder/Honors)
- [x] Perbaikan Query Builder (`->pluck()` dihapus & diganti native CI4)
- [x] Smart Service Enhancements:
  - [x] `generateStudentNarrative(int $programId, int $studentId)`
  - [x] `calculateAttendanceStats(int $programId)`
  - [x] `calculateIpooHealth(int $programId)`
- [x] Controller & Routes: Endpoint AJAX `student-narrative` & integrasi data analytics
- [x] Modernisasi UI/UX Views:
  - [x] `index.php`: Top KPI cards, category badges dengan icon warna, search filter
  - [x] `attendance.php`: Quick buttons `Setel Semua Hadir` & `Setel Semua Izin`, live % meter, student filter
  - [x] `sessions.php`: ApexCharts attendance timeline trend chart, filter tanggal/lokasi
  - [x] `members.php`: Filter nama instan, role badge, ekspor CSV
  - [x] `reports.php`: ApexCharts donut distribusi predikat, ✨ Asisten Narasi Rapor Ekstrakurikuler (1-click copy), ekspor CSV
  - [x] `student_report.php`: Draf narasi rapor kualitatif, badge prestasi/lencana, tata letak cetak rapor
  - [x] `evaluations.php`: IPOO 4-pillar quality scorecard & status kebugaran program
  - [x] `detail.php`: Ringkasan IPOO health meter & tombol aksi cepat
- [x] Unit & Feature Test Suite: `ExtracurricularEngineTest.php` (48 tests / 197 assertions passed)
