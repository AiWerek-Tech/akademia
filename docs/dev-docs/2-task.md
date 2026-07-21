# Task Checklist

## Phase 1: Fix All Extended Tests (100% Pass Rate)
- `[x]` 1.1 Fix `phpunit.xml.dist` — add `app.baseURL` env override
- `[x]` 1.2 Rewrite `ExtendedAuthTest.php` using FeatureTestTrait
- `[x]` 1.3 Rewrite `ExtendedRbacTest.php` using FeatureTestTrait
- `[x]` 1.4 Rewrite `ExtendedUserManagementTest.php` using FeatureTestTrait
- `[x]` 1.5 Rewrite `ExtendedAcademicTest.php` using FeatureTestTrait
- `[x]` 1.6 Rewrite `ExtendedSecurityTest.php` using FeatureTestTrait
- `[x]` 1.7 Rewrite `ExtendedAuditTest.php` (already correct)
- `[x]` 1.8 Run full PHPUnit suite — verify 0 failures

## Phase 2: Migrate SPMB Dashboard UI
- `[x]` 2.1 Create `public/assets/css/foundation.css`
- `[x]` 2.2 Create `public/assets/css/dashboard.css`
- `[x]` 2.3 Create `public/assets/css/admin-dashboard.css`
- `[x]` 2.4 Create `public/assets/css/dashboard-mobile.css`
- `[x]` 2.5 Create `public/assets/js/theme-sync.js`
- `[x]` 2.6 Rewrite `app/Views/layouts/admin.php`
- `[x]` 2.7 Update `app/Views/dashboard.php`
- `[x]` 2.8 Update auth views (login, change_password)
- `[x]` 2.9 Update user views (index, create, edit)
- `[x]` 2.10 Update role/permission views
- `[x]` 2.11 Update academic period/year views & unit views
- `[x]` 2.12 PHPUnit & PHP syntax verification (40/40 tests passed, 0 syntax errors)
- `[x]` 2.13 Composer validate
- `[x]` 2.14 Documentation updates (walkthrough.md created)
