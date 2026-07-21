# Milestone 3 Test Discovery Audit

This document details the complete inventory of automated tests across the codebase, confirming the resolution of the test discovery gap (why the test suite run with `tests/database/` parameter ran fewer tests than the total suite).

## 1. Test Suite Configuration
The PHPUnit configuration [phpunit.xml.dist](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/phpunit.xml.dist) defines the global suite `App` which scans `./tests` recursively.

## 2. Inventory of Test Folders
The project includes the following test directories:
- `tests/database/`: Contained 115 database-driven acceptance and integration tests.
- `tests/Security/`: Contains CSRF enforcement tests (13 tests).
- `tests/session/`: Contains session behavior verification (1 test).
- `tests/unit/`: Contains fast controller, health, and router tests (4 tests).

Total suite: **133 tests** (before Milestone 3 audit extensions).
After final hardening, we added **6 tests**, bringing the total to **139 tests**.

## 3. Full List of Discovered Tests (139 total)
- `Tests\Security\CsrfEnforcementTest` (13 tests)
  - `testLogoutWithoutCsrfTokenIsRejected`
  - `testLogoutWithInvalidCsrfTokenIsRejected`
  - `testLogoutWithValidCsrfTokenIsProcessed`
  - `testContextUnitWithoutCsrfTokenIsRejected`
  - `testContextUnitWithValidCsrfTokenIsProcessed`
  - `testContextPeriodWithoutCsrfTokenIsRejected`
  - `testCreateUserWithoutCsrfTokenIsRejected`
  - `testCreateAcademicYearWithoutCsrfTokenIsRejected`
  - `testWorkflowPeriodWithoutCsrfTokenIsRejected`
  - `testCsrfTokenNotInAuditLog`
  - `testCsrfTokenRegenerationBehavior`
  - `testLoginFormContainsCsrfField`
  - `testAuthenticatedFormContainsCsrfField`
- `Tests\Database\AuthTest` (2 tests)
  - `testUserCreationAndPasswordHash`
  - `testFailedAttemptsLockout`
- `Tests\Database\CoreTablesTest` (1 test)
  - `testCoreTablesPopulated`
- `Tests\Database\CurriculumSecurityTest` (4 tests)
  - `testSec01_SQLInjectionInVersionCodeSanitized`
  - `testSec02_XSSInVersionNameEscapedInView`
  - `testSec03_JSONInjectionInBlockPatternRejected`
  - `testSec04_NegativeHoursRejected`
- `ExampleDatabaseTest` (2 tests)
  - `testModelFindAll`
  - `testSoftDeleteLeavesRow`
- `Tests\Database\ExtendedAcademicTest` (5 tests)
  - `testAcademicYearOverlappingAndValidation`
  - `testAcademicYearActivationOnlyOneActive`
  - `testPeriodSemesterNumberValidation`
  - `testPeriodTransitionsAndPermissions`
  - `testStaleRevisionOptLocking`
- `Tests\Database\ExtendedAuditTest` (1 test)
  - `testAuditLogSanitization`
- `Tests\Database\ExtendedAuthTest` (12 tests)
  - `testLoginSuccessRedirectsToDashboard`
  - `testLoginFailureIncrementsCounter`
  - `testFiveFailuresTriggersLockout`
  - `testLoginDuringLockoutRejected`
  - `testLoginSuccessResetsCounter`
  - `testNonExistentUserGenericError`
  - `testInactiveUserRejected`
  - `testMustChangePasswordRedirects`
  - `testChangePasswordRejectsWeakPassword`
  - `testChangePasswordSucceeds`
  - `testLogoutPostDestroysSession`
  - `testLogoutGetRejected`
- `Tests\Database\ExtendedRbacTest` (5 tests)
  - `testGuestAccessRedirectsToLogin`
  - `testGuruCannotManageUsers`
  - `testSmpAdminCannotSwitchToSma`
  - `testWakasekPermissionBoundaries`
  - `testKepsekPermissionBoundaries`
- `Tests\Database\ExtendedSecurityTest` (3 tests)
  - `testSqlInjectionSecurityOnQueries`
  - `testOverlongInputsRejectedByValidation`
  - `testInvalidUuidUrlHandling`
- `Tests\Database\ExtendedUserManagementTest` (3 tests)
  - `testUserCreationDuplicateUsernameAndEmail`
  - `testUserCreationPayloadValidationAndXssDefense`
  - `testUserResetPasswordForcesChange`
- `Tests\Database\ImportExportAcceptanceTest` (14 tests)
  - `testK01_TemplateGenerationAllTypes`
  - `testK02_TemplateInvalidTypeRejected`
  - `testK03_ValidateTeacherRowValid`
  - `testK04_ValidateTeacherRowMissingName`
  - `testK05_ValidateSubjectRowValid`
  - `testK06_ValidateSubjectDuplicateProposesUpdate`
  - `testK07_ValidateRoomRowMissingCode`
  - `testK08_ValidateRoomRowMissingName`
  - `testK09_ValidateRoomDuplicateProposesUpdate`
  - `testK10_ValidateClassroomRowMissingCode`
  - `testK11_ApplyBatchRejectsAlreadyApplied`
  - `testK12_ApplyBatchCreatesTeachers`
  - `testK13_ApplyBatchSkipsErrorRows`
  - `testK14_ApplyBatchSkipsSkipDecision`
- `Tests\Database\ImportExportAcceptanceTest` - Export (5 tests)
  - `testL01_ExportTeachersExcel`
  - `testL02_ExportSubjectsExcel`
  - `testL03_ExportClassroomsExcel`
  - `testL04_ExportRoomsExcel`
  - `testL05_ExportInvalidEntityRejected`
- `Tests\Database\Milestone2AcceptanceTest` (37 tests)
  - Teacher CRUD (E01-E20, 12 tests)
  - Review & Merge (F01-F05, 3 tests)
  - Subject CRUD (G01-G05, 5 tests)
  - Grade Levels (H01-H04, 4 tests)
  - Classroom CRUD (I01-I04, 4 tests)
  - Room CRUD (J01-J05, 5 tests)
  - CSRF & RBAC (M01-M03, 4 tests)
- `Tests\Database\Milestone2Test` (4 tests)
  - `testTeacherCreationAndDuplicateDetection`
  - `testSubjectCreationAndAlias`
  - `testRoomCreationAndValidation`
  - `testClassroomCopyPeriod`
- `Tests\Database\Milestone3AcceptanceTest` (17 tests)
  - Version CRUD & Clones (C01-C05, 5 tests)
  - Effective Hours & Validation Rules (D01-D09, 9 tests)
  - Block Pattern validation (E01-E03, 3 tests)
  - Resolution & Override (F01-F02, 2 tests)
  - Export Verification (G01, 1 test)
- `Tests\Database\WorkflowTest` (1 test)
  - `testWorkflowTransitionsAndLocking`
- `ExampleSessionTest` (1 test)
  - `testSessionSimple`
- `Tests\HealthControllerTest` (1 test)
  - `testHealthIndexReturnsSuccess`
- `HealthTest` (2 tests)
  - `testIsDefinedAppPath`
  - `testBaseUrlHasBeenSet`
- `Tests\HomeControllerTest` (1 test)
  - `testIndexRendersDashboard`
