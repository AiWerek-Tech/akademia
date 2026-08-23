# WMVAA Akademia — Test Suite Reference

## 1. Overview

Aplikasi memiliki **79+ test files** di direktori `tests/database/` yang menguji fitur dari semua milestone dan phase.

### 1.1 Test Framework

| Komponen | Versi |
|---|---|
| **Framework** | PHPUnit 10.5 |
| **Database Tests** | CodeIgniter4 Database Test |
| **Faker** | fakerphp/faker ^1.9 |
| **VFS** | mikey179/vfsstream ^1.6 |

### 1.2 Test Execution

```bash
php spark test                    # Run all tests
php spark test --filter Auth      # Run specific test
phpunit tests/database/           # Run database tests only
```

---

## 2. Test Categories

### 2.1 Authentication & Security Tests

| Test File | Deskripsi |
|---|---|
| `AuthTest.php` | Basic authentication flow |
| `ExtendedAuthTest.php` | Extended auth scenarios |
| `ExtendedSecurityTest.php` | Security edge cases |
| `ExtendedRbacTest.php` | RBAC permission testing |
| `AssignmentSecurityTest.php` | Assignment module security |
| `CurriculumSecurityTest.php` | Curriculum module security |
| `EducationFoundationSecurityTest.php` | Education foundation security |
| `ScheduleSecurityTest.php` | Schedule module security |
| `KspSecurityTest.php` | KSP security |
| `UnitScopeSecurityTest.php` | Unit scope isolation security |

### 2.2 Core Infrastructure Tests

| Test File | Deskripsi |
|---|---|
| `CoreTablesTest.php` | Core database tables |
| `ExampleDatabaseTest.php` | Basic database connection |

### 2.3 Master Data Tests

| Test File | Deskripsi |
|---|---|
| `Milestone2Test.php` | Master data CRUD |
| `Milestone2AcceptanceTest.php` | Acceptance test |
| `Milestone2SeederTest.php` | Seeder verification |
| `TeacherAccountProvisioningTest.php` | Teacher account provisioning |
| `TeacherScheduleSubstitutionTest.php` | Teacher substitution |
| `TeacherScheduleSubstitutionUiTest.php` | Substitution UI |
| `TeacherSubstitutionScheduleRepairTest.php` | Substitution repair |
| `ExtendedUserManagementTest.php` | User management |

### 2.4 Curriculum Tests

| Test File | Deskripsi |
|---|---|
| `Milestone3AcceptanceTest.php` | Curriculum acceptance |
| `CurriculumImportTemplateTest.php` | Import template |
| `CurriculumPlanningServiceTest.php` | Planning settings |
| `CurriculumCoverageTest.php` | Coverage analysis |
| `LearningOutcomeTest.php` | Learning outcomes |
| `LearningObjectiveTest.php` | Learning objectives |
| `LearningObjectiveAdaptationTest.php` | Objective adaptation |
| `LearningSequenceTest.php` | ATP/sequences |
| `LearningPackTest.php` | Learning packs |
| `SubjectLearningPackEngineTest.php` | Pack engine |
| `GraduateProfileDimensionTest.php` | Graduate profile |
| `RegulationRegistryTest.php` | Regulations |
| `EducationFoundationMigrationTest.php` | Migration test |
| `EducationFoundationSeederTest.php` | Seeder test |
| `EducationFoundationImportTest.php` | Import test |
| `EducationControlCenterTest.php` | Control center |

### 2.5 Digital KSP Tests

| Test File | Deskripsi |
|---|---|
| `DigitalKspTest.php` | KSP CRUD |
| `KspComplianceTest.php` | Compliance checking |
| `KspDocumentGeneratorTest.php` | Document generation |
| `KspEvidenceTest.php` | Evidence management |
| `KspBrowserAcceptanceTest.php` | Browser acceptance |

### 2.6 Assignment & Workload Tests

