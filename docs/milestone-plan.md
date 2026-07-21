# Milestone Plan - WMVAA Akademia

This document lists the milestones and criteria for transitioning from one development phase to another.

---

## Milestone Directory

| Milestone | Title | Focus Areas | Status |
|---|---|---|---|
| **M0** | Discovery and Foundation | Project Init, Base Layout, DB Config, Documentation | **PASSED AFTER SECURITY GATE** |
| **M1** | Auth, RBAC, Unit, Periode | User Access, Roles, Semesters, Unit Access Controls | *NOT STARTED* |
| **M2** | Master Guru, Mapel, Kelas, Ruang | Basic Entities, Import Staging, Duplication Filters | *PLANNED* |
| **M3** | Struktur Kurikulum | Curriculum structure, Weekly Hours, Block Pattern Verification | *PLANNED* |
| **M4** | Penugasan & Beban Kerja | Teaching Assignment Matrix, Additional Duty, Workload Reports | *PLANNED* |
| **M5** | Fondasi Jadwal | Day Slots, Constraints, Availability Matrix | *PLANNED* |
| **M6** | Editor & Validasi Jadwal | UI Editor, Conflict validation, Version comparing | *PLANNED* |
| **M7** | Generator Jadwal | PHP heuristic solver, Alternative generation, score display | *PLANNED* |
| **M8** | Piket | Duty rules generator, Daily Piket Journal | *PLANNED* |
| **M9** | Dokumen | Official decisions, PDF/ZIP generation, QR code verifier | *PLANNED* |
| **M10**| Import Legacy | Adapters for legacy databases and Excel parsing | *PLANNED* |
| **M11**| Portal Guru & Laporan | Teacher Schedule dashboard, Workload dashboard, letters | *PLANNED* |
| **M12**| Final QA & Deployment | Regression, cPanel install, Upgrade/Rollback rehearsals | *PLANNED* |

---

## Milestone 0 Deliverables

- Initial CodeIgniter 4.4.8 structure.
- Local configuration (`.env`).
- Git repository initialization.
- Documentation structure (`docs/`).
- Automated tests base framework.
- Core UI shell dashboard structure (sidebar layout).
- Health check API routing.
