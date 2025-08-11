# Parallax Sections Plugin

## Overview
The Parallax Sections plugin allows you to create engaging parallax scrolling effects for sections of your website content. It works by applying a background image to a content block and moving it at a different speed than the foreground content, creating a 3D depth illusion.

## Usage
To use the Parallax Sections plugin, you need to wrap your content within the `[parallax_section]` shortcode. This shortcode supports several attributes to customize the parallax effect.

### Shortcode Structure
```
[parallax_section id="your-unique-id" background_image="/path/to/your/image.jpg" speed="0.5" effect="scroll"]
    <!-- Your content goes here -->
    <p>This is the content that will scroll over the parallax background.</p>
[/parallax_section]
```

### Attributes

*   **`id`** (Required): A unique identifier for the parallax section. This is used for CSS targeting and JavaScript manipulation.
    *   **Example**: `id="hero-section"`

*   **`background_image`** (Required): The URL to the image that will be used as the parallax background. This should be an absolute or relative path to your image file.
    *   **Example**: `background_image="/uploads/my-parallax-background.jpg"`

*   **`speed`** (Required): A decimal value that determines the scrolling speed of the background image relative to the foreground content.
    *   A value of `1.0` means the background scrolls at the same speed as the content (no parallax).
    *   A value of `0.5` means the background scrolls at half the speed of the content.
    *   A value of `0` means the background is fixed (classic parallax effect).
    *   **Example**: `speed="0.4"`

*   **`effect`** (Required): Specifies the type of parallax effect.
    *   `"scroll"`: The background image scrolls at a different speed.
    *   (Future effects may be added)
    *   **Example**: `effect="scroll"`

### Example

Here's a complete example of how to implement a parallax section:

```
[parallax_section id="about-us-parallax" background_image="/assets/images/cityscape.jpg" speed="0.3" effect="scroll"]
    <div style="text-align: center; padding: 100px 0; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
        <h2>Welcome to Our World</h2>
        <p>Experience the depth and beauty of our content with stunning parallax effects.</p>
        <a href="/learn-more" style="color: white; border: 1px solid white; padding: 10px 20px; text-decoration: none;">Learn More</a>
    </div>
[/parallax_section]
```

This will create a section with the `cityscape.jpg` image as a background, scrolling at 30% the speed of the main content, and containing a centered text block with a call-to-action button.
