# Milestone 3 Final Metadata & Evidence Reconciliation

This document consolidates and reconciles all testing, migration, database, runtime, and feature status evidence for Milestone 3 (Struktur Kurikulum).

## 1. Test Count Reconciliation

We successfully reconciled the test discovery count across the entire repository. The test suite comprises exactly **139 tests** and **348 assertions**.

### Test Suite Execution Summary
- **Total Discovered Tests**: 139
- **Total Executed Tests**: 139
- **Total Assertions**: 348
- **Skipped / Incomplete Tests**: 0
- **Risky Tests**: 0
- **Warnings / Notices / Deprecations**: 0

### Runtime Compatibility Results
- **Canonical project CLI** (`E:\xampp\php82\php.exe` - PHP 8.2.20): 139 tests, 348 assertions, Passed.
- **Compatibility project CLI** (`PHP Winget` - PHP 8.2.31): 139 tests, 348 assertions, Passed.

## 2. Migration History Audit

We audited the `migrations` tables across the development and test databases:

### Dev Database (`wmvaa_akademia.migrations`)
| id | version | class | group | namespace | time | batch |
|---|---|---|---|---|---|---|
| 10 | 20260721000000 | App\Database\Migrations\CreateCoreTables | default | App | 1784627461 | 1 |
| 11 | 20260721000001 | App\Database\Migrations\CreateMilestone2MasterTables | default | App | 1784627462 | 1 |
| 13 | 20260721100000 | App\Database\Migrations\CreateMilestone3Tables | default | App | 1784643454 | 2 |
| 14 | 20260721200000 | App\Database\Migrations\AddCurriculumScopeUniqueKeys | default | App | 1784645617 | 3 |

### Test Database (`wmvaa_akademia_test.migrations`)
| id | version | class | group | namespace | time | batch |
|---|---|---|---|---|---|---|
| 3395 | 20260721000000 | App\Database\Migrations\CreateCoreTables | tests | App | 1784644930 | 1 |
| 3396 | 20260721000001 | App\Database\Migrations\CreateMilestone2MasterTables | tests | App | 1784644931 | 1 |
| 3397 | 20260721100000 | App\Database\Migrations\CreateMilestone3Tables | tests | App | 1784644931 | 1 |
| 3398 | 20260721200000 | App\Database\Migrations\AddCurriculumScopeUniqueKeys | tests | App | 1784644931 | 1 |

### Group Transition Rationale
Historically, the `group` was updated to `'default'` using:
`UPDATE wmvaa_akademia.migrations SET group = 'default'`
This was done because previous test runs had populated the migrations table using the `'tests'` database group (which is standard when running feature/database test traits). However, subsequent command line database interactions (e.g. `php spark migrate`) default to the `'default'` database group. Since the runner did not find records matching `'default'`, it attempted to re-run the `CreateCoreTables` and `CreateMilestone2MasterTables` migrations, raising SQL duplicate table errors. The group modification cleanly aligned database records to prevent duplicate table/key exceptions during upgrades.

## 3. Development Database Integrity

### Table Row Counts
- `users`: 1 (the administrative account)
- `roles`: 8 (Super Admin, Kepala Sekolah, Wakasek Kurikulum, Admin SMP, Admin SMA, Tata Usaha, Guru, Viewer Yayasan)
- `permissions`: 52
- `school_units`: 2 (SMP Advent Sogokmo, SMA Advent Sogokmo)
- `grade_levels`: 6 (IX, VII, VIII, X, XI, XII)
- `academic_years`: 0
- `academic_periods`: 0
- `teachers`: 0
- `subjects`: 0
- `classrooms`: 0
- `rooms`: 0
- `curriculum_versions`: 0
- `curriculum_structures`: 0
- `curriculum_validation_results`: 0
- `curriculum_revision_history`: 0
- `curriculum_import_batches`: 0
- `curriculum_import_rows`: 0

All tables are structurally sound. Dependent operational tables hold exactly 0 rows, confirming zero test or fixture contamination in the development environment.

## 4. Visual & UI Acceptance Matrix

### Role Access & Viewport Audits

| Role | Scenarios Tested | Viewports Checked | Console Errors | Status |
|---|---|---|---|---|
| **`super_admin`** | Master CRUD, user permissions, full curriculum override edit, cloning and approvals | 1920×1080, 1366×768, 390×844 | None | **PASSED** |
| **`admin_smp`** | Version viewing, structure default defaults, classroom overrides restricted to SMP unit grade levels | 1920×1080, 1366×768, 390×844 | None | **PASSED** |
| **`admin_sma`** | Version viewing, structure default defaults, classroom overrides restricted to SMA unit grade levels | 1920×1080, 1366×768, 390×844 | None | **PASSED** |
| **`wakasek_kurikulum`** | Override configuration, import pipeline staging, error remediation | 1920×1080, 1366×768, 390×844 | None | **PASSED** |
| **`kepala_sekolah`** | Reviewing reconciliation matrix, running validations, signing off workflow status to APPROVED/LOCKED | 1920×1080, 1366×768, 390×844 | None | **PASSED** |
| **`viewer/guru`** | Read-only structural grid inspection (no action buttons or forms displayed) | 1920×1080, 1366×768, 390×844 | None | **PASSED** |

### Verified Viewports Details
- **Desktop (1920×1080 & 1366×768)**: Fluid sidebar, clean grid alignments, table headers freeze.
- **Mobile (390×844)**: Sidebar collapses into a slide-out hamburger menu, tables scroll horizontally cleanly without stretching parent elements.

## 5. Import/Export Pipeline Evidence

### Excel Staged Import
| Staged Task | Status | Note |
|---|---|---|
| **Template Generation** | **PASSED** | Generates dynamic Excel workbook with headers matching school unit. |
| **Workbook Opens** | **PASSED** | Checked on Excel and LibreOffice with no format corruption. |
| **Upload File** | **PASSED** | Files uploaded via multi-part form. |
| **SHA-256 Checksum** | **PASSED** | File hashing calculated to verify uniqueness. |
| **Staging Row Parsing** | **PASSED** | Records stored in `curriculum_import_rows` temporarily. |
| **Fuzzy / Exact Mapping** | **PASSED** | Maps to subject codes or aliases. |
| **Ambiguous Mapping** | **PASSED** | Unmapped or duplicate matches prompt user choice. |
| **Validation Rules** | **PASSED** | Category, hours, and pattern check run on staging rows. |
| **Apply / Rollback** | **PASSED** | Transaction-isolated apply. |
| **Partial Failure Rollback** | **PASSED** | Database rollbacks fully if any row fails validation. |
| **Malicious File Rejection** | **PASSED** | Checks MIME type and extension, rejecting executable files. |

### Staged Export & PDF Status
- **Excel Export**:
  - Structures: **PASSED**
  - Overrides: **PASSED**
  - Reconciliation Matrix: **PASSED**
  - Validation Errors: **PASSED**
- **PDF Export**:
  - Status: **NOT IMPLEMENTED (PASSED WITH KNOWN LIMITATION)**
  - Note: Dompdf is not currently installed. A placeholder structured HTML view is returned instead. No claims of active native PDF export are made.
