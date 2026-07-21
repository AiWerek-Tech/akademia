# Milestone 3 Migration & Seeder Results

This document records the verification of the database tables, unique constraints, and seeder behavior for Milestone 3.

## 1. Migration Overview
The migration is divided into two parts applied to target databases using the `InnoDB` engine and `utf8mb4` character set:
1. [20260721100000_CreateMilestone3Tables.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721100000_CreateMilestone3Tables.php)
2. [20260721200000_AddCurriculumScopeUniqueKeys.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721200000_AddCurriculumScopeUniqueKeys.php)

## 2. Table Verification
The migration creates six primary tables:
1. `curriculum_versions`
2. `curriculum_structures`
3. `curriculum_validation_results`
4. `curriculum_revision_history`
5. `curriculum_import_batches`
6. `curriculum_import_rows`

## 3. Concurrency-Safe Unique Constraints
To prevent race conditions on duplicate entries (Grade Defaults and Classroom Overrides), we defined stored generated columns with unique indexes on `curriculum_structures` inside the second migration (`AddCurriculumScopeUniqueKeys`):
- **`grade_default_key`**: `CASE WHEN classroom_id IS NULL THEN CONCAT(curriculum_version_id, '-', unit_id, '-', grade_level_id, '-', subject_id) ELSE NULL END` (Guarantees at most one default per subject/grade).
- **`classroom_override_key`**: `CASE WHEN classroom_id IS NOT NULL THEN CONCAT(curriculum_version_id, '-', classroom_id, '-', subject_id) ELSE NULL END` (Guarantees at most one override per subject/classroom).
- A UNIQUE constraint is applied on each generated column. This handles concurrent inserts cleanly at the database level.

## 4. Seeder Idempotency
We verified that [Milestone3CurriculumSeeder.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Seeds/Milestone3CurriculumSeeder.php):
- Seeds 9 curriculum permissions and maps them to role-permissions idempotently.
- Resolves the feature flag `curriculum` to enabled by inserting it if missing, or updating it if present.
- Does NOT write mock or duplicate data into operational tables (`curriculum_versions`, `curriculum_structures`, etc.).
- Execution is 100% repeatable without duplicate key violations.
