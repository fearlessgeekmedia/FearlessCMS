# FearlessCMS Code Review

**Date**: November 8, 2025  
**Reviewer**: Amp Code Review  
**Project Status**: Alpha (actively maintained)  
**Overall Assessment**: Good foundation with solid architecture, but several critical issues requiring immediate attention

---

## Executive Summary

FearlessCMS demonstrates a well-structured, modular PHP CMS with strong intentions toward security and accessibility. The codebase shows competent architecture with proper separation of concerns, a functional plugin system, and comprehensive documentation. However, several critical and high-priority issues must be addressed before production use:

**Critical Issues**: 3  
**High-Priority Issues**: 5  
**Medium-Priority Issues**: 6  
**Code Quality Observations**: 4

---

## 🚨 CRITICAL ISSUES (Immediate Action Required)

### 1. **Unsafe Unserialize Usage (Object Injection Vulnerability)**
- **File**: `includes/cache.php:251`
- **Severity**: CRITICAL
- **Risk**: Remote Code Execution (RCE)

```php
// VULNERABLE CODE
$cached = get_cached_content($key);
if ($cached !== false) {
    return unserialize($cached);  // ❌ UNSAFE
}
```

**Issue**: `unserialize()` on untrusted data can lead to object injection attacks. If cache files are modified or poisoned, attackers can execute arbitrary code.

**Fix**: Use `json_decode()` instead or validate cache data integrity with HMAC:
```php
// SECURE CODE
$cached = get_cached_content($key);
if ($cached !== false) {
    return json_decode($cached, true);  // ✅ SAFE
}
```

---

### 2. **Disabled Rate Limiting on Login (Brute Force Vulnerability)**
- **File**: `includes/auth.php:201-225`
- **Severity**: CRITICAL
- **Risk**: Brute force attacks, credential compromise

```php
function check_login_rate_limit($username, $max_attempts = 5, $time_window = 900): bool {
    // Rate limiting temporarily disabled - always allow login attempts
    return true;  // ❌ ALWAYS TRUE - NO PROTECTION
}
```

**Issue**: Rate limiting is completely disabled with a hardcoded `return true`. This allows unlimited login attempts without any throttling.

**Fix**: Re-enable rate limiting immediately:
```php
function check_login_rate_limit(string $username, int $max_attempts = 5, int $time_window = 900): bool {
    $key = "login_rate_limit_" . md5($username);
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }
    
    if ($now > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = ['count' => 0, 'reset_time' => $now + $time_window];
    }
    
    if ($_SESSION[$key]['count'] >= $max_attempts) {
        return false;  // Block login attempts
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
```

---

### 3. **XSS Vulnerability in Parsedown Markup Rendering**
- **File**: `includes/Router.php:64`, `includes/ContentLoader.php:118-119`
- **Severity**: CRITICAL
- **Risk**: Cross-Site Scripting (XSS) attacks

```php
// VULNERABLE CODE
$Parsedown->setMarkupEscaped(false);  // ❌ Disables escaping
$pageContentHtml = $Parsedown->text($content);
```

**Issue**: Parsedown is configured to NOT escape markup, allowing raw HTML/JavaScript in Markdown files. Combined with user-editable content, this enables XSS attacks.

**Fix**: Enable markup escaping and sanitize content:
```php
// SECURE CODE
require_once PROJECT_ROOT . '/includes/HtmlSanitizer.php';
$Parsedown->setMarkupEscaped(true);  // ✅ Enable escaping
$html = $Parsedown->text($content);
$pageContentHtml = sanitize_html($html);  // Additional sanitization
```

Or use a dedicated HTML sanitizer library:
```php
$pageContentHtml = HtmlPurifier::getInstance()->purify($pageContentHtml);
```

---

## ⚠️ HIGH-PRIORITY ISSUES

### 4. **Missing CSRF Protection on File Operations**
- **File**: `admin/index.php:3-70` (file uploads)
- **Severity**: HIGH
- **Risk**: CSRF attacks on critical operations

**Issue**: File upload handlers (featured images, quill uploads) may not properly validate CSRF tokens in all cases. The featured image upload (lines 31-70) doesn't show explicit CSRF validation.

