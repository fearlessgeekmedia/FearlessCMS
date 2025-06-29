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
    │   ├── page.html
    │   ├── home.html (optional)
    │   ├── blog.html (optional)
    │   └── 404.html
    ├── assets/
    │   ├── style.css
    │   ├── images/
    │   └── js/
    ├── config.json
    └── README.md (optional)
```

## Required Files

### 1. config.json

This is the main theme configuration file that defines your theme's metadata and options:

```json
{
    "name": "Your Theme Name",
    "description": "A brief description of your theme",
    "version": "1.0.0",
    "author": "Your Name",
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner"
        }
    }
}
```

### 2. Templates

You need at least these template files in the `templates/` directory:

- **page.html** - Individual page template (required)
- **404.html** - Error page template (required)
- **home.html** - Homepage template (optional)
- **blog.html** - Blog listing template (optional)

**Note**: Only `page.html` and `404.html` are strictly required. The system will fall back to `page.html` for any missing templates.

## Template System

FearlessCMS uses Handlebars template syntax with variable substitution and conditional logic. Templates are written in HTML with special syntax for dynamic content.

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

FearlessCMS provides several built-in variables you can use in your templates. Variables can be accessed in two ways:

#### Direct Variable Access
- `{{siteName}}` - Site name from config
- `{{siteDescription}}` - Site description/tagline
- `{{theme}}` - Current theme name
- `{{currentYear}}` - Current year
- `{{title}}` - Page title
- `{{content}}` - Page content (HTML)
- `{{logo}}` - Theme logo option
- `{{heroBanner}}` - Theme hero banner option

#### Theme Options Access
- `{{themeOptions.logo}}` - Logo from theme options
- `{{themeOptions.herobanner}}` - Hero banner from theme options
- `{{themeOptions.primaryColor}}` - Primary color setting
- `{{themeOptions.fontFamily}}` - Font family setting

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
- `{{menu=name}}` - Any menu by name (e.g., `{{menu=main}}`, `{{menu=footer}}`, `{{menu=crazy}}`)

#### Theme Options
- `{{themeOptions.key}}` - Custom theme options
- Direct access: `{{logo}}`, `{{heroBanner}}`, etc.

### Conditional Logic

You can use conditional statements in your templates using Handlebars syntax:

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
            {{sidebar=main}}
        </div>
    </main>
    {{module=footer.html}}
</body>
</html>
```

### Creating Module Files

Create separate files for each component in your theme's `templates/` directory:

