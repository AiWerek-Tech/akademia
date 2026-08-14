# Database Schema Reference

## Core Tables (Milestone 0–2)
- `school_units`: `id`, `code`, `name`, `is_active`, `created_at`, `updated_at`.
- `academic_years`: `id`, `name`, `start_date`, `end_date`, `status`, `is_active`.
- `academic_periods`: `id`, `academic_year_id`, `semester_number`, `name`, `workflow_status`, `is_active`.
- `users`: `id`, `username`, `email`, `password_hash`, `full_name`, `is_active`.
- `teachers`: `id`, `nip`, `nik`, `full_name`, `email`, `phone`, `employment_status`, `is_active`.
- `subjects`: `id`, `code`, `name`, `category`, `is_active`.
- `grade_levels`: `id`, `unit_id`, `code`, `name`, `grade_number`, `is_active`.
- `classrooms`: `id`, `academic_period_id`, `unit_id`, `grade_level_id`, `code`, `name`, `is_active`.

## Curriculum & Workload Tables (Milestone 3–4)
- `curriculum_versions`: `id`, `uuid`, `academic_period_id`, `code`, `name`, `workflow_status`, `is_active`.
- `curriculum_structures`: `id`, `uuid`, `curriculum_version_id`, `unit_id`, `grade_level_id`, `subject_id`, `official_weekly_hours`, `custom_weekly_hours`, `effective_source`, `effective_weekly_hours`, `adjustment_reason`, `revision_number`.
- `workload_policies`: `id`, `academic_period_id`, `unit_id`, `minimum_teaching_hours`, `target_total_hours`, `maximum_total_hours`, `is_active`.

## Curriculum Planning Enhancement Table (Enhancement Migration)
- `curriculum_planning_settings`:
  - `id` (`BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
  - `curriculum_version_id` (`BIGINT UNSIGNED NOT NULL`, FK `curriculum_versions.id`)
  - `unit_id` (`BIGINT UNSIGNED NOT NULL`, FK `school_units.id`)
  - `workload_policy_id` (`BIGINT UNSIGNED NULLABLE`, FK `workload_policies.id`)
  - `teaching_days_per_week` (`TINYINT UNSIGNED DEFAULT 5`)
  - `selected_day_codes_json` (`TEXT NULLABLE`)
  - `daily_jp_capacity` (`DECIMAL(5,2) DEFAULT 9.00`)
  - `allow_custom_hours` (`TINYINT(1) DEFAULT 1`)
  - `notes` (`TEXT NULLABLE`)
  - `revision_number` (`INT UNSIGNED DEFAULT 1`)
  - `created_at`, `updated_at`, `created_by`, `updated_by`
  - Unique Key: `uq_curriculum_planning_scope` on `(curriculum_version_id, unit_id)`
# Pemilihan Mata Pelajaran Fase F

- `elective_periods`: periode unit/tahun/kurikulum, tingkat asal-tujuan, jendela
  pemilihan, batas perubahan, aturan 4-5 pilihan, dan status publikasi.
- `elective_offerings`: mapel pilihan yang tersedia, guru, JP, minimum peminat,
  kapasitas, serta informasi studi/karier.
- `elective_students`: peserta per unit dan tahun pelajaran, opsional terhubung
  dengan akun `users`.
- `student_elective_submissions`: profil minat dan status workflow pilihan.
- `student_elective_choices`: urutan pilihan utama/cadangan dan status alokasi.
- `student_elective_reviews`: keputusan BK dan kurikulum.
- `student_elective_change_requests`: usulan perubahan, alasan, keputusan, dan
  jejak penilaian ulang sekolah.

Seluruh tabel operasional memakai foreign key ke master kanonik. Penawaran
merujuk `subjects` dan `teachers`; periode merujuk `curriculum_versions`; siswa
dan submission dibatasi oleh unit serta tahun pelajaran.
