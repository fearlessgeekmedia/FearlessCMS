<!-- json {
    "title": "Documentation System",
    "template": "documentation"
} -->

# Documentation System Overview

This document explains how the FearlessCMS documentation system is organized and how to use it effectively.

## System Architecture

The documentation system is built on top of FearlessCMS's content management capabilities, using markdown files with JSON frontmatter for metadata and a custom documentation template for consistent presentation.

### File Structure

```
content/
└── documentation/
    ├── documentation.md              # Main documentation index
    ├── install.md                    # Installation guide
    ├── gettingstarted.md             # Getting started guide
    ├── creating-themes.md            # Theme creation guide
    ├── plugin-development-guide.md   # Plugin development
    ├── cms-modes.md                  # CMS modes explanation
    ├── file-permissions.md           # File permissions guide
    ├── theme-development-workflow.md # Theme development workflow
    ├── theme-templates-reference.md  # Template reference
    ├── theme-options-guide.md        # Theme options guide
    ├── sass-theme-guide.md          # SASS theme guide
    ├── modular-templates.md          # Modular templates
    ├── theme-thumbnails-guide.md     # Theme thumbnails
    ├── theme-development-index.md    # Theme development index
    ├── customization-overview.md     # Customization overview
    ├── ad-area-system.md            # AD area system
    ├── parallax-plugin.md            # Parallax plugin
    └── documentation-nav.md          # Navigation component
```

### Template System

The documentation uses a custom `documentation.html` template located at `themes/default/templates/documentation.html`. This template provides:

- **Responsive Layout**: Two-column layout with sidebar navigation
- **Sticky Sidebar**: Navigation that stays visible while scrolling
- **Breadcrumb Navigation**: Shows current location in documentation
- **Consistent Styling**: Professional appearance for all documentation pages
- **Mobile Responsive**: Adapts to different screen sizes

## Adding New Documentation

### 1. Create the Markdown File

Create a new `.md` file in the `content/documentation/` directory with the following structure:

```markdown
<!-- json {
    "title": "Your Document Title",
    "template": "documentation"
} -->

# Your Document Title

Your content here...
```

### 2. Update Navigation

Add your new document to the navigation in the documentation template (`themes/default/templates/documentation.html`) and update the main documentation index (`content/documentation.md`).

### 3. Test the Page

Visit `/documentation/your-page-name` to verify the page is accessible and displays correctly.

## Documentation Features

### JSON Frontmatter

Each documentation file includes JSON metadata in HTML comments:

```html
<!-- json {
    "title": "Page Title",
    "template": "documentation",
    "description": "Optional description",
    "keywords": "optional, keywords"
} -->
```

### Markdown Support

The documentation system supports full markdown syntax including:

- **Headers**: `#`, `##`, `###`
- **Lists**: Bulleted and numbered lists
- **Code Blocks**: Inline `code` and fenced code blocks
- **Links**: `[text](url)` format
- **Images**: `![alt](url)` format
- **Tables**: Standard markdown table syntax

### Internal Linking

Link between documentation pages using relative URLs:

```markdown
[Installation Guide](documentation/install)
[Creating Themes](documentation/creating-themes)
```

## Customization

### Theme Integration

The documentation template can be customized by:

1. **Copying the template** to your theme's templates directory
2. **Modifying the CSS** in the `<style>` section
3. **Updating the navigation** structure
4. **Adding theme-specific elements**

### Styling

The documentation template includes comprehensive CSS for:

- **Typography**: Consistent heading and text styles
- **Code blocks**: Syntax highlighting appearance
- **Navigation**: Sidebar and breadcrumb styling
- **Responsive design**: Mobile-friendly layouts

## Best Practices

### Content Organization

1. **Clear Structure**: Use consistent heading hierarchy
2. **Table of Contents**: Include TOC for long documents
3. **Cross-references**: Link related documentation
4. **Examples**: Provide practical code examples
5. **Screenshots**: Include visual aids when helpful

### File Naming

- Use descriptive, lowercase names
- Separate words with hyphens
- Keep names concise but clear
- Avoid special characters

### Metadata

- Always include a descriptive title
- Use the `documentation` template
- Add descriptions for SEO when appropriate
- Include relevant keywords

## Maintenance

### Regular Updates

- Review documentation monthly
- Update outdated information
- Add new features and changes
- Remove deprecated content

### Quality Assurance

- Test all internal links
- Verify code examples work
- Check mobile responsiveness
- Validate markdown syntax

---

*This documentation system is designed to be maintainable, extensible, and user-friendly. For questions or suggestions about improving the documentation, please contribute to the project.* 