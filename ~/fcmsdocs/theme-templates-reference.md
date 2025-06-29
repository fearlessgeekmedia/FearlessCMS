# Theme Templates Reference

This document provides a comprehensive reference for all template syntax, variables, and features available in FearlessCMS themes.

## Table of Contents

1. [Template Syntax](#template-syntax)
2. [Template Variables](#template-variables)
3. [Custom Variables](#custom-variables)
4. [Conditional Logic](#conditional-logic)
5. [Loops and Iteration](#loops-and-iteration)
6. [Modular Templates](#modular-templates)
7. [Sidebars and Widgets](#sidebars-and-widgets)
8. [Template Examples](#template-examples)
9. [Advanced Features](#advanced-features)

## Template Syntax

FearlessCMS uses Handlebars template syntax for dynamic content rendering. All templates are HTML files with embedded Handlebars expressions.

### Basic Syntax

```html
<!-- Variable output -->
<h1>{{title}}</h1>
<p>{{content}}</p>

<!-- HTML attributes -->
<img src="{{imageUrl}}" alt="{{imageAlt}}">
<a href="/{{url}}" class="{{#if active}}active{{/if}}">Link</a>

<!-- Comments -->
{{!-- This is a Handlebars comment --}}
```

## Template Variables

### Global Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{siteName}}` | Site name from configuration | "My Website" |
| `{{siteDescription}}` | Site description/tagline | "A great website" |
| `{{theme}}` | Current theme name | "modern-minimal" |
| `{{currentYear}}` | Current year | "2024" |
| `{{baseUrl}}` | Base URL of the site | "https://example.com" |
| `{{currentUrl}}` | Current page URL | "/about" |

### Page Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{title}}` | Page title | "About Us" |
| `{{content}}` | Page content (HTML) | "<p>Page content...</p>" |
| `{{url}}` | Page URL | "about" |
| `{{excerpt}}` | Page excerpt | "Brief description..." |
| `{{date}}` | Page date (if set) | "2024-01-15" |
| `{{author}}` | Page author (if set) | "John Doe" |
| `{{parent}}` | Parent page object | Object with parent page data |
| `{{children}}` | Array of child pages | Array of page objects |

### Menu Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{menu=name}}` | Any menu by name | Rendered menu HTML |

### Theme Options

Theme options can be accessed in two ways:

#### Direct Variable Access
| Variable | Description | Example |
|----------|-------------|---------|
| `{{logo}}` | Logo from theme options | "/uploads/logo.png" |
| `{{heroBanner}}` | Hero banner from theme options | "/uploads/banner.jpg" |
| `{{primaryColor}}` | Primary color setting | "#007cba" |
| `{{fontFamily}}` | Font family setting | "sans-serif" |

#### Theme Options Object Access
| Variable | Description | Example |
|----------|-------------|---------|
| `{{themeOptions.logo}}` | Logo from theme options | "/uploads/logo.png" |
| `{{themeOptions.herobanner}}` | Hero banner from theme options | "/uploads/banner.jpg" |
| `{{themeOptions.primaryColor}}` | Primary color setting | "#007cba" |
| `{{themeOptions.fontFamily}}` | Font family setting | "sans-serif" |

## Custom Variables

FearlessCMS supports custom variables that you can define and use in your templates. These provide flexibility for creating dynamic, customizable content.

### Page-Level Custom Variables

Add custom fields to your markdown files using JSON frontmatter at the top of the file:

```markdown
<!-- json {
    "title": "My Page Title",
    "customField": "My Custom Value",
    "showSidebar": true,
    "specialColor": "#ff0000",
    "featuredImage": "/uploads/featured.jpg",
    "tags": ["tag1", "tag2", "tag3"],
    "meta": {
        "description": "Custom meta description",
        "keywords": "custom, keywords"
    }
} -->

Your page content here...
```

### Using Custom Variables in Templates

Once defined in frontmatter, custom variables are automatically available in your templates:

```html
<h1>{{title}}</h1>
<p>{{customField}}</p>

{{#if showSidebar}}
    <aside class="sidebar">
        {{sidebar=main}}
    </aside>
{{/if}}

<div style="color: {{specialColor}}">
    Custom colored text
</div>

{{#if featuredImage}}
    <img src="{{featuredImage}}" alt="{{title}}" class="featured-image">
{{/if}}

{{#if tags}}
    <div class="tags">
        {{#each tags}}
            <span class="tag">{{this}}</span>
        {{/each}}
    </div>
{{/if}}

{{#if meta.description}}
    <meta name="description" content="{{meta.description}}">
{{/if}}
```

### Global Custom Variables (Theme Options)

Define custom theme options in your theme's `config.json` file:

```json
{
    "name": "My Theme",
    "version": "1.0.0",
    "options": {
        "customSetting": {
            "type": "text",
            "label": "Custom Setting",
            "default": "default value"
        },
        "showFeature": {
            "type": "boolean",
            "label": "Show Feature",
            "default": true
        },
        "accentColor": {
            "type": "color",
            "label": "Accent Color",
            "default": "#007cba"
        }
    }
}
```

These options appear in the admin panel and become available as variables:

```html
<p>{{customSetting}}</p>

{{#if showFeature}}
    <div class="feature">Feature content</div>
{{/if}}

<div style="color: {{accentColor}}">
    Accent colored text
</div>
```

### Custom Variable Types

#### Text Variables
```markdown
<!-- json {
    "textField": "Simple text value"
} -->
```

#### Boolean Variables
```markdown
<!-- json {
    "showElement": true,
    "hideElement": false
} -->
```

#### Numeric Variables
```markdown
<!-- json {
    "priority": 5,
    "rating": 4.5
} -->
```

#### Array Variables
```markdown
<!-- json {
    "categories": ["tech", "design", "business"],
    "numbers": [1, 2, 3, 4, 5]
} -->
```

#### Object Variables
```markdown
<!-- json {
    "settings": {
        "enabled": true,
        "count": 10,
        "name": "My Setting"
    }
} -->
```

### Accessing Nested Variables

For object variables, use dot notation:

```html
{{#if settings.enabled}}
    <div class="feature">
        Count: {{settings.count}}
        Name: {{settings.name}}
    </div>
{{/if}}
```

### Best Practices for Custom Variables

1. **Use Descriptive Names**: Choose clear, meaningful variable names
2. **Consistent Naming**: Use camelCase or snake_case consistently
3. **Default Values**: Provide sensible defaults for theme options
4. **Documentation**: Document custom variables in your theme's README
5. **Validation**: Use conditional checks before using variables

### Example: Blog Post with Custom Fields

```markdown
<!-- json {
    "title": "My Blog Post",
    "author": "John Doe",
    "publishDate": "2024-01-15",
    "category": "Technology",
    "tags": ["php", "cms", "tutorial"],
    "featured": true,
    "readingTime": 5,
    "meta": {
        "description": "Learn how to build a custom CMS",
        "keywords": "php, cms, development"
    }
} -->

Your blog post content here...
```

```html
<article class="blog-post {{#if featured}}featured{{/if}}">
    <header class="post-header">
        <h1>{{title}}</h1>
        <div class="post-meta">
            <span class="author">By {{author}}</span>
            <span class="date">{{publishDate}}</span>
            <span class="category">{{category}}</span>
            <span class="reading-time">{{readingTime}} min read</span>
        </div>
    </header>
    
    <div class="post-content">
        {{content}}
    </div>
    
    {{#if tags}}
        <footer class="post-footer">
            <div class="tags">
                {{#each tags}}
                    <span class="tag">{{this}}</span>
                {{/each}}
            </div>
        </footer>
    {{/if}}
</article>
```

## Conditional Logic

### Basic Conditionals

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
```

### If-Else Statements

```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="{{siteName}}">
{{else}}
    <h1>{{siteName}}</h1>
{{/if}}
```

### Complex Conditions

```html
{{#if title}}
    {{#if content}}
        <article>
            <h1>{{title}}</h1>
            <div>{{content}}</div>
        </article>
    {{/if}}
{{/if}}
```

### Unless (Inverse If)

```html
{{#unless children}}
    <p>No child pages found.</p>
{{/unless}}
```

## Loops and Iteration

### Basic Loops

```html
{{#each children}}
    <div class="child-page">
        <h3><a href="/{{url}}">{{title}}</a></h3>
        {{#if excerpt}}
            <p>{{excerpt}}</p>
        {{/if}}
    </div>
{{/each}}
```

### Menu Rendering

```html
<nav class="main-navigation">
    {{menu=main}}
</nav>

<nav class="footer-navigation">
    {{menu=footer}}
</nav>
```

### Array Loops

```html
{{#each themeOptions.socialLinks}}
    <a href="{{url}}" target="_blank" rel="noopener">
        {{#if icon}}
            <i class="{{icon}}"></i>
        {{/if}}
        {{name}}
    </a>
{{/each}}
```

### Loop Context

```html
{{#each children}}
    <div class="page-item {{#if @first}}first{{/if}} {{#if @last}}last{{/if}}">
        <h3>{{title}}</h3>
        <p>Index: {{@index}}</p>
    </div>
{{/each}}
```

## Modular Templates

### Including Modules

```html
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
</head>
<body>
    {{module=header.html}}
    <main>
        {{content}}
    </main>
    {{module=footer.html}}
</body>
</html>
```

### Module File Locations

Module files should be placed in your theme's `templates/` directory:

```
themes/
└── my-theme/
    └── templates/
        ├── page.html          # Main template
        ├── header.html        # Header module
        ├── footer.html        # Footer module
        ├── navigation.html    # Navigation module
        └── head.html          # Head module
```

### File Extensions

You can include modules with or without the `.html` extension:

```html
{{module=header.html}}  <!-- With extension -->
{{module=header}}       <!-- Without extension (auto-adds .html) -->
```

### Error Handling

If a module file is not found, the system will log an error and insert a comment:

```html
<!-- Module not found: missing-module.html -->
```

This allows your theme to continue working even if some modules are missing, making debugging easier.

### Module Variables

Modules have access to all template variables:

```html
<!-- header.html -->
<header class="site-header">
    <div class="container">
        {{#if themeOptions.logo}}
            <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
        {{else}}
            <h1 class="site-title">{{siteName}}</h1>
        {{/if}}
        
        {{#if siteDescription}}
            <p class="site-description">{{siteDescription}}</p>
        {{/if}}
        
        <nav class="main-navigation">
            {{menu=main}}
        </nav>
    </div>
</header>
```

## Sidebars and Widgets

### Sidebar Syntax

To render sidebars with widgets, use the `{{sidebar=sidebar-name}}` syntax:

```html
<aside class="sidebar">
    {{sidebar=main}}
</aside>
```

### Available Sidebars

| Sidebar Name | Description | Usage |
|--------------|-------------|-------|
| `main` | Main sidebar with widgets | `{{sidebar=main}}` |
| `footer` | Footer sidebar | `{{sidebar=footer}}` |
| `left` | Left sidebar | `{{sidebar=left}}` |
| `right` | Right sidebar | `{{sidebar=right}}` |

### Sidebar with Conditional Logic

```html
{{#if themeOptions.showSidebar}}
    <aside class="sidebar">
        {{sidebar=main}}
    </aside>
{{/if}}
```

### Multiple Sidebars

```html
<div class="content-layout">
    <aside class="sidebar-left">
        {{sidebar=left}}
    </aside>
    
    <main class="main-content">
        {{content}}
    </main>
    
    <aside class="sidebar-right">
        {{sidebar=right}}
    </aside>
</div>
```

### Widget Rendering

Sidebars automatically render widgets based on the sidebar configuration. Widgets are managed through the admin panel and can include:

- Navigation menus
- Recent posts
- Categories
- Search forms
- Custom HTML
- Social media links

### Sidebar Configuration

Sidebars are configured in the admin panel under Widgets. You can:

1. Add widgets to specific sidebars
2. Reorder widgets within sidebars
3. Configure widget settings
4. Enable/disable sidebars per page

## Template Examples

### Complete Page Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{#if title}}{{title}} - {{/if}}{{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
    {{#if themeOptions.customCSS}}
        <style>{{themeOptions.customCSS}}</style>
    {{/if}}
</head>
<body class="theme-{{themeOptions.fontFamily}} {{#if themeOptions.showSidebar}}with-sidebar{{/if}}">
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                {{#if themeOptions.logo}}
                    <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
                {{else}}
                    <h1 class="site-title">{{siteName}}</h1>
                {{/if}}
                
                {{#if siteDescription}}
                    <p class="site-description">{{siteDescription}}</p>
                {{/if}}
                
                <nav class="main-navigation">
                    {{menu=main}}
                </nav>
            </div>
        </div>
    </header>
    
    <main class="site-main">
        <div class="container">
            <div class="content-layout">
                <div class="main-content">
                    <article class="content">
                        {{#if title}}
                            <header class="content-header">
                                <h1 class="content-title">{{title}}</h1>
                                {{#if date}}
                                    <time class="content-date">{{date}}</time>
                                {{/if}}
                                {{#if author}}
                                    <span class="content-author">by {{author}}</span>
                                {{/if}}
                            </header>
                        {{/if}}
                        
                        {{#if excerpt}}
                            <div class="content-excerpt">
                                <p>{{excerpt}}</p>
                            </div>
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
                        
                        {{#if menu.sidebar}}
                            <div class="sidebar-widget">
                                <h3>Quick Links</h3>
                                <ul class="sidebar-menu">
                                    {{#each menu.sidebar}}
                                        <li class="sidebar-item">
                                            <a href="/{{url}}" class="sidebar-link">{{title}}</a>
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
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <p>&copy; {{currentYear}} {{siteName}}. All rights reserved.</p>
                </div>
                
                {{#if menu.footer}}
                    <nav class="footer-navigation">
                        <ul class="footer-menu">
                            {{#each menu.footer}}
                                <li class="footer-item">
                                    <a href="/{{url}}" class="footer-link">{{title}}</a>
                                </li>
                            {{/each}}
                        </ul>
                    </nav>
                {{/if}}
                
                {{#if themeOptions.socialLinks}}
                    <div class="social-links">
                        {{#each themeOptions.socialLinks}}
                            <a href="{{url}}" target="_blank" rel="noopener" class="social-link">
                                {{#if icon}}
                                    <i class="{{icon}}"></i>
                                {{/if}}
                                {{name}}
                            </a>
                        {{/each}}
                    </div>
                {{/if}}
            </div>
        </div>
    </footer>
</body>
</html>
```

### Home Template

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

### Blog Template

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
            <div class="blog-header">
                <h1 class="blog-title">{{#if title}}{{title}}{{else}}Blog{{/if}}</h1>
                {{#if excerpt}}
                    <p class="blog-description">{{excerpt}}</p>
                {{/if}}
            </div>
            
            <div class="blog-content">
                {{content}}
            </div>
            
            {{#if children}}
                <div class="blog-posts">
                    {{#each children}}
                        <article class="blog-post">
                            <header class="post-header">
                                <h2 class="post-title">
                                    <a href="/{{url}}" class="post-link">{{title}}</a>
                                </h2>
                                {{#if date}}
                                    <time class="post-date">{{date}}</time>
                                {{/if}}
                                {{#if author}}
                                    <span class="post-author">by {{author}}</span>
                                {{/if}}
                            </header>
                            
                            {{#if excerpt}}
                                <div class="post-excerpt">
                                    <p>{{excerpt}}</p>
                                </div>
                            {{/if}}
                            
                            <footer class="post-footer">
                                <a href="/{{url}}" class="read-more">Read More</a>
                            </footer>
                        </article>
                    {{/each}}
                </div>
            {{/if}}
        </div>
    </main>
    
    {{module=footer.html}}
</body>
</html>
```

### 404 Error Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
    <title>404 - Page Not Found - {{siteName}}</title>
</head>
<body>
    {{module=header.html}}
    
    <main class="site-main">
        <div class="container">
            <div class="error-page">
                <h1 class="error-title">404 - Page Not Found</h1>
                <p class="error-message">The page you are looking for does not exist.</p>
                <div class="error-actions">
                    <a href="/" class="btn btn-primary">Return to Homepage</a>
                </div>
            </div>
        </div>
    </main>
    
    {{module=footer.html}}
</body>
</html>
```

## Advanced Features

### Custom CSS Classes

```html
<div class="page {{#if parent}}has-parent{{/if}} {{#if children}}has-children{{/if}}">
    <!-- Content -->
</div>
```

### Dynamic Attributes

```html
<a href="{{url}}" 
   {{#if target}}target="{{target}}"{{/if}}
   {{#if rel}}rel="{{rel}}"{{/if}}>
    {{title}}
</a>
```

### Nested Conditionals

```html
{{#if themeOptions.showSidebar}}
    {{#if menu.sidebar}}
        <aside class="sidebar">
            <nav class="sidebar-navigation">
                <ul class="sidebar-menu">
                    {{#each menu.sidebar}}
                        <li class="sidebar-item">
                            <a href="/{{url}}" class="sidebar-link">{{title}}</a>
                        </li>
                    {{/each}}
                </ul>
            </nav>
        </aside>
    {{/if}}
{{/if}}
```

### Helper Functions

Some themes may include custom helper functions. Check your theme's documentation for available helpers.

## Best Practices

1. **Use Semantic HTML**: Always use proper HTML5 semantic elements
2. **Content Rendering**: Use `{{content}}` for content (HTML is automatically rendered)
3. **Check for Existence**: Always check if variables exist before using them
4. **Modular Design**: Break templates into reusable modules
5. **Responsive Design**: Ensure templates work on all screen sizes
6. **Accessibility**: Follow WCAG guidelines for accessibility
7. **Performance**: Minimize template complexity for better performance

## Troubleshooting

### Common Issues

1. **Variables Not Showing**: Check if the variable name is correct and exists
2. **Conditionals Not Working**: Ensure proper Handlebars syntax (`{{#if}}`, `{{/if}}`)
3. **Loops Not Iterating**: Verify array structure and use `{{#each}}` correctly
4. **Content Not Rendering**: Use `{{content}}` for content (HTML is automatically processed)

### Debugging

Add debug output to check variable values:

```html
<!-- Debug output -->
<div style="display: none;">
    <p>Title: {{title}}</p>
    <p>Children count: {{children.length}}</p>
    <p>Theme options: {{themeOptions}}</p>
</div>
```

This reference covers all the essential template features in FearlessCMS. For more advanced usage, refer to the Handlebars documentation and your theme's specific documentation. 