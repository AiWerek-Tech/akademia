# WMVAA Akademia Milestone Plan

This document maps the project progress, past achievements, and future roadmap phases.

---

## Milestone 0: Environment & Core Setup
- **Status**: **PASSED**
- **Deliverables**: Initial environment configuration, basic CI4 setup, migrations database connectivity.

## Milestone 1: Basic Authentication & RBAC
- **Status**: **PASSED**
- **Deliverables**: Users, roles, permission mapping, session handling, password strength policies.

## Milestone 2: Master Data Akademik Terpadu
- **Status**: **PASSED**
- **Deliverables**: CRUD for teachers, subjects, classrooms, rooms, staging import pipeline, fuzzy name merge verification.

## Milestone 3: Struktur Kurikulum
- **Status**: **PASSED** (This release)
- **Deliverables**:
  - Curriculum version management (transitions and draft-publish lifecycle).
  - Subject Availability mapping (SMP/SMA grade levels).
  - Effective hours logic validation (`OFFICIAL`, `CUSTOM`, `MANUAL`).
  - Generated scopes to prevent concurrency duplicates.
  - Reconciliation matrix and multi-rule validation checks.
  - Import/Export staging interface.

## Milestone 4: Penugasan Mengajar & Beban Kerja Guru
- **Status**: **FINAL PASSED**
- **Deliverables**:
  - Teaching assignments, team teaching split ratios, and classroom overrides.
  - Teacher workload engine (teaching hours + additional duties).
  - Configurable workload policies and automated status snapshots.
  - Assignment version state machine (`DRAFT` → `VALIDATED` → `REVIEWED` → `APPROVED` → `LOCKED`).
  - Import staging pipeline and export capabilities.

## Milestone 5: Penjadwalan & Roster Mengajar
- **Status**: **NOT STARTED** (Pending authorization)
- **Deliverables**: Time slots, schedule generators, automated conflict detection, duty rosters, piket, SK documents.
