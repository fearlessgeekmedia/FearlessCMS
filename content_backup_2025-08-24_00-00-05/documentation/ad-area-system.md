<!-- json {
    "title": "Ad Area System Guide",
    "template": "documentation"
} -->

# Ad Area System Guide

The Ad Area System is a conditional advertising feature that displays promotional content only when the CMS is in hosting service mode. This system provides hosting providers with a professional way to showcase their services while maintaining a clean experience for self-hosted users.

## 📋 Table of Contents

- [Overview](#overview)
- [How It Works](#how-it-works)
- [CMS Mode Integration](#cms-mode-integration)
- [Template System](#template-system)
- [Theme Integration](#theme-integration)
- [Customization](#customization)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)
- [Advanced Features](#advanced-features)

## 🎯 Overview

The Ad Area System provides:

- **Conditional Display**: Ads only appear in hosting service modes
- **Theme Consistency**: Same ad appearance across all themes
- **Professional Design**: Modern, responsive advertising interface
- **User Control**: Close button for user preference
- **Easy Maintenance**: Single template file for all themes

### When Ads Are Visible

| CMS Mode | Ad Area Status |
|----------|----------------|
| Full Featured | ❌ Hidden |
| Hosting Service (Plugin Mode) | ✅ Visible |
| Hosting Service (No Plugin Management) | ✅ Visible |

## 🔧 How It Works

### Conditional Logic
The ad area uses template conditional syntax to determine visibility:

```html
{{#if isHostingServiceMode}}
<div class="ad-area">
    <!-- Ad content only visible in hosting service modes -->
</div>
{{/if}}
```

### Template Variables
The system provides these variables to templates:

- `{{cmsMode}}` - Current CMS mode identifier
- `{{isHostingServiceMode}}` - Boolean for conditional logic
- `{{cmsModeName}}` - Human-readable mode name

### Include System
Themes include the ad area using:

```html
{{include=ad-area.html}}
```

## 🚀 CMS Mode Integration

### Template Data
The main `index.php` automatically includes CMS mode information:

```php
$templateData = [
    // ... other data ...
    'cmsMode' => $cmsModeManager->getCurrentMode(),
    'isHostingServiceMode' => $cmsModeManager->isRestricted(),
    'cmsModeName' => $cmsModeManager->getModeName(),
];
```

### Mode Detection
The system automatically detects the current mode from `config/cms_mode.json`:

```json
{
    "mode": "hosting-service-plugins"
}
```

## 📝 Template System

### Ad Area Template
Located at `themes/ad-area.html`, the template includes:

- **Responsive Design**: Works on all device sizes
- **Modern Styling**: Glassmorphism design with gradients
- **Interactive Elements**: Hover effects and animations
- **Close Functionality**: User-controlled dismissal

### Template Structure
```html
{{#if isHostingServiceMode}}
<div class="ad-area">
    <!-- Background Pattern -->
    <div class="ad-background"></div>
    
    <!-- Ad Content -->
    <div class="ad-content">
        <h3>🚀 Premium Hosting Features</h3>
        <p>Unlock the full potential of your website...</p>
        
        <!-- Feature Highlights -->
        <div class="feature-tags">
            <span>⚡ Fast Performance</span>
            <span>🔒 Enhanced Security</span>
            <span>📱 Mobile Optimized</span>
        </div>
        
        <!-- Call to Action -->
        <a href="#" class="cta-button">Learn More →</a>
    </div>
    
    <!-- Close Button -->
    <button class="close-button">×</button>
</div>
{{/if}}
```

## 🎨 Theme Integration

### Automatic Inclusion
All themes automatically include the ad area. The system has been integrated with:

- **Default Theme**: page.html, home.html
- **Cyberpunk Theme**: page.html
- **Elegant Dark Theme**: page.html
- **Heroic Theme**: page.html, home.html
- **Minimal Theme**: page.html, home.html
- **Minimalist Theme**: page.html
- **Modern Cards Theme**: page.html, home.html
- **Salt Lake Theme**: page.html, home.html
- **Simple Modern Theme**: page.html, home.html
- **Starter Scores Theme**: page.html, home.html
- **Vintage Theme**: page.html
- **Custom Variables Demo Theme**: page.html

### Positioning
The ad area is positioned right after the header in all themes for maximum visibility:

```html
{{module=header.html}}
{{include=ad-area.html}}
<main>
    <!-- Page content -->
</main>
```

## 🎯 Customization

### Modifying Ad Content
Edit `themes/ad-area.html` to customize:

- **Text Content**: Change headlines, descriptions, and CTAs
- **Visual Design**: Modify colors, gradients, and styling
- **Features**: Update the highlighted service features
- **Links**: Change the call-to-action destination

### Styling Customization
The ad area uses inline styles for consistency, but you can:

1. **Add CSS Classes**: Modify the template to use theme-specific CSS
2. **Theme Integration**: Create theme-specific ad area variations
3. **Responsive Design**: Adjust breakpoints for different screen sizes

### Content Localization
Support multiple languages by creating localized versions:

```
themes/
├── ad-area.html          # Default (English)
├── ad-area-es.html       # Spanish
├── ad-area-fr.html       # French
└── ad-area-de.html       # German
```

## 🧪 Testing

### Testing in Full Featured Mode
1. Ensure CMS is in "full-featured" mode
2. Navigate to any page
3. Verify ad area is **NOT visible**

### Testing in Hosting Service Mode
1. Access admin panel: `/admin`
2. Navigate to CMS Mode section
3. Change to "Hosting Service (Plugin Mode)"
4. Refresh any page
5. Verify ad area is **visible**

### Testing Across Themes
1. Change CMS mode to hosting service mode
2. Switch between different themes
3. Verify ad area appears consistently
4. Test responsive behavior on different screen sizes

### Demo Page
Use the built-in demo page at `/ad-demo` to test the feature.

## 🔧 Troubleshooting

### Common Issues

#### Ad Area Not Visible
**Problem**: Ad area doesn't appear in hosting service mode

**Solutions**:
1. Check CMS mode: `config/cms_mode.json`
2. Verify template includes: `{{include=ad-area.html}}`
3. Check template syntax: `{{#if isHostingServiceMode}}`
4. Review browser console for JavaScript errors

#### Ad Area Always Visible
**Problem**: Ad area appears even in full-featured mode

**Solutions**:
1. Verify CMS mode is "full-featured"
2. Check template conditional logic
3. Clear browser cache
4. Verify `isHostingServiceMode` variable value

#### Styling Issues
**Problem**: Ad area doesn't match theme design

**Solutions**:
1. Check CSS conflicts in theme stylesheets
2. Verify responsive breakpoints
3. Test in different browsers
4. Review theme-specific CSS overrides

### Debug Information
Add debug output to verify variables:

```html
<!-- Debug: Remove in production -->
<div style="background: #f0f0f0; padding: 10px; margin: 10px; font-family: monospace;">
    <strong>Debug Info:</strong><br>
    CMS Mode: {{cmsMode}}<br>
    Is Hosting Service: {{isHostingServiceMode}}<br>
    Mode Name: {{cmsModeName}}
</div>
```

## 🚀 Advanced Features

### A/B Testing
Implement different ad variations:

```html
{{#if isHostingServiceMode}}
    {{#if themeOptions.adVariation}}
        {{include=ad-area-variation-b.html}}
    {{else}}
        {{include=ad-area.html}}
    {{/if}}
{{/if}}
```

### Analytics Integration
Track ad performance:

```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    const adArea = document.querySelector('.ad-area');
    if (adArea) {
        // Track ad impression
        gtag('event', 'ad_impression', {
            'event_category': 'advertising',
            'event_label': 'hosting_service_ad'
        });
        
        // Track close button clicks
        const closeBtn = adArea.querySelector('.close-button');
        closeBtn.addEventListener('click', function() {
            gtag('event', 'ad_closed', {
                'event_category': 'advertising',
                'event_label': 'hosting_service_ad'
            });
        });
    }
});
</script>
```

### Dynamic Content
Load ad content dynamically:

```html
{{#if isHostingServiceMode}}
<div class="ad-area" data-ad-type="hosting-service">
    <div class="ad-content">
        <h3>{{themeOptions.adHeadline}}</h3>
        <p>{{themeOptions.adDescription}}</p>
        <a href="{{themeOptions.adLink}}" class="cta-button">
            {{themeOptions.adButtonText}}
        </a>
    </div>
</div>
{{/if}}
```

### User Preferences
Remember user ad preferences:

```html
<script>
// Check if user has dismissed ads
if (localStorage.getItem('adsDismissed') === 'true') {
    document.querySelector('.ad-area')?.remove();
}

// Handle close button
document.querySelector('.close-button')?.addEventListener('click', function() {
    localStorage.setItem('adsDismissed', 'true');
    this.parentElement.remove();
});
</script>
```

## 📚 Related Documentation

- [CMS Modes Guide](cms-modes.md)
- [Theme Development](theme-development-index.md)
- [Template System](theme-templates-reference.md)
- [Installation Script](install.md)

## 🆘 Getting Help

If you need assistance with the Ad Area System:

1. **Check the troubleshooting section** above
2. **Verify CMS mode settings** in the admin panel
3. **Review template syntax** for conditional logic
4. **Test with different themes** to isolate issues
5. **Consult the community** for additional support

---

**Happy advertising!** 🎉

*This documentation is maintained by the FearlessCMS community. Last updated: January 2024*
