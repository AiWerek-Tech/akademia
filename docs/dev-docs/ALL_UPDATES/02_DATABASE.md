# WMVAA Akademia — Complete Database Schema Reference

## 1. Schema Overview

Database menggunakan MySQL/MariaDB dengan MySQLi driver. Total migration: **60+ file**.

### 1.1 Naming Convention

- Tabel menggunakan **snake_case** (contoh: `school_units`, `academic_periods`)
- Kolom ID menggunakan `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`
- Kolom UUID menggunakan `CHAR(36)` dengan UUID v4
- Foreign key: `table_id` (contoh: `unit_id`, `academic_period_id`)
- Timestamps: `created_at`, `updated_at` (`TIMESTAMP` atau `DATETIME`)
- Soft delete: `deleted_at` (`TIMESTAMP NULLABLE`)
- Audit: `created_by`, `updated_by` (`BIGINT UNSIGNED NULLABLE`)
- Workflow: `workflow_status` (`VARCHAR(50)`)

---

## 2. Core Tables (Milestone 0–1)

### 2.1 `school_units`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `code` | VARCHAR(20) UNIQUE | Kode unit (SMP, SMA) |
| `name` | VARCHAR(100) | Nama unit |
| `is_active` | TINYINT(1) DEFAULT 1 | Status aktif |
| `created_at` | TIMESTAMP | Waktu dibuat |
| `updated_at` | TIMESTAMP | Waktu diperbarui |
| `deleted_at` | TIMESTAMP NULLABLE | Soft delete |

### 2.2 `academic_years`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `name` | VARCHAR(50) | Nama tahun akademik (2026/2027) |
| `start_date` | DATE | Tanggal mulai |
| `end_date` | DATE | Tanggal akhir |
| `status` | VARCHAR(20) | draft/active/archived |
| `is_active` | TINYINT(1) | Status aktif |

### 2.3 `academic_periods`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `academic_year_id` | BIGINT FK → academic_years | Tahun akademik |
| `semester_number` | TINYINT | 1 (Ganjil) / 2 (Genap) |
| `name` | VARCHAR(100) | Nama periode |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `is_active` | TINYINT(1) | Status aktif |

### 2.4 `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `username` | VARCHAR(50) UNIQUE | Username login |
| `email` | VARCHAR(100) UNIQUE | Email |
| `password_hash` | VARCHAR(255) | Password bcrypt hash |
| `full_name` | VARCHAR(100) | Nama lengkap |
| `is_active` | TINYINT(1) | Status aktif |
| `deleted_at` | TIMESTAMP NULLABLE | Soft delete |
| `must_change_password` | TINYINT(1) | Wajib ganti password |
| `must_change_username` | TINYINT(1) | Wajib ganti username |
| `password_changed_at` | TIMESTAMP NULLABLE | Terakhir ganti password |

### 2.5 `roles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `code` | VARCHAR(50) UNIQUE | Kode role (super_admin, guru, dll) |
| `name` | VARCHAR(100) | Nama role |

### 2.6 `user_roles`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `user_id` | BIGINT FK → users | ID pengguna |
| `role_id` | BIGINT FK → roles | ID role |

### 2.7 `user_unit_access`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `user_id` | BIGINT FK → users | ID pengguna |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `is_default` | TINYINT(1) | Unit default |

### 2.8 `permissions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `code` | VARCHAR(100) UNIQUE | Kode permission (module.action) |
| `name` | VARCHAR(100) | Nama permission |

### 2.9 `role_permissions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `role_id` | BIGINT FK → roles | ID role |
| `permission_id` | BIGINT FK → permissions | ID permission |

### 2.10 `login_attempts`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `user_id` | BIGINT FK → users | ID pengguna |
| `ip_address` | VARCHAR(45) | IP address |
| `user_agent` | TEXT | User agent string |
| `attempted_at` | TIMESTAMP | Waktu percobaan |
| `success` | TINYINT(1) | Berhasil/gagal |

---

## 3. Master Data Tables (Milestone 2)

