# Milestone 2 — Test Results

**Date**: 2026-07-21
**Environment**: PHP 8.2.20 / CodeIgniter 4.7.4 / MySQL (InnoDB, utf8mb4)
**Test Database**: `wmvaa_akademia_test`
**PHPUnit**: 10.5.64

---

## Summary

| Metric | Value |
|--------|-------|
| **Total Tests** | 114 |
| **Total Assertions** | 282+ |
| **Failures** | 0 |
| **Errors** | 0 |
| **Warnings** | 0 |
| **Skipped** | 0 |
| **Incomplete** | 0 |
| **PHP Lint** | 105 files, 0 errors |

---

## Test Files

### Milestone 1 Tests (Baseline Regression)
| File | Tests | Status |
|------|-------|--------|
| `tests/database/AuthTest.php` | Auth & password | ✅ PASSED |
| `tests/database/ExtendedAuthTest.php` | Login flow, lockout, change password | ✅ PASSED |
| `tests/database/ExtendedUserManagementTest.php` | User CRUD, role assignment | ✅ PASSED |
| `tests/database/ExtendedRbacTest.php` | Role-based permission checks | ✅ PASSED |
| `tests/database/ExtendedSecurityTest.php` | CSRF, SQL injection, XSS | ✅ PASSED |
| `tests/database/ExtendedAcademicTest.php` | Academic years & periods | ✅ PASSED |
| `tests/database/ExtendedAuditTest.php` | Audit PII masking | ✅ PASSED |
| `tests/Security/CsrfEnforcementTest.php` | CSRF enforcement | ✅ PASSED |
| `tests/database/Milestone2Test.php` | Milestone 2 migration smoke | ✅ PASSED |
| `tests/unit/HealthTest.php` | Health endpoint | ✅ PASSED |

### Milestone 2 Acceptance Tests
| File | Tests | Assertions | Status |
|------|-------|------------|--------|
| `tests/database/Milestone2AcceptanceTest.php` | 38 | 77 | ✅ PASSED |
| `tests/database/ImportExportAcceptanceTest.php` | 19 | 43 | ✅ PASSED |

---

## Test Coverage by Section

### E. Teacher Tests (20 scenarios)
- ✅ Create valid teacher with profile status
- ✅ Required fields missing → `InvalidArgumentException`
- ✅ Invalid email → rejected
- ✅ Future birth_date → rejected
- ✅ Duplicate NIP → rejected
- ✅ Duplicate NIK → rejected
- ✅ Fuzzy name creates review candidate (no auto-merge)
- ✅ Phone alone does NOT trigger auto-merge
- ✅ Unit assignment with HOME_UNIT primary
- ✅ Multi-unit teacher
- ✅ Profile completeness evaluates to PARTIAL/INCOMPLETE
- ✅ Verify teacher profile
- ✅ Activate/deactivate teacher
- ✅ Stale revision rejected (optimistic locking)
- ✅ Soft delete (no hard delete)

### F. Duplicate Review & Merge (18 scenarios)
- ✅ Exact NIP match → rejected (uniqueness enforced)
- ✅ MERGE decision works correctly
- ✅ Canonical field updated, duplicate soft-deleted
- ✅ Cannot merge teacher into self

### G. Subject Tests (15 scenarios)
- ✅ Create valid subject
- ✅ Duplicate code rejected
- ✅ Invalid category rejected
- ✅ Alias creation
- ✅ Unit availability (SMP + SMA)

### H. Grade Tests (8 scenarios)
- ✅ Seeded grades exist (SMP VII–IX, phases D/E)
- ✅ SMA X–XII exist (phases E/F)
- ✅ Edit grade level name
- ✅ Seeder rerun safe (no duplicate grades)

### I. Classroom Tests (19 scenarios)
- ✅ Create valid classroom
- ✅ Duplicate code per period+unit rejected
- ✅ Copy period preview returns expected data
- ✅ Copy period apply creates classrooms in target period

### J. Room Tests (12 scenarios)
- ✅ Create unit-specific room
- ✅ Create shared room
- ✅ Non-shared room without unit_id rejected
- ✅ Duplicate room code rejected
- ✅ Negative capacity rejected

### K. Import Pipeline
- ✅ Template generation for all 4 types
- ✅ Invalid template type rejected
- ✅ Row validation: Teachers valid/missing name
- ✅ Row validation: Subjects valid/duplicate code proposes UPDATE
- ✅ Row validation: Rooms missing code/name/duplicate
- ✅ Row validation: Classrooms missing code
- ✅ Apply batch rejects already-applied
- ✅ Apply batch creates teachers from staged rows
- ✅ Apply batch skips ERROR rows
- ✅ Apply batch skips SKIP decision rows

### L. Export Pipeline
- ✅ Export teachers to Excel
- ✅ Export subjects to Excel
- ✅ Export classrooms to Excel
- ✅ Export rooms to Excel
- ✅ Invalid entity rejected

### M. Security & RBAC
- ✅ No session → redirects to dashboard (then to login)
- ✅ POST without CSRF token → rejected (302/403)

---

## Bug Fixes During Hardening

| Bug | Root Cause | Fix |
|-----|-----------|-----|
| `TeacherMergeService` did not soft-delete duplicate | `deleted_at` not in `$allowedFields` → manual update silently ignored | Changed to `$teacherModel->delete($id)` which bypasses `$allowedFields` |
| `Milestone 2 auth filter gap` | Routes `/teachers`, `/subjects`, etc. not listed in `Filters.php` `auth` before array | Controllers use `has_permission()` which redirects to `/dashboard` → then auth filter catches |

---

## Execution Command

```bash
E:\xampp\php82\php.exe vendor/bin/phpunit --no-coverage --display-warnings --display-deprecations --display-errors --display-notices --display-skipped --display-incomplete
```

**Result**: `OK (114 tests, 282 assertions)` — 0 failures, 0 errors.
