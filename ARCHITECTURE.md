# FearlessCMS Architecture Documentation

> **Changelog (2024-06):**
> - Expanded admin section: all handler files, dynamic admin sections, action-to-template mapping
> - Theme system: modular templates (.html.mod), theme options (config.json), SASS/SCSS support
> - Plugin system: full hook/filter architecture, admin section registration, built-in plugins, MariaDB Connector pattern
> - Content management: Markdown+JSON frontmatter, hierarchical structure, content types
> - CMS modes: clarified restrictions and enforcement
> - Security, performance, and extension points updated

## Overview

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, a modern theme system, and three operational modes for different deployment scenarios. The system prioritizes security, performance, and maintainability through proper file ownership and standard permissions.

## 1. Entry Points

- **index.php (Root):** Main frontend entry point; initializes session, routes requests, loads themes, processes markdown with JSON frontmatter, handles plugin hooks.
- **router.php:** PHP built-in server router for development; routes /admin/*, serves static files, handles session, redirects unauthenticated users.
- **admin/index.php:** Main admin interface entry point; initializes session, handles authentication, routes admin actions, manages CMS mode restrictions, loads admin templates and sections.

## 2. Configuration System

- **includes/config.php:** Core configuration and constants (PROJECT_ROOT, CONTENT_DIR, THEME_DIR, PLUGIN_DIR, CONFIG_DIR, ADMIN_CONFIG_DIR).
- **config/cms_mode.json:** CMS operational mode configuration (full-featured, hosting-service-plugins, hosting-service-no-plugins).
- **config/config.json:** Site-wide configuration (site name, description, admin path, etc).

## 3. Authentication & Session System

- **includes/session.php:** Centralized session management; session save path in /sessions, secure cookies, unified session for admin/frontend.
- **includes/auth.php:** User authentication; isLoggedIn(), login(), logout(), permission checks, createDefaultAdminUser().
- **config/users.json:** User account storage (JSON array, hashed passwords, permissions).

## 4. Content Management

- **Markdown + JSON frontmatter:** Content files are Markdown (.md) with a JSON frontmatter block for metadata (title, template, editor_mode, parent, etc).
- **Hierarchical structure:** Supports folders/subfolders for nested content; parent/child relationships managed in frontmatter.
- **Content types:** Pages, blog posts, forms, imported WordPress content, plugin-specific data.
- **content/** directory structure:
  - home.md, about.md, blog_posts.json, forms/ (plugin data), form_submissions/, _preview/ (for previews), and subfolders for categories or custom types.

## 5. Theme System

- **includes/ThemeManager.php:** Theme management and rendering; discovers themes, loads config, renders templates, manages assets.
- **Modular templates:** Themes use .html.mod files for reusable components (header, footer, sidebar, etc). Included with `{{module=header.html}}` syntax. Page templates use .html.
- **Theme options:** Defined in config.json in each theme; supports text, textarea, select, checkbox, color, image, array. Accessible in templates as `{{themeOptions.key}}`.
- **SASS/SCSS support:** Themes can include SASS/SCSS for advanced styling; compiled to assets/style.css.
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
- **Built-in plugins:**
  - Forms (contact forms), SEO (meta tags), Blog (blog posts), MariaDB Connector (database access), WordPress Import (import WP XML), and more.
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

## 8. Admin Interface

- **Handler files:** The admin directory includes many handler files for different actions: plugin-handler.php, theme-handler.php, store-handler.php, newpage-handler.php, filedel-handler.php, filesave-handler.php, widgets-handler.php, widget-handler.php, user-handler.php, newuser-handler.php, edituser-handler.php, deluser-handler.php, menu-handler.php, role-handler.php, preview-handler.php, toastui-upload-handler.php, etc.
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
- **Input validation:** File upload validation, content sanitization, XSS/CSRF prevention, SQL injection prevention (for DB plugins).
- **Critical directories:** sessions/, content/forms/, content/form_submissions/, config/, uploads/, admin/uploads/ (all 755, owned by web server user).

## 11. Performance Considerations

- **Caching:** Template caching, plugin hook caching, file system caching, session file optimization.
- **Optimization:** Lazy loading of plugins/themes, efficient file ops, minimal DB dependencies, optimized template rendering.
- **Asset management:** Theme asset organization, static file serving, image optimization, JS/CSS minification.

## 12. Extension Points

- **Plugin development:** Hook system, admin section registration, custom content types, custom templates/themes, DB integration via MariaDB connector.
- **Theme development:** Modular templates, asset management, theme options, SASS/SCSS, custom JS.
- **Custom handlers:** Custom routing, admin sections, file processors, authentication/permission systems.

## 13. Deployment Considerations

- **File permissions/ownership:** Set writable dirs to web server user, use 755/644, avoid 777/666, verify file ops.
- **Server config:** URL rewriting, PHP optimization, file upload/security settings, HTTPS enforcement.
- **Security hardening:** HTTPS, file access restrictions, error reporting config, regular audits/updates.
- **Environment config:** Development (debug), production (optimized), maintenance (maintenance mode).

## 14. Development Workflow

- **Local development:** PHP built-in server, file-based config, direct file editing, debug mode.
- **Plugin/theme development:** Directory structure, hook integration, admin UI, testing/validation.
- **Testing/QA:** Cross-browser/mobile testing, performance/security testing, responsive design.

---

This architecture provides a solid foundation for a lightweight, extensible content management system with strong separation of concerns, clear extension points for customization, and robust security practices through proper file ownership and standard permissions. 