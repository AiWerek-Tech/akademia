# Implementation Plan — Milestone 3 Final Acceptance & Security Hardening

This plan outlines the steps to resolve route security gaps, audit test discovery, verify runtime consistency, enforce effective source constraints, guarantee migration concurrency-safety, audit seeders, and perform a complete walkthrough verification of the entire test suite.

## User Review Required

> [!IMPORTANT]
> - We will enforce `auth`, `password_change_required`, and `unit_access` filters on all administrative routes including `/teachers`, `/subjects`, `/grade-levels`, `/classrooms`, `/rooms`, `/imports/master`, `/duplicates`, `/users`, `/roles`, `/academic-years`, `/academic-periods`, and `/curriculum`.
> - The old guest redirect test in `Milestone2AcceptanceTest` will be updated to expect a redirect to `/login` instead of `/dashboard` because the guest should never bypass route filters and reach the controller.

## Proposed Changes

### Configuration

#### [MODIFY] [Filters.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Filters.php)
- Update `auth`, `unit_access`, and `password_change_required` filter definitions to strictly include the administrative routes list:
  ```php
  'teachers', 'teachers/*',
  'subjects', 'subjects/*',
  'grade-levels', 'grade-levels/*',
  'classrooms', 'classrooms/*',
  'rooms', 'rooms/*',
  'imports/master', 'imports/master/*',
  'duplicates', 'duplicates/*',
  ```

### Database Migration & Seeder

#### [MODIFY] [20260721100000_CreateMilestone3Tables.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721100000_CreateMilestone3Tables.php)
- Add generated columns `grade_default_key` and `classroom_override_key` to table `curriculum_structures`.
- Define unique constraints `uq_grade_default` and `uq_classroom_override` on these generated columns respectively, to make duplicate scopes 100% concurrency-safe at the database level.

#### [MODIFY] [Milestone3CurriculumSeeder.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Seeds/Milestone3CurriculumSeeder.php)
- Update feature flag seeding logic to perform an insert if the flag is missing, ensuring complete seeder idempotency.

### Acceptance & Security Tests

#### [MODIFY] [Milestone2AcceptanceTest.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/Milestone2AcceptanceTest.php)
- Modify `testM01_NoSessionRedirectsToLogin` to assert redirect to `/login`.
- Add `testM03_TeachersGuestControllerNotExecuted` to verify the controller method is bypassed completely when accessed by a guest.

#### [MODIFY] [Milestone3AcceptanceTest.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/Milestone3AcceptanceTest.php)
- Add acceptance tests for:
  - Rejecting `EFFECTIVE` effective source.
  - Rejecting empty or invalid effective source.
  - Verifying client-supplied `effective_weekly_hours` is ignored.
  - Successful validation for `MANUAL` effective source.
  - Requirements for `CUSTOM` effective source.

## Verification Plan

### Automated Tests
- Save the current test list.
- Run the full test suite with all PHPUnit warnings/deprecations displayed:
  ```powershell
  & "E:\xampp\php82\php.exe" vendor/bin/phpunit --no-coverage --display-warnings --display-deprecations --display-errors --display-notices --display-skipped --display-incomplete
  ```
- Run the second check with PHP Winget:
  ```powershell
  vendor/bin/phpunit --no-coverage
  ```

### Manual Verification
- Generate documentation for test discovery, routes, runtimes, validation, and reconciliation.
- Verify migration rollbacks and reapplies on test/dev DB.
- Audit counts on the development database.
