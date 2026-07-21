# Database Schema - WMVAA Akademia

This document outlines the database schema draft for **WMVAA Akademia**. It defines entities, relations, indices, and constraints.

---

## 1. Key Database Rules

- **Engine**: InnoDB (support for Foreign Keys and Transactions).
- **Charset**: `utf8mb4_unicode_ci`.
- **Soft Delete**: `deleted_at` field present on transactional entities.
- **Audit Columns**: `created_at`, `updated_at`, `created_by`, `updated_by` are mandatory.
- **UUIDs**: UUIDs used for external references (URLs, PDFs) to prevent ID scraping.
- **IDs**: Auto-incrementing BIGINT as primary key internally.

---

## 2. Preliminary Schema Definition (Milestone 0)

### A. Organization & Period

```sql
-- 1. School Units
CREATE TABLE `school_units` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) UNIQUE NOT NULL,       -- 'SMP', 'SMA'
  `name` VARCHAR(100) NOT NULL,
  `level` VARCHAR(20) NOT NULL,              -- 'SMP', 'SMA'
  `npsn` VARCHAR(30) UNIQUE,
  `address` TEXT,
  `phone` VARCHAR(30),
  `email` VARCHAR(100),
  `logo_path` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Academic Years
CREATE TABLE `academic_years` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(20) UNIQUE NOT NULL,        -- '2026/2027'
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Semesters
CREATE TABLE `semesters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `academic_year_id` INT NOT NULL,
  `number` INT NOT NULL,                      -- 1, 2
  `name` VARCHAR(50) NOT NULL,               -- 'Semester Ganjil', 'Semester Genap'
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` VARCHAR(20) DEFAULT 'DRAFT',      -- 'DRAFT', 'ACTIVE', 'LOCKED'
  `is_active` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### B. Users & Permissions

```sql
-- 4. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) UNIQUE NOT NULL,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Roles
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. Permissions
CREATE TABLE `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

---

## 3. General Indices & optimization
- Index `unit_id` across classrooms and schedules to allow faster lookups.
- Composite index on `academic_period_id` and `teacher_id` for quick assignment lookups.
- UUID columns indexed uniquely.
