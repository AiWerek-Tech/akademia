# Schedule Import Guide & Hardening Specification — Milestone 5

## Staging Pipeline
The schedule import engine (`ScheduleImportService`) operates via a 3-stage transactional pipeline:
1. **Upload & Security Validation**:
   - File MIME validation (`text/csv`, `application/vnd.ms-excel`, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`).
   - File size limit: 5MB maximum.
   - Excel security checks: Formula neutralization (prefixed with `'`), macro rejection (`.xlsm` / `.xltm` rejected), path traversal prevention, randomized storage filename (`SHA-256` hash).
2. **Staging & Entity Resolution**:
   - Rows inserted into `schedule_import_rows` under `schedule_import_batches`.
   - Codes for day, slot number, classroom, teacher, subject, room resolved against database records. Unresolved or ambiguous codes set `validation_status = 'ERROR'`.
3. **Conflict Preview & Decision Apply**:
   - User previews conflict detection report on staged rows.
   - Batch apply inserts valid rows into `schedule_entries` atomically. Rollback capability reverts applied entries if requested.
