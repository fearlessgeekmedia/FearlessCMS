# Theme Options Guide

This guide explains how to implement and use theme options in FearlessCMS, allowing users to customize themes through the admin panel.

## Overview

Theme options allow you to make your themes configurable without requiring users to edit code. Users can customize colors, upload images, toggle features, and more through a user-friendly interface.

## Creating Theme Options

### 1. Define Options in config.json

Create a `config.json` file in your theme directory to define available options:

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo Image",
            "description": "Upload your site logo (recommended: 200x60px)"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner",
            "description": "Hero banner image for homepage"
        },
        "primaryColor": {
            "type": "select",
            "label": "Primary Color",
            "description": "Choose your theme's primary color",
            "options": [
                {"value": "blue", "label": "Blue"},
                {"value": "green", "label": "Green"},
                {"value": "purple", "label": "Purple"},
                {"value": "orange", "label": "Orange"}
            ],
            "default": "blue"
        },
        "showSidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "description": "Display sidebar on all pages",
            "default": true
        },
        "footerText": {
            "type": "text",
            "label": "Footer Text",
            "description": "Custom text to display in footer",
            "default": "© 2024 My Site"
        },
        "socialLinks": {
            "type": "array",
            "label": "Social Links",
            "description": "Add social media links",
            "fields": {
                "name": {"type": "text", "label": "Name"},
                "url": {"type": "text", "label": "URL"},
                "icon": {"type": "text", "label": "Icon Class"}
            }
        }
    }
}
```

### 2. Option Types

FearlessCMS supports several option types:

#### Image Upload
```json
{
    "logo": {
        "type": "image",
        "label": "Logo",
        "description": "Upload your logo"
    }
}
```

#### Text Input
```json
{
    "siteTitle": {
        "type": "text",
        "label": "Site Title",
        "description": "Your site title",
        "default": "My Site"
    }
}
```

#### Textarea
```json
{
    "footerText": {
        "type": "textarea",
        "label": "Footer Text",
        "description": "Custom text to display in the footer",
        "rows": 3
    }
}
```

#### Select Dropdown
```json
{
    "layout": {
        "type": "select",
        "label": "Layout Style",
        "options": [
            {"value": "wide", "label": "Wide Layout"},
            {"value": "narrow", "label": "Narrow Layout"},
            {"value": "sidebar", "label": "With Sidebar"}
        ],
        "default": "wide"
    }
}
```

#### Checkbox
```json
{
    "showSearch": {
        "type": "checkbox",
        "label": "Show Search",
        "description": "Display search box in header",
        "default": true
    }
}
```

#### Color Picker
```json
{
    "accentColor": {
        "type": "color",
        "label": "Accent Color",
        "description": "Choose accent color",
        "default": "#007bff"
    }
}
```

#### Array/Repeater
```json
{
    "socialLinks": {
        "type": "array",
        "label": "Social Links",
        "fields": {
            "platform": {
                "type": "select",
                "label": "Platform",
                "options": [
                    {"value": "facebook", "label": "Facebook"},
                    {"value": "twitter", "label": "Twitter"},
                    {"value": "instagram", "label": "Instagram"}
                ]
            },
            "url": {
                "type": "text",
                "label": "URL"
            },
            "icon": {
                "type": "text",
                "label": "Icon Class"
            }
        }
    }
}
```

## Using Theme Options in Templates

### Basic Usage

Access theme options using the `{{themeOptions.key}}` syntax:

```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="Logo" class="logo">
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

### Using Theme Options with Modular Templates

When using modular templates, theme options work seamlessly across all modules:

