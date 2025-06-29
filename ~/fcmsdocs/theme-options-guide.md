# Theme Options Guide

This guide explains how to create and use theme options in FearlessCMS themes. Theme options allow users to customize your theme without editing code.

## Table of Contents

1. [Overview](#overview)
2. [Defining Theme Options](#defining-theme-options)
3. [Option Types](#option-types)
4. [Using Options in Templates](#using-options-in-templates)
5. [Advanced Features](#advanced-features)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

## Overview

Theme options provide a user-friendly way to customize themes through the admin panel. They can control colors, fonts, layouts, and other visual elements without requiring code changes.

### Benefits

- **User-friendly**: No coding required for customization
- **Flexible**: Support various data types (colors, text, images, etc.)
- **Maintainable**: Changes are stored in configuration, not code
- **Reusable**: Options can be used across multiple templates

### Basic Structure

Theme options are defined in the `config.json` file within an `options` section:

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
        }
    }
}
```

## Defining Theme Options

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

## Option Types

### Color Picker

```json
{
    "primaryColor": {
        "type": "color",
        "label": "Primary Color",
        "default": "#007cba",
        "description": "Main brand color"
    }
}
```

### Text Input

```json
{
    "siteTagline": {
        "type": "text",
        "label": "Site Tagline",
        "default": "Welcome to our site",
        "description": "Displayed below the site title"
    }
}
```

### Textarea

```json
{
    "customCSS": {
        "type": "textarea",
        "label": "Custom CSS",
        "description": "Add custom CSS styles",
        "placeholder": "Enter your custom CSS here..."
    }
}
```

### Select Dropdown

```json
{
    "layout": {
        "type": "select",
        "label": "Layout Style",
        "options": [
            {"value": "wide", "label": "Wide Layout"},
            {"value": "narrow", "label": "Narrow Layout"},
            {"value": "full", "label": "Full Width"}
        ],
        "default": "wide",
        "description": "Choose the main layout style"
    }
}
```

### Boolean (Checkbox)

```json
{
    "showSearch": {
        "type": "boolean",
        "label": "Show Search",
        "default": true,
        "description": "Display search functionality"
    }
}
```

### Image Upload

```json
{
    "heroImage": {
        "type": "image",
        "label": "Hero Image"
    }
}
```

## Using Options in Templates

You can access theme options in two ways:

### Direct Variable Access

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

### Theme Options Object Access

```html
{{#if themeOptions.logo}}
    <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
{{else}}
    <h1 class="site-title">{{siteName}}</h1>
{{/if}}

<div class="content {{#if themeOptions.showSidebar}}with-sidebar{{/if}}">
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

**Note**: Both methods work the same way. Use whichever style you prefer for consistency in your theme.

## Advanced Features

### Conditional CSS Classes

```html
<body class="theme-{{themeOptions.fontFamily}} {{#if themeOptions.showSidebar}}with-sidebar{{/if}} {{#if themeOptions.darkMode}}dark-mode{{/if}}">
```

### Dynamic Attributes

```html
<a href="{{url}}" 
   {{#if themeOptions.openInNewTab}}target="_blank"{{/if}}
   {{#if themeOptions.noFollow}}rel="nofollow"{{/if}}>
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

### Custom CSS Injection

```html
{{#if themeOptions.customCSS}}
    <style>
        {{themeOptions.customCSS}}
    </style>
{{/if}}
```

## Best Practices

### 1. Provide Sensible Defaults

Always provide default values for your options:

```json
{
    "primaryColor": {
        "type": "color",
        "label": "Primary Color",
        "default": "#007cba"
    }
}
```

### 2. Use Descriptive Labels

Make option labels clear and user-friendly:

```json
{
    "showSidebar": {
        "type": "boolean",
        "label": "Display Sidebar on Pages",
        "description": "Show a sidebar with navigation and widgets"
    }
}
```

### 3. Group Related Options

Organize related options together:

```json
{
    "options": {
        "colors": {
            "primaryColor": { "type": "color", "label": "Primary Color" },
            "secondaryColor": { "type": "color", "label": "Secondary Color" }
        },
        "layout": {
            "showSidebar": { "type": "boolean", "label": "Show Sidebar" },
            "sidebarPosition": { "type": "select", "label": "Sidebar Position" }
        }
    }
}
```

### 4. Validate Input

Consider validation for user input:

```json
{
    "customCSS": {
        "type": "textarea",
        "label": "Custom CSS",
        "description": "Add custom CSS (be careful with syntax)",
        "placeholder": "/* Enter your custom CSS here */"
    }
}
```

### 5. Use Semantic Names

Choose option names that clearly indicate their purpose:

```json
{
    "headerStyle": { "type": "select", "label": "Header Style" },
    "footerText": { "type": "text", "label": "Footer Text" },
    "enableAnimations": { "type": "boolean", "label": "Enable Animations" }
}
```

## Examples

### Complete Theme Configuration

```json
{
    "options": {
        "branding": {
            "logo": {
                "type": "image",
                "label": "Site Logo",
                "description": "Upload your site logo"
            },
            "primaryColor": {
                "type": "color",
                "label": "Primary Color",
                "default": "#007cba",
                "description": "Main brand color"
            },
            "secondaryColor": {
                "type": "color",
                "label": "Secondary Color",
                "default": "#6c757d",
                "description": "Secondary brand color"
            }
        },
        "typography": {
            "fontFamily": {
                "type": "select",
                "label": "Font Family",
                "options": [
                    {"value": "sans-serif", "label": "Sans Serif"},
                    {"value": "serif", "label": "Serif"},
                    {"value": "monospace", "label": "Monospace"}
                ],
                "default": "sans-serif",
                "description": "Choose the main font"
            },
            "fontSize": {
                "type": "select",
                "label": "Font Size",
                "options": [
                    {"value": "small", "label": "Small"},
                    {"value": "medium", "label": "Medium"},
                    {"value": "large", "label": "Large"}
                ],
                "default": "medium",
                "description": "Choose the base font size"
            }
        },
        "layout": {
            "showSidebar": {
                "type": "boolean",
                "label": "Show Sidebar",
                "default": true,
                "description": "Display sidebar on pages"
            },
            "sidebarPosition": {
                "type": "select",
                "label": "Sidebar Position",
                "options": [
                    {"value": "left", "label": "Left"},
                    {"value": "right", "label": "Right"}
                ],
                "default": "right",
                "description": "Choose sidebar position"
            },
            "containerWidth": {
                "type": "select",
                "label": "Container Width",
                "options": [
                    {"value": "narrow", "label": "Narrow"},
                    {"value": "standard", "label": "Standard"},
                    {"value": "wide", "label": "Wide"}
                ],
                "default": "standard",
                "description": "Choose the main container width"
            }
        },
        "content": {
            "showExcerpts": {
                "type": "boolean",
                "label": "Show Excerpts",
                "default": true,
                "description": "Display page excerpts"
            },
            "excerptLength": {
                "type": "number",
                "label": "Excerpt Length",
                "default": 150,
                "description": "Number of characters in excerpts"
            },
            "showDates": {
                "type": "boolean",
                "label": "Show Dates",
                "default": true,
                "description": "Display page dates"
            }
        },
        "social": {
            "socialLinks": {
                "type": "array",
                "label": "Social Media Links",
                "description": "Add social media links",
                "fields": {
                    "platform": {
                        "type": "select",
                        "label": "Platform",
                        "options": [
                            {"value": "facebook", "label": "Facebook"},
                            {"value": "twitter", "label": "Twitter"},
                            {"value": "instagram", "label": "Instagram"},
                            {"value": "linkedin", "label": "LinkedIn"}
                        ]
                    },
                    "url": {
                        "type": "text",
                        "label": "Profile URL"
                    }
                }
            }
        },
        "advanced": {
            "customCSS": {
                "type": "textarea",
                "label": "Custom CSS",
                "description": "Add custom CSS styles",
                "placeholder": "/* Enter your custom CSS here */"
            },
            "customJS": {
                "type": "textarea",
                "label": "Custom JavaScript",
                "description": "Add custom JavaScript",
                "placeholder": "// Enter your custom JavaScript here"
            },
            "analyticsCode": {
                "type": "textarea",
                "label": "Analytics Code",
                "description": "Add Google Analytics or other tracking code"
            }
        }
    }
}
```

### Template Usage Example

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{#if title}}{{title}} - {{/if}}{{siteName}}</title>
    
    <style>
        :root {
            --primary-color: {{themeOptions.primaryColor}};
            --secondary-color: {{themeOptions.secondaryColor}};
            --font-family: {{themeOptions.fontFamily}};
            --font-size: {{themeOptions.fontSize}};
        }
        
        {{#if themeOptions.customCSS}}
            {{themeOptions.customCSS}}
        {{/if}}
    </style>
    
    <link rel="stylesheet" href="/themes/{{theme}}/assets/style.css">
</head>
<body class="theme-{{themeOptions.fontFamily}} {{#if themeOptions.showSidebar}}with-sidebar sidebar-{{themeOptions.sidebarPosition}}{{/if}}">
    <header class="site-header">
        <div class="container container-{{themeOptions.containerWidth}}">
            {{#if themeOptions.logo}}
                <img src="/{{themeOptions.logo}}" alt="{{siteName}}" class="logo">
            {{else}}
                <h1 class="site-title">{{siteName}}</h1>
            {{/if}}
            
            {{#if menu.main}}
                <nav class="main-navigation">
                    <ul class="nav-menu">
                        {{menu=main}}
                    </ul>
                </nav>
            {{/if}}
        </div>
    </header>
    
    <main class="site-main">
        <div class="container container-{{themeOptions.containerWidth}}">
            <div class="content-layout">
                <div class="main-content">
                    <article class="content">
                        {{#if title}}
                            <header class="content-header">
                                <h1 class="content-title">{{title}}</h1>
                                {{#if themeOptions.showDates}}
                                    {{#if date}}
                                        <time class="content-date">{{date}}</time>
                                    {{/if}}
                                {{/if}}
                            </header>
                        {{/if}}
                        
                        {{#if themeOptions.showExcerpts}}
                            {{#if excerpt}}
                                <div class="content-excerpt">
                                    <p>{{excerpt}}</p>
                                </div>
                            {{/if}}
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
        <div class="container container-{{themeOptions.containerWidth}}">
            <div class="footer-content">
                <div class="footer-info">
                    <p>&copy; {{currentYear}} {{siteName}}. All rights reserved.</p>
                </div>
                
                {{#if themeOptions.socialLinks}}
                    <div class="social-links">
                        {{#each themeOptions.socialLinks}}
                            <a href="{{url}}" target="_blank" rel="noopener" class="social-link social-{{platform}}">
                                {{platform}}
                            </a>
                        {{/each}}
                    </div>
                {{/if}}
            </div>
        </div>
    </footer>
    
    {{#if themeOptions.customJS}}
        <script>
            {{themeOptions.customJS}}
        </script>
    {{/if}}
    
    {{#if themeOptions.analyticsCode}}
        {{themeOptions.analyticsCode}}
    {{/if}}
</body>
</html>
```

This comprehensive guide covers all aspects of theme options in FearlessCMS. Use these examples and best practices to create flexible, user-friendly themes that can be easily customized without code changes. 