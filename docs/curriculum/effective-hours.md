# Curriculum Effective Hours Specification

This document details the server-side calculation rules and validations for effective weekly hours.

## 1. Effective Hours Definition
The effective weekly hours (`effective_weekly_hours`) of a curriculum structure are computed server-side from three possible sources:

- **`OFFICIAL`**:
  - Requires: `official_weekly_hours` must be set.
  - Calculation: `effective_weekly_hours = official_weekly_hours`.
- **`CUSTOM`**:
  - Requires: `custom_weekly_hours` must be set.
  - Requires: `adjustment_reason` must not be empty.
  - Calculation: `effective_weekly_hours = custom_weekly_hours`.
- **`MANUAL`**:
  - Requires: `manual_weekly_hours` must be set.
  - Requires: `adjustment_reason` must not be empty.
  - Calculation: `effective_weekly_hours = manual_weekly_hours`.

Any input that attempts to directly supply or override `effective_weekly_hours` is ignored; the server recalculates the values before saving.

## 2. Invalid Input & Boundary Validations
The calculation service validates the following invariants:
- **No Negative Hours**: `official_weekly_hours`, `custom_weekly_hours`, and `manual_weekly_hours` must be non-negative (>= 0). Any negative value throws an `InvalidArgumentException`.
- **Source Constraints**: Only `OFFICIAL`, `CUSTOM`, and `MANUAL` are accepted. Any other string (such as `EFFECTIVE` or invalid values) is rejected.
- **Adjustment Reason Constraints**: For `CUSTOM` and `MANUAL`, a detailed reason is required to document the structural adjustment.
