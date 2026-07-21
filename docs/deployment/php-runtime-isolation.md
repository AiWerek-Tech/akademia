# PHP Runtime Isolation Guide

This document describes how we isolate the PHP runtime for **WMVAA Akademia** (PHP 8.2) while maintaining the legacy **Kumer** application on PHP 7.4 within the same XAMPP server.

---

## 1. Apache Isolation (Web Server)

The local Apache server utilizes virtual hosts. The domain `app.wmvaa.local` maps to the folder `E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id`.

By default, the virtual host runs on the default PHP version (PHP 7.4.29). To run PHP 8.2 for `wmvaa-akademia` without affecting other folders, we configure a directory handler.

### Configuration in Apache vhosts
In `E:/xampp/apache/conf/extra/httpd-vhosts.conf`, we register the global CGI handler for PHP 8.2:
```apache
ScriptAlias /php82/ "E:/xampp/php82/"
Action application/x-httpd-php82 "/php82/php-cgi.exe"

<Directory "E:/xampp/php82">
    AllowOverride None
    Options None
    Require all granted
    SetEnv PHPRC "E:/xampp/php82"
</Directory>
```

And inside the `<VirtualHost *:80>` block of `app.wmvaa.local`, we add a handler override for the `wmvaa-akademia` subdirectory:
```apache
<Directory "E:/xampp/htdocs/wmvaa.id/public_html/app.wmvaa.id/wmvaa-akademia">
    SetEnv PHPRC "E:/xampp/php82"
    <FilesMatch "\.php$">
        SetHandler application/x-httpd-php82
    </FilesMatch>
</Directory>
```

Alternatively, this directive can be placed directly in the project's root `.htaccess` if directory overrides are allowed:
```htaccess
# Target PHP 8.2 in local XAMPP Apache
SetEnv PHPRC "E:/xampp/php82"
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php82
</FilesMatch>
```

---

## 2. CLI Isolation (Terminal Commands)

Since the system's global environment variables have `E:\xampp\php` (PHP 7.4) in the PATH, running standard `php` or `composer` commands in the terminal will run under PHP 7.4. 

To execute CLI actions inside `wmvaa-akademia` with PHP 8.2, you must call the PHP 8.2 binary directly:

### Running Spark Commands
```bash
E:\xampp\php82\php.exe spark <command>
# Example: E:\xampp\php82\php.exe spark serve
```

### Running Composer Commands
```bash
E:\xampp\php82\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar <command>
# Example: E:\xampp\php82\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install
```

### Running Unit Tests
```bash
E:\xampp\php82\php.exe vendor/bin/phpunit
```

---

## 3. Production Isolation (cPanel)

On production (cPanel), PHP version isolation is configured using MultiPHP Manager in cPanel or by specifying the handler in `.htaccess`. 

cPanel automatically generates the following handler directives at the top of `.htaccess` depending on the PHP version assigned to the subdomain:
```htaccess
# php -- BEGIN cPanel-generated handler, do not edit
# Set the "ea-php82" package as the default "PHP" programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php82 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
```
This ensures the subfolder runs on PHP 8.2 in production, while other directories or subdomains remain unaffected.
