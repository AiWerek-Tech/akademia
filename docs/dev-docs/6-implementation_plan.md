# Implementation Plan — Milestone 4 (Teaching Assignments & Workload Planning)

This plan outlines the design, database schemas, services, controllers, routes, and testing suite to build the **Teaching Assignments & Workload Planning** module (Milestone 4) for WMVAA Akademia.

---

## Proposed Database Schema

We will create a new database migration file `20260722100000_CreateMilestone4Tables.php` to define the database structures.

### 1. `assignment_versions` Table
Tracks versioning and workflow transitions of teaching assignments.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `academic_period_id` INT UNSIGNED NOT NULL
- `curriculum_version_id` BIGINT UNSIGNED NOT NULL
- `code` VARCHAR(50) NOT NULL
- `name` VARCHAR(150) NOT NULL
- `description` TEXT NULL
- `workflow_status` VARCHAR(30) NOT NULL DEFAULT 'DRAFT' (DRAFT, VALIDATED, REVIEWED, APPROVED, LOCKED, REJECTED, ARCHIVED)
- `revision_number` INT NOT NULL DEFAULT 1
- `is_active` TINYINT(1) NOT NULL DEFAULT 0
- `previous_version_id` BIGINT UNSIGNED NULL
- `change_summary` TEXT NULL
- `validated_by` INT UNSIGNED NULL
- `validated_at` DATETIME NULL
- `reviewed_by` INT UNSIGNED NULL
- `reviewed_at` DATETIME NULL
- `approved_by` INT UNSIGNED NULL
- `approved_at` DATETIME NULL
- `locked_by` INT UNSIGNED NULL
- `locked_at` DATETIME NULL
- `archived_by` INT UNSIGNED NULL
- `archived_at` DATETIME NULL
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `created_by` INT UNSIGNED NULL
- `updated_by` INT UNSIGNED NULL
- *Unique Constraints*:
  - `uq_period_code` (`academic_period_id`, `code`)
- *Foreign Keys*:
  - `academic_period_id` -> `academic_periods` (`id`) ON DELETE CASCADE
  - `curriculum_version_id` -> `curriculum_versions` (`id`) ON DELETE RESTRICT

### 2. `teaching_assignments` Table
Maps teachers to curriculum structures (classroom and subject levels) and tracks workload allocation.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `curriculum_structure_id` BIGINT UNSIGNED NOT NULL
- `academic_period_id` INT UNSIGNED NOT NULL
- `unit_id` BIGINT UNSIGNED NOT NULL
- `grade_level_id` BIGINT UNSIGNED NOT NULL
- `classroom_id` BIGINT UNSIGNED NOT NULL
- `subject_id` BIGINT UNSIGNED NOT NULL
- `teacher_id` BIGINT UNSIGNED NOT NULL
- `assignment_role` VARCHAR(30) NOT NULL (PRIMARY, CO_TEACHER, ASSISTANT, SUBSTITUTE, OTHER)
- `assigned_weekly_hours` DECIMAL(5,2) NOT NULL
- `workload_weekly_hours` DECIMAL(5,2) NOT NULL
- `source_weekly_hours` DECIMAL(5,2) NOT NULL
- `allocation_percentage` DECIMAL(5,2) NULL
- `is_primary_teacher` TINYINT(1) NOT NULL DEFAULT 0
- `team_group_uuid` CHAR(36) NULL
- `notes` TEXT NULL
- `status` VARCHAR(30) NOT NULL DEFAULT 'DRAFT' (DRAFT, ACTIVE, INACTIVE, ARCHIVED)
- `revision_number` INT NOT NULL DEFAULT 1
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `deleted_at` DATETIME NULL
- `created_by` INT UNSIGNED NULL
- `updated_by` INT UNSIGNED NULL
- *Foreign Keys*:
  - `assignment_version_id` -> `assignment_versions` (`id`) ON DELETE CASCADE
  - `curriculum_structure_id` -> `curriculum_structures` (`id`) ON DELETE RESTRICT
  - `teacher_id` -> `teachers` (`id`) ON DELETE RESTRICT

