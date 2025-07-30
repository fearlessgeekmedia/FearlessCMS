# FearlessCMS Customization Overview

## Introduction

FearlessCMS is designed to be highly customizable while maintaining simplicity and performance. This guide provides an overview of all the ways you can customize your FearlessCMS installation.

## 🎨 Theme Customization

### Creating Custom Themes
The primary way to customize FearlessCMS is through themes. Themes control the visual appearance and layout of your website.

**Key Features:**
- **HTML Templates**: Use Handlebars templating for dynamic content
- **CSS Styling**: Full control over visual design
- **JavaScript**: Add interactive functionality
- **Modular Templates**: Break down templates into reusable components
- **Theme Options**: User-friendly customization without code editing

**Getting Started:**
- [Creating Themes Guide](creating-themes.md) - Complete theme development tutorial
- [Theme Development Workflow](theme-development-workflow.md) - Step-by-step process
- [Template Reference](theme-templates-reference.md) - All available variables and syntax

### Theme Options System
Allow users to customize themes without editing code:

**Available Option Types:**
- **Text**: Simple text input
- **Textarea**: Multi-line text input
- **Select**: Dropdown selection
- **Checkbox**: Boolean toggle
- **Color**: Color picker
- **Image**: Image upload/selection
- **Array**: Repeating field groups

