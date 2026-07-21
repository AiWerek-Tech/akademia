# Implementation Plan - Milestone 0: Discovery & Foundation

This plan outlines the steps to initialize the **WMVAA Akademia** project (Integrated Academic Planning System) as a separate, fresh codebase. It focuses on establishing the project structure, development environment, base UI shell, database connectivity, and automated testing framework without touching the legacy "Kumer" application.

---

## User Review Required

> [!IMPORTANT]
> - **PHP Version Compatibility**: The local environment runs PHP 7.4.29. CodeIgniter 4.5+ requires PHP 8.1+. Therefore, we will initialize **CodeIgniter 4.4.8** (which supports PHP 7.4/8.0) to ensure local compatibility and smooth deployment on cPanel.
> - **Project Location**: The project will be initialized in `E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia` as requested. It is completely isolated from the legacy `kumer` directory.
> - **Database Configuration**: We will configure connection settings for the database `wmvaa_akademia`. Please ensure this database exists or can be created in your local MySQL.

---

## Open Questions

> [!NOTE]
> There are no blockers for Milestone 0, but please let us know if:
> 1. You have a preferred local MySQL username/password for the `.env` configuration (defaulting to standard root/no-password).
> 2. You have a specific Git branch naming convention (defaulting to `main` or `master`).

---

## Proposed Changes

We will perform setup and file creation across the following areas:

### 1. CodeIgniter 4.4.8 Initialization

We will initialize a clean CodeIgniter 4 project using Composer targeting version `4.4.8`.

#### [NEW] [composer.json](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/composer.json)
- Set up core dependencies and autoloader rules.

#### [NEW] [env](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/env) & [.env](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/env)
- Environment file copying and database `wmvaa_akademia` settings configuration.

#### [NEW] [.gitignore](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/.gitignore)
- Configure Git to ignore `.env`, `writable/` session/cache files, `vendor/` directory, and dynamically generated directories (`imports/`, `exports/`, `backups/`).

---

### 2. Architecture & Database Documentation

We will establish documentation files inside the `docs/` folder.

#### [NEW] [architecture.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/architecture.md)
- Detail CodeIgniter 4 modular structure, controllers, service layers, repositories, and authentication plan.

#### [NEW] [database-schema.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/database-schema.md)
- Define initial MySQL schemas for school units, academic periods, users, roles, and permissions.

#### [NEW] [milestone-plan.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/milestone-plan.md)
- Document milestones 0 through 12, listing the deliverables and transition checklist.

---

### 3. Base UI Layout (Admin Shell)

We will build a responsive sidebar-based layout shell with Tailwind/Bootstrap.

#### [NEW] [Home.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/Home.php)
- Home controller to serve the dashboard landing page.

#### [NEW] [layout.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/layouts/admin.php)
- Base layout template using Bootstrap 5, Bootstrap Icons, sidebar navigation, top bar navigation with Unit and Semester filters, and main content area.

#### [NEW] [dashboard.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Views/dashboard.php)
- Basic dashboard content with widgets/cards representing empty placeholders for next milestones.

---

### 4. Health Check & Core Configs

#### [NEW] [Health.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Controllers/Health.php)
- Verify DB connection, PHP version, write privileges on `writable/`, and return JSON status.

#### [NEW] [Routes.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Routes.php)
- Define standard web routes for home, health check, and diagnostic routes.

---

## Verification Plan

### Automated Tests
- Run PHPUnit tests to verify system boot:
  ```bash
  vendor/bin/phpunit
  ```

### Manual Verification
1. Launch local PHP development server:
   ```bash
   php spark serve
   ```
2. Navigate to `http://localhost:8080/health` to confirm database connection and environment checks return a `"status": "OK"` response.
3. Access the dashboard shell at `http://localhost:8080` to verify the Bootstrap-based responsive layout, topbar filters, sidebar navigation, and loading states.
