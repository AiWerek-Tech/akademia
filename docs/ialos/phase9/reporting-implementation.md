# Dokumentasi Implementasi Phase 9 — Reporting & Portfolio

Dokumen ini menjelaskan implementasi teknis **Phase 9: Reporting & Portfolio** pada IALOS Education (Blueprint `07_ASSESSMENT_MASTERY_REPORTING.md` §7–12).

## Tujuan

Menghasilkan **rapor semester sebagai output** dari pipeline penilaian yang sudah ada (`Teaching → Evidence → Assessment → TP Attainment → Summative → Rapor`), dengan:

- Narasi yang **wajib direview guru** sebelum rapor diterbitkan (blueprint §10).
- Portofolio yang **menarik bukti terpilih secara otomatis** tanpa duplikasi file (blueprint §11).
- **Decision support kenaikan kelas/kelulusan** untuk dewan guru (blueprint §12).

## Model Data

| Tabel | Peran |
|---|---|
| `reporting_policies` | Kebijakan perhitungan nilai ber-versioning per unit/periode/mapel (dimiliki Phase 6). |
| `report_snapshots` | Rapor beku per siswa per periode; status `DRAFT → LOCKED → PUBLISHED`; unique `(unit, period, student, snapshot_type)`. |
| `report_subject_results` | Hasil per mapel dalam satu snapshot: `final_score`, `final_predicate`, `tp_coverage_pct`, `mastery_pct`, `attendance_pct`, `detail_json`. |
| `report_narratives` | Narasi per hasil mapel: `narrative_type` (SUBJECT/GENERAL/COCURRICULAR/EXTRACURRICULAR), `source` (TEACHER/AI), `status` (DRAFT/APPROVED). |
| `portfolio_collections` | Item portofolio siswa; mereferensikan sumber bukti (`source_type` + `source_id`) tanpa menyalin file. |

## Snapshot Generation (`ReportingService::generateSnapshot`)

1. **Siswa & kelas:** Ambil `elective_students` (validasi unit) → `classroom_id`.
2. **Daftar mapel & guru:** dari `teaching_assignments` (unit + periode + kelas, `status=ACTIVE`); guru utama diutamakan (`assignment_role=PRIMARY`). Fallback `subject_unit_availability` bila siswa tak punya pemetaan kelas.
3. **Nilai akhir:** dari `summative_results` ter-`VALIDATED` (prefer) → `raw_score` & `grade_label`. Predikat dihitung otomatis (`scoreToPredicate`) bila kosong.
4. **Penguasaan TP:** `mastery_records` → `assessment_objectives` → `assessments` (filter unit + mapel). Mastered = `result` ∈ {`ACHIEVED`, `ADVANCED`}; hasil terbaru per TP dipakai; `mastery_pct` = mastered/total.
5. **Cakupan TP:** total TP unik pada assessment `PUBLISHED`/`CLOSED` untuk unit + mapel; `tp_coverage_pct` = TP tercatat/total.
6. **Kehadiran:** `attendance_sessions` + `student_attendances` per mapel/kelas/periode; hadir = `HADIR` + `TERLAMBAT` + `DISPENSASI` (konsisten dengan `AttendanceService::STUDENT_STATUSES`).
7. Upsert snapshot (idempoten) → hapus hasil lama → isi `report_subject_results` → audit log.

## Narasi & Review Wajib

- `saveNarrative()` menyimpan/upsert narasi guru per `(subject_result_id, narrative_type, source)` dengan status `DRAFT`.
- `generateDraftNarrative()` menghasilkan draft narasi dari data snapshot (nilai, penguasaan, kehadiran).
- `approveNarrative()` memindahkan `DRAFT → APPROVED` (idempoten, dengan audit).
- `publishSnapshot()` **menolak** penerbitan jika ada hasil mapel tanpa ≥1 narasi `APPROVED` — `RuntimeException("…narasi wajib disetujui…")`. Setelah lolos, status `PUBLISHED` + `published_at`.

## Portofolio Auto-Pull (Blueprint §11)

