# Creating Themes in FearlessCMS

FearlessCMS uses a simple but powerful theme system that allows you to create custom themes with HTML templates, CSS styling, and optional theme options. This guide will walk you through creating a complete theme from scratch.

## Table of Contents

1. [Theme Structure](#theme-structure)
2. [Required Files](#required-files)
3. [Template System](#template-system)
4. [Template Variables](#template-variables)
5. [Theme Options](#theme-options)
6. [CSS and Styling](#css-and-styling)
7. [Example: Creating a Simple Theme](#example-creating-a-simple-theme)
8. [Advanced Features](#advanced-features)
9. [Best Practices](#best-practices)

## Theme Structure

A FearlessCMS theme consists of the following directory structure:

```
themes/
└── your-theme-name/
    ├── templates/
    │   ├── home.html              # Page template
    │   ├── page.html              # Page template
    │   ├── blog.html              # Page template
    │   ├── 404.html               # Page template
    │   ├── header.html.mod        # Module file
    │   ├── footer.html.mod        # Module file
    │   ├── head.html.mod          # Module file
    │   └── sidebar.html.mod       # Module file
    ├── assets/
    │   ├── style.css
    │   ├── images/
    │   └── js/
    ├── config.json                # Theme configuration
    └── README.md                  # Documentation
```

## Required Files

### 1. theme.json

This is the main theme configuration file that defines your theme's metadata:

```json
{
    "name": "Your Theme Name",
    "description": "A brief description of your theme",
    "version": "1.0.0",
    "author": "Your Name",
    "license": "MIT",
    "templates": {
        "home": "home.html",
        "page": "page.html",
        "blog": "blog.html",
        "404": "404.html"
    }
}
```

### 2. Templates

You need at least these **page template** files in the `templates/` directory:

- **page.html** - Individual page template (required)
- **404.html** - Error page template (required)
- **home.html** - Homepage template (optional)
- **blog.html** - Blog listing template (optional)

**Note**: Page templates use the `.html` extension and will appear as template options in the admin interface when creating or editing content.

### Page Templates vs Modules

**Page Templates** (`.html` extension):
- Used as full page layouts
- Appear in admin template selection dropdown
- Examples: `page.html`, `home.html`, `404.html`, `blog.html`

**Module Files** (`.html.mod` extension):
- Reusable components included in page templates
- Do not appear in admin template selection
- Examples: `header.html.mod`, `footer.html.mod`, `sidebar.html.mod`

## Template System

FearlessCMS uses a simple template system with variable substitution and conditional logic. Templates are written in HTML with special syntax for dynamic content.

### Basic Template Syntax

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <header>
        <h1>{{siteName}}</h1>
        <p>{{siteDescription}}</p>
    </header>
    
    <main>
        {{content}}
    </main>
    
    <footer>
        <p>&copy; {{currentYear}} {{siteName}}</p>
    </footer>
</body>
</html>
```

### Template Variables

FearlessCMS provides several built-in variables you can use in your templates:

#### Global Variables
- `{{siteName}}` - Site name from config
- `{{siteDescription}}` - Site description/tagline
- `{{theme}}` - Current theme name
- `{{currentYear}}` - Current year
- `{{baseUrl}}` - Base URL of the site

#### Page-Specific Variables
- `{{title}}` - Page title
- `{{content}}` - Page content (HTML)
- `{{url}}` - Current page URL
- `{{parent}}` - Parent page (if any)
- `{{children}}` - Child pages (if any)

#### Menu Variables
- `{{menu.main}}` - Main menu items
- `{{menu.footer}}` - Footer menu items

#### Theme Options
- `{{themeOptions.key}}` - Custom theme options

### Conditional Logic

You can use conditional statements in your templates:

```html
{{#if title}}
    <h1>{{title}}</h1>
{{/if}}

{{#if children}}
    <ul>
    {{#each children}}
        <li><a href="/{{url}}">{{title}}</a></li>
    {{/each}}
    </ul>
{{/if}}

{{#if themeOptions.showSidebar}}
    <aside>
        <!-- Sidebar content -->
    </aside>
{{/if}}
```

### Loops

Use loops to iterate over arrays:

```html
{{#each menu.main}}
    <li><a href="/{{url}}">{{title}}</a></li>
{{/each}}

{{#each themeOptions.socialLinks}}
    <a href="{{url}}" target="{{target}}">{{name}}</a>
{{/each}}
```

## Modular Templates

FearlessCMS supports modular templates, allowing you to break down your templates into reusable components. This makes themes more maintainable and reduces code duplication.

### Using Modular Templates

Instead of having everything in one large template file, you can break it into smaller, reusable modules:

```html
<!-- page.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
</head>
<body>
    {{module=header.html}}
    <main>
        {{module=hero-banner.html}}
        <div class="content">
            {{module=sidebar.html}}
        </div>
    </main>
    {{module=footer.html}}
</body>
</html>
```

### Creating Module Files

Create separate files for each component in your theme's `templates/` directory. **Important**: Module files should use the `.html.mod` extension to prevent them from appearing as page template options in the admin interface.

**head.html.mod:**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{title}} - {{siteName}}</title>
<link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
```

**header.html.mod:**
```html
<header>
    <div class="logo">{{siteName}}</div>
    {{#if siteDescription}}
    <p>{{siteDescription}}</p>
    {{/if}}
    <nav class="main-menu">
        {{menu=main}}
    </nav>
</header>
```

**footer.html.mod:**
```html
<footer>
    &copy; {{currentYear}} {{siteName}}
</footer>
```

### Module File Naming Convention

- **Use `.html.mod` extension** for all module files (e.g., `header.html.mod`, `footer.html.mod`)
- **Page templates** use `.html` extension (e.g., `page.html`, `home.html`, `404.html`)
- **Module syntax** remains the same: `{{module=header.html}}` (without the `.mod` extension)

### Module Features

- **Variable Access**: Modules have access to all template variables
- **Conditional Logic**: Support for all template conditionals
- **Loops**: Support for foreach loops
- **Nested Modules**: Modules can include other modules
- **Backward Compatibility**: Works with both `.html` and `.html.mod` extensions
- **Admin Integration**: `.html.mod` files are excluded from page template selection

### Benefits of Modular Templates

1. **Maintainability**: Common elements are in single files
2. **Reusability**: Modules can be used across multiple templates
3. **Consistency**: Changes update everywhere automatically
4. **Organization**: Cleaner, more organized code structure
5. **Testing**: Easier to test individual components

For detailed information about modular templates, see the [Modular Templates Guide](modular-templates.md).

## Theme Options

You can add custom theme options that users can configure through the admin panel. Create a `config.json` file in your theme directory:

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo Image",
            "description": "Upload your site logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner",
            "description": "Hero banner image for homepage"
        },
        "primaryColor": {
            "type": "select",
            "label": "Primary Color",
            "options": [
                {"value": "blue", "label": "Blue"},
                {"value": "green", "label": "Green"},
                {"value": "red", "label": "Red"}
            ],
            "default": "blue"
        },
        "showSidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": true
        }
    }
}
```

### Accessing Theme Options in Templates

```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="Logo">
{{/if}}

<div class="theme-{{themeOptions.primaryColor}}">
    <!-- Content with theme color -->
</div>

{{#if themeOptions.showSidebar}}
    <aside class="sidebar">
        <!-- Sidebar content -->
    </aside>
{{/if}}
```

## CSS and Styling

Create your CSS file in the `assets/` directory. You can use any CSS features including:

- CSS Grid and Flexbox
- CSS Custom Properties (variables)
- Media queries for responsive design
- CSS animations and transitions

### Example CSS Structure

```css
/* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}

/* Layout */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
header {
    background: #f8f9fa;
    padding: 2rem 0;
    border-bottom: 1px solid #e9ecef;
}

/* Navigation */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

nav ul {
    display: flex;
    list-style: none;
    gap: 2rem;
}

/* Main content */
main {
    padding: 2rem 0;
    min-height: 60vh;
}

/* Footer */
footer {
    background: #343a40;
    color: white;
    padding: 2rem 0;
    margin-top: auto;
}

/* Responsive design */
@media (max-width: 768px) {
    nav ul {
        flex-direction: column;
        gap: 1rem;
    }
}
```

## Example: Creating a Simple Theme

Let's create a complete example theme called "SimpleBlog":

### 1. Create Theme Directory

```bash
mkdir -p themes/simpleblog/templates
mkdir -p themes/simpleblog/assets
```

### 2. Create theme.json

```json
{
    "name": "Simple Blog",
    "description": "A clean and simple blog theme",
    "version": "1.0.0",
    "author": "Your Name",
    "license": "MIT",
    "templates": {
        "home": "home.html",
        "page": "page.html",
        "blog": "blog.html",
        "404": "404.html"
    }
}
```

### 3. Create config.json

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo",
            "description": "Upload your site logo"
        },
        "accentColor": {
            "type": "select",
            "label": "Accent Color",
            "options": [
                {"value": "blue", "label": "Blue"},
                {"value": "green", "label": "Green"},
                {"value": "purple", "label": "Purple"}
            ],
            "default": "blue"
        },
        "showSidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": true
        }
    }
}
```

### 4. Create Templates

**templates/home.html:**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            {{#if themeOptions.logo}}
                <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
            {{else}}
                <h1>{{siteName}}</h1>
            {{/if}}
            <p>{{siteDescription}}</p>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="content-area">
                <article>
                    {{content}}
                </article>
            </div>
            
            {{#if themeOptions.showSidebar}}
                <aside class="sidebar">
                    <h3>Recent Posts</h3>
                    {{#each children}}
                        <div class="post-preview">
                            <h4><a href="/{{url}}">{{title}}</a></h4>
                            <p>{{excerpt}}</p>
                        </div>
                    {{/each}}
                </aside>
            {{/if}}
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
```

**templates/page.html:**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}} - {{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>{{title}}</h1>
        </div>
    </header>

    <main>
        <div class="container">
            <article>
                {{content}}
            </article>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}</p>
        </div>
    </footer>
</body>
</html>
```

### 5. Create CSS

**assets/style.css:**
```css
:root {
    --accent-color: #007bff;
    --text-color: #333;
    --bg-color: #fff;
    --border-color: #e9ecef;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background: var(--bg-color);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

header {
    background: #f8f9fa;
    padding: 2rem 0;
    border-bottom: 1px solid var(--border-color);
}

.logo {
    max-height: 60px;
    width: auto;
}

main {
    padding: 2rem 0;
    min-height: 60vh;
}

.content-area {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.sidebar {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.post-preview {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.post-preview:last-child {
    border-bottom: none;
}

footer {
    background: #343a40;
    color: white;
    padding: 2rem 0;
    margin-top: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .content-area {
        grid-template-columns: 1fr;
    }
}
```

## Advanced Features

### Custom Template Functions

You can extend the template system by adding custom functions to the TemplateRenderer class.

### Dynamic Menus

Menus are automatically generated from your content structure and can be customized in the admin panel.

### SEO Optimization

Templates automatically include meta tags and structured data for better SEO.

## Best Practices

1. **Keep it Simple**: Start with a basic theme and add features gradually
2. **Responsive Design**: Always make your themes mobile-friendly
3. **Semantic HTML**: Use proper HTML5 semantic elements
4. **Accessibility**: Follow WCAG guidelines for accessibility
5. **Performance**: Optimize images and minimize CSS/JS
6. **Documentation**: Include a README.md with installation and customization instructions
7. **Testing**: Test your theme with different content types and screen sizes

### File Naming Conventions

- Use lowercase for file names
- Use hyphens for spaces in file names
- Keep template names descriptive but short

### CSS Organization

- Use CSS custom properties for theme colors
- Organize CSS with comments
- Use a mobile-first approach
- Keep specificity low to avoid conflicts

## Next Steps

Once you've created your theme:

1. Test it thoroughly with different content
2. Add it to your FearlessCMS installation
3. Activate it in the admin panel
4. Customize theme options
5. Share it with the community!

For more advanced theme development, check out the existing themes in the `themes/` directory for examples and inspiration. 