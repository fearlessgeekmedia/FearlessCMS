# FearlessCMS Template Reference

This document provides a comprehensive reference for the FearlessCMS template system, including all available variables, syntax, and examples.

## Template Syntax Overview

FearlessCMS uses a simple template system with double curly braces `{{}}` for variables and special tags for conditionals and loops.

## Variables

### Global Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{siteName}}` | Site name from config | "My Awesome Site" |
| `{{siteDescription}}` | Site description/tagline | "A great website" |
| `{{theme}}` | Current theme name | "nightfall" |
| `{{currentYear}}` | Current year | "2024" |
| `{{baseUrl}}` | Base URL of the site | "https://example.com" |
| `{{currentUrl}}` | Current page URL | "/about" |

### Page Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{title}}` | Page title | "About Us" |
| `{{content}}` | Page content (HTML) | "<p>Page content...</p>" |
| `{{url}}` | Current page URL | "about" |
| `{{parent}}` | Parent page object | `{"title": "Home", "url": "home"}` |
| `{{children}}` | Array of child pages | `[{"title": "Child 1", "url": "child1"}]` |
| `{{excerpt}}` | Page excerpt (first paragraph) | "This is the excerpt..." |
| `{{date}}` | Page creation date | "2024-01-15" |
| `{{author}}` | Page author | "John Doe" |

### Menu Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `{{menu.main}}` | Main menu items | Array of menu objects |
| `{{menu.footer}}` | Footer menu items | Array of menu objects |
| `{{menu.sidebar}}` | Sidebar menu items | Array of menu objects |

### Theme Options

| Variable | Description | Example |
|----------|-------------|---------|
| `{{themeOptions.key}}` | Custom theme option | Value depends on theme |

## Conditional Statements

### Basic If/Else

```html
{{#if condition}}
    <!-- Content when condition is true -->
{{else}}
    <!-- Content when condition is false -->
{{/if}}
```

### Examples

```html
<!-- Check if title exists -->
{{#if title}}
    <h1>{{title}}</h1>
{{/if}}

<!-- Check if children exist -->
{{#if children}}
    <ul>
        {{#each children}}
            <li><a href="/{{url}}">{{title}}</a></li>
        {{/each}}
    </ul>
{{/if}}

<!-- Check theme option -->
{{#if themeOptions.showSidebar}}
    <aside class="sidebar">
        <!-- Sidebar content -->
    </aside>
{{/if}}

<!-- Multiple conditions -->
{{#if title && content}}
    <article>
        <h1>{{title}}</h1>
        {{content}}
    </article>
{{else}}
    <p>No content available</p>
{{/if}}
```

## Loops

### Foreach Loop

```html
{{#each array}}
    <!-- Content for each item -->
{{/each}}
```

### Examples

```html
<!-- Loop through menu items -->
<nav>
    <ul>
        {{#each menu.main}}
            <li><a href="/{{url}}">{{title}}</a></li>
        {{/each}}
    </ul>
</nav>

<!-- Loop through children -->
{{#if children}}
    <div class="child-pages">
        {{#each children}}
            <div class="child-page">
                <h3><a href="/{{url}}">{{title}}</a></h3>
                {{#if excerpt}}
                    <p>{{excerpt}}</p>
                {{/if}}
            </div>
        {{/each}}
    </div>
{{/if}}

<!-- Loop through theme options -->
{{#if themeOptions.socialLinks}}
    <div class="social-links">
        {{#each themeOptions.socialLinks}}
            <a href="{{url}}" target="{{target}}" rel="{{rel}}">
                {{#if icon}}
                    <i class="{{icon}}"></i>
                {{/if}}
                {{name}}
            </a>
        {{/each}}
    </div>
{{/if}}
```

## Template Functions

### Include Function

Include other template files:

```html
{{include "partials/header.html"}}
{{include "partials/footer.html"}}
```

### Date Formatting

Format dates using PHP's date format:

```html
{{date "Y-m-d"}}  <!-- 2024-01-15 -->
{{date "F j, Y"}} <!-- January 15, 2024 -->
{{date "M j"}}    <!-- Jan 15 -->
```

## Modular Templates

FearlessCMS supports modular templates, allowing you to break down templates into reusable components. This makes themes more maintainable and reduces code duplication.

### Module Include Syntax

Use the `{{module=filename.html}}` syntax to include other template files:

```html
{{module=header.html}}
{{module=footer.html}}
{{module=navigation.html}}
```

### Module Features

#### Variable Access
Modules have access to all template variables:

```html
<!-- header.html -->
<header>
    <h1>{{siteName}}</h1>
    {{#if siteDescription}}
    <p>{{siteDescription}}</p>
    {{/if}}
</header>
```

#### Conditional Logic
Modules support all template conditionals:

```html
<!-- hero-banner.html -->
{{#if heroBanner}}
<div class="hero-banner">
    <img src="{{heroBanner}}" alt="{{title}}">
</div>
{{/if}}
```

#### Loops
Modules support foreach loops:

```html
<!-- navigation.html -->
<nav>
    <ul>
    {{#each menu.main}}
        <li><a href="/{{url}}">{{title}}</a></li>
    {{/each}}
    </ul>
</nav>
```

#### Nested Modules
Modules can include other modules:

```html
<!-- header.html -->
<header>
    <div class="logo">{{siteName}}</div>
    {{module=navigation.html}}
</header>
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

### Example: Modular Template Structure

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

**Head module (head.html):**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{title}} - {{siteName}}</title>
<link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
```

