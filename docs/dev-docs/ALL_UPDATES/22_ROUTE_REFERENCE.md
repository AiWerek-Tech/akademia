# WMVAA Akademia — Complete Route Reference

## 1. Overview

Semua routes didefinisikan di `app/Config/Routes.php` (679 baris). Total: **200+ routes**.

---

## 2. Route Groups

### 2.1 Core Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/` | `Home::index` | - |
| GET | `/dashboard` | `Home::index` | - |
| GET | `/health` | `Health::index` | - |
| GET | `/system/runtime` | `SystemController::runtime` | `dev_only` |

### 2.2 Authentication Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/login` | `AuthController::login` | - |
| POST | `/login` | `AuthController::attemptLogin` | - |
| POST | `/logout` | `AuthController::logout` | - |
| GET | `/change-password` | `AuthController::changePassword` | - |
| POST | `/change-password` | `AuthController::attemptChangePassword` | - |

### 2.3 Context Switcher

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| POST | `/context/unit` | `ContextController::changeUnit` | - |
| POST | `/context/period` | `ContextController::changePeriod` | - |

### 2.4 Settings Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/settings/units` | `UnitController::index` | - |
| GET | `/settings/units/:id/edit` | `UnitController::edit` | - |
| POST | `/settings/units/:id` | `UnitController::update` | - |
| GET | `/settings/application` | `SettingsController::application` | `permission:settings.view` |
| POST | `/settings/application/save` | `SettingsController::saveApplication` | `permission:settings.manage` |
| POST | `/settings/application/save-maintenance` | `SettingsController::saveMaintenance` | `permission:settings.manage` |
| POST | `/settings/application/save-registration` | `SettingsController::saveRegistration` | `permission:settings.manage` |
| GET | `/settings/academic-operations` | `AcademicOperatingSettingsController::index` | `permission:academic_calendar.manage` |
| POST | `/settings/academic-operations` | `AcademicOperatingSettingsController::save` | `permission:academic_calendar.manage` |
| GET | `/settings/attendance` | `AttendanceSettingsController::index` | `permission:attendances.admin` |
| POST | `/settings/attendance` | `AttendanceSettingsController::save` | `permission:attendances.admin` |
| GET | `/settings/school-profile` | `SettingsController::schoolProfile` | `permission:settings.view` |
| POST | `/settings/school-profile` | `SettingsController::saveSchoolProfile` | `permission:settings.manage` |
| GET | `/settings/database` | `SettingsController::database` | `permission:settings.manage` |
| GET | `/settings/database/:table/export` | `SettingsController::databaseExport` | `permission:settings.manage` |
| POST | `/settings/database/:table/truncate` | `SettingsController::databaseTruncate` | `permission:settings.manage` |
| GET | `/settings/database/:table/detail` | `SettingsController::databaseTableDetail` | `permission:settings.manage` |
| POST | `/settings/database/save-backup` | `SettingsController::saveBackup` | `permission:settings.manage` |
| GET | `/settings/appearance` | `SettingsController::appearance` | `permission:settings.view` |
| POST | `/settings/appearance/save` | `SettingsController::saveAppearance` | `permission:settings.manage` |
| POST | `/settings/appearance/save-email` | `SettingsController::saveEmail` | `permission:settings.manage` |
| POST | `/settings/appearance/save-security` | `SettingsController::saveSecurity` | `permission:settings.manage` |

