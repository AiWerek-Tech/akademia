# KSP Compliance Preview

Status: implemented and verified, 17 August 2026.

Compliance preview extends the Phase 1 Regulation Registry; it does not duplicate or bypass it. `regulation_rules` must point to a published `regulation_versions` row. Rules are data, but execution is restricted to the typed evaluator whitelist `SECTION_MIN_PERCENT`, `ENTITY_COUNT_MIN`, `EVIDENCE_COUNT_MIN`, and `STATUS_IN`; arbitrary database code is not executed.

## Classification and result contract

- Rule classes: `LEGAL_REQUIRED`, `GUIDELINE`, `SCHOOL_POLICY`.
- Result statuses: `PASS`, `WARNING`, `FAIL`, `NOT_APPLICABLE`.
- Every persisted result contains `rule_code`, `regulation_version`, `expected`, `actual`, `severity`, `source_reference`, structured provenance, and `suggested_action`.
- Every run is immutable and stores the KSP source revision, counts, actor, and timestamp.

The pilot contains only three declared internal quality rules: one `GUIDELINE` and two `SCHOOL_POLICY` rules. It does not introduce a legal rule. Because no verified official `LEGAL_REQUIRED` source exists in the active Registry, the engine emits `LEGAL_REGISTRY_UNCONFIGURED` as `NOT_APPLICABLE` with provenance explicitly stating that no legal conclusion was made. This is the safety behavior required by the no-invented-law constraint.

Pilot browser result: overall `PASS`; internal rules 3 `PASS`; legal safety sentinel 1 `NOT_APPLICABLE`.

Security: preview routes require `ksp.view`; executing a preview requires `ksp.review`; KSP lookup is always unit scoped.
