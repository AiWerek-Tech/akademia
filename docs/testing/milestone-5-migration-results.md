# Migration & Database Integrity Verification — Milestone 5

## Fresh Migration Test Steps (M0–M5)
1. **Clean Database**:
   - Environment: MySQL / MariaDB on PHP 8.2.20
   - Database: `wmvaa_akademia_test`
2. **Fresh Migration (M0–M5)**:
   ```powershell
   & "E:\xampp\php82\php.exe" spark migrate --g tests
   ```
   - **Result**: **SUCCESSFUL** (All 21 Milestone 5 tables created successfully).
3. **Seeder Idempotency Verification**:
   ```powershell
   & "E:\xampp\php82\php.exe" spark db:seed Milestone5Seeder --g tests
   ```
   - Re-run seeder: No duplicate key errors or primary key violations. All permissions and constraints populated idempotently.
4. **Rollback Safety Verification**:
   ```powershell
   & "E:\xampp\php82\php.exe" spark migrate:rollback --g tests
   ```
   - Rollback of M5 tables executed with foreign key check disabled (`disableForeignKeyChecks()`).
   - M0–M4 tables remained intact.
5. **Re-migration Verification**:
   ```powershell
   & "E:\xampp\php82\php.exe" spark migrate --g tests
   ```
   - **Result**: **SUCCESSFUL**.

## Table Name Consistency Audit
- **Migration**: Table named `schedule_candidate_entries` (shortened from 69 chars to fit MySQL 64-char FK constraint limit).
- **Model**: `ScheduleCandidateEntryModel.php` (`$table = 'schedule_candidate_entries'`).
- **Services**: `DeterministicGreedyScheduleGenerator.php` queries `schedule_candidate_entries`.
- **Tests**: `ScheduleGeneratorTest.php` asserts `schedule_candidate_entries`.
- **Documentation**: All schema docs refer to `schedule_candidate_entries`.
