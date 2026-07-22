# Milestone 4 Test Execution Results

**Date**: 2026-07-22  
**Environment**: PHP 8.2.20, MySQL 8.0 / MariaDB, CodeIgniter 4.5  
**Result**: 100% PASSED (163 tests, 481 assertions)  

---

## 1. Milestone 4 Core Database Test Suite

| Test File | Tests | Assertions | Status |
| :--- | :---: | :---: | :---: |
| `tests/database/Milestone4AcceptanceTest.php` | 1 | 20 | PASSED |
| `tests/database/Milestone4MigrationTest.php` | 2 | 19 | PASSED |
| `tests/database/Milestone4SeederTest.php` | 1 | 7 | PASSED |
| `tests/database/TeachingAssignmentTest.php` | 1 | 2 | PASSED |
| `tests/database/AdditionalDutyTest.php` | 2 | 5 | PASSED |
| `tests/database/AssignmentMatrixTest.php` | 1 | 3 | PASSED |
| `tests/database/AssignmentVersionTest.php` | 1 | 4 | PASSED |
| `tests/database/TeamTeachingTest.php` | 1 | 2 | PASSED |
| `tests/database/WorkloadCalculationTest.php` | 1 | 2 | PASSED |
| `tests/database/WorkloadPolicyTest.php` | 1 | 2 | PASSED |
| `tests/database/AssignmentSecurityTest.php` | 1 | 3 | PASSED |
| `tests/database/AssignmentImportTest.php` | 1 | 3 | PASSED |

---

## 2. Regression & Cross-Module Test Suite Execution

All 163 unit and database tests across all milestones (M0, M1, M2, M3, M4) were executed:

```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit --no-coverage
```

```text
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.20
Configuration: E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\phpunit.xml.dist

...............................................................  63 / 163 ( 38%)
............................................................... 126 / 163 ( 77%)
.....................................                           163 / 163 (100%)

Time: 21:37.450, Memory: 72.00 MB

OK (163 tests, 481 assertions)
```

---

## 3. Verified Business Rules

- **Workload Status Calculation**: Produces `INCOMPLETE`, `UNDERLOAD`, `WITHIN_TARGET`, `OVERLOAD`, `NO_POLICY`, or `NEEDS_REVIEW`.
- **Workflow State Machine**: `DRAFT` → `VALIDATED` → `REVIEWED` → `APPROVED` → `LOCKED` enforced with optimistic locking.
- **Cross-Unit Policy**: Enforces strict unit boundary checks for unit admins, while allowing cross-unit teaching assignments (SMP–SMA).
- **Immutability**: Locked assignment versions cannot be altered without initiating a formal revision.