### 3.1 `teachers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `nip` | VARCHAR(30) UNIQUE | Nomor Induk Pegawai |
| `nik` | VARCHAR(20) | Nomor Induk Kependudukan |
| `full_name` | VARCHAR(100) | Nama lengkap |
| `email` | VARCHAR(100) | Email |
| `phone` | VARCHAR(20) | Nomor telepon |
| `employment_status` | VARCHAR(30) | Status kepegawaian |
| `is_active` | TINYINT(1) | Status aktif |

### 3.2 `teacher_identifiers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `teacher_id` | BIGINT FK → teachers | ID guru |
| `identifier_type` | VARCHAR(50) | Tipe identifier |
| `identifier_value` | VARCHAR(100) | Nilai identifier |

### 3.3 `teacher_qualifications`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `teacher_id` | BIGINT FK → teachers | ID guru |
| `qualification` | VARCHAR(100) | Kualifikasi |
| `institution` | VARCHAR(100) | Institusi |
| `year_obtained` | YEAR | Tahun perolehan |

### 3.4 `subjects`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `code` | VARCHAR(20) UNIQUE | Kode mapel |
| `name` | VARCHAR(100) | Nama mapel |
| `category` | VARCHAR(50) | Kategori (umum, khusus, lokal) |
| `is_active` | TINYINT(1) | Status aktif |

### 3.5 `subject_aliases`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `subject_id` | BIGINT FK → subjects | ID mapel |
| `alias` | VARCHAR(100) | Nama alias |

### 3.6 `grade_levels`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `code` | VARCHAR(20) | Kode tingkat |
| `name` | VARCHAR(50) | Nama tingkat |
| `grade_number` | TINYINT | Nomor grade |
| `is_active` | TINYINT(1) | Status aktif |

### 3.7 `classrooms`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `academic_period_id` | BIGINT FK → academic_periods | Periode akademik |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `grade_level_id` | BIGINT FK → grade_levels | ID tingkat |
| `code` | VARCHAR(20) | Kode rombel |
| `name` | VARCHAR(50) | Nama rombel |
| `is_active` | TINYINT(1) | Status aktif |

### 3.8 `rooms`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `code` | VARCHAR(20) | Kode ruang |
| `name` | VARCHAR(50) | Nama ruang |
| `capacity` | INT | Kapasitas |
| `room_type_id` | BIGINT FK → room_types | Tipe ruang |

### 3.9 `room_types`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `name` | VARCHAR(50) | Nama tipe |

### 3.10 `students` (via master import)
Tabel siswa dikelola melalui import staging pipeline dengan tabel `students` sebagai target utama.

---

## 4. Curriculum Tables (Milestone 3)

### 4.1 `curriculum_versions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID v4 |
| `academic_period_id` | BIGINT FK → academic_periods | Periode akademik |
| `code` | VARCHAR(50) | Kode versi |
| `name` | VARCHAR(100) | Nama versi |
| `workflow_status` | VARCHAR(50) | draft/validated/reviewed/approved/locked |
| `is_active` | TINYINT(1) | Status aktif |

### 4.2 `curriculum_structures`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID v4 |
| `curriculum_version_id` | BIGINT FK → curriculum_versions | Versi kurikulum |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `grade_level_id` | BIGINT FK → grade_levels | ID tingkat |
| `subject_id` | BIGINT FK → subjects | ID mapel |
| `official_weekly_hours` | DECIMAL(4,2) | JP resmi per minggu |
| `custom_weekly_hours` | DECIMAL(4,2) NULLABLE | JP custom per minggu |
| `effective_source` | ENUM('official','custom') | Sumber jam efektif |
| `effective_weekly_hours` | DECIMAL(4,2) | Jam efektif per minggu |
| `adjustment_reason` | TEXT NULLABLE | Alasan penyesuaian |
| `revision_number` | INT UNSIGNED | Nomor revisi |

