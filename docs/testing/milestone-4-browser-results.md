# Milestone 4 Browser & Security Acceptance Verification Matrix

**Date**: 2026-07-22  
**Tested Viewports**: Desktop (`1920×1080`), Laptop (`1366×768`), Mobile (`390×844`)  
**Status**: PASSED (0 Console Errors, 0 Network Errors)  

---

## 1. Role-Based Access Matrix

| Page / Feature | Super Admin | Admin SMP | Admin SMA | Wakasek | Kepala Sekolah | Guru / Viewer |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| Assignment Versions List | Full Access | SMP Scope | SMA Scope | Read-Only | Read-Only | View Assigned |
| Assignment Matrix View | Full Access | SMP Scope | SMA Scope | Read-Only | Read-Only | View Assigned |
| Teaching Assignments Edit | Full Access | SMP Scope | SMA Scope | Blocked | Blocked | Blocked |
| Team Teaching Config | Full Access | SMP Scope | SMA Scope | Blocked | Blocked | Blocked |
| Additional Duties | Full Access | SMP Scope | SMA Scope | Blocked | Blocked | View Self |
| Workload Policies | Full Access | SMP Scope | SMA Scope | Read-Only | Read-Only | Blocked |
| Workload Dashboard | Full Access | SMP Scope | SMA Scope | Unit Scope | Unit Scope | View Self |
| Import Staging | Full Access | SMP Scope | SMA Scope | Blocked | Blocked | Blocked |
| Export Reports | Full Access | SMP Scope | SMA Scope | Unit Scope | Unit Scope | Blocked |
| Workflow Actions (Approve/Lock)| Full Access | SMP Scope | SMA Scope | Review Only | Approve Only | Blocked |
| Revision History | Full Access | SMP Scope | SMA Scope | Read-Only | Read-Only | Blocked |

---

## 2. Responsive Viewport Audits

- **1920×1080 (Desktop)**: Full matrix rendering with side-by-side teacher workload panels. Clean alignment, 0 overflow.
- **1366×768 (Laptop)**: Sticky header on matrix table, horizontal scroll container for wide curriculum grids.
- **390×844 (Mobile)**: Stacked card view for workload summaries and duty assignments. Clean mobile drawer navigation.

---

## 3. Security Boundary Verification Scenarios

| Security Scenario | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :---: |
| **Cross-Unit Query** | Admin SMP accessing SMA version ID via URL | Redirected with 403 Forbidden / Unit Scope Error | PASSED |
| **Direct UUID Tampering** | Manipulating assignment UUID in POST data | Request rejected; entity scope validated | PASSED |
| **Form Field Tampering** | Submitting unassigned subject IDs | Rejected by `AssignmentValidationService` | PASSED |
| **Import Batch Ownership** | Accessing import batch of another unit | Blocked by unit filter service | PASSED |
| **Export Scope Limit** | Exporting workload report across unauthorized units | Filtered strictly to authorized unit | PASSED |
| **Stale Revision Locking** | Updating version with outdated `revision_number` | Prevented via optimistic locking exception | PASSED |
| **Locked Edit Prevention** | Modifying assignment in `LOCKED` version | Blocked with version immutability warning | PASSED |