### 3. `teaching_assignment_groups` Table
Supports advanced assignment allocation modes (Split Hours, Team Teaching).
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `curriculum_structure_id` BIGINT UNSIGNED NOT NULL
- `allocation_mode` VARCHAR(30) NOT NULL (SINGLE_TEACHER, SPLIT_HOURS, TEAM_TEACHING)
- `required_weekly_hours` DECIMAL(5,2) NOT NULL
- `allocated_weekly_hours` DECIMAL(5,2) NOT NULL
- `workload_calculation_mode` VARCHAR(30) NOT NULL (SAME_AS_ASSIGNED, FULL_FOR_EACH, DIVIDED, MANUAL)
- `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
- `notes` TEXT NULL
- `revision_number` INT NOT NULL DEFAULT 1
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `created_by` INT UNSIGNED NULL
- `updated_by` INT UNSIGNED NULL

### 4. `additional_duty_types` Table
Standard lookup table for additional duties (e.g. Homeroom Teacher, Lab Head).
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `code` VARCHAR(30) NOT NULL UNIQUE
- `name` VARCHAR(100) NOT NULL
- `category` VARCHAR(50) NOT NULL
- `default_workload_hours` DECIMAL(5,2) NULL
- `maximum_holders` INT NULL
- `requires_unit` TINYINT(1) NOT NULL DEFAULT 0
- `requires_period` TINYINT(1) NOT NULL DEFAULT 0
- `counts_toward_workload` TINYINT(1) NOT NULL DEFAULT 1
- `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `sort_order` INT NOT NULL DEFAULT 0
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL

### 5. `teacher_additional_duties` Table
Links teachers to additional duties for a specific period/unit.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `academic_period_id` INT UNSIGNED NOT NULL
- `unit_id` BIGINT UNSIGNED NULL
- `teacher_id` BIGINT UNSIGNED NOT NULL
- `duty_type_id` BIGINT UNSIGNED NOT NULL
- `title_override` VARCHAR(150) NULL
- `workload_hours` DECIMAL(5,2) NULL
- `valid_from` DATE NULL
- `valid_until` DATE NULL
- `reference_number` VARCHAR(100) NULL
- `notes` TEXT NULL
- `status` VARCHAR(30) NOT NULL DEFAULT 'ACTIVE'
- `revision_number` INT NOT NULL DEFAULT 1
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `deleted_at` DATETIME NULL
- `created_by` INT UNSIGNED NULL
- `updated_by` INT UNSIGNED NULL

### 6. `workload_policies` Table
Defines min/max workload settings for teachers per period/unit/employment details.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) NOT NULL UNIQUE
- `academic_period_id` INT UNSIGNED NOT NULL
- `unit_id` BIGINT UNSIGNED NULL
- `employment_status` VARCHAR(50) NULL
- `employment_type` VARCHAR(50) NULL
- `teacher_category` VARCHAR(50) NULL
- `minimum_teaching_hours` DECIMAL(5,2) NULL
- `maximum_teaching_hours` DECIMAL(5,2) NULL
- `target_total_hours` DECIMAL(5,2) NULL
- `maximum_total_hours` DECIMAL(5,2) NULL
- `additional_duty_cap` DECIMAL(5,2) NULL
- `overload_warning_threshold` DECIMAL(5,2) NULL
- `underload_warning_threshold` DECIMAL(5,2) NULL
- `priority` INT NOT NULL DEFAULT 0
- `is_active` TINYINT(1) NOT NULL DEFAULT 1
- `revision_number` INT NOT NULL DEFAULT 1
- `created_at` DATETIME NULL
- `updated_at` DATETIME NULL
- `created_by` INT UNSIGNED NULL
- `updated_by` INT UNSIGNED NULL

