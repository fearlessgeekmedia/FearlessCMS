<?php
/**
 * Parallax Plugin for FearlessCMS
 * 
 * This plugin provides parallax scrolling effects for website content.
 * It supports multiple parallax effects including scroll, fixed, scale, rotate, fade, blur, slide, and zoom.
 * 
 * Features:
 * - Multiple parallax effects
 * - Customizable speed and overlay options
 * - Responsive design support
 * - Performance optimized
 * - Dark mode support
 * 
 * @version 2.0
 * @author FearlessCMS
 * @license MIT
 */

// Prevent direct access
if (!defined('PROJECT_ROOT')) {
    exit('Direct access not allowed');
}

// Immediately exit if we're in an admin context to prevent header issues
if (isset($_SERVER['REQUEST_URI']) && (
    strpos($_SERVER['REQUEST_URI'], '/admin') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/login') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/logout') !== false
)) {
    return; // Exit early without defining any functions or hooks
}

// Initialize parallax functionality
function parallax_init() {
    // This function is called by the hook system
    // No immediate output here to prevent header issues
}

// Process parallax shortcodes in content
function parallax_process_shortcode($content) {
    // Only process if we're not in an admin context that might need headers
    if (defined('ADMIN_MODE') && ADMIN_MODE) {
        return $content;
    }
    
    // Additional check: look for admin paths in the request
    if (isset($_SERVER['REQUEST_URI']) && (
        strpos($_SERVER['REQUEST_URI'], '/admin') !== false ||
        strpos($_SERVER['REQUEST_URI'], '/login') !== false ||
        strpos($_SERVER['REQUEST_URI'], '/logout') !== false
    )) {
        return $content;
    }
    
    // Process parallax shortcodes
    $content = process_parallax_shortcodes($content);
    
    // Output assets only when processing content
    output_parallax_assets();
    
    return $content;
}

// Process parallax shortcodes in content
function process_parallax_shortcodes($content) {
    // Define the shortcode pattern
    $pattern = '/\[parallax_section\s+([^\]]*)\](.*?)\[\/parallax_section\]/s';
    
    // Replace shortcodes with HTML
    $content = preg_replace_callback($pattern, function($matches) {
        $attributes = parse_shortcode_attributes($matches[1]);
        $inner_content = $matches[2];
        

        
        return generate_parallax_section($attributes, $inner_content);
    }, $content);
    
    return $content;
}

// Parse shortcode attributes
function parse_shortcode_attributes($attribute_string) {
    $attributes = [];
    
    // Parse key="value" pairs
    preg_match_all('/(\w+)\s*=\s*["\']([^"\']*)["\']/', $attribute_string, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $attributes[$match[1]] = $match[2];
    }
    
    // Parse key=value pairs (without quotes)
    preg_match_all('/(\w+)\s*=\s*([^\s]+)/', $attribute_string, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        if (!isset($attributes[$match[1]])) {
            $attributes[$match[1]] = $match[2];
        }
    }
    
    return $attributes;
}

