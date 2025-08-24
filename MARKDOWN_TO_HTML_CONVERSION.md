# Markdown to HTML Conversion Guide

## Overview

This guide explains how to convert your existing Markdown files to HTML format for use with the new Quill.js HTML editor.

## Why Convert to HTML?

- **Better Layout Control** - Preserve columns, rows, and complex layouts
- **No Escaping Issues** - Shortcodes work perfectly without backslashes
- **WYSIWYG Editing** - See exactly what your content will look like
- **Rich Formatting** - Full HTML formatting capabilities
- **No Conversion Loss** - Content structure is preserved

## Conversion Process

### 1. Run the Conversion Script

```bash
php convert_markdown_to_html.php
```

### 2. What the Script Does

- **Backs up** all original Markdown files with timestamp
- **Converts** Markdown syntax to HTML
- **Updates** metadata to set `editor_mode: "html"`
- **Preserves** all your existing content and structure
- **Maintains** shortcodes and custom HTML

### 3. Backup Location

Your original files are backed up to:
```
content_backup_YYYY-MM-DD_HH-MM-SS/
```

## What Gets Converted

| Markdown | HTML |
|----------|------|
| `# Header` | `<h1>Header</h1>` |
| `**Bold**` | `<strong>Bold</strong>` |
| `*Italic*` | `<em>Italic</em>` |
| `[Link](url)` | `<a href="url">Link</a>` |
| `![Alt](image.jpg)` | `<img src="image.jpg" alt="Alt">` |
| `> Quote` | `<blockquote>Quote</blockquote>` |
| `- List item` | `<li>List item</li>` |
| `` `code` `` | `<code>code</code>` |

## After Conversion

### 1. Test the Editor

- Edit a page using the new Quill.js HTML editor
- Verify that your content displays correctly
- Check that shortcodes work without escaping

### 2. Update Templates (if needed)

If you have templates that expect Markdown, you may need to update them to handle HTML content.

### 3. Content Management

- **New content** will be created in HTML format
- **Existing content** is now in HTML format
- **Shortcodes** work perfectly without issues

## Benefits of HTML Mode

✅ **Layout Preservation** - Columns, rows, and complex structures maintained  
✅ **No Escaping Issues** - Shortcodes work cleanly  
✅ **Rich Editing** - Full formatting toolbar  
✅ **WYSIWYG** - See content as it will appear  
✅ **Better Performance** - No conversion processing needed  

## Troubleshooting

### If Conversion Fails

1. Check the backup directory for original files
2. Verify file permissions
3. Check PHP error logs
4. Run the script again if needed

### If Content Looks Wrong

1. Check the backup for original content
2. Verify HTML conversion in the files
3. Test the editor with simple content first

## Reverting (if needed)

If you need to revert to Markdown:

1. Copy files from the backup directory
2. Update metadata to set `editor_mode: "easy"`
3. Note: This will lose any HTML formatting

## Support

For issues with the conversion process:
1. Check the backup directory first
2. Review the conversion script output
3. Verify file permissions and PHP setup

---

**Note**: This conversion is one-way. Once converted to HTML, you'll be using the HTML editor going forward. Make sure to test thoroughly before converting production content. 