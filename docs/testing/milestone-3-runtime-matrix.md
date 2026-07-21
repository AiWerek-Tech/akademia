# Milestone 3 Runtime Matrix

This document defines the canonical runtime configuration and extension compatibility matrix verified for the development environment.

## 1. Runtime Versions
- **CLI Project Runtime (PHP 8.2.20 / PHP 8.2.31)**:
  - Canonical Dev Path: `E:\xampp\php82\php.exe` (PHP 8.2.20)
  - System/Winget CLI Path: `PHP 8.2.31` (Microsoft.Winget.Source_8wekyb3d8bbwe)
- **Apache Browser Runtime**:
  - PHP version served via Apache: PHP 8.2.20
- **Composer Runtime**:
  - PHP version used to run Composer dependencies: PHP 8.2.20 / 8.2.31
- **PHPUnit Runtime**:
  - Version: PHPUnit 10.5.64

## 2. Required PHP Extensions Status
We verified that all critical extensions are installed, loaded, and consistent across CLI runtimes and the browser:

| Extension | Purpose | Status (XAMPP CLI) | Status (Winget CLI) |
|---|---|---|---|
| **mysqli** | Database connectivity (MariaDB/MySQL) | Loaded | Loaded |
| **intl** | Internationalization (date formatting, etc.) | Loaded | Loaded |
| **mbstring** | Multi-byte string manipulation | Loaded | Loaded |
| **fileinfo** | MIME type detection on uploads | Loaded | Loaded |
| **gd** | Image verification and scaling | Loaded | Loaded |
| **zip** | Zip archiving (Excel template processing) | Loaded | Loaded |
| **dom** | Document Object Model (XML parsing) | Loaded | Loaded |
| **xml** | XML schema verification | Loaded | Loaded |
| **openssl** | Encrypted session handling / UUID services | Loaded | Loaded |

No warnings or notices were generated during tests relating to missing extensions or configuration differences.
