# KSP Document Generation

Status: implemented and verified, 17 August 2026.

The generator consumes a structured snapshot of the existing Digital KSP domain. It does not maintain a second document model. Generation is allowed only for `APPROVED` or `LOCKED` KSP versions.

Both DOCX and PDF contain Cover, Identity, Approval, School Characteristics, Vision, Mission, Goals, Learning Organization, Intracurricular, Cocurricular, Extracurricular, Learning Planning, Evaluation, Improvement Actions, Appendices, Compliance Summary, and Evidence Index.

Every output is appended to `ksp_generated_documents` with `ksp_version_id`, `source_revision`, source snapshot hash, `generated_by`, `generated_at`, `document_hash`, storage reference, format, and byte size. Filenames include timestamp plus UUID; existing files and database history are never overwritten. Download is guarded by `ksp.export` and unit scope.

Verified final pilot artifacts:

- DOCX: `writable/exports/ksp/4cd96d05-f07a-45c0-a6a5-f93316b2cf9a/20260817_002737_932a5ae6-aa9a-40f7-a000-4ea62b439904.docx`; SHA-256 `bf92236f40bdb6f2407dc5581bf01deb66c84f3c04c490ae5938a4f35a0d3f5e`; opened read-only with Microsoft Word and visually verified across 9 pages.
- PDF: `writable/exports/ksp/4cd96d05-f07a-45c0-a6a5-f93316b2cf9a/20260817_002549_123fce0e-bb31-4c15-b752-028ebf6a1b02.pdf`; SHA-256 `f5b69251aecbe9109d636b170f21931326d51bc32dffc0bebb2d24dd32822f6f`; Poppler render visually verified across 5 pages.

The visual verification included cover, approval, all structured sections, the numeracy improvement action, compliance including legal `NOT_APPLICABLE`, evidence index, long hashes, page headers/footers, and table overflow checks.