### 2.5 Academic Years & Periods

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/academic-years` | `AcademicYearController::index` | - |
| GET | `/academic-years/create` | `AcademicYearController::create` | - |
| POST | `/academic-years` | `AcademicYearController::store` | - |
| GET | `/academic-years/:id/edit` | `AcademicYearController::edit` | - |
| POST | `/academic-years/:id` | `AcademicYearController::update` | - |
| POST | `/academic-years/:id/activate` | `AcademicYearController::activate` | - |
| GET | `/academic-periods` | `AcademicPeriodController::index` | - |
| GET | `/academic-periods/create` | `AcademicPeriodController::create` | - |
| POST | `/academic-periods` | `AcademicPeriodController::store` | - |
| GET | `/academic-periods/:id` | `AcademicPeriodController::show` | - |
| GET | `/academic-periods/:id/edit` | `AcademicPeriodController::edit` | - |
| POST | `/academic-periods/:id` | `AcademicPeriodController::update` | - |
| POST | `/academic-periods/:id/activate` | `AcademicPeriodController::activate` | - |

### 2.6 Academic Calendar

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/academic-calendar` | `AcademicCalendarController::index` | `permission:academic_calendar.view` |
| POST | `/academic-calendar/generate` | `AcademicCalendarController::generate` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/preview` | `AcademicCalendarController::preview` | `permission:academic_calendar.manage` |
| GET | `/academic-calendar/:id/editor` | `AcademicCalendarController::editor` | `permission:academic_calendar.view` |
| POST | `/academic-calendar/:id/update-day` | `AcademicCalendarController::updateDay` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/reset-day` | `AcademicCalendarController::resetDay` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/events/store` | `AcademicCalendarController::storeEvent` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/events/:eid/delete` | `AcademicCalendarController::deleteEvent` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/events/:eid/update` | `AcademicCalendarController::updateEvent` | `permission:academic_calendar.manage` |
| GET | `/academic-calendar/:id/print` | `AcademicCalendarController::printCalendar` | `permission:academic_calendar.view` |
| POST | `/academic-calendar/:id/activate` | `AcademicCalendarController::activate` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/rebuild` | `AcademicCalendarController::rebuild` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/rules/store` | `AcademicCalendarController::storeRule` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/rules/:rid/delete` | `AcademicCalendarController::deleteRule` | `permission:academic_calendar.manage` |
| POST | `/academic-calendar/:id/rules/:rid/update` | `AcademicCalendarController::updateRule` | `permission:academic_calendar.manage` |

### 2.7 User & Role Management

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/users` | `UserController::index` | - |
| GET | `/users/create` | `UserController::create` | - |
| POST | `/users` | `UserController::store` | - |
| POST | `/users/provision-teachers` | `UserController::provisionTeachers` | - |
| GET | `/users/:id/edit` | `UserController::edit` | - |
| POST | `/users/:id` | `UserController::update` | - |
| POST | `/users/:id/reset-password` | `UserController::resetPassword` | - |
| GET | `/roles` | `RoleController::index` | - |
| GET | `/roles/:id/permissions` | `RoleController::permissions` | - |
| POST | `/roles/:id/permissions` | `RoleController::updatePermissions` | - |

### 2.8 Master Data Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/teachers` | `TeachersController::index` | - |
| GET | `/teachers/create` | `TeachersController::create` | - |
| POST | `/teachers` | `TeachersController::store` | - |
| GET | `/teachers/export` | `TeachersController::export` | - |
| GET | `/teachers/:id` | `TeachersController::show` | - |
| GET | `/teachers/:id/edit` | `TeachersController::edit` | - |
| POST | `/teachers/:id` | `TeachersController::update` | - |
| POST | `/teachers/:id/verify` | `TeachersController::verify` | - |
| GET | `/subjects` | `SubjectsController::index` | - |
| GET | `/subjects/create` | `SubjectsController::create` | - |
| POST | `/subjects` | `SubjectsController::store` | - |
| GET | `/subjects/export` | `SubjectsController::export` | - |
| GET | `/subjects/:id/edit` | `SubjectsController::edit` | - |
| POST | `/subjects/:id` | `SubjectsController::update` | - |
| GET | `/grade-levels` | `GradeLevelsController::index` | - |
| GET | `/grade-levels/create` | `GradeLevelsController::create` | - |
| POST | `/grade-levels/store` | `GradeLevelsController::store` | - |
| GET | `/grade-levels/:id/edit` | `GradeLevelsController::edit` | - |
| POST | `/grade-levels/:id` | `GradeLevelsController::update` | - |
| GET | `/classrooms` | `ClassroomsController::index` | - |
| GET | `/classrooms/create` | `ClassroomsController::create` | - |
| POST | `/classrooms` | `ClassroomsController::store` | - |
| POST | `/classrooms/store` | `ClassroomsController::store` | - |
| GET | `/classrooms/export` | `ClassroomsController::export` | - |
| GET | `/classrooms/copy-period` | `ClassroomsController::copyPeriodView` | - |
| POST | `/classrooms/copy-period` | `ClassroomsController::applyCopyPeriod` | - |
| GET | `/classrooms/promote` | `ClassroomsController::promoteView` | - |
| POST | `/classrooms/promote` | `ClassroomsController::applyPromote` | - |
| GET | `/classrooms/:id/students` | `ClassroomsController::students` | - |
| POST | `/classrooms/:id/students/assign` | `ClassroomsController::assignStudent` | - |
| POST | `/classrooms/:id/students/:sid/remove` | `ClassroomsController::removeStudent` | - |
| GET | `/classrooms/:id/edit` | `ClassroomsController::edit` | - |
| POST | `/classrooms/:id` | `ClassroomsController::update` | - |
| GET | `/rooms` | `RoomsController::index` | - |
| GET | `/rooms/create` | `RoomsController::create` | - |
| POST | `/rooms` | `RoomsController::store` | - |
| POST | `/rooms/store` | `RoomsController::store` | - |
| GET | `/rooms/export` | `RoomsController::export` | - |
| GET | `/rooms/:id/edit` | `RoomsController::edit` | - |
| POST | `/rooms/:id` | `RoomsController::update` | - |
| GET | `/students` | `StudentsController::index` | `permission:students.view` |
| POST | `/students` | `StudentsController::store` | `permission:students.manage` |
| POST | `/students/:id/update` | `StudentsController::update` | `permission:students.manage` |
| POST | `/students/:id/delete` | `StudentsController::delete` | `permission:students.manage` |

