# Milestone 2 — Migration Results

**Date**: 2026-07-21
**Database Engine**: InnoDB
**Charset**: `utf8mb4_general_ci`
**Status**: ✅ **PASSED**

---

## Schema Verification

All 15 Milestone 2 tables are verified to exist with `InnoDB` engine and `utf8mb4_general_ci` collation. Primary keys, foreign keys, indexes, and unique constraints are fully configured.

### Table List & Details

| # | Table Name | Purpose | Primary Key | Foreign Keys / Unique / Indexes |
|---|------------|---------|-------------|--------------------------------|
| 1 | `teachers` | Master data guru | `id` (BIGINT) | Unique `uuid`, `employee_number`, `nip`, `nik` |
| 2 | `teacher_identifiers` | Kode sertifikasi/identitas | `id` (BIGINT) | FK `teacher_id` -> `teachers.id` |
| 3 | `teacher_qualifications` | Riwayat pendidikan/sertifikasi | `id` (BIGINT) | FK `teacher_id` -> `teachers.id` |
| 4 | `teacher_unit_assignments`| Penugasan unit sekolah | `id` (BIGINT) | FK `teacher_id` -> `teachers.id`, FK `unit_id` -> `school_units.id` |
| 5 | `duplicate_review_groups`| Grup review duplikasi guru | `id` (BIGINT) | Unique `uuid` |
| 6 | `duplicate_review_members`| Anggota review duplikasi | `id` (BIGINT) | FK `group_id` -> `duplicate_review_groups.id`, FK `teacher_id` -> `teachers.id` |
| 7 | `subjects` | Master mata pelajaran global | `id` (BIGINT) | Unique `uuid`, `code` |
| 8 | `subject_aliases` | Alias nama mata pelajaran | `id` (BIGINT) | FK `subject_id` -> `subjects.id` |
| 9 | `subject_unit_availability`| Ketersediaan mapel per unit | `id` (BIGINT) | FK `subject_id` -> `subjects.id`, FK `unit_id` -> `school_units.id` |
| 10| `grade_levels` | Tingkat kelas & fase (I-XII) | `id` (INT) | Unique `uuid`, FK `unit_id` -> `school_units.id` |
| 11| `classrooms` | Rombongan belajar (kelas) | `id` (INT) | Unique `uuid`, FK `unit_id` -> `school_units.id`, FK `academic_period_id` -> `academic_periods.id` |
| 12| `rooms` | Ruangan sekolah | `id` (BIGINT) | Unique `uuid`, `code`, FK `unit_id` -> `school_units.id`, FK `room_type_id` -> `room_types.id` |
| 13| `room_types` | Jenis ruangan sekolah | `id` (INT) | Unique `uuid`, `code` |
| 14| `master_import_batches` | Batch import master data | `id` (BIGINT) | Unique `uuid` |
| 15| `master_import_rows` | Baris data import di staging | `id` (BIGINT) | FK `batch_id` -> `master_import_batches.id` |

---

## Idempotency & Rollback Verification

1. **Rollback M2 Only**:
   - Command: `php spark migrate:rollback -g tests -b 2`
   - Result: Successful. Only M2 tables dropped, Milestone 1 tables (`users`, `roles`, `school_units`, etc.) remained intact.
2. **Reapply Migration**:
   - Command: `php spark migrate -g tests`
   - Result: Successful. Reapplied M2 schemas, restored tables cleanly.
