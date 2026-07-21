# Milestone 1 Completion + SPMB Dashboard UI Migration

## Phase 1: Fix All Extended Tests (100% Pass Rate)

### Root Cause Analysis

The 12 test failures fall into 3 categories:

1. **POST data not reaching controllers** — `ControllerTestTrait` creates a request object once during `setUp()`. When `setGlobal('post', [...])` is called later, the internal `$globals['post']` may already be lazily populated as empty from `$_POST` (which is empty in tests). The correct approach is to use **`FeatureTestTrait`** which creates a fresh request per `call()` and properly injects POST params via `populateGlobals()`.

2. **URL mismatch in redirects** — The `.env` sets `app.baseURL = 'http://app.wmvaa.local/wmvaa-akademia/'` which overrides the `phpunit.xml.dist` setting of `http://example.com/`. So `redirect()->to('/dashboard')` generates `http://app.wmvaa.local/wmvaa-akademia/dashboard`, but `assertRedirectTo('/dashboard')` tries to match `site_url('/dashboard')` which also resolves to the same. The fix is to **override `app.baseURL` in the phpunit.xml.dist** using `<env>` tag so tests use a clean, predictable base URL.

3. **Audit sanitization assertion error** — The `AuditService::sanitize()` method replaces sensitive keys with `'[REDACTED]'` instead of removing them. Tests wrongly asserted `assertArrayNotHasKey`. Fixed in latest rewrite.

### Proposed Changes

#### [MODIFY] [phpunit.xml.dist](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/phpunit.xml.dist)
- Add `<env name="app.baseURL" value="http://example.com/"/>` to override `.env` during tests
- This ensures `site_url()` and `redirect()->to()` produce predictable URLs

#### [MODIFY] All 6 Extended test files in `tests/database/`
- Switch from `ControllerTestTrait` to `FeatureTestTrait` for all tests that need POST/GET simulation
- Use `$this->post('/login', [...])` instead of `$this->controller(AuthController::class)->execute('attemptLogin')`
- Use `$this->get('/users')` for GET requests
- Use `$this->withSession([...])` for session injection
- Keep `DatabaseTestTrait` for migration/seeding
- Fix redirect assertions to use `assertRedirectTo()` with path strings

---

## Phase 2: Migrate SPMB Dashboard UI to WMVAA Akademia

### Architecture Summary — SPMB Dashboard

The SPMB admin dashboard uses a **layered CSS design system**:

| Layer | File | Purpose |
|---|---|---|
| 1. Tokens | `foundation.css` | CSS custom properties (colors, spacing, shadows, radius, transitions), glassmorphism, animations |
| 2. Layout | `dashboard.css` | Sidebar, navbar, content area, collapsed sidebar, responsive breakpoints |
| 3. Admin Polish | `admin-dashboard.css` | Hero panels, stat card grids, task lists, admin-specific widgets |
| 4. Mobile | `dashboard-mobile.css` + `mobile-first-polish.css` | Touch targets, mobile sidebar overlay, responsive tables |

**Technology stack:**
- Bootstrap 5.3 CSS + JS
- Google Fonts: Poppins, Inter, Plus Jakarta Sans
- Lucide Icons (SVG icon library, loaded via CDN with local fallback)
- DataTables, Select2, SweetAlert2, Flatpickr, ApexCharts
- Custom theme-sync.js (dark mode)
- `<?= $this->extend('layouts/dashboard') ?>` layout system (CI4 View Cells)

### Current WMVAA Akademia Dashboard

- Uses **inline `<style>` block** inside `layouts/admin.php` (606 lines, ~23KB)
- Bootstrap 5.3 + Bootstrap Icons
- Plus Jakarta Sans font
- SweetAlert2
- No external CSS files, no design tokens, no dark mode
- No collapsible sidebar, no mobile optimization

### Migration Plan

#### [NEW] `public/assets/css/foundation.css`
- Copy and adapt from SPMB's `foundation.css`
- Rebrand design tokens: `--sp-*` → `--ak-*` (Akademia prefix)
- Keep the same color palette and spacing scale
- Keep glassmorphism and animation utilities

#### [NEW] `public/assets/css/dashboard.css`
- Copy and adapt from SPMB's `dashboard.css`
- Rename CSS class prefixes where needed
- Include sidebar collapsed state, glassmorphism navbar, responsive breakpoints
- Adapt sidebar menu items for WMVAA Akademia navigation (Dashboard, Users, Roles, Academic Periods, Settings, Audit Logs)

#### [NEW] `public/assets/css/admin-dashboard.css`
- Copy and adapt SPMB's `admin-dashboard.css`
- Hero panel, stat card grid, task list, admin widgets

#### [NEW] `public/assets/css/dashboard-mobile.css`
- Copy from SPMB for mobile responsiveness

#### [NEW] `public/assets/js/theme-sync.js`
- Copy from SPMB for dark mode toggle support

#### [MODIFY] [admin.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/layouts/admin.php)
- Remove all inline `<style>` blocks
- Add `<link>` references to new external CSS files
- Restructure HTML to match SPMB layout: `layout-wrapper` > `sidebar` + `main-container` > `navbar` + `content-body`
- Add Lucide Icons CDN with local fallback
- Add collapsible sidebar support
- Add dark mode toggle
- Add mobile sidebar overlay
- Add `renderSection('content')` and `renderSection('additional_css')` for child views

#### [MODIFY] [dashboard.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/dashboard.php)
- Restructure to use `$this->extend('layouts/admin')` + `$this->section('content')`
- Add hero panel, stat cards, priority tasks, audit log widgets following SPMB patterns
- Use Lucide icons

#### [MODIFY] All other view files (users/, roles/, academic_years/, academic_periods/, auth/, system/)
- Switch to the new `$this->extend('layouts/admin')` pattern
- Use consistent card styling and form patterns from the SPMB design system

> [!IMPORTANT]
> The `.env` file already exists and is **not tracked in Git**. All CSS/JS assets will be placed in `public/assets/` which IS tracked.

## Verification Plan

### Phase 1 Verification
- Run `E:\xampp\php82\php.exe vendor/bin/phpunit --display-warnings --display-deprecations` 
- All 36+ tests must pass with 0 failures, 0 errors
- Only allowed warning: "No code coverage driver available"

### Phase 2 Verification
- Launch browser subagent to visually verify:
  - Login page renders correctly
  - Dashboard shows hero panel, stat cards, sidebar
  - Sidebar collapses/expands
  - Mobile responsive layout works
  - Dark mode toggle works
  - All existing pages (Users, Roles, Academic Periods) render within new layout
