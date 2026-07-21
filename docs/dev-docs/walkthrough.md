# Walkthrough - Milestone 0: Discovery & Foundation

We have successfully initialized the new **WMVAA Akademia** (Integrated Academic Planning System) application. It is completely isolated from the legacy "Kumer" application.

---

## Changes Made

### 1. Project Initialization & Git Setup
- Initialized a new CodeIgniter **4.4.8** application in `e:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia` compatible with **PHP 7.4.29**.
- Initialized Git repository and set up a robust `.gitignore` file that ignores local configurations (`.env`) and directories (`writable/*`, `imports/*`, `exports/*`, `backups/*`).
- Created the required dynamic folders `imports/`, `exports/`, and `backups/` containing `.gitkeep` files.
- Made the initial Git repository commit.

### 2. Configuration & Databases
- Created and configured `.env` and `.env.example` with connection parameters for the local MySQL databases:
  - Default database: `wmvaa_akademia`
  - Testing database: `wmvaa_akademia_test`
- Created the databases `wmvaa_akademia` and `wmvaa_akademia_test` in MySQL.

### 3. Documentation
Established a clean documentation structure inside the `docs/` folder:
- [architecture.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/architecture.md): Blueprint of modular structures, services, repositories, and security layers.
- [database-schema.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/database-schema.md): Schemas for school units, academic periods, users, roles, and permissions.
- [milestone-plan.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/milestone-plan.md): Project milestones tracker and deliverables definition.

### 4. Admin UI Shell Layout
- Created the base dashboard layout view [admin.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/layouts/admin.php) with a sidebar and topbar containing filters for **Unit (SMP/SMA)** and **Semester/Tahun Ajaran**.
- Implemented the dashboard landing view [dashboard.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/dashboard.php) presenting system statistics, system health checks, and a checklist of remaining milestones.
- Modified the default `Home` controller to serve the dashboard.

### 5. Health Check Endpoint
- Implemented a JSON health check controller [Health.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/Health.php) checking database connection initialization and folder write privileges.
- Added route rules in [Routes.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php).

---

## Verification Results

### Automated Tests
- Ran PHPUnit tests to verify system boot, configurations, and controllers.
- Created unit tests for the endpoints:
  - `Tests\HealthControllerTest`: verifies database connectivity and write permissions return a successful JSON structure.
  - `Tests\HomeControllerTest`: verifies the dashboard renders containing relevant system labels.
- **Result**: `OK (7 tests, 18 assertions)`

### Linting Checks
- Executed `php -l` recursively on all PHP files in `app/` and `tests/`.
- **Result**: `No syntax errors detected`