| Test File | Deskripsi |
|---|---|
| `Milestone4AcceptanceTest.php` | Assignment acceptance |
| `Milestone4MigrationTest.php` | Migration test |
| `Milestone4SeederTest.php` | Seeder test |
| `AssignmentVersionTest.php` | Version management |
| `AssignmentMatrixTest.php` | Matrix generation |
| `AssignmentImportTest.php` | Import pipeline |
| `TeachingAssignmentTest.php` | Teaching assignment |
| `WorkloadCalculationTest.php` | Workload calculation |
| `WorkloadPolicyTest.php` | Workload policies |
| `AdditionalDutyTest.php` | Additional duties |
| `FourJpBlockPatternTest.php` | 4-JP block pattern |
| `TeamTeachingTest.php` | Team teaching |

### 2.7 Scheduling Tests

| Test File | Deskripsi |
|---|---|
| `Milestone5MigrationTest.php` | Migration test |
| `Milestone5SeederTest.php` | Seeder test |
| `ScheduleGeneratorTest.php` | Auto-generation |
| `ScheduleConflictDetectionTest.php` | Conflict detection |
| `ScheduleVersionTest.php` | Version management |
| `ScheduleImportExportTest.php` | Import/Export |
| `SchedulingAvailabilityPrecedenceTest.php` | Availability precedence |

### 2.8 Lesson Plan & Teaching Tests

| Test File | Deskripsi |
|---|---|
| `LessonPlanEngineTest.php` | Lesson plan engine |
| `TeachingWorkspaceEngineTest.php` | Teaching workspace |

### 2.9 Assessment & Mastery Tests

| Test File | Deskripsi |
|---|---|
| `AssessmentMasteryEngineTest.php` | Mastery engine |

### 2.10 Co/Extracurricular Tests

| Test File | Deskripsi |
|---|---|
| `CocurricularEngineTest.php` | Cocurricular engine |
| `ExtracurricularEngineTest.php` | Extracurricular engine |

### 2.11 Reporting & Quality Tests

| Test File | Deskripsi |
|---|---|
| `ReportingEngineTest.php` | Reporting engine |
| `QualityEngineTest.php` | Quality engine |

### 2.12 Sync & Integration Tests

| Test File | Deskripsi |
|---|---|
| `UniversalSyncEngineTest.php` | Universal sync |
| `ExtendedAuditTest.php` | Audit trail |

### 2.13 Integration & Acceptance Tests

| Test File | Deskripsi |
|---|---|
| `EndToEndLifecycleTest.php` | End-to-end lifecycle |
| `Milestone2AcceptanceTest.php` | Milestone 2 acceptance |
| `Milestone3AcceptanceTest.php` | Milestone 3 acceptance |
| `Milestone4AcceptanceTest.php` | Milestone 4 acceptance |
| `ExtendedAcademicTest.php` | Extended academic features |
| `ImportExportAcceptanceTest.php` | Import/Export acceptance |
| `IntegratedAttendanceWorkflowTest.php` | Attendance workflow |
| `AcademicCalendarRuleEngineTest.php` | Calendar rule engine |
| `PortalUnitScopeTest.php` | Portal unit scope |
| `WaliKelasElectiveScopeTest.php` | Wali kelas elective scope |
| `OperationalRolePermissionTest.php` | Operational role permissions |
| `InspectionDataSeederTest.php` | Inspection data seeder |
| `Phase12PilotDataSeederTest.php` | Phase 12 pilot data |

---

## 3. Test Support

| File | Deskripsi |
|---|---|
| `tests/_support/` | Test support classes |
| `tests/README.md` | Test documentation |

---

## 4. Test Configuration

### 4.1 phpunit.xml.dist

Konfigurasi PHPUnit untuk menjalankan semua test suite.

### 4.2 Database Testing

- Menggunakan database testing configuration
- Automatic migration before tests
- Transaction rollback after each test

---

## 5. Quality Gates

| Gate | Status |
|---|---|
| Composer validate | ✅ |
| Full test suite | ✅ (79+ tests) |
| Security audit | ✅ |
| Privacy audit | ✅ |
| Route security tests | ✅ |
| Browser acceptance tests | ✅ |