**Main template (page.html):**
```html
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

**Header module (header.html):**
```html
<header class="theme-{{themeOptions.primaryColor}}">
    {{#if themeOptions.logo}}
        <img src="/{{themeOptions.logo}}" alt="Logo" class="logo">
    {{else}}
        <h1>{{siteName}}</h1>
    {{/if}}
    
    {{#if themeOptions.showSearch}}
        <div class="search-box">
            <!-- Search functionality -->
        </div>
    {{/if}}
</header>
```

**Sidebar module (sidebar.html):**
```html
{{#if themeOptions.showSidebar}}
    <aside class="sidebar">
        {{#if themeOptions.socialLinks}}
            <div class="social-links">
                {{#each themeOptions.socialLinks}}
                    <a href="{{url}}" target="_blank">
                        <i class="{{icon}}"></i>
                        {{name}}
                    </a>
                {{/each}}
            </div>
        {{/if}}
    </aside>
{{/if}}
```

**Footer module (footer.html):**
```html
<footer>
    {{#if themeOptions.footerText}}
        <p>{{themeOptions.footerText}}</p>
    {{else}}
        <p>&copy; {{currentYear}} {{siteName}}</p>
    {{/if}}
</footer>
```

This approach allows you to:
- **Organize theme options** by component (header options in header.html)
- **Maintain consistency** across all templates
- **Simplify maintenance** by keeping related code together
- **Reuse components** with different theme option configurations

## CSS Integration

### Using CSS Custom Properties

```css
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --accent-color: #28a745;
}

/* Override with theme options */
.theme-blue {
    --primary-color: #007bff;
    --accent-color: #0056b3;
}

.theme-green {
    --primary-color: #28a745;
    --accent-color: #1e7e34;
}

.theme-purple {
    --primary-color: #6f42c1;
    --accent-color: #5a2d91;
}
```

### Dynamic CSS with Theme Options

```html
<style>
    .hero-banner {
        background-image: url('/{{themeOptions.herobanner}}');
    }
    
    .footer-text {
    {{themeOptions.footerText}}
}
</style>
```

## Complete Example: Blog Theme

### config.json

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo",
            "description": "Upload your site logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner",
            "description": "Hero banner for homepage"
        },
        "colorScheme": {
            "type": "select",
            "label": "Color Scheme",
            "options": [
                {"value": "light", "label": "Light"},
                {"value": "dark", "label": "Dark"},
                {"value": "auto", "label": "Auto (follows system)"}
            ],
            "default": "light"
        },
        "primaryColor": {
            "type": "color",
            "label": "Primary Color",
            "default": "#007bff"
        },
        "showSidebar": {
            "type": "checkbox",
            "label": "Show Sidebar",
            "default": true
        },
        "sidebarPosition": {
            "type": "select",
            "label": "Sidebar Position",
            "options": [
                {"value": "left", "label": "Left"},
                {"value": "right", "label": "Right"}
            ],
            "default": "right"
        },
        "showSearch": {
            "type": "checkbox",
            "label": "Show Search",
            "default": true
        },
        "footerText": {
            "type": "text",
            "label": "Footer Text",
            "default": "© 2024 My Blog"
        },
        "socialLinks": {
            "type": "array",
            "label": "Social Links",
            "fields": {
                "name": {"type": "text", "label": "Name"},
                "url": {"type": "text", "label": "URL"},
                "icon": {"type": "text", "label": "Icon Class"}
            }
        },
        "footerText": {
            "type": "textarea",
            "label": "Footer Text",
            "description": "Custom text to display in the footer",
            "rows": 3
        }
    }
}
```

### Template Usage

```html
<!DOCTYPE html>
<html lang="en" class="theme-{{themeOptions.colorScheme}}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}} - {{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
    <!-- Theme options are applied via CSS classes and inline styles -->
</head>
<body>
    <header class="header">
        <div class="container">
            {{#if themeOptions.logo}}
                <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
            {{else}}
                <h1>{{siteName}}</h1>
            {{/if}}
            
            <nav class="nav">
                {{#if menu.main}}
                    <ul>
                        {{#each menu.main}}
                            <li><a href="/{{url}}">{{title}}</a></li>
                        {{/each}}
                    </ul>
                {{/if}}
            </nav>
            
            {{#if themeOptions.showSearch}}
                <div class="search">
                    <input type="search" placeholder="Search...">
                </div>
            {{/if}}
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="layout {{#if themeOptions.showSidebar}}with-sidebar sidebar-{{themeOptions.sidebarPosition}}{{/if}}">
                <div class="content">
                    {{#if themeOptions.herobanner && url == 'home'}}
                        <div class="hero-banner" style="background-image: url('/{{themeOptions.herobanner}}')">
                            <h2>{{title}}</h2>
                        </div>
                    {{/if}}
                    
                    <article>
                        {{content}}
                    </article>
                </div>
                
                {{#if themeOptions.showSidebar}}
                    <aside class="sidebar">
                        {{#if menu.sidebar}}
                            <nav class="sidebar-nav">
                                <h3>Categories</h3>
                                <ul>
                                    {{#each menu.sidebar}}
                                        <li><a href="/{{url}}">{{title}}</a></li>
                                    {{/each}}
                                </ul>
                            </nav>
                        {{/if}}
                    </aside>
                {{/if}}
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>{{themeOptions.footerText}}</p>
            
            {{#if themeOptions.socialLinks}}
                <div class="social-links">
                    {{#each themeOptions.socialLinks}}
                        <a href="{{url}}" target="_blank" rel="noopener">
                            {{#if icon}}
                                <i class="{{icon}}"></i>
                            {{/if}}
                            {{name}}
                        </a>
                    {{/each}}
                </div>
            {{/if}}
        </div>
    </footer>
</body>
</html>
```

### CSS with Theme Options

```css
:root {
    --primary-color: #007bff;
    --text-color: #333;
    --bg-color: #fff;
    --border-color: #e9ecef;
}

/* Dark theme */
.theme-dark {
    --text-color: #fff;
    --bg-color: #1a1a1a;
    --border-color: #333;
}

/* Layout variations */
.layout.with-sidebar {
    display: grid;
    gap: 2rem;
}

.layout.with-sidebar.sidebar-left {
    grid-template-columns: 300px 1fr;
}

.layout.with-sidebar.sidebar-right {
    grid-template-columns: 1fr 300px;
}

/* Hero banner */
.hero-banner {
    background-size: cover;
    background-position: center;
    padding: 4rem 0;
    color: white;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .layout.with-sidebar {
        grid-template-columns: 1fr;
    }
}
```

## Best Practices

### 1. Provide Sensible Defaults

Always provide default values for your options:

```json
{
    "primaryColor": {
        "type": "color",
        "label": "Primary Color",
        "default": "#007bff"
    }
}
```

### 2. Use Descriptive Labels

Make option labels clear and user-friendly:

```json
{
    "showSidebar": {
        "type": "checkbox",
        "label": "Display Sidebar on All Pages",
        "description": "Show the sidebar navigation on every page"
    }
}
```

### 3. Group Related Options

Organize related options together in your config:

```json
{
    "options": {
        "header": {
            "logo": { "type": "image", "label": "Logo" },
            "showSearch": { "type": "checkbox", "label": "Show Search" }
        },
        "layout": {
            "showSidebar": { "type": "checkbox", "label": "Show Sidebar" },
            "sidebarPosition": { "type": "select", "label": "Sidebar Position" }
        }
    }
}
```

### 4. Validate User Input

Always check if options exist before using them:

```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="Logo">
{{/if}}
```

### 5. Provide Fallbacks

Use fallback values when options aren't set:

```html
<div class="theme-{{themeOptions.colorScheme || 'light'}}">
    <!-- Content -->
</div>
```

## Troubleshooting

### Common Issues

1. **Option not showing**: Check if the option is defined in `config.json`
2. **Value not updating**: Clear browser cache and refresh
3. **Image not displaying**: Check file permissions and path
4. **CSS not applying**: Verify CSS custom properties are defined

### Debug Tips

- Use browser developer tools to inspect theme option values
- Check the `config/theme_options.json` file for saved values
- Test with different option combinations
- Verify template syntax is correct 