# Example Parallax Usage

This file demonstrates how to use the parallax plugin shortcodes in your content.

## Basic Parallax Section

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll"]
    <div style="text-align: center; padding: 150px 0; color: white;">
        <h1>Welcome to Our Website</h1>
        <p>Experience stunning parallax effects that bring your content to life</p>
        <a href="/about" style="color: white; border: 2px solid white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Learn More</a>
    </div>
[/parallax_section]
```

## Parallax Section with Custom CSS Class

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll" class="hero-parallax custom-theme dark-overlay"]
    <div style="text-align: center; padding: 150px 0; color: white;">
        <h1>Welcome to Our Website</h1>
        <p>Experience stunning parallax effects that bring your content to life</p>
        <a href="/about" style="color: white; border: 2px solid white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Learn More</a>
    </div>
[/parallax_section]
```

## Parallax Section with Custom ID

```
[parallax_section id="hero-section" background_image="/uploads/hero-bg.jpg" speed="0.3" effect="scroll" custom_id="main-hero-section"]
    <div style="text-align: center; padding: 150px 0; color: white;">
        <h1>Welcome to Our Website</h1>
        <p>Experience stunning parallax effects that bring your content to life</p>
        <a href="/about" style="color: white; border: 2px solid white; padding: 15px 30px; text-decoration: none; border-radius: 5px;">Learn More</a>
    </div>
[/parallax_section]
```

## About Section with Parallax

```
[parallax_section id="about-parallax" background_image="/uploads/about-bg.jpg" speed="0.5" effect="scroll" class="about-section company-theme"]
    <div style="text-align: center; padding: 100px 0; color: white;">
        <h2>About Our Company</h2>
        <p>We are passionate about creating amazing digital experiences</p>
        <div style="margin-top: 30px;">
            <span style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 25px; margin: 0 10px;">Innovation</span>
            <span style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 25px; margin: 0 10px;">Quality</span>
            <span style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 25px; margin: 0 10px;">Excellence</span>
        </div>
    </div>
[/parallax_section]
```

## Services Section

```
[parallax_section id="services-parallax" background_image="/uploads/services-bg.jpg" speed="0.4" effect="scroll" class="services-section light-overlay"]
    <div style="text-align: center; padding: 120px 0; color: white;">
        <h2>Our Services</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 40px;">
            <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px;">
                <h3>Web Design</h3>
                <p>Beautiful, responsive websites that convert</p>
            </div>
            <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px;">
                <h3>Development</h3>
                <p>Custom solutions for your business needs</p>
            </div>
            <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 10px;">
                <h3>Consulting</h3>
                <p>Expert advice to grow your digital presence</p>
            </div>
        </div>
    </div>
[/parallax_section]
```

## Contact Section

```
[parallax_section id="contact-parallax" background_image="/uploads/contact-bg.jpg" speed="0.6" effect="scroll" class="contact-section contact-theme" custom_id="main-contact"]
    <div style="text-align: center; padding: 100px 0; color: white;">
        <h2>Get In Touch</h2>
        <p>Ready to start your next project? Let's talk!</p>
        <div style="margin-top: 30px;">
            <a href="mailto:hello@example.com" style="color: white; border: 1px solid white; padding: 12px 25px; text-decoration: none; margin: 0 10px; border-radius: 5px;">Email Us</a>
            <a href="tel:+1234567890" style="color: white; border: 1px solid white; padding: 12px 25px; text-decoration: none; margin: 0 10px; border-radius: 5px;">Call Us</a>
        </div>
    </div>
[/parallax_section]
```

## New Attributes Reference

### CSS Class (`class`)
- **Usage**: `class="custom-class another-class"`
- **Purpose**: Add custom CSS classes to the parallax section for styling
- **Example**: `class="hero-parallax dark-theme custom-spacing"`
- **Note**: Multiple classes can be separated by spaces

### Custom ID (`custom_id`)
- **Usage**: `custom_id="unique-identifier"`
- **Purpose**: Add an additional identifier attribute for JavaScript or CSS targeting
- **Example**: `custom_id="main-hero-section"`
- **Note**: This is separate from the required `id` attribute and is stored as a data attribute

## Tips for Best Results

1. **Image Quality**: Use high-resolution images (at least 1920x1080) for best visual impact
2. **Speed Values**: 
   - `0.3` - Subtle effect, good for hero sections
   - `0.5` - Medium effect, balanced performance and visual appeal
   - `0.7` - Strong effect, use sparingly
3. **Content Readability**: Always ensure text has sufficient contrast with the background
4. **Performance**: Don't use too many parallax sections on one page
5. **Mobile**: Test on mobile devices as parallax effects may behave differently
6. **CSS Classes**: Use semantic class names for better maintainability
7. **Custom IDs**: Use custom IDs for JavaScript event handling or specific styling needs

## Customization

You can customize the appearance by adding inline styles to your content or by modifying the plugin's CSS. The plugin automatically adds text shadows to ensure readability, but you can override these with your own styles.

### Using Custom CSS Classes
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