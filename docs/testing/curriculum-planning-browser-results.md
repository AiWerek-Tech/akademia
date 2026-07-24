# Browser Matrix & User Role Acceptance Matrix

## 1. User Roles Matrix
| Role | Unit Scope | Access Level | Test Result |
| :--- | :--- | :--- | :--- |
| `super_admin` | All (SMP & SMA) | Full Read/Write | **PASSED** |
| `admin_smp` | Unit 1 (SMP) | Unit 1 Read/Write; Cross-Unit 403/Redirect | **PASSED** |
| `admin_sma` | Unit 2 (SMA) | Unit 2 Read/Write; Cross-Unit 403/Redirect | **PASSED** |
| `wakasek_kurikulum` | Unit Assigned | Read/Write Planning & Matrix | **PASSED** |
| `kepala_sekolah` | Unit Assigned | Read-Only View | **PASSED** |
| `viewer` | Unit Assigned | Read-Only View; Form Submissions 403 | **PASSED** |

## 2. Responsive Viewports Matrix
- **1920×1080 (Desktop Wide)**: **PASSED** (Full matrix grid, capacity indicators, settings drawer render without overflow).
- **1366×768 (Laptop Standard)**: **PASSED** (Horizontal scrolling on subject matrix grid enabled, summary cards responsive).
- **390×844 (Mobile Portrait)**: **PASSED** (Mobile navigation hamburger menu accessible, summary cards stacked vertically).

## 3. UI Component Actions Verified
- Planning overview summary cards (Official Hours, Effective Hours, Required Total Hours, Available Capacity, FTE Estimate, Unallocated Hours).
- 5-Day SMP / 6-Day SMA day code selectors.
- Official vs Custom weekly hours toggle.
- Mandatory custom reason input field.
- Locked curriculum version alert banner.
