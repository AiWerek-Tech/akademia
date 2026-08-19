# Current Learning Pack Audit

Phase 3 starts from the Phase 1 education foundation. This audit classifies the existing learning-pack surface before any Subject Learning Pack Engine expansion.

## Source Reviewed

- `docs/WMVAA_Academia_IALOS/01_VISI_DAN_PRINSIP_PRODUK.md`
- `docs/WMVAA_Academia_IALOS/02_PRD_MASTER_IALOS.md`
- `docs/WMVAA_Academia_IALOS/03_ARSITEKTUR_DOMAIN_DAN_MODUL.md`
- `docs/WMVAA_Academia_IALOS/06_TEACHING_AND_LEARNING_ENGINE.md`
- `docs/WMVAA_Academia_IALOS/09_DATA_MODEL_AND_INTEGRATION_BLUEPRINT.md`
- `docs/WMVAA_Academia_IALOS/11_ROADMAP_MILESTONES_IMPLEMENTASI.md`
- `docs/WMVAA_Academia_IALOS/12_ACCEPTANCE_SECURITY_AND_QUALITY_GATES.md`
- `docs/WMVAA_Academia_IALOS/13_SOURCE_TO_SYSTEM_TRACEABILITY.md`
- `docs/WMVAA_Academia_IALOS/14_IMPLEMENTATION_AGENT_BRIEF.md`
- `docs/ialos/phase1/*.md`
- `app/Database/Migrations/20260816000000_CreateEducationFoundationTables.php`
- `app/Services/LearningPackService.php`
- `app/Models/SubjectLearningPackModel.php`
- `tests/database/LearningPackTest.php`

## Classification

| Area | Existing Surface | Classification | Phase 3 Decision |
|---|---|---|---|
| `subject_learning_packs` table | Unit-scoped pack shell with curriculum, subject, grade, code, name, description, status, revision, audit columns | EXTEND | Keep table as aggregate root and add generic lineage fields: phase, source type/id, parent pack, workflow actors/timestamps as needed. |
| `subject_learning_pack_objectives` table | Pack-to-TP link only | EXTEND | Preserve relation and add role, sequence order, and estimated hours via Phase 3 unit/objective mapping. Do not duplicate TP text. |
| `subject_learning_pack_sequences` table | Pack-to-ATP link only | REUSE | Keep as ATP linkage. Phase 3 unit/activity coverage should read it but not replace ATP. |
| `SubjectLearningPackModel` | Simple CI model with Phase 1 allowed fields | EXTEND | Add only generic Phase 3 pack fields. Keep existing model naming to avoid a second app/module. |
| `LearningPackService` | Create, attach objective, attach sequence, update, scoped list | EXTEND | Keep service boundary; add workflow, clone/adapt, OCC, validation, coverage hooks, and child aggregate orchestration. |
| `LearningPackTest` | One basic link test | EXTEND | Keep test and add Phase 3 test classes for migration, workflow, units, concepts, prerequisites, activity/resource/guidance, security, concurrency, import, and genericity. |
| CP/TP/ATP schema | `learning_outcomes_cp`, `curriculum_elements`, `learning_objectives_tp`, `learning_sequences_atp`, `learning_sequence_items` | REUSE | Treat these as source of truth. Learning units and activities reference objectives; they must not copy canonical TP text. |
| Source registry | `curriculum_sources` | REUSE | Phase 3 records store `source_id`, `source_locator`, copyright/license notes where applicable. |
| Graduate profile dimensions | `graduate_profile_dimensions` | REUSE | Add generic junctions from unit/activity/objective to existing dimensions. Do not create duplicate profile masters. |
| Unit scope | `UnitScopeService` and existing school units | REUSE | All school and teacher adaptations remain unit-scoped. Cross-unit direct UUID access must be denied. |
| RBAC | `learning_packs.view`, `learning_packs.manage` | EXTEND | Add canonical Phase 3 permissions for validate/review/approve/lock/clone and child aggregate management. Controllers must check permissions, not role names. |
| Audit | `AuditService` plus `audit_logs` | REUSE | Every create/edit/link/workflow/import action writes audit records. |
| OCC | `EducationFoundationService::atomicUpdate` pattern | REUSE | Apply revision checks to pack, unit, activity, resource, and workflow transitions. |
| UI | Existing IALOS control center surfaces only | MISSING | Build integrated pages under existing Academia layout with separate menu/category pages, not a tab-only single-page app. |
| Learning unit hierarchy | None | MISSING | Add generic `learning_units` with parent hierarchy and unit type. |
| Concept model | None | MISSING | Add `learning_concepts` and `learning_concept_relations`; prerequisite edges must be acyclic. |
| Prerequisite validation | None | MISSING | Add `LearningPrerequisiteService` for missing/circular/phase/subject/unit-scope checks. |
| Essential material | None | MISSING | Add generic material topics with levels and teacher notes. |
| Misconceptions | None | MISSING | Add guidance model that avoids labeling students permanently. |
| Activations/aperception | None | MISSING | Add activation prompts linked to learning units. |
| Activities/resources | None | MISSING | Add generic activity engine, resource requirements, alternatives, and delivery/grouping metadata. |
| Teacher guidance/expected responses | None | MISSING | Add structured guidance and expected response records as teacher-facing planning data. |
| Assessment reference | None | MISSING | Add reference-only assessment guidance; do not create assessment results, attempts, scores, or mastery. |
| Remedial/enrichment/reflection prompts | None | MISSING | Add planning guidance only. No student assignment/submission in Phase 3. |
| Import staging | Phase 1 CP/TP staging only | EXTEND | Extend staged import entity list for Phase 3; keep upload/hash/stage/validate/preview/apply workflow. |
| Existing scheduling/teaching execution | Schedule and attendance modules already exist | DO_NOT_TOUCH | Phase 3 must not create Daily Teaching Workspace, Teaching Mode, LearningSession execution, Assessment/Mastery, Reporting, or AI. |
| Subject-specific tables | None | DO_NOT_TOUCH | Do not add `informatika_*`, `math_*`, or any per-subject schema. |

## Architectural Notes

- The existing Phase 1 pack is the correct aggregate root; Phase 3 should deepen it rather than create another learning-pack module.
- Informatika X is a reference implementation and fixture source only. Production seeders must not dump copyrighted book content.
- The non-Informatika genericity fixture is mandatory because plugged/unplugged and device metadata are optional capabilities, not universal requirements.
- Phase 3 ends at structured learning design readiness. Daily lesson planning, teaching execution, assessment results, mastery, portfolios, reports, and AI stay out of scope.
