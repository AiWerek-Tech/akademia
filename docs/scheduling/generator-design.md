# Generator Design & Algorithm Specification — Milestone 5

## Overview
The scheduling engine in Akademia uses the **`DeterministicGreedyScheduleGenerator`**.

## Key Algorithm Characteristics
- **Deterministic**: Given identical input state (version requirements, slots, rules, and locks), the generator produces exact, reproducible outputs across repeated executions.
- **Reproducible**: No random seed generation or stochastic mutation is employed.
- **Greedy**: Places requirements sequentially into available slots based on constraint heuristics.
- **Best-Effort**: Attempts maximum slot placement without breaking hard constraints.
- **Constraint-Aware**: Strictly enforces hard constraints (teacher overlap, classroom overlap, room overlap, cross-unit availability) and evaluates soft constraints (load balancing, gap minimization).
- **May Return Partial Result**: If conflict-free placement is impossible for certain slots, unplaced requirements are placed into an unscheduled queue.
- **Not Guaranteed Globally Optimal**: As a greedy heuristic algorithm, it does not guarantee finding a global mathematical optimum (which would require NP-hard integer linear programming or constraint programming solvers).

## Execution Lifecycle
1. **Fetch & Freeze Inputs**: Requirements, active day slots, teacher availability rules, room availability rules.
2. **Preserve Locks**: Pre-existing locked entries (`schedule_locks` or manually pinned entries) are preserved without modification.
3. **Sequential Placement**: Iterates through unscheduled requirements, attempting slot assignments.
4. **Hard Constraint Validation**:
   - M1: Teacher Double Booking (same unit)
   - M2: Classroom Double Booking
   - M3: Room Double Booking
   - M4: Cross-Unit Teacher Double Booking (global SMP vs SMA check)
   - M5: Teacher Unavailability Rule
   - M6: Room Type Mismatch
5. **Soft Score Evaluation**: Calculates penalty scores for soft constraints (`SOFT_TEACHER_MAX_DAILY_HOURS`, `SOFT_TEACHER_CONSECUTIVE_SLOTS`, `SOFT_CLASSROOM_GAP_MINIMIZATION`, `SOFT_PREFERRED_ROOM`).
6. **Candidate Staging**: Generates candidate solution in `schedule_generation_candidates` and `schedule_candidate_entries` with `is_applied = 0`.
7. **Manual Review & Apply**: Requires explicit user action (`applyCandidate`) to merge candidate entries into live `schedule_entries`.
