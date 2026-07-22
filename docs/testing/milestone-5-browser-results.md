# Browser Acceptance & Security Matrix Results — Milestone 5

## Matrix Verification Overview
All administrative scheduling pages and UI workflows were verified across roles and viewports.

## Role Verification Matrix
| Role | View Schedules | Manage Schedules | Run Generator | Stage Import | Export Reports | Unit Scope Enforced |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `super_admin` | YES | YES | YES | YES | YES | All Units Allowed |
| `admin_smp` | YES | YES | YES | YES | YES | Isolated to SMP |
| `admin_sma` | YES | YES | YES | YES | YES | Isolated to SMA |
| `wakasek_kurikulum` | YES | YES | YES | YES | YES | Assigned Unit Only |
| `kepala_sekolah` | YES | Read-Only | Read-Only | Read-Only | YES | Assigned Unit Only |
| `guru` | Own Schedule | NO | NO | NO | Own Schedule | Personal Scope |
| `viewer` | Read-Only | NO | NO | NO | Read-Only | Read-Only |

## Viewport Compatibility
- **Desktop (1920x1080)**: Full grid view, editor drag-and-drop/click placement, conflict panel side-by-side — PASSED.
- **Laptop (1366x768)**: Responsive layout, sticky table headers, collapsible sidebar — PASSED.
- **Mobile (390x844)**: Stacked cards view for schedule entries, mobile-friendly slot selector — PASSED.

## Security Scenarios
- **Cross-Unit Tampering**: Tampering `unit_id` in URL parameters causes `UnitScopeService` exception (403 Forbidden).
- **Direct UUID Access**: Attempting to access schedule version UUID of an unauthorized unit is blocked.
- **Stale Revision Locking**: Modifying locked schedule versions (`LOCKED` or `ARCHIVED`) returns 400 Bad Request.
- **CSRF Token Enforcement**: POST/PUT/DELETE actions without CSRF token are rejected.
