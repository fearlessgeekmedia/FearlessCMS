# FearlessCMS Project Agent Instructions

## Project Overview
FearlessCMS is a lightweight, flat-file PHP content management system. No database — content is stored as Markdown files with JSON frontmatter in `content/`. Key characteristics:
- PHP 7.4+ with Composer
- Flat-file architecture (no database required)
- Custom PHP template engine with Handlebars-inspired syntax
- Admin panel called "Mission Control" built with Tailwind CSS
- Plugin system via hook/filter architecture
- Three CMS modes: `full-featured`, `hosting-service-plugins`, `hosting-service-no-plugins`

## Directory Structure
- `themes/` — Theme templates and CSS
- `content/` — Flat-file content (.md with JSON frontmatter)
- `admin/` — Admin panel (Mission Control)
- `includes/` — Core PHP classes (ThemeManager, TemplateRenderer, MenuManager, etc.)
- `config/` — Site and theme configuration JSON files
- `public/` — Public web root
- `fcmsdocs/` — Project documentation

## Working with Themes

### Theme Structure
```
themes/{theme-name}/
├── config.json          # Theme metadata + options (REQUIRED)
├── templates/
│   ├── page.html        # REQUIRED - generic page template
│   ├── 404.html         # REQUIRED - error page
│   ├── home.html        # optional homepage
│   ├── blog.html        # optional blog listing
│   ├── *.html.mod       # reusable modules (hidden from admin)
│   └── *.html           # page templates (visible in admin)
├── assets/
│   ├── style.css        # Main stylesheet
│   └── js/              # JavaScript
└── thumbnail.png        # optional preview image
```

### Template Syntax
- `{{variable}}` — Variable substitution (both camelCase and snake_case supported)
- `{{#if condition}}...{{/if}}` — Conditionals
- `{{#each arrayName}}...{{/each}}` — Loops
- `{{module=filename.html}}` — Include module from current theme
- `{{include=filename.html}}` — Include from themes/ root
- `{{sidebar=name}}` — Render sidebar widgets
- `{{menu=main}}` — Render menu by ID

### Key Variables
- `{{siteName}}`, `{{site_name}}`
- `{{title}}`, `{{content}}`, `{{url}}`
- `{{themeOptions.keyName}}`
- `{{menu.main}}`, `{{menu.footer}}`
- `{{currentYear}}`, `{{current_year}}`

### Theme Validation
A theme passes validation if it has a `templates/` directory containing both `page.html` and `404.html`. Active theme is set in `config/config.json` under `active_theme`.

## Working with the Admin Panel
- Admin routes are defined in `admin/index.php` via `$template_map`
- Menu handlers: `admin/includes/menu-handlers.php`
- Menu AJAX handler: `menu-ajax-handler.php`
- Admin sections are registered via `fcms_register_admin_section()` in `includes/plugins.php`
- Templates reference variables like `$menu_options` — ensure these are defined and globalized before the base template includes them

## PHP Conventions
- Short array syntax `[]`
- Namespaces/classes in `includes/` directory
- Config constants in `includes/config.php` (CONFIG_DIR, CONTENT_DIR, etc.)
- JSON config files use `JSON_PRETTY_PRINT` when writing
- Session management via `includes/session.php`
- Permission checks via `fcms_check_permission()`

## Current Work
- Building the **Terminal** theme at `themes/terminal/` — retro CRT/command-line aesthetic
- Fixed admin menu dropdown not populating (`$menu_options` scope issue in `admin/index.php`)

## Known Issues / Notes
- `manage_menus` uses a closure render callback — variables must be explicitly globalized for template visibility
- Menu AJAX handler exists at both `admin/includes/menu-handlers.php` (POST) and root `menu-ajax-handler.php` (AJAX)
- Default theme is the most complete reference for theme structure

## FearlessCMS Manifesto

> Source: https://github.com/fearlessgeekmedia/fearlesscms

For years, the World Wide Web has been dominated by content management systems that have become bloated and clunky over time. **FearlessCMS** strives to be different.

Open-source projects must respect both the users of the software and the developers who contribute to it. FearlessCMS will adhere to these principles by fostering an inclusive, transparent, and collaborative environment for all contributors.

### Fearless Geek Media declares the following in the making of FearlessCMS:

1. **FearlessCMS will respect the culture and spirit of open-source software.**
   - Contributors are encouraged to improve, distribute, and use the code for commercial or non-commercial purposes, as long as proper attribution is given to developers and contributors.
   - All trademarks associated with the original project can be used freely by others, without restriction.
   - Input from designers, developers, and users is welcome and integral to the platform's success.

2. **Templates should be templates. Plugins should be plugins.**
   - In FearlessCMS, themes and templates are not to include plugin functionality, and plugins should be clearly declared dependencies, well-documented, and disclosed upfront.
   - Themes and templates will consist only of **HTML, CSS, and JavaScript**, with minimal JavaScript to limit potential attack vectors.

3. **FearlessCMS will evolve with technology.**
   - Technology is constantly advancing. Every two years after the initial launch, the codebase will be reviewed for efficiency and overhauled if necessary.

4. **Security and privacy are non-negotiable.**
   - Regular security audits and prompt patching of vulnerabilities are essential parts of the development cycle.

5. **The platform will remain lightweight and performance-driven.**
   - Bloat is the enemy. The CMS will be designed to be as lightweight as possible without compromising functionality.

6. **Accessibility and inclusivity are core values.**
   - FearlessCMS will follow web standards to ensure the platform is accessible to users with disabilities, including adherence to **WCAG** guidelines.
   - An inclusive community where everyone, regardless of skill level or background, is encouraged to participate.

7. **Ethical development and use.**
   - Developers contributing to FearlessCMS will uphold ethical programming standards, prioritizing user safety, privacy, and the open web.
   - FearlessCMS will not tolerate contributions that introduce unethical practices, such as data mining, surveillance, or exploitation of vulnerabilities.

8. **Sustainability in software development.**
   - FearlessCMS will strive to use efficient code and consider its carbon footprint, aiming to reduce unnecessary resource usage.

---

*The ideas in section 2 were contributed by James Potts.*

With these policies in place, FearlessCMS seeks to create a platform that is lightweight, secure, and accessible, but most importantly, it is a CMS **for the people**. Whether you are a developer, a designer, or a casual user, FearlessCMS will respect your needs and provide a toolset for creating websites that empower the open web and its diverse community.

## How Kilo Should Work Here
- Read `fcmsdocs/` before making changes to understand the system
- Validate theme structure after creation (check required files exist)
- Check PHP syntax with `php -l` after edits
- Keep theme templates consistent with existing conventions
- Don't assume external libraries exist — check `composer.json` or `package.json` first
- Don't commit changes unless explicitly asked
