# Cocurricular & Character Engine — Dokumentasi Implementasi

Phase 7 menyediakan siklus **program kokurikuler yang menguatkan profil lulusan**: dari perencanaan program, pemetaan lintas-disiplin, eksekusi sesi, asesmen formatif & sumatif, laporan per siswa, hingga evaluasi model **INPUT → PROCESS → OUTPUT → OUTCOME** — sebagai pelengkap siklus pembelajaran harian (Phase 4–6) dan penunjang rapor/portfolio (Phase 9).

## 1. Model Data

Migrasi `20260823000000_CreatePhase7CocurricularTables.php` membuat **15 tabel**. Seluruh tabel memakai fondasi `baseFields()` (id, uuid) + `auditFields()` (created_by/updated_by/created_at/updated_at) dari Phase 1, dan migrasi idempotent (aman dijalankan ulang). Migrasi juga melakukan seeding RBAC (`cocurricular.view`, `cocurricular.manage`) dan feature flag `ialos_phase7_cocurricular`/`ialos_7kahi` jika belum ada.

| Tabel | Fungsi |
|---|---|
| `cocurricular_programs` | Induk program (unit, periode, kode, judul, `program_type`, tema, rasional, tujuan, `annual_minutes`, `delivery_model`, rentang tanggal, status `DRAFT`/`ACTIVE`/`COMPLETED`/`ARCHIVED`). |
| `cocurricular_program_dimensions` | Junction program → Dimensi Profil Lulusan (`graduate_profile_dimensions`), unique `(program_id, dimension_id)`. |
| `cocurricular_program_subjects` | Junction program → mapel (`subjects`). |
| `cocurricular_program_objectives` | Junction program → Tujuan Pembelajaran (`learning_objectives_tp`). |
| `cocurricular_program_teachers` | Junction program → guru (`teachers`) dengan peran (`FACILITATOR`, dll.), unique `(program_id, teacher_id, role)`. |
| `cocurricular_program_classes` | Junction program → kelas (`classrooms`). |
| `cocurricular_program_partners` | Mitra program (nama, jenis, peran, kontak) — tanpa duplikasi konten. |
| `cocurricular_program_resources` | Sumber daya program (nama, jenis, jumlah, catatan). |
| `cocurricular_sessions` | Sesi terjadwal per program: kelas, guru, tanggal, waktu, mode (`BLOCK`/`WEEKLY`/`PROJECT`/`MIXED`), status `PLAN`/`EXECUTED`/`CANCELLED`, `executed_at`. |
| `cocurricular_observations` | Asesmen formatif per siswa: tipe (`OBSERVATION`/`JOURNAL`/`PEER_FEEDBACK`/`SELF_ASSESSMENT`/`REFLECTION`), dimensi, catatan, rating, tanggal. |
| `cocurricular_evidences` | Bukti sumatif per siswa: tipe (`PERFORMANCE`/`PROJECT`/`ACTION`/`PRODUCT`/`PRESENTATION`/`FINAL_REFLECTION`), dimensi (nullable), `file_path`, `meta_json` (nama asli, ukuran, MIME), `captured_at`. |
| `cocurricular_student_results` | Hasil akhir murid × dimensi: level `EMERGING`/`DEVELOPING`/`PROFICIENT`/`EXEMPLARY` + catatan, unique `(program_id, student_id, dimension_id)`. |
| `cocurricular_evaluations` | Evaluasi IPOO per program: aspek `INPUT`/`PROCESS`/`OUTPUT`/`OUTCOME`, indikator, temuan, rating 1–5. |
| `cocurricular_habits` | Definisi kebiasaan 7KAIH per unit (kode, nama, deskripsi, ikon, tantangan mingguan, urutan, `enabled`), unique `(code, unit_id)`. |
| `cocurricular_habit_checkins` | Diary/check-in mingguan murid: status `DONE`/`PARTIAL`/`SKIP`, catatan guru & catatan orang tua, unique `(habit_id, student_id, checkin_week)`. |

## 2. Workflow Program & Transisi Status

`CocurricularService::transition($id, $unitId, $targetStatus, $userId)` memvalidasi state machine:

```
DRAFT ──────► ACTIVE ──────► COMPLETED
  │             │  │            │
  └── ARCHIVED ◄──┘  └── ARCHIVED ◄──┘
ARCHIVED ─────► DRAFT (reaktivasi)
```

- Transisi tidak sah ditolak (`InvalidArgumentException`).
- `deleteProgram()` hanya mengizinkan penghapusan program yang masih `DRAFT`; program yang sudah `ACTIVE`/berjalan ditolak.
- Setiap transisi/mutasi utama dicatat melalui `AuditService::log('cocurricular', ...)`.

## 3. Junction Lintas-Disiplin & Sinkronisasi

`syncJunctions()` menyinkronkan lima junction sekaligus: dimensi profil lulusan, mapel, TP, guru, dan kelas. Pola **sync (selaras, bukan timpa)**:

