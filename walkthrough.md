# Curriculum Planning Enhancement — Walkthrough Report

## Accomplished Work Summary

### 1. Planning Settings Architecture & Database Migration
- Refactored `20260724000000_CreateCurriculumPlanningSettings.php` to include:
  - `workload_policy_id` (`BIGINT UNSIGNED NULLABLE`, FK `workload_policies.id` ON DELETE SET NULL)
  - `selected_day_codes_json` (`TEXT NULLABLE`)
  - `revision_number` (`INT UNSIGNED DEFAULT 1`)
  - Safe `down()` implementation with `try/finally` foreign key check restoration.
- Updated `CurriculumPlanningSettingModel.php` allowed fields.

### 2. Five-Day / Day Codes Configuration
- Implemented `selected_day_codes` handling in `CurriculumPlanningService.php` (`['MON', 'TUE', 'WED', 'THU', 'FRI']` for SMP, `['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']` for SMA).
- Implemented strict day code validation: count must match `teaching_days_per_week`, duplicate codes rejected, invalid day codes rejected.

### 3. Workload Policy Single Source of Truth & FTE Calculation
- Linked workload thresholds (`minimum_teaching_hours`, `target_total_hours`, `maximum_total_hours`) directly to Milestone 4 `workload_policies` table via `resolveWorkloadPolicy()`.
- Explicitly labeled teacher capacity metrics as **Estimasi Kebutuhan Guru (FTE)** using `target_total_hours` denominator (with fallback minimum load label `estimated_fte_at_minimum_load`).

### 4. Route Security & Concurrency Guardrails
- Created dedicated route security test suite [`tests/Security/CurriculumPlanningRouteSecurityTest.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/Security/CurriculumPlanningRouteSecurityTest.php).
- Verified guest redirects, 403 authorization checks, cross-unit boundary isolation (Admin SMP vs Admin SMA), and CSRF token protection on POST routes.
- Enforced version immutability (`LOCKED`, `APPROVED`, `ARCHIVED`) and optimistic concurrency control (`revision_number`).

### 5. Automated Tests & Privacy
- Enhanced [`tests/database/CurriculumPlanningServiceTest.php`](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/CurriculumPlanningServiceTest.php) with comprehensive test cases.
- Validated Composer compliance (`composer validate --strict` and `composer audit`).
- Confirmed privacy checks: 0 `.xlsx`, `.xls`, `.pdf`, `.csv` or teacher personal data files tracked in version control.
