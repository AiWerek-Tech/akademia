# KSP Evidence and Provenance

Status: implemented and verified, 17 August 2026.

Evidence is a shared Digital KSP capability, not a page-local attachment table. A single `ksp_evidence` model can target `SCHOOL_CONTEXT`, `VISION_MISSION_GOAL`, `EVALUATION`, `IMPROVEMENT_ACTION`, or `KSP_SECTION`; the service verifies that the target UUID belongs to the same scoped KSP.

Supported evidence types are `RAPOR_PENDIDIKAN`, `INTERNAL_ASSESSMENT`, `TEACHER_OBSERVATION`, `STUDENT_FEEDBACK`, `PARENT_FEEDBACK`, `FACILITY_DATA`, `LOCAL_CONTEXT`, `MEETING_DECISION`, and `OTHER`.

Each row stores source, evidence date, description, reference and/or private file storage reference, original filename, MIME type, byte size, SHA-256 for files, creator, creation time, and audit record. Files are restricted to the configured safe MIME whitelist, limited to 10 MB, and stored below `writable/uploads/ksp-evidence/{uuid}` rather than the public web root.

The browser pilot verified a real synthetic text-file upload. Stored SHA-256: `abf54d554f7125b9af17cf89a46fe29587c2e1417d8decaf7904e76c360d47e8`. Five reference-only pilot records cover every target type. No real learner, parent, or teacher-sensitive data is included.

Mutation is allowed only while the KSP is `DRAFT`. Download is permission and unit scoped. Cross-KSP target UUID and locked mutation are rejected.
