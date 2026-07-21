# Runtime & Security Audit - WMVAA Akademia

This document audits the runtime and package dependencies of **WMVAA Akademia** during the Milestone 0 discovery phase, identifying security blockers and establishing a secure upgrade path to PHP 8.2.

---

## 1. Initial State Audit

### PHP & Web Server Environment
- **PHP CLI (Default)**: `PHP 7.4.29 (cli) (built: Apr 12 2022 20:21:18)`
- **PHP CLI (PHP 8.2.20 Available)**: `E:\xampp\php82\php.exe`
- **PHP Apache (Default)**: PHP 7.4.29
- **PHP Apache (spmb folder / php82 CGI Mode)**: PHP 8.2.20 (running via `application/x-httpd-php82` CGI Handler mapped to `E:/xampp/php82/php-cgi.exe`)
- **PHP Target cPanel**: PHP 8.2 (based on `spmb` configuration compatibility)
- **Available Extensions in PHP 8.2.20**: `bcmath`, `calendar`, `ctype`, `curl`, `dom`, `exif`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `mysqli`, `mysqlnd`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `Phar`, `random`, `readline`, `session`, `SimpleXML`, `zip`, `zlib`.

### Composer Configuration
- **Composer Version**: `2.9.7 2026-04-14 13:31:52`
- **Composer Validate Status**: `composer.json is valid`

### Installed Packages (Milestone 0)
- `codeigniter4/framework`: `4.4.8` (Locked to support PHP 7.4)
- `phpunit/phpunit`: `9.6.35`
- `fakerphp/faker`: `1.24.1`
- `mikey179/vfsstream`: `1.6.12`
- Other sub-dependencies.

---

## 2. Bypassed Security Advisories

Due to the PHP 7.4 version lock, `composer install` was run using `--no-security-blocking` which bypassed the following **critical/medium** advisories affecting `codeigniter4/framework` v4.4.8:

| Advisory ID | CVE | Severity | Title | Affected Versions | URL |
|---|---|---|---|---|---|
| **PKSA-217t-qqjr-nkt3** | CVE-2026-48062 | Critical | Validation bypass when uploading file extensions via `ext_in` rule | <4.7.2 | [Link](https://github.com/advisories/GHSA-2gr4-ppc7-7mhx) |
| **PKSA-7ybs-j1bv-y5mc** | CVE-2025-54418 | Critical | ImageMagick Handler Command Injection Vulnerability | <4.6.2 | [Link](https://github.com/advisories/GHSA-9952-gv64-x94c) |
| **PKSA-qbjf-dc24-wrff** | CVE-2025-24013 | Medium | Missing validation of header name and value | <4.5.8 | [Link](https://github.com/advisories/GHSA-x5mq-jjr3-vmx6) |

---

## 3. Risk Assessment

1. **Production Vulnerabilities**: Leaving the application on v4.4.8 in production poses severe security risks, including command injection (remote code execution) via the ImageMagick handler and validation bypasses during file uploads.
2. **PHP 7.4 EOL Status**: PHP 7.4 has been unsupported since November 2022. It receives no official security patches, leaving the hosting server itself vulnerable.
3. **Decision**: The application **cannot be deployed to production** using CodeIgniter 4.4.8. We must transition to the PHP 8.2 environment available on the server.

---

## 4. Upgrade Strategy (PHP 8.2)

We will configure `wmvaa-akademia` to run on PHP 8.2 by using the PHP 8.2 CLI and adding CGI handler instructions in the `.htaccess` file, ensuring complete isolation from Kumer (which remains on PHP 7.4). CodeIgniter will be upgraded to the latest secure version (`^4.7`).
