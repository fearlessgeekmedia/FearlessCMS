# Parallax Sections Plugin

## Overview
The Parallax Sections plugin creates engaging parallax scrolling effects for website sections using shortcodes. It applies background images to content blocks and moves them at different speeds than the foreground content, creating a 3D depth illusion.

## Features

✅ **Reliable Image Coverage** - Background images maintain full coverage during all scroll positions  
✅ **Smooth Performance** - Hardware-accelerated animations with optimized JavaScript  
✅ **Responsive Design** - Works seamlessly across all device sizes  
✅ **Theme Integration** - Automatically adapts to light/dark themes  
✅ **Content Separation** - Content stays in markdown files, not hardcoded in templates  

## Usage

### Basic Shortcode Structure
```
[parallax_section id="your-unique-id" background_image="/path/to/your/image.jpg" speed="0.5" effect="scroll"]
    <!-- Your content goes here -->
    <p>This is the content that will scroll over the parallax background.</p>
[/parallax_section]
```

### Required Attributes

*   **`id`** (Required): A unique identifier for the parallax section.
    *   **Example**: `id="hero-section"`
    *   **Note**: Must be unique across the entire page

*   **`background_image`** (Required): The URL to the background image.
    *   **Example**: `background_image="/uploads/my-parallax-background.jpg"`
    *   **Supported formats**: JPG, PNG, WebP, SVG
    *   **Recommended**: High-resolution images (1920x1080 or larger)

### Optional Attributes

*   **`speed`** (Optional): Controls parallax scroll speed. Default: `0.5`
    *   `0.0` = Background stays fixed (classic parallax)
    *   `0.3` = Subtle movement (recommended for hero sections)
    *   `0.5` = Moderate movement (default)
    *   `0.8` = More pronounced movement
    *   **Example**: `speed="0.3"`

*   **`effect`** (Optional): Animation effect type. Default: `"scroll"`
    *   `"scroll"`: Standard parallax scrolling effect
    *   **Example**: `effect="scroll"`

*   **`overlay_color`** (Optional): Text overlay color for better readability.
    *   **Color names**: `red`, `blue`, `green`, `black`, `white`
    *   **Hex codes**: `#0066cc`, `#ff0000`
    *   **RGBA**: `rgba(0,0,0,0.5)`
    *   **Example**: `overlay_color="rgba(0,0,0,0.6)"`

*   **`overlay_opacity`** (Optional): Overlay transparency. Default: `0.4`
    *   Range: `0.0` (transparent) to `1.0` (opaque)
    *   **Example**: `overlay_opacity="0.7"`

## Examples

### Hero Section with Dark Overlay
```
[parallax_section id="hero" background_image="/uploads/hero-bg.jpg" speed="0.3" overlay_color="rgba(0,0,0,0.5)" overlay_opacity="0.5"]
# Welcome to Our Site
<span style="color:white;">Experience stunning parallax effects that bring your content to life</span>
[Get Started](/about)
[/parallax_section]
```

### About Section with Blue Overlay
```
[parallax_section id="about" background_image="/uploads/about-bg.jpg" speed="0.4" overlay_color="#0066cc" overlay_opacity="0.6"]
## About Our Company
Our story with a blue overlay for better readability
[/parallax_section]
```

### Services Section with Red Overlay
```
[parallax_section id="services" background_image="/uploads/services-bg.jpg" speed="0.5" overlay_color="red" overlay_opacity="0.7"]
## Our Services
Professional services with red overlay for emphasis
[/parallax_section]
```

## Technical Details

### CSS Architecture
- **Oversized backgrounds**: Images are 120% of section size for guaranteed coverage
- **Centered positioning**: Uses CSS transforms for perfect centering
- **Hardware acceleration**: `transform: translateZ(0)` for smooth performance
- **Responsive sizing**: Automatically adapts to different screen sizes

### JavaScript Performance
- **Viewport-aware calculations**: Only processes visible sections
- **Optimized transforms**: Minimal DOM manipulation
- **Smooth transitions**: Hardware-accelerated animations
- **Memory efficient**: Clean event handling and cleanup

### Browser Compatibility
- **Modern browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Mobile devices**: iOS Safari, Chrome Mobile, Samsung Internet
- **Fallbacks**: Graceful degradation for older browsers

## Best Practices

