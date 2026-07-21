# Milestone 3 Import End-to-End Results

This document verifies the robustness, security, and transaction safety of the curriculum structure Excel import pipeline.

## 1. Import Workflow Validation
The following staged actions were verified during testing:
- **Template Generation**: Downloadable Excel files with correct structural headers are generated dynamically.
- **Staging & Hash Safety**: Uploaded files generate a random filename and a SHA-256 checksum recorded in `curriculum_import_batches`.
- **MIME & Extension Security**: Only valid Excel files (e.g. `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`) are accepted. Wrong extensions, XLSM, macro files, or formula injections are actively rejected at the filter/service level.
- **Preview & Dry Run**: Rows are normalized, mapped against master data, and warnings/errors are returned without mutating operational structures.
- **Transactional Apply**: On admin approval, the batch is processed inside a single DB transaction. Partial failure triggers a complete rollback.

## 2. Data Mapping Boundaries
- **Exact & Alias Mapping**: Valid subjects map by code or preconfigured aliases.
- **No Auto-Resolve for Ambiguous Data**: If a subject alias matches multiple records, the row is marked as `UNRESOLVED` and remains in a review state. Auto-mapping of ambiguous data is strictly prohibited.
- **Category & Source Fallbacks**: Empty category defaults to `INTRAKURIKULER`. Missing block patterns are mapped as null defaults. If the effective source is not one of `OFFICIAL`, `CUSTOM`, or `MANUAL`, the row fails validation.
