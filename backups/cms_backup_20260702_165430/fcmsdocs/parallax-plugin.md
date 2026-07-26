# Parallax Plugin for FearlessCMS

A powerful parallax scrolling effects plugin that adds dynamic, engaging visual elements to your website content.

## Features

- **Multiple Parallax Effects**: Scroll, fixed, scale, rotate, fade, fade-in, blur, slide, and zoom effects
- **Customizable Speed**: Adjust parallax intensity from 0.1 to 1.0
- **Overlay Controls**: Customizable overlay colors and opacity
- **Responsive Design**: Works seamlessly across all devices
- **Performance Optimized**: Efficient JavaScript with smooth animations
- **Dark Mode Support**: Automatic dark mode detection and styling

## Installation

1. Copy the `parallax` folder to your `plugins/` directory
2. Activate the plugin through the admin panel
3. Start using parallax shortcodes in your content

## Basic Usage

### Shortcode Syntax

The basic parallax shortcode syntax is:

```
[parallax_section id="unique-id" background_image="/path/to/image.jpg" speed="0.5" effect="scroll"]
Your content here
[/parallax_section]
```

### Required Attributes

- **`id`**: A unique identifier for the parallax section
- **`background_image`**: Path to the background image file

### Optional Attributes

- **`speed`**: Parallax speed (0.1 to 1.0, default: 0.5)
- **`effect`**: Parallax effect type (default: scroll)
- **`class`**: Custom CSS classes for styling
- **`custom_id`**: Alternative ID for the HTML element
- **`overlay_color`**: Custom overlay color (default: rgba(0,0,0,0.4))
- **`overlay_opacity`**: Overlay opacity (default: 0.4)

## Available Effects

### 1. Scroll Effect (Default)
Traditional parallax scrolling where background moves at different speed than content.

```
[parallax_section id="hero" background_image="/hero.jpg" speed="0.3" effect="scroll"]
# Hero Content
Your engaging hero text here
[/parallax_section]
```

### 2. Fade-In Effect
Content gradually fades in as you scroll down the page.

```
[parallax_section id="fade-demo" background_image="/demo.jpg" effect="fade-in" fade_start_percent="30" fade_distance="60" start_opacity="0.2" start_offset="30"]
# Fade In Content
This content will fade in as you scroll
[/parallax_section]
```

**Fade-In Specific Attributes:**
- **`fade_start_percent`**: When the fade starts (default: 30)
- **`fade_distance`**: How far to fade (default: 60)
- **`start_opacity`**: Starting opacity (default: 0.2)
- **`start_offset`**: Starting offset (default: 30)

### 3. Fixed Effect
Background image stays fixed while content scrolls over it.

```
[parallax_section id="fixed-bg" background_image="/fixed.jpg" effect="fixed"]
# Fixed Background
Content scrolls over a fixed background
[/parallax_section]
```

### 4. Scale Effect
Background image scales as you scroll.

```
[parallax_section id="scale-demo" background_image="/scale.jpg" effect="scale" speed="0.4"]
# Scale Effect
Background will scale as you scroll
[/parallax_section]
```

### 5. Rotate Effect
Background image rotates slightly as you scroll.

```
[parallax_section id="rotate-demo" background_image="/rotate.jpg" effect="rotate"]
# Rotate Effect
Background will rotate as you scroll
[/parallax_section]
```

### 6. Blur Effect
Background image blurs as you scroll.

```
[parallax_section id="blur-demo" background_image="/blur.jpg" effect="blur"]
# Blur Effect
Background will blur as you scroll
[/parallax_section]
```

### 7. Slide Effect
Content slides in from the side as you scroll.

```
[parallax_section id="slide-demo" background_image="/slide.jpg" effect="slide"]
# Slide Effect
Content will slide in from the side
[/parallax_section]
```

### 8. Zoom Effect
Background image zooms in/out as you scroll.

```
[parallax_section id="zoom-demo" background_image="/zoom.jpg" effect="zoom"]
# Zoom Effect
Background will zoom as you scroll
[/parallax_section]
```

## Advanced Usage

### Custom CSS Classes

Add custom CSS classes to style your parallax sections:

```
[parallax_section id="hero" background_image="/hero.jpg" class="hero-section dark-theme custom-spacing"]
# Custom Styled Hero
This section uses custom CSS classes
[/parallax_section]
```

