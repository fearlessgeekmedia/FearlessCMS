# Theme Development Workflow

This guide outlines the recommended workflow for developing themes in FearlessCMS, from initial setup to final deployment.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Development Environment](#development-environment)
3. [Theme Structure](#theme-structure)
4. [Development Process](#development-process)
5. [Testing and Debugging](#testing-and-debugging)
6. [Optimization](#optimization)
7. [Deployment](#deployment)
8. [Maintenance](#maintenance)

## Getting Started

### Prerequisites

Before developing a theme, ensure you have:

- A working FearlessCMS installation
- Basic knowledge of HTML, CSS, and JavaScript
- Understanding of Handlebars template syntax
- A code editor (VS Code, Sublime Text, etc.)
- Browser developer tools

### Theme Requirements

Every FearlessCMS theme must include:

- `theme.json` - Theme metadata and configuration
- `templates/` directory with required template files
- `assets/style.css` - Main stylesheet
- Optional `config.json` for theme options

## Development Environment

### Local Development Setup

1. **Create Theme Directory**
   ```bash
   mkdir -p themes/your-theme-name/templates
   mkdir -p themes/your-theme-name/assets
   ```

2. **Initialize Theme Files**
   ```bash
   touch themes/your-theme-name/theme.json
   touch themes/your-theme-name/config.json
   touch themes/your-theme-name/templates/home.html
   touch themes/your-theme-name/templates/page.html
   touch themes/your-theme-name/templates/404.html
   touch themes/your-theme-name/assets/style.css
   ```

3. **Configure Development Server**
   ```bash
   # Start local development server
   php -S localhost:8000
   ```

### Recommended Tools

- **Code Editor**: VS Code with extensions for HTML, CSS, and Handlebars
- **Browser Tools**: Chrome DevTools or Firefox Developer Tools
- **Version Control**: Git for tracking changes
- **Image Optimization**: Tools like ImageOptim or TinyPNG
- **CSS Preprocessors**: Optional SASS/SCSS support

## Theme Structure

### Basic Structure

```
themes/your-theme-name/
├── theme.json              # Theme metadata
├── config.json             # Theme options (optional)
├── README.md               # Theme documentation
├── templates/
│   ├── home.html          # Homepage template
│   ├── page.html          # Page template
│   ├── blog.html          # Blog template (optional)
│   ├── 404.html           # Error page template
│   ├── head.html          # Head module (optional)
│   ├── header.html        # Header module (optional)
│   ├── footer.html        # Footer module (optional)
│   └── custom_js.html     # Custom JavaScript (optional)
├── assets/
│   ├── style.css          # Main stylesheet
│   ├── images/            # Theme images
│   └── js/                # Theme JavaScript
└── preview.png            # Theme preview image
```

### Required Files

#### theme.json
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
        "404": "404.html"
    }
}
```

#### Basic Template (home.html)
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{#if title}}{{title}} - {{/if}}{{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">{{siteName}}</h1>
            {{#if siteDescription}}
                <p class="site-description">{{siteDescription}}</p>
            {{/if}}
        </div>
    </header>
    
    <main class="site-main">
        <div class="container">
            <article class="content">
                {{content}}
            </article>
        </div>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}</p>
        </div>
    </footer>
</body>
</html>
```

## Development Process

### 1. Planning Phase

- **Define Requirements**: What type of site is this theme for?
- **Research**: Look at similar themes and websites for inspiration
- **Wireframing**: Create basic layouts and structure
- **Design System**: Define colors, typography, and spacing

### 2. Initial Setup

Create the basic theme structure:

```bash
# Create theme directory
mkdir -p themes/my-theme/{templates,assets}

# Create basic files
touch themes/my-theme/theme.json
touch themes/my-theme/templates/{home,page,404}.html
touch themes/my-theme/assets/style.css
```

### 3. Template Development

Start with a minimal template and build up:

#### Step 1: Basic Structure
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
        <h1>{{siteName}}</h1>
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

#### Step 2: Add Navigation
```html
<header class="site-header">
    <div class="container">
        <h1 class="site-title">{{siteName}}</h1>
        
        {{#if menu.main}}
            <nav class="main-navigation">
                <ul class="nav-menu">
                    {{menu=main}}
                </ul>
            </nav>
        {{/if}}
    </div>
</header>
```

#### Step 3: Add Theme Options
```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
{{else}}
    <h1 class="site-title">{{siteName}}</h1>
{{/if}}
```

### 4. CSS Development

#### Step 1: Reset and Base Styles
```css
/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Base styles */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
```

#### Step 2: Layout Components
```css
/* Header */
.site-header {
    background: #f8f9fa;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

/* Navigation */
.main-navigation ul {
    list-style: none;
    display: flex;
    gap: 2rem;
}

.main-navigation a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

/* Main content */
.site-main {
    padding: 2rem 0;
    min-height: 60vh;
}

/* Footer */
.site-footer {
    background: #343a40;
    color: white;
    padding: 2rem 0;
    margin-top: auto;
}
```

#### Step 3: Responsive Design
```css
/* Mobile-first approach */
@media (max-width: 768px) {
    .main-navigation ul {
        flex-direction: column;
        gap: 1rem;
    }
    
    .container {
        padding: 0 15px;
    }
}
```

### 5. Theme Options Integration

#### Define Options (config.json)
```json
{
    "options": {
        "primaryColor": {
            "type": "color",
            "label": "Primary Color",
            "default": "#007cba"
        },
        "showSidebar": {
            "type": "boolean",
            "label": "Show Sidebar",
            "default": true
        },
        "logo": {
            "type": "image",
            "label": "Logo"
        }
    }
}
```

#### Use in Templates
```html
<style>
:root {
    --primary-color: {{themeOptions.primaryColor}};
}
</style>

<div class="content-layout {{#if themeOptions.showSidebar}}with-sidebar{{/if}}">
    <div class="main-content">
        {{content}}
    </div>
    
    {{#if themeOptions.showSidebar}}
        <aside class="sidebar">
            <!-- Sidebar content -->
        </aside>
    {{/if}}
</div>
```

## Testing and Debugging

### 1. Template Testing

Test your templates with different content scenarios:

- **Empty content**: Pages with no content
- **Long content**: Pages with lots of text
- **Short content**: Pages with minimal content
- **Special characters**: Content with HTML, quotes, etc.
- **Missing variables**: Test fallbacks

### 2. Browser Testing

Test across different browsers and devices:

- Chrome, Firefox, Safari, Edge
- Mobile devices (iOS, Android)
- Different screen sizes
- High DPI displays

### 3. Debugging Techniques

#### Template Debugging
```html
<!-- Debug output -->
<div style="display: none;">
    <p>Title: {{title}}</p>
    <p>Children: {{#if children}}Yes{{else}}No{{/if}}</p>
    <p>Theme options: {{themeOptions}}</p>
</div>
```

#### CSS Debugging
```css
/* Debug borders */
* {
    border: 1px solid red;
}

/* Debug specific elements */
.debug {
    outline: 2px solid blue;
    background: rgba(0,0,255,0.1);
}
```

#### JavaScript Debugging
```javascript
// Console logging
console.log('Theme loaded:', {
    theme: '{{theme}}',
    options: {{themeOptions}}
});

// Error handling
try {
    // Your code
} catch (error) {
    console.error('Theme error:', error);
}
```

### 4. Performance Testing

- **Page load speed**: Use browser dev tools
- **Image optimization**: Compress images
- **CSS optimization**: Minify CSS
- **JavaScript optimization**: Minify JS

## Optimization

### 1. CSS Optimization

#### Use CSS Custom Properties
```css
:root {
    --primary-color: {{themeOptions.primaryColor}};
    --font-family: {{themeOptions.fontFamily}};
    --spacing: 1rem;
}

.button {
    background: var(--primary-color);
    padding: var(--spacing);
    font-family: var(--font-family);
}
```

#### Efficient Selectors
```css
/* Good */
.nav-item { }

/* Bad */
body header nav ul li { }
```

#### Mobile-First Approach
```css
/* Base styles (mobile) */
.content {
    padding: 1rem;
}

/* Tablet and up */
@media (min-width: 768px) {
    .content {
        padding: 2rem;
    }
}

/* Desktop and up */
@media (min-width: 1024px) {
    .content {
        padding: 3rem;
    }
}
```

### 2. Template Optimization

#### Minimize Conditionals
```html
<!-- Good -->
{{#if menu.main}}
    <nav class="main-navigation">
        {{menu=main}}
    </nav>
{{/if}}

<!-- Bad -->
{{#if menu.main}}
    {{#if menu.main.length}}
        {{#if menu.main.length > 0}}
            <nav class="main-navigation">
                {{menu=main}}
            </nav>
        {{/if}}
    {{/if}}
{{/if}}
```

#### Use Modular Templates
```html
<!-- Break into modules -->
{{module=header.html}}
{{module=content.html}}
{{module=footer.html}}
```

### 3. Image Optimization

- Use appropriate formats (WebP, AVIF for modern browsers)
- Compress images without quality loss
- Use responsive images
- Implement lazy loading

```html
<picture>
    <source srcset="{{image.webp}}" type="image/webp">
    <img src="{{image.jpg}}" alt="{{image.alt}}" loading="lazy">
</picture>
```

## Deployment

### 1. Pre-Deployment Checklist

- [ ] All templates work correctly
- [ ] CSS is optimized and responsive
- [ ] Images are compressed
- [ ] Theme options are tested
- [ ] Cross-browser compatibility verified
- [ ] Performance is acceptable
- [ ] Documentation is complete

### 2. File Organization

```
themes/your-theme/
├── theme.json
├── config.json
├── README.md
├── preview.png
├── templates/
│   ├── home.html
│   ├── page.html
│   ├── 404.html
│   └── modules/
├── assets/
│   ├── style.css
│   ├── images/
│   └── js/
└── docs/
    ├── installation.md
    ├── customization.md
    └── troubleshooting.md
```

### 3. Documentation

Create comprehensive documentation:

#### README.md
```markdown
# Theme Name

Brief description of the theme.

## Features

- Responsive design
- Customizable colors
- Multiple layout options
- SEO optimized

## Installation

1. Upload theme to `themes/` directory
2. Activate in admin panel
3. Configure theme options

## Customization

Describe how to customize the theme.

## Support

Contact information and support details.
```

### 4. Version Control

Use Git for version control:

```bash
git init
git add .
git commit -m "Initial theme release"
git tag v1.0.0
```

## Maintenance

### 1. Regular Updates

- Monitor for security updates
- Update dependencies
- Test with new CMS versions
- Fix reported issues

### 2. User Feedback

- Collect user feedback
- Address common issues
- Improve documentation
- Add requested features

### 3. Performance Monitoring

- Monitor page load times
- Track user experience
- Optimize based on usage data
- Update for new web standards

### 4. Documentation Updates

- Keep documentation current
- Add troubleshooting guides
- Update installation instructions
- Document new features

## Best Practices Summary

1. **Start Simple**: Begin with basic templates and add complexity gradually
2. **Test Thoroughly**: Test with various content types and devices
3. **Optimize Performance**: Minimize CSS/JS and optimize images
4. **Use Standards**: Follow HTML5, CSS3, and accessibility guidelines
5. **Document Everything**: Create clear, comprehensive documentation
6. **Version Control**: Use Git for tracking changes
7. **User Feedback**: Listen to users and improve based on feedback
8. **Regular Updates**: Keep themes current with web standards

This workflow ensures you create high-quality, maintainable themes that provide a great user experience and are easy to support and update. 