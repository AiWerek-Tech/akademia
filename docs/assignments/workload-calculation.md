# Workload Calculation Engine Reference

**Module**: `App\Services\TeacherWorkloadCalculationService`  

---

## 1. Overview

The Teacher Workload Engine calculates a teacher's total workload for an academic period and assignment version by combining:
1. **Teaching Assigned Hours**: Total weekly period hours (JP) assigned in `teaching_assignments`.
2. **Additional Duty Hours**: Equivalent workload hours derived from `teacher_additional_duties`.

Total Workload = `Teaching Assigned Hours` + `Additional Duty Hours`

---

## 2. Standard Workload Statuses

The workload status is evaluated against the applicable `workload_policies` for the teacher's primary unit and employment status:

- **`INCOMPLETE`**: Required teacher data or curriculum structure is missing.
- **`UNDERLOAD`**: Total workload is below `minimum_teaching_hours`.
- **`WITHIN_TARGET`**: Total workload meets or exceeds minimum requirement and is within acceptable parameters.
- **`OVERLOAD`**: Total workload exceeds `maximum_total_hours`.
- **`NO_POLICY`**: No active workload policy matches the teacher's profile.
- **`NEEDS_REVIEW`**: Manual review required due to pending workflow or team teaching split validation.

> Note: All previous references to legacy `BALANCED` status have been fully migrated to `WITHIN_TARGET`.
