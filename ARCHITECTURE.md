# FearlessCMS Architecture Documentation

> **Changelog (2024-07):**
> - Added new plugins: ecommerce, forms, seo, wordpress-import, blog
> - Added new themes: cyberpunk, custom-variables-demo, starterscores
> - Added static site export functionality with export.js
> - Added CRUSH.md build/lint/test documentation
> - Added CMS_MODES.md detailed mode documentation
> - Added new admin handlers: updater-handler.php, user-actions.php
> - Added new utility files: export.js, serve.sh, various test files
> - Enhanced security with permission backup scripts and debug tools
> - Added .github/ directory for GitHub workflows

## Overview

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, a modern theme system, and three operational modes for different deployment scenarios. The system prioritizes security, performance, and maintainability through proper file ownership and standard permissions.

## 1. Entry Points

- **index.php (Root):** Main frontend entry point; initializes session, routes requests, loads themes, processes markdown with JSON frontmatter, handles plugin hooks.
- **router.php:** PHP built-in server router for development; routes /admin/*, serves static files, handles session, redirects unauthenticated users.
- **admin/index.php:** Main admin interface entry point; initializes session, handles authentication, routes admin actions, manages CMS mode restrictions, loads admin templates and sections.
- **serve.sh:** Development server script for easy local development setup.

## 2. Configuration System

- **includes/config.php:** Core configuration and constants (PROJECT_ROOT, CONTENT_DIR, THEME_DIR, PLUGIN_DIR, CONFIG_DIR, ADMIN_CONFIG_DIR).
- **config/cms_mode.json:** CMS operational mode configuration (full-featured, hosting-service-plugins, hosting-service-no-plugins).
- **config/config.json:** Site-wide configuration (site name, description, admin path, etc).
- **config/theme_options.json:** Global theme options and settings.
- **config/active_plugins.json:** Currently active plugins list.
- **config/roles.json:** User role definitions and permissions.

## 3. Authentication & Session System

- **includes/session.php:** Centralized session management; session save path in /sessions, secure cookies, unified session for admin/frontend.
- **includes/auth.php:** User authentication; isLoggedIn(), login(), logout(), permission checks, createDefaultAdminUser().
- **config/users.json:** User account storage (JSON array, hashed passwords, permissions).
- **config/admin/users.json:** Admin-specific user configurations.

## 4. Content Management

- **Markdown + JSON frontmatter:** Content files are Markdown (.md) with a JSON frontmatter block for metadata (title, template, editor_mode, parent, etc).
- **Hierarchical structure:** Supports folders/subfolders for nested content; parent/child relationships managed in frontmatter.
- **Content types:** Pages, blog posts, forms, imported WordPress content, plugin-specific data.
- **content/** directory structure:
  - home.md, about.md, blog_posts.json, forms/ (plugin data), form_submissions/, _preview/ (for previews), and subfolders for categories or custom types.
- **Static Export:** export.js provides Node.js-based static site generation with full theme processing and SEO optimization.

## 5. Theme System

- **includes/ThemeManager.php:** Theme management and rendering; discovers themes, loads config, renders templates, manages assets.
- **Modular templates:** Themes use .html.mod files for reusable components (header, footer, sidebar, etc). Included with `{{module=header.html}}` syntax. Page templates use .html.
- **Theme options:** Defined in config.json in each theme; supports text, textarea, select, checkbox, color, image, array. Accessible in templates as `{{themeOptions.key}}`.
- **SASS/SCSS support:** Themes can include SASS/SCSS for advanced styling; compiled to assets/style.css.
- **Current themes:** default, minimal, minimalist, modern-cards, elegant-dark, cyberpunk, custom-variables-demo, heroic, salt-lake, simple-modern, starterscores, vintage.
- **Theme structure:**
  ```
  themes/your-theme/
    ├── templates/
    │   ├── page.html, home.html, blog.html, 404.html
    │   ├── header.html.mod, footer.html.mod, sidebar.html.mod, ...
    ├── assets/
    │   ├── style.css, images/, js/, sass/
    ├── theme.json (metadata)
    ├── config.json (theme options)
    └── README.md
  ```

## 6. Plugin System

- **includes/plugins.php:** Plugin framework and management; supports a robust hook/filter architecture:
  - Hooks: init, before_content, after_content, before_render, after_render, route, check_permission, content, filter_admin_sections, etc.
  - Plugins register hooks with fcms_add_hook(), admin sections with fcms_register_admin_section(), and can add custom routes/content filters.
- **Current plugins:**
  - **blog:** Blog post management and display
  - **forms:** Contact forms and form submissions
  - **seo:** Basic SEO meta tags and optimization
  - **ecommerce:** E-commerce functionality
  - **wordpress-import:** WordPress XML import functionality
- **MariaDB Connector pattern:** Plugins requiring DB access use fcms_do_hook('database_connect') and fcms_do_hook('database_query', ...). DB credentials/config in content/mariadb-connector/config.json.
- **Plugin structure:**
  ```
  plugins/your-plugin/
    ├── plugin.json
    ├── your-plugin.php
    ├── includes/, templates/, assets/, admin/, README.md
  ```
- **Plugin loader:** Only loads plugins listed in admin/config/plugins.json.

## 7. CMS Mode Management

- **includes/CMSModeManager.php:** Manages operational modes and permissions; mode switching, permission checks, feature restrictions.
- **Modes:**
  - full-featured: All features enabled
  - hosting-service-plugins: Plugins allowed, no store
  - hosting-service-no-plugins: No plugin management, only pre-installed plugins
- **Mode enforcement:** Admin UI, plugin actions, file management, and uploads are restricted based on mode. Navigation adapts to mode.
- **Detailed documentation:** See CMS_MODES.md for comprehensive mode information and implementation details.

## 8. Admin Interface

- **Handler files:** The admin directory includes comprehensive handler files for different actions:
  - **Content management:** newpage-handler.php, preview-handler.php, filedel-handler.php, filesave-handler.php
  - **Plugin management:** plugin-handler.php, store-handler.php
  - **Theme management:** theme-handler.php
  - **User management:** user-handler.php, newuser-handler.php, edituser-handler.php, deluser-handler.php, pchange-handler.php
  - **System management:** updater-handler.php, role-handler.php, menu-handler.php
  - **Widget management:** widget-handler.php, widgets-handler.php
  - **File handling:** toastui-upload-handler.php
- **Action-to-template mapping:** Admin actions are mapped to templates (dashboard.php, content-management.php, plugins.php, themes.php, menus.php, site-settings.html, edit_content.php, new_content.php, file_manager.php, users.php, role-management.html, widgets.php, etc).
- **Dynamic admin sections:** Plugins can register admin sections dynamically; navigation and permissions are updated accordingly.
- **Navigation and permissions:** Admin navigation is built dynamically, and access is controlled by CMS mode and user permissions.
- **UI:** Built with Tailwind CSS, modular, and extensible.

## 9. Widget and Menu Systems

- **Widget system:** Sidebar widget management; widgets.json in config/ and admin/config/; supports multiple sidebars, widget types, drag-and-drop ordering.
- **Menu system:** Navigation menu management; menus.json in config/; supports hierarchical menus, drag-and-drop editing, and plugin integration.

## 10. Security Features

- **Authentication & session management:** Centralized, secure, unified for admin/frontend.
- **File system security:** Proper ownership (web server user), standard permissions (755/644), path traversal prevention, file type restrictions, directory access controls.
- **Permission management:** Multiple permission backup scripts (fix_permissions.sh, fix_server_permissions.sh, fix_session.php) for security maintenance.
- **Input validation:** File upload validation, content sanitization, XSS/CSRF prevention, SQL injection prevention (for DB plugins).
- **Critical directories:** sessions/, content/forms/, content/form_submissions/, config/, uploads/, admin/uploads/ (all 755, owned by web server user).

## 11. Performance Considerations

- **File-based page caching:** Public (non-logged-in) pages are cached as static HTML files in the `cache/` directory for 5 minutes by default. This reduces server load and improves response times. Cache is automatically cleared when content is updated via the admin interface.
- **Static site export:** export.js provides full static site generation for maximum performance and CDN deployment.
- **Caching:** Template caching, plugin hook caching, file system caching, session file optimization.
- **Optimization:** Lazy loading of plugins/themes, efficient file ops, minimal DB dependencies, optimized template rendering.
- **Asset management:** Theme asset organization, static file serving, image optimization, JS/CSS minification.

## 12. Development and Build Tools

- **CRUSH.md:** Comprehensive build, lint, and test documentation with code style guidelines.
- **Package management:** package.json and package-lock.json for Node.js dependencies (export functionality).
- **Development scripts:** serve.sh for local development server, various test files for debugging.
- **Debug tools:** debug_plugin.php, debug_plugin_loading.php, test_*.php files for development and troubleshooting.
- **Build artifacts:** Build outputs and temporary files managed by build system.

## 13. Extension Points

- **Plugin development:** Hook system, admin section registration, custom content types, custom templates/themes, DB integration via MariaDB connector.
- **Theme development:** Modular templates, asset management, theme options, SASS/SCSS, custom JS.
- **Custom handlers:** Custom routing, admin sections, file processors, authentication/permission systems.
- **Static export customization:** export.js can be extended for custom export formats and optimizations.

## 14. Deployment Considerations

- **File permissions/ownership:** Set writable dirs to web server user, use 755/644, avoid 777/666, verify file ops.
- **Server config:** URL rewriting, PHP optimization, file upload/security settings, HTTPS enforcement.
- **Security hardening:** HTTPS, file access restrictions, error reporting config, regular audits/updates.
- **Environment config:** Development (debug), production (optimized), maintenance (maintenance mode).
- **Static export:** Use export.js for CDN deployment and maximum performance.

## 15. Development Workflow

- **Local development:** PHP built-in server via serve.sh, file-based config, direct file editing, debug mode.
- **Plugin/theme development:** Directory structure, hook integration, admin UI, testing/validation.
- **Testing/QA:** Cross-browser/mobile testing, performance/security testing, responsive design.
- **Build process:** Use CRUSH.md guidelines for consistent code quality and testing.

## 16. New Features and Enhancements


- **E-commerce Plugin:** Full e-commerce functionality for online stores.
- **Forms Plugin:** Contact forms and form submission management.
- **WordPress Import:** Import existing WordPress content and structure.
- **Static Export:** Generate static sites for CDN deployment and maximum performance.
- **Enhanced Security:** Multiple permission management scripts and debug tools.
- **Development Tools:** Comprehensive testing framework and build documentation.

---

This architecture provides a solid foundation for a lightweight, extensible content management system with strong separation of concerns, clear extension points for customization, robust security practices through proper file ownership and standard permissions, and comprehensive development tools for building and maintaining high-quality websites. 