**Example:**
```json
{
    "options": {
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

**Usage in Templates:**
```html
<div class="theme-{{themeOptions.primaryColor}}">
    {{#if themeOptions.showSidebar}}
        <aside class="sidebar">...</aside>
    {{/if}}
</div>
```

## 🔌 Plugin Development

### Creating Custom Plugins
Extend FearlessCMS functionality with custom plugins:

**Plugin Capabilities:**
- **Content Processing**: Modify content before display
- **Admin Integration**: Add admin panel features
- **API Endpoints**: Create custom API endpoints
- **Database Operations**: Custom data storage and retrieval
- **Event Hooks**: Respond to system events

**Getting Started:**
- [Plugin Development Guide](plugin-development-guide.md) - Complete plugin tutorial
- [Plugin Examples](../plugins/) - Browse existing plugins

### Built-in Plugins
FearlessCMS includes several built-in plugins:

- **Forms Plugin**: Create and manage contact forms
- **Sitemap Plugin**: Generate XML sitemaps
- **SEO Plugin**: Meta tag management
- **Analytics Plugin**: Basic analytics integration

## 🎛️ System Configuration

### CMS Modes
Configure FearlessCMS for different environments:

**Available Modes:**
- **Development**: Full debugging, detailed error messages
- **Production**: Optimized performance, minimal error output
- **Maintenance**: Show maintenance page to visitors

**Configuration:**
```php
// In config/config.json
{
    "mode": "production",
    "debug": false,
    "maintenance": false
}
```

**Getting Started:**
- [CMS Modes Guide](cms-modes.md) - Detailed mode configuration

### File Permissions
Proper file permissions are crucial for security and functionality:

**Critical Directories:**
- `sessions/` - Session file storage
- `content/forms/` - Forms plugin data
- `content/form_submissions/` - Form submissions
- `config/` - Configuration files
- `uploads/` - File uploads
- `admin/uploads/` - Admin file uploads

**Security Best Practices:**
- Use proper ownership (web server user)
- Standard permissions (755 for directories, 644 for files)
- Avoid overly permissive 777/666 permissions

**Getting Started:**
- [File Permissions Guide](file-permissions.md) - Complete permission setup

## 📝 Content Customization

### Content Structure
Customize how content is organized and displayed:

**Content Types:**
- **Pages**: Static content pages
- **Blog Posts**: Time-based content
- **Custom Content**: User-defined content types

**Content Fields:**
- **Title**: Page/post title
- **Content**: Main content (Markdown supported)
- **Meta**: Custom metadata
- **Tags**: Content categorization
- **Published Date**: Publication timing

### Template Variables
Access dynamic content in templates:

**Page Variables:**
- `{{title}}` - Page title
- `{{content}}` - Page content
- `{{url}}` - Page URL
- `{{meta}}` - Page metadata

**Site Variables:**
- `{{siteName}}` - Site name
- `{{siteDescription}}` - Site description
- `{{currentYear}}` - Current year
- `{{theme}}` - Active theme name

**Menu Variables:**
- `{{menu.main}}` - Main navigation menu
- `{{menu.sidebar}}` - Sidebar menu
- `{{menu.footer}}` - Footer menu

## 🎯 Advanced Customization

### Modular Templates
Break down templates into reusable components:

**Module Syntax:**
```html
{{module=header.html}}
{{module=footer.html}}
{{module=navigation.html}}
```

**Benefits:**
- **Reusability**: Use components across multiple templates
- **Maintainability**: Update components in one place
- **Organization**: Keep related code together
- **Consistency**: Ensure uniform appearance

**Getting Started:**
- [Modular Templates Guide](modular-templates.md) - Complete modular system guide

### SASS/SCSS Support
Use SASS for advanced CSS development:

**Features:**
- **Variables**: Define reusable values
- **Mixins**: Reusable CSS patterns
- **Nesting**: Organized CSS structure
- **Functions**: Dynamic CSS generation

**Getting Started:**
- [SASS Theme Guide](sass-theme-guide.md) - SASS integration tutorial

### Custom JavaScript
Add interactive functionality:

**Integration Methods:**
- **Theme Assets**: Include in theme's `assets/js/` directory
- **Inline Scripts**: Add directly to templates
- **Plugin Integration**: Load via plugins

**Best Practices:**
- **Progressive Enhancement**: Ensure functionality without JavaScript
- **Performance**: Minimize and optimize scripts
- **Accessibility**: Follow accessibility guidelines

## 🔧 Development Workflow

### Local Development
Set up a development environment:

1. **Install FearlessCMS** locally
2. **Configure Development Mode** for debugging
3. **Set Proper Permissions** for file operations
4. **Create Test Content** for development
5. **Use Version Control** for theme/plugin development

### Testing
Ensure your customizations work correctly:

- **Cross-browser Testing**: Check in multiple browsers
- **Mobile Testing**: Verify responsive design
- **Performance Testing**: Optimize loading times
- **Security Testing**: Validate input handling

### Deployment
Deploy customizations to production:

1. **Switch to Production Mode** for performance
2. **Set Proper Permissions** for security
3. **Optimize Assets** (minify CSS/JS, compress images)
4. **Test Thoroughly** before going live
5. **Monitor Performance** after deployment

## 📚 Resources

### Documentation
- [Creating Themes](creating-themes.md) - Theme development tutorial
- [Plugin Development](plugin-development-guide.md) - Plugin creation guide
- [Template Reference](theme-templates-reference.md) - Template syntax reference
- [Theme Options](theme-options-guide.md) - Theme customization options
- [Modular Templates](modular-templates.md) - Component system guide
- [SASS Integration](sass-theme-guide.md) - Advanced CSS development
- [CMS Modes](cms-modes.md) - System configuration
- [File Permissions](file-permissions.md) - Security setup

### Examples
- [Theme Examples](../themes/) - Browse existing themes
- [Plugin Examples](../plugins/) - Browse existing plugins
- [Nightfall Theme](../themes/nightfall/) - Complete theme example

### Community
- **GitHub Issues**: Report bugs and request features
- **Discussions**: Ask questions and share solutions
- **Contributions**: Submit improvements and new features

## 🎯 Best Practices

### Security
- **Input Validation**: Always validate user input
- **File Permissions**: Use proper ownership and permissions
- **HTTPS**: Use secure connections in production
- **Regular Updates**: Keep FearlessCMS and plugins updated

### Performance
- **Asset Optimization**: Minify CSS/JS, compress images
- **Caching**: Implement appropriate caching strategies
- **Database Optimization**: Optimize database queries
- **CDN Usage**: Use content delivery networks for assets

#### Built-in Page Caching
FearlessCMS includes a built-in file-based page caching system for public (non-logged-in) pages. When a public page is requested, the generated HTML is saved as a static file in the `cache/` directory and served for subsequent requests for up to 5 minutes. This greatly improves performance and reduces server load. The cache is automatically cleared whenever content is updated via the admin interface, ensuring visitors always see the latest version of your site.

### Maintainability
- **Code Organization**: Use modular templates and clear structure
- **Documentation**: Document custom code and configurations
- **Version Control**: Use Git for all customizations
- **Testing**: Test thoroughly before deployment

### Accessibility
- **Semantic HTML**: Use proper HTML structure
- **ARIA Labels**: Add accessibility attributes
- **Keyboard Navigation**: Ensure keyboard accessibility
- **Color Contrast**: Maintain sufficient color contrast

---

**Happy customizing!** 🚀

*This guide covers the main customization options in FearlessCMS. For detailed information on specific topics, refer to the individual documentation files.*