**Header module (header.html):**
```html
<header>
    <div class="logo">{{siteName}}</div>
    <nav class="main-menu">
        {{menu=main}}
    </nav>
</header>
```

**Footer module (footer.html):**
```html
<footer>
    &copy; {{currentYear}} {{siteName}}
</footer>
```

### Best Practices for Modular Templates

1. **Keep modules focused** - Each module should have a single responsibility
2. **Use descriptive names** - Name modules clearly (e.g., `site-header.html` not `h.html`)
3. **Plan your structure** - Think about what parts are reused across pages
4. **Avoid deep nesting** - Don't create circular includes or deeply nested structures
5. **Test thoroughly** - Ensure all variables and conditionals work in modules

### Common Module Types

- `head.html` - HTML head section (meta tags, CSS, JS)
- `header.html` - Site header (logo, navigation)
- `footer.html` - Site footer (copyright, links)
- `navigation.html` - Navigation menus
- `sidebar.html` - Sidebar content and layout
- `hero-banner.html` - Hero banner sections
- `content-layout.html` - Content area layouts

For more detailed information about modular templates, see the [Modular Templates Guide](modular-templates).

## Template Examples

### Complete Home Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{siteName}}</title>
    <meta name="description" content="{{siteDescription}}">
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
            
            {{#if siteDescription}}
                <p class="tagline">{{siteDescription}}</p>
            {{/if}}
            
            {{#if menu.main}}
                <nav>
                    <ul>
                        {{#each menu.main}}
                            <li><a href="/{{url}}">{{title}}</a></li>
                        {{/each}}
                    </ul>
                </nav>
            {{/if}}
        </div>
    </header>

    <main>
        <div class="container">
            <article>
                {{content}}
            </article>
            
            {{#if children}}
                <section class="child-pages">
                    <h2>Related Pages</h2>
                    <div class="grid">
                        {{#each children}}
                            <div class="card">
                                <h3><a href="/{{url}}">{{title}}</a></h3>
                                {{#if excerpt}}
                                    <p>{{excerpt}}</p>
                                {{/if}}
                            </div>
                        {{/each}}
                    </div>
                </section>
            {{/if}}
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}. All rights reserved.</p>
            
            {{#if themeOptions.socialLinks}}
                <div class="social-links">
                    {{#each themeOptions.socialLinks}}
                        <a href="{{url}}" target="{{target}}" rel="{{rel}}">
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

### Blog Template

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
            <div class="blog-layout">
                <div class="blog-content">
                    {{#if children}}
                        {{#each children}}
                            <article class="blog-post">
                                <header>
                                    <h2><a href="/{{url}}">{{title}}</a></h2>
                                    {{#if date}}
                                        <time datetime="{{date}}">{{date "F j, Y"}}</time>
                                    {{/if}}
                                    {{#if author}}
                                        <span class="author">by {{author}}</span>
                                    {{/if}}
                                </header>
                                
                                {{#if excerpt}}
                                    <div class="excerpt">
                                        {{excerpt}}
                                    </div>
                                {{/if}}
                                
                                <a href="/{{url}}" class="read-more">Read More</a>
                            </article>
                        {{/each}}
                    {{else}}
                        <p>No blog posts found.</p>
                    {{/if}}
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

    <footer>
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}</p>
        </div>
    </footer>
</body>
</html>
```

### 404 Error Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - {{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <div class="error-page">
        <div class="container">
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>The page you're looking for doesn't exist.</p>
            
            {{#if menu.main}}
                <p>Try one of these pages:</p>
                <ul>
                    {{#each menu.main}}
                        <li><a href="/{{url}}">{{title}}</a></li>
                    {{/each}}
                </ul>
            {{/if}}
            
            <a href="/" class="btn">Go Home</a>
        </div>
    </div>
</body>
</html>
```

## Advanced Techniques

### Nested Conditionals

```html
{{#if themeOptions.showSidebar}}
    <aside class="sidebar">
        {{#if menu.sidebar}}
            <nav>
                {{#each menu.sidebar}}
                    <div class="menu-item">
                        <a href="/{{url}}">{{title}}</a>
                        {{#if children}}
                            <ul>
                                {{#each children}}
                                    <li><a href="/{{url}}">{{title}}</a></li>
                                {{/each}}
                            </ul>
                        {{/if}}
                    </div>
                {{/each}}
            </nav>
        {{/if}}
    </aside>
{{/if}}
```

### Dynamic Classes

```html
<div class="page {{#if parent}}has-parent{{/if}} {{#if children}}has-children{{/if}}">
    <!-- Content -->
</div>
```

### Conditional Attributes

```html
<a href="/{{url}}" 
   {{#if target}}target="{{target}}"{{/if}}
   {{#if rel}}rel="{{rel}}"{{/if}}>
    {{title}}
</a>
```

## Best Practices

1. **Always check if variables exist** before using them
2. **Use semantic HTML** elements
3. **Keep templates DRY** - reuse common elements
4. **Test with different content** scenarios
5. **Use meaningful variable names** in theme options
6. **Include proper meta tags** for SEO
7. **Make templates accessible** with proper ARIA labels

## Troubleshooting

### Common Issues

1. **Variable not showing**: Check if the variable exists in the data
2. **Conditional not working**: Verify the condition syntax
3. **Loop not iterating**: Ensure the array is not empty
4. **Template not loading**: Check file paths and names

### Debug Tips

- Use `{{debug}}` to output all available variables
- Check the browser console for JavaScript errors
- Verify template file permissions
- Test with simple content first 