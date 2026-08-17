# Phase 2 Browser Acceptance Results

Status: PASS, 17 August 2026.

Target URL: local integrated IALOS Education application at `app.wmvaa.local/wmvaa-akademia`. The test used a temporary synthetic super-admin account, deleted after acceptance.

Verified through the rendered application:

1. Login renders with the IALOS Education identity and enters the existing shared dashboard/session.
2. Digital KSP dashboard displays `KSP-SMP-2026-2027`, nine sections at 100%, and separate navigation destinations for context, VMG, organization, evaluation, evidence, compliance, and documents.
3. Context, vision, mission, goal, all three learning-organization categories, evaluation, and the numeracy improvement action were visible with correct values.
4. A non-sensitive synthetic evidence file was uploaded and its SHA-256 displayed in the Evidence Index.
5. Compliance preview returned three internal `PASS` results and the legal safety sentinel as `NOT_APPLICABLE`.
6. UI workflow transitions returned `REVIEW`, `APPROVED`, then `LOCKED`; draft mutation controls disappeared after lock.
7. DOCX and PDF were generated from the locked KSP; multiple generations remained as distinct historical rows with distinct hashes and storage references.
8. Final DOCX opened through Microsoft Word; final PDF opened through page rendering; every page was inspected.

Role contract was exercised by automated browser/feature acceptance for `super_admin`, `admin_smp`, `admin_sma`, `wakasek_kurikulum`, `kepala_sekolah`, application role `guru` (teacher), and `viewer_yayasan` (viewer). The real interactive pilot used `super_admin`; permission-specific and negative paths use the same application filters and database-backed sessions in the acceptance suite.
