# Curriculum Planning Profile Architecture

## 1. Overview
The Planning Profile architecture provides dynamic configuration of school operating parameters per curriculum version and school unit. It removes hardcoded 5-day / 6-day constants and allows flexible configuration of teaching days, active day codes, daily JP capacity, and link to workload policies.

## 2. Configuration Schema (`curriculum_planning_settings`)
- `curriculum_version_id`: Target curriculum version ID (`BIGINT UNSIGNED`).
- `unit_id`: Target school unit ID (`BIGINT UNSIGNED`).
- `teaching_days_per_week`: Number of operating days per week (1–7 days, default 5 for SMP, 6 for SMA).
- `selected_day_codes_json`: Active day codes array (`['MON', 'TUE', 'WED', 'THU', 'FRI']` or `['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']`).
- `daily_jp_capacity`: Standard daily JP capacity (default 9.00 JP).
- `allow_custom_hours`: Flag allowing local custom hours overrides (TINYINT 1).
- `workload_policy_id`: Optional link to `workload_policies.id` (`BIGINT UNSIGNED NULLABLE`).
- `revision_number`: Optimistic locking counter (`INT UNSIGNED`).

## 3. Capacity Calculation
$$\text{Weekly Capacity} = \text{teaching\_days\_per\_week} \times \text{daily\_jp\_capacity}$$

- **SMP 5-Day Preset**: $5 \times 9.00 = 45.00\text{ JP/week capacity}$.
- **SMA 6-Day Preset**: $6 \times 9.00 = 54.00\text{ JP/week capacity}$.

## 4. Immutability & Scope
- If `workflow_status` of the curriculum version is `LOCKED`, `APPROVED`, or `ARCHIVED`, modifications are strictly rejected.
- Unit isolation enforces scope boundaries between SMP (unit 1) and SMA (unit 2).