// Generate parallax section HTML
function generate_parallax_section($attributes, $content) {
    // Extract attributes with defaults
    $id = $attributes['id'] ?? 'parallax-' . uniqid();
    $background_image = $attributes['background_image'] ?? '';
    $speed = $attributes['speed'] ?? '0.5';
    $effect = $attributes['effect'] ?? 'scroll';
    $class = $attributes['class'] ?? '';
    $custom_id = $attributes['custom_id'] ?? '';
    $overlay_color = $attributes['overlay_color'] ?? 'rgba(0,0,0,0.4)';
    $overlay_opacity = $attributes['overlay_opacity'] ?? '0.4';
    
    // Additional effect-specific attributes
    $fade_start_percent = $attributes['fade_start_percent'] ?? '30';
    $fade_distance = $attributes['fade_distance'] ?? '60';
    $start_opacity = $attributes['start_opacity'] ?? '0.2';
    $start_offset = $attributes['start_offset'] ?? '30';
    
    // Build CSS classes
    $css_classes = ['parallax-section', 'parallax-' . $effect];
    if ($class) {
        $css_classes = array_merge($css_classes, explode(' ', $class));
    }
    
    // Build data attributes
    $data_attrs = [
        'data-speed="' . htmlspecialchars($speed) . '"',
        'data-effect="' . htmlspecialchars($effect) . '"',
        'data-overlay-color="' . htmlspecialchars($overlay_color) . '"',
        'data-overlay-opacity="' . htmlspecialchars($overlay_opacity) . '"'
    ];
    
    // Add effect-specific data attributes
    if ($effect === 'fade-in') {
        $data_attrs[] = 'data-fade-start-percent="' . htmlspecialchars($fade_start_percent) . '"';
        $data_attrs[] = 'data-fade-distance="' . htmlspecialchars($fade_distance) . '"';
        $data_attrs[] = 'data-start-opacity="' . htmlspecialchars($start_opacity) . '"';
        $data_attrs[] = 'data-start-offset="' . htmlspecialchars($start_offset) . '"';
    }
    
    // Build the HTML
    $html = '<div class="' . implode(' ', $css_classes) . '" id="' . htmlspecialchars($custom_id ?: $id) . '" ' . implode(' ', $data_attrs) . '>';
    
    // Add background image if specified
    if ($background_image) {
        $html .= '<div class="parallax-background" style="background-image: url(\'' . htmlspecialchars($background_image) . '\');"></div>';
    }
    
    // Add content wrapper
    $html .= '<div class="parallax-content">' . $content . '</div>';
    
    $html .= '</div>';
    
    return $html;
}

