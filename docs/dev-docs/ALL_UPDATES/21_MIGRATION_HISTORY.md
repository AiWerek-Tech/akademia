# WMVAA Akademia — Database Migration History

## 1. Overview

Aplikasi menggunakan **60+ migration files** di `app/Database/Migrations/` yang diurutkan secara kronologis berdasarkan timestamp.

---

## 2. Migration Timeline

### Milestone 0: Core Infrastructure

| Migration | Deskripsi |
|---|---|
| `20260721000000_CreateCoreTables.php` | Users, roles, permissions, user_roles, user_unit_access, login_attempts, school_units, academic_years, academic_periods |

### Milestone 1: Master Data

| Migration | Deskripsi |
|---|---|
| `20260721000001_CreateMilestone2MasterTables.php` | Teachers, subjects, grade_levels, classrooms, rooms, teacher_identifiers, teacher_qualifications |

### Milestone 2: Curriculum Structures

| Migration | Deskripsi |
|---|---|
| `20260721100000_CreateMilestone3Tables.php` | Curriculum versions, curriculum structures, workload_policies |
| `20260721200000_AddCurriculumScopeUniqueKeys.php` | Unique keys untuk curriculum scope |
| `20260722200000_FixCurriculumScopeUniqueKeys.php` | Fix unique keys |

### Milestone 3: Assignments & Workloads

| Migration | Deskripsi |
|---|---|
| `20260722100000_CreateMilestone4Tables.php` | Teaching assignments, assignment groups, workload snapshots |
| `20260722200000_AddGradeLevelImportPermission.php` | Grade level import permission |
| `20260722210000_RemoveAcademicPeriodWorkflowPermissions.php` | Cleanup permissions |

### Milestone 4: Scheduling

| Migration | Deskripsi |
|---|---|
| `20260722300000_CreateMilestone5Tables.php` | Schedule versions, entries, days, slots, conflicts, locks, requirements |
| `20260726000000_FixSchedulingTeacherReferences.php` | Fix teacher FK references |
| `20260726010000_AddPlanningProductionFields.php` | Planning production fields |

### Curriculum Planning Enhancement

| Migration | Deskripsi |
|---|---|
| `20260724000000_CreateCurriculumPlanningSettings.php` | curriculum_planning_settings table |

### Elective Planning

| Migration | Deskripsi |
|---|---|
| `20260727000000_CreateElectivePlanningTables.php` | Elective periods, offerings, students |
| `20260727010000_CompleteElectiveSelectionWorkflow.php` | Submissions, choices, reviews, change requests |
| `20260727020000_AddArtCultureCraftSelectionType.php` | Art/culture/craft selection type |

### Curriculum Planning Settings Extensions

| Migration | Deskripsi |
|---|---|
| `20260728000000_AddMinutesPerJpToCurriculumPlanningSettings.php` | Minutes per JP |
| `20260728010000_AddDailyJpCapacitiesToCurriculumPlanningSettings.php` | Daily JP capacities |
| `20260729050000_AddStartTimeJp1ToCurriculumPlanningSettings.php` | Start time JP1 |

### Routine Activities

| Migration | Deskripsi |
|---|---|
| `20260729000000_CreateSchoolRoutineActivitiesTable.php` | Routine activities table |
| `20260729010000_AddTeachingLoadToRoutineActivities.php` | Teaching load field |
| `20260729020000_EnhanceSchoolRoutineActivities.php` | Enhanced fields |
| `20260729030000_AddPlacementZoneToRoutineActivities.php` | Placement zone |
| `20260729040000_MakeScheduleRequirementIdNullable.php` | Nullable FK |

### Multi-Role & Permissions

| Migration | Deskripsi |
|---|---|
| `20260729100000_AddMultiRoleEntityLinkage.php` | Multi-role entity linkage |
| `20260729110000_NormalizeMorningRoutinePlacement.php` | Normalize placement |
| `20260729113000_RestoreAcademicMorningRoutinePlacement.php` | Restore academic placement |
| `20260730100000_SeparateTeacherAndHomeroomPermissions.php` | Separate permissions |

### Schedule Enhancements

| Migration | Deskripsi |
|---|---|
| `20260802010000_AddScheduleConflictFingerprint.php` | Conflict fingerprint |

### Teacher Duty Schedules

| Migration | Deskripsi |
|---|---|
| `20260803000000_CreateTeacherDutySchedulesTable.php` | Duty schedules table |

### Teacher Schedule & Substitutions

| Migration | Deskripsi |
|---|---|
| `20260809000000_AddTeacherSchedulePresentationFields.php` | Presentation fields |
| `20260809010000_CreateTeacherScheduleSubstitutions.php` | Substitutions table |
| `20260809020000_CreateTeacherSubstitutionRepairCandidates.php` | Repair candidates |
| `20260809030000_AddPersonalPortalProductPermissions.php` | Portal permissions |
| `20260809040000_BackfillPersonalRoleEntityLinks.php` | Backfill entity links |
| `20260809050000_RemoveLegacyPersonalRoleAdminGrants.php` | Remove legacy grants |
| `20260809060000_AddRequiredUsernameChange.php` | Required username change |

### Teacher Elective Portal

| Migration | Deskripsi |
|---|---|
| `20260810000000_AddTeacherElectivePortalPermission.php` | Elective portal permission |

### Student Attendance

| Migration | Deskripsi |
|---|---|
| `20260810100000_CreateStudentAttendancesTables.php` | Student attendance tables |

### Academic Calendar