### 2.9 Import Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/imports/master` | `MasterImportController::index` | - |
| GET | `/imports/master/template/:type` | `MasterImportController::downloadTemplate` | - |
| POST | `/imports/master/upload` | `MasterImportController::upload` | - |
| GET | `/imports/master/:id` | `MasterImportController::showBatch` | - |
| POST | `/imports/master/:id/apply` | `MasterImportController::apply` | - |

### 2.10 IALOS Education Routes

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/education` | `EducationFoundationController::dashboard` | `permission:*` |
| GET | `/education/ksp` | `DigitalKspController::index` | `permission:ksp.view` |
| POST | `/education/ksp` | `DigitalKspController::store` | `permission:ksp.manage` |
| GET | `/education/ksp/:id` | `DigitalKspController::dashboard` | `permission:ksp.view` |
| POST | `/education/ksp/:id/transition` | `DigitalKspController::transition` | `permission:ksp.review` |
| POST | `/education/ksp/:id/sections/:sid` | `DigitalKspController::updateSection` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/context` | `DigitalKspController::context` | `permission:ksp.view` |
| POST | `/education/ksp/:id/context` | `DigitalKspController::storeContext` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/vision-goals` | `DigitalKspController::visionGoals` | `permission:ksp.view` |
| POST | `/education/ksp/:id/vision-goals` | `DigitalKspController::storeGoal` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/organization` | `DigitalKspController::organization` | `permission:ksp.view` |
| POST | `/education/ksp/:id/organization` | `DigitalKspController::storeOrganization` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/evaluation` | `DigitalKspController::evaluation` | `permission:ksp.view` |
| POST | `/education/ksp/:id/evaluation` | `DigitalKspController::storeEvaluation` | `permission:ksp.manage` |
| POST | `/education/ksp/:id/evaluation/:eid/actions` | `DigitalKspController::storeImprovementAction` | `permission:ksp.manage` |
| POST | `/education/ksp/:id/improvement-actions/:aid` | `DigitalKspController::updateImprovementAction` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/compliance` | `DigitalKspController::compliance` | `permission:ksp.view` |
| POST | `/education/ksp/:id/compliance` | `DigitalKspController::runCompliance` | `permission:ksp.review` |
| GET | `/education/ksp/:id/evidence` | `DigitalKspController::evidence` | `permission:ksp.view` |
| POST | `/education/ksp/:id/evidence` | `DigitalKspController::storeEvidence` | `permission:ksp.manage` |
| GET | `/education/ksp/:id/evidence/:eid/download` | `DigitalKspController::downloadEvidence` | `permission:ksp.view` |
| GET | `/education/ksp/:id/documents` | `DigitalKspController::documents` | `permission:ksp.view` |
| POST | `/education/ksp/:id/documents` | `DigitalKspController::generateDocument` | `permission:ksp.export` |
| GET | `/education/ksp/:id/documents/:did/download` | `DigitalKspController::downloadDocument` | `permission:ksp.view` |

