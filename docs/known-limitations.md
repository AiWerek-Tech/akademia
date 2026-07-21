# Known Technical Debt & Intentional Scope Boundaries

## Milestone 1 Scope Boundaries
In strict accordance with project directives, the following modules and tables have **intentionally NOT been created** in Milestone 1:
- Teachers (`guru`)
- Subjects (`mata_pelajaran`)
- Classrooms (`kelas`)
- Rooms (`ruang`)
- Curriculum (`kurikulum`)
- Assignments (`tugas`)
- Workload (`beban_kerja`)
- Schedules (`jadwal`)
- Duty Roster (`piket`)
- Documents (`dokumen`)

These domain entities will be built starting in Milestone 2.

## Technical Debt & Asset Prefixes
1. **Asset CSS Class Prefixes**: Some CSS utility classes retain `sp-` and `spmb-` prefixes copied from the foundation design system. These prefixes have been audited and verified to cause zero runtime collisions or external network requests. Full refactoring to `--ak-*` prefixes can be scheduled as non-breaking technical debt cleanup in future iterations.
2. **CSRF Testing Override**: In automated test suites (`FeatureTestTrait`), global CSRF protection is bypassed by default to allow unit-level endpoint testing. Real HTTP CSRF enforcement is rigorously tested and proven in the dedicated test suite `tests/Security/CsrfEnforcementTest.php` using `$GLOBALS['enable_csrf_testing'] = true`.
