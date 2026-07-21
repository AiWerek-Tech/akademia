# Milestone 2 — Import Results

**Date**: 2026-07-21
**Features Tested**: Template Generation, Row Validation, Batch Apply, Error/Skip Handling
**Status**: ✅ **PASSED**

---

## Verification Results

### 1. Template Generation
- **Supported Entities**: `TEACHERS`, `SUBJECTS`, `CLASSROOMS`, `ROOMS`.
- **Formats**: Excel `.xlsx`.
- **Validation**: Generated templates verified to have correct header names and a second worksheet for filling instructions (`Petunjuk Pengisian`).

### 2. Row Validation (Staging)
- **TEACHERS**:
  - Valid row → validation status `VALID`, proposed action `INSERT`.
  - Missing name/employment status → validation status `ERROR`, messages recorded.
  - Fuzzy duplicate (Soundex/Levenshtein score >= 80%) → status `WARNING`, proposed action `MERGE`, target canonical ID matched.
- **SUBJECTS**:
  - Valid row → status `VALID`, proposed action `INSERT`.
  - Duplicate code → status `WARNING`, proposed action `UPDATE` (updates existing subject instead of inserting new).
- **ROOMS**:
  - Missing code/name → status `ERROR`.
  - Duplicate code → status `WARNING`, proposed action `UPDATE`.

### 3. Batch Apply (Transaction Safety)
- **All-or-Nothing**: Applied inside a single database transaction. Rollback occurs automatically on failure.
- **Filtering**:
  - Rows with `ERROR` validation status are skipped.
  - Rows marked as `SKIP` by administrative decision are skipped.
  - Only rows with `INSERT` or `UPDATE` action are applied.
- **Status Change**: Batch status transitions from `VALIDATED` to `APPLIED` with applied row count recorded in `master_import_batches`.
