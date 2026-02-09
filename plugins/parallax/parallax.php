<?php
/*
Plugin Name: Parallax Sections
Description: Adds parallax scrolling effects for website sections using shortcodes
Version: 0.0.3
Author: Fearless Geek
*/

// Prevent direct access
if (!defined('PROJECT_ROOT')) {
    die('Direct access not allowed');
}

// Initialize plugin
function parallaxPluginInit() {
    // Register the parallax_section shortcode
    fcms_add_hook('content', 'process_parallax_shortcodes');
    fcms_add_hook('after_content', 'process_parallax_shortcodes');
    
    // Add CSS and JS to pages that use parallax
    fcms_add_hook('before_render', 'add_parallax_assets');
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parallax plugin initialized");
    }
}

// Process parallax shortcodes in content
function process_parallax_shortcodes($content) {
    if (strpos($content, '[parallax_section') === false) {
        return $content;
    }
    
    // Pattern to match parallax shortcodes
    $pattern = '/\[parallax_section\s+([^\]]*)\](.*?)\[\/parallax_section\]/s';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $attributes_string = $matches[1];
        $inner_content = trim($matches[2]);
        
        // Parse attributes
        $attributes = parse_shortcode_attributes($attributes_string);
        
        // Generate parallax section HTML
        return generate_parallax_section($attributes, $inner_content);
    }, $content);
    
    return $content;
}

// Parse shortcode attributes
function parse_shortcode_attributes($attributes_string) {
    $attributes = [];
    
    // Match key="value" or key='value' patterns
    preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attributes_string, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $attributes[$match[1]] = $match[2];
    }
    
    return $attributes;
}

// Generate parallax section HTML
function generate_parallax_section($attributes, $content) {
    // Required attributes
    if (empty($attributes['id']) || empty($attributes['background_image'])) {
        return '<div class="parallax-error">Parallax section requires id and background_image attributes</div>';
    }
    
    $id = sanitize_id($attributes['id']);
    $background_image = htmlspecialchars($attributes['background_image']);
    $speed = isset($attributes['speed']) ? floatval($attributes['speed']) : 0.5;
    $effect = isset($attributes['effect']) ? $attributes['effect'] : 'scroll';
    $overlay_color = isset($attributes['overlay_color']) ? $attributes['overlay_color'] : 'rgba(0,0,0,0.4)';
    $overlay_opacity = isset($attributes['overlay_opacity']) ? floatval($attributes['overlay_opacity']) : 0.4;
    $class = isset($attributes['class']) ? htmlspecialchars($attributes['class']) : '';
    $custom_id = isset($attributes['custom_id']) ? htmlspecialchars($attributes['custom_id']) : '';
    
    // Validate speed (allow higher speeds for more dramatic effect)
    $speed = max(0.0, $speed);
    
    // Validate opacity
    $overlay_opacity = max(0.0, min(1.0, $overlay_opacity));
    
    // Build CSS classes
    $css_classes = ['parallax-section'];
    if ($class) {
        $css_classes = array_merge($css_classes, explode(' ', $class));
    }
    $css_classes[] = 'parallax-effect-' . $effect;
    
    // Build HTML
    $html = '<div class="' . implode(' ', $css_classes) . '"';
    $html .= ' id="' . $id . '"';
    if ($custom_id) {
        $html .= ' data-custom-id="' . $custom_id . '"';
    }
    $html .= ' data-parallax-speed="' . $speed . '"';
    $html .= ' data-parallax-effect="' . $effect . '"';
    $html .= '">';
    
    // Add parallax background element
    $html .= '<div class="parallax-background" style="background-image: url(\'' . $background_image . '\');"></div>';
    
    // Add overlay if specified
    if ($overlay_color && $overlay_opacity > 0) {
        $html .= '<div class="parallax-overlay" style="background-color: ' . $overlay_color . '; opacity: ' . $overlay_opacity . ';"></div>';
    }
    
    // Add content
    $html .= '<div class="parallax-content">' . $content . '</div>';
    
    $html .= '</div>';
    
    // Mark that this page uses parallax
    $GLOBALS['parallax_used'] = true;
    
    return $html;
}

// Sanitize ID for HTML
function sanitize_id($id) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
}

