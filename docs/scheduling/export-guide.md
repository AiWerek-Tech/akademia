# Schedule Export Guide & Grid Matrix Specification — Milestone 5

## Export Capabilities
The `ScheduleExportService` generates grid views and downloadable schedule reports for:
1. **Classroom Schedule Grid**: Matrix of days x slots for a specific classroom (`getGridForClassroom`).
2. **Teacher Schedule Grid**: Matrix of days x slots for a specific teacher across all assigned units (`getGridForTeacher`).
3. **Room Schedule Grid**: Matrix of days x slots for a specific room (`getGridForRoom`).
4. **School Unit Overview Grid**: Complete schedule matrix for an entire school unit (`getGridForUnit`).

## Security & Formula Neutralization
- Exported spreadsheet values are neutralized against CSV/Excel formula injection (leading `=`, `+`, `-`, `@` characters escaped with single quotes).
- Unit boundary isolation (`UnitScopeService`) strictly enforced; users can only export schedule data for units to which they have explicit access.
- Audit log records all export requests with user ID, version ID, unit ID, and timestamp.
