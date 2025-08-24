# FearlessCMS Architecture Documentation

> **Changelog (2025-08):**
> - **Major Update**: Transitioned from Markdown to HTML editing system
> - **New HTML Editor**: Dual-mode WYSIWYG + Code view editor with automatic content sync
> - **Enhanced Export System**: Full HTML content support with parallax plugin integration
> - **New Themes**: whisperwind, punk_rock, salt-lake, heroic, simple-modern, vintage
> - **Plugin Updates**: Enhanced parallax plugin with v2.0 improvements, forms plugin with admin interface
> - **Ad Area System**: Conditional advertising system for hosting service modes
> - **Security Enhancements**: Comprehensive security update management system
> - **Development Tools**: Hugo to FearlessCMS theme converter, enhanced build documentation
> - **Performance**: Improved caching system and static site generation

> **Previous Changelog (2024-07):**
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

FearlessCMS is a lightweight, file-based content management system built in PHP. It features a modular architecture with plugin support, a modern theme system, and three operational modes for different deployment scenarios. The system prioritizes security, performance, and maintainability through proper file ownership and standard permissions. **Recent major updates include a complete transition from Markdown to HTML editing and enhanced export capabilities.**

## 1. Entry Points

- **index.php (Root):** Main frontend entry point; initializes session, routes requests, loads themes, processes HTML content with JSON frontmatter, handles plugin hooks.
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

