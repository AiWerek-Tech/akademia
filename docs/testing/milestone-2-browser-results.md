# Milestone 2 — Browser Testing Results

**Date**: 2026-07-21
**Browser**: Chrome Headless (via Browser Subagent)
**Test Target**: `http://app.wmvaa.local/wmvaa-akademia/`
**Status**: ✅ **PASSED**

---

## Verified Scenarios

### Login Page
- **URL**: `http://app.wmvaa.local/wmvaa-akademia/public/login`
- **Actions**:
  1. Input `admin` into Username field.
  2. Input the development admin password.
  3. Click **Masuk** (Login).
- **Result**: Successfully authenticated, session regenerated, and redirected to `http://app.wmvaa.local/wmvaa-akademia/dashboard`.

### Dashboard Page (Role: Super Admin)
- **URL**: `http://app.wmvaa.local/wmvaa-akademia/dashboard`
- **Result**: Page loaded with full navigation sidebar, school unit selector, and academic period context. Access granted to all master data features.

---

## Known Limitations

- **Browser Viewports**: Chrome Headless default desktop viewport (1920×945) verified. Responsive views (tablet, mobile) not exhaustively tested using automation.
- **Roles Matrix**: Only `super_admin` role verified through browser login. Other roles (`admin_smp`, `admin_sma`, `wakasek_kurikulum`, `guru`) verified through PHPUnit feature testing (`ExtendedRbacTest`).
