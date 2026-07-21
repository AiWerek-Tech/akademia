# Milestone 3 Test Execution Results

This document contains the execution output from the canonical runtimes, demonstrating that the full suite of 139 tests passes with zero failures.

## 1. Canonical PHP CLI Runtime (XAMPP PHP 8.2.20)
Command:
```powershell
& "E:\xampp\php82\php.exe" vendor/bin/phpunit --no-coverage --display-warnings --display-deprecations --display-errors --display-notices --display-skipped --display-incomplete
```

Output:
```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.20
Configuration: E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\phpunit.xml.dist

...............................................................  63 / 139 ( 45%)
............................................................... 126 / 139 ( 90%)
.............                                                   139 / 139 (100%)

Time: 08:27.408, Memory: 28.00 MB

OK (139 tests, 348 assertions)
```

## 2. Secondary PHP CLI Runtime (Winget PHP 8.2.31)
Command:
```powershell
& "C:\Users\wmvaa\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" vendor/bin/phpunit --no-coverage
```

Output:
```
PHPUnit 10.5.64 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.31
Configuration: E:\xampp\htdocs\wmvaa.id\public_html\app.wmvaa.id\wmvaa-akademia\phpunit.xml.dist

...............................................................  63 / 139 ( 45%)
............................................................... 126 / 139 ( 90%)
.............                                                   139 / 139 (100%)

Time: 08:35.687, Memory: 28.00 MB

OK (139 tests, 348 assertions)
```

Both runtimes successfully complete execution of all tests (including CSRF, security, database, and unit tests) with 100% success rate.
