# Milestone 2 Implementation Plan — Master Data Academic Terpadu

## Overview
Milestone 2 builds the core unified academic master data layer for both SMP and SMA units in the WMVAA Akademia platform. It establishes single-source-of-truth master records for Teachers, Subjects, Grade Levels, Classrooms/Rombel, Rooms, Import Staging, and Duplicate Resolution without introducing any teaching assignments, JP structures, or schedule entries (which are reserved for Milestone 3).

## Key Architectural Principles
- **Unified Single Application & Database**: One `teachers` table, one `subjects` table, two units (`unit_id`). No `guru_smp`/`guru_sma` tables or duplicated controllers.
- **Strict Layered Architecture**: `Controller` -> `Service` -> `Repository/Model` -> `Database`. No business logic in controllers/views.
- **Optimistic Locking & Audit**: Revision numbers on entity records to prevent stale updates. Audit logs generated for all master mutations.
- **Staging Import Engine**: Upload -> Parse -> Staging DB -> Validation -> Duplicate Detection -> Preview/Decision -> Transactional Apply & Rollback.
- **Clean Scope Isolation**: Milestone 2 stops strictly before curriculum structures, JP calculations, or teaching assignments.

---

## User Review Required

> [!IMPORTANT]
> **No Palpable Fake Data in Development Database**: Per section 26, development database will only seed grade levels, room types, permissions, and role mappings. Master teachers, subjects, classrooms, and rooms will be populated through forms or import staging.

> [!NOTE]
> **Pre-Gate Validation Complete**:
> - Git commit `ad0e0b5` recorded & tree clean.
> - Composer validate `--strict` & `audit` (0 advisories) PASSED.
> - Full PHPUnit suite (53 tests, 149 assertions) 100% PASSED.
> - Full PHP linter (122 files) 0 errors PASSED.
> - Database schema backed up to `backups/milestone1_schema_backup.sql`.

---

## Open Questions

> [!NOTE]
> None at this stage. All requirements, database schemas, API routes, RBAC permissions, duplicate detection algorithms, import staging steps, and UI screens are fully defined in the specification.

---

## Proposed Changes & Components

### 1. Database Migration & Models

#### [NEW] [20260721000001_CreateMilestone2MasterTables.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721000001_CreateMilestone2MasterTables.php)
Migration creating all 15 required tables with InnoDB, utf8mb4, foreign keys, indices, and audit columns:
- `teachers`: Master teacher profiles (`uuid`, `employee_number`, `nip`, `nik`, `full_name`, `normalized_name`, `profile_status`, `revision_number`, etc.)
- `teacher_unit_assignments`: Multi-unit relationships (`teacher_id`, `unit_id`, `assignment_type`, etc.)
- `teacher_qualifications`: Educational backgrounds
- `subjects`: Master subjects (`code`, `name`, `normalized_name`, `category`, `counts_in_report`, `counts_as_teaching_load`, `revision_number`)
- `subject_aliases`: Mapping alternative subject codes/names per unit
- `subject_unit_availability`: Subject availability per unit (`subject_id`, `unit_id`, `is_available`)
- `grade_levels`: Year levels per unit (SMP: VII-IX, SMA: X-XII, Phase D/E/F)
- `classrooms`: Active classrooms per academic period and unit (`code`, `name`, `homeroom_teacher_id`, `default_room_id`, `status`)
- `room_types`: Room classification lookup (Classroom, Lab, Library, Chapel, Hall, etc.)
- `rooms`: School physical rooms (`code`, `name`, `shared_between_units`, `capacity`, `location`)
- `master_import_batches`: Import batch metadata
- `master_import_rows`: Staging row entries with JSON payload & proposed action
- `duplicate_review_groups` & `duplicate_review_members`: Duplicate candidate groups and side-by-side comparison snapshots

#### [NEW] Master Models
- `TeacherModel.php`, `TeacherUnitAssignmentModel.php`, `TeacherQualificationModel.php`
- `SubjectModel.php`, `SubjectAliasModel.php`, `SubjectUnitAvailabilityModel.php`
- `GradeLevelModel.php`, `ClassroomModel.php`, `RoomTypeModel.php`, `RoomModel.php`
- `MasterImportBatchModel.php`, `MasterImportRowModel.php`
- `DuplicateReviewGroupModel.php`, `DuplicateReviewMemberModel.php`

---

### 2. Core Business Services

#### [NEW] [TeacherService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherService.php)
- Handles teacher creation, editing, profile completeness calculations via `TeacherProfileCompletenessService`, unit assignment synchronization, optimistic locking verification, and duplicate detection triggers.

