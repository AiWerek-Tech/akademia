# 11 — Roadmap & Milestones Implementasi

## Strategy

Jangan melakukan big-bang rewrite. Sistem penjadwalan yang ada tetap dipakai. Pembangunan IALOS dilakukan di atas baseline stabil.

---

## Phase 0 — Architecture Freeze & Discovery

Tujuan:
- audit current schema;
- map M0–M5;
- identify stable contracts;
- freeze naming conventions;
- create architecture decision records.

Exit:
- no code change required except docs/tests if needed.

---

## Phase 1 — Education Foundation Model

Deliver:
- regulation registry;
- graduate profile dimensions;
- source registry;
- CP/element/TP/ATP generic model;
- versioning;
- official/reference vs school adaptation.

Pilot:
- Informatika Fase E.

Exit:
- CP→TP→ATP can be stored and traced.
- no duplicate source of truth with existing curriculum.

---

## Phase 2 — Digital KSP

Deliver:
- KSP versions;
- school context;
- vision/mission/goals;
- organization of learning;
- KSP completeness;
- compliance preview;
- document export.

Exit:
- KSP draft can be generated from structured data.

---

## Phase 3 — Subject Learning Pack Engine

Deliver:
- unit/chapter;
- prerequisites;
- essential material;
- misconception;
- activities;
- resource requirements;
- teacher guidance;
- assessment references.

Reference implementation:
**Informatika Kelas X**.

Exit:
- one complete subject can plan a semester.

---

## Phase 4 — Deep Learning Lesson Planner

Deliver:
- identification;
- graduate profile selection;
- pedagogical practice;
- partnership;
- environment;
- digital use;
- understand/apply/reflect;
- initial/formative/summative planning.

Exit:
- teacher can prepare a lesson without Word document.

---

## Phase 5 — Daily Teaching Workspace

Deliver:
- Today dashboard;
- learning sessions linked to schedule;
- teaching mode;
- attendance integration;
- activity execution;
- reflection.

Exit:
- teacher completes real classroom session end-to-end.

---

## Phase 6 — Assessment & Mastery

Deliver:
- diagnostic/formative/summative;
- criteria/rubric;
- evidence;
- mastery;
- intervention;
- enrichment.

Exit:
- TP progress is evidence-backed.

---

## Phase 7 — Cocurricular & Character

Deliver:
- theme;
- dimensions;
- interdisciplinary mapping;
- time;
- activity;
- evaluation;
- reporting;
- school-specific activities.

Exit:
- at least one WMVAA cocurricular program executed digitally.

---

## Phase 8 — Extracurricular

Deliver:
- programs;
- Pathfinder pilot;
- membership;
- attendance;
- competency;
- qualitative reporting.

---

## Phase 9 — Reporting & Portfolio

Deliver:
- subject report;
- narrative;
- cocurricular;
- extracurricular;
- portfolio;
- class/teacher analytics.

Exit:
- semester reporting is derived from learning data.

---

## Phase 10 — Quality & AI Copilot

Deliver:
- teacher reflection;
- supervision;
- KSP evaluation;
- improvement plan;
- AI assistant.

AI is last because quality depends on structured data.

---

## Recommended Pilot Sequence

1. Informatika X.
2. Koding & KA IX.
3. One non-technology subject (e.g. Mathematics).
4. One cocurricular interdisciplinary project.
5. Pathfinder/extracurricular.

This tests whether the model is truly generic.

---

## Rollout Rule

Setiap phase harus:
- migration/rollback pass;
- unit isolation pass;
- permissions pass;
- full regression pass;
- browser acceptance pass;
- documentation updated;
- feature flag available;
- pilot validated before expanding.
