# Theme Development Workflow

This guide provides a step-by-step workflow for developing themes in FearlessCMS, from initial setup to final deployment.

## Prerequisites

Before starting theme development, ensure you have:

- A working FearlessCMS installation
- Basic knowledge of HTML, CSS, and JavaScript
- A code editor (VS Code, Sublime Text, etc.)
- Basic understanding of responsive design principles

## Development Environment Setup

### 1. Create Theme Directory

```bash
# Navigate to your FearlessCMS themes directory
cd themes/

# Create your theme directory
mkdir my-theme
cd my-theme

# Create required subdirectories
mkdir templates
mkdir assets
mkdir assets/images
mkdir assets/js
```

### 2. Initialize Theme Files

Create the basic theme structure:

```bash
# Create theme configuration
touch theme.json
touch config.json
touch README.md

# Create template files
touch templates/home.html
touch templates/page.html
touch templates/blog.html
touch templates/404.html

# Create assets
touch assets/style.css
touch assets/theme.js
```

## Step-by-Step Development Process

### Step 1: Define Theme Configuration

Start by creating your `theme.json`:

```json
{
    "name": "My Awesome Theme",
    "description": "A modern, responsive theme for blogs and websites",
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

### Step 2: Create Basic Templates

Start with a simple `templates/page.html`:

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
        <h1>{{title}}</h1>
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

### Step 3: Add Basic Styling

Create `assets/style.css`:

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
header, main, footer {
    padding: 2rem;
}

/* Typography */
h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    h1 {
        font-size: 2rem;
    }
}
```

### Step 4: Test Your Theme

1. **Activate the theme** in the admin panel
2. **Create a test page** with some content
3. **View the page** in your browser
4. **Check for errors** in browser console

### Step 5: Iterate and Improve

Based on your testing, improve the theme:

1. **Fix layout issues**
2. **Improve typography**
3. **Add responsive design**
4. **Enhance visual appeal**

### Step 6: Add Theme Options

Create `config.json` for customizable options:

```json
{
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo Image",
            "description": "Upload your site logo"
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
        }
    }
}
```

### Step 7: Implement Advanced Features

Add more sophisticated features:

1. **Navigation menus**
2. **Blog functionality**
3. **Search capability**
4. **Social media integration**
5. **Custom JavaScript**

## Development Best Practices

### 1. Start Simple

Begin with a basic, functional theme and add features gradually:

```html
<!-- Start with this simple structure -->
<!DOCTYPE html>
<html>
<head>
    <title>{{title}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body>
    <header>{{siteName}}</header>
    <main>{{content}}</main>
    <footer>&copy; {{currentYear}}</footer>
</body>
</html>
```

### 2. Use Semantic HTML

Always use proper HTML5 semantic elements:

```html
<header>
    <nav>
        <ul>
            {{foreach menu.main}}
                <li><a href="/{{url}}">{{title}}</a></li>
            {{endforeach}}
        </ul>
    </nav>
</header>

<main>
    <article>
        <header>
            <h1>{{title}}</h1>
        </header>
        <section>
            {{content}}
        </section>
    </article>
</main>

<footer>
    <p>&copy; {{currentYear}} {{siteName}}</p>
</footer>
```

### 3. Mobile-First Design

Start with mobile styles and add desktop enhancements:

```css
/* Mobile first */
.container {
    padding: 1rem;
}

/* Tablet and up */
@media (min-width: 768px) {
    .container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .container {
        padding: 3rem;
    }
}
```

### 4. Use CSS Custom Properties

Make your theme easily customizable:

```css
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --text-color: #333;
    --bg-color: #fff;
    --border-color: #e9ecef;
}

.button {
    background-color: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
}

.card {
    background-color: var(--bg-color);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
}
```

### 5. Progressive Enhancement

Add JavaScript features progressively:

```html
<!-- Basic functionality works without JS -->
<nav class="nav">
    <ul>
        {{foreach menu.main}}
            <li><a href="/{{url}}">{{title}}</a></li>
        {{endforeach}}
    </ul>
</nav>

<!-- Enhanced with JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add mobile menu toggle
    const nav = document.querySelector('.nav');
    const toggle = document.createElement('button');
    toggle.textContent = 'Menu';
    toggle.classList.add('nav-toggle');
    
    toggle.addEventListener('click', function() {
        nav.classList.toggle('nav-open');
    });
    
    nav.parentNode.insertBefore(toggle, nav);
});
</script>
```