### Custom IDs

Use custom IDs for specific styling or JavaScript targeting:

```
[parallax_section id="hero" background_image="/hero.jpg" custom_id="main-hero-section"]
# Custom ID Section
This section has a custom ID for targeting
[/parallax_section]
```

### Overlay Customization

Customize the overlay appearance:

```
[parallax_section id="custom-overlay" background_image="/image.jpg" overlay_color="rgba(255,0,0,0.3)" overlay_opacity="0.3"]
# Custom Overlay
This section has a red overlay with 30% opacity
[/parallax_section]
```

## Complete Examples

### Hero Section with Dark Overlay

```
[parallax_section id="hero" background_image="/hero.jpg" speed="0.3" effect="scroll" class="hero-parallax dark-theme"]
# Welcome to Our Site
## Experience the difference

This hero section demonstrates a classic parallax scroll effect with a dark overlay for better text readability.

[Get Started](#contact)
[/parallax_section]
```

### Services Section with Fade-In

```
[parallax_section id="services" background_image="/services.jpg" speed="0.4" effect="fade-in" class="services-section light-overlay"]
# Our Services
## Comprehensive Solutions

- **Web Development**
- **Design Services**
- **Consulting**
- **Support & Maintenance**

This section showcases the fade-in effect, where content gradually appears as you scroll down the page.
[/parallax_section]
```

### Contact Section with Fixed Background

```
[parallax_section id="contact" background_image="/contact.jpg" speed="0.6" effect="fixed" class="contact-section" custom_id="main-contact-form"]
# Get In Touch
## Ready to Start Your Project?

Contact us today to discuss your needs and discover how we can help transform your online presence.

[Contact Form or Contact Information]
[/parallax_section]
```

## CSS Customization

The plugin automatically generates CSS classes for each parallax section. You can override or extend these styles:

```css
/* Custom styling for hero parallax sections */
.hero-parallax {
    min-height: 500px;
    text-align: center;
}

.dark-theme .parallax-content {
    color: #ffffff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
}

.custom-spacing .parallax-content {
    padding: 4rem 2rem;
}

/* Premium theme styling */
.premium-theme .parallax-content {
    background: rgba(0,0,0,0.6);
    border-radius: 10px;
    padding: 2rem;
}
```

## JavaScript Integration

You can target parallax sections using the custom IDs or classes:

```javascript
// Target specific parallax sections
const heroSections = document.querySelectorAll('.hero-parallax');
const fadeSections = document.querySelectorAll('[data-effect="fade-in"]');

// Add custom animations
heroSections.forEach(section => {
    section.addEventListener('scroll', function() {
        // Custom scroll handling
    });
});

// Trigger custom events
fadeSections.forEach(section => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    });
    observer.observe(section);
});
```

## Performance Optimization

### Best Practices

1. **Limit Sections**: Don't use more than 3-4 parallax sections per page
2. **Optimize Images**: Use compressed images for better loading performance
3. **Appropriate Speeds**: Use lower speed values (0.1-0.3) for subtle effects
4. **Mobile Testing**: Test on mobile devices as parallax effects may behave differently

### Image Optimization

- Use WebP format when possible for better compression
- Optimize images to appropriate sizes (1920x1080 max for most cases)
- Consider lazy loading for images below the fold

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and permissions
2. **Effects not working**: Ensure JavaScript is enabled and no console errors
3. **Performance issues**: Reduce number of parallax sections or use lower speed values
4. **Mobile issues**: Test on mobile devices and adjust settings accordingly

### Debug Mode

Enable debug mode to see detailed logging:

```bash
export FCMS_DEBUG=true
```

## Browser Support

- **Modern Browsers**: Full support for all effects
- **Mobile Browsers**: Basic support with some limitations
- **Older Browsers**: Graceful degradation to static backgrounds

## Changelog

### Version 2.0
- Fixed image coverage issues
- Improved CSS architecture
- Enhanced JavaScript performance
- Better dark mode support
- Added multiple new effects (blur, slide, zoom)
- Improved mobile responsiveness

### Version 1.0
- Basic parallax scrolling effect
- Simple overlay system
- Basic responsive support

## Support

For support and feature requests, please visit the FearlessCMS documentation or submit an issue through the project repository.

---

*The parallax plugin transforms your content with engaging visual effects while maintaining performance and accessibility.*
