# Assessment, Mastery & Reporting Engine — Dokumentasi Implementasi

Fase 6 menyediakan siklus penilaian yang berujung pada **mastery berbasis Tujuan Pembelajaran (TP)**, rekomendasi **intervensi belajar**, dan **kebijakan pelaporan** — sebagai penghubung antara pengajaran harian (Phase 5) dan rapor/portfolio (Phase 9).

## 1. Model Data

Migrasi `20260820100000_CreatePhase6AssessmentMasteryTables.php` membuat 11 tabel, disempurnakan oleh `20260821000000_Phase6AssessmentRefinements.php` (dimensi profil lulusan pada evidence dan target kriteria pada intervensi) dan `20260822000000_CreateSummativeResults.php` (pengolahan nilai sumatif):

| Tabel | Fungsi |
|---|---|
| `assessments` | Induk penilaian (unit, periode, kelas, mapel, guru, tipe, bentuk, tanggal, status, OCC `revision_number`). |
| `assessment_objectives` | Pemetaan assessment → TP (`learning_objective_id`) dengan urutan. |
| `assessment_criteria` | Kriteria penilaian yang memetakan ke TP via `learning_objective_id` (nullable — kriteria tanpa TP tidak menghitung mastery); menyimpan `rubric_levels_json` (definisi level rubrik per kriteria). |
| `assessment_items` | Butir soal dengan tipe skor/bobot. |
| `assessment_attempts` | Percobaan/jawaban siswa. |
| `criterion_results` | Hasil siswa per kriteria: skor, level_index, status (diturunkan otomatis). |
| `assessment_evidence` | Bukti hasil belajar siswa (lampiran/referensi); dapat dialign ke Dimensi Profil Lulusan (`profile_dimension_id`) dan tujuan kokurikuler (`cocurricular_objective_id`) sesuai blueprint §7. |
| `assessment_feedback` | Umpan balik guru ke siswa. |
| `mastery_records` | Status mastery murid per TP: `result`, `source` (`ASSESSMENT`/`MANUAL`/`OBSERVATION`), `confidence`, `version` (OCC), dan jejak bukti/attempt. |
| `interventions` | Rencana intervensi: tipe `REMEDIAL`/`REINFORCEMENT`/`ENRICHMENT`, status `RECOMMENDED`/`APPROVED`/`COMPLETED`/`CANCELLED`, target kriteria yang gagal (`criterion_id`) untuk remedial tertarget, tanggal & catatan tindak lanjut. |
| `reporting_policies` | Kebijakan pelaporan (format skor, kategori, cut-off) dengan `version` dan `status` immutable. |
| `summative_results` | Hasil pengolahan mastery TP menjadi nilai akhir per siswa/mapel/periode sesuai kebijakan: `calculation_method`, `raw_score`, `grade_label`, `detail_json` (breakdown per TP), `status` `DRAFT`/`VALIDATED` dengan jejak `validated_by`/`validated_at`. Unique `(unit, period, subject, student)`. |

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
- **Level rubrik 0-based:** bila guru memilih level rubrik (`level_index` 0–3) di picker gradebook, status kriteria diturunkan dari `level_index` (level 0 = `NEEDS_SUPPORT` … level 3 = `ADVANCED`); fallback skor/maks bila level kosong via `MasteryService::deriveCriterionStatus(score, max, levelIndex)`.
- **Enforcement tipe (semantik):** `MasteryService::isAttainmentType()` — hanya `FORMATIVE` dan `SUMMATIVE` yang menulis `mastery_records` dan memicu intervensi otomatis; `DIAGNOSTIC` tetap mencatat `criterion_results` (readiness/starting point) tetapi **tidak** menjadi bagian pengolahan nilai akhir. `mastery_records.source` menyimpan tipe assessment (`FORMATIVE`/`SUMMATIVE`/`MANUAL`).
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
- **Remediasi tertarget per kriteria:** `MasteryService::failingCriteriaForPair($studentId, $objectiveId)` mengumpulkan kriteria yang belum tuntas (`NEEDS_SUPPORT`/`DEVELOPING`) dari seluruh assessment formatif/sumatif; `recommendInterventionForPair()` menyimpan `criterion_id` kriteria paling gagal dan menambahkan teksnya ke `planned_activity` ("Fokus kriteria: …"), sehingga remedial menunjuk kriteria spesifik, bukan hanya TP.
- Tidak membuat duplikat untuk pasangan murid-TP yang masih `RECOMMENDED`/`APPROVED`.
- `updateIntervention($id, ...)` mengalihkan status dan mencatat tindak lanjut.
- **Intervensi manual (termasuk pengayaan):** `MasteryService::createIntervention($studentId, $objectiveId, $type, $plannedActivity, $userId, $opts)` membuat intervensi `REMEDIAL`/`REINFORCEMENT`/`ENRICHMENT` langsung berstatus `APPROVED`, dengan validasi kriteria cocok dengan TP dan pencatatan audit `INTERVENTION_CREATE`. Form "Buat Intervensi Manual" tersedia di halaman `interventions` (pemilih siswa, TP, tipe, rencana kegiatan).

