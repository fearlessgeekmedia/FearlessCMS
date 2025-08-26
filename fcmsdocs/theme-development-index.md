# FearlessCMS Theme Development Documentation

Welcome to the FearlessCMS theme development documentation! This collection of guides will help you create beautiful, functional themes for FearlessCMS.

## 📚 Documentation Index

### Getting Started
- **[Customization Overview](customization-overview)** - Complete guide to all customization options
- **[Creating Themes in FearlessCMS](creating-themes)** - Complete guide to creating themes from scratch
- **[Theme Development Workflow](theme-development-workflow)** - Step-by-step development process and best practices

### Reference Guides
- **[Template Reference](theme-templates-reference)** - Complete template syntax and variable reference
- **[Theme Options Guide](theme-options-guide)** - How to implement and use theme options
- **[Modular Templates](modular-templates)** - How to use the modular template system for better code organization
- **Global UI Enhancements** - Optional hamburger and theme toggle controls

### System Administration
- **[CMS Modes Guide](cms-modes)** - How to configure and manage CMS operational modes
- **[File Permissions Guide](file-permissions)** - Setting up proper file permissions and ownership
- **[Ad Area System](ad-area-system)** - Conditional advertising system for hosting service modes

### Examples and Tutorials
- **[Nightfall Theme Example](../themes/nightfall/)** - Real-world example of a complete theme
- **[Theme Examples](../themes/)** - Browse all available themes for inspiration

## 🚀 Quick Start

1. **Read the Basics**: Start with [Creating Themes in FearlessCMS](creating-themes)
2. **Follow the Workflow**: Use [Theme Development Workflow](theme-development-workflow) for step-by-step guidance
3. **Reference Syntax**: Check [Template Reference](theme-templates-reference) for all available variables and syntax
4. **Add Options**: Learn about [Theme Options](theme-options-guide) to make your theme customizable
5. **Go Modular**: Explore [Modular Templates](modular-templates) for better code organization

## 🎯 What You'll Learn

- How to create a complete theme from scratch
- Template system and variable usage
- **Modular template system for reusable components**
- Implementing theme options for customization
- Responsive design principles
- Best practices for theme development
- Testing and deployment strategies
- **CMS modes and system administration**
- **File permissions and security best practices**
- **Ad area system integration for hosting services**

## 📁 Theme Structure

A typical FearlessCMS theme includes:

```
themes/your-theme/
├── templates/
│   ├── home.html      # Homepage template
│   ├── page.html      # Individual page template
│   ├── blog.html      # Blog listing template
│   ├── 404.html       # Error page template
│   ├── header.html    # Header module (modular system)
│   ├── footer.html    # Footer module (modular system)
│   └── navigation.html # Navigation module (modular system)
├── assets/
│   ├── style.css      # Main stylesheet
│   ├── images/        # Theme images
│   └── js/           # JavaScript files
├── theme.json        # Theme metadata
├── config.json       # Theme options (optional)
└── README.md         # Theme documentation
```

## 🔧 Key Features

- **Simple Template System**: Easy-to-learn syntax with powerful features
- **Modular Templates**: Break down templates into reusable components with `{{module=filename.html}}`
- **Theme Options**: User-friendly customization without code editing
- **Responsive Design**: Built-in support for mobile-first design
- **Extensible**: Add custom functionality with JavaScript
- **SEO-Friendly**: Semantic HTML and meta tag support
- **Ad Area Integration**: Automatic conditional advertising for hosting service modes

## Global UI Enhancements (Optional)

FearlessCMS can optionally inject two small UI features across themes:

- A responsive hamburger button that toggles the main `nav` under 900px
- A theme mode toggle (System → Light → Dark) that stores preference in `localStorage` and applies it via `data-theme` on `<html>`

### Enabling/Disabling

Site-level flags in `config/config.json` (defaults shown):

```json
{
  "global_ui_enhancements": true,
  "enable_hamburger": true,
  "enable_theme_toggle": true
}
```

Theme-level flags in `themes/<theme>/config.json`:

```json
{
  "disableGlobalEnhancements": false,
  "disableHamburger": false,
  "disableThemeToggle": false,
  "supportsDarkMode": true
}
```

To quickly disable on a page/theme without changing JSON, add in your head module:

```html
<meta name="fcms-disable-global-ui" content="1">
```

### Requirements

- Your `header` should contain a `nav`. If `.header-inner` exists, controls appear before `nav` inside it; otherwise they appear before `nav` in `header`.
- For themes without dark mode, set `supportsDarkMode: false` or `disableThemeToggle: true` to hide the toggle.

### Accessibility

- Buttons are keyboard-focusable and announce state (`aria-expanded` for hamburger).
- Dark/light/system preference persists per browser via `localStorage` (`fcms-theme`).

## 💡 Tips for Success

1. **Start Simple**: Begin with a basic, functional theme
2. **Use Modular Templates**: Break down complex templates into reusable modules
3. **Test Thoroughly**: Check on different devices and browsers
4. **Use Semantic HTML**: Follow web standards for better accessibility
5. **Mobile-First**: Design for mobile devices first, then enhance for desktop
6. **Document Everything**: Include clear documentation for users

## 🤝 Contributing

Found an issue or have a suggestion? Contributions are welcome! Please:

1. Check existing issues first
2. Follow the established documentation style
3. Test your changes thoroughly
4. Submit clear, descriptive pull requests

## 📖 Additional Resources

- [FearlessCMS Main Documentation](../README)
- [Admin Panel Guide](../admin/README)
- [Plugin Development Guide](../plugins/README)
- [CMS Modes Guide](cms-modes) - System administration and deployment modes
- [File Permissions Guide](file-permissions) - Security and permission setup
- [Ad Area System](ad-area-system) - Conditional advertising features
- [API Reference](../docs/api)

## 🆘 Getting Help

If you need help with theme development:

1. **Check the documentation** - Most questions are answered here
2. **Look at examples** - Browse existing themes for inspiration
3. **Search issues** - Check if your question has been asked before
4. **Ask the community** - Join discussions in the project forums

## 🔒 Security Note: Config Directory Location

FearlessCMS supports storing your configuration files outside the webroot for enhanced security. Set the `FCMS_CONFIG_DIR` environment variable to point to a secure directory. See [File Permissions Guide](file-permissions) for details.

---

**Happy theme development!** 🎨

*This documentation is maintained by the FearlessCMS community. Last updated: January 2025* 