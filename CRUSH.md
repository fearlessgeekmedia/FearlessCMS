# CRUSH.md

## Build/Lint/Test Commands

- **General PHP Lint:** `find . -name "*.php" -print0 | xargs -0 -n1 php -l`
- **Run all PHPUnit tests:** This project doesn't appear to use PHPUnit or a similar testing framework directly. Individual test files like `test_user_management.php` can be run via `php test_user_management.php`.
- **Run a single PHP test:** `php <path_to_test_file.php>` (e.g., `php test_user_management.php`)
- **JavaScript dependencies (if any):** `npm install` (based on `package.json`)
- **JavaScript Lint/Build:** Refer to `package.json` for specific scripts (e.g., `npm run lint`, `npm run build`)
- **Bash scripts:** `./<script_name>.sh` (e.g., `./serve.sh`)

## Code Style Guidelines (PHP)

- **Imports:** Use `require_once` or `include_once` for dependencies. Prefer `require_once` for critical files.
- **Formatting:** Adhere to PSR-1/PSR-12 where applicable (though not strictly enforced, aim for consistency). Use 4 spaces for indentation.
- **Naming Conventions:**
    - Classes: PascalCase (e.g., `CMSModeManager`)
    - Functions/Methods: camelCase (e.g., `getSetting`, `renderTemplate`)
    - Variables: camelCase (e.g., `$userName`, `$pageContent`)
    - Constants: SCREAMING_SNAKE_CASE (e.g., `DEFAULT_THEME`)
- **Types:** PHP is dynamically typed, but aim for clear variable naming to indicate expected types.
- **Error Handling:** Use `try-catch` blocks for exceptions where appropriate. For simpler errors, check return values and use `error_log` or similar for logging. Avoid `die()` or `exit()` in core logic unless absolutely necessary.
- **Comments:** Keep comments concise and explain "why" rather than "what."

