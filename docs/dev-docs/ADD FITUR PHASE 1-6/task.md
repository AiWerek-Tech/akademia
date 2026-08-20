# Task Tracker — Master Enhancement Plan (Phases 1–6)

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

## Tahap E: Testing & Verification
- `[x]` Buat `tests/unit/SmartAnalyticsTest.php` — verifikasi Rubric, Differentiation, AdaptiveMode, Heatmap
- `[x]` Verifikasi seluruh test suite: **67/67 tests passing, 261 assertions, 0 Failures, 0 Errors**
- `[x]` Verifikasi RBAC route permission filters untuk seluruh endpoint baru
