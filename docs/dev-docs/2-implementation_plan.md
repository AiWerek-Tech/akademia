# Milestone 1 Acceptance & Hardening Gate Plan

This plan documents the verification, hardening, and testing tasks required to validate Milestone 1 of the **WMVAA Akademia** portal.

## User Review Required

> [!IMPORTANT]
> - All tests will be run using **PHP 8.2.20** and the **`wmvaa_akademia_test`** database to prevent data loss or state contamination in development/production databases.
> - We will migrate the PHPUnit configuration file `phpunit.xml.dist` to PHPUnit 10 format to eliminate deprecation notices.
> - A browser subagent will be run to verify the Super Admin, Admin SMP, Admin SMA, and Guru dashboards in the local web server environment (`http://app.wmvaa.local/wmvaa-akademia/`).
> - We will NOT create or modify any Milestone 2 entities/modules (Guru database, schedules, class rosters, Mata Pelajaran, etc.) to keep the scope restricted to Milestone 1.

## Open Questions

* No open questions. The validation targets are well-specified.

---

## Proposed Changes

### Configuration

#### [MODIFY] [phpunit.xml.dist](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/phpunit.xml.dist)
- Updated to PHPUnit 10 format to resolve configuration schema deprecation warnings.

---

### Automated Tests

#### [NEW] [ExtendedAuthTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedAuthTest.php)
- Test valid/invalid login, user inactive rejection, must change password redirect, lockout limits, session regeneration, GET logout rejection, POST logout validity, CSRF logout, and failed count reset.
- Test IP and username rate limiting, lockout duration, and recovery/clearance.

#### [NEW] [ExtendedRbacTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedRbacTest.php)
- Test guest access denial, read-only permissions for `viewer_yayasan`, Guru RBAC restriction, isolation between Admin SMP and Admin SMA unit context, wakasek validate/review permissions, kepala sekolah approve/lock permissions.
- Test server-side endpoint protection, hidden unit ID manipulation rejection, role permission recalculation, and super_admin revocation block.

#### [NEW] [ExtendedUserManagementTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedUserManagementTest.php)
- Test user creation, duplication checks, input validation, XSS filtering, mass assignment attempt blocks, role/unit assignment, activation/deactivation, temporary password security, password reset, and optimistic locking check.

#### [NEW] [ExtendedAcademicTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedAcademicTest.php)
- Test Academic Years: create, validate name, start >= end, overlapping check, transactional activation (only one year active).
- Test Academic Periods: semesters 1/2 validation, tanggal bounds, transition checks (DRAFT -> VALIDATED -> REVIEWED -> APPROVED -> LOCKED), jump transition rejection, locked edit block, only one active period, and transition audit logs.

#### [NEW] [ExtendedSecurityTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedSecurityTest.php)
- Test CSRF protection, SQL injection string defense, XSS inputs, overlong input lengths, session fixation, and secure HTTP response headers.

#### [NEW] [ExtendedAuditTest.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/tests/database/ExtendedAuditTest.php)
- Test audit log mapping and data sanitization to guarantee passwords, password_confirmation, CSRF, and session tokens are never logged.

---

### Documentation

#### [NEW] [milestone-1-acceptance-report.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/testing/milestone-1-acceptance-report.md)
- Complete summary of gate results and definition of done items.

#### [NEW] [milestone-1-test-results.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/testing/milestone-1-test-results.md)
- Test execution results including database, unit, and security coverage details.

#### [NEW] [milestone-1-browser-results.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/testing/milestone-1-browser-results.md)
- Playbook and execution logs of manual and automated browser verification.

#### [MODIFY] [milestone-1-runtime-gate.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/testing/milestone-1-runtime-gate.md)
- Document browser & CLI version matching, PHP_SAPI, and database drivers.

#### [NEW] [security.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/security.md)
- Security architectural specification, headers, rate limits, session policies, and CSRF patterns.

#### [NEW] [roles-permissions.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/roles-permissions.md)
- Role-based permissions grid, scopes, and administration.

#### [NEW] [authentication.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/authentication.md)
- Password policies, login workflow, lockout, and password change policies.

#### [NEW] [academic-period-workflow.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/academic-period-workflow.md)
- State machine of Academic Period transition levels and authorization requirements.

#### [NEW] [known-limitations.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/known-limitations.md)
- Record known system limitations, PHPUnit coverage warnings, etc.

#### [MODIFY] [milestone-plan.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/milestone-plan.md)
- Update milestone progress statuses.

#### [NEW] [walkthrough.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/walkthrough.md)
- Detailed summary walkthrough of Milestone 1.

---

## Verification Plan

### Automated Tests
- Run database and unit tests with PHPUnit:
  `E:\xampp\php82\php.exe vendor/bin/phpunit --display-warnings --display-deprecations`

### Manual Verification
- Verify browser runtime values by hitting `/system/runtime` endpoint.
- Verify migration rollbacks and reapply commands manually on local DB.
- Verify seeder idempotency manually on local DB.
- Run browser subagent task to navigate through authentication, context switches, role matrices, user creations, period workflows, audit views, and verify isolation limits.