// Add parallax CSS and JS assets
function add_parallax_assets() {
    if (!isset($GLOBALS['parallax_used']) || !$GLOBALS['parallax_used']) {
        return;
    }
    
    // Add CSS
    echo '<style>' . get_parallax_css() . '</style>';
    
    // Add JavaScript
    echo '<script>' . get_parallax_js() . '</script>';
}

// Generate parallax CSS
function get_parallax_css() {
    return '
.parallax-section {
    position: relative;
    min-height: 200px;
    margin: 0;
    padding: 0;
    border: none;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Ensure no gaps between sections */
.parallax-section + .parallax-section {
    margin-top: 0;
}

.parallax-section .parallax-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
}

.parallax-section .parallax-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: white;
    padding: 4rem;
    max-width: 800px;
    margin: 0 auto;
}

.parallax-section .parallax-content h1,
.parallax-section .parallax-content h2,
.parallax-section .parallax-content h3,
.parallax-section .parallax-content h4,
.parallax-section .parallax-content h5,
.parallax-section .parallax-content h6 {
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
}

.parallax-section .parallax-content p {
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}

.parallax-error {
    background: #ff6b6b;
    color: white;
    padding: 4rem;
    border-radius: 4px;
    margin: 1rem 0;
}


/* Smooth transitions for fade-in effect */
.parallax-section {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.parallax-section .parallax-background {
    position: absolute;
    top: -50%;
    left: 0;
    right: 0;
    height: 200%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: 0;
    will-change: transform;
    transition: transform 0.1s ease-out;
}
/* Responsive design */
@media (max-width: 768px) {
    .parallax-section {
        background-attachment: scroll;
        min-height: 200px;
    margin: 0;
    padding: 0;
    border: none;
    box-sizing: border-box;
    vertical-align: top;
    margin: 0;
    padding: 0;
    }
    
    .parallax-section .parallax-content {
        padding: 4rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .parallax-section .parallax-content {
        color: #ffffff;
    }
}
';
}

// Generate parallax JavaScript
function get_parallax_js() {
    return '
document.addEventListener("DOMContentLoaded", function() {
    const parallaxSections = document.querySelectorAll(".parallax-section");
    
    if (parallaxSections.length === 0) return;
    
    let ticking = false;
    
    function updateParallax() {
        const scrollTop = window.pageYOffset;
        
        parallaxSections.forEach(function(section) {
            const speed = parseFloat(section.dataset.parallaxSpeed) || parseFloat(section.dataset.speed) || 2.0;
            const effect = section.dataset.effect || section.dataset.parallaxEffect || "scroll";
            const rect = section.getBoundingClientRect();
            const sectionTop = rect.top + scrollTop;
            
            if (effect === "scroll") {
                // Only animate if section is in viewport
                if (rect.bottom >= 0 && rect.top <= window.innerHeight) {
                    const yPos = -(scrollTop - sectionTop) * speed;
                    const background = section.querySelector(".parallax-background");
                    if (background) {
                        background.style.transform = "translateY(" + yPos + "px)";
                    }
                }
            } else if (effect === "fade-in") {
                // Fade-in effect: fade in as section enters viewport
                const sectionTop = rect.top;
                const sectionBottom = rect.bottom;
                const viewportHeight = window.innerHeight;
                
                let opacity = 0;
                let translateY = 20;
                
                if (sectionTop < viewportHeight && sectionBottom > 0) {
                    // Section is in viewport
                    const visibleHeight = Math.min(sectionBottom, viewportHeight) - Math.max(sectionTop, 0);
                    const totalHeight = rect.height;
                    const visibilityRatio = visibleHeight / totalHeight;
                    
                    // Fade in as more of the section becomes visible
                    opacity = Math.min(1, visibilityRatio * 2);
                    translateY = 20 * (1 - visibilityRatio);
                }
                
                section.style.opacity = opacity;
                section.style.transform = "translateY(" + translateY + "px)";
            }
        });
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }
    
    // Throttled scroll event
    window.addEventListener("scroll", requestTick, { passive: true });
    
    // Initial call
    updateParallax();
});
';
}

// Initialize the plugin
parallaxPluginInit();
