# HTML Editor Guide

FearlessCMS now features a powerful **HTML editing system** that supports both HTML content creation and Markdown content display, with HTML as the default editing mode.

## Overview

We've enhanced FearlessCMS to support **HTML content creation** while maintaining **Markdown content compatibility**:

- **HTML Mode (Default)** - Rich WYSIWYG editor with code view capabilities
- **Markdown Support** - Can read and display Markdown files created in external editors
- **Dual Mode Toggle** - Switch between rich editor and code view in HTML mode
- **Automatic Content Sync** - Changes sync between editor modes
- **Backwards Compatibility** - All existing Markdown content continues to work perfectly

## Why HTML Editing with Markdown Support?

The decision to use HTML editing while maintaining Markdown compatibility was driven by several key factors:

- **User Choice** - Some users prefer external Markdown editing, others want HTML's power
- **Layout Preservation** - HTML mode maintains columns, rows, and complex structures perfectly
- **No Escaping Issues** - HTML mode eliminates shortcode escaping problems
- **Better Control** - HTML mode provides precise formatting and layout control
- **WYSIWYG Experience** - HTML mode shows exactly what your content will look like
- **Professional Interface** - Clean, intuitive design for content creators
- **Backwards Compatibility** - Existing Markdown content continues to work without conversion

## Editor Modes

### HTML Mode (Default)

The HTML editor provides a full-featured WYSIWYG experience with:

- **Formatting Toolbar** - Bold, italic, headers, lists, links, images
- **Real-time Preview** - See changes as you type
- **Drag & Drop** - Easy image and file insertion
- **Keyboard Shortcuts** - Power user efficiency
- **Code View Toggle** - Switch to raw HTML editing when needed

### Markdown Support

Markdown content created in external editors:

- **External Creation** - Use your preferred Markdown editor (VS Code, Typora, etc.)
- **File Upload** - Upload Markdown files to your content directory
- **Version Control Friendly** - Easy to track changes in external editors
- **Existing Content** - All your current Markdown content works perfectly

### Dual Mode Toggle (HTML Mode Only)

In HTML mode, you can switch between rich editor and code view:

- **Rich Editor Mode** - Full WYSIWYG experience
- **Code View Mode** - Raw HTML editing for precise control

## Content Creation Workflows

### HTML Content Creation

Create HTML content directly in the CMS:

- **Admin Panel** - Use the HTML editor for new content
- **Per-Page Settings** - Configure editor preferences per page
- **Content Migration** - Convert existing content to HTML as needed

### HTML Editor Toggle

Within the HTML editor, switch between rich editor and code view:

- **Toggle Button** - Click the mode toggle button in the editor
- **Keyboard Shortcut** - Use `Ctrl+Shift+C` to switch modes
- **Automatic Sync** - Content automatically syncs between modes

## Content Conversion and Compatibility

### Existing Content

All existing content continues to work perfectly:

- **Markdown Content** - Automatically detected and rendered from external files
- **HTML Content** - New default format with full feature support
- **Mixed Content** - Both formats can coexist in the same site
- **No Data Loss** - All content is preserved during content type changes

### Migration Options

You have several options for content migration:

- **Keep as-is** - Leave existing Markdown content unchanged
- **Convert to HTML** - Transform Markdown to HTML for new features
- **Hybrid approach** - Use HTML for new content, keep Markdown for existing
- **Automatic detection** - System automatically detects and handles both formats

## Feature Cards and Layout

Both content types maintain all your existing layouts:

- **Feature Card Grid** - 2-column responsive grid layout
- **CSS Styling** - All custom CSS is preserved
- **Responsive Design** - Mobile and tablet layouts maintained
- **Hover Effects** - Interactive elements work perfectly

## Shortcode Support

All existing shortcodes continue to work with both content types:

- **Parallax Sections** - Background images with scroll effects
- **Custom Attributes** - Speed, overlay colors, opacity
- **Content Wrapping** - Proper structure generation
- **Export Support** - Static site generation with parallax effects

## Export System

The export system supports both content types:

- **Dual-mode processing** - Handles both HTML and Markdown content
- **Parallax Support** - Generates CSS and JavaScript for parallax effects
- **Asset Management** - Automatic CSS/JS inclusion
- **Static Site Generation** - Fully functional exported sites

## Keyboard Shortcuts

### HTML Mode Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+Shift+C` | Toggle between rich editor and code view |
| `Ctrl+B` | Bold text |
| `Ctrl+I` | Italic text |
| `Ctrl+K` | Insert link |

### Markdown Mode Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+B` | Bold text |
| `Ctrl+I` | Italic text |
| `Ctrl+K` | Insert link |

## Best Practices

### Choosing Your Content Creation Method

- **Use HTML Editor** when you need:
  - Complex layouts and formatting
  - WYSIWYG editing experience
  - Advanced styling control
  - Feature-rich content creation

- **Use External Markdown Editor** when you prefer:
  - Simple text-based editing
  - Version control friendly content
  - Lightweight editing experience
  - Familiar Markdown syntax

### Content Management

- **Consistency** - Choose one mode per project for consistency
- **Team Collaboration** - Ensure team members understand the chosen mode
- **Documentation** - Document your mode choice for future reference
- **Training** - Provide appropriate training for your chosen mode

### Migration Strategy

- **Gradual Migration** - Convert content gradually rather than all at once
- **Testing** - Test converted content thoroughly before going live
- **Backups** - Always backup content before mode changes
- **User Training** - Train users on the new mode before switching 