### Image Selection
- **High resolution**: Use images at least 1920x1080 pixels
- **Optimized files**: Compress images for faster loading
- **Aspect ratios**: Consider how images will crop on different devices
- **File formats**: WebP for modern browsers, JPG/PNG for compatibility

### Content Styling
- **Text contrast**: Use light text on dark backgrounds or vice versa
- **Text shadows**: Add shadows for better readability over images
- **Content spacing**: Provide adequate padding around text content
- **Responsive text**: Use relative font sizes for mobile compatibility

### Performance Tips
- **Image optimization**: Compress background images appropriately
- **Section count**: Limit to 3-5 parallax sections per page
- **Scroll speed**: Use lower speed values (0.3-0.5) for subtle effects
- **Content length**: Keep sections reasonably sized for smooth scrolling

## Troubleshooting

### Common Issues

**Images not covering sections completely**
- Ensure images are high resolution (1920x1080+)
- Check that `background-size: cover` is working
- Verify no conflicting CSS is overriding parallax styles

**Performance issues**
- Reduce the number of parallax sections
- Use lower speed values
- Optimize background images (compress, use WebP)

**Text not readable**
- Add `overlay_color` and `overlay_opacity` attributes
- Use text shadows in your content
- Ensure sufficient contrast between text and background

### Debug Mode
Enable debug logging by setting the environment variable:
```bash
export FCMS_DEBUG=true
```

This will log detailed information about parallax processing to help troubleshoot issues.

## Export Support

The parallax plugin now includes full support for static site export. When you export your site using the export script, all parallax sections are automatically processed and converted to fully functional HTML with CSS and JavaScript.

### Export Features

- **Automatic Processing** - Parallax shortcodes are converted to HTML during export
- **CSS Generation** - Complete parallax styles are generated automatically
- **JavaScript Generation** - Interactive parallax effects are included
- **Asset Management** - CSS and JS files are automatically included on pages with parallax
- **Performance Optimized** - Export generates optimized, production-ready assets

### Export Process

When you run the export script:

1. All parallax shortcodes are processed and converted to HTML
2. Parallax CSS is generated with responsive design support
3. Parallax JavaScript is generated with performance optimizations
4. Assets are automatically included on pages that need them
5. Static site is generated with fully functional parallax effects

### Generated Files

The export process creates these files:

- `export/assets/parallax.css` - Complete parallax styling
- `export/assets/parallax.js` - Interactive parallax functionality

### Export Example

Run the export script to generate your static site:

```bash
npx node export.js
```

Your exported site will include fully functional parallax sections that work exactly like the live CMS version.

## Content Management

### Template Integration
- **Content separation**: Parallax sections belong in HTML content files, not templates
- **Dynamic rendering**: Plugin automatically processes shortcodes in content
- **Theme compatibility**: Works with all FearlessCMS themes
- **Plugin independence**: No theme modifications required
- **Export support**: Full static site generation with parallax effects

### File Organization
```
content/
├── home.md          # Contains parallax shortcodes (now HTML format)
├── about.md         # Other content files
└── blog/
    └── post-1.md    # Blog posts with parallax sections

themes/
└── default/         # Theme templates (no parallax code needed)
```

## Recent Improvements

### v2.0 - Enhanced Coverage & Performance
- **Fixed image coverage issues** - Backgrounds now maintain full coverage during all scroll positions
- **Improved CSS architecture** - Clean, conflict-free styling with proper specificity
- **Enhanced JavaScript** - Viewport-aware calculations and smooth performance
- **Better dark mode support** - Automatic theme adaptation
- **Content separation** - Moved from hardcoded templates to markdown files

### Technical Fixes
- **CSS specificity issues resolved** - Dark mode styles now work correctly
- **Image positioning improved** - Centered backgrounds with proper overflow handling
- **Performance optimized** - Hardware acceleration and smooth transitions
- **Mobile responsive** - Better coverage on all device sizes

## Future Enhancements

- **Additional effects**: Fade, zoom, and rotation animations
- **Video backgrounds**: Support for MP4 and WebM files
- **Advanced overlays**: Gradient and pattern overlays
- **Performance monitoring**: Built-in performance metrics
- **Accessibility**: ARIA labels and keyboard navigation support

---

*The Parallax Sections plugin is designed to be reliable, performant, and easy to use. It automatically handles the complexities of image coverage, scroll performance, and responsive design while maintaining clean, maintainable code.*
