# Modular Templates in FearlessCMS

FearlessCMS now supports modular templates, allowing you to break down your templates into reusable components. This makes themes more maintainable and reduces code duplication.

## How It Works

Use the `{{module=filename.html}}` syntax to include other template files within your templates. The included modules have access to all the same variables and functionality as the main template.

## Basic Syntax

```html
{{module=header.html}}
{{module=footer.html}}
{{module=navigation.html}}
```

## Example: Breaking Down a Template

Instead of having everything in one large template file:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}} - {{siteName}}</title>
    <link rel="stylesheet" href="/themes/my-theme/css/style.css">
</head>
<body>
    <header>
        <div class="logo">{{siteName}}</div>
        <nav class="main-menu">
            {{menu=main}}
        </nav>
    </header>
    <main>
        <h1>{{title}}</h1>
        {{content}}
    </main>
    <footer>
        &copy; {{currentYear}} {{siteName}}
    </footer>
</body>
</html>
```

You can break it into modular components:

**page.html:**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    {{module=head.html}}
</head>
<body>
    {{module=header.html}}
    <main>
        <h1>{{title}}</h1>
        {{content}}
    </main>
    {{module=footer.html}}
</body>
</html>
```

**head.html.mod:**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{title}} - {{siteName}}</title>
<link rel="stylesheet" href="/themes/my-theme/css/style.css">
```

**header.html.mod:**
```html
<header>
    <div class="logo">{{siteName}}</div>
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

## Module File Locations

Module files should be placed in your theme's `templates/` directory and use the `.html.mod` extension:

```
themes/
└── my-theme/
    └── templates/
        ├── page.html              # Main template (page template)
        ├── header.html.mod        # Header module
        ├── footer.html.mod        # Footer module
        ├── navigation.html.mod    # Navigation module
        └── head.html.mod          # Head module
```

**Important**: Use the `.html.mod` extension for all module files to prevent them from appearing as page template options in the admin interface.

## Module Features

### Variable Access
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

### Conditional Logic
Modules support all template conditionals:

```html
<!-- hero-banner.html -->
{{#if heroBanner}}
<div class="hero-banner">
    <img src="{{heroBanner}}" alt="{{title}}">
</div>
{{/if}}
```

### Loops
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

### Nested Modules
Modules can include other modules:

```html
<!-- header.html -->
<header>
    <div class="logo">{{siteName}}</div>
    {{module=navigation.html}}
</header>
```

## File Extensions

Module files use the `.html.mod` extension, but you reference them without the `.mod` part:

```html
{{module=header.html}}  <!-- References header.html.mod -->
{{module=header}}       <!-- References header.html.mod (auto-adds .html) -->
```

The system automatically looks for `.html.mod` files first, then falls back to `.html` files for backward compatibility.

## Error Handling

If a module file is not found, the system will log an error and insert a comment:

```html
<!-- Module not found: missing-module.html -->
```

## Best Practices

### 1. Keep Modules Focused
Each module should have a single responsibility:

- `header.html.mod` - Site header
- `footer.html.mod` - Site footer  
- `navigation.html.mod` - Navigation menus
- `sidebar.html.mod` - Sidebar content
- `hero-banner.html.mod` - Hero banner section

### 2. Use Descriptive Names
Name your modules clearly:

```html
{{module=site-header.html}}      <!-- Good -->
{{module=main-navigation.html}}  <!-- Good -->
{{module=h.html}}                <!-- Avoid -->
```

### 3. Plan Your Structure
Think about what parts of your templates are reused across multiple pages:

- Head section (meta tags, CSS, JS)
- Header (logo, navigation)
- Footer (copyright, links)
- Sidebar (widgets, navigation)
- Hero sections
- Content layouts

### 4. Avoid Deep Nesting
While modules can include other modules, avoid creating deeply nested structures that are hard to debug.

## Example Theme Structure

Here's an example of how you might structure a modular theme:

```
themes/
└── my-theme/
    ├── templates/
    │   ├── page.html           # Main page template
    │   ├── home.html           # Home page template
    │   ├── blog.html           # Blog template
    │   ├── 404.html            # 404 error page
    │   ├── head.html           # HTML head section
    │   ├── header.html         # Site header
    │   ├── footer.html         # Site footer
    │   ├── navigation.html     # Main navigation
    │   ├── sidebar.html        # Sidebar content
    │   ├── hero-banner.html    # Hero banner
    │   ├── blog-header.html    # Blog-specific header
    │   └── blog-footer.html    # Blog-specific footer
    ├── css/
    │   └── style.css
    └── config.json
```

## Migration from Monolithic Templates

To migrate existing templates to the modular system:

1. **Identify reusable sections** in your current templates
2. **Extract common elements** into separate module files
3. **Replace inline sections** with `{{module=filename.html}}` includes
4. **Test thoroughly** to ensure all variables and conditionals work correctly

## Troubleshooting

### Module Not Found
- Check that the module file exists in the theme's `templates/` directory
- Verify the filename and extension are correct
- Check file permissions

### Variables Not Working
- Ensure the module has access to the required variables
- Check that variable names match exactly (case-sensitive)
- Verify the main template is passing the correct data

### Infinite Loops
- Avoid circular module includes (A includes B, B includes A)
- Use clear naming conventions to prevent confusion

The modular template system makes FearlessCMS themes more maintainable and flexible while preserving all existing template functionality. 