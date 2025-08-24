# HTML Editor Guide

FearlessCMS now features a powerful HTML editor system that provides both rich WYSIWYG editing and raw HTML code editing capabilities.

## Overview

We've transitioned from Markdown to HTML editing to provide better layout control, eliminate escaping issues, and offer a more professional editing experience. The new system includes:

- **Quill.js Rich Editor** - Full WYSIWYG experience with formatting toolbar
- **Code View Mode** - Raw HTML editing for precise control
- **Dual Mode Toggle** - Switch between rich editor and code view
- **Automatic Content Sync** - Changes sync between both modes

## Why HTML Instead of Markdown?

The decision to switch to HTML was driven by several key factors:

- **Layout Preservation** - Columns, rows, and complex structures are maintained perfectly
- **No Escaping Issues** - Shortcodes work without backslashes or special characters
- **Better Control** - Precise formatting and layout control
- **WYSIWYG Experience** - See exactly what your content will look like
- **Professional Interface** - Clean, intuitive design for content creators

## Editor Modes

### Rich Editor Mode (Default)

The rich editor provides a full-featured WYSIWYG experience with:

- **Formatting Toolbar** - Bold, italic, headers, lists, links, images
- **Real-time Preview** - See changes as you type
- **Drag & Drop** - Easy image and file insertion
- **Keyboard Shortcuts** - Power user efficiency

### Code View Mode

Switch to code view for:

- **Raw HTML Editing** - Direct HTML code manipulation
- **Custom HTML** - Insert custom HTML elements
- **Precise Control** - Fine-tune formatting and structure
- **Troubleshooting** - Debug layout and formatting issues

## Switching Between Modes

You can switch between editor modes using:

- **Toggle Button** - Click the mode toggle button in the editor
- **Keyboard Shortcut** - Use `Ctrl+Shift+C` to switch modes
- **Automatic Sync** - Content automatically syncs between modes

## Content Conversion

All existing Markdown content has been automatically converted to HTML format. The conversion process:

- **Preserves Structure** - All content and formatting is maintained
- **Updates Metadata** - Sets `editor_mode: "html"`
- **Creates Backups** - Original files are backed up with timestamps
- **Maintains Shortcodes** - Parallax and other shortcodes work perfectly

## Feature Cards and Layout

The HTML editor maintains all your existing layouts:

- **Feature Card Grid** - 2-column responsive grid layout
- **CSS Styling** - All custom CSS is preserved
- **Responsive Design** - Mobile and tablet layouts maintained
- **Hover Effects** - Interactive elements work perfectly

## Shortcode Support

All existing shortcodes continue to work:

- **Parallax Sections** - Background images with scroll effects
- **Custom Attributes** - Speed, overlay colors, opacity
- **Content Wrapping** - Proper HTML structure generation
- **Export Support** - Static site generation with parallax effects

## Export System

The export system has been updated to support HTML content:

- **HTML Processing** - No Markdown conversion needed
- **Parallax Support** - Generates CSS and JavaScript for parallax effects
- **Asset Management** - Automatic CSS/JS inclusion
- **Static Site Generation** - Fully functional exported sites

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+Shift+C` | Toggle between rich editor and code view |
| `Ctrl+B` | Bold text |
| `Ctrl+I` | Italic text |
| `Ctrl+K` | Insert link |

## Best Practices

### Content Creation

- Use the rich editor for most content creation
- Switch to code view for precise HTML adjustments
- Test your content in both modes to ensure consistency
- Use the preview function to verify final appearance

### Layout Management

- Feature cards automatically arrange in responsive grids
- Parallax sections maintain proper spacing and alignment
- CSS classes are preserved for consistent styling
- Mobile responsiveness is maintained automatically

### Shortcode Usage

- Parallax shortcodes work exactly as before
- All attributes are supported (id, background_image, speed, effect, overlay_color, overlay_opacity)
- Content within shortcodes is properly formatted
- Export generates all necessary CSS and JavaScript

## Troubleshooting

### Common Issues

- **Content Not Saving** - Check that you're in the correct editor mode
- **Formatting Lost** - Use code view to restore HTML structure
- **Layout Broken** - Verify feature card CSS classes are present
- **Shortcodes Not Working** - Check that shortcode syntax is correct

### Getting Help

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Verify that all required CSS files are loading
3. Test content in both editor modes
4. Check the export logs for any processing errors

## Migration Notes

If you're migrating from the old Markdown system:

- **Content Conversion** - Run the conversion script to update all files
- **Backup Creation** - Original files are automatically backed up
- **Template Updates** - Templates automatically handle HTML content
- **Export Testing** - Test exported sites to verify functionality

## Future Enhancements

Planned improvements to the HTML editor system:

- **Advanced Formatting** - More formatting options and styles
- **Template Library** - Pre-built content templates
- **Media Management** - Enhanced image and file handling
- **Collaboration Features** - Multi-user editing capabilities

## Conclusion

The new HTML editor system provides a significant upgrade to the content creation experience in FearlessCMS. With dual-mode editing, automatic content sync, and full shortcode support, you now have the tools to create professional, well-formatted content while maintaining all the flexibility and power of the previous system.

For questions or support with the new editor system, please refer to the troubleshooting section above or consult the FearlessCMS community resources. 