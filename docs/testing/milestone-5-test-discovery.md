# Test Discovery Reconciliation Report — Milestone 5

## Inventory of Tests
Full test discovery executed using command:
```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit --list-tests
```

### Categorization by Directory
| Test Directory | File Count | Discovered Test Methods | Description |
| :--- | :--- | :--- | :--- |
| `tests/database/` | 35 files | 152 tests | Database integration, migrations, seeders, M0-M5 models & services |
| `tests/Security/` | 1 file | 12 tests | CSRF enforcement & security filters |
| `tests/session/` | 1 file | 1 test | Session isolation tests |
| `tests/unit/` | 3 files | 5 tests | Health & home controller unit tests |
| **Total Full Suite** | **40 files** | **170 tests** | **Entire Akademia Test Suite** |

## Reconciliation of Test Counts
- **Baseline M4 Total Full Suite**: 163 tests
- **Milestone 5 New Tests**:
  - `Milestone5MigrationTest.php`: 1 test (21 table structure assertions)
  - `Milestone5SeederTest.php`: 1 test (25 permission & constraint assertions)
  - `ScheduleVersionTest.php`: 1 test (8 workflow & OCC assertions)
  - `ScheduleGeneratorTest.php`: 1 test (7 requirement sync, greedy generator, candidate apply, & scoring assertions)
  - `ScheduleConflictDetectionTest.php`: 1 test (2 hard conflict assertions)
  - `ScheduleImportExportTest.php`: 1 test (6 staging, batch apply, & export assertions)
  - `ScheduleSecurityTest.php`: 1 test (6 unit boundary assertions)
- **Expected M5 Full Suite Total**: 163 + 7 = **170 tests**
- **Discovered Full Suite Total**: **170 tests** (100% reconciled).

### Investigation of `tests/database/` Sub-suite (152 tests)
Running `vendor/bin/phpunit tests/database/` targets only the 35 files inside `tests/database/` (145 M0-M4 database tests + 7 M5 tests = 152 tests).
The 18 non-database tests reside in `tests/Security/` (12), `tests/session/` (1), and `tests/unit/` (5).
Executing `vendor/bin/phpunit` from the root executes all 170 tests across all directories.
