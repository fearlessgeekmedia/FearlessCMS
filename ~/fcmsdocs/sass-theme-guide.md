# SASS Theme Development Guide

This guide covers how to develop FearlessCMS themes that use SASS (Syntactically Awesome Style Sheets) for CSS preprocessing. This is useful for complex themes that benefit from SASS features like variables, mixins, and nested rules.

## When to Use SASS

Consider using SASS for your theme if you need:

- **Complex styling** with many variables and calculations
- **Reusable components** that benefit from mixins
- **Organized CSS structure** with partials and imports
- **Advanced features** like functions and loops
- **Better maintainability** for large stylesheets

## SASS Setup

### 1. Install SASS

You'll need to install SASS on your development machine:

```bash
# Using npm (recommended)
npm install -g sass

# Using Ruby (if you have Ruby installed)
gem install sass

# Using Homebrew (macOS)
brew install sass/sass/sass
```

### 2. Theme Structure with SASS

Organize your SASS files in a logical structure:

```
themes/your-sass-theme/
├── templates/
│   ├── home.html
│   ├── page.html
│   ├── blog.html
│   └── 404.html
├── assets/
│   ├── sass/
│   │   ├── main.scss          # Main SASS file
│   │   ├── _variables.scss    # Variables
│   │   ├── _mixins.scss       # Mixins
│   │   ├── _base.scss         # Base styles
│   │   ├── _layout.scss       # Layout styles
│   │   ├── _components.scss   # Component styles
│   │   └── _utilities.scss    # Utility classes
│   ├── style.css              # Compiled CSS (generated)
│   ├── images/
│   └── js/
├── theme.json
├── config.json
└── README.md
```

### 3. SASS File Organization

#### main.scss (Main Entry Point)

```scss
// Import all SASS files
@import 'variables';
@import 'mixins';
@import 'base';
@import 'layout';
@import 'components';
@import 'utilities';
```

#### _variables.scss (Variables)

```scss
// Colors
$primary-color: #007bff;
$secondary-color: #6c757d;
$success-color: #28a745;
$danger-color: #dc3545;
$warning-color: #ffc107;
$info-color: #17a2b8;

// Typography
$font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
$font-size-base: 1rem;
$line-height-base: 1.6;

// Spacing
$spacing-unit: 1rem;
$container-max-width: 1200px;

// Breakpoints
$breakpoint-sm: 576px;
$breakpoint-md: 768px;
$breakpoint-lg: 992px;
$breakpoint-xl: 1200px;

// Theme options integration
$theme-colors: (
  'blue': #007bff,
  'green': #28a745,
  'purple': #6f42c1,
  'orange': #fd7e14
);
```

#### _mixins.scss (Reusable Mixins)

```scss
// Responsive breakpoint mixin
@mixin respond-to($breakpoint) {
  @if $breakpoint == sm {
    @media (min-width: $breakpoint-sm) { @content; }
  } @else if $breakpoint == md {
    @media (min-width: $breakpoint-md) { @content; }
  } @else if $breakpoint == lg {
    @media (min-width: $breakpoint-lg) { @content; }
  } @else if $breakpoint == xl {
    @media (min-width: $breakpoint-xl) { @content; }
  }
}

// Button mixin
@mixin button($bg-color: $primary-color, $text-color: white) {
  background-color: $bg-color;
  color: $text-color;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  
  &:hover {
    background-color: darken($bg-color, 10%);
  }
}

// Card mixin
@mixin card($bg-color: white, $border-color: #e9ecef) {
  background-color: $bg-color;
  border: 1px solid $border-color;
  border-radius: 8px;
  padding: $spacing-unit;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

#### _base.scss (Base Styles)

```scss
// Reset and base styles
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px;
}

body {
  font-family: $font-family-base;
  font-size: $font-size-base;
  line-height: $line-height-base;
  color: $text-color;
  background-color: $bg-color;
}