- Data yang dikirim dari form (mis. `dimension_ids[]`) dibandingkan dengan kondisi DB.
- Junction yang tidak lagi dipilih dihapus; yang baru ditambahkan; yang sama dibiarkan — sehingga alignment lama tidak hilang jika form parsial.

## 4. Eksekusi Sesi

- `addSession()`/`updateSession()`/`deleteSession()` mengelola jadwal sesi per program.
- `executeSession()` memindahkan sesi `PLAN` → `EXECUTED` dan mengisi `executed_at` (waktu aktual).
- `cancelSession()` menandai sesi `CANCELLED`.
- Sesi hanya dapat diubah dalam lingkup unit program (`assertProgramInUnit`).

## 5. Asesmen Formatif & Sumatif

**Formatif (observasi):** `addObservation()` mencatat observasi/jurnal/peer feedback/self assessment/refleksi per siswa, dapat dialign ke dimensi profil lulusan, dengan rating opsional 1–5.

**Sumatif (bukti):** `addEvidence()` menerima tipe bukti sesuai `EVIDENCE_TYPES` (6 jenis blueprint §7), deskripsi, dimensi, dan file opsional (disimpan dengan `meta_json`). Download bukti dilayani route terproteksi `GET cocurricular/evidence-file/(:num)` (permission `cocurricular.view` + pemeriksaan unit program).

## 6. Hasil Siswa & Laporan

- `saveResults()` melakukan **upsert** per murid × dimensi (`cocurricular_student_results`) dengan level + catatan.
- `report($programId)` menyusun matriks murid × dimensi, jumlah bukti & observasi per murid, serta **distribusi level per dimensi** (`EMERGING`/`DEVELOPING`/`PROFICIENT`/`EXEMPLARY`).
- `generateStudentNarrative($programId, $studentId)` menyusun narasi otomatis berbahasa Indonesia dari hasil per dimensi, kekuatan (`SAB`/`BSH`), area pengembangan, dan jumlah bukti — siap dipakai di rapor/portfolio (Phase 9).

## 7. Evaluasi IPOO

`calculateIpooHealth($programId)` menghitung skor kualitas per aspek:

- Rata-rata rating per aspek (0–5) → persen (0–100).
- **Overall score** = rata-rata keempat aspek yang aktif.
- **Status kesehatan:** `SANGAT SEHAT` (≥85), `BAIK & SESUAI TARGET` (≥70), `CUKUP` (≥55), `PERLU INTERVENSI` (<55).

Menjadi dasar tindak lanjut perbaikan program sesuai model evaluasi blueprint.

## 8. 7KAIH (opsional, feature-flagged)

- **Feature flag `ialos_7kahi`** mengaktifkan/menonaktifkan fitur; kontroller memeriksa ulang flag (`CocurricularController::require7Kaih()`) selain filter route.
- `habits()`/`saveHabit()`/`updateHabit()`/`deleteHabit()` mengelola definisi kebiasaan + tantangan mingguan per unit.
- `checkins()`/`saveCheckins()` mengelola diary mingguan murid (status `DONE`/`PARTIAL`/`SKIP`, catatan guru & orang tua), difilter per kelas/kebiasaan.
- Unique constraint mencegah double check-in pada minggu yang sama.

## 9. RBAC

- **Permissions:** `cocurricular.view` (baca daftar/detail/laporan) dan `cocurricular.manage` (kelola program, sesi, observasi, bukti, hasil, evaluasi, kebiasaan, check-in).
- **Role map migrasi:** `super_admin`, `wakasek_kurikulum`, `admin_smp`, `admin_sma` mendapat seluruh permission; `kepala_sekolah` hanya `cocurricular.view`.
- **RolePermissions runtime:** `GURU` dan `WALI_KELAS` juga memperoleh `cocurricular.view` + `cocurricular.manage` (guru sebagai fasilitator program).
- Seluruh route Phase 7 membawa filter `permission:cocurricular.view` / `permission:cocurricular.manage` di samping filter autentikasi & unit access global.

## 10. Pengujian

- **`tests/database/CocurricularEngineTest.php`** (19 test): program + junction, transisi, sesi, observasi, bukti, hasil (upsert per murid × dimensi), laporan & distribusi, check-in 7KAIH, narasi, IPOO, dan verifikasi permission/flag tersedia.
- **`tests/Security/CocurricularRouteSecurityTest.php`** (10 test): matriks akses tiap route untuk guest, guru tanpa manage, dan pemegang permission; perilaku flag `ialos_7kahi` (off → route 7KAIH ditolak, on → dapat diakses).
- **`tests/unit/RolePermissionsTest.php`**: kumpulan permission peran konsisten (termasuk Phase 7).

Hasil: **33 test, 131 assertions — 100% PASSED** terhadap baseline test DB yang tersinkronisasi.