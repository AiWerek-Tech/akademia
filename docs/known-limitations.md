# System Known Limitations

## 1. Curriculum Planning & Scheduling
- **Teacher FTE Estimation**: The FTE calculation assumes standard full-time teaching capacity and does not account for individual medical leave or partial semester sabbaticals.
- **Fixed Activity Scheduling**: Non-teaching fixed activities (e.g., *Chapel*, *Upacara*, *SID*) are reserved as block slots across all grade levels; custom room assignment for sub-groups must be finalized in Milestone 5 matrix editor.
- **Day Code Customization**: Active day codes support standard 7-day ISO identifiers (`MON`–`SUN`). Split shift schedules across midnight are not supported in the current version.

## 2. Multi-Unit Operations
- Admin scope separation relies on session-bound `unit_access` parameters. Cross-unit API requests without authorized unit access are rejected with HTTP 403 / redirect to dashboard.