- `suggestPortfolioItems()` mengumpulkan kandidat dari tiga sumber (referensi, tanpa menyalin file):
  1. `assessment_evidence` → `assessment_attempts` → `assessments` (kategori PROJECT/BEST_WORK).
  2. `cocurricular_evidences` → program (kategori COCURRICULAR).
  3. `extracurricular_achievements` → member → program/kompetensi (kategori EXTRACURRICULAR).
- `importPortfolioItems()` menambahkan item terpilih ke `portfolio_collections` dengan `source_type` + `source_id`; **di-skip bila referensi yang sama sudah ada** (dedupe).
- UI: halaman `reporting/portfolio/(:num)` menampilkan rekomendasi dengan checkbox impor massal.

## Decision Support Kenaikan Kelas (Blueprint §12)

`promotionReadiness($unitId, $periodId, ?$classroomId)`:

- Untuk setiap siswa aktif: kelengkapan nilai (`completion_pct`), rata-rata nilai, rata-rata mastery, rata-rata kehadiran (peringatan <75%), dan jumlah intervensi belum tuntas (`status NOT IN (COMPLETED, CANCELLED)`).
- Klasifikasi:
  - `READY` — tidak ada peringatan.
  - `REVIEW` — ada peringatan ringan (mis. nilai belum lengkap, belum ada data laporan).
  - `ATTENTION` — kehadiran <75%, >2 intervensi belum tuntas, atau kelengkapan <50% (dengan data).
- Keputusan akhir tetap milik sekolah; halaman ini hanya menyediakan data pendukung.

## Route & RBAC

Routes baru (`app/Config/Routes.php`):

| Method | Route | Action | Permission |
|---|---|---|---|
| POST | `reporting/narrative/(:num)/approve` | `approveNarrative` | `reporting.manage` |
| POST | `reporting/portfolio/(:num)/import` | `importPortfolio` | `reporting.manage` |
| GET | `reporting/promotion` | `promotion` | `reporting.view` |

Menu sidebar `Rapor & Portofolio` menambahkan **Kesiapan Kenaikan Kelas**.

Permissions: `reporting.view` (lihat rapor, dashboard, portofolio, kesiapan) dan `reporting.manage` (generate snapshot, narasi, publish, kelola portofolio) untuk `super_admin`, `superadmin`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `kepala_sekolah`.

## Baseline Test DB

Karena test suite tidak menjalankan migrasi (lihat `tests/_support/IsolatedDatabaseTestTrait.php`), baseline `wmvaa_akademia_ialos_final_test` harus disinkronkan manual:

1. Tabel Phase 9: `report_snapshots`, `report_subject_results`, `report_narratives`, `portfolio_collections` (DDL identik dengan dev).
2. Permissions `reporting.view` / `reporting.manage` + grant `role_permissions` untuk peran di atas.

## Pengujian

`tests/database/ReportingEngineTest.php` (11 test, 41 assertions):

- Snapshot generation dari `teaching_assignments` (nilai, predikat, mastery, cakupan TP, kehadiran) + idempotensi + tolak siswa tak dikenal.
- Publish diblokir tanpa narasi disetujui; sukses setelah semua narasi `APPROVED`.
- Approve narasi idempoten.
- Portfolio suggestion dari `ASSESSMENT_EVIDENCE` + import dengan dedupe.
- `promotionReadiness` klasifikasi READY/REVIEW, filter kelas, dan ringkasan.
- Route `reporting/promotion` accessible untuk pemegang `reporting.view`.

Jalankan:

```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit --no-coverage tests/database/ReportingEngineTest.php
```

## Catatan Implementasi

- `selectCount('DISTINCT …')` tidak dipakai karena `protectIdentifiers` merusak SQL di CI4 — digunakan `select('…')->distinct()->getResultArray()` dan `countAllResults()`.
- `reporting_policies` pada migrasi Phase 9 adalah no-op karena tabel sudah dimiliki Phase 6 (`createTable(…, true)`), dan kolom aktual memakai `policy_name`/`calculation_method`.