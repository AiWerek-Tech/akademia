# Schedule Security & RBAC Results — Milestone 5

## Security Controls Enforced
1. **CSRF Protection**: All POST/PUT/DELETE scheduling routes enforce CSRF token verification.
2. **Unit Isolation (`UnitScopeService`)**: Single source of truth for unit boundaries. SMP users cannot manage or view SMA schedules unless explicit cross-unit permission is granted.
3. **Cross-Unit Teacher Global Availability**: Global cross-unit query checks prevent a teacher from being assigned to SMP and SMA in the exact same time slot.
4. **Optimistic Concurrency Control (OCC)**: `schedule_versions.revision_number` ensures atomic updates and prevents overwrite of stale edits.
5. **Immutability of Locked Versions**: Schedule versions with `workflow_status` in `['LOCKED', 'ARCHIVED']` reject all entry additions, edits, or deletions.