### 7. `teacher_workload_snapshots` Table
Snapshots calculated workload metrics for auditing and workflow checkpoints.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `teacher_id` BIGINT UNSIGNED NOT NULL
- `academic_period_id` INT UNSIGNED NOT NULL
- `unit_id` BIGINT UNSIGNED NULL
- `teaching_assigned_hours` DECIMAL(5,2) NOT NULL
- `teaching_workload_hours` DECIMAL(5,2) NOT NULL
- `additional_duty_hours` DECIMAL(5,2) NOT NULL
- `total_workload_hours` DECIMAL(5,2) NOT NULL
- `policy_id` BIGINT UNSIGNED NULL
- `policy_minimum` DECIMAL(5,2) NULL
- `policy_target` DECIMAL(5,2) NULL
- `policy_maximum` DECIMAL(5,2) NULL
- `shortage_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00
- `overload_hours` DECIMAL(5,2) NOT NULL DEFAULT 0.00
- `status` VARCHAR(30) NOT NULL (INCOMPLETE, UNDERLOAD, WITHIN_TARGET, OVERLOAD, NO_POLICY, NEEDS_REVIEW)
- `details_json` TEXT NULL
- `calculated_at` DATETIME NOT NULL
- `calculated_by` INT UNSIGNED NULL

### 8. `assignment_validation_results` Table
Stores issues found during assignment audits.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `teaching_assignment_id` BIGINT UNSIGNED NULL
- `teacher_id` BIGINT UNSIGNED NULL
- `validation_code` VARCHAR(50) NOT NULL
- `severity` VARCHAR(20) NOT NULL DEFAULT 'WARNING' (INFO, WARNING, ERROR, BLOCKER)
- `message` TEXT NOT NULL
- `details_json` TEXT NULL
- `is_resolved` TINYINT(1) NOT NULL DEFAULT 0
- `resolved_by` INT UNSIGNED NULL
- `resolved_at` DATETIME NULL
- `created_at` DATETIME NULL

### 9. `assignment_revision_history` Table
Immutable audit log of assignment modifications.
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `assignment_version_id` BIGINT UNSIGNED NOT NULL
- `entity_type` VARCHAR(50) NOT NULL
- `entity_id` BIGINT UNSIGNED NULL
- `revision_number` INT NOT NULL
- `action` VARCHAR(50) NOT NULL
- `before_json` LONGTEXT NULL
- `after_json` LONGTEXT NULL
- `change_reason` TEXT NULL
- `actor_id` INT UNSIGNED NULL
- `created_at` DATETIME NULL

### 10. `assignment_import_batches` & `assignment_import_rows` Tables
Staging areas for assignment Excel spreadsheets.
- Structured similarly to curriculum import batches/rows, but tailored to assignments, mapping raw Excel rows to units, grade levels, classrooms, subjects, and teachers.

---

## Core Services & Services Architecture

We will implement several business logic services:
- **`AssignmentVersionService`**: Implements creation, cloning, activation, workflow transitions, revisioning, and validation checks.
- **`TeacherWorkloadCalculationService`**: Computes allocated teaching hours, workload hours, additional duties, and matches them against active `WorkloadPolicy` rules.
- **`AssignmentValidationService`**: Evaluates assignments against structure requirements, teacher status, unit mappings, and workload capacities.
- **`AssignmentImportService`**: Validates uploaded Excel workbooks, parses rows, stages data, resolves entity IDs, and commits transactions.

---

## Verification Plan

We will add a new test class `tests/database/Milestone4AcceptanceTest.php` containing tests for all core features:
- Core migrations rollback and clean execution.
- Seeding default duty types and permissions.
- Workload policy matching and priorities.
- Single teacher, split hours, and team teaching workload calculation modes.
- Staging and importing data with validation results.
- Isolation and RBAC checks between SMP and SMA units.
