# WMVAA Akademia — Reporting & Portfolio Module

## 1. Overview

Modul pelaporan mengelola laporan semester (snapshot), rapor siswa, portofolio, narasi, dan decision support kenaikan kelas.

Blueprint: `docs/WMVAA_Academia_IALOS/07_ASSESSMENT_MASTERY_REPORTING.md` (§7–12).

---

## 2. Reporting Dashboard (`/reporting`)

### 2.1 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **Index** | `GET /reporting` | Daftar laporan semester (filter status) | `reporting.view` |
| **Generate** | `GET /reporting/generate/:studentId` | Generate snapshot per siswa | `reporting.manage` |
| **Detail** | `GET /reporting/:snapshotId` | Detail laporan (nilai, narasi, portofolio) | `reporting.view` |
| **Lock** | `POST /reporting/:snapshotId/lock` | Kunci laporan (DRAFT → LOCKED) | `reporting.manage` |
| **Publish** | `POST /reporting/:snapshotId/publish` | Publikasikan laporan (hanya jika semua narasi APPROVED) | `reporting.manage` |
| **Bulk Generate** | `POST /reporting/bulk-generate` | Generate massal untuk rombel | `reporting.manage` |

---

## 3. Snapshot Generation Flow

`ReportingService::generateSnapshot($unitId, $periodId, $studentId, $userId)`

**Data Sources:**
1. **Mata Pelajaran & Guru** → `teaching_assignments` (unit+periode+kelas, status=ACTIVE, role=PRIMARY)
2. **Nilai Akhir** → `summative_results` (prioritas status=VALIDATED, fallback latest) → `raw_score`, `grade_label`
3. **Mastery TP** → `mastery_records` → `assessment_objectives` → `assessments` (unit+mapel, status=PUBLISHED/CLOSED) → mastered = ACHIEVED/ADVANCED
4. **Cakupan TP** → TP unik pada assessment PUBLISHED/CLOSED untuk unit+mapel
5. **Kehadiran** → `attendance_sessions` + `student_attendances` per mapel/kelas/periode (HADIR+TERLAMBAT+DISPENSASI)

**Output:** Upsert `report_snapshots` + `report_subject_results` (idempotent per student/periode).

---

## 4. Narrative Management

### 4.1 Routes

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **Save Narrative** | `POST /reporting/narrative/:subjectResultId/save` | Simpan narasi guru | `reporting.manage` |
| **Generate Draft** | `GET /reporting/narrative/:subjectResultId/generate` | AI draft dari `ReportingService::generateDraftNarrative()` | `reporting.manage` |
| **Approve** | `POST /reporting/narrative/:narrativeId/approve` | DRAFT → APPROVED (wajib sebelum publish) | `reporting.manage` |

### 4.2 Workflow
- Narasi disimpan sebagai **DRAFT**
- `publishSnapshot()` **tolak** jika ada `report_subject_results` tanpa minimal 1 narasi `APPROVED`
- Setelah semua narasi `APPROVED` → publish berhasil, status `PUBLISHED`, `published_at` diset

---

## 5. Student Portfolio (`/reporting/portfolio/:studentId`)

### 5.1 Concept
Portofolio = referensi ke bukti yang sudah ada (`source_type` + `source_id`) tanpa duplikasi file.

### 5.2 Auto-Pull Sources (`ReportingService::suggestPortfolioItems()`)
1. **Assessment Evidence** → `assessment_evidence` → `assessment_attempts` → `assessments` (kategori PROJECT/BEST_WORK)
2. **Cocurricular Evidence** → `cocurricular_evidences` (kategori COCURRICULAR)
3. **Extracurricular Achievements** → `extracurricular_achievements` → `extracurricular_members` → `extracurricular_programs` (kategori EXTRACURRICULAR)

### 5.3 Features

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /reporting/portfolio/:studentId` | Lihat portofolio + rekomendasi | `reporting.view` |
| **Add Manual** | `POST /reporting/portfolio/:studentId/add` | Tambah item manual | `reporting.manage` |
| **Import** | `POST /reporting/portfolio/:studentId/import` | Import dari rekomendasi (dedupe by source_type+source_id) | `reporting.manage` |
| **Delete** | `POST /reporting/portfolio/:itemId/delete` | Hapus item | `reporting.manage` |
| **Toggle Highlight** | `POST /reporting/portfolio/:itemId/highlight` | Toggle starred | `reporting.manage` |

---

## 6. Class Dashboard (`/reporting/class-dashboard`)

Agregat per kelas:
- Rata-rata skor, distribusi predikat (A–E)
- Rata-rata mastery & cakupan TP per mapel
- Attendance rate per kelas
- Top/bottom students

---

## 7. Promotion Readiness (`/reporting/promotion`)

### 7.1 Concept
Decision support kenaikan kelas/kelulusan (Blueprint §12). Output: **READY** / **REVIEW** / **ATTENTION**.

### 7.2 Factors
| Faktor | Threshold ATTENTION |
|---|---|
| Kelengkapan nilai (`completion_pct`) | < 50% (dengan data) |
| Rata-rata kehadiran | < 75% |
| Intervensi belum tuntas | > 2 |
| Tidak ada data laporan | REVIEW (soft) |

### 7.3 Route

| Aksi | Route | Deskripsi | Permission |
|---|---|---|---|
| **View** | `GET /reporting/promotion` | Tabel kesiapan + filter kelas | `reporting.view` |

---

## 8. Student Report Card API

### 8.1 Endpoint
```
GET /reporting/student/:studentId/card
```

Returns JSON data rapor siswa untuk integrasi.  
Controller: `ReportingController::studentReportCardApi()`.

---

## 9. Print Layout

| Print | Route | Deskripsi |
|---|---|---|
| **Report Print** | `GET /reporting/:snapshotId/print` | Cetak laporan semester |
| **Student Card** | `GET /reporting/student/:studentId/card` | Data rapor siswa (JSON) |

---

## 10. Database Tables

| Table | Fungsi |
|---|---|
| `report_snapshots` | Snapshot rapor per siswa per periode (DRAFT/LOCKED/PUBLISHED) |
| `report_subject_results` | Hasil per mapel dalam snapshot (score, predicate, mastery%, attendance%, TP coverage) |
| `report_narratives` | Narasi per subject_result (SUBJECT/GENERAL/COCURRICULAR/EXTRACURRICULAR), status DRAFT/APPROVED |
| `portfolio_collections` | Item portofolio (referensi: source_type + source_id, no file duplication) |
| `reporting_policies` | Kebijakan perhitungan nilai ber-versioning (Phase 6 table) |

---

## 11. Services

| Service | Fungsi |
|---|---|
| `ReportingService` | Core engine: snapshot generation, narasi approve/publish, portfolio suggest/import, promotionReadiness |
| `NarrativeService` | AI draft narasi berbasis mastery TP (external, dipakai ReportingService untuk draft) |

---

## 12. RBAC

| Permission | Scope |
|---|---|
| `reporting.view` | Index, detail, class-dashboard, promotion, portfolio view, student card API, print |
| `reporting.manage` | Generate, lock, publish, narasi save/approve, portfolio add/import/delete/highlight, bulk actions |

Granted to: `super_admin`, `superadmin`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `kepala_sekolah`.