## 5. Rubrik Assessment & Alignment Evidence

- **Rubrik per kriteria:** `assessment_criteria.rubric_levels_json` menyimpan level rubrik `{level_index, label, score, description}`; `assessments.rubric_json` menampung rubrik keseluruhan. Editor level rubrik tersedia di form authoring (`_form.php` / `_criterion_row.php`) — JSON diserialisasi otomatis ke `criteria[i][rubric_levels_json]`.
- Tampilan: badge label rubrik pada header kolom gradebook dan pada tabel kriteria di halaman detail.
- **Picker level rubrik di gradebook:** kolom siswa per kriteria menyediakan select level (dibaca dari `rubric_levels_json`); `saveGradebook` meneruskan `level_index` ke `criterion_results`.
- **Upload bukti belajar:** `AssessmentController::addEvidence` menerima file (PDF, gambar, video, dokumen, dll; maks 10 MB) disimpan ke `writable/uploads/evidence` dengan `file_path` + `meta_json` (nama asli, ukuran, MIME, ekstensi); download dilayani route terproteksi `GET assessment/evidence-file/(:num)` dengan pemeriksaan permission `assessment.manage` dan akses assessment asal. Jenis bukti divalidasi terhadap `ALLOWED_EVIDENCE_TYPES` (12 jenis blueprint §7). Form bukti kini memiliki pemilih `evidence_type` + input file.
- **Daftar evidence & feedback read-only:** `AssessmentService::gradebook()` melampirkan daftar bukti dan umpan balik per siswa (dari `attempt_ids` assessment), ditampilkan di blok detail siswa tanpa form edit.
- **UI `assessments.rubric_json`:** textarea JSON rubrik tingkat assessment di form authoring (`_form.php`), diteruskan `store()`/`update()`, dan dirender sebagai badge level di `assessment/detail`.
- **Alignment evidence:** `AssessmentService::addEvidence()` menyimpan `profile_dimension_id` (Dimensi Profil Lulusan, FK → `graduate_profile_dimensions`) dan `cocurricular_objective_id` (nullable, tanpa FK — tabel kokurikuler disediakan Phase 9). Form bukti di gradebook menyediakan pemilih kriteria dan dimensi profil lulusan.

## 6. Mastery Board & Summary

`MasteryService::board($unitId, $periodId, $classroomId, $subjectId, ?$objectiveId)`:
- Objective dalam cakupan unit (TP adaptasi unit + TP nasional untuk mapel tersebut).
- Matriks murid × TP dengan `mastery` (record terbaru) dan `intervention` (pending) per pasangan.
- `masterySummary(...)` menghitung distribusi status untuk gauge ringkasan.

## 7. Reporting Policies

- `AssessmentService::savePolicies($unitId, $periodId, $payload)` membuat versi kebijakan baru (`version+1`) tanpa mengubah historis; policy terakhir dengan `status ACTIVE` menjadi acuan.
- `policies()` menyajikan daftar kebijakan dengan status versioning untuk audit.

## 7b. Pengolahan Nilai Sumatif & Validasi Guru

`SummativeProcessingService` mengubah mastery TP menjadi nilai akhir per siswa/mapel/periode (jembatan ke rapor Phase 9, blueprint §8–9):

- **Metode:** `AVERAGE` (rata-rata skor TP), `LATEST` (TP terbaru), `WEIGHTED` (bobot = jumlah kriteria per TP dari assessment FORMATIVE/SUMMATIVE), `PROFICIENCY` (mastery tertinggi). Pemetaan hasil ke skor default: `NEEDS_SUPPORT`=60, `DEVELOPING`=75, `ACHIEVED`=88, `ADVANCED`=95; predikat A≥90/B≥80/C≥70/D≥60/E<60.
- **Kebijakan aktif:** `activePolicy()` memilih `reporting_policies` aktif (prefer `is_active`, fallback versi terbaru); tanpa policy → AVERAGE default.
- **Alur:** `processSubject()` menghitung per siswa dalam cakupan TP (unit + nasional), melewati hasil yang sudah `VALIDATED` (terkunci), lalu upsert `summative_results` dengan `detail_json` (breakdown per TP). `processAll()` menjalankan semua mapel aktif.
- **Validasi guru:** `validate($id, $userId)` mengunci hasil menjadi `VALIDATED` (+ jejak); `reopen()` membukanya kembali untuk diproses ulang. Audit: `SUMMATIVE_PROCESS`/`SUMMATIVE_VALIDATE`/`SUMMATIVE_REOPEN`.
- **UI:** `assessment/summative` (status proses per mapel + tombol "Proses Semua Mapel"), `assessment/summative_detail` (hasil per siswa/kelas, filter kelas, tombol Validasi/Buka, modal rincian per TP).