**Fix**: Add CSRF token validation:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_featured_image') {
    // Validate CSRF token FIRST
    if (!validate_csrf_token()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    // ... rest of upload logic
}
```

---

### 5. **File Path Traversal - Incomplete Validation**
- **File**: `includes/auth.php:120-148` (validate_file_path function)
- **Severity**: HIGH
- **Risk**: Directory traversal attacks

```php
function validate_file_path($path, $allowed_base_dir) {
    // ... validation code ...
    // Build the full path
    $full_path = $allowed_base_dir . '/' . $path;
    $base_path = realpath($allowed_base_dir);
    
    // ISSUE: realpath returns false if path doesn't exist
    $normalized_full = str_replace('//', '/', $full_path);
    if (strpos($normalized_full, $base_path) !== 0) {
        return false;
    }
    return $full_path;
}
```

**Issue**: Using `realpath()` on non-existent paths returns `false`. The check using string comparison is fragile and may be bypassable with symlinks.

**Fix**: Use proper path resolution:
```php
function validate_file_path($path, $allowed_base_dir) {
    // Remove null bytes
    $path = str_replace("\0", '', $path);
    
    // Prevent directory traversal
    if (strpos($path, '..') !== false || preg_match('#^/#', $path)) {
        return false;
    }
    
    // Build and resolve paths
    $full_path = realpath($allowed_base_dir . '/' . $path) ?: $allowed_base_dir . '/' . $path;
    $base_path = realpath($allowed_base_dir) ?: $allowed_base_dir;
    
    // Ensure no symlink escapes and path is under base
    $normalized = realpath(dirname($full_path)) . '/' . basename($full_path);
    
    if (strpos($normalized, $base_path) !== 0) {
        return false;
    }
    
    return $full_path;
}
```

---

### 6. **Missing Input Validation on JSON Metadata**
- **File**: `includes/ContentLoader.php:81-82`, `includes/Router.php:50`
- **Severity**: HIGH
- **Risk**: Injection attacks, template variable pollution

```php
// VULNERABLE CODE
if (preg_match('/^<!--\\s*json\\s*(.*?)\\s*-->/s', $fileContent, $matches)) {
    $metadata = json_decode($matches[1], true);  // No validation
    if ($metadata) {
        $pageTitle = $metadata['title'] ?? '';
        // ... use metadata directly in templates
    }
}
```

**Issue**: JSON metadata is parsed but not validated. Malicious JSON could inject template variables or values.

**Fix**: Validate and sanitize metadata:
```php
function validate_metadata($metadata) {
    $allowed_keys = ['title', 'description', 'editor_mode', 'template', 'parent', 'author', 'date'];
    $validated = [];
    
    foreach ($allowed_keys as $key) {
        if (isset($metadata[$key])) {
            $validated[$key] = htmlspecialchars($metadata[$key], ENT_QUOTES, 'UTF-8');
        }
    }
    
    return $validated;
}

// Usage:
$metadata = json_decode($matches[1], true);
$metadata = validate_metadata($metadata);
```

---

### 7. **Weak Session Configuration for Development**
- **File**: `includes/session.php:128` (use_strict_mode disabled)
- **Severity**: HIGH  
- **Risk**: Session fixation attacks, session hijacking

```php
// VULNERABLE CODE
$sessionUseStrictMode = @ini_set('session.use_strict_mode', 0);  // ❌ Disabled
```

**Issue**: `use_strict_mode` is disabled for "development," but this is enabled in production, allowing attackers to use uninitialized session IDs.

**Fix**: Enable strict mode always:
```php
$sessionUseStrictMode = @ini_set('session.use_strict_mode', 1);  // ✅ Enabled
```

---

### 8. **Inadequate SQL Injection Protection (If DB is added)**
- **Files**: Plugin system patterns
- **Severity**: HIGH
- **Risk**: SQL injection if database features are added

**Issue**: The codebase has references to `prepare()`, `execute()`, and `query()` in disabled plugins. If these are enabled without parameterized queries, SQL injection is likely.

**Fix**: When using databases, always use prepared statements:
```php
// SECURE PATTERN
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// OR with named parameters
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute([':username' => $username]);
```

---

## 🔴 MEDIUM-PRIORITY ISSUES

### 9. **Command Injection in Update Handler**
- **File**: `admin/updater-handler.php:67, 75, 85, 96, 102, 110, 214`
- **Severity**: MEDIUM
- **Risk**: Command injection if update URLs are user-controlled

```php
exec($cmd, $output, $return_code);  // $cmd may be user-influenced
exec('rm -rf ' . escapeshellarg($temp_dir));  // Better, but check $temp_dir source
```

**Issue**: While `escapeshellarg()` is used in some places, the overall command construction should be reviewed. If `$cmd` is built from user input, it could be exploited.

**Fix**: Use safer alternatives:
```php
// Instead of exec()
$process = proc_open($cmd, $descriptorspec, $pipes);
$return_code = proc_close($process);

