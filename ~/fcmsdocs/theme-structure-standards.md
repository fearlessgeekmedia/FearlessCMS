# Theme Structure Standards

This document establishes standards for FearlessCMS theme structure to ensure consistency across all themes.

## Required Theme Structure

All themes must follow this directory structure:

```
themes/
└── theme-name/
    ├── config.json          # Theme configuration (required)
    ├── templates/           # Template files (required)
    │   ├── page.html        # Page template (required)
    │   ├── 404.html         # Error page template (required)
    │   ├── home.html        # Homepage template (optional)
    │   ├── blog.html        # Blog template (optional)
    │   └── [other templates] # Additional templates (optional)
    ├── assets/              # Theme assets (optional)
    │   ├── style.css        # Main stylesheet
    │   ├── images/          # Theme images
    │   └── js/              # JavaScript files
    └── README.md            # Theme documentation (optional)
```

## Configuration File Standards

### File Name
- **Use**: `config.json` (not `theme.json`)
- **Location**: Root of theme directory
- **Format**: Valid JSON

### Required Fields
```json
{
    "name": "Theme Name",
    "version": "1.0.0",
    "author": "Author Name",
    "description": "Brief theme description"
}
```

### Optional Fields
```json
{
    "name": "Theme Name",
    "version": "1.0.0",
    "author": "Author Name",
    "description": "Brief theme description",
    "options": {
        "logo": {
            "type": "image",
            "label": "Logo"
        },
        "herobanner": {
            "type": "image",
            "label": "Hero Banner"
        }
    },
    "supports": ["menus", "responsive", "dark-mode"],
    "preview_image": "screenshot.jpg"
}
```

## Template Standards

### Required Templates
- **page.html**: Individual page template (required)
- **404.html**: Error page template (required)

### Optional Templates
- **home.html**: Homepage template
- **blog.html**: Blog listing template
- **header.html**: Header module
- **footer.html**: Footer module
- **head.html**: Head section module
- **custom_js.html**: Custom JavaScript module

### Template Naming
- Use lowercase with hyphens for multi-word templates
- Examples: `hero-banner.html`, `main-navigation.html`
- Avoid spaces or underscores in filenames

## Asset Organization

### CSS Files
- Place main stylesheet in `assets/style.css`
- Use descriptive names for additional CSS files
- Examples: `assets/components.css`, `assets/responsive.css`

### JavaScript Files
- Place in `assets/js/` directory
- Use descriptive names
- Examples: `assets/js/theme.js`, `assets/js/navigation.js`

### Images
- Place in `assets/images/` directory
- Use descriptive names
- Examples: `assets/images/logo.png`, `assets/images/hero-bg.jpg`

## Module Standards

### Module File Requirements
- All modules must be in the `templates/` directory
- Use `.html` extension
- Modules should be self-contained and reusable

### Module Naming
- Use descriptive, lowercase names
- Separate words with hyphens
- Examples: `site-header.html`, `main-navigation.html`

### Error Handling
- Always check if modules exist before including them
- Provide fallback content for missing modules
- Use consistent error handling across themes

## Theme Options Standards

### Option Types
- **image**: For file uploads (logo, banner, etc.)
- **color**: For color picker inputs
- **text**: For text input fields
- **select**: For dropdown selections
- **boolean**: For checkbox/toggle options

### Option Structure
```json
{
    "optionName": {
        "type": "option_type",
        "label": "Human Readable Label",
        "default": "default_value"  // optional
    }
}
```

### Naming Conventions
- Use camelCase for option names
- Use descriptive, human-readable labels
- Keep option names consistent across themes

## Best Practices

### 1. Consistency
- Follow the established structure
- Use consistent naming conventions
- Maintain similar file organization across themes

### 2. Modularity
- Break templates into reusable modules
- Keep modules focused on single responsibilities
- Use descriptive module names

### 3. Error Handling
- Always provide fallbacks for missing content
- Handle missing theme options gracefully
- Log errors appropriately

### 4. Documentation
- Include a README.md file
- Document theme options and their purposes
- Provide usage examples

### 5. Performance
- Minimize file sizes
- Use efficient CSS and JavaScript
- Optimize images for web use

## Migration Guide

### From theme.json to config.json
If your theme uses `theme.json`, rename it to `config.json` and update the structure:

**Old structure:**
```json
{
    "name": "Theme Name",
    "templates": {
        "home": "home.html",
        "page": "page.html"
    }
}
```

**New structure:**
```json
{
    "name": "Theme Name",
    "version": "1.0.0",
    "author": "Author Name",
    "description": "Theme description"
}
```

### Template Updates
- Ensure `page.html` and `404.html` exist
- Move optional templates to appropriate locations
- Update any hardcoded paths to use relative paths

## Validation Checklist

Before releasing a theme, ensure:

- [ ] `config.json` exists and is valid JSON
- [ ] Required templates (`page.html`, `404.html`) exist
- [ ] All referenced modules exist
- [ ] Theme options are properly defined
- [ ] Assets are organized in `assets/` directory
- [ ] No hardcoded paths or dependencies
- [ ] Error handling is implemented
- [ ] Documentation is complete

## Common Issues

### Missing Required Templates
**Problem**: Theme doesn't have `page.html` or `404.html`
**Solution**: Create these templates or copy from default theme

### Incorrect Configuration File
**Problem**: Using `theme.json` instead of `config.json`
**Solution**: Rename file and update structure

### Missing Modules
**Problem**: Templates reference modules that don't exist
**Solution**: Create missing modules or update templates to handle missing files

### Inconsistent Naming
**Problem**: Mixed naming conventions
**Solution**: Follow established naming standards consistently

---

Following these standards ensures that all FearlessCMS themes work consistently and are easy to maintain and customize. 