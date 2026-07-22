# Milestone 4 Database Migration & Schema Verification Results

**Date**: 2026-07-22  
**Database Engine**: InnoDB  
**Charset & Collation**: `utf8mb4_general_ci` / `utf8mb4_unicode_ci`  
**Migration File**: `20260722100000_CreateMilestone4Tables.php`  

---

## 1. Migration Execution & Rollback Cycle

Verification performed on a fresh/disposable database:

1. **Migrate M0–M4**: `spark migrate` — Success (11 new tables created).
2. **Seed M0–M4**: `spark db:seed Milestone4Seeder` — Success (Permissions, additional duty types inserted).
3. **Re-seed Idempotency**: Running `Milestone4Seeder` again — Success (0 duplicate key errors, clean update).
4. **Rollback M4 Only**: `spark migrate:rollback` — Success (All 11 M4 tables dropped in reverse order).
5. **Verify M0–M3 Tables**: Master data tables (`users`, `academic_years`, `classrooms`, `curriculum_structures`, etc.) remain completely intact and functional.
6. **Re-Migrate M4**: `spark migrate` — Recreated all 11 tables cleanly.

---

## 2. Table Schema & Foreign Key Verification

| Table Name | Engine | Foreign Keys & Constraints | Indexes | Idempotency |
| :--- | :---: | :--- | :--- | :---: |
| `assignment_versions` | InnoDB | `academic_period_id`, `curriculum_version_id` | `idx_period_unit` | Passed |
| `teaching_assignment_groups` | InnoDB | `assignment_version_id`, `classroom_id`, `grade_level_id` | `idx_version_class` | Passed |
| `teaching_assignments` | InnoDB | `assignment_version_id`, `curriculum_structure_id`, `teacher_id` | `idx_version_teacher` | Passed |
| `additional_duty_types` | InnoDB | Unique `code` | `idx_duty_code` | Passed |
| `teacher_additional_duties` | InnoDB | `assignment_version_id`, `teacher_id`, `duty_type_id` | `idx_version_duty` | Passed |
| `workload_policies` | InnoDB | `academic_period_id`, `unit_id` | `idx_period_unit_pol` | Passed |
| `teacher_workload_snapshots` | InnoDB | `assignment_version_id`, `teacher_id` | `idx_version_snap` | Passed |
| `assignment_validation_results` | InnoDB | `assignment_version_id` | `idx_version_val` | Passed |
| `assignment_revision_history` | InnoDB | `assignment_version_id` | `idx_version_rev` | Passed |
| `assignment_import_batches` | InnoDB | `academic_period_id`, `unit_id`, `user_id` | `idx_batch_user` | Passed |
| `assignment_import_rows` | InnoDB | `batch_id` | `idx_row_batch` | Passed |

---

## 3. Data Integrity & Types

- **UUID Columns**: `VARCHAR(36)` indexed for fast lookup across all entities.
- **Foreign Keys**: Strictly typed `INT UNSIGNED` matching primary keys. `ON DELETE RESTRICT` / `CASCADE` where appropriate.
- **Timestamps**: Standard `created_at`, `updated_at` timestamps on all tables.
