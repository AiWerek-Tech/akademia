# Curriculum Validation Codes Reference

This document maps all validation codes verified by the curriculum validation engine.

| Validation Code | Severity | Entity / Trigger | Workflow Blocker |
|---|---|---|---|
| **`MISSING_SUBJECT`** | WARNING | Version / No subjects configured in curriculum version structure | No |
| **`SUBJECT_DUPLICATE`** | ERROR | Structure / Subject is added twice in same grade-default or classroom-override | **Yes** |
| **`INACTIVE_SUBJECT`** | ERROR | Subject / Subject status is inactive in master data | **Yes** |
| **`SUBJECT_UNAVAILABLE_UNIT`** | ERROR | Subject / Subject is not mapped as active for the version unit (SMP/SMA) | **Yes** |
| **`INACTIVE_GRADE`** | ERROR | Grade Level / Grade level status is inactive | **Yes** |
| **`GRADE_UNIT_MISMATCH`** | ERROR | Grade Level or Classroom / Mismatch between version school unit and grade/classroom unit | **Yes** |
| **`INACTIVE_CLASSROOM`** | ERROR | Classroom / Classroom is inactive or soft-deleted | **Yes** |
| **`CLASSROOM_PERIOD_MISMATCH`** | ERROR | Classroom / Classroom belongs to a different academic period | **Yes** |
| **`INVALID_JP`** | ERROR | Structure / JP (Weekly Hours) is negative | **Yes** |
| **`MISSING_EFFECTIVE_SOURCE`** | ERROR | Structure / Effective source is not in OFFICIAL, CUSTOM, or MANUAL | **Yes** |
| **`MISSING_ADJUSTMENT_REASON`** | ERROR | Structure / Source is CUSTOM or MANUAL but adjustment reason is blank | **Yes** |
| **`INVALID_BLOCK_TOTAL`** | ERROR | Block Pattern / Total block pattern sum does not match effective weekly hours | **Yes** |
| **`MAXIMUM_DAILY_VIOLATION`** | ERROR | Block Pattern / Single block size exceeds maximum daily hours | **Yes** |
| **`MINIMUM_DAY_VIOLATION`** | ERROR | Block Pattern / Number of blocks is less than minimum weekly days | **Yes** |
| **`MISSING_REPORT_FLAG`** | WARNING | Structure / counts_in_report flag is not set | No |
| **`MISSING_LOAD_FLAG`** | WARNING | Structure / counts_as_teaching_load flag is not set | No |
| **`INACTIVE_ROOM_TYPE`** | WARNING | Room Type / Specified required room type is inactive in database | No |

## Workflow Blocking Rules
Any validation result with severity **`ERROR`** or **`BLOCKER`** will prevent the curriculum version from transitioning past **`VALIDATED`** or being approved/locked. All errors must be resolved or overrides cleared before publishing.