### 4.3 `curriculum_planning_settings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `curriculum_version_id` | BIGINT FK → curriculum_versions | Versi kurikulum |
| `unit_id` | BIGINT FK → school_units | ID unit |
| `workload_policy_id` | BIGINT FK → workload_policies | Kebijakan beban kerja |
| `teaching_days_per_week` | TINYINT UNSIGNED DEFAULT 5 | Hari mengajar per minggu |
| `selected_day_codes_json` | TEXT NULLABLE | JSON hari yang dipilih |
| `daily_jp_capacity` | DECIMAL(5,2) DEFAULT 9.00 | Kapasitas JP per hari |
| `allow_custom_hours` | TINYINT(1) DEFAULT 1 | Izinkan jam custom |
| `notes` | TEXT NULLABLE | Catatan |
| `revision_number` | INT UNSIGNED DEFAULT 1 | Nomor revisi |
| Unique: `uq_curriculum_planning_scope` | (curriculum_version_id, unit_id) | |

### 4.4 `curriculum_elements`
Tabel elemen kurikulum yang terhubung dengan learning outcomes.

---

## 5. Education Foundation Tables (IALOS)

### 5.1 `education_foundations`
Kontrol center utama IALOS Education.

### 5.2 `regulations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `title` | VARCHAR(255) | Judul regulasi |
| `document_number` | VARCHAR(100) | Nomor dokumen |
| `description` | TEXT | Deskripsi |
| `category` | VARCHAR(50) | Kategori |

### 5.3 `regulation_versions`
Versi dari setiap regulasi.

### 5.4 `curriculum_sources`
Sumber-sumber kurikulum yang digunakan.

### 5.5 `graduate_profile_dimensions`
Dimensi profil lulusan.

### 5.6 `learning_outcomes`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID |
| `code` | VARCHAR(50) | Kode CP |
| `description` | TEXT | Deskripsi capaian |
| `grade_level_id` | BIGINT FK → grade_levels | Tingkat |
| `subject_id` | BIGINT FK → subjects | Mapel |

### 5.7 `curriculum_elements` (elements of learning outcomes)

### 5.8 `learning_objectives`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID |
| `code` | VARCHAR(50) | Kode TP |
| `description` | TEXT | Deskripsi tujuan pembelajaran |
| `learning_outcome_id` | BIGINT FK → learning_outcomes | CP induk |
| `subject_id` | BIGINT FK → subjects | Mapel |

### 5.9 `objective_criteria`
Kriteria penilaian untuk setiap tujuan pembelajaran.

### 5.10 `learning_sequences` (ATP - Alur Tujuan Pembelajaran)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID |
| `code` | VARCHAR(50) | Kode ATP |
| `title` | VARCHAR(255) | Judul |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `grade_level_id` | BIGINT FK | Tingkat |
| `subject_id` | BIGINT FK | Mapel |

### 5.11 `learning_sequence_items`
Item-item dalam satu ATP.

### 5.12 `learning_outcome_elements`
Relasi antara learning outcomes dan elemen kurikulum.

---

## 6. Subject Learning Pack Tables (Phase 3)

### 6.1 `subject_learning_packs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID |
| `subject_id` | BIGINT FK → subjects | Mapel |
| `grade_level_id` | BIGINT FK → grade_levels | Tingkat |
| `code` | VARCHAR(50) | Kode paket |
| `title` | VARCHAR(255) | Judul paket |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `version_number` | INT UNSIGNED | Nomor versi |

### 6.2 `learning_session` (units dalam paket)
### 6.3 `learning_session_activity` (aktivitas dalam sesi)
### 6.4 `activity_resource` (resources untuk aktivitas)
### 6.5 `assessment_item` (asesmen dalam paket)
### 6.6 `learning_session_observation` (observasi)
### 6.7 `learning_session_reflection` (refleksi)
### 6.8 `intervention` (intervensi)
### 6.9 `mastery_record` (record mastery)

---

## 7. Lesson Plan Tables (Phase 4)

### 7.1 `lesson_plans`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `uuid` | CHAR(36) UNIQUE | UUID |
| `teacher_id` | BIGINT FK → teachers | Guru |
| `subject_id` | BIGINT FK → subjects | Mapel |
| `classroom_id` | BIGINT FK → classrooms | Rombel |
| `workflow_status` | VARCHAR(50) | Status workflow |
| `design_notes` | TEXT | Catatan desain |

