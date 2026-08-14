# 12 — Acceptance, Security & Quality Gates

## 1. Definition of Done per Module

Tidak cukup:
“UI sudah tampil.”

Minimum:
- schema;
- service/domain rules;
- authorization;
- unit scope;
- validation;
- audit;
- OCC where mutable;
- tests;
- browser acceptance;
- documentation;
- rollback/recovery.

---

## 2. Security Gates

- authentication;
- authorization;
- unit scope;
- academic period scope;
- CSRF;
- IDOR negative tests;
- mass assignment control;
- input validation;
- secure file upload;
- export scope;
- audit integrity;
- PII minimization.

---

## 3. Data Integrity Gates

- FK valid;
- unique indexes;
- transaction boundary;
- idempotent seeder;
- deterministic backfill;
- migration rollback;
- stale revision rejection;
- no destructive sync without explicit strategy.

---

## 4. Education Logic Gates

### Curriculum
- no orphan TP;
- ATP coverage;
- source lineage;
- official/custom distinction;
- version immutability.

### Teaching
- session links to correct schedule/assignment;
- historical completed sessions preserved;
- no automatic completion.

### Assessment
- formative not auto-final-grade;
- evidence links valid;
- mastery explainable;
- intervention traceable.

### Reporting
- result traceable to source;
- versioned policy;
- teacher validation before finalization.

### Cocurricular
- profile dimension alignment;
- activity/evidence;
- assessment/evaluation;
- no double-counting without explicit alignment policy.

---

## 5. Browser Acceptance Roles

- super_admin;
- admin_smp;
- admin_sma;
- waka_kurikulum;
- kepala_sekolah;
- teacher;
- homeroom;
- viewer/reviewer.

Negative scenarios:
- cross-unit direct UUID;
- unauthorized approve;
- unauthorized report;
- stale revision;
- forged source id;
- AI output bypass.

---

## 6. AI Quality Gate

Test:
- wrong TP context;
- missing source;
- prompt injection in content;
- personal data leakage;
- hallucinated regulation;
- overconfident recommendation.

Expected:
- AI labels uncertainty;
- regulation answers come from registry/reference;
- no autonomous state transition.

---

## 7. Performance Gate

Measure:
- teacher today dashboard;
- class roster;
- lesson open;
- assessment save;
- mastery recompute;
- report generation;
- compliance check.

Avoid loading all evidence files on dashboard.

---

## 8. Audit Gate

Critical entities require:
- create/update/delete;
- status transition;
- approval;
- source change;
- AI acceptance;
- grade/report finalization.

Audit should answer:
**who, what, when, why, source, previous revision.**

---

## 9. Backup & Recovery

Before major release:
- database backup;
- restore drill;
- migration rehearsal;
- rollback plan;
- document/export recovery;
- object/file storage verification.

---

## 10. Production Readiness Status

Use:
- READY FOR DEVELOPMENT
- READY FOR PILOT
- READY FOR PRODUCTION
- PASSED WITH KNOWN LIMITATIONS
- BLOCKED

Do not use “100% perfect”.
