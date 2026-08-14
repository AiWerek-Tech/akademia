# 09 — Data Model & Integration Blueprint

## 1. Design Rule

Gunakan **semantic education model**, bukan “one table per document”.

RPP/modul ajar/KSP/rapor adalah representasi dari data domain.

---

## 2. Conceptual Entities

### Regulation
- regulations
- regulation_versions
- regulation_rules
- compliance_runs
- compliance_results
- waivers

### KSP
- ksp_versions
- school_context_snapshots
- school_context_evidence
- ksp_vision_mission_goals
- ksp_learning_organization
- ksp_evaluations
- improvement_actions

### Curriculum
Reuse/extend existing:
- curriculum_versions
- curriculum_structures

Add:
- curriculum_frameworks
- learning_outcomes_cp
- curriculum_elements
- learning_objectives_tp
- objective_criteria
- learning_sequences_atp
- learning_sequence_items
- learning_units
- concept_maps
- misconceptions
- curriculum_sources

### Learning Plan
- subject_learning_packs
- lesson_sequences
- lesson_templates
- lesson_plans
- lesson_plan_objectives
- lesson_plan_dimensions
- lesson_activities
- lesson_resources
- lesson_assessment_plans

### Execution
- learning_sessions
- session_activity_executions
- session_attendance_links
- session_notes
- session_reflections

### Assessment
- assessments
- assessment_objectives
- assessment_criteria
- rubrics
- rubric_levels
- assessment_attempts
- criterion_results
- learning_evidence
- evidence_alignments
- feedback_records
- mastery_records
- interventions

### Cocurricular
- cocurricular_programs
- cocurricular_dimensions
- cocurricular_subjects
- cocurricular_objectives
- cocurricular_sessions
- cocurricular_evaluations

### Extracurricular
- extracurricular_programs
- extracurricular_members
- extracurricular_sessions
- extracurricular_competencies
- extracurricular_results

### Reporting
- reporting_policies
- report_snapshots
- report_subject_results
- report_narratives
- portfolio_collections

### Quality
- teacher_reflections
- learning_observations
- supervision_records
- evaluation_findings
- professional_development_actions

### AI
- ai_requests
- ai_context_snapshots
- ai_outputs
- ai_approvals
- ai_feedback

---

## 3. Key Relationships

```text
curriculum_version
 └─ learning_outcomes_cp
     └─ curriculum_elements
         └─ learning_objectives_tp
             └─ objective_criteria

learning_sequence_atp
 └─ sequence_items → TP

schedule_entry
 └─ learning_session
     ├─ lesson_plan
     ├─ activities
     ├─ assessments
     └─ reflections

assessment
 └─ evidence
     └─ criterion_results
         └─ mastery_records
```

---

## 4. Source Lineage

Semua imported/reference content mempunyai:
- source_type;
- source_id;
- source_title;
- edition/version;
- source_locator (chapter/page/reference);
- license/usage note;
- imported_by;
- reviewed_by.

---

## 5. Versioning Pattern

Gunakan pola:
- stable `uuid`;
- numeric internal id;
- `revision_number`;
- `status`;
- `effective_from/to`;
- `supersedes_id`;
- optimistic locking.

---

## 6. Multi-Unit Scope

Setiap record operasional yang perlu isolasi harus mempunyai explicit scope:
- unit_id;
- academic_period_id;
- school year/semester;
- classroom/grade when applicable.

National reference records tidak perlu unit scope; school adaptations memilikinya.

---

## 7. Existing Scheduling Integration

Jangan memodifikasi scheduling menjadi learning system.

Tambahkan adapter:

`ScheduleLearningBridgeService`

Tugas:
- create future session shell;
- sync safe metadata;
- detect changed/cancelled schedule;
- preserve completed learning session history.

Jika jadwal berubah setelah sesi selesai, histori sesi lama tidak dihapus.

---

## 8. Data Retention

- evidence dan assessment histories retained sesuai kebijakan sekolah;
- generated temporary files tidak disimpan permanen tanpa kebutuhan;
- AI prompt context diminimalkan;
- personally identifiable data dikontrol dan diaudit.

---

## 9. Migration Strategy

1. Add new tables only.
2. Backfill minimal references.
3. Feature flag new modules.
4. Pilot Informatika X.
5. No destructive migration to legacy/stable modules.
6. Reconciliation scripts before turning on automation.