// Typography
h1, h2, h3, h4, h5, h6 {
  margin-bottom: $spacing-unit;
  font-weight: 600;
  line-height: 1.2;
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
h4 { font-size: 1.5rem; }
h5 { font-size: 1.25rem; }
h6 { font-size: 1rem; }

p {
  margin-bottom: $spacing-unit;
}

a {
  color: $primary-color;
  text-decoration: none;
  
  &:hover {
    text-decoration: underline;
  }
}
```

#### _layout.scss (Layout Styles)

```scss
// Container
.container {
  max-width: $container-max-width;
  margin: 0 auto;
  padding: 0 $spacing-unit;
  
  @include respond-to(md) {
    padding: 0 ($spacing-unit * 2);
  }
}

// Grid system
.grid {
  display: grid;
  gap: $spacing-unit;
  
  &--2-cols {
    grid-template-columns: 1fr;
    
    @include respond-to(md) {
      grid-template-columns: 1fr 1fr;
    }
  }
  
  &--3-cols {
    grid-template-columns: 1fr;
    
    @include respond-to(md) {
      grid-template-columns: repeat(3, 1fr);
    }
  }
}

// Header
.header {
  background-color: $header-bg;
  padding: $spacing-unit 0;
  border-bottom: 1px solid $border-color;
}

// Main content
.main {
  padding: ($spacing-unit * 2) 0;
  min-height: 60vh;
}

// Footer
.footer {
  background-color: $footer-bg;
  color: $footer-text-color;
  padding: ($spacing-unit * 2) 0;
  margin-top: auto;
}
```

#### _components.scss (Component Styles)

```scss
// Buttons
.btn {
  @include button;
  
  &--secondary {
    @include button($secondary-color);
  }
  
  &--success {
    @include button($success-color);
  }
  
  &--danger {
    @include button($danger-color);
  }
}

// Cards
.card {
  @include card;
  
  &--featured {
    @include card($primary-color, $primary-color);
    color: white;
  }
}

// Navigation
.nav {
  display: flex;
  align-items: center;
  
  &__list {
    display: flex;
    list-style: none;
    gap: $spacing-unit;
  }
  
  &__link {
    color: $nav-link-color;
    text-decoration: none;
    padding: 0.5rem;
    
    &:hover {
      color: $nav-link-hover-color;
    }
  }
}

// Blog posts
.blog-post {
  margin-bottom: ($spacing-unit * 2);
  padding-bottom: ($spacing-unit * 2);
  border-bottom: 1px solid $border-color;
  
  &:last-child {
    border-bottom: none;
  }
  
  &__title {
    margin-bottom: $spacing-unit;
    
    a {
      color: $text-color;
      text-decoration: none;
      
      &:hover {
        color: $primary-color;
      }
    }
  }
  
  &__meta {
    color: $text-muted;
    font-size: 0.9rem;
    margin-bottom: $spacing-unit;
  }
  
  &__excerpt {
    color: $text-muted;
    margin-bottom: $spacing-unit;
  }
}
```

## Development Workflow with SASS

### 1. Watch Mode

Use SASS watch mode during development:

```bash
# Watch for changes and compile automatically
sass --watch assets/sass/main.scss:assets/style.css

# Or with compressed output
sass --watch assets/sass/main.scss:assets/style.css --style compressed
```

### 2. Build Process

For production builds:

```bash
# Compile once with compressed output
sass assets/sass/main.scss:assets/style.css --style compressed

# Or create a build script in package.json
```

### 3. Package.json Scripts

Create a `package.json` file in your theme directory:

```json
{
  "name": "your-sass-theme",
  "version": "1.0.0",
  "scripts": {
    "sass:watch": "sass --watch assets/sass/main.scss:assets/style.css",
    "sass:build": "sass assets/sass/main.scss:assets/style.css --style compressed",
    "sass:dev": "sass assets/sass/main.scss:assets/style.css --style expanded"
  },
  "devDependencies": {
    "sass": "^1.32.0"
  }
}
```

Then use:

```bash
npm run sass:watch  # Development
npm run sass:build  # Production
npm run sass:dev    # Development with expanded output
```

## Theme Options Integration

### CSS Custom Properties with SASS

Use SASS to generate CSS custom properties for theme options:

```scss
// _variables.scss
$theme-colors: (
  'blue': #007bff,
  'green': #28a745,
  'purple': #6f42c1,
  'orange': #fd7e14
);

// Generate CSS custom properties
:root {
  @each $name, $color in $theme-colors {
    --color-#{$name}: #{$color};
  }
  
  --primary-color: var(--color-blue);
  --secondary-color: var(--color-gray);
}

// Theme-specific classes
@each $name, $color in $theme-colors {
  .theme-#{$name} {
    --primary-color: var(--color-#{$name});
  }
}
```

### Dynamic Theme Options

Create SASS functions to handle theme options:

```scss
// _functions.scss
@function theme-color($color-name) {
  @return var(--color-#{$color-name});
}

@function theme-option($option-name) {
  @return var(--theme-#{$option-name});
}

// Usage
.button {
  background-color: theme-color('primary');
  color: white;
}

.sidebar {
  @if theme-option('show-sidebar') {
    display: block;
  } @else {
    display: none;
  }
}
```

## Example: Complete SASS Theme

### Theme Structure

```
themes/sass-example/
├── templates/
│   ├── home.html
│   ├── page.html
│   ├── blog.html
│   └── 404.html
├── assets/
│   ├── sass/
│   │   ├── main.scss
│   │   ├── _variables.scss
│   │   ├── _mixins.scss
│   │   ├── _base.scss
│   │   ├── _layout.scss
│   │   ├── _components.scss
│   │   └── _utilities.scss
│   ├── style.css
│   └── images/
├── theme.json
├── config.json
├── package.json
└── README.md
```

### main.scss

```scss
// Import all SASS files
@import 'variables';
@import 'mixins';
@import 'base';
@import 'layout';
@import 'components';
@import 'utilities';

// Theme-specific overrides
@import 'theme-overrides';
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
</head>
<body>
    <header class="header">
        <div class="container">
            {{if themeOptions.logo}}
                <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
            {{else}}
                <h1>{{siteName}}</h1>
            {{endif}}
            
            <nav class="nav">
                {{if menu.main}}
                    <ul class="nav__list">
                        {{foreach menu.main}}
                            <li><a href="/{{url}}" class="nav__link">{{title}}</a></li>
                        {{endforeach}}
                    </ul>
                {{endif}}
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <article class="content">
                {{content}}
            </article>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{currentYear}} {{siteName}}</p>
        </div>
    </footer>
</body>
</html>
```

## Best Practices

### 1. File Organization

- Keep SASS files modular and focused
- Use partials (files starting with `_`) for imports
- Group related styles together
- Use consistent naming conventions

### 2. Variable Management

- Define all colors, fonts, and spacing as variables
- Use semantic variable names
- Create variable maps for complex data
- Document variable purposes

### 3. Mixin Usage

- Create mixins for repeated patterns
- Keep mixins focused and reusable
- Use parameters for flexibility
- Document mixin parameters

### 4. Performance

- Minimize SASS compilation time
- Use `@import` sparingly (consider `@use` for newer SASS)
- Avoid deeply nested selectors
- Compress output for production

### 5. Maintenance

- Keep SASS files up to date
- Document complex SASS logic
- Use consistent formatting
- Regular refactoring

## Troubleshooting

### Common Issues

1. **SASS not compiling**: Check file paths and syntax
2. **Variables not working**: Ensure proper import order
3. **Large CSS output**: Use `@extend` instead of mixins where appropriate
4. **Slow compilation**: Reduce file complexity and nesting

### Debug Tips

- Use SASS source maps for debugging
- Check SASS compilation errors
- Validate CSS output
- Test with different SASS versions

## Deployment

### 1. Build for Production

```bash
# Compile with compressed output
sass assets/sass/main.scss:assets/style.css --style compressed

# Or use npm script
npm run sass:build
```

### 2. Include in Theme Package

Make sure to include the compiled `style.css` file in your theme package, not the SASS source files.

### 3. Documentation

Include SASS setup instructions in your theme's README.md for developers who want to modify the theme.

This approach gives you the power of SASS while maintaining compatibility with FearlessCMS's simple theme system. 