# Milestone 3 Route Security Audit & Repair

This document summarizes the route audit findings and the security repairs applied to ensure that all administrative paths are fully guarded server-side.

## 1. Audit Findings
During the audit, we identified that administrative routes introduced in Milestone 0–2 (e.g. `/teachers`, `/subjects`, `/grade-levels`, `/classrooms`, `/rooms`, `/imports/master`, and `/duplicates`) did not have explicit filters configured globally in `app/Config/Filters.php`. Instead, they relied on controller-side `has_permission` checks.
While controller-side checks are safe, a guest request was allowed to execute the controller method before getting rejected, resulting in a redirect to `/dashboard` rather than being blocked at the routing layer and redirected to `/login`.

## 2. Implemented Route Protections
We updated [Filters.php](file:///E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Filters.php) to protect all administrative paths using three layered filters:
- **`auth`**: Ensures the user has a valid active session. Otherwise, redirects to `/login`.
- **`password_change_required`**: Requires user password change if the `must_change_password` flag is set in their account metadata.
- **`unit_access`**: Validates the active user unit context.

The following routes are now fully protected:
- `/teachers` and `/teachers/*`
- `/subjects` and `/subjects/*`
- `/grade-levels` and `/grade-levels/*`
- `/classrooms` and `/classrooms/*`
- `/rooms` and `/rooms/*`
- `/imports/master` and `/imports/master/*`
- `/duplicates` and `/duplicates/*`
- `/users` and `/users/*`
- `/roles` and `/roles/*`
- `/academic-years` and `/academic-years/*`
- `/academic-periods` and `/academic-periods/*`
- `/curriculum` and `/curriculum/*`

## 3. Acceptance Tests Updated
- **`testM01_NoSessionRedirectsToLogin`**: Updated to assert that a guest request to `/teachers` is intercepted and redirected directly to `/login`.
- **`testM03_TeachersGuestControllerNotExecuted`**: Verifies that the controller method is never reached by a guest, and session is not polluted with unit redirect logic.