| Migration | Deskripsi |
|---|---|
| `20260811140000_CreateAcademicCalendarTables.php` | Calendar tables |
| `20260811170000_EnhanceAcademicCalendarRuleEngine.php` | Rule engine |
| `20260811171000_BackfillAcademicCalendarRules.php` | Backfill rules |
| `20260811172000_NormalizeAcademicCalendarRulePriority.php` | Normalize priorities |
| `20260811173000_EnforceAcademicCalendarScopeUniqueness.php` | Scope uniqueness |
| `20260811174000_RevalidateAcademicCalendars.php` | Revalidate |
| `20260811175000_AdjustCalendarEventsToWorkingDays.php` | Adjust events |

### Attendance Hardening

| Migration | Deskripsi |
|---|---|
| `20260811160000_HardenAttendancePortalPermissions.php` | Harden permissions |
| `20260811161000_HardenOperationalRolePermissions.php` | Operational role hardening |
| `20260811162000_CompleteRolePortalBaselines.php` | Complete baselines |

### Academic Operating Settings

| Migration | Deskripsi |
|---|---|
| `20260811180000_CreateAcademicOperatingSettings.php` | Operating settings |
| `20260811181000_SyncCalendarsWithOperatingSettings.php` | Sync calendars |

### Integrated Attendance Journal

| Migration | Deskripsi |
|---|---|
| `20260811182000_UpgradeIntegratedAttendanceJournal.php` | Upgrade journal |
| `20260811183000_EnforceAttendanceSessionUniqueness.php` | Session uniqueness |

### Phase 3: Education Foundation & KSP

| Migration | Deskripsi |
|---|---|
| `20260816000000_CreateEducationFoundationTables.php` | Regulations, sources, outcomes, objectives, sequences, packs |
| `20260816010000_CreateDigitalKspTables.php` | KSP tables |
| `20260816011000_RenameApplicationToIalosEducation.php` | Rename to IALOS |
| `20260817000000_ExtendKspImprovementActions.php` | KSP improvement actions |
| `20260817010000_CreateKspPhase2ClosureTables.php` | KSP closure tables |
| `20260817020000_FixKspComplianceForeignKeyActions.php` | Fix FK actions |

### Phase 3: Subject Learning Packs

| Migration | Deskripsi |
|---|---|
| `20260818000000_CreatePhase3SubjectLearningPackTables.php` | Learning pack tables |

### Phase 4: Lesson Plans

| Migration | Deskripsi |
|---|---|
| `20260819000000_CreatePhase4LessonPlanTables.php` | Lesson plan tables |
| `20260819100000_CreatePhase4Iteration2Tables.php` | Iteration 2 tables |

### Phase 5: Teaching Workspace

| Migration | Deskripsi |
|---|---|
| `20260819100000_CreatePhase5TeachingWorkspaceTables.php` | Teaching workspace tables |

### Phase 6: Assessment & Mastery

| Migration | Deskripsi |
|---|---|
| `20260820100000_CreatePhase6AssessmentMasteryTables.php` | Assessment & mastery tables |
| `20260821000000_Phase6AssessmentRefinements.php` | Assessment refinements |
| `20260822000000_CreateSummativeResults.php` | Summative results |
| `20260823000000_CreateStudentNarrativeDraftsTable.php` | Narrative drafts |

### Phase 7: Cocurricular

| Migration | Deskripsi |
|---|---|
| `20260823000000_CreatePhase7CocurricularTables.php` | Cocurricular tables |

### Phase 8: Extracurricular

| Migration | Deskripsi |
|---|---|
| `20260824000000_CreatePhase8ExtracurricularTables.php` | Extracurricular tables |

### Phase 9: Reporting

| Migration | Deskripsi |
|---|---|
| `20260825000000_CreatePhase9ReportingTables.php` | Reporting tables |

### Phase 10: Quality & AI Copilot

| Migration | Deskripsi |
|---|---|
| `20260826000000_CreatePhase10QualityCopilotTables.php` | Quality & AI tables |

### Phase 11: Universal Sync

| Migration | Deskripsi |
|---|---|
| `20260828000000_CreatePhase11UniversalSyncTables.php` | Sync tables |

### Phase 12: Settings

| Migration | Deskripsi |
|---|---|
| `20260829000000_CreateSettingsTables.php` | System settings, feature flags |

---

## 3. Seeders

| Seeder | Deskripsi |
|---|---|
| `SuperAdminSeeder.php` | Buat akun super admin |
| `CoreSeeder.php` | Seed data core (roles, permissions) |
| `CheckLoginSeeder.php` | Seed data login check |
| `ResetAdminPasswordSeeder.php` | Reset password admin |
| `SchoolMasterDataSeeder.php` | Seed master data sekolah |
| `Milestone2MasterSeeder.php` | Seed master data milestone 2 |
| `Milestone3CurriculumSeeder.php` | Seed kurikulum milestone 3 |
| `Milestone4Seeder.php` | Seed milestone 4 |
| `Milestone5Seeder.php` | Seed milestone 5 |
| `CurriculumDataSeeder.php` | Seed data kurikulum |
| `EducationFoundationSeeder.php` | Seed education foundation |
| `DigitalKspSeeder.php` | Seed KSP |
| `Phase2KspPilotSeeder.php` | Seed KSP pilot |
| `NumeracyImprovementActionSeeder.php` | Seed improvement actions |
| `ElectiveModuleSeeder.php` | Seed elective module |
| `Phase12PilotDataSeeder.php` | Seed phase 12 pilot data |
| `InspectDataSeeder.php` | Seed inspection data |

---

## 4. Migration Best Practices

1. **Always backup** sebelum menjalankan migration di production
2. **Run in order**: Migrations berjalan berdasarkan timestamp
3. **Test first**: Jalankan migration di environment development/testing dulu
4. **Rollback**: Setiap migration harus memiliki method `down()` untuk rollback
