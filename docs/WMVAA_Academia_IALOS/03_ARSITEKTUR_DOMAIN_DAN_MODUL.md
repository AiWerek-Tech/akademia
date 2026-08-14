# 03 — Arsitektur Domain dan Modul

## 1. Architectural Style

Disarankan menggunakan **modular monolith** terlebih dahulu di dalam CodeIgniter 4 dan MySQL yang sudah digunakan WMVAA Academia.

Alasan:
- ukuran sekolah masih memungkinkan;
- deployment cPanel lebih sederhana;
- transaksi lintas domain lebih mudah;
- menghindari kompleksitas microservices dini;
- batas domain tetap dapat dijaga melalui service contract.

---

## 2. Bounded Contexts

### A. Identity & Organization
Users, roles, permissions, units, academic context.

### B. Regulation & Compliance
National regulation versions, rule sets, scope and compliance results.

### C. School Curriculum / KSP
School context, vision/mission/goals, organization of learning, evaluation.

### D. Curriculum Planning
Curriculum version, subject, CP, element, TP, ATP, hours, school adaptation.

### E. Workforce
Teachers, assignments, team teaching, workload, additional duties.

### F. Scheduling
Schedule versions, requirements, slots, availability, generator, conflicts, entries.

### G. Learning Design
Subject learning packs, learning units, lesson sequences, lesson plans, resources.

### H. Learning Execution
Scheduled teaching session, attendance, activity execution, notes, completion.

### I. Assessment & Mastery
Assessment, criteria, rubric, evidence, result, mastery, feedback, intervention.

### J. Cocurricular
Program, theme, dimensions, interdisciplinary alignment, execution, evaluation.

### K. Extracurricular
Program, member, coach, activity, achievement, qualitative assessment.

### L. Reporting & Portfolio
Rapor, narrative, portfolio, transcript, class/teacher reports.

### M. Quality Improvement
Reflection, observation, supervision, program evaluation, action plan.

### N. AI Assistance
Prompt context, generated draft, approval, provenance, safety policy.

---

## 3. End-to-End Domain Flow

```text
RegulationVersion
    ↓
SchoolCurriculumVersion (KSP)
    ↓
CurriculumVersion
    ↓
LearningObjective/ATP
    ↓
TeachingAssignment
    ↓
ScheduleRequirement
    ↓
ScheduleEntry
    ↓
LearningSession
    ↓
LearningActivity
    ↓
AssessmentEvidence
    ↓
MasteryRecord
    ↓
Intervention / Enrichment
    ↓
ReportRecord
    ↓
SchoolEvaluation
```

---

## 4. Existing WMVAA Academia Integration

Modul yang sudah ada **tidak dibuang**:

### Existing → Future Role
- Auth/RBAC → foundational security.
- Master Data → source identity for subjects, teachers, classes, rooms.
- Curriculum Structure → national/school curriculum structure layer.
- Teaching Assignment & Workload → workforce layer.
- Scheduling → orchestration and session trigger.

Layer baru dimulai **setelah dan di sekitar** fondasi tersebut.

---

## 5. Event/Action Examples

Walau tetap modular monolith, gunakan domain events internal atau service hooks:

- `ScheduleEntryApplied` → create/refresh future LearningSession shells.
- `TeachingAssignmentChanged` → mark impacted plans/sessions for reconciliation.
- `ATPRevised` → run lesson coverage impact analysis.
- `AssessmentSubmitted` → recompute mastery.
- `MasteryChanged` → recompute intervention recommendation.
- `TermClosed` → freeze reporting snapshot.
- `KSPApproved` → freeze KSP version.

Event harus idempotent dan diaudit.

---

## 6. Key Architectural Invariants

1. Tidak ada modul yang membaca data lintas unit tanpa scope policy.
2. Tidak ada reporting calculation yang mengambil “nilai final” dari field tunggal tanpa provenance.
3. Schedule entry bukan lesson plan; ia menjadi *trigger/reference*.
4. Lesson plan bukan assessment result.
5. Evidence dapat dipakai oleh lebih dari satu alignment melalui junction table, tetapi tidak diduplikasi.
6. AI output selalu mempunyai `generated_by_ai`, provenance dan approval state.
7. National reference data read-only pada level sekolah; adaptasi dibuat sebagai child/override.
8. KSP dan curriculum version tidak diubah setelah locked; revisi menggunakan clone/new revision.

---

## 7. Suggested Namespace/Folder Direction

```text
app/
  Domain/
    Regulation/
    Ksp/
    Curriculum/
    Workforce/
    Scheduling/
    Learning/
    Assessment/
    Cocurricular/
    Extracurricular/
    Reporting/
    Quality/
    AI/
  Application/
    ...
  Controllers/
  Models/
  Policies/
  Services/
```

Tidak wajib direfactor sekaligus. Gunakan struktur ini untuk modul baru dan migrasikan modul lama hanya bila ada manfaat nyata.
