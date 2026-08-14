# 14 — Implementation Agent Brief

## Mission

Implementasikan WMVAA Academia IALOS **bertahap**, tanpa mengganggu baseline M0–M5 yang stabil dan tanpa big-bang rewrite.

## First Development Target

**Phase 1 — Education Foundation Model**
dengan **Informatika Kelas X** sebagai reference implementation.

## Mandatory Rules

1. Audit schema/branch/test baseline dahulu.
2. Jangan ubah legacy Kumer.
3. Jangan merge/deploy tanpa approval.
4. Reuse current unit/period/RBAC/version/audit patterns.
5. National reference data read-only; school adaptation separate.
6. Every mutable aggregate uses OCC/revision.
7. Every source/reference stores provenance.
8. Do not use AI for deterministic compliance logic.
9. No student PII in logs.
10. No copied copyrighted full-book content unless explicitly authorized.

## Deliverables Phase 1

- migrations;
- seed/reference schema;
- models;
- services;
- policies;
- routes/controllers/views;
- import template;
- tests;
- browser acceptance;
- docs;
- feature flags.

## Initial Entity Set

- regulations / regulation_versions
- graduate_profile_dimensions
- curriculum_sources
- learning_outcomes_cp
- curriculum_elements
- learning_objectives_tp
- objective_criteria
- learning_sequences_atp
- learning_sequence_items
- subject_learning_packs

## Acceptance

- fresh migration;
- rollback;
- idempotent seed;
- unit isolation;
- source lineage;
- official/adaptation isolation;
- TP/ATP coverage test;
- full regression;
- clean git state.

STOP after Phase 1 and report. Do not continue automatically.