// Or use array syntax (safer)
$cmd = ['git', 'clone', $url, $directory];  // Avoids shell interpretation
```

---

### 10. **Missing Content-Type Headers on JSON Responses**
- **File**: `admin/index.php:22` (featured image upload), multiple handlers
- **Severity**: MEDIUM
- **Risk**: MIME type confusion attacks

Some handlers set `Content-Type: application/json`, but others may not consistently. This could lead to browsers misinterpreting responses.

**Fix**: Standardize all JSON responses:
```php
header('Content-Type: application/json; charset=UTF-8');
http_response_code(200);
echo json_encode(['success' => true, 'data' => $data]);
exit;
```

---

### 11. **Weak Password Validation**
- **File**: `includes/auth.php:93-101`
- **Severity**: MEDIUM
- **Risk**: Weak passwords

```php
function validate_password($password) {
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return false;
    }
    return true;  // Only requires 8+ chars, 1 letter, 1 number
}
```

**Issue**: Password policy is weak (no special characters required, no uppercase requirement).

**Fix**: Strengthen password requirements:
```php
function validate_password($password) {
    $minLength = 12;
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
    
    if (strlen($password) < $minLength) {
        return false;
    }
    
    if (!($hasUppercase && $hasLowercase && $hasNumber && $hasSpecial)) {
        return false;
    }
    
    return true;
}
```

---

### 12. **Logging Sensitive Information**
- **File**: `includes/auth.php:287-288`, `admin/login-handler.php:33-35, 58, 62`
- **Severity**: MEDIUM
- **Risk**: Information disclosure

```php
// VULNERABLE CODE
error_log("DEBUG LOGIN: Demo user session set - username: " . $_SESSION['username']);
error_log("DEBUG LOGIN: Demo user permissions: " . json_encode($_SESSION['permissions']));
error_log("Login attempt for user: " . $username);
```

**Issue**: Usernames and session data are logged in plaintext, which could expose sensitive information if logs are compromised.

**Fix**: Use hashed identifiers and limit log verbosity:
```php
$hash = hash('sha256', $username);
error_log("Login attempt: $hash");  // Use hash instead of username
// Don't log permissions or other sensitive data
```

---

### 13. **Missing Object Injection Protection**
- **File**: `includes/plugins.php:77` (unfiltered callback execution)
- **Severity**: MEDIUM
- **Risk**: Plugin-based code injection

```php
function fcms_apply_filter($hook, $value, ...$args) {
    if (!empty($GLOBALS['fcms_hooks'][$hook])) {
        foreach ($GLOBALS['fcms_hooks'][$hook] as $cb) {
            $value = call_user_func_array($cb, array_merge([$value], $args));  // $cb not validated
        }
    }
    return $value;
}
```

**Issue**: Callback validation is minimal. Malicious plugins could register dangerous callbacks.

**Fix**: Validate callbacks:
```php
function fcms_apply_filter($hook, $value, ...$args) {
    if (!empty($GLOBALS['fcms_hooks'][$hook])) {
        foreach ($GLOBALS['fcms_hooks'][$hook] as $cb) {
            if (!is_callable($cb)) {
                error_log("Invalid callback for hook: $hook");
                continue;
            }
            $value = call_user_func_array($cb, array_merge([$value], $args));
        }
    }
    return $value;
}
```

---

## 💡 CODE QUALITY OBSERVATIONS

### 14. **Inconsistent Error Handling**
The codebase uses a mix of error handling approaches:
- Some files use `try/catch`
- Others use `if (file_exists())` checks
- Some suppress errors with `@` operator
- Limited use of exceptions for critical errors

**Recommendation**: Adopt consistent error handling patterns. Use exceptions for exceptional cases and proper error logging.

---

### 15. **Heavy Reliance on Global $GLOBALS**
The plugin system heavily uses `$GLOBALS['fcms_hooks']` and `$GLOBALS['cmsModeManager']`. This makes the code harder to test and less maintainable.

**Recommendation**: Consider using a dependency injection container or class-based plugin system.

---

### 16. **Incomplete Type Hints**
Many functions lack type hints or return type declarations:
```php
// Missing type hints
function sanitize_input($input, $type = 'string') {  // Should be: function sanitize_input(string $input, string $type = 'string'): string
    // ...
}
```

**Recommendation**: Add full type hints to improve code clarity and enable IDE assistance.

---

### 17. **Missing Comprehensive Test Suite**
The project lacks unit tests and integration tests. Critical paths like authentication and file handling should have automated tests.

**Recommendation**: Add PHPUnit tests for:
- Authentication and authorization
- File path validation
- CSRF token generation/validation
- Content processing

---

## ✅ POSITIVE OBSERVATIONS

### Strong Points

1. **Excellent Security Headers Implementation** (`auth.php:164-197`)
   - CSP headers, X-Frame-Options, X-Content-Type-Options all properly set

2. **Good Session Security Configuration** (`session.php`)
   - HTTPOnly cookies, SameSite protection, secure cookies on HTTPS
   - Proper session timeout and regeneration

3. **Comprehensive Input Sanitization** (`auth.php:104-117`)
   - Type-specific sanitization for different input types
   - Good path traversal protection in most cases

4. **Plugin Architecture** (`plugins.php`)
   - Well-designed hook/filter system
   - Good separation of concerns

5. **Well-Documented** 
   - ARCHITECTURE.md is comprehensive
   - Code comments explain complex logic
   - Clear README and guides

6. **Modular Design**
   - Clear separation between frontend, admin, and plugin systems
   - Easy to extend with new themes and plugins

---

## 🔧 RECOMMENDATIONS (Priority Order)

### Immediate (Within 1 week)
1. ✅ Re-enable login rate limiting (Issue #2)
2. ✅ Replace `unserialize()` with `json_decode()` (Issue #1)
3. ✅ Add CSRF validation to all upload handlers (Issue #4)
4. ✅ Enable session.use_strict_mode (Issue #7)

### High Priority (Within 2 weeks)
5. ✅ Fix Parsedown XSS vulnerability (Issue #3)
6. ✅ Improve file path validation (Issue #5)
7. ✅ Add metadata validation (Issue #6)
8. ✅ Strengthen password validation (Issue #11)
9. ✅ Stop logging sensitive information (Issue #12)

### Medium Priority (Within 1 month)
10. ✅ Review and secure command execution (Issue #9)
11. ✅ Standardize JSON response headers (Issue #10)
12. ✅ Add callback validation in plugin system (Issue #13)
13. ✅ Add comprehensive test suite (Issue #17)

### Long-term (Ongoing)
14. ✅ Add full type hints (Issue #16)
15. ✅ Implement dependency injection (Issue #14)
16. ✅ Consider security audit by third party
17. ✅ Add automated security scanning to CI/CD

---

## Conclusion

FearlessCMS has a solid architectural foundation and demonstrates good security awareness in many areas. However, **the three critical issues must be fixed before any production deployment**. The codebase would benefit from:

- A comprehensive test suite
- Consistent error handling patterns
- Full type hints throughout
- Regular security audits

With these improvements, FearlessCMS could become a competitive, secure, lightweight CMS option.

---

**Review Status**: Complete  
**Confidence Level**: High (90%)  
**Follow-up Recommended**: After critical issues are fixed, conduct focused security review
