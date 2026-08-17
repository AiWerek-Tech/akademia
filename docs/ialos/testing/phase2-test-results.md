# Phase 2 Test Results

Verified 17 August 2026 with PHP 8.2.20 and PHPUnit 10.5.64.

| Gate | Result |
|---|---|
| Focused Phase 2 | PASS — 16 tests / 128 assertions |
| KSP + following Milestone4 order reproduction | PASS — 17 tests / 148 assertions |
| Full root PHPUnit | PASS — 303 tests / 1,242 assertions |
| Baseline comparison | +16 tests / +128 assertions from 287 / 1,114 |
| Composer validate `--strict` | PASS |
| Composer audit | PASS — no security advisories |
| Full PHP lint | PASS — 569 PHP files |
| `git diff --check` | PASS |
| Full isolated migrate | PASS — 118 tables |
| Full isolated rollback | PASS — 1 migration metadata table remains |
| Full isolated reapply | PASS — 118 tables |

Added suites: `KspComplianceTest`, `KspEvidenceTest`, `KspDocumentGeneratorTest`, `KspSecurityTest`, and `KspBrowserAcceptanceTest`.

Negative coverage includes guest access, unauthorized permission matrix, cross-unit UUID, stale revision/OCC, locked mutation, cross-KSP evidence target, and cross-unit document export.

PHPUnit's project configuration sets a 512 MB runner limit because loading Dompdf inside the complete 303-test process exceeded the machine's default 128 MB. Final peak memory was 156 MB; this is test-runner capacity, not an application permission or data-boundary change.