## 8. RBAC & Keamanan Route

Route (semua di bawah filter `auth`, `unit_access`, `password_change_required`, dan permission):

| Route | Permission |
|---|---|
| `GET assessment`, `GET assessment/(:num)` | `assessment.view` |
| `POST/GET assessment/*` (create, edit, update, transition, delete, gradebook, evidence, feedback, evidence-file) | `assessment.manage` |
| `mastery`, `mastery/*`, `interventions`, `interventions/create`, `reporting-policies` | `assessment.mastery` |
| `summative`, `summative/(:num)`, `summative/process`, `summative/(:num)/validate`, `summative/(:num)/reopen` | `assessment.mastery` |

- Seeding: `admin_smp`, `admin_sma`, `wakasek_kurikulum`, dan `super_admin` mendapat `assessment.view` + `assessment.manage` + `assessment.mastery`; `kepala_sekolah` mendapat `assessment.view` (supervisi read-only).
- Guru (via `RolePermissions::GURU`) mendapat `assessment.view` + `assessment.manage` — hanya untuk assessment milik sendiri (kepemilikan ditegakkan di `AssessmentController::assertAssessmentAccess` lewat `resolveOwnTeacherId`); guru **tidak** mendapat `assessment.mastery`.
- `UnitScopeService::assertUnit` menjamin batas unit; kegagalan otorisasi diarahkan ke `/assessment` dengan pesan error.

## 9. UI

- `assessment/index` — daftar + filter (kelas, mapel, tipe, status).
- `assessment/create|edit` — form dengan pemetaan TP (refresh otomatis saat ganti mapel) dan baris kriteria dinamis (`_form.php`, `_criterion_row.php`).
- `assessment/detail` — ringkasan, status workflow, TP & kriteria, tombol transisi.
- `assessment/gradebook` — tabel murid × kriteria: input skor, status, notes, detail bukti/feedback per siswa.
- `assessment/mastery` — Mastery Board (matriks + summary + filter).
- `assessment/interventions` — daftar intervensi + aksi rekomendasi/update + form pembuatan manual.
- `assessment/policies` — kebijakan pelaporan + simpan versi baru.
- `assessment/summative` & `assessment/summative_detail` — pengolahan nilai sumatif + validasi guru.

Navigasi sidebar & mobile: grup **"Penilaian & Mastery"** di layout admin.

## 10. Pengujian

- `tests/database/AssessmentMasteryEngineTest.php` — 17 engine test: schema & RBAC seed, derivation status, end-to-end create, state machine + transisi tidak sah, larangan delete terpublikasi, gradebook → mastery & intervensi, mastery board matrix, set mastery manual + batch recommend, versioning reporting policy, diagnostic tidak menulis mastery/intervensi, intervensi menargetkan kriteria gagal, evidence menyimpan dimensi profil lulusan, **evidence menolak tipe tidak valid, gradebook menyimpan level rubrik, intervensi pengayaan manual, pengolahan sumatif + validasi/kunci/reopen**. **17/17 lulus.**
- `tests/Security/AssessmentRouteSecurityTest.php` — 14 route security test: guest → login, viewer 403, super admin 200, guru index 200, guru mastery 403, admin SMA mastery/interventions/policies 200, **admin SMA summative 200, guru dilarang membuat intervensi, admin SMA dapat membuat intervensi, viewer dilarang mengakses file bukti**, admin SMP dilarang membuka assessment SMA, guru tidak dapat membuka assessment guru lain, guru dapat membuka assessment sendiri, viewer tidak dapat meng-*grade*. **14/14 lulus.**
- Perintah: `php vendor/bin/phpunit tests/database/AssessmentMasteryEngineTest.php tests/Security/AssessmentRouteSecurityTest.php` (DB test: MySQL `wmvaa_akademia_ialos_final_test`, schema disinkron dari produksi + migrasi `summative_results`). Gabungan **31/31 lulus (115 assertion)**.

## 11. Catatan Integrasi

- Gradebook menulis mastery secara langsung (tanpa antrian); operasi batch transaksional.
- TP nasional (tanpa unit) tetap dihitung di Mastery Board selama mapelnya sesuai.
- Kebijakan pelaporan dan intervensi menjadi masukan untuk Phase 9 (Reporting & Portfolio).