# Database Backup Policy

This document outlines the security policies, constraints, and instructions for backing up database resources.

## 1. Schema-Only Backups
- **Policy**: Schema-only backups (DDL structures) are allowed to be tracked in version control when necessary for development architecture documentation.
- **Command**:
  ```bash
  mysqldump -u [username] -p --no-data [database_name] > [output_file.sql]
  ```
- **Rule**: You must always include the `--no-data` option to prevent operational tables or user records from being included in the export.

## 2. Private Data Backups
- **Policy**: Data backups containing operational rows, settings, audit logs, or user records must **never** be committed to version control or saved within the workspace repository.
- **Storage**: Full backups must be exported to a secure location outside the repository root directory (e.g. secure cloud storage, private backups directory).
- **Git Ignore**: Stored SQL file exports are explicitly blocked in `.gitignore` via:
  ```
  *.sql
  backups/
  docs/dev-docs/*.sql
  ```

## 3. Encryption & Access
- **Policy**: All backups containing personal data or credentials must be encrypted at rest (AES-256 or similar) before storage.
- **Access Control**: Access to backups must be restricted via RBAC to authorized administrators only.

## 4. Retention Policy
- **Policy**: Backups must be retained in accordance with school policies (e.g. 30 days retention for daily backups, 12 months for monthly archives). Outdated backups must be securely purged.

## 5. Restore Testing
- **Policy**: Restoring testing procedures must be executed regularly (e.g. quarterly) on staging databases to verify dump integrity and disaster recovery readiness.
