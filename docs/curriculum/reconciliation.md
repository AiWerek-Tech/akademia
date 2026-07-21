# Curriculum Reconciliation Specification

This document details the reconciliation engine and statuses computed for curriculum versions.

## 1. Reconciliation Overview
The reconciliation engine compares the target weekly hours (from the `OFFICIAL` curriculum specification) against the actual hours allocated in the curriculum structure (using `effective_weekly_hours` which accounts for `CUSTOM` and `MANUAL` adjustments).

It groups calculations by School Unit and Grade Level. Classroom overrides are not double-counted (reconciliation is performed on Grade Defaults, and override counts are displayed separately).

## 2. Reconciliation Statuses

| Status | Trigger | Description |
|---|---|---|
| **`MATCHED`** | `total_effective == total_official` and `errors == 0` | The allocated effective hours match the official target. |
| **`DIFFERENT_ACCEPTED`** | `total_effective != total_official` and `errors == 0` | The allocated hours differ from the official hours, but valid custom or manual override reasons were supplied. |
| **`NEEDS_REVIEW`** | `unresolved_rows > 0` | Unmapped effective sources or blank adjustment details are present. |
| **`ERROR`** | `error_count > 0` | Active validation errors exist (e.g. negative JP or missing required arguments). |

## 3. Totals Aggregation
For each grade level, the following sums are calculated and aggregated to version-level totals:
- **`total_official`**: Grand sum of official weekly JP.
- **`total_custom`**: Grand sum of custom weekly JP.
- **`total_manual`**: Grand sum of manual weekly JP.
- **`total_effective`**: Grand sum of effective weekly hours.