#### [NEW] [TeacherDuplicateDetectionService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherDuplicateDetectionService.php)
- Tier 1 exact match (NIP, NIK, employee number, email) and Tier 2 fuzzy match (normalized name, DOB). Creates `duplicate_review_groups` for admin review instead of auto-merging.

#### [NEW] [TeacherMergeService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherMergeService.php)
- Performs admin-approved record merging, re-linking unit assignments, updating audit trails, and marking source duplicates as merged.

#### [NEW] [SubjectService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/SubjectService.php)
- Manages global subject creation, unit availability mappings, alias registration, category validation, and optimistic locking.

#### [NEW] [ClassroomService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/ClassroomService.php)
- Manages period-bound classroom creation, period-to-period copy with preview/skip functionality, homeroom teacher linking, and code unique constraints.

#### [NEW] [RoomService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/RoomService.php)
- Manages physical room records, shared room access rules, capacity constraints, and location tracking.

#### [NEW] [MasterImportService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/MasterImportService.php)
- Handles template generation, secure file upload (mime/size/extension check, SHA-256 hash), macro-free parsing, staging DB insertion, validation, proposed action assignment, transactional apply, and batch rollback.

#### [NEW] [MasterExportService.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/MasterExportService.php)
- Generates filtered Excel and PDF reports for Teachers, Subjects, Classrooms, Rooms, and Duplicate Reviews securely from `writable/`.

---

### 3. Seeders & RBAC Update

#### [MODIFY] [CoreSeeder.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Seeds/CoreSeeder.php)
- Seeds idempotent grade levels (SMP VII-IX Phase D, SMA X-XII Phase E/F), room types, and 16 new Milestone 2 permissions (`teachers.*`, `subjects.*`, `grade_levels.*`, `classrooms.*`, `rooms.*`, `duplicates.*`).
- Maps permissions to roles (`super_admin`, `kepala_sekolah`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `tata_usaha`).

---

### 4. Controllers & Routes

#### [MODIFY] [Routes.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)
Adds routes for:
- `/teachers` (index, create, edit, verify, units, export)
- `/subjects` (index, create, edit, aliases, units, export)
- `/grade-levels` (index, edit)
- `/classrooms` (index, create, edit, activate, archive, copy-period, export)
- `/rooms` (index, create, edit, export)
- `/imports/master` (upload, template, validate, decision, apply, rollback, cancel)
- `/duplicates` (index, show, merge, keep-separate)

#### [NEW] Controllers
- `TeacherController.php`, `SubjectController.php`, `GradeLevelController.php`, `ClassroomController.php`, `RoomController.php`, `MasterImportController.php`, `DuplicateReviewController.php`

---

### 5. Frontend Views & Design System

#### [MODIFY] [admin.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/layouts/admin.php)
- Updates sidebar menu under `MASTER DATA` dropdown:
  - Guru
  - Mata Pelajaran
  - Tingkat
  - Kelas/Rombel
  - Ruang
  - Import Master
  - Review Duplikat

#### [NEW] View Folders & Views
- `teachers/` (`index.php`, `create.php`, `edit.php`, `show.php`)
- `subjects/` (`index.php`, `create.php`, `edit.php`)
- `grade_levels/` (`index.php`, `edit.php`)
- `classrooms/` (`index.php`, `create.php`, `edit.php`, `copy_preview.php`)
- `rooms/` (`index.php`, `create.php`, `edit.php`)
- `imports/` (`index.php`, `show.php`)
- `duplicates/` (`index.php`, `show.php`)

---

## Verification Plan

### Automated Tests
- Create test files:
  - `tests/database/TeacherMasterTest.php`
  - `tests/database/SubjectMasterTest.php`
  - `tests/database/ClassroomRoomMasterTest.php`
  - `tests/database/DuplicateResolutionTest.php`
  - `tests/database/MasterImportTest.php`
- Run command: `E:\xampp\php82\php.exe vendor/bin/phpunit --no-coverage --display-warnings --display-deprecations --display-errors`

### Manual & Browser Verification
- Test all role scenarios (`superadmin`, `admin_smp`, `admin_sma`, `wakasek`, `kepala_sekolah`, `guru`) on viewports `1920x1080`, `1366x768`, and `390x844`.
- Validate dark/light mode, Lucide icon rendering, flash messages, and unit isolation.
- Run composer validate, composer audit, and full PHP linter (`lint_all.php`).
