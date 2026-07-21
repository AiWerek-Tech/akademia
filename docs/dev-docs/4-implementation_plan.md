# Implementation Plan - Milestone 2: Master Data Akademik Terpadu

## Goal Description
Build the unified Master Data foundation for SMP and SMA units in **WMVAA Akademia**:
1. **Master Guru Global & Multi-Unit Relations** (`teachers`, `teacher_unit_assignments`, `teacher_identifiers`, `teacher_qualifications`).
2. **Master Mata Pelajaran & Alias** (`subjects`, `subject_aliases`, `subject_unit_availability`).
3. **Tingkat Kelas & Fase Pendidikan** (`grade_levels` - SMP VII-IX Phase D, SMA X Phase E, SMA XI-XII Phase F).
4. **Kelas / Rombel per Periode** (`classrooms` - period & unit-scoped, homeroom assignment, default room, copy period workflow).
5. **Ruang Sekolah** (`rooms`, `room_types` - unit-specific vs shared rooms).
6. **Import Staging Pipeline** (`master_import_batches`, `master_import_rows` - template download, upload, validation, preview, decision, transactional apply, batch rollback).
7. **Duplicate Detection & Review Engine** (`duplicate_review_groups`, `duplicate_review_members` - multi-tier identifier & fuzzy matching, side-by-side comparison, merge & separate decisions).
8. **Master Completeness & Health Dashboard** (`TeacherProfileCompletenessService`, statistics, alert cards).
9. **Export Engine** (Excel & PDF master data exports with permission & unit isolation).
10. **Audit, Optimistic Locking & Security** (`revision_number`, PII masking, RBAC unit isolation via `UnitContextService`).

---

## User Review Required

> [!IMPORTANT]
> **Strict Milestone 2 Scope Enforcement**:
> - **NO** curriculum structure (kurikulum), subject teaching load allocation (jumlah JP), teacher subject assignment (pengampu mapel), schedule generation, SK/surat tugas, teacher portal, or direct Kumer database read/write will be introduced in this milestone.
> - Data model uses single global `teachers` and `subjects` tables partitioned across SMP and SMA via `unit_id` and unit availability relations. No unit-specific duplicate tables (`teachers_smp`/`teachers_sma`) are created.

---

## Proposed Changes

### Database & Seeders

#### [NEW] [20260721000001_CreateMilestone2MasterTables.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Migrations/20260721000001_CreateMilestone2MasterTables.php)
Migration defining all 15 master tables: `teachers`, `teacher_unit_assignments`, `teacher_identifiers`, `teacher_qualifications`, `room_types`, `subjects`, `subject_aliases`, `subject_unit_availability`, `grade_levels`, `rooms`, `classrooms`, `master_import_batches`, `master_import_rows`, `duplicate_review_groups`, `duplicate_review_members`.

#### [NEW] [Milestone2MasterSeeder.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Database/Seeds/Milestone2MasterSeeder.php)
Idempotent seeder populating:
- **Grade Levels**: SMP (VII, VIII, IX - Phase D), SMA (X - Phase E, XI - Phase F, XII - Phase F).
- **Room Types**: `CLASSROOM`, `LAB_COMPUTER`, `LAB_SCIENCE`, `LIBRARY`, `CHAPEL`, `HALL`, `OFFICE`, `OUTDOOR`, `OTHER`.
- **Permissions**: `teachers.view/manage/verify/import/export`, `subjects.view/manage/import/export`, `grade_levels.view/manage`, `classrooms.view/manage/import/export`, `rooms.view/manage/import/export`, `duplicates.view/resolve`.
- **Role Permissions Mapping**: `super_admin`, `kepala_sekolah`, `wakasek_kurikulum`, `admin_smp`, `admin_sma`, `tata_usaha`, `guru`, `viewer_yayasan`.

---

### Core Data Models & Repositories

#### [NEW] [TeacherModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/TeacherModel.php)
#### [NEW] [TeacherUnitAssignmentModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/TeacherUnitAssignmentModel.php)
#### [NEW] [TeacherIdentifierModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/TeacherIdentifierModel.php)
#### [NEW] [TeacherQualificationModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/TeacherQualificationModel.php)
#### [NEW] [SubjectModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/SubjectModel.php)
#### [NEW] [SubjectAliasModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/SubjectAliasModel.php)
#### [NEW] [SubjectUnitAvailabilityModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/SubjectUnitAvailabilityModel.php)
#### [NEW] [GradeLevelModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/GradeLevelModel.php)
#### [NEW] [ClassroomModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/ClassroomModel.php)
#### [NEW] [RoomTypeModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/RoomTypeModel.php)
#### [NEW] [RoomModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/RoomModel.php)
#### [NEW] [MasterImportBatchModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/MasterImportBatchModel.php)
#### [NEW] [MasterImportRowModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/MasterImportRowModel.php)
#### [NEW] [DuplicateReviewGroupModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/DuplicateReviewGroupModel.php)
#### [NEW] [DuplicateReviewMemberModel.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Models/DuplicateReviewMemberModel.php)

---

### Business Logic Services

#### [NEW] [TeacherService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherService.php)
Handles teacher creation, updates, verification, unit assignments, qualifications, optimistic locking verification, name normalization, and audit logging.

