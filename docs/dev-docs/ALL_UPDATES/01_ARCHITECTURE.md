# WMVAA Akademia — Architecture & System Design

## 1. Architectural Pattern

Aplikasi menggunakan **Layered Architecture** berdasarkan MVC pattern dari CodeIgniter 4:

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT LAYER                         │
│              Web Browser / PWA / Mobile App             │
│    Bootstrap 5 + Lucide Icons + Service Worker          │
├─────────────────────────────────────────────────────────┤
│                  FILTER LAYER                           │
│    AuthFilter → PermissionFilter → UnitAccessFilter     │
│    GuestFilter → PasswordChangeRequiredFilter           │
├─────────────────────────────────────────────────────────┤
│                 CONTROLLER LAYER                        │
│    Thin Controllers — HTTP parsing, validation trigger  │
│    Response formatting, session management              │
│    60+ controllers di app/Controllers/                  │
├─────────────────────────────────────────────────────────┤
│                  SERVICE LAYER                          │
│    Business Logic Validators, Calculation Engines       │
│    Schedule heuristics, Piket fairness, Workload limits │
│    100+ service files di app/Services/                  │
├─────────────────────────────────────────────────────────┤
│                  MODEL / REPO LAYER                     │
│    Standard CI4 Models for schema definition            │
│    CRUD and custom queries via Query Builder            │
│    100+ model files di app/Models/                      │
├─────────────────────────────────────────────────────────┤
│                  DATABASE LAYER                         │
│              MySQL / MariaDB (MySQLi Driver)            │
│    60+ migration files, 18 seeder files                 │
└─────────────────────────────────────────────────────────┘
```

### 1.1 Separation of Concerns

| Layer | Tanggung Jawab | File Lokasi |
|---|---|---|
| **Controller** | HTTP request parsing, response formatting, validation trigger, session management | `app/Controllers/*.php` |
| **Service** | Business logic, validators, calculation engines, workflow orchestration | `app/Services/*.php` |
| **Model** | Schema definition, CRUD operations, custom queries | `app/Models/*.php` |
| **View** | HTML rendering, template composition | `app/Views/**/*.php` |
| **Filter** | Request interception, authentication, authorization | `app/Filters/*.php` |
| **Config** | Application settings, routing, RBAC definitions | `app/Config/*.php` |

### 1.2 Core Constraints

1. **Strict Separation of Units**: Data SMP dan SMA dibedakan via `unit_id` di database
2. **Unified Data Structures**: Master Guru dan Mata Pelajaran bersifat global (lintas unit)
3. **Controller Tidak Boleh Logika Bisnis**: Semua logika bisnis harus di Service layer
4. **No Direct SQL in Controllers/Views**: Hanya boleh via Query Builder atau Model

---

## 2. Routing System

### 2.1 Route Configuration

Routes didefinisikan di `app/Config/Routes.php` (679 baris). Semua routes menggunakan filter untuk otorisasi:

```php
// Pattern dasar
$routes->get('path', 'Controller::method');
$routes->post('path', 'Controller::method', ['filter' => 'permission:code']);

// Group routes dengan shared filter
$routes->group('schedules', ['filter' => 'permission:schedules.view'], function ($routes) {
    $routes->get('/', 'SchedulesController::index');
    // ...
});
```

### 2.2 Route Naming Convention

| Pattern | Contoh | Keterangan |
|---|---|---|
| `GET /module` | `/teachers` | Index/list page |
| `GET /module/create` | `/teachers/create` | Form tambah baru |
| `POST /module` | `/teachers` (POST) | Proses simpan |
| `GET /module/:id` | `/teachers/5` | Detail page |
| `GET /module/:id/edit` | `/teachers/5/edit` | Form edit |
| `POST /module/:id` | `/teachers/5` (POST) | Proses update |
| `POST /module/:id/action` | `/teachers/5/verify` | Aksi khusus |

---

## 3. Filter Pipeline

Request melewati pipeline filter berikut:

### 3.1 AuthFilter (`app/Filters/AuthFilter.php`)

- Memverifikasi session `logged_in`
- Memeriksa status `is_active` dan `deleted_at` user
- Mendeteksi password rotation (jika password diubah setelah login)
- Mengatur flag `must_change_password` dan `must_change_username`
- Redirect ke `/login` jika session tidak valid

### 3.2 PermissionFilter (`app/Filters/PermissionFilter.php`)

- Menerima daftar permission codes sebagai argument (dipisah koma atau pipe)
- Memanggil `UserModel::getPermissions()` untuk mendapatkan semua permission user
- Mendukung OR logic: jika user memiliki salah satu dari permission yang diminta, akses diberikan
- Mengembalikan 403 JSON untuk AJAX atau HTML error page untuk request biasa

### 3.3 UnitAccessFilter (`app/Filters/UnitAccessFilter.php`)

- Memverifikasi `active_unit_id` sesuai dengan unit yang diakses user
- Menggunakan `UnitScopeService::accessibleUnitIds()` untuk validasi
- Redirect ke dashboard jika unit tidak valid

### 3.4 GuestFilter

- Membatasi akses halaman login/registration untuk user yang sudah login

### 3.5 PasswordChangeRequiredFilter

- Memaksa user mengubah password jika flag `must_change_password` aktif

### 3.6 DevelopmentOnlyFilter

- Membatasi akses route tertentu hanya untuk environment development

---

## 4. Controller Registry

### 4.1 Core Controllers

| Controller | Fungsi |
|---|---|
| `BaseController` | Base class untuk semua controllers |
| `Home` | Dashboard utama |
| `Health` | Health check endpoint |
| `AuthController` | Login, logout, ganti password |
| `ContextController` | Switch unit & period context |
| `UserController` | User management |
| `RoleController` | Role & permission management |

### 4.2 Master Data Controllers

| Controller | Fungsi |
|---|---|
| `TeachersController` | CRUD guru, export, verifikasi |
| `StudentsController` | CRUD peserta didik |
| `SubjectsController` | CRUD mata pelajaran |
| `ClassroomsController` | CRUD rombel, assign siswa, promote, copy period |
| `GradeLevelsController` | CRUD tingkat kelas & fase |
| `RoomsController` | CRUD ruang sekolah |
| `EducationFoundationController` | Control center IALOS, regulasi, kurikulum sumber, profil lulusan |
| `DigitalKspController` | KSP Digital (Komite Sekolah Pendidikan) |
| `MasterImportController` | Import staging pipeline untuk master data |
| `DuplicateReviewController` | Review & resolve duplikasi guru |
| `RoutineActivitiesController` | Kegiatan rutin sekolah |
| `DutiesController` | Tugas tambahan guru |

### 4.3 Curriculum & Planning Controllers

| Controller | Fungsi |
|---|---|
| `CurriculumController` | Struktur kurikulum, versioning |
| `CurriculumImportController` | Import kurikulum dari Excel |
| `CurriculumMatrixController` | Matrix interaktif kurikulum (sel grade × mapel) |
| `SubjectLearningPackController` | Paket pembelajaran (units, concepts, activities, resources) |
| `ElectivesController` | Pemilihan mata pelajaran (admin side) |
| `ElectiveSelectionsController` | Pemilihan mata pelajaran (student/wali kelas side) |
| `AcademicCalendarController` | Kalender pendidikan, rule engine, event management |
| `AcademicCalendarController` | Generate, preview, edit, activate, rebuild kalender |

### 4.4 Assignment & Scheduling Controllers

| Controller | Fungsi |
|---|---|
| `AssignmentsController` | Penugasan mengajar, matrix, workflow |
| `AssignmentsImportController` | Import penugasan dari Excel |
| `AssignmentDocumentsController` | SK Pembagian Tugas (集体 & individual) |
| `WorkloadsController` | Beban kerja guru, kebijakan |
| `SchedulesController` | Jadwal pelajaran, workflow |
| `ScheduleEditorController` | Editor jadwal manual (grid) |
| `ScheduleGeneratorController` | Generator jadwal otomatis |
| `ScheduleConflictsController` | Audit konflik jadwal |
| `ScheduleAvailabilityController` | Ketersediaan guru |
| `ScheduleConstraintsController` | Bobot constraint |
| `ScheduleImportsController` | Import jadwal dari Excel |
| `ScheduleReportsController` | Laporan jadwal (rombel, guru, unit) |
| `DutySchedulesController` | Jadwal piket guru |
| `TeacherScheduleSubstitutionsController` | Substitusi guru & repair |

### 4.5 Lesson Plan & Teaching Controllers

| Controller | Fungsi |
|---|---|
| `LessonPlanController` | Rencana pembelajaran (RPP), desain, tahapan, aktivitas, cetak |
| `TeachingWorkspaceController` | Ruang mengajar harian, sesi, observasi, refleksi, presensi |
| `TeacherPortalController` | Portal personal guru (jadwal, beban, SK, piket) |
| `TeacherElectivesController` | Portal mapel pilihan guru |
| `TeacherAttendanceController` | Portal absensi guru |
| `HomeroomPortalController` | Portal wali kelas (kelas binaan) |

### 4.6 Assessment & Mastery Controllers

| Controller | Fungsi |
|---|---|
| `AssessmentController` | Asesmen, gradebook, mastery, intervensi, narasi |
| `SummativeController` | Pengolahan sumatif |

### 4.7 Co/Extracurricular Controllers

| Controller | Fungsi |
|---|---|
| `CocurricularController` | Program kokurikuler, sesi, observasi, bukti, evaluasi, kebiasaan 7KAIH |
| `ExtracurricularController` | Program ekstrakurikuler, anggota, sesi, presensi, kompetensi, evaluasi, sertifikat |

### 4.8 Reporting & Quality Controllers

| Controller | Fungsi |
|---|---|
| `ReportingController` | Laporan semester, rapor, portofolio, narasi, cetak, kenaikan kelas |
| `QualityController` | Dashboard mutu, refleksi, supervisi, evaluasi KSP, AI Copilot, laporan mutu |

### 4.9 Analytics & Smart Controllers

| Controller | Fungsi |
|---|---|
| `SmartAnalyticsController` | Peta lineage, mastery heatmap, tren refleksi, remedial package, narasi drafter, rubric generator, differentiation assistant |

### 4.10 System Controllers

| Controller | Fungsi |
|---|---|
| `SettingsController` | Pengaturan sekolah, tampilan, database, aplikasi |
| `AcademicOperatingSettingsController` | Pengaturan operasional akademik |
| `AttendanceSettingsController` | Pengaturan absensi |
| `SystemIntegrationController` | Sync mobile dashboard (admin) |
| `SystemDiagnosticsController` | Diagnostik sistem, health report |

### 4.11 API Controllers

| Controller | Fungsi |
|---|---|
| `Api\MobileSyncController` | Central router untuk mobile sync API (login, sync_all, sync_delta, upload_file, health) |

---

## 5. Service Layer

### 5.1 Service Organization

Semua bisnis logic terkonsentrasi di `app/Services/` dengan lebih dari 100 service files:

| Kategori | Services |
|---|---|
| **Master Data** | `TeacherService`, `SubjectService`, `ClassroomService`, `RoomService`, `GradeLevelService` |
| **Curriculum** | `CurriculumVersionService`, `CurriculumStructureService`, `CurriculumPlanningService`, `CurriculumImportService`, `CurriculumExportService`, `CurriculumValidationService`, `CurriculumWorkflowService`, `CurriculumCoverageService`, `CurriculumLineageService` |
| **Learning** | `LearningOutcomeService`, `LearningObjectiveService`, `LearningSequenceService`, `LearningPackService`, `SubjectLearningPackEngineService` |
| **Assignment** | `AssignmentWorkflowService`, `AssignmentValidationService`, `AssignmentMatrixService`, `AssignmentImportService`, `AssignmentDocumentService` |
| **Workload** | `TeacherWorkloadCalculationService`, `TeamTeachingPolicyService` |
| **Scheduling** | `DeterministicGreedyScheduleGenerator`, `ScheduleConflictDetectionService`, `ScheduleScoringService`, `ScheduleCapacityService`, `ScheduleWorkflowService`, `ScheduleImportService`, `ScheduleExportService` |
| **Room/Availability** | `RoomAvailabilityService`, `ClassroomAvailabilityService`, `TeacherAvailabilityService` |
| **Lesson Plan** | `LessonPlanService`, `LessonPlanDocxService`, `LessonPlanPdfService` |
| **Teaching** | `TeachingWorkspaceService`, `AttendanceService` |
| **Assessment** | `AssessmentService`, `MasteryService`, `MasteryHeatmapService`, `SummativeProcessingService`, `NarrativeService`, `RemediationPackagerService` |
| **Elective** | `ElectiveSelectionService`, `ElectiveConflictMatrixService`, `ElectiveComplianceService`, `ElectiveExportService`, `ElectivePromotionCatalog` |
| **KSP** | `DigitalKspService`, `KspComplianceService`, `KspEvidenceService`, `KspDocumentGeneratorService` |
| **Education** | `EducationFoundationService`, `EducationFoundationImportService`, `EducationControlCenterService`, `RegulationRegistryService` |
| **Reporting** | `ReportingService` |
| **Quality** | `QualityService`, `ReflectionTrendsService`, `RubricGeneratorService`, `DifferentiationService`, `AdaptiveModeService` |
| **Cocurricular** | `CocurricularService` |
| **Extracurricular** | `ExtracurricularService` |
| **Sync/API** | `UniversalSyncService` |
| **System** | `SettingsService`, `AuditService`, `FeatureFlagService`, `SystemDiagnosticsService`, `UuidService` |
| **Teacher Management** | `TeacherService`, `TeacherDuplicateDetectionService`, `TeacherMergeService`, `TeacherProfileCompletenessService`, `TeacherAccountProvisioningService`, `TeacherDutyScheduleService`, `TeacherElectivePortalService`, `TeacherScheduleReadService`, `TeacherScheduleSubstitutionService`, `TeacherScheduleSubstitutionManagementService`, `TeacherSubstitutionScheduleRepairService` |
| **Unit & Scope** | `UnitScopeService`, `PortalUnitScopeService`, `WaliKelasAccessService` |
| **Sync/Import** | `MasterImportService`, `MasterExportService` |

### 5.2 Key Service Patterns

- **WorkflowService**: Mengelola state machine untuk workflow (draft → validated → reviewed → approved → locked)
- **ImportService**: Menangani staging pipeline (upload → validate → preview → apply)
- **ValidationService**: Menjalankan aturan bisnis sebelum transaksi
- **CalculationService**: Menghitung metrik (beban kerja, FTE, coverage)

---

## 6. View Layer

### 6.1 Template Engine

Menggunakan native CI4 template engine dengan:

- **Master Layout**: `app/Views/layouts/admin.php` (1043 baris)
- **Section System**: `<?= $this->section('main_content') ?>` dan `<?= $this->renderSection('main_content') ?>`
- **Partial Views**: Komponen reusable (form rows, headers)

### 6.2 View Organization

```
app/Views/
├── layouts/
│   └── admin.php           # Master layout (sidebar, navbar, footer)
├── dashboard.php           # Dashboard desktop
├── dashboard_mobile.php    # Dashboard mobile
├── auth/                   # Login pages
├── academic_calendar/      # Kalender pendidikan views
├── academic_periods/       # Periode akademik views
├── academic_years/         # Tahun akademik views
├── assessment/             # Asesmen & mastery views
├── assignments/            # Penugasan & SK views
├── attendances/            # Absensi & jurnal views
├── classrooms/             # Rombel views
├── cocurricular/           # Kokurikuler views
├── curriculum/             # Kurikulum & import views
│   ├── imports/            # Import kurikulum views
│   ├── matrix/             # Matrix kurikulum views
│   ├── reconciliation/     # Rekonstruksi views
│   └── versions/           # Versioning views
├── duplicates/             # Duplikat review views
├── duties/                 # Tugas tambahan views
├── education_foundation/   # IALOS Education views
├── electives/              # Pemilihan mapel views
├── errors/                 # Error pages (403, 404, 500)
├── extracurricular/        # Ekstrakurikuler views
├── grade_levels/           # Tingkat kelas views
├── homeroom_portal/        # Portal wali kelas views
├── imports/                # Import master data views
├── ksp/                    # KSP Digital views
├── learning_packs/         # Paket pembelajaran views
├── lesson_plans/           # Rencana pembelajaran views
├── quality/                # Quality & AI Copilot views
├── reporting/              # Rapor & portofolio views
├── roles/                  # Role management views
├── rooms/                  # Ruang sekolah views
├── routine_activities/     # Kegiatan rutin views
├── schedules/              # Jadwal pelajaran views
│   ├── reports/            # Laporan jadwal views
│   └── substitutions/      # Substitusi guru views
├── settings/               # Settings views
├── smart/                  # Smart analytics views
├── students/               # Peserta didik views
├── subjects/               # Mata pelajaran views
├── system/                 # System management views
├── teachers/               # Guru views
├── teacher_portal/         # Portal guru views
├── teaching/               # Teaching workspace views
├── units/                  # Unit sekolah views
├── users/                  # User management views
└── workloads/              # Beban kerja views
```

---

## 7. Data Flow Pattern

### 7.1 Standard Request Flow

```
HTTP Request
    ↓
AuthFilter (session check)
    ↓
PermissionFilter (RBAC check)
    ↓
UnitAccessFilter (unit scope check)
    ↓
Controller (parsing, validation)
    ↓
Service (business logic)
    ↓
Model (database query)
    ↓
Response (JSON / HTML / Redirect)
```

### 7.2 Import Workflow Pattern

```
Upload Excel/CSV → Staging Table → Validation → Preview → Apply → Audit Log
     ↓                 ↓               ↓            ↓         ↓          ↓
 MasterImportController  ImportBatch  ValidationService  Show Batch  Apply   AuditService
```

### 7.3 Workflow State Machine

Banyak entitas mengikuti pola workflow:

```
draft → validated → reviewed → approved → locked
                                      ↓
                              (revisi) → draft (new version)
```

Entities yang menggunakan workflow:
- Curriculum Versions
- Learning Sequences (ATP)
- Subject Learning Packs
- Lesson Plans
- Teaching Assignments
- Schedule Versions
- Assessments
- Cocurricular Programs
- Extracurricular Programs
- KSP Documents

---

## 8. Audit Trail

Semua perubahan data signifikan dicatat ke tabel `audit_logs` melalui `AuditService::log()`:

| Parameter | Deskripsi |
|---|---|
| `module` | Nama modul (contoh: `teachers`, `curriculum`, `assignments`) |
| `action` | Tipe aksi (contoh: `create`, `update`, `delete`, `workflow_approve`) |
| `entity_type` | Tipe entitas (contoh: `Teacher`, `CurriculumVersion`) |
| `entity_id` | ID entitas yang diubah |
| `before_state` | State sebelum perubahan (JSON) |
| `after_state` | State setelah perubahan (JSON) |
| `reason` | Catatan/alasan perubahan |
| `user_id` | ID pengguna yang melakukan |
| `ip_address` | IP address request |
| `created_at` | Timestamp perubahan |
