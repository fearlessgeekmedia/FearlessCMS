# Sidebar Template for Default Theme

This theme now includes a sidebar template that provides a two-column layout with main content and a sidebar area.

## Available Templates

### 1. `page-with-sidebar.html`
A basic page template that includes a sidebar alongside the main content.

### 2. `sidebar-demo.html`
A demonstration template showing the sidebar functionality with sample content.

## How to Use

1. **Select the Template**: In the admin interface, when creating or editing a page, select either "Sidebar Demo" or "Page with Sidebar" as the template.

2. **Configure Widgets**: The sidebar will automatically display widgets from the `left-sidebar` configuration in `config/sidebars.json`.

3. **Add Sidebar Menu**: Optionally add a sidebar menu through the menu management system.

## Sidebar Features

### Widget Support
The sidebar automatically displays widgets configured in the `left-sidebar` section:
- Documentation navigation widgets
- Custom content widgets
- Any other widget types you create

### Navigation
- Shows sidebar menu items if a sidebar menu is configured
- Displays related pages (child pages) if they exist

### Responsive Design
- **Desktop**: Sidebar appears on the right side with sticky positioning
- **Mobile**: Sidebar stacks above content for better mobile experience
- **Tablet**: Responsive breakpoint at 1024px

## File Structure

```
themes/default/templates/
├── page-with-sidebar.html    # Main sidebar template
├── sidebar-demo.html         # Demo template
├── sidebar.html.mod          # Sidebar module (reusable)
└── ... other templates

themes/default/assets/
└── style.css                 # Contains sidebar CSS styles
```

## Customization

### Modifying Sidebar Content
Edit `sidebar.html.mod` to change what appears in the sidebar:
- Add new widget types
- Modify navigation structure
- Change sidebar sections

### Styling
Modify the CSS variables in `style.css`:
- Colors: `--color-bg`, `--color-surface`, `--color-text`
- Spacing: Adjust padding and margins
- Shadows: `--shadow-sm`, `--shadow-md`
- Border radius: `--radius-md`

### Layout Adjustments
The sidebar layout uses CSS Grid:
- Main content: `1fr` (flexible width)
- Sidebar: `300px` (fixed width)
- Gap: `2rem` between content and sidebar

## Widget Configuration

The sidebar reads from `config/sidebars.json`:

```json
{
    "left-sidebar": {
        "name": "Left Sidebar",
        "widgets": [
            {
                "type": "documentation-nav",
                "title": "Documentation",
                "content": "documentation-nav"
            }
        ]
    }
}
```

## Browser Support

- Modern browsers with CSS Grid support
- Responsive design works on all screen sizes
- Graceful fallback for older browsers

## Troubleshooting

### Sidebar Not Appearing
- Check that you've selected a sidebar template
- Verify the template file exists in the templates directory
- Check for any PHP errors in the error log

### Layout Issues
- Ensure CSS is properly loaded
- Check for conflicting CSS rules
- Verify the sidebar module is included correctly

### Widgets Not Showing
- Check the `config/sidebars.json` file
- Verify widget types are supported in `sidebar.html.mod`
- Check admin widget configuration

## Examples

See `sidebar-demo.html` for a complete example of how to use the sidebar template with sample content and explanations. 