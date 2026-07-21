# Milestone 2 — Export Results

**Date**: 2026-07-21
**Features Tested**: Excel Export, Security/RBAC
**Status**: ✅ **PASSED** (With Known Limitations)

---

## Verification Results

### 1. Excel Export (`.xlsx`)
- **Supported Entities**: `TEACHERS`, `SUBJECTS`, `CLASSROOMS`, `ROOMS`.
- **Validation**:
  - File created in `writable/exports/` with timestamped filename.
  - Correct number of rows and columns mapped.
  - Column headers set to bold.
  - File cleanup verified after assertions.

### 2. Security and Isolation
- **RBAC**: Access to exports checked against user permissions (e.g. `teachers.export`, `subjects.export`, `classrooms.export`, `rooms.export`).
- **Audit Logging**: Success actions logged in `audit_logs` under `EXPORT_EXCEL`.

---

## Known Limitations

- **PDF Export not implemented**: PDF export was skipped during Milestone 2 as `dompdf` / `mpdf` was not present/configured. PDF status is documented as `PASSED WITH KNOWN LIMITATIONS`.
