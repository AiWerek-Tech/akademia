# Walkthrough - Milestone 0 & Security Foundation Gate

This document records the completion of **Milestone 0: Discovery & Foundation** and **Pre-Milestone 1: Runtime & Security Foundation Gate**.

---

## 1. Runtime & Security Upgrades

To address critical security vulnerabilities in older CodeIgniter versions, we transitioned the project to PHP 8.2.

- **PHP Version**: Upgraded requirements from PHP 7.4.29 to **PHP 8.2.20** (located at `E:\xampp\php82`).
- **CodeIgniter Framework**: Upgraded from v4.4.8 to **v4.7.4** (latest secure version).
- **PHPUnit Framework**: Upgraded from v9.6 to **v10.5.64**.
- **Audit Results**: 
  - `composer validate --strict` returns **VALID**.
  - `composer audit` returns **No security vulnerability advisories found** (0 vulnerabilities).

---

## 2. PHP Runtime Isolation

We implemented environment isolation using a CGI handler to ensure the new application runs on PHP 8.2, while the legacy **Kumer** application remains unaffected on PHP 7.4.

1. **Apache Virtual Host (`httpd-vhosts.conf`)**:
   Added a Directory override mapping `.php` files inside `/wmvaa-akademia` to the PHP 8.2 handler:
   ```apache
   <Directory "E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia">
       SetEnv PHPRC "E:/xampp/php82"
       <FilesMatch "\.php$">
           SetHandler application/x-httpd-php82
       </FilesMatch>
   </Directory>
   ```
2. **Project Router (`.htaccess`)**:
   Created [wmvaa-akademia/.htaccess](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/.htaccess) to handle portable routing to the `public/` directory and enforce security headers (X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy).

---

## 3. Configuration Updates

With the framework upgrade to v4.7.4, old configuration templates were updated to maintain compatibility:
- **Kint Config**: Overwrote [Kint.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Kint.php) to remove deprecated class inheritance and the undefined `RichRenderer` sort constant.
- **Format Config**: Updated [Format.php](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/app/Config/Format.php) to add the required `$jsonEncodeDepth` parameter, preventing error exceptions inside json formatter trait responses.

---

## 4. Database Period Redundancy Review

We reviewed the academic periods structure and updated [database-schema.md](file:///e:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia/docs/database-schema.md):
- Removed the redundant `semesters` table.
- Mapped everything to a unified `academic_periods` table referencing `academic_years`.
- Defined states: `DRAFT`, `VALIDATED`, `REVIEWED`, `APPROVED`, `LOCKED`.

---

## 5. Dashboard Placeholders

Cleaned up the dashboard mockup numbers to prevent misleading metrics:
- Replaced dummy stats with explicit `0 Guru`, `0 Mapel`, and `Belum tersedia` values.
- Appended `(Mockup)` labels to data cards.

---

## 6. Verification Results

### Automated Tests
Ran PHPUnit tests under PHP 8.2 using:
`E:\xampp\php82\php.exe vendor/bin/phpunit`
- **Result**: `OK (7 tests, 18 assertions)`

### Linting Checks
All files in `app/` and `tests/` were parsed using PHP 8.2.
- **Result**: `No syntax errors detected`