### 7.2 Terkait: objective, stage, activity, assessment, rubric tables

---

## 8. Teaching Workspace Tables (Phase 5)

### 8.1 `learning_session` (sesi mengajar harian)

---

## 9. Assessment & Mastery Tables (Phase 6)

### 9.1 `assessments`
### 9.2 `assessment_items`
### 9.3 `assessment_criterion`
### 9.4 `assessment_attempt` (percobaan asesmen siswa)
### 9.5 `assessment_evidence` (bukti asesmen)
### 9.6 `assessment_feedback` (feedback asesmen)
### 9.7 `assessment_objective` (hubungan asesmen-TP)
### 9.8 `criterion_result` (hasil kriteria)
### 9.9 `mastery_record` (record mastery TP)
### 9.10 `intervention` (intervensi belajar)
### 9.11 `student_narrative_drafts` (draf narasi siswa)
### 9.12 `reporting_policies` (kebijakan pelaporan)
### 9.13 `summative_results` (hasil pengolahan sumatif)

---

## 10. Cocurricular Tables (Phase 7)

### 10.1 `cocurricular_programs`
### 10.2 `cocurricular_sessions`
### 10.3 `cocurricular_observations`
### 10.4 `cocurricular_evidences`
### 10.5 `cocurricular_evaluations`
### 10.6 `cocurricular_habits` (7KAIH)
### 10.7 `cocurricular_checkins`

---

## 11. Extracurricular Tables (Phase 8)

### 11.1 `extracurricular_programs`
### 11.2 `extracurricular_members`
### 11.3 `extracurricular_sessions`
### 11.4 `extracurricular_attendance`
### 11.5 `extracurricular_competencies`
### 11.6 `extracurricular_evaluations`

---

## 12. Reporting Tables (Phase 9)

### 12.1 `reporting_records`
### 12.2 `reporting_narratives`
### 12.3 `reporting_portfolios`
### 12.4 `reporting_promotions`

---

## 13. Quality & AI Tables (Phase 10)

### 13.1 `teacher_reflections` (refleksi guru)
### 13.2 `supervision_records` (catatan supervisi)
### 13.3 `ksp_evaluation_records` (evaluasi KSP)
### 13.4 `ai_copilot_drafts` (draf AI)
### 13.5 `quality_report_records`

---

## 14. Scheduling Tables (Milestone 5)

### 14.1 `schedule_versions`
### 14.2 `schedule_entries`
### 14.3 `schedule_days`
### 14.4 `schedule_day_slots`
### 14.5 `schedule_slot_templates`
### 14.6 `schedule_slot_template_items`
### 14.7 `schedule_conflicts`
### 14.8 `schedule_locks`
### 14.9 `schedule_requirements`
### 14.10 `schedule_fixed_activities`
### 14.11 `schedule_exceptions`
### 14.12 `schedule_import_batches`
### 14.13 `schedule_import_rows`
### 14.14 `schedule_generation_runs`
### 14.15 `schedule_generation_candidates`
### 14.16 `schedule_candidate_entries`
### 14.17 `scheduling_constraints`
### 14.18 `classroom_availability_rules`
### 14.19 `room_availability_rules`
### 14.20 `teacher_availability_rules`

---

## 15. Assignment & Workload Tables (Milestone 4)

### 15.1 `teaching_assignments`
### 15.2 `teaching_assignment_groups`
### 15.3 `teacher_unit_assignments`
### 15.4 `teacher_workload_snapshots`
### 15.5 `workload_policies`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `academic_period_id` | BIGINT FK | Periode akademik |
| `unit_id` | BIGINT FK | ID unit |
| `minimum_teaching_hours` | DECIMAL | JP minimum mengajar |
| `target_total_hours` | DECIMAL | JP target |
| `maximum_total_hours` | DECIMAL | JP maksimum |
| `is_active` | TINYINT(1) | Status aktif |

