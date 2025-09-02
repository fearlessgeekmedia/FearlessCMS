# Parallax Sections Plugin

A powerful parallax scrolling effects plugin for FearlessCMS that adds dynamic, engaging visual elements to your website content.

## Features

- **Multiple Parallax Effects**: Scroll, fixed, scale, rotate, fade, fade-in, blur, slide, and zoom effects
- **Customizable Attributes**: Speed, overlay colors, opacity, and more
- **CSS Class Support**: Add custom CSS classes for styling flexibility
- **Custom ID Support**: Additional identifiers for JavaScript and CSS targeting
- **Responsive Design**: Optimized for all device sizes
- **Performance Optimized**: Efficient scroll handling and smooth animations
- **Theme Integration**: Automatically adapts to light/dark themes

## Installation

1. The plugin is automatically loaded by FearlessCMS when placed in the `plugins/parallax/` directory
2. Ensure the plugin is activated in your `config/active_plugins.json` file
3. No additional configuration required

## Usage

### Basic Syntax

```
[parallax_section id="unique-id" background_image="/path/to/image.jpg" speed="0.5" effect="scroll"]
    Your content here
[/parallax_section]
```

### Required Attributes

- `id`: Unique identifier for the section (required)
- `background_image`: Path to the background image (required)

### Optional Attributes

- `speed`: Parallax speed (0.1 to 1.0, default: 0.5)
- `effect`: Parallax effect type (default: scroll)
- `overlay_color`: Overlay color (default: rgba(0,0,0,0.4))
- `overlay_opacity`: Overlay opacity (0.0 to 1.0, default: 0.4)
- `class`: Custom CSS classes
- `custom_id`: Additional identifier attribute

### Examples

#### Hero Section with Dark Overlay
```
[parallax_section id="hero" background_image="/uploads/hero-bg.jpg" speed="0.3" overlay_color="rgba(0,0,0,0.5)" overlay_opacity="0.5"]
# Welcome to Our Site
Experience stunning parallax effects that bring your content to life
[Get Started](/about)
[/parallax_section]
```

#### About Section with Blue Overlay
```
[parallax_section id="about" background_image="/uploads/about-bg.jpg" speed="0.4" overlay_color="#0066cc" overlay_opacity="0.6"]
## About Our Company
Our story with a blue overlay for better readability
[/parallax_section]
```

## Technical Details

### CSS Architecture
- **Oversized backgrounds**: Images maintain full coverage during all scroll positions
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

## Version History

### v2.1 - Current Version
- **Complete rewrite** for FearlessCMS compatibility
- **Improved plugin architecture** using proper hooks
- **Enhanced performance** with viewport-aware calculations
- **Better error handling** and validation
- **Responsive design** improvements
- **Dark mode support** integration

### v2.0 - Previous Version
- Fixed image coverage issues
- Improved CSS architecture
- Enhanced JavaScript performance
- Better dark mode support
- Content separation improvements

---

*The Parallax Sections plugin is designed to be reliable, performant, and easy to use. It automatically handles the complexities of image coverage, scroll performance, and responsive design while maintaining clean, maintainable code.*