- **HTML + JSON frontmatter:** Content files are now HTML (.md files converted to HTML) with a JSON frontmatter block for metadata (title, template, editor_mode, parent, etc).
- **Dual-mode HTML Editor:** New WYSIWYG editor with code view mode, automatic content sync between modes, and full shortcode support.
- **Hierarchical structure:** Supports folders/subfolders for nested content; parent/child relationships managed in frontmatter.
- **Content types:** Pages, blog posts, forms, imported WordPress content, plugin-specific data.
- **content/** directory structure:
  - home.md, about.md, blog_posts.json, forms/ (plugin data), form_submissions/, _preview/ (for previews), and subfolders for categories or custom types.
- **Static Export:** export.js provides Node.js-based static site generation with full HTML content processing, parallax plugin integration, and SEO optimization.

## 5. Theme System

- **includes/ThemeManager.php:** Theme management and rendering; discovers themes, loads config, renders templates, manages assets.
- **Modular templates:** Themes use .html.mod files for reusable components (header, footer, sidebar, etc). Included with `{{module=header.html}}` syntax. Page templates use .html.
- **Theme options:** Defined in config.json in each theme; supports text, textarea, select, checkbox, color, image, array. Accessible in templates as `{{themeOptions.key}}`.
- **SASS/SCSS support:** Themes can include SASS/SCSS for advanced styling; compiled to assets/style.css.
- **Current themes:** default, minimal, minimalist, modern-cards, elegant-dark, cyberpunk, custom-variables-demo, heroic, salt-lake, simple-modern, starterscores, vintage, whisperwind, punk_rock.
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
  - **forms:** Contact forms and form submissions with comprehensive admin interface
  - **seo:** Basic SEO meta tags and optimization
  - **ecommerce:** E-commerce functionality
  - **wordpress-import:** WordPress XML import functionality
  - **parallax:** Enhanced parallax sections with v2.0 improvements (image coverage fixes, CSS architecture, dark mode support)
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
  - hosting-service-plugins: Plugins allowed, no store, includes ad area system
  - hosting-service-no-plugins: No plugin management, only pre-installed plugins, includes ad area system
- **Mode enforcement:** Admin UI, plugin actions, file management, and uploads are restricted based on mode. Navigation adapts to mode.
- **Ad Area System:** Conditional advertising system that displays ads only in hosting service modes, providing professional hosting experience.
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
- **Security Update Management:** Comprehensive security update and patch management system with automated vulnerability scanning and emergency patch procedures.

## 11. Performance Considerations

- **File-based page caching:** Public (non-logged-in) pages are cached as static HTML files in the `cache/` directory for 5 minutes by default. This reduces server load and improves response times. Cache is automatically cleared when content is updated via the admin interface.
- **Static site export:** export.js provides full static site generation with HTML content support, parallax plugin integration, and maximum performance for CDN deployment.
- **Caching:** Template caching, plugin hook caching, file system caching, session file optimization.
- **Optimization:** Lazy loading of plugins/themes, efficient file ops, minimal DB dependencies, optimized template rendering.
- **Asset management:** Theme asset organization, static file serving, image optimization, JS/CSS minification.

## 12. Development and Build Tools

- **CRUSH.md:** Comprehensive build, lint, and test documentation with code style guidelines.
- **Package management:** package.json and package-lock.json for Node.js dependencies (export functionality).
- **Development scripts:** serve.sh for local development server, various test files for debugging.
- **Debug tools:** debug_plugin.php, debug_plugin_loading.php, test_*.php files for development and troubleshooting.
- **Build artifacts:** Build outputs and temporary files managed by build system.
- **Hugo to FearlessCMS Converter:** Python tool for automatically converting Hugo themes to FearlessCMS themes with template conversion, variable mapping, and asset conversion.

## 13. Extension Points

- **Plugin development:** Hook system, admin section registration, custom content types, custom templates/themes, DB integration via MariaDB connector.
- **Theme development:** Modular templates, asset management, theme options, SASS/SCSS, custom JS.
- **Custom handlers:** Custom routing, admin sections, file processors, authentication/permission systems.
- **Static export customization:** export.js can be extended for custom export formats and optimizations.
- **HTML editor extensions:** Custom editor plugins and content processors.

## 14. Deployment Considerations

- **File permissions/ownership:** Set writable dirs to web server user, use 755/644, avoid 777/666, verify file ops.
- **Server config:** URL rewriting, PHP optimization, file upload/security settings, HTTPS enforcement.
- **Security hardening:** HTTPS, file access restrictions, error reporting config, regular audits/updates.
- **Environment config:** Development (debug), production (optimized), maintenance (maintenance mode).
- **Static export:** Use export.js for CDN deployment and maximum performance with full HTML content support.

## 15. Development Workflow

- **Local development:** PHP built-in server via serve.sh, file-based config, direct file editing, debug mode.
- **Plugin/theme development:** Directory structure, hook integration, admin UI, testing/validation.
- **Testing/QA:** Cross-browser/mobile testing, performance/security testing, responsive design.
- **Build process:** Use CRUSH.md guidelines for consistent code quality and testing.
- **Theme conversion:** Use Hugo to FearlessCMS converter for rapid theme development.

## 16. New Features and Enhancements (2025-08)

### HTML Editor System
- **Dual-mode editing:** Rich WYSIWYG editor with code view mode
- **Automatic content sync:** Seamless switching between editor modes
- **Shortcode support:** Full support for parallax and other shortcodes
- **Layout preservation:** Maintains feature cards and complex structures
- **Export integration:** Full static site generation with HTML content

### Enhanced Export System
- **HTML content processing:** No more Markdown conversion needed
- **Parallax plugin integration:** Automatic CSS/JS generation for parallax effects
- **Asset management:** Smart inclusion of required resources
- **Performance optimized:** Production-ready static sites

### Plugin Improvements
- **Parallax Plugin v2.0:** Fixed image coverage issues, improved CSS architecture, enhanced JavaScript performance, better dark mode support
- **Forms Plugin:** Comprehensive admin interface for form management and submissions
- **Ad Area System:** Conditional advertising for hosting service modes

### New Themes
- **whisperwind:** Modern design with stacked waves and responsive layout
- **punk_rock:** Edgy aesthetic with custom styling
- **salt-lake:** Clean, professional design
- **heroic:** Hero-focused layout with modern components
- **simple-modern:** Minimalist approach with clean typography
- **vintage:** Classic design with modern touches

### Security Enhancements
- **Security Update Management:** Comprehensive patch management system
- **Automated vulnerability scanning:** Built-in security monitoring
- **Emergency patch procedures:** Rapid response to critical vulnerabilities

### Development Tools
- **Hugo to FearlessCMS Converter:** Automated theme conversion tool
- **Enhanced build documentation:** Comprehensive development guidelines
- **Improved testing framework:** Better debugging and validation tools

## 17. Migration and Compatibility

### Markdown to HTML Transition
- **Automatic conversion:** All existing Markdown content automatically converted to HTML
- **Backup creation:** Original files preserved with timestamps
- **Content preservation:** All formatting, layouts, and shortcodes maintained
- **Export compatibility:** Static site generation fully supports HTML content

### Plugin Compatibility
- **Shortcode support:** All existing shortcodes continue to work
- **Template compatibility:** Themes automatically handle HTML content
- **Export integration:** Plugins integrate seamlessly with export system

---

This architecture provides a solid foundation for a lightweight, extensible content management system with strong separation of concerns, clear extension points for customization, robust security practices through proper file ownership and standard permissions, comprehensive development tools for building and maintaining high-quality websites, and a modern HTML editing experience that maintains all the flexibility and power of the previous system while providing enhanced usability and export capabilities. 