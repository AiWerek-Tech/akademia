# Task Tracker — Master Enhancement Plan (Phases 1–7)

## Tahap A: UI/UX Polishing & Visual Graph Engines

### Phase 1 — Lineage Graph & Coverage Scanner
- `[x]` Buat `CurriculumLineageService.php` — engine analisis relasi Regulasi → CP → TP → ATP → RPP dengan skor kesehatan & gap detector
- `[x]` Buat `app/Views/smart/lineage_graph.php` — visualisasi alur lineage kurikulum bertingkat
- `[x]` Buat `SmartAnalyticsController.php` — endpoint untuk graph data & coverage gap
- `[x]` Integrasi sidebar menu "Peta Lineage Kurikulum"

### Phase 2 — KSP Compliance Radar & Governance
- `[x]` Evaluasi `KspComplianceService.php` — preview kepatuhan & evaluasi aturan regulasi
- `[x]` Integrasi Digital KSP dashboard dengan status kepatuhan multi-seksi

### Phase 3 — Adaptive Mode Switcher (Plugged vs Unplugged)
- `[x]` Buat `AdaptiveModeService.php` — engine switcher strategi hands-on/unplugged (Papua Pegunungan)

---

## Tahap B: Smart Pedagogical Studio & Rubric Engines

### Phase 4 — Taxonomy Rubric Generator & Differentiated Learning
- `[x]` Buat `RubricGeneratorService.php` — engine rubrik 4-tier otomatis berbasis Taksonomi Bloom (C1–C6)
- `[x]` Buat `DifferentiationService.php` — engine strategi diferensiasi pembelajaran (Konten, Proses, Produk)
- `[x]` AJAX endpoints di `SmartAnalyticsController.php` untuk integrasi studio RPP

---

## Tahap C: Live In-Class Pulse & Workspace Intelligence

### Phase 5 — Daily Teaching Workspace & Reflection Trends
- `[x]` Buat `ReflectionTrendsService.php` — engine agregasi tren refleksi pedagogis guru
- `[x]` Buat `app/Views/smart/reflection_trends.php` — dashboard tren frekuensi, pertumbuhan, dan insight refleksi
- `[x]` Integrasi sidebar menu "Tren Refleksi Mengajar"

---

## Tahap D: Mastery Heatmap & Targeted Remediation

### Phase 6 — Interactive Mastery Heatmap & Smart Remediation
- `[x]` Buat `MasteryHeatmapService.php` — engine matriks siswa × TP dengan skor numerik & distribusi ApexCharts
- `[x]` Buat `app/Views/smart/mastery_heatmap.php` — visualisasi interactive heatmap berwarna & tooltips
- `[x]` Buat `RemediationPackagerService.php` — auto-paket remedial terstruktur 3-tahap (Klarifikasi, Latihan, Exit-Check)
- `[x]` Buat `app/Views/smart/remedial_package.php` — lembar cetak & penugasan paket remedial terarah
- `[x]` Buat `NarrativeService.php` — engine penyusun draf narasi capaian kompetensi rapor otomatis
- `[x]` Buat `app/Views/smart/narrative_drafter.php` — UI human-in-the-loop review narasi rapor
- `[x]` Integrasi sidebar menu "Mastery Heatmap" dan "Draf Narasi Rapor"

---

## Tahap E: Level 1 Smart UI & Workflow Enhancements
- `[x]` `SmartAnalyticsController.php` + `Routes.php` — endpoint AJAX `POST smart/ajax/adaptive-mode`
- `[x]` `AssessmentController.php` + `Routes.php` — endpoint `POST interventions/bulk-status` untuk bulk approve/cancel
- `[x]` `assessment/interventions.php` — checkbox massal, floating action bar, dan batch submit
- `[x]` `smart/lineage_graph.php` — highlighting interaktif node upstream/downstream (BFS traversal)
- `[x]` `assessment/gradebook.php` — mode switcher (Tabel vs Kartu) dengan view responsif mobile
- `[x]` `lesson_plans/detail.php` — dropdown "✨ Asisten Cerdas" dengan 3 modal AI (Rubrik Bloom, Diferensiasi 3D, Adaptif Unplugged)

---

## Tahap F: Level 2 Deep Functional Enhancements
- `[x]` **Step 1: Smart Analytics Drill-Down & Export**
  - `[x]` Heatmap cell drill-down modal + quick filter buttons (`mastery_heatmap.php`)
  - `[x]` Heatmap client-side CSV export (`mastery_heatmap.php`)
  - `[x]` Database migration + Model `student_narrative_drafts` table
  - `[x]` Endpoint `POST smart/narrative-drafter/save` + Save button, character counter, saved indicator (`narrative_drafter.php`)
  - `[x]` Date range filter `from_date` & `to_date` (`reflection_trends.php`, `ReflectionTrendsService.php`, `SmartAnalyticsController`)
- `[x]` **Step 2: Teaching Workspace Live Tools**
  - `[x]` 🎲 Panggil Siswa Acak (Random Student Picker) with animation & chime (`session.php`)
  - `[x]` ⏱️ Activity Countdown Timer with presets (3m, 5m, 10m, 15m, 20m) & audio chime (`session.php`)
  - `[x]` ⌨️ Keyboard Shortcuts (`Alt+O`, `Alt+P`, `Space`) (`session.php`)
  - `[x]` LocalStorage autosave + restore banner for reflection journal (`reflect.php`)
  - `[x]` Clickable prompt / starter chips (`+ Antusiasme Siswa`, `+ Waktu Kurang`, `+ Remedial Terarah`) (`reflect.php`)
- `[x]` **Step 3: Gradebook Productivity**
  - `[x]` Live class statistics calculation bar (Rata-rata Kelas, Siswa Lengkap, Perlu Pendampingan) (`gradebook.php`)
  - `[x]` Gradebook client-side CSV export (`gradebook.php`)
- `[x]` **Step 4: Remedial Package Workflow Closure**
  - `[x]` Digital remedial completion form (`remedial_package.php`)
  - `[x]` Endpoint `POST smart/remedial/complete` with direct `mastery_records` update (`SmartAnalyticsController`, `Routes.php`)

---

## Tahap G: Phase 7 — Cocurricular, Character & 7KAIH Engine (Smart Modern Enhancements)
- `[x]` **Visual Analytics:** Radar Chart Dimensi Profil & Donut Chart Distribusi Capaian ApexCharts (`report.php`)
- `[x]` **✨ Generator Narasi Rapor P5 / Kokurikuler:** Auto-drafter narasi kualitatif karakter per siswa dengan tombol 1-klik salin clipboard (`report.php`, `CocurricularService.php`)
- `[x]` **✨ Asisten Rubrik & Deskriptor Dimensi:** Modal pedoman perilaku 4-level kompetensi MB-SAB (`detail.php`)
- `[x]` **Productivity Matriks Hasil:** Tombol Quick-Fill massal (`Setel BSH/SB/SAB`), live progress bar pengisian kelas, filter pencarian siswa, dan ekspor CSV (`detail.php`)
- `[x]` **Indeks Mutu IPOO:** Scorecard kesehatan program 4 pilar (Input, Process, Output, Outcome) dengan status mutu program (`detail.php`, `CocurricularService.php`)
- `[x]` **Pelacak 7KAIH Cerdas:** Tombol check-in massal dan pencarian siswa instan (`checkins.php`)
- `[x]` **Penyatuan Sidebar Navigasi:** Grup terpadu "Kokurikuler & Ekstra" yang rapi di `admin.php`
- `[x]` **Unit, Feature & Route Security Tests:** **100/100 tests passing, 372 assertions, 0 Failures, 0 Errors**