#### [NEW] [TeacherDuplicateDetectionService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherDuplicateDetectionService.php)
Multi-tier engine:
- Tier 1: Exact NIP, NIK, employee number, valid email match.
- Tier 2: Normalized name match, name + birthdate match, name + unit match.
Creates structured `duplicate_review_groups` and `duplicate_review_members`.

#### [NEW] [TeacherMergeService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherMergeService.php)
Performs safe side-by-side merging: select canonical fields, combine unit assignments, record snapshot history, soft-archive merged record, audit logging.

#### [NEW] [TeacherProfileCompletenessService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/TeacherProfileCompletenessService.php)
Calculates completeness percentage, missing mandatory & optional fields, and assigns status (`INCOMPLETE`, `PARTIAL`, `COMPLETE`, `NEEDS_REVIEW`).

#### [NEW] [SubjectService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/SubjectService.php)
Manages global subjects, unit availability per school unit, alias mapping, category validation, optimistic locking, and audit logging.

#### [NEW] [GradeLevelService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/GradeLevelService.php)
Manages unit grade levels, phase editing, sort orders, and ensures unit immutability once referenced by classrooms.

#### [NEW] [ClassroomService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/ClassroomService.php)
Manages period & unit-scoped classrooms, homeroom teacher assignment, default room selection, code uniqueness per period & unit, copy period functionality (preview, insert-only copy, skip existing, optional homeroom copy, transactional execution, audit log).

#### [NEW] [RoomService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/RoomService.php)
Manages rooms, shared vs unit-specific constraints, capacity, location, floor, facilities JSON, room type validity, optimistic locking, and audit logging.

#### [NEW] [MasterImportService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/MasterImportService.php)
Staging import pipeline:
- Template generation & download (Teachers, Subjects, Classrooms, Rooms).
- Upload validation (MIME, extension, size limit, randomized file storage, SHA-256 calculation).
- Spreadsheet parsing & staging storage in `master_import_rows`.
- Data normalization, validation rules execution, duplicate candidate detection.
- Preview & admin decision interface.
- Transactional apply & batch rollback engine.

#### [NEW] [MasterExportService.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Services/MasterExportService.php)
Generates styled Excel spreadsheets and formatted PDF files for Teachers, Subjects, Classrooms, Rooms, Duplicate Review Reports, and Master Completeness Summaries with unit context filtering & permission checks.

---

### Controllers & HTTP Routing

#### [MODIFY] [Routes.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)
Registers all Milestone 2 routes under authenticated and RBAC-protected groups.

#### [NEW] [TeachersController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/TeachersController.php)
#### [NEW] [DuplicateReviewController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/DuplicateReviewController.php)
#### [NEW] [SubjectsController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/SubjectsController.php)
#### [NEW] [GradeLevelsController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/GradeLevelsController.php)
#### [NEW] [ClassroomsController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/ClassroomsController.php)
#### [NEW] [RoomsController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/RoomsController.php)
#### [NEW] [MasterImportController.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/MasterImportController.php)

---

### Views & Navigation UI

#### [MODIFY] [sidebar.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/layouts/sidebar.php)
Adds the "MASTER DATA" navigation section containing Guru, Mata Pelajaran, Tingkat, Kelas/Rombel, Ruang, Import Master, Review Duplikat.

#### [NEW] Master Views Directory Structure:
- `app/Views/teachers/` (index, create, edit, show, history, export)
- `app/Views/duplicates/` (index, show, compare_merge)
- `app/Views/subjects/` (index, create, edit, show)
- `app/Views/grade_levels/` (index, edit)
- `app/Views/classrooms/` (index, create, edit, copy_period)
- `app/Views/rooms/` (index, create, edit)
- `app/Views/imports/` (index, show_batch, preview)

---

## Verification Plan

### Automated Tests (PHPUnit)
- `tests/Database/Milestone2MigrationTest.php`: Migration fresh, rollback, reapply, schema integrity.
- `tests/Database/Milestone2SeederTest.php`: Grade levels, room types, permissions, role mapping idempotency.
- `tests/Services/TeacherServiceTest.php`: Teacher CRUD, profile completeness, unit assignments, optimistic locking.
- `tests/Services/TeacherDuplicateAndMergeTest.php`: Multi-tier duplicate detection, side-by-side merge, transaction rollback on failure.
- `tests/Services/SubjectServiceTest.php`: Subject CRUD, code uniqueness, alias mapping, unit availability.
- `tests/Services/ClassroomServiceTest.php`: Classroom CRUD, unit/period scoping, copy period workflow.
- `tests/Services/RoomServiceTest.php`: Room CRUD, shared vs unit-specific rules, capacity checks.
- `tests/Services/MasterImportServiceTest.php`: Staging import, invalid MIME/extension/macro rejection, transactional apply, batch rollback.
- `tests/Services/MasterExportServiceTest.php`: Excel & PDF generation with permission enforcement.
- `tests/Security/Milestone2SecurityTest.php`: CSRF, RBAC unit isolation, PII masking in audit logs, mass assignment protection.
- Milestone 1 Regression Test Suite (`vendor/bin/phpunit`).

### Manual & CLI Verification
1. Run `composer validate --strict` and `composer audit`.
2. Run full PHP syntax lint on all modified & newly added files.
3. Run PHPUnit test suite with PHP 8.2 binary.