// Output CSS and JavaScript only when needed
function output_parallax_assets() {
    // Only output once
    static $output_done = false;
    if ($output_done) {
        return;
    }
    $output_done = true;
    
    // CSS for parallax effects
    $css = <<<CSS
.parallax-section {
    position: relative;
    overflow: hidden;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.parallax-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    transition: transform 0.1s ease-out;
    z-index: 0;
}

.parallax-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.parallax-content h1,
.parallax-content h2,
.parallax-content h3,
.parallax-content h4,
.parallax-content h5,
.parallax-content h6 {
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
}

.parallax-content p {
    margin-bottom: 1.5rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
    line-height: 1.6;
}

.parallax-content a {
    color: white;
    text-decoration: none;
    border-bottom: 2px solid white;
    transition: border-color 0.3s ease;
}

.parallax-content span,
.parallax-content strong,
.parallax-content em,
.parallax-content code,
.parallax-content mark {
    display: inline-block;
    margin: 0 0.2rem;
}

.parallax-content > *:first-child {
    margin-top: 0;
}

/* Effect-specific styles */
.parallax-fixed .parallax-background {
    position: fixed;
}

.parallax-scale .parallax-background {
    transform: scale(1.1);
}

.parallax-rotate .parallax-background {
    transform: rotate(1deg);
}

.parallax-fade-in .parallax-content {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.parallax-fade-in.visible .parallax-content {
    opacity: 1;
    transform: translateY(0);
}

.parallax-blur .parallax-background {
    filter: blur(2px);
}

.parallax-slide .parallax-content {
    transform: translateX(-100px);
    transition: transform 0.6s ease;
}

.parallax-slide.visible .parallax-content {
    transform: translateX(0);
}

.parallax-zoom .parallax-background {
    transform: scale(1.2);
    transition: transform 0.8s ease;
}

.parallax-zoom.visible .parallax-background {
    transform: scale(1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .parallax-section {
        min-height: 200px;
    }
    
    .parallax-content {
        padding: 1rem;
    }
    
    .parallax-content h1 {
        font-size: 1.8rem;
    }
    
    .parallax-content h2 {
        font-size: 1.5rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .parallax-content {
        color: #f0f0f0;
    }
    
    .parallax-content a {
        color: #f0f0f0;
        border-bottom-color: #f0f0f0;
    }
}
CSS;

    // JavaScript for parallax effects
    $js = <<<JS
function initParallax() {
    const parallaxSections = document.querySelectorAll(".parallax-section");
    
    if (parallaxSections.length === 0) return;
    
    // Set up scroll event listener for parallax effect
    window.addEventListener('scroll', function() {
        // Apply parallax to visible sections
        parallaxSections.forEach(function(section) {
            const rect = section.getBoundingClientRect();
            const background = section.querySelector(".parallax-background");
            
            if (background && rect.top < window.innerHeight && rect.bottom > 0) {
                // Calculate parallax relative to section position, not total page scroll
                const speed = parseFloat(section.dataset.speed) || 0.5;
                const sectionTop = rect.top;
                const sectionHeight = rect.height;
                const windowHeight = window.innerHeight;
                
                // Only apply parallax when section is actually visible in viewport
                if (sectionTop < windowHeight && sectionTop > -sectionHeight) {
                    // Calculate how much this specific section should move based on its visibility
                    const visibleHeight = Math.min(windowHeight, sectionHeight);
                    const scrollProgress = Math.max(0, Math.min(1, (windowHeight - sectionTop) / visibleHeight));
                    const maxMove = sectionHeight * 0.15; // Limit movement to 15% of section height
                    const moveAmount = scrollProgress * maxMove * speed;
                    
                    // Apply the transform only to this section's background
                    background.style.transform = 'translateY(' + moveAmount + 'px)';
                } else {
                    // Reset transform when section is not visible
                    background.style.transform = 'translateY(0px)';
                }
            }
        });
    });
    
    // Initialize overlays
    setOverlayStyles();
}

function setOverlayStyles() {
    const parallaxSections = document.querySelectorAll(".parallax-section");
    console.log("Found " + parallaxSections.length + " parallax sections");
    
    parallaxSections.forEach(function(section, index) {
        const overlayColor = section.dataset.overlayColor || "rgba(0,0,0,0.4)";
        const overlayOpacity = section.dataset.overlayOpacity || "0.4";
        
        // Create overlay div
        const overlayDiv = document.createElement("div");
        overlayDiv.className = "parallax-overlay";
        overlayDiv.style.cssText = "position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: " + overlayColor + "; z-index: 1;";
        
        // Remove existing overlay if any
        const existingOverlay = section.querySelector(".parallax-overlay");
        if (existingOverlay) {
            existingOverlay.remove();
        }
        
        // Insert the overlay before the content
        const content = section.querySelector(".parallax-content");
        if (content) {
            section.insertBefore(overlayDiv, content);
            console.log("Overlay added to section " + index);
        } else {
            console.log("No content found in section " + index);
        }
    });
}

// Initialize parallax when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initParallax);
} else {
    initParallax();
}

// Also initialize on window load for images
window.addEventListener("load", function() {
    // Ensure all background images are loaded
    const parallaxSections = document.querySelectorAll(".parallax-section");
    parallaxSections.forEach(function(section) {
        const background = section.querySelector(".parallax-background");
        if (background) {
            const bgImage = background.style.backgroundImage;
            if (bgImage && bgImage !== "none") {
                const imgUrl = bgImage.replace(/url\(["\']?([^"\']+)["\']?\)/, "$1");
                const img = new Image();
                img.onload = function() {
                    background.classList.add("loaded");
                };
                img.onerror = function() {
                    console.warn("Failed to load parallax background image:", imgUrl);
                };
                img.src = imgUrl;
            } else {
                background.classList.add("loaded");
            }
        }
    });
    
    // Initialize overlays after images are loaded
    setOverlayStyles();
});
JS;

    // Output the CSS and JavaScript
    echo '<style>' . $css . '</style>';
    echo '<script>' . $js . '</script>';
}

// Register hooks only when this plugin is loaded through the plugin system
// This prevents immediate execution that causes header issues
if (function_exists('fcms_add_hook')) {
    // Only register hooks if we're not in an admin context
    if (!isset($_SERVER['REQUEST_URI']) || (
        strpos($_SERVER['REQUEST_URI'], '/admin') === false &&
        strpos($_SERVER['REQUEST_URI'], '/login') === false &&
        strpos($_SERVER['REQUEST_URI'], '/logout') === false
    )) {
        fcms_add_hook('init', 'parallax_init');
        fcms_add_hook('content', 'parallax_process_shortcode');
    }
}
?> 