# Conflict Detection Engine Specification — Milestone 5

## Overview
The `ScheduleConflictDetectionService` performs comprehensive validation against schedule entries in a schedule version.

## Hard Constraints (Critical Severity)
- `HARD_TEACHER_DOUBLE_BOOKING`: A teacher assigned to multiple classrooms in the same day slot within the same school unit.
- `HARD_CLASSROOM_DOUBLE_BOOKING`: A classroom assigned to multiple subjects/teachers in the same day slot.
- `HARD_ROOM_DOUBLE_BOOKING`: A room assigned to multiple entries in the same day slot.
- `HARD_CROSS_UNIT_TEACHER_DOUBLE_BOOKING`: A teacher assigned to a slot in SMP while simultaneously assigned to the same time slot in SMA (global cross-unit check).
- `HARD_TEACHER_UNAVAILABLE`: Entry assigned to a slot where the teacher has an explicit unavailable rule.
- `HARD_ROOM_UNAVAILABLE`: Entry assigned to a slot where the room is blocked.
- `HARD_ROOM_TYPE_MISMATCH`: Room assigned to a subject requiring a specific room type (e.g. Science Lab) does not match.

## Soft Constraints (Warning Severity & Scoring Penalty)
- `SOFT_TEACHER_MAX_DAILY_HOURS`: Teacher daily hours exceeding recommended maximum.
- `SOFT_TEACHER_CONSECUTIVE_SLOTS`: Teacher consecutive slots exceeding threshold without break.
- `SOFT_CLASSROOM_GAP_MINIMIZATION`: Gaps (idle slots) between lessons for a classroom during a day.
- `SOFT_PREFERRED_ROOM`: Assignment placed in a room other than the preferred room configured in curriculum structure.
