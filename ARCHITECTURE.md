# FearlessCMS Architecture Documentation

> **Changelog (2026-02):**
> - **Beta Release (0.1.0b)**: Transitioned from Alpha to Beta testing status.
> - **Pure PHP Export System**: Replaced external Node.js/shell-based exporting with an internal Dashboard Export button.
> - **Documentation Consolidation**: Centralized all core documentation into the `fcmsdocs/` directory.
> - **Session Reliability**: Standardized session configuration across all handlers to prevent 401 errors.
> - **UI Polish**: Stabilized drag-and-drop menu reordering (Sortable.js) and editor synchronization.

> **Previous Changelog (2025-08):**
> - **Major Update**: Transitioned from Markdown to HTML editing system.
> - **New HTML Editor**: Dual-mode WYSIWYG + Code view editor.
> - **Enhanced Export System**: Full HTML content support with parallax plugin integration.
> - **Security Enhancements**: Comprehensive security update management system.

## Overview

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, a modern theme system, and three operational modes for different deployment scenarios. The system prioritizes security, performance, and maintainability through proper file ownership and standard permissions. **As of February 2026, the project has entered Beta status (0.1.0b).**

## 1. Entry Points

- **index.php (Root):** Main frontend entry point; initializes session, routes requests, loads themes, processes content, and handles plugin hooks.
- **router.php:** PHP built-in server router for development; routes /admin/* and serves static files.
- **admin/index.php:** Main admin interface entry point; handles authentication, routing for admin actions, and manages CMS mode restrictions.
- **serve.sh:** Development server script for easy local development setup.

## 2. Configuration System

- **includes/config.php:** Core constants (PROJECT_ROOT, CONTENT_DIR, etc.) and default site settings.
- **config/cms_mode.json:** CMS operational mode (full-featured, hosting-service-plugins, hosting-service-no-plugins).
- **config/config.json:** Site-wide configuration (site name, admin path, Store URL, etc).
- **config/theme_options.json:** Global theme options and settings.
- **config/active_plugins.json:** List of currently active plugins.
- **config/roles.json:** User role definitions and capability sets.

## 3. Authentication & Session System

- **includes/session.php:** Centralized session management using `/sessions` directory with secure cookie defaults.
- **includes/auth.php:** User authentication logic (login, logout, permission checks).
- **config/users.json:** User account storage with hashed passwords.

## 4. Content Management

- **HTML + JSON frontmatter:** Content files use HTML format (stored as .md for compatibility) with a JSON frontmatter block for metadata (title, template, parent).
- **WYSIWYG Editor:** Modern HTML editor with a code view toggle and automatic content synchronization.
- **Dashboard Export:** A pure PHP implementation that crawls the site internally and generates a static mirror in the `export/` directory.

## 5. Theme System

- **includes/ThemeManager.php:** Handles theme discovery, configuration loading, and template rendering.
- **Modular templates:** Themes use `.html.mod` files for reusable components (header, footer, etc.) included via `{{module=name.html}}`.
- **SASS/SCSS support:** Themes can include source styles compiled to `assets/style.css`.
- **Themes:** A variety of modern themes (vintage, heroic, elegant-dark, etc.) are supported out of the box.

## 6. Plugin System

- **includes/plugins.php:** Robust hook/filter architecture (init, before_content, after_render, etc).
- **Key Plugins:**
  - **blog:** Full post management and RSS generation.
  - **forms:** Contact form builder and submission handler.
  - **seo:** Meta tag optimization.
  - **parallax:** Interactive scrolling sections v2.0.

## 7. CMS Mode Management

- **includes/CMSModeManager.php:** Restricts features (like the Plugin Store) based on the deployment environment.
- **Ad Area System:** Displays professional ads only in hosting-service modes.

## 8. Admin Interface (Mission Control)

- **Handlers:** Modular PHP files in `admin/` for specific tasks (newpage, user management, updater).
- **Dashboard:** Features real-time site stats, cache monitoring, and the primary **Export Site** button.
- **UI:** Utility-first CSS using Tailwind CSS for a responsive, modular layout.

## 9. Security Features

- **Centralized Config:** Ensures all AJAX handlers use the same session settings to prevent accidental logouts.
- **File System:** Strict 700/600 permissions for sensitive directories and JSON configs.
- **CSRF & Sanitization:** Global protection for all POST operations and input data.
- **Update Management:** Automated vulnerability scanning and emergency patching system.

## 10. Performance

- **Caching:** File-based page caching in the `cache/` directory.
- **Internal Export:** High-performance internal rendering for static site generation.
- **Optimization:** Lazy-loading architecture for plugins and themes.

## 11. Documentation

FearlessCMS maintains its primary documentation in the `fcmsdocs/` directory:
- **`install.md`**: Web and CLI installation guides.
- **`security-policy.md`**: Support tiers and disclosure process.
- **`export-sites-to-static-html.md`**: Comprehensive export guide.
- **`session-management.md`**: Implementation of headers and session reliability.
- **`cms-modes.md`**: Hosting provider configuration guide.

---

This architecture ensures FearlessCMS remains lightweight, secure, and easy to deploy, staying true to its mission of being a CMS **for the people**. 🚀