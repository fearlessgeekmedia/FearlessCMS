# SASS Theme Development Guide

This guide explains how to use SASS (Syntactically Awesome Style Sheets) to create more maintainable and powerful themes in FearlessCMS.

## Table of Contents

1. [Introduction to SASS](#introduction-to-sass)
2. [Setting Up SASS](#setting-up-sass)
3. [SASS Features](#sass-features)
4. [Theme Integration](#theme-integration)
5. [Best Practices](#best-practices)
6. [Advanced Techniques](#advanced-techniques)
7. [Examples](#examples)

## Introduction to SASS

SASS is a CSS preprocessor that extends CSS with powerful features like variables, nesting, mixins, and functions. It makes CSS more maintainable and reduces code duplication.

### Benefits of Using SASS

- **Variables**: Define colors, fonts, and spacing once
- **Nesting**: Organize CSS logically
- **Mixins**: Reusable CSS patterns
- **Functions**: Dynamic value calculations
- **Partials**: Modular CSS organization
- **Better organization**: Cleaner, more maintainable code

## Setting Up SASS

### 1. Install SASS

#### Using Node.js
```bash
npm install -g sass
```

#### Using Package Managers
```bash
# macOS with Homebrew
brew install sass/sass/sass

# Ubuntu/Debian
sudo apt-get install sass
```

### 2. Project Structure

Organize your SASS files:

```
themes/your-theme/
├── sass/
│   ├── main.scss          # Main SASS file
│   ├── _variables.scss    # Variables
│   ├── _mixins.scss       # Mixins
│   ├── _functions.scss    # Functions
│   ├── _base.scss         # Base styles
│   ├── _layout.scss       # Layout styles
│   ├── _components.scss   # Component styles
│   └── _utilities.scss    # Utility classes
├── assets/
│   └── style.css          # Compiled CSS
└── templates/
    └── *.html
```

### 3. Compilation

Compile SASS to CSS:

```bash
# Watch for changes
sass --watch sass/main.scss:assets/style.css

# One-time compilation
sass sass/main.scss:assets/style.css

# Compressed output
sass sass/main.scss:assets/style.css --style compressed
```

## SASS Features

### Variables

Define reusable values:

```scss
// _variables.scss
$primary-color: #007cba;
$secondary-color: #6c757d;
$text-color: #333;
$background-color: #fff;
$font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
$border-radius: 4px;
$spacing: 1rem;
$container-width: 1200px;

// Breakpoints
$breakpoint-sm: 576px;
$breakpoint-md: 768px;
$breakpoint-lg: 992px;
$breakpoint-xl: 1200px;
```

### Nesting

Organize CSS logically:

```scss
// _components.scss
.site-header {
    background: $primary-color;
    padding: $spacing 0;
    
    .container {
        max-width: $container-width;
        margin: 0 auto;
        padding: 0 $spacing;
    }
    
    .site-title {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        
        a {
            color: white;
            text-decoration: none;
            
            &:hover {
                text-decoration: underline;
            }
        }
    }
    
    .main-navigation {
        ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            
            li {
                a {
                    color: white;
                    text-decoration: none;
                    font-weight: 500;
                    
                    &:hover {
                        opacity: 0.8;
                    }
                }
            }
        }
    }
}
```

### Mixins

Create reusable CSS patterns:

```scss
// _mixins.scss

// Flexbox center
@mixin flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}

// Responsive breakpoint
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

// Button styles
@mixin button($bg-color: $primary-color, $text-color: white) {
    background: $bg-color;
    color: $text-color;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: $border-radius;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    transition: opacity 0.2s;
    
    &:hover {
        opacity: 0.8;
    }
}

// Card styles
@mixin card($bg-color: $background-color, $border-color: #e9ecef) {
    background: $bg-color;
    border: 1px solid $border-color;
    border-radius: $border-radius;
    padding: $spacing;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

### Functions

Create dynamic calculations:

```scss
// _functions.scss

// Calculate contrast ratio
@function contrast-ratio($color1, $color2) {
    $l1: lightness($color1);
    $l2: lightness($color2);
    @return ($l1 + 0.05) / ($l2 + 0.05);
}

// Generate color variations
@function lighten-color($color, $amount: 10%) {
    @return lighten($color, $amount);
}

@function darken-color($color, $amount: 10%) {
    @return darken($color, $amount);
}

// Calculate spacing
@function spacing($multiplier: 1) {
    @return $spacing * $multiplier;
}
```

### Partials

Organize code into modules:

```scss
// main.scss
@import 'variables';
@import 'mixins';
@import 'functions';
@import 'base';
@import 'layout';
@import 'components';
@import 'utilities';
```

## Theme Integration

### 1. Dynamic Variables with Theme Options

Use SASS with FearlessCMS theme options:

```scss
// _variables.scss
// These will be replaced by actual values during compilation
$primary-color: {{themeOptions.primaryColor}};
$font-family: {{themeOptions.fontFamily}};
$show-sidebar: {{themeOptions.showSidebar}};
```

### 2. Conditional Styles

```scss
// _layout.scss
.content-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: spacing(2);
    
    @if $show-sidebar == 'true' {
        grid-template-columns: 2fr 1fr;
        
        @include respond-to(md) {
            grid-template-columns: 1fr 300px;
        }
    }
}
```

### 3. Component System

```scss
// _components.scss

// Button component
.btn {
    @include button();
    
    &--secondary {
        @include button($secondary-color);
    }
    
    &--large {
        padding: spacing(0.75) spacing(1.5);
        font-size: 1.1rem;
    }
}

// Card component
.card {
    @include card();
    
    &--featured {
        @include card($primary-color, $primary-color);
        color: white;
    }
}

// Navigation component
.nav {
    &__list {
        list-style: none;
        display: flex;
        gap: spacing(1);
    }
    
    &__item {
        a {
            color: $text-color;
            text-decoration: none;
            padding: spacing(0.5);
            
            &:hover {
                color: $primary-color;
            }
        }
    }
}
```

## Best Practices

### 1. File Organization

```
sass/
├── abstracts/
│   ├── _variables.scss
│   ├── _mixins.scss
│   └── _functions.scss
├── base/
│   ├── _reset.scss
│   ├── _typography.scss
│   └── _base.scss
├── components/
│   ├── _buttons.scss
│   ├── _cards.scss
│   └── _navigation.scss
├── layout/
│   ├── _header.scss
│   ├── _footer.scss
│   └── _sidebar.scss
├── pages/
│   ├── _home.scss
│   └── _blog.scss
└── main.scss
```

### 2. Naming Conventions

Use BEM (Block, Element, Modifier) methodology:

```scss
.site-header {
    &__logo {
        // Element
    }
    
    &--dark {
        // Modifier
        background: $dark-color;
    }
}

.nav {
    &__list {
        // Element
    }
    
    &__item {
        // Element
        &--active {
            // Modifier
        }
    }
}
```

### 3. Variable Naming

```scss
// Colors
$color-primary: #007cba;
$color-secondary: #6c757d;
$color-success: #28a745;
$color-danger: #dc3545;

// Typography
$font-family-base: sans-serif;
$font-size-base: 1rem;
$line-height-base: 1.6;

// Spacing
$spacing-xs: 0.25rem;
$spacing-sm: 0.5rem;
$spacing-md: 1rem;
$spacing-lg: 1.5rem;
$spacing-xl: 3rem;
```

### 4. Responsive Design

```scss
// _mixins.scss
@mixin mobile-first {
    // Base styles for mobile
    @content;
    
    // Tablet
    @media (min-width: $breakpoint-md) {
        @content;
    }
    
    // Desktop
    @media (min-width: $breakpoint-lg) {
        @content;
    }
}

// Usage
.content {
    @include mobile-first {
        padding: $spacing-sm;
        
        @media (min-width: $breakpoint-md) {
            padding: $spacing-md;
        }
        
        @media (min-width: $breakpoint-lg) {
            padding: $spacing-lg;
        }
    }
}
```

## Advanced Techniques

### 1. Theme Option Integration

Create a SASS compilation script that processes theme options:

```javascript
// build-sass.js
const sass = require('sass');
const fs = require('fs');

function compileSassWithOptions(themeOptions) {
    const sassContent = fs.readFileSync('sass/main.scss', 'utf8');
    
    // Replace SASS variables with theme option values
    const processedContent = sassContent
        .replace(/\{\{themeOptions\.primaryColor\}\}/g, themeOptions.primaryColor || '#007cba')
        .replace(/\{\{themeOptions\.fontFamily\}\}/g, themeOptions.fontFamily || 'sans-serif');
    
    // Compile SASS
    const result = sass.compileString(processedContent);
    
    fs.writeFileSync('assets/style.css', result.css);
}

// Usage
const themeOptions = {
    primaryColor: '#ff6b6b',
    fontFamily: 'serif'
};

compileSassWithOptions(themeOptions);
```

### 2. CSS Custom Properties

Use SASS to generate CSS custom properties:

```scss
// _variables.scss
:root {
    --primary-color: #{$primary-color};
    --secondary-color: #{$secondary-color};
    --font-family: #{$font-family};
    --spacing: #{$spacing};
    
    // Generate spacing scale
    @for $i from 1 through 10 {
        --spacing-#{$i}: #{spacing($i)};
    }
    
    // Generate color variations
    --primary-light: #{lighten-color($primary-color, 10%)};
    --primary-dark: #{darken-color($primary-color, 10%)};
}
```

### 3. Utility Classes

Generate utility classes with SASS:

```scss
// _utilities.scss

// Spacing utilities
@each $size, $value in $spacing-scale {
    .p-#{$size} { padding: $value; }
    .m-#{$size} { margin: $value; }
    .pt-#{$size} { padding-top: $value; }
    .pb-#{$size} { padding-bottom: $value; }
    .pl-#{$size} { padding-left: $value; }
    .pr-#{$size} { padding-right: $value; }
}

// Color utilities
@each $name, $color in $colors {
    .text-#{$name} { color: $color; }
    .bg-#{$name} { background-color: $color; }
    .border-#{$name} { border-color: $color; }
}

// Flexbox utilities
.d-flex { display: flex; }
.flex-column { flex-direction: column; }
.justify-center { justify-content: center; }
.align-center { align-items: center; }
```

## Examples

### Complete Theme with SASS

#### main.scss
```scss
// Abstracts
@import 'abstracts/variables';
@import 'abstracts/mixins';
@import 'abstracts/functions';

// Base
@import 'base/reset';
@import 'base/typography';
@import 'base/base';

// Components
@import 'components/buttons';
@import 'components/cards';
@import 'components/navigation';
@import 'components/forms';

// Layout
@import 'layout/header';
@import 'layout/footer';
@import 'layout/sidebar';
@import 'layout/grid';

// Pages
@import 'pages/home';
@import 'pages/blog';

// Utilities
@import 'utilities/spacing';
@import 'utilities/colors';
@import 'utilities/flexbox';
```

#### _variables.scss
```scss
// Colors
$primary-color: {{themeOptions.primaryColor}};
$secondary-color: {{themeOptions.secondaryColor}};
$success-color: #28a745;
$danger-color: #dc3545;
$warning-color: #ffc107;
$info-color: #17a2b8;

$text-color: #333;
$text-muted: #6c757d;
$background-color: #fff;
$border-color: #e9ecef;

// Typography
$font-family: {{themeOptions.fontFamily}};
$font-size-base: 1rem;
$line-height-base: 1.6;
$font-weight-normal: 400;
$font-weight-bold: 700;

// Spacing
$spacing: 1rem;
$spacing-xs: 0.25rem;
$spacing-sm: 0.5rem;
$spacing-md: 1rem;
$spacing-lg: 1.5rem;
$spacing-xl: 3rem;

// Layout
$container-width: 1200px;
$sidebar-width: 300px;
$header-height: 80px;

// Breakpoints
$breakpoint-sm: 576px;
$breakpoint-md: 768px;
$breakpoint-lg: 992px;
$breakpoint-xl: 1200px;

// Theme options
$show-sidebar: {{themeOptions.showSidebar}};
$logo: {{themeOptions.logo}};
```

#### _components.scss
```scss
// Buttons
.btn {
    @include button();
    
    &--primary {
        @include button($primary-color);
    }
    
    &--secondary {
        @include button($secondary-color);
    }
    
    &--large {
        padding: spacing(0.75) spacing(1.5);
        font-size: 1.1rem;
    }
}

// Cards
.card {
    @include card();
    
    &__header {
        padding: spacing(1);
        border-bottom: 1px solid $border-color;
    }
    
    &__body {
        padding: spacing(1);
    }
    
    &__footer {
        padding: spacing(1);
        border-top: 1px solid $border-color;
        background: lighten($background-color, 2%);
    }
}

// Navigation
.nav {
    &__list {
        list-style: none;
        display: flex;
        gap: spacing(1);
        margin: 0;
        padding: 0;
    }
    
    &__item {
        a {
            color: $text-color;
            text-decoration: none;
            padding: spacing(0.5);
            border-radius: $border-radius;
            transition: all 0.2s;
            
            &:hover {
                color: $primary-color;
                background: lighten($primary-color, 45%);
            }
        }
        
        &--active a {
            color: $primary-color;
            background: lighten($primary-color, 45%);
        }
    }
}
```

#### _layout.scss
```scss
// Header
.site-header {
    background: $primary-color;
    color: white;
    padding: spacing(1) 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    
    .container {
        @include flex-center;
        justify-content: space-between;
        max-width: $container-width;
        margin: 0 auto;
        padding: 0 spacing(1);
    }
    
    .site-title {
        font-size: 1.5rem;
        font-weight: $font-weight-bold;
        margin: 0;
        
        a {
            color: white;
            text-decoration: none;
        }
    }
    
    .main-navigation {
        .nav__list {
            @include respond-to(md) {
                gap: spacing(1.5);
            }
        }
    }
}

// Main content
.site-main {
    min-height: calc(100vh - #{$header-height});
    padding: spacing(2) 0;
}

.content-layout {
    display: grid;
    gap: spacing(2);
    
    @if $show-sidebar == 'true' {
        grid-template-columns: 1fr;
        
        @include respond-to(md) {
            grid-template-columns: 1fr $sidebar-width;
        }
    } @else {
        grid-template-columns: 1fr;
    }
}

// Sidebar
.sidebar {
    @if $show-sidebar == 'true' {
        background: lighten($background-color, 1%);
        padding: spacing(1);
        border-radius: $border-radius;
        height: fit-content;
        
        @include respond-to(md) {
            position: sticky;
            top: calc(#{$header-height} + #{spacing(1)});
        }
    }
}

// Footer
.site-footer {
    background: $text-color;
    color: white;
    padding: spacing(2) 0;
    margin-top: auto;
    
    .container {
        max-width: $container-width;
        margin: 0 auto;
        padding: 0 spacing(1);
        text-align: center;
    }
}
```

### Build Script

Create a build script to compile SASS with theme options:

```bash
#!/bin/bash
# build-theme.sh

THEME_NAME="your-theme"
SASS_DIR="themes/$THEME_NAME/sass"
CSS_DIR="themes/$THEME_NAME/assets"

# Compile SASS to CSS
sass --style compressed "$SASS_DIR/main.scss:$CSS_DIR/style.css"

echo "Theme compiled successfully!"
```

This comprehensive SASS guide provides everything you need to create maintainable, powerful themes in FearlessCMS using SASS preprocessing. 