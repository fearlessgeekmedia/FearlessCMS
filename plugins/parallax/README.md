# Parallax Scrolling Plugin

This plugin adds beautiful parallax scrolling effects to your FearlessCMS website using simple shortcodes.

## Basic Usage

```markdown
[parallax_section id="my-section" background_image="/uploads/my-image.jpg" speed="0.5" effect="scroll"]
Your content here
[/parallax_section]
```

## Parameters

- **id**: Unique identifier for the section (required)
- **background_image**: Path to the background image (required)
- **speed**: Parallax speed (optional, default: 0.5)
- **effect**: Animation effect (optional, default: "scroll")
- **overlay_color**: Color for the text overlay (optional, default: rgba(0,0,0,0.4))
- **overlay_opacity**: Opacity of the overlay (optional, default: 0.4)

## Overlay Color Options

### Color Names
You can use common color names:
```markdown
[parallax_section id="section1" background_image="/uploads/image1.jpg" overlay_color="red" overlay_opacity="0.6"]
Content with red overlay
[/parallax_section]
```

### Hex Colors
You can use hex color codes:
```markdown
[parallax_section id="section2" background_image="/uploads/image2.jpg" overlay_color="#0066cc" overlay_opacity="0.7"]
Content with blue overlay
[/parallax_section]
```

### RGBA Colors
You can use full rgba values:
```markdown
[parallax_section id="section3" background_image="/uploads/image3.jpg" overlay_color="rgba(255,0,0,0.8)" overlay_opacity="0.8"]
Content with custom rgba overlay
[/parallax_section]
```

## Examples

### Hero Section with Dark Overlay
```markdown
[parallax_section id="hero" background_image="/uploads/hero.jpg" speed="0.3" overlay_color="black" overlay_opacity="0.5"]
# Welcome to Our Site
This text will be clearly readable over the background image
[/parallax_section]
```

### Section with Blue Overlay
```markdown
[parallax_section id="about" background_image="/uploads/about.jpg" speed="0.4" overlay_color="#0066cc" overlay_opacity="0.6"]
## About Us
Our story with a blue overlay for better readability
[/parallax_section]
```

## Supported Color Names

- black, white, red, green, blue, yellow
- purple, orange, pink, brown, gray, grey

## Tips

1. **Text Readability**: Use darker overlays for light text, lighter overlays for dark text
2. **Opacity**: Keep opacity between 0.3-0.8 for best readability
3. **Speed**: Lower speed values (0.1-0.3) create subtle effects, higher values (0.6-1.0) create dramatic effects
4. **Image Quality**: Use high-quality, high-resolution images for best results

## Troubleshooting

- **Images not showing**: Check that the image path is correct and the file exists
- **Overlay not working**: Ensure both `overlay_color` and `overlay_opacity` are set
- **Parallax not working**: Check browser console for JavaScript errors 