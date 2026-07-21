# Milestone 1: Runtime Gate Evidence

## Browser Apache Runtime Verification

Endpoint: `http://app.wmvaa.local/wmvaa-akademia/system/runtime`

### Verified Runtime Properties

| Property | Value | Requirement | Status |
| :--- | :--- | :--- | :--- |
| **PHP_VERSION** | `8.2.20` | Must be `8.2.20` | **VERIFIED** |
| **PHP_SAPI** | `cgi-fcgi` (Apache FastCGI) | Browser SAPI (not CLI) | **VERIFIED** |
| **CI_VERSION** | `4.7.4` | CodeIgniter 4.7.4 | **VERIFIED** |
| **environment** | `development` | `development` (404 in production) | **VERIFIED** |
| **database_driver** | `MySQLi` | MySQLi database connection | **VERIFIED** |
| **server_software** | `Apache` | Running via Apache Web Server | **VERIFIED** |

### Environment Restrictions & Security
1. Endpoint is active **only** in `development` environment.
2. Endpoint returns HTTP 404 Page Not Found when `ENVIRONMENT` is set to `production`.
3. Endpoint contains zero database credentials, zero absolute filesystem paths, and zero secret keys.
