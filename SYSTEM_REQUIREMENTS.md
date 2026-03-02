# FearlessCMS System Requirements

## Minimum Requirements

### PHP
- **Version:** PHP 8.0 or higher (PHP 8.1+ recommended)
- **Required Extensions:**
  - `curl` - for HTTP requests and updates
  - `json` - for configuration file handling (JSON-based config)
  - `mbstring` - for multi-byte string support
  - `openssl` - for secure token generation and HTTPS
  - `session` - for user authentication

### Web Server
- **Apache** with `mod_rewrite` enabled (`.htaccess` support)
- **Nginx** with proper URL rewriting configured
- PHP built-in server (for development only)

### File System
- Writable directories for:
  - `config/` - configuration files
  - `content/` - page content storage
  - `uploads/` - user uploads
  - `admin/uploads/` - admin media uploads
  - `sessions/` - PHP session storage
  - `cache/` - page caching
  - `backups/` - backup storage
  - `.fcms_updates/` - update staging

### Operating System
- **Linux/Unix** (tested)
- **macOS** (tested)
- **Windows:** Not officially supported (WSL recommended)

---

## Recommended Requirements

### PHP
- **Version:** PHP 8.2+ for best performance and security
- **Additional Extensions (recommended):**
  - `phar` - for updates (preferred method)
  - `zip` - fallback for updates
  - `gd` or `imagick` - for image processing
  - `fileinfo` - for MIME type detection in uploads

### Node.js (Optional)
Required only for:
- Static site export functionality
- Tailwind CSS development builds

**Versions:**
- Node.js 14.14.0 or higher (Node.js 18+ recommended)
- npm 6.0+

**npm packages (auto-installed):**
- `fs-extra` - file system utilities
- `handlebars` - templating for export

---

---

## With MariaDB Connector Plugin

When the MariaDB Connector plugin is enabled, the requirements become comparable to WordPress:

### Additional Requirements
- **MariaDB 10.4+** or **MySQL 5.7+**
- **PHP Extension:** `pdo_mysql`

### Database Server Settings (Recommended)
```ini
; MariaDB/MySQL
innodb_buffer_pool_size = 128M
max_connections = 100
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

### Comparison: FearlessCMS + MariaDB vs WordPress

| Aspect | FearlessCMS + MariaDB | WordPress |
|--------|----------------------|-----------|
| **Database** | MariaDB 10.4+ / MySQL 5.7+ | MariaDB 10.4+ / MySQL 5.7+ |
| **PHP Extensions** | 6 (base + pdo_mysql) | 10+ |
| **Memory** | ~128MB | 256MB+ |
| **Core Tables** | Plugin-defined only | 12+ core tables |
| **Content Storage** | Still flat-file (hybrid) | Fully database |
| **Static Export** | Native | Requires plugins |

**Key advantage:** FearlessCMS uses database *optionally* for plugins like FearlessCommerce, while keeping content in flat files. This hybrid approach allows:
- Easy backups (content = files, plugin data = DB)
- Static export still works
- Lower database load than full-DB CMS

---

## Recommended PHP Settings

```ini
; Memory
memory_limit = 128M

; Upload limits
upload_max_filesize = 64M
post_max_size = 64M

; Execution time
max_execution_time = 300

; Session security
session.cookie_httponly = 1
session.use_only_cookies = 1
session.cookie_samesite = Lax

; Security
expose_php = Off
display_errors = Off  ; (set to On only in development)
log_errors = On
```

---

## Directory Permissions

| Directory | Permission | Description |
|-----------|------------|-------------|
| `config/` | 700 | Configuration files with sensitive data |
| `content/` | 755 | Page content storage |
| `uploads/` | 755 | User-uploaded files |
| `sessions/` | 700 | PHP session files |
| `cache/` | 755 | Cached page content |
| `backups/` | 700 | Backup archives |
| `.fcms_updates/` | 700 | Update staging area |

---

## Production Recommendations

### Security
- Use HTTPS (SSL/TLS certificate)
- Set `session.cookie_secure = 1` when using HTTPS
- Keep PHP and extensions updated
- Regular security audits

### Performance
- Enable PHP OPcache
- Enable FearlessCMS page caching (built-in)
- Use a CDN for static assets
- Consider PHP-FPM for high-traffic sites

### Hosting
- Shared hosting: Ensure PHP 8.0+ and required extensions
- VPS/Dedicated: Full control over PHP configuration
- Static hosting: Export to static HTML for CDN/serverless deployment

---

## Development Environment

For local development:

```bash
# Start built-in PHP server
php -S localhost:8000 router.php

# Or use the provided script
./serve.sh
```

### Debug Mode
Set the environment variable for verbose logging:
```bash
export FCMS_DEBUG=true
```

---

## Quick Compatibility Check

Run the installer to verify your environment:

```bash
php install.php --check
```

Or visit `install.php` in your browser for a visual compatibility report.