**head.html:**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{title}} - {{siteName}}</title>
<link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
```

**header.html:**
```html
<header class="site-header">
    <div class="container">
        <div class="logo">
            {{#if themeOptions.logo}}
                <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo-image">
            {{else}}
                <h1 class="site-title">{{siteName}}</h1>
            {{/if}}
        </div>
        
        {{#if siteDescription}}
            <p class="site-description">{{siteDescription}}</p>
        {{/if}}
        
        <nav class="main-navigation">
            {{menu=main}}
        </nav>
    </div>
</header>
```

**footer.html:**
```html
<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-info">
                <p>&copy; {{currentYear}} {{siteName}}. All rights reserved.</p>
            </div>
            
            <nav class="footer-navigation">
                {{menu=footer}}
            </nav>
        </div>
    </div>
</footer>
```

## Theme Options

Theme options allow users to customize your theme without editing code. You can define various options like images, colors, fonts, and layout settings.

### Defining Theme Options

Create a `config.json` file in your theme directory with an `options` section:

```json
{
    "name": "Your Theme Name",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "A brief description of your theme",
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner"
        },
        "primaryColor": {
            "type": "color",
            "label": "Primary Color",
            "default": "#007cba"
        },
        "fontFamily": {
            "type": "select",
            "label": "Font Family",
            "options": [
                {"value": "sans-serif", "label": "Sans Serif"},
                {"value": "serif", "label": "Serif"},
                {"value": "monospace", "label": "Monospace"}
            ],
            "default": "sans-serif"
        },
        "showSidebar": {
            "type": "boolean",
            "label": "Show Sidebar",
            "default": true
        }
    }
}
```

### Option Types

#### Image Upload
```json
{
    "logo": {
        "type": "image",
        "label": "Logo"
    }
}
```

#### Color Picker
```json
{
    "primaryColor": {
        "type": "color",
        "label": "Primary Color",
        "default": "#007cba"
    }
}
```

#### Text Input
```json
{
    "siteTagline": {
        "type": "text",
        "label": "Site Tagline",
        "default": "Welcome to our site"
    }
}
```

#### Select Dropdown
```json
{
    "fontFamily": {
        "type": "select",
        "label": "Font Family",
        "options": [
            {"value": "sans-serif", "label": "Sans Serif"},
            {"value": "serif", "label": "Serif"}
        ],
        "default": "sans-serif"
    }
}
```

#### Boolean (Checkbox)
```json
{
    "showSidebar": {
        "type": "boolean",
        "label": "Show Sidebar",
        "default": true
    }
}
```

## CSS and Styling

### Basic CSS Structure

Create a `style.css` file in your theme's `assets/` directory:

```css
/* Reset and base styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family, sans-serif);
    line-height: 1.6;
    color: var(--text-color, #333);
    background-color: var(--background-color, #fff);
}

/* Layout */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
.site-header {
    background: var(--primary-color, #007cba);
    color: white;
    padding: 1rem 0;
}

.site-title {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.site-description {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* Navigation */
.main-navigation ul {
    list-style: none;
    display: flex;
    gap: 2rem;
}

.main-navigation a {
    color: white;
    text-decoration: none;
    font-weight: 500;
}

.main-navigation a:hover {
    text-decoration: underline;
}

/* Main Content */
.site-main {
    padding: 2rem 0;
}

.content {
    max-width: 800px;
    margin: 0 auto;
}

/* Footer */
.site-footer {
    background: var(--footer-bg, #f5f5f5);
    padding: 2rem 0;
    margin-top: 3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .main-navigation ul {
        flex-direction: column;
        gap: 1rem;
    }
}
```

### CSS Custom Properties

Use CSS custom properties to make your theme more flexible:

```css
:root {
    --primary-color: {{themeOptions.primaryColor}};
    --font-family: {{themeOptions.fontFamily}};
    --text-color: #333;
    --background-color: #fff;
    --footer-bg: #f5f5f5;
}
```

## Example: Creating a Simple Theme

Let's create a complete theme called "SimpleBlog":

### 1. Create Theme Directory

```
themes/simple-blog/
├── templates/
│   ├── home.html
│   ├── page.html
│   ├── blog.html
│   └── 404.html
├── assets/
│   └── style.css
├── config.json
└── README.md
```

### 2. config.json

```json
{
    "name": "Simple Blog",
    "description": "A clean and simple blog theme",
    "version": "1.0.0",
    "author": "Your Name",
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner"
        }
    }
}
```

### 3. home.html

```html
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
</head>
<body>
    {{module=header.html}}
    
    <main class="site-main">
        <div class="container">
            <div class="home-hero">
                <h1 class="hero-title">{{#if title}}{{title}}{{else}}Welcome to {{siteName}}{{/if}}</h1>
                {{#if excerpt}}
                    <p class="hero-subtitle">{{excerpt}}</p>
                {{/if}}
            </div>
            
            <div class="content-layout">
                <div class="main-content">
                    <article class="content">
                        {{content}}
                    </article>
                </div>
                
                {{#if themeOptions.showSidebar}}
                    <aside class="sidebar">
                        {{#if children}}
                            <div class="sidebar-widget">
                                <h3>Recent Pages</h3>
                                <ul class="recent-pages">
                                    {{#each children}}
                                        <li class="recent-page">
                                            <a href="/{{url}}" class="recent-link">{{title}}</a>
                                        </li>
                                    {{/each}}
                                </ul>
                            </div>
                        {{/if}}
                    </aside>
                {{/if}}
            </div>
        </div>
    </main>
    
    {{module=footer.html}}
</body>
</html>
```

### 4. page.html

```html
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
</head>
<body>
    {{module=header.html}}
    
    <main class="site-main">
        <div class="container">
            <div class="content-layout">
                <div class="main-content">
                    <article class="content">
                        {{#if title}}
                            <header class="content-header">
                                <h1 class="content-title">{{title}}</h1>
                            </header>
                        {{/if}}
                        
                        <div class="content-body">
                            {{content}}
                        </div>
                    </article>
                </div>
                
                {{#if themeOptions.showSidebar}}
                    <aside class="sidebar">
                        {{#if children}}
                            <div class="sidebar-widget">
                                <h3>Related Pages</h3>
                                <ul class="related-pages">
                                    {{#each children}}
                                        <li class="related-page">
                                            <a href="/{{url}}" class="related-link">{{title}}</a>
                                        </li>
                                    {{/each}}
                                </ul>
                            </div>
                        {{/if}}
                    </aside>
                {{/if}}
            </div>
        </div>
    </main>
    
    {{module=footer.html}}
</body>
</html>
```

### 5. style.css

```css
/* Simple Blog Theme Styles */

:root {
    --primary-color: {{themeOptions.primaryColor}};
    --text-color: #333;
    --background-color: #fff;
    --border-color: #e0e0e0;
    --sidebar-bg: #f8f9fa;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--background-color);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
.site-header {
    background: var(--primary-color);
    color: white;
    padding: 2rem 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo-image {
    max-height: 50px;
}

.site-title {
    font-size: 2rem;
    font-weight: 700;
}

.site-description {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

/* Navigation */
.main-navigation ul {
    list-style: none;
    display: flex;
    gap: 2rem;
}

.main-navigation a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s;
}

.main-navigation a:hover {
    opacity: 0.8;
}

/* Main Content */
.site-main {
    padding: 3rem 0;
}

.home-hero {
    text-align: center;
    margin-bottom: 3rem;
}

.hero-title {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.hero-subtitle {
    font-size: 1.3rem;
    color: #666;
}

.content-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
}

{{#if themeOptions.showSidebar}}
.content-layout.with-sidebar {
    grid-template-columns: 2fr 1fr;
}
{{/if}}

.content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.content-title {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.content-body {
    font-size: 1.1rem;
    line-height: 1.8;
}

/* Sidebar */
.sidebar {
    background: var(--sidebar-bg);
    padding: 2rem;
    border-radius: 8px;
    height: fit-content;
}

.sidebar-widget h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.recent-pages,
.related-pages {
    list-style: none;
}

.recent-page,
.related-page {
    margin-bottom: 0.5rem;
}

.recent-link,
.related-link {
    color: var(--text-color);
    text-decoration: none;
    transition: color 0.2s;
}

.recent-link:hover,
.related-link:hover {
    color: var(--primary-color);
}

/* Footer */
.site-footer {
    background: var(--sidebar-bg);
    padding: 2rem 0;
    margin-top: 3rem;
    border-top: 1px solid var(--border-color);
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-menu {
    list-style: none;
    display: flex;
    gap: 2rem;
}

.footer-menu a {
    color: var(--text-color);
    text-decoration: none;
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .main-navigation ul {
        flex-direction: column;
        gap: 1rem;
    }
    
    .content-layout.with-sidebar {
        grid-template-columns: 1fr;
    }
    
    .footer-content {
        flex-direction: column;
        gap: 1rem;
    }
}
```

## Advanced Features

### Custom JavaScript

Add custom JavaScript to your theme by creating a `custom_js.html` template:

```html
<!-- custom_js.html -->
<script>
// Theme-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Your custom JavaScript here
    console.log('Theme loaded!');
});
</script>
```

### Custom CSS Classes

You can add custom CSS classes based on theme options:

```html
<body class="theme-{{themeOptions.fontFamily}} {{#if themeOptions.showSidebar}}with-sidebar{{/if}}">
```

### Conditional Styling

Use theme options to conditionally apply styles:

```css
{{#if themeOptions.showSidebar}}
.content-layout {
    grid-template-columns: 2fr 1fr;
}
{{else}}
.content-layout {
    grid-template-columns: 1fr;
}
{{/if}}
```

## Best Practices

### 1. Use Semantic HTML

Always use semantic HTML elements for better accessibility and SEO:

```html
<header class="site-header">
<nav class="main-navigation">
<main class="site-main">
<article class="content">
<aside class="sidebar">
<footer class="site-footer">
```

### 2. Make Themes Responsive

Ensure your themes work well on all device sizes:

```css
/* Mobile-first approach */
.content-layout {
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .content-layout.with-sidebar {
        grid-template-columns: 2fr 1fr;
    }
}
```

### 3. Use CSS Custom Properties

Make your themes more flexible with CSS custom properties:

```css
:root {
    --primary-color: {{themeOptions.primaryColor}};
    --font-family: {{themeOptions.fontFamily}};
}
```

### 4. Optimize Performance

- Minimize CSS and JavaScript
- Optimize images
- Use efficient selectors
- Avoid unnecessary DOM manipulation

### 5. Follow Accessibility Guidelines

- Use proper heading hierarchy
- Provide alt text for images
- Ensure sufficient color contrast
- Make navigation keyboard-accessible

### 6. Test Thoroughly

- Test on different browsers
- Test on mobile devices
- Test with different content lengths
- Test with various theme options

## Conclusion

Creating themes for FearlessCMS is straightforward and powerful. By following this guide and best practices, you can create professional, customizable themes that provide a great user experience.

Remember to:
- Use Handlebars syntax correctly (`{{#if}}`, `{{#each}}`, etc.)
- Make themes responsive and accessible
- Provide meaningful theme options
- Test thoroughly across different scenarios
- Follow modern web development best practices

Happy theming!

### Using Theme Options in Templates

You can access theme options in two ways:

#### Direct Variable Access
```html
{{#if logo}}
    <img src="/{{logo}}" alt="{{siteName}}" class="logo">
{{else}}
    <h1 class="site-title">{{siteName}}</h1>
{{/if}}

{{#if heroBanner}}
    <div class="hero-banner">
        <img src="/{{heroBanner}}" alt="{{title}}">
    </div>
{{/if}}
```

#### Theme Options Access
```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
{{else}}
    <h1 class="site-title">{{siteName}}</h1>
{{/if}}

<div class="content {{#if themeOptions.showSidebar}}with-sidebar{{/if}}">
    {{content}}
    
    {{#if themeOptions.showSidebar}}
        <aside class="sidebar">
            <!-- Sidebar content -->
        </aside>
    {{/if}}
</div>
```

**Note**: Both methods work the same way. Use whichever style you prefer for consistency in your theme. 