# WMVAA Akademia — Milestone Roadmap

## Milestone 1: Foundation, Security, RBAC & Core Admin UI (CLOSED)
- [x] Base CodeIgniter 4.7.4 installation and PHP 8.2 compatibility
- [x] Database migrations & seeders (`CoreSeeder`)
- [x] Unit isolation (SMP vs SMA) & Multi-tenant access controls
- [x] Session-based authentication & forced password change workflow
- [x] Dynamic Role-Based Access Control (RBAC) permission checking
- [x] Academic Period lifecycle workflow (`DRAFT` -> `VALIDATED` -> `REVIEWED` -> `APPROVED` -> `LOCKED`)
- [x] Real-time Audit Logging system
- [x] 100% PHPUnit Test Suite coverage (53 tests, 149 assertions, 0 errors, 0 warnings)
- [x] Dedicated CSRF HTTP enforcement test suite (`CsrfEnforcementTest`)
- [x] Browser runtime verification endpoint (`/system/runtime`)
- [x] SPMB design system migration & offline asset independence (`public/assets/`)
- [x] Final Acceptance Evidence Gate — **PASSED**

## Milestone 2: Academic Domain & Curriculum Management (PENDING)
- [ ] Teacher management (`guru`)
- [ ] Subject catalog (`mata_pelajaran`)
- [ ] Classrooms & room allocation (`kelas`, `ruang`)
- [ ] Curriculum & workload management
- [ ] Schedule generation & duty roster