### 2.11 Learning Outcomes, Objectives, Sequences, Packs

*Rute lengkap ada di Routes.php — semua mengikuti pola CRUD + workflow + sub-resource*

### 2.12 Lesson Plans

*Rute lengkap di Routes.php — termasuk CRUD, stages, activities, assessments, rubrics, clone, transition, validate, print, export*

### 2.13 Teaching Workspace

*Rute lengkap di Routes.php — termasuk today, session lifecycle, observations, attendance, reflection*

### 2.14 Assessment & Mastery

*Rute lengkap di Routes.php — termasuk CRUD, gradebook, mastery, interventions, policies, summative, narrative*

### 2.15 Cocurricular & Extracurricular

*Rute lengkap di Routes.php — termasuk CRUD, sessions, observations, evaluations, habits, checkins*

### 2.16 Reporting

*Rute lengkap di Routes.php — termasuk generate, lock, publish, narrative, portfolio, print, promotion*

### 2.17 Quality & AI Copilot

*Rute lengkap di Routes.php — termasuk reflections, supervisions, KSP eval, copilot*

### 2.18 Electives

*Rute lengkap di Routes.php — termasuk admin, student selection, wali kelas review*

### 2.19 Smart Analytics

*Rute lengkap di Routes.php — termasuk lineage, heatmap, trends, remedial, narrative drafter, rubric, differentiation, adaptive*

### 2.20 Sync API

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET/POST | `/api/v1/sync` | `Api\MobileSyncController::router` | - |
| GET/POST | `/api/sync` | `Api\MobileSyncController::router` | - |
| POST | `/api/v1/auth/login` | `Api\MobileSyncController::login` | - |
| GET | `/api/v1/sync/all` | `Api\MobileSyncController::syncAll` | - |
| POST | `/api/v1/sync/delta` | `Api\MobileSyncController::syncDelta` | - |
| POST | `/api/v1/storage/upload` | `Api\MobileSyncController::uploadFile` | - |
| GET | `/api/v1/health` | `Api\MobileSyncController::health` | - |
| GET | `/system/sync` | `SystemIntegrationController::index` | `permission:sync.view` |
| POST | `/system/sync/bump/:entity` | `SystemIntegrationController::bumpVersion` | `permission:sync.manage` |
| POST | `/system/sync/revoke/:id` | `SystemIntegrationController::revokeSession` | `permission:sync.manage` |

### 2.21 System Diagnostics

| Method | Route | Controller::Method | Filter |
|---|---|---|---|
| GET | `/system/diagnostics` | `SystemDiagnosticsController::index` | `permission:sync.view` |
| POST | `/system/diagnostics/purge-sessions` | `SystemDiagnosticsController::purgeSessions` | `permission:sync.manage` |
| GET | `/api/v1/system/diagnostics` | `SystemDiagnosticsController::apiHealthReport` | - |

---

## 3. Route Filter Summary

| Filter | Keterangan |
|---|---|
| `auth` | Autentikasi wajib |
| `permission:code` | Permission check (OR logic untuk multiple codes) |
| `dev_only` | Hanya untuk development |
| `guest` | Hanya untuk guest (belum login) |

---

## 4. Route Statistics

| Category | Count |
|---|---|
| **Core Routes** | ~4 |
| **Auth Routes** | ~5 |
| **Context Routes** | ~2 |
| **Settings Routes** | ~16 |
| **Academic Routes** | ~15 |
| **Calendar Routes** | ~15 |
| **User/Role Routes** | ~8 |
| **Master Data Routes** | ~40 |
| **Import Routes** | ~5 |
| **IALOS Education Routes** | ~30 |
| **Learning Routes** | ~40 |
| **Assignment Routes** | ~20 |
| **Schedule Routes** | ~20 |
| **Lesson Plan Routes** | ~20 |
| **Teaching Routes** | ~15 |
| **Assessment Routes** | ~20 |
| **Co/Extra Routes** | ~30 |
| **Reporting Routes** | ~15 |
| **Quality Routes** | ~15 |
| **Elective Routes** | ~20 |
| **Smart Routes** | ~10 |
| **Sync API Routes** | ~10 |
| **System Routes** | ~5 |
| **Total** | **~200+** |
