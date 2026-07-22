# Schedule Import End-to-End Verification — Milestone 5

## Import Security & Staging Tests
1. **File Type & MIME Validation**: Only `.csv`, `.xls`, `.xlsx` allowed; executable or macro files (`.xlsm`) rejected.
2. **Size & Bounds Check**: Files over 5MB, sheets over 5, rows over 1,000 rejected.
3. **Formula Neutralization**: Cells starting with `=`, `+`, `-`, `@` escaped with single quote `'` prior to DB insertion.
4. **Randomized Filename**: Uploaded files stored with SHA-256 randomized UUID filenames.
5. **Staging Pipeline**:
   - `ScheduleImportService::stageBatch()` creates batch in `schedule_import_batches` and rows in `schedule_import_rows`.
   - Code resolution validates `day_code`, `slot_number`, `class_code`, `teacher_code`, `subject_code`, `room_code`.
   - Ambiguous or non-existent codes flagged as `validation_status = 'ERROR'`.
6. **Conflict Preview & Apply**:
   - Conflicts detected against existing schedule entries shown in preview.
   - Batch apply inserts valid entries atomically.
   - Batch rollback removes applied entries if requested.
