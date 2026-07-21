# Milestone 3 Final Acceptance & Hardening Report

This report summarizes the functional and security coverage completed for Milestone 3 (Struktur Kurikulum).

## 1. Acceptance Gates Coverage
Milestone 3 is verified across the following architectural components:
- **Curriculum Versions**: Lifecycle transition rules (DRAFT, VALIDATED, REVIEWED, APPROVED, LOCKED, ARCHIVED) are validated server-side. Once LOCKED, version structure configuration becomes immutable.
- **Grade Defaults & Classroom Overrides**: Default values apply at the grade level, and classroom overrides can be set to customize JP or block patterns.
- **Effective Weekly Hours**: Evaluates JP based on OFFICIAL, CUSTOM, and MANUAL sources, verifying that manually entered or custom hours must supply an adjustment reason. Client-side tampering with effective JP is prevented.
- **Block Patterns**: Confirms pattern structure arrays (e.g. `[2, 2]`), minimum days, and maximum hours constraints.
- **Import/Export Pipeline**: Staged imports from Excel are verified, including MIME type checks, rollback safety on transactions, preview stages, and export outputs.

## 2. Security Hardenings
- **Route Security**: All admin endpoints are protected server-side via `auth`, `unit_access`, and `password_change_required` filters.
- **Database Concurrency Protection**: Database generated columns enforce unique indexes, preventing race conditions on duplicate structures.
- **Idempotent Seeders**: Permissions, role mapping, and feature flags are seeded idempotently.
