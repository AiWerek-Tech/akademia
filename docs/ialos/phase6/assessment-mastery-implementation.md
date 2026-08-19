# Assessment, Mastery & Reporting Engine — Dokumentasi Implementasi

Fase 6 menyediakan siklus penilaian yang berujung pada **mastery berbasis Tujuan Pembelajaran (TP)**, rekomendasi **intervensi belajar**, dan **kebijakan pelaporan** — sebagai penghubung antara pengajaran harian (Phase 5) dan rapor/portfolio (Phase 9).

## 1. Model Data

Migrasi `20260820100000_CreatePhase6AssessmentMasteryTables.php` membuat 11 tabel:

| Tabel | Fungsi |
|---|---|
| `assessments` | Induk penilaian (unit, periode, kelas, mapel, guru, tipe, bentuk, tanggal, status, OCC `revision_number`). |
| `assessment_objectives` | Pemetaan assessment → TP (`learning_objective_id`) dengan urutan. |
| `assessment_criteria` | Kriteria penilaian yang memetakan ke TP via `learning_objective_id` (nullable — kriteria tanpa TP tidak menghitung mastery). |
| `assessment_items` | Butir soal dengan tipe skor/bobot. |
| `assessment_attempts` | Percobaan/jawaban siswa. |
| `criterion_results` | Hasil siswa per kriteria: skor, level_index, status (diturunkan otomatis). |
| `assessment_evidence` | Bukti hasil belajar siswa (lampiran/referensi). |
| `assessment_feedback` | Umpan balik guru ke siswa. |
| `mastery_records` | Status mastery murid per TP: `result`, `source` (`ASSESSMENT`/`MANUAL`/`OBSERVATION`), `confidence`, `version` (OCC), dan jejak bukti/attempt. |
| `interventions` | Rencana intervensi: tipe `REMEDIAL`/`REINFORCEMENT`/`ENRICHMENT`, status `RECOMMENDED`/`APPROVED`/`COMPLETED`/`CANCELLED`, target tanggal & catatan tindak lanjut. |
| `reporting_policies` | Kebijakan pelaporan (format skor, kategori, cut-off) dengan `version` dan `status` immutable. |

Seluruh tabel memakai fondasi `baseFields()` (uuid, audit `created_by`/`updated_by`, timestamp) dan `auditFields()` dari Phase 1. Migrasi idempotent: `up()`/`down()` aman dijalankan ulang, dan seeding RBAC dilakukan saat migrasi (karena migrasi Phase 6 berjalan sebelum `CoreSeeder` pada instalasi baru).

## 2. State Machine Assessment

`AssessmentService::transition($id, $targetStatus, $revisionNumber)`:

```
DRAFT ──────────► PUBLISHED ──────────► CLOSED
  │  ▲                │                     
  └──┘                └── (hanya melalui CLOSED)
```

- Validasi ketat: transisi tidak sah ditolak (`InvalidArgumentException`).
- `PUBLISHED` membuka gradebook; `CLOSED` mengunci perubahan dan mengunci pengubahan/deletion.
- `destroy()` hanya mengizinkan menghapus assessment yang belum `PUBLISHED` (jika sudah dipublikasikan, harus melewati alur resmi).

## 3. Gradebook & Derivation Mastery

`AssessmentService::saveGradebook($id, $payload)` memproses per siswa:
- Upsert `criterion_results` dengan skor + notes.
- Status kriteria diturunkan via `MasteryService::deriveCriterionStatus(score, max, levelIndex)` (fallback otomatis bila tidak dikirim).
- Menyinkronkan mastery per TP melalui `MasteryService::syncFromAssessment($assessmentId, $periodId)`:
  - Mengambil TP yang tercakup dalam assessment.
  - Menghitung status agregat: **worst-case dominates**; semua `ADVANCED` → `ADVANCED`; tanpa data → `DEVELOPING`.
  - Upsert `mastery_records` dengan kenaikan `version`.
  - Membuat intervensi otomatis untuk status yang membutuhkan tindak lanjut.

`MasteryService::setMastery($studentId, $objectiveId, $result, $userId, $opts)` menyediakan koreksi manual dengan sumber `MANUAL`.

## 4. Intervensi Belajar

- `recommendInterventions($unitId, $periodId, ?$classroomId)` melakukan pemindaian batch:
  - `NEEDS_SUPPORT` → `REMEDIAL`
  - `DEVELOPING` → `REINFORCEMENT`
  - (`ACHIEVED`/`ADVANCED` dapat direkomendasikan `ENRICHMENT` via alur manual)