## Testing Your Theme

### 1. Content Testing

Test with different types of content:

- **Short pages** (just a title and paragraph)
- **Long pages** (lots of content)
- **Pages with images**
- **Pages with lists and tables**
- **Blog posts with different formats**

### 2. Browser Testing

Test in multiple browsers:

- Chrome/Chromium
- Firefox
- Safari
- Edge

### 3. Device Testing

Test on different devices:

- **Mobile phones** (portrait and landscape)
- **Tablets** (portrait and landscape)
- **Desktop computers** (different screen sizes)

### 4. Performance Testing

Check theme performance:

- **Page load speed**
- **CSS file size**
- **JavaScript execution time**
- **Image optimization**

## Debugging Tips

### 1. Use Browser Developer Tools

- **Inspect elements** to see how CSS is applied
- **Check console** for JavaScript errors
- **Use network tab** to see if files are loading
- **Test responsive design** with device simulation

### 2. Add Debug Output

Temporarily add debug information to your templates:

```html
<!-- Debug information -->
<div style="background: #f0f0f0; padding: 1rem; margin: 1rem 0; font-family: monospace;">
    <strong>Debug Info:</strong><br>
    Title: {{title}}<br>
    URL: {{url}}<br>
    Theme: {{theme}}<br>
    Children: {{if children}}Yes{{else}}No{{endif}}
</div>
```

### 3. Check File Permissions

Ensure your theme files have proper permissions:

```bash
chmod 644 assets/style.css
chmod 644 templates/*.html
chmod 644 theme.json
chmod 644 config.json
```

## Version Control

### 1. Initialize Git Repository

```bash
cd themes/my-theme
git init
```

### 2. Create .gitignore

```bash
# .gitignore
.DS_Store
*.log
node_modules/
# Add any build tool directories if you use them
# .sass-cache/
# dist/
# build/
```

### 3. Make Initial Commit

```bash
git add .
git commit -m "Initial theme commit"
```

## Deployment

### 1. Package Your Theme

Create a clean distribution:

```bash
# Remove development files
rm -rf .git
rm -rf node_modules
# Remove any build tool directories
# rm -rf .sass-cache
# rm -rf dist
# rm -rf build

# Create zip file
zip -r my-theme-v1.0.0.zip . -x "*.git*" "node_modules/*"
```

### 2. Install in FearlessCMS

1. **Upload** the theme to your FearlessCMS installation
2. **Extract** to the `themes/` directory
3. **Activate** the theme in admin panel
4. **Configure** theme options
5. **Test** thoroughly

## Maintenance

### 1. Keep Dependencies Updated

Regularly update any external dependencies:

- CSS frameworks
- JavaScript libraries
- Font files

### 2. Monitor Performance

Track theme performance over time:

- Page load speeds
- User feedback
- Browser compatibility issues

### 3. Version Management

Use semantic versioning for your theme:

- **Major version** (1.0.0 → 2.0.0): Breaking changes
- **Minor version** (1.0.0 → 1.1.0): New features
- **Patch version** (1.0.0 → 1.0.1): Bug fixes

## Example: Complete Development Session

Here's an example of a complete development session:

```bash
# 1. Create theme structure
mkdir -p themes/my-blog-theme/{templates,assets/{css,js,images}}

# 2. Create basic files
cd themes/my-blog-theme
touch theme.json config.json README.md
touch templates/{home,page,blog,404}.html
touch assets/css/style.css assets/js/theme.js

# 3. Edit theme.json
cat > theme.json << 'EOF'
{
    "name": "My Blog Theme",
    "description": "A clean blog theme",
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
EOF

# 4. Create basic template
cat > templates/page.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}} - {{siteName}}</title>
    <link rel="stylesheet" href="/themes/{{theme}}/assets/css/style.css">
</head>
<body>
    <header>
        <h1>{{title}}</h1>
    </header>
    <main>{{content}}</main>
    <footer>&copy; {{currentYear}} {{siteName}}</footer>
</body>
</html>
EOF

# 5. Add basic CSS
cat > assets/css/style.css << 'EOF'
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: sans-serif; line-height: 1.6; padding: 2rem; }
EOF

# 6. Test the theme
# - Activate in admin panel
# - Create test content
# - View in browser
# - Debug any issues

# 7. Iterate and improve
# - Add more styling
# - Implement responsive design
# - Add theme options
# - Test thoroughly
```

This workflow ensures you create a robust, maintainable theme that works well across different devices and content types. 