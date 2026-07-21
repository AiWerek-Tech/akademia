# Walkthrough — Milestone 3: Struktur Kurikulum

We have successfully completed Milestone 3 (Struktur Kurikulum) for SMP & SMA units. All database schemas, models, services, controllers, routes, filters, UI views, tests, and documentation have been implemented and verified.

---

## 1. Database Schema & Models
- Created database migration file [20260721100000_CreateMilestone3Tables.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721100000_CreateMilestone3Tables.php).
- Defined six new models with full schema validation and hooks:
  - [CurriculumVersionModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumVersionModel.php)
  - [CurriculumStructureModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumStructureModel.php)
  - [CurriculumValidationResultModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumValidationResultModel.php)
  - [CurriculumRevisionHistoryModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumRevisionHistoryModel.php)
  - [CurriculumImportBatchModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumImportBatchModel.php)
  - [CurriculumImportRowModel](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/CurriculumImportRowModel.php)

## 2. Core Services & Business Logic
- **CurriculumVersionService**: Handles lifecycle, draft/active/archived/locked transitions, cloning with structures, and version revisioning.
- **CurriculumStructureService**: Manages subject-level availability, defaults, and overrides.
- **CurriculumEffectiveHoursService**: Implements JP calculations based on OFFICIAL, CUSTOM, and MANUAL hours, verifying non-negative boundaries.
- **BlockPatternService**: Validates block schedule schemes (e.g. `[2, 2]` or `[4]`) and minimum/maximum constraints.
- **CurriculumResolutionService**: Resolves classroom-level overrides correctly (resolves to classroom override if present; falls back to grade-level default).
- **CurriculumValidationService**: Performs complex multi-rule database integrity validations (cross-subject, total JP limit, room validation, duplicate checks).
- **CurriculumReconciliationService**: Calculates discrepancies between target and allocated JP structures.
- **CurriculumImportService / CurriculumExportService**: Manages structured Excel imports (validates rows, stages them, commits on apply) and exports version structure metrics.

## 3. UI Views & Integration
- Created migration [20260721200000_AddCurriculumScopeUniqueKeys.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721200000_AddCurriculumScopeUniqueKeys.php) to isolate generated unique index constraints.

## 4. Verification & Testing
- Created [Milestone3AcceptanceTest.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/Milestone3AcceptanceTest.php) (17 tests) verifying all functional requirements.
- Created [CurriculumSecurityTest.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/CurriculumSecurityTest.php) (4 tests) verifying security invariants.
- Executed the entire test suite: **all 139 tests passed successfully with 0 errors or failures.**

```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.20
OK (139 tests, 348 assertions)
```