- Tidak membuat duplikat untuk pasangan murid-TP yang masih `RECOMMENDED`/`APPROVED`.
- `updateIntervention($id, ...)` mengalihkan status dan mencatat tindak lanjut.

## 5. Mastery Board & Summary

`MasteryService::board($unitId, $periodId, $classroomId, $subjectId, ?$objectiveId)`:
- Objective dalam cakupan unit (TP adaptasi unit + TP nasional untuk mapel tersebut).
- Matriks murid × TP dengan `mastery` (record terbaru) dan `intervention` (pending) per pasangan.
- `masterySummary(...)` menghitung distribusi status untuk gauge ringkasan.

## 6. Reporting Policies

- `AssessmentService::savePolicies($unitId, $periodId, $payload)` membuat versi kebijakan baru (`version+1`) tanpa mengubah historis; policy terakhir dengan `status ACTIVE` menjadi acuan.
- `policies()` menyajikan daftar kebijakan dengan status versioning untuk audit.

## 7. RBAC & Keamanan Route

Route (semua di bawah filter `auth`, `unit_access`, `password_change_required`, dan permission):

| Route | Permission |
|---|---|
| `GET assessment`, `GET assessment/(:num)` | `assessment.view` |
| `POST/GET assessment/*` (create, edit, update, transition, delete, gradebook, evidence, feedback) | `assessment.manage` |
| `mastery`, `mastery/*`, `interventions`, `reporting-policies` | `assessment.mastery` |

- Seeding: `admin_smp`, `admin_sma`, `wakasek_kurikulum`, dan `super_admin` mendapat `assessment.view` + `assessment.manage` + `assessment.mastery`; `kepala_sekolah` mendapat `assessment.view` (supervisi read-only).
- Guru (via `RolePermissions::GURU`) mendapat `assessment.view` + `assessment.manage` — hanya untuk assessment milik sendiri (kepemilikan ditegakkan di `AssessmentController::assertAssessmentAccess` lewat `resolveOwnTeacherId`); guru **tidak** mendapat `assessment.mastery`.
- `UnitScopeService::assertUnit` menjamin batas unit; kegagalan otorisasi diarahkan ke `/assessment` dengan pesan error.

## 8. UI

- `assessment/index` — daftar + filter (kelas, mapel, tipe, status).
- `assessment/create|edit` — form dengan pemetaan TP (refresh otomatis saat ganti mapel) dan baris kriteria dinamis (`_form.php`, `_criterion_row.php`).
- `assessment/detail` — ringkasan, status workflow, TP & kriteria, tombol transisi.
- `assessment/gradebook` — tabel murid × kriteria: input skor, status, notes, detail bukti/feedback per siswa.
- `assessment/mastery` — Mastery Board (matriks + summary + filter).
- `assessment/interventions` — daftar intervensi + aksi rekomendasi/update.
- `assessment/policies` — kebijakan pelaporan + simpan versi baru.

Navigasi sidebar & mobile: grup **"Penilaian & Mastery"** di layout admin.

## 9. Pengujian

- `tests/database/AssessmentMasteryEngineTest.php` — 10 engine test: schema & RBAC seed, derivation status, end-to-end create, state machine + transisi tidak sah, larangan delete terpublikasi, gradebook → mastery & intervensi, mastery board matrix, set mastery manual + batch recommend, versioning reporting policy. **10/10 lulus.**
- `tests/Security/AssessmentRouteSecurityTest.php` — 10 route security test: guest → login, viewer 403, super admin 200, guru index 200, guru mastery 403, admin SMA mastery/interventions/policies 200, admin SMP dilarang membuka assessment SMA, guru tidak dapat membuka assessment guru lain, guru dapat membuka assessment sendiri, viewer tidak dapat meng-*grade*. **10/10 lulus.**
- Perintah: `php vendor/bin/phpunit tests/database/AssessmentMasteryEngineTest.php tests/Security/AssessmentRouteSecurityTest.php` (DB test: SQLite `:memory:`).

## 10. Catatan Integrasi

- Gradebook menulis mastery secara langsung (tanpa antrian); operasi batch transaksional.
- TP nasional (tanpa unit) tetap dihitung di Mastery Board selama mapelnya sesuai.
- Kebijakan pelaporan dan intervensi menjadi masukan untuk Phase 9 (Reporting & Portfolio).