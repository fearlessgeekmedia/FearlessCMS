# FearlessCMS Documentation

Welcome to the FearlessCMS documentation. This guide will help you understand how to use and customize FearlessCMS for your website needs.

## Quick Start

**New to FearlessCMS?** Start with our [Customization Overview](customization-overview.md) for a comprehensive guide to all available tweaks and customizations.

## Table of Contents

1. [Customization Overview](#customization-overview)
2. [Creating Themes](#creating-themes)
3. [Theme Templates Reference](#theme-templates-reference)
4. [Theme Options Guide](#theme-options-guide)
5. [Modular Templates](#modular-templates)
6. [Theme Development Workflow](#theme-development-workflow)
7. [SASS Theme Guide](#sass-theme-guide)
8. [Theme Structure Standards](#theme-structure-standards)

## Customization Overview

A comprehensive overview of all customization options available in FearlessCMS, including:

- Admin path customization
- CMS mode configuration
- Custom CSS and JavaScript
- Theme options and settings
- Plugin management
- Menu and widget systems
- File and user management
- Advanced customizations

This guide provides quick references, examples, and links to detailed documentation for each area.

[Read the Customization Overview →](customization-overview.md)

## Creating Themes

Learn how to create custom themes for FearlessCMS from scratch. This guide covers:

- Theme structure and required files
- Template system and variables
- Theme options and customization
- CSS styling and best practices
- Advanced features and examples

**Key Points:**
- Use `config.json` (not `theme.json`) for theme configuration
- Only `page.html` and `404.html` are strictly required templates
- Theme options can be accessed via direct variables or `themeOptions` object

[Read the Creating Themes Guide →](creating-themes.md)

## Theme Templates Reference

A comprehensive reference for all template syntax, variables, and features available in FearlessCMS themes.

**Features:**
- Complete template variable reference
- Conditional logic and loops
- Modular template system
- Error handling for missing modules

[Read the Templates Reference →](theme-templates-reference.md)

## Theme Options Guide

Learn how to create and use theme options to make your themes customizable without code changes.

**Supported Types:**
- Image uploads (logo, banner, etc.)
- Color pickers
- Text inputs
- Select dropdowns
- Boolean toggles

[Read the Theme Options Guide →](theme-options-guide.md)

## Modular Templates

Break down your templates into reusable components for better maintainability.

**Features:**
- `{{module=filename.html}}` syntax
- Automatic error handling for missing modules
- Support for nested modules
- Variable access across modules

[Read the Modular Templates Guide →](modular-templates.md)

## Theme Development Workflow

Best practices and workflow for developing themes efficiently.

**Topics:**
- Development environment setup
- Testing and debugging
- Performance optimization
- Deployment strategies

[Read the Development Workflow Guide →](theme-development-workflow.md)

## SASS Theme Guide

Advanced styling with SASS/SCSS for more sophisticated themes.

**Features:**
- SASS compilation setup
- Variable and mixin usage
- Responsive design patterns
- Performance optimization

[Read the SASS Guide →](sass-theme-guide.md)

## Theme Structure Standards

**NEW**: Standards for consistent theme structure across all FearlessCMS themes.

**Key Standards:**
- Required file structure and naming
- Configuration file requirements
- Template and module standards
- Asset organization guidelines
- Migration guide for existing themes

[Read the Theme Structure Standards →](theme-structure-standards.md)

## Quick Start

1. **Create a new theme directory** in `/themes/your-theme-name/`
2. **Add required files**:
   - `config.json` - Theme configuration
   - `templates/page.html` - Page template (required)
   - `templates/404.html` - Error page template (required)
3. **Define theme options** in `config.json`
4. **Create additional templates** as needed
5. **Add styling** in `assets/style.css`

## Example Theme Structure

```
themes/
└── my-theme/
    ├── config.json          # Theme configuration
    ├── templates/
    │   ├── page.html        # Required
    │   ├── 404.html         # Required
    │   ├── home.html        # Optional
    │   ├── header.html      # Module
    │   └── footer.html      # Module
    ├── assets/
    │   └── style.css        # Main stylesheet
    └── README.md            # Documentation
```

## Getting Help

- Check the [Theme Structure Standards](theme-structure-standards.md) for consistency guidelines
- Review existing themes in the `/themes/` directory for examples
- Use the [Templates Reference](theme-templates-reference.md) for syntax help
- Follow the [Development Workflow](theme-development-workflow.md) for best practices

---

*This documentation is maintained by the FearlessCMS team. For issues or suggestions, please contribute to the project.* 