# Example Parallax Usage

This file demonstrates how to use the parallax plugin shortcodes in your content.

## Basic Parallax Section

The simplest form of a parallax section:

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll"]
# Hero Title
## Subtitle

Experience stunning parallax effects that bring your content to life
[/parallax_section]
```

## Parallax Section with Custom CSS Class

Add custom CSS classes for styling flexibility:

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll" class="hero-parallax custom-theme dark-overlay"]
# Hero Title
## Subtitle

Experience stunning parallax effects that bring your content to life
[/parallax_section]
```

## Parallax Section with Custom ID

Use custom IDs for JavaScript targeting and specific styling:

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll" custom_id="main-hero-section"]
# Hero Title
## Subtitle

Experience stunning parallax effects that bring your content to life
[/parallax_section]
```

## About Section with Parallax

Create engaging about sections with parallax backgrounds:

```
[parallax_section id="about-parallax" background_image="/uploads/about-bg.jpg" speed="0.5" effect="scroll" class="about-section company-theme"]
# About Our Company
## Building the Future of Web Development

We are passionate about creating innovative web solutions that drive business growth and user engagement. Our team combines creativity with technical expertise to deliver exceptional digital experiences.

### Our Mission
To empower businesses with cutting-edge web technologies that transform their online presence and drive measurable results.

### Our Vision
To be the leading force in modern web development, setting industry standards for performance, accessibility, and user experience.
[/parallax_section]
```

## Services Section with Fade-In Effect

Use fade-in effects for dynamic content presentation:

```
[parallax_section id="services-parallax" background_image="/uploads/services-bg.jpg" speed="0.4" effect="fade-in" class="services-section light-overlay"]
# Our Services
## Comprehensive Web Solutions

### Web Development
Custom websites and web applications built with modern technologies and best practices.

### Design Services
Beautiful, responsive designs that engage users and reflect your brand identity.

### Consulting
Strategic guidance to optimize your web presence and digital strategy.

### Support & Maintenance
Ongoing support to ensure your website remains secure, fast, and up-to-date.
[/parallax_section]
```

## Contact Section with Fixed Background

Create contact sections with fixed backgrounds for professional appearance:

```
[parallax_section id="contact-parallax" background_image="/uploads/contact-bg.jpg" speed="0.6" effect="fixed" class="contact-section" custom_id="main-contact"]
# Get In Touch
## Ready to Start Your Project?

Contact us today to discuss your web development needs and discover how we can help transform your online presence.

### Contact Information
- **Email**: info@yourcompany.com
- **Phone**: (555) 123-4567
- **Address**: 123 Web Street, Digital City, DC 12345

### Business Hours
- Monday - Friday: 9:00 AM - 6:00 PM
- Saturday: 10:00 AM - 4:00 PM
- Sunday: Closed
[/parallax_section]
```

## Complete Page Example

Here's a complete page structure using multiple parallax sections:

```
# Welcome to Our Website

[parallax_section id="hero" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll" class="hero-section dark-overlay"]
# Welcome to Our Company
## Innovation Meets Excellence

Discover how we can transform your business with cutting-edge web solutions.
[/parallax_section]

## Our Story

We started with a simple mission: to make the web more beautiful and functional for everyone.

[parallax_section id="story" background_image="/uploads/story-bg.jpg" speed="0.4" effect="fade-in" class="story-section light-overlay"]
# Our Journey
## From Startup to Industry Leader

Our story began in 2020 when a group of passionate developers came together with a shared vision.
[/parallax_section]

## What We Do

[parallax_section id="services" background_image="/uploads/services-bg.jpg" speed="0.5" effect="scale" class="services-section custom-overlay"]
# Our Services
## Comprehensive Solutions

- Web Development
- Design Services
- Digital Marketing
- Consulting
[/parallax_section]

## Get Started

[parallax_section id="contact" background_image="/uploads/contact-bg.jpg" speed="0.6" effect="fixed" class="contact-section minimal-overlay"]
# Ready to Begin?
## Let's Build Something Amazing Together

Contact us today to start your project.
[/parallax_section]
```

## Shortcode Reference

### Basic Syntax
```
[parallax_section id="unique-id" background_image="/path/to/image.jpg" speed="0.5" effect="scroll"]
Your content here
[/parallax_section]
```

### Required Attributes
- **`id`**: Unique identifier for the section
- **`background_image`**: Path to the background image

### Optional Attributes
- **`speed`**: Parallax speed (0.1 to 1.0, default: 0.5)
- **`effect`**: Effect type (scroll, fade-in, scale, rotate, blur, slide, zoom)
- **`class`**: Custom CSS classes for styling
- **`custom_id`**: Alternative ID for the HTML element
- **`overlay_color`**: Custom overlay color (default: rgba(0,0,0,0.4))
- **`overlay_opacity`**: Overlay opacity (default: 0.4)

### Effect-Specific Attributes

For fade-in effects:
- **`fade_start_percent`**: When the fade starts (default: 30)
- **`fade_distance`**: How far to fade (default: 60)
- **`start_opacity`**: Starting opacity (default: 0.2)
- **`start_offset`**: Starting offset (default: 30)

## CSS Classes

The plugin automatically generates CSS classes for each parallax section. You can target these for custom styling:

```css
/* Custom styling for hero parallax sections */
.hero-parallax {
    min-height: 500px;
    text-align: center;
}

.dark-overlay .parallax-content {
    color: #ffffff;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
}

.custom-theme .parallax-content {
    background: rgba(0,0,0,0.6);
    border-radius: 10px;
    padding: 2rem;
}

.light-overlay .parallax-content {
    color: #333333;
    text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
}
```

## Performance Tips

1. **Limit Sections**: Don't use more than 3-4 parallax sections per page
2. **Optimize Images**: Use compressed images for better loading performance
3. **Mobile Testing**: Test on mobile devices as parallax effects may behave differently
4. **Use Appropriate Speeds**: Lower speeds (0.1-0.3) work better for subtle effects

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and ensure images exist
2. **Effects not working**: Verify JavaScript is enabled and check browser console
3. **Performance issues**: Reduce number of sections or use lower speed values
4. **Mobile issues**: Test on mobile devices and adjust settings accordingly

### Debug Mode

Enable debug mode to see detailed logging:

```bash
export FCMS_DEBUG=true
```

---

*These examples demonstrate the full range of parallax effects available in FearlessCMS. Each section showcases different effects and styling options.* 