### 15.6 `assignment_versions`
### 15.7 `assignment_import_batches`
### 15.8 `assignment_import_rows`
### 15.9 `assignment_revision_history`
### 15.10 `assignment_validation_results`
### 15.11 `additional_duty_types`
### 15.12 `teacher_additional_duties`

---

## 16. Elective Selection Tables

### 16.1 `elective_periods`
### 16.2 `elective_offerings`
### 16.3 `elective_students`
### 16.4 `student_elective_submissions`
### 16.5 `student_elective_choices`
### 16.6 `student_elective_reviews`
### 16.7 `student_elective_change_requests`

---

## 17. Attendance Tables

### 17.1 `attendance_sessions`
### 17.2 `student_attendances`
### 17.3 `teacher_schedule_substitutions`
### 17.4 `teacher_substitution_repair_candidates`

---

## 18. Academic Calendar Tables

### 18.1 `academic_calendars`
### 18.2 `academic_calendar_days`
### 18.3 `academic_calendar_events`
### 18.4 `academic_calendar_rules`

---

## 19. Duty Schedule Tables

### 19.1 `teacher_duty_schedules`

---

## 20. Routine Activity Tables

### 20.1 `routine_activities`
Kegiatan rutin sekolah dengan placement zone, teaching load, dan slot waktu.

---

## 21. System Tables

### 21.1 `system_settings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `group_key` | VARCHAR(50) | Grup pengaturan |
| `setting_key` | VARCHAR(50) | Kunci pengaturan |
| `setting_value` | TEXT | Nilai pengaturan |

### 21.2 `audit_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `user_id` | BIGINT FK → users | ID pengguna |
| `username` | VARCHAR(50) | Username |
| `module` | VARCHAR(50) | Modul |
| `action` | VARCHAR(50) | Aksi |
| `entity_type` | VARCHAR(50) | Tipe entitas |
| `entity_id` | BIGINT | ID entitas |
| `before_state` | JSON | State sebelum |
| `after_state` | JSON | State sesudah |
| `reason` | TEXT | Alasan |
| `ip_address` | VARCHAR(45) | IP address |
| `created_at` | TIMESTAMP | Waktu |

### 21.3 `feature_flags`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AUTO | ID unik |
| `code` | VARCHAR(50) UNIQUE | Kode flag |
| `enabled` | TINYINT(1) | Status aktif |
| `updated_at` | TIMESTAMP | Waktu update |
| `updated_by` | BIGINT FK → users | ID pengubah |

### 21.4 `mobile_sync_sessions` (Phase 11)
### 21.5 `mobile_sync_versions` (Phase 11)
### 21.6 `mobile_sync_files` (Phase 11)

---

## 22. Student Attendance Tables

### 22.1 `student_attendance_sessions`
### 22.2 `student_attendance_records`

---

## 23. Relationship Summary

```
school_units ──┬── grade_levels ──── classrooms ──┬── student_attendances
               │                                   │
               ├── teachers ──────── teacher_unit_assignments
               │       │
               │       ├── teaching_assignments ── teaching_assignment_groups
               │       ├── teacher_workload_snapshots
               │       ├── teacher_availability_rules
               │       ├── teacher_additional_duties
               │       ├── teacher_schedule_substitutions
               │       └── teacher_duty_schedules
               │
               ├── rooms ──── room_availability_rules
               │
               ├── curriculum_structures
               ├── curriculum_planning_settings
               ├── schedule_entries
               └── workload_policies

academic_years ── academic_periods ──┬── curriculum_versions
                                    ├── classrooms
                                    ├── schedule_versions
                                    ├── assignment_versions
                                    └── workload_policies

subjects ──────────────────────────┬── curriculum_structures
                                   ├── teaching_assignments
                                   ├── subject_learning_packs
                                   ├── learning_objectives
                                   ├── learning_outcomes
                                   └── lesson_plans

users ──── user_roles ──── roles ──── role_permissions ──── permissions
    └── user_unit_access ──── school_units
```
