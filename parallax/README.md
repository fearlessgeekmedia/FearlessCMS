# Parallax Plugin for FearlessCMS

A powerful parallax scrolling effects plugin that adds dynamic, engaging visual elements to your website content.

## Features

- **Multiple Parallax Effects**: Scroll, fixed, scale, rotate, fade, fade-in, blur, slide, and zoom effects
- **Customizable Attributes**: Speed, overlay colors, opacity, and more
- **CSS Class Support**: Add custom CSS classes for styling flexibility
- **Custom ID Support**: Additional identifiers for JavaScript and CSS targeting
- **Responsive Design**: Optimized for all device sizes
- **Performance Optimized**: Efficient scroll handling and smooth animations

## Installation

1. Copy the `parallax` folder to your `plugins/` directory
2. The plugin will be automatically loaded by FearlessCMS
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
- `class`: Custom CSS classes (new feature)
- `custom_id`: Additional identifier attribute (new feature)

### New Features: CSS Classes and Custom IDs

#### CSS Class Support
Add custom CSS classes to style your parallax sections:

```
[parallax_section id="hero" background_image="/hero.jpg" class="hero-section dark-theme custom-spacing"]
    Content here
[/parallax_section]
```

#### Custom ID Support
Add an additional identifier for JavaScript or CSS targeting:

```
[parallax_section id="hero" background_image="/hero.jpg" custom_id="main-hero-section"]
    Content here
[/parallax_section]
```

## Available Effects

### 1. Scroll (Default)
Traditional parallax scrolling effect where background moves at different speed than content.

### 2. Fixed
Background stays fixed while content scrolls over it.

### 3. Scale
Background scales up/down during scroll for dynamic zoom effects.

### 4. Rotate
Background rotates during scroll for unique visual effects.

### 5. Fade
Background opacity changes during scroll.

### 6. Fade-in
Content gradually appears as you scroll down (with configurable parameters).

### 7. Blur
Background starts clear and gets blurrier during scroll.

### 8. Slide
Background slides horizontally during scroll.

### 9. Zoom
Background zooms in/out during scroll.

## Advanced Usage Examples

### Hero Section with Custom Classes
```
[parallax_section id="hero" background_image="/hero.jpg" speed="0.3" effect="scroll" class="hero-parallax dark-theme"]
    <div style="text-align: center; padding: 150px 0; color: white;">
        <h1>Welcome to Our Website</h1>
        <p>Experience stunning parallax effects</p>
        <a href="/about" class="btn btn-primary">Learn More</a>
    </div>
[/parallax_section]
```

### Services Section with Multiple Effects
```
[parallax_section id="services" background_image="/services.jpg" speed="0.4" effect="fade-in" class="services-section light-overlay"]
    <div style="text-align: center; padding: 100px 0;">
        <h2>Our Services</h2>
        <div class="services-grid">
            <div class="service-card">Web Design</div>
            <div class="service-card">Development</div>
            <div class="service-card">Consulting</div>
        </div>
    </div>
[/parallax_section]
```

### Contact Section with Custom ID
```
[parallax_section id="contact" background_image="/contact.jpg" speed="0.6" effect="fixed" class="contact-section" custom_id="main-contact-form"]
    <div style="text-align: center; padding: 100px 0;">
        <h2>Get In Touch</h2>
        <p>Ready to start your next project?</p>
        <form class="contact-form">
            <!-- Form fields here -->
        </form>
    </div>
[/parallax_section]
```

## Customization

### CSS Styling
The plugin automatically generates CSS classes for each parallax section. You can override or extend these styles:

```css
/* Custom styling for hero parallax sections */
.hero-parallax {
    min-height: 600px;
}

/* Custom styling for dark theme */
.dark-theme .parallax-content {
    background: rgba(0,0,0,0.7);
}

/* Custom spacing for specific sections */
.custom-spacing .parallax-content {
    padding: 4rem 2rem;
}
```

### JavaScript Events
You can target parallax sections using the custom IDs or classes:

```javascript
// Target by custom ID
const heroSection = document.querySelector('[data-custom-id="main-hero-section"]');

// Target by custom class
const heroSections = document.querySelectorAll('.hero-parallax');

// Add custom event listeners
heroSection.addEventListener('click', function() {
    console.log('Hero section clicked!');
});
```

## Performance Tips

1. **Image Optimization**: Use compressed, appropriately sized images
2. **Limit Sections**: Don't use more than 3-4 parallax sections per page
3. **Speed Values**: Use lower speed values (0.3-0.5) for better performance
4. **Mobile Testing**: Test on mobile devices as effects may behave differently

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (with some effect limitations)

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and permissions
2. **Effects not working**: Ensure JavaScript is enabled
3. **Performance issues**: Reduce number of parallax sections or use lower speed values
4. **Mobile issues**: Some effects may be disabled on mobile for performance

### Debug Mode
Enable debug mode by setting `FCMS_DEBUG=true` in your environment to see detailed logging.

## Changelog

### Version 2.1
- Added CSS class support (`class` attribute)
- Added custom ID support (`custom_id` attribute)
- Improved attribute parsing
- Enhanced documentation

### Version 2.0
- Multiple parallax effects
- Configurable overlay colors and opacity
- Fade-in effect with configurable parameters
- Performance improvements

### Version 1.0
- Basic parallax scrolling effect
- Simple overlay support

## Support

For issues or questions, please check the documentation or create an issue in the repository.

## License

This plugin is part of FearlessCMS and follows the same licensing terms. 