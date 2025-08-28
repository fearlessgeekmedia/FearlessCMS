<?php
// Parallax Sections Plugin
// Adds parallax scrolling effects for website sections using shortcodes

// Plugin file loaded

// Register the plugin
fcms_add_hook('init', 'parallax_init');
fcms_add_hook('after_content', 'parallax_process_shortcode');

// Initialize the plugin
function parallax_init() {
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parallax plugin initialized");
    }
    // Don't output assets here - will be handled by content processing
}

// Process parallax shortcodes
function parallax_process_shortcode($content) {
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parallax plugin called with content length: " . strlen($content));
        error_log("Content preview: " . substr($content, 0, 500));
        error_log("Parallax plugin version 2.0 - Added multiple parallax effects");
    }
    
    // Check if content contains parallax shortcodes
    if (strpos($content, '[parallax_section') === false) {
        return $content; // No parallax shortcodes, return content as-is
    }
    
    // Wrap feature sections in div containers for styling
    $content = wrapFeatureSections($content);
    
    // Now use a simple regex that works reliably
    $content = preg_replace_callback('/\[parallax_section(.*?)\](.*?)\[\/parallax_section\]/s', 'process_parallax_shortcode', $content);
    
    // Add CSS and JavaScript to the content
    $content .= get_parallax_assets();
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parallax processing complete. Content length after: " . strlen($content));
    }
    
    return $content;
}

// Function to wrap feature sections in div containers
function wrapFeatureSections($content) {
    // The content now already has proper HTML structure with feature-card divs
    // We just need to ensure the CSS grid layout works properly
    
    // Add a wrapper div around feature sections to ensure proper grid layout
    $content = preg_replace(
        '/(<h2>Built for the Modern Web<\/h2>.*?)(<div class="feature-card">.*?<\/div>.*?<\/div>.*?<\/div>)/s',
        '$1<div class="features-grid">$2</div>',
        $content
    );
    
    $content = preg_replace(
        '/(<h2>Our Core Principles<\/h2>.*?)(<div class="feature-card">.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>)/s',
        '$1<div class="features-grid">$2</div>',
        $content
    );
    
    return $content;
}

// Helper function to process a single parallax shortcode
function process_parallax_shortcode($matches) {
    $attributes_text = $matches[1];
    $inner_content = $matches[2];
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Processing parallax shortcode - Attributes: " . $attributes_text . ", Content length: " . strlen($inner_content));
    }
    
    // Default values
    $id = '';
    $background_image = '';
    $speed = '0.5';
    $effect = 'scroll';
    $overlay_color = 'rgba(0,0,0,0.4)'; // Default dark overlay
    $overlay_opacity = '0.4'; // Default opacity
    $css_class = ''; // New: custom CSS class
    $custom_id = ''; // New: custom ID (separate from the required id attribute)
    
    // Fade-in effect parameters (only used when effect="fade-in")
    $fade_start_percent = '25'; // Start fade-in when 25% into viewport
    $fade_distance = '50'; // Fade over 50% of viewport
    $start_opacity = '0.1'; // Start at 10% opacity
    $start_offset = '20'; // Start 20px down
    
    // Extract attributes using regex
    if (preg_match('/id="([^"]+)"/', $attributes_text, $id_match)) {
        $id = $id_match[1];
    }
    if (preg_match('/background_image="([^"]+)"/', $attributes_text, $bg_match)) {
        $background_image = $bg_match[1];
    }
    if (preg_match('/speed="([^"]+)"/', $attributes_text, $speed_match)) {
        $speed = $speed_match[1];
    }
    if (preg_match('/effect="([^"]+)"/', $attributes_text, $effect_match)) {
        $effect = $effect_match[1];
    }
    if (preg_match('/overlay_color="([^"]+)"/', $attributes_text, $color_match)) {
        $overlay_color = $color_match[1];
    }
    if (preg_match('/overlay_opacity="([^"]+)"/', $attributes_text, $opacity_match)) {
        $overlay_opacity = $opacity_match[1];
    }
    
    // New: Extract CSS class and custom ID attributes
    if (preg_match('/class="([^"]+)"/', $attributes_text, $class_match)) {
        $css_class = $class_match[1];
    }
    if (preg_match('/custom_id="([^"]+)"/', $attributes_text, $custom_id_match)) {
        $custom_id = $custom_id_match[1];
    }
    
    // Parse fade-in effect parameters
    if (preg_match('/fade_start_percent="([^"]+)"/', $attributes_text, $fade_start_match)) {
        $fade_start_percent = $fade_start_match[1];
    }
    if (preg_match('/fade_distance="([^"]+)"/', $attributes_text, $fade_distance_match)) {
        $fade_distance = $fade_distance_match[1];
    }
    if (preg_match('/start_opacity="([^"]+)"/', $attributes_text, $start_opacity_match)) {
        $start_opacity = $start_opacity_match[1];
    }
    if (preg_match('/start_offset="([^"]+)"/', $attributes_text, $start_offset_match)) {
        $start_offset = $start_offset_match[1];
    }
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parsed attributes - ID: $id, Background: $background_image, Speed: $speed, Effect: $effect, Overlay: $overlay_color, Opacity: $overlay_opacity, Class: $css_class, Custom ID: $custom_id");
    }
    
    // Validate required attributes
    if (empty($id) || empty($background_image)) {
        return '<div class="alert alert-danger">Parallax section requires both id and background_image attributes</div>';
    }
    
    // Generate unique CSS class - combine default class with custom class
    $default_css_class = 'parallax-section-' . sanitize_id($id);
    $final_css_class = trim($default_css_class . ' ' . $css_class);
    
    // Clean up the inner content by removing unwanted paragraph wrappers and fixing HTML structure
    // Remove paragraph tags that are just wrapping the shortcode content
    $inner_content = preg_replace('/<p>\s*\[parallax_section/', '[parallax_section', $inner_content);
    $inner_content = preg_replace('/\[\/parallax_section\]\s*<\/p>/', '[/parallax_section]', $inner_content);
    
    // Remove empty paragraphs
    $inner_content = preg_replace('/<p>\s*<\/p>/', '', $inner_content);
    
    // Clean up any remaining paragraph wrapping issues
    $inner_content = preg_replace('/<p>\s*<div/', '<div', $inner_content);
    $inner_content = preg_replace('/<\/div>\s*<\/p>/', '</div>', $inner_content);
    
    // Only remove paragraph tags that are purely wrapping other HTML elements
    // This preserves span tags and other inline HTML
    $inner_content = preg_replace('/<p>\s*(<(?!\/?p\b)[^>]*>.*?<\/[^>]*>)\s*<\/p>/s', '$1', $inner_content);
    
    // Remove paragraph tags that are just wrapping text (but preserve the text)
    $inner_content = preg_replace('/<p>\s*([^<]+)\s*<\/p>/s', '$1', $inner_content);
    
    // Build the parallax section HTML
    $output = '<div id="' . htmlspecialchars($id) . '" class="' . $final_css_class . ' parallax-section" data-speed="' . htmlspecialchars($speed) . '" data-effect="' . htmlspecialchars($effect) . '" data-overlay-color="' . htmlspecialchars($overlay_color) . '" data-overlay-opacity="' . htmlspecialchars($overlay_opacity) . '"';
    
    // Add custom ID if specified
    if (!empty($custom_id)) {
        $output .= ' data-custom-id="' . htmlspecialchars($custom_id) . '"';
    }
    
    // Add fade-in parameters if using fade-in effect
    if ($effect === 'fade-in') {
        $output .= ' data-fade-start-percent="' . htmlspecialchars($fade_start_percent) . '"';
        $output .= ' data-fade-distance="' . htmlspecialchars($fade_distance) . '"';
        $output .= ' data-start-opacity="' . htmlspecialchars($start_opacity) . '"';
        $output .= ' data-start-offset="' . htmlspecialchars($start_offset) . '"';
    }
    
    $output .= '>';
    $output .= '<div class="parallax-background" style="background-image: url(\'' . htmlspecialchars($background_image) . '\');"></div>';
    $output .= '<div class="parallax-content">';
    $output .= $inner_content;
    $output .= '</div>';
    $output .= '</div>';
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Generated parallax HTML: " . substr($output, 0, 200) . "...");
    }
    
    return $output;
}

// Sanitize ID for CSS class
function sanitize_id($id) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
}

// Get CSS and JavaScript as a string
function get_parallax_assets() {
    // Return CSS and JavaScript as a string
    return <<<'ASSETS'
<!-- Parallax Plugin v2.0 - Multiple parallax effects available -->
<style>
        html body .parallax-section,
        body .parallax-section,
        .parallax-section {
            position: relative !important;
            overflow: hidden !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            /* Ensure sections have adequate height for content */
            min-height: 300px !important;
            height: auto !important;
            /* Ensure proper background coverage */
            background: transparent !important;
        }
        
        html body .parallax-background,
        body .parallax-background,
        .parallax-background {
            position: absolute !important;
            top: 0px !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-size: cover !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-attachment: scroll !important;
            z-index: 1 !important;
            /* Remove initial transform to allow JavaScript to control it */
            will-change: transform;
            /* Prevent any gaps during scroll */
            background-clip: border-box;
        }
        
        /* Ensure intro-section specifically gets proper background sizing */
        #intro-section .parallax-background {
            background-size: cover !important;
            background-position: center center !important;
        }
        
        /* Even more specific selectors to override any conflicting styles */
        html body #intro-section .parallax-background,
        body #intro-section .parallax-background {
            background-size: cover !important;
            background-position: center center !important;
        }
        
        /* Special styling for fixed effect */
        .parallax-section[data-effect="fixed"] .parallax-background {
            position: fixed !important;
        }
        
        body .parallax-content,
        .parallax-content {
            position: relative !important;
            z-index: 3 !important;
            padding: 2rem 1rem !important;
            min-height: 400px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            /* Ensure content takes full height */
            width: 100% !important;
            height: 100% !important;
            /* Ensure content is properly spaced */
            box-sizing: border-box !important;
        }
        
        /* Ensure content is readable over background */
        .parallax-content h1,
        .parallax-content h2,
        .parallax-content h3,
        .parallax-content h4,
        .parallax-content h5,
        .parallax-content h6 {
            text-shadow: 3px 3px 6px rgba(0,0,0,0.9);
            color: white !important;
        }
        
        .parallax-content p {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
            color: white !important;
        }
        
        .parallax-content a {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
            color: white !important;
        }
        
        /* Support for inline HTML elements like span, strong, em, etc. */
        .parallax-content span,
        .parallax-content strong,
        .parallax-content em,
        .parallax-content code,
        .parallax-content mark {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
            color: white !important;
        }
        
        /* Ensure content is properly spaced */
        .parallax-content > *:first-child {
            margin-top: 0;
        }
        
        .parallax-content > *:last-child {
            margin-bottom: 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .parallax-section {
                min-height: 400px;
            }
            
            .parallax-content {
                padding: 2rem 1rem;
                min-height: 300px;
            }
        }
        
        /* Ensure proper z-index layering */
        .parallax-section {
            z-index: 1;
        }
        
        .parallax-background {
            z-index: 1;
        }
        
        .parallax-overlay {
            z-index: 2;
        }
        
        .parallax-content {
            z-index: 3;
        }
        
        /* Add smooth transitions */
        .parallax-section {
            transition: all 0.3s ease;
            /* Remove any default margins that could affect positioning */
            margin: 0;
            padding: 0;
        }
        

        
        .parallax-background {
            transition: transform 0.3s ease;
        }
        
        /* Ensure proper text contrast */
        .parallax-content * {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
        }
        
        /* Button styling for parallax sections */
        .parallax-content .btn-primary,
        .parallax-content .btn-secondary {
            background: rgba(255,255,255,0.9);
            color: #333 !important;
            text-shadow: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .parallax-content .btn-primary:hover,
        .parallax-content .btn-secondary:hover {
            background: rgba(255,255,255,1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        /* Grid layout for feature cards */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .feature-card {
            background: rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.4);
        }
        
        .feature-card h3 {
            color: white;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: rgba(255,255,255,0.9);
            line-height: 1.6;
        }
    </style>
    
    <script>
        // Parallax scrolling effect
        function initParallax() {
            const parallaxSections = document.querySelectorAll('.parallax-section');
            
            if (parallaxSections.length === 0) return;
            
            // Set up scroll event listener
            window.addEventListener('scroll', function() {
                parallaxSections.forEach(function(section, index) {
                    const speed = parseFloat(section.dataset.speed) || 0.5;
                    const effect = section.dataset.effect || 'scroll';
                    const background = section.querySelector('.parallax-background');
                    
                    if (!background) return;
                    
                    if (effect === 'scroll') {
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        // Use a simpler, more reliable parallax calculation
                        // This prevents the initial upward motion by using a cleaner approach
                        const rate = scrolled * speed;
                        const finalRate = rate * 0.3;
                        background.style.transform = `translateY(${finalRate}px)`;
                    } else if (effect === 'fixed') {
                        // Fixed effect: background stays in place while content scrolls
                        background.style.transform = 'translateY(0)';
                        background.style.position = 'fixed';
                    } else if (effect === 'scale') {
                        // Scale effect: background scales up/down during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        const scale = 1 + (rate * 0.001); // Scale factor
                        background.style.transform = `scale(${Math.max(0.5, Math.min(2.0, scale))})`;
                    } else if (effect === 'rotate') {
                        // Rotation effect: background rotates during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        const rotation = rate * 0.1; // Rotation in degrees
                        background.style.transform = `rotate(${rotation}deg)`;
                    } else if (effect === 'fade') {
                        // Fade effect: background opacity changes during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        const opacity = Math.max(0.1, Math.min(1.0, 1 - (Math.abs(rate) * 0.001)));
                        background.style.opacity = opacity;
                    } else if (effect === 'fade-in') {
                        // Fade-in effect: content gradually appears as you scroll down
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        
                        // Get configurable parameters from data attributes
                        const fadeStartPercent = parseFloat(section.dataset.fadeStartPercent) || 25; // Default: start when 25% into viewport
                        const fadeDistance = parseFloat(section.dataset.fadeDistance) || 50; // Default: fade over 50% of viewport
                        const startOpacity = parseFloat(section.dataset.startOpacity) || 0.1; // Default: start at 10% opacity
                        const startOffset = parseFloat(section.dataset.startOffset) || 20; // Default: start 20px down
                        
                        // Only start fade-in when section is partially visible (viewport is over the area)
                        const viewportHeight = window.innerHeight;
                        const sectionVisible = viewportHeight - rect.top;
                        const fadeStartThreshold = viewportHeight * (fadeStartPercent / 100);
                        
                        if (sectionVisible > fadeStartThreshold) {
                            // Calculate fade-in progress based on how much of the section is visible
                            const fadeProgress = Math.min(1.0, (sectionVisible - fadeStartThreshold) / (viewportHeight * (fadeDistance / 100)));
                            const opacity = Math.max(startOpacity, Math.min(1.0, startOpacity + (fadeProgress * (1.0 - startOpacity))));
                            const content = section.querySelector('.parallax-content');
                            if (content) {
                                content.style.opacity = opacity;
                                content.style.transform = `translateY(${Math.max(0, startOffset - (opacity * startOffset))}px)`;
                            }
                        } else {
                            // Keep content hidden until fade-in threshold is met
                            const content = section.querySelector('.parallax-content');
                            if (content) {
                                content.style.opacity = startOpacity;
                                content.style.transform = `translateY(${startOffset}px)`;
                            }
                        }
                    } else if (effect === 'blur') {
                        // Blur effect: background starts clear, gets blurrier during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        // Start with no blur (0px) and increase as you scroll down
                        const blur = Math.max(0, Math.min(8, Math.abs(rate) * 0.005)); // More subtle blur increase
                        background.style.filter = `blur(${blur}px)`;
                    } else if (effect === 'slide') {
                        // Slide effect: background slides horizontally during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        const slideRate = rate * 0.5;
                        background.style.transform = `translateX(${slideRate}px)`;
                    } else if (effect === 'zoom') {
                        // Zoom effect: background zooms in/out during scroll
                        const rect = section.getBoundingClientRect();
                        const scrolled = window.pageYOffset;
                        const sectionTop = rect.top + scrolled;
                        const rate = (scrolled - sectionTop) * speed;
                        const zoom = 1 + (rate * 0.0005); // Zoom factor
                        background.style.transform = `scale(${Math.max(0.3, Math.min(3.0, zoom))})`;
                    }
                });
            });
            
            // Initialize overlay styles
            setOverlayStyles();
        }
        
        // Set overlay styles for parallax sections
        function setOverlayStyles() {
            const parallaxSections = document.querySelectorAll('.parallax-section');
            
            parallaxSections.forEach(function(section, index) {
                const overlayColor = section.dataset.overlayColor || 'rgba(0,0,0,0.4)';
                const overlayOpacity = section.dataset.overlayOpacity || '0.4';
                
                let finalColor = overlayColor;
                if (overlayColor.startsWith("rgba")) {
                    finalColor = overlayColor;
                } else if (overlayColor.startsWith("#")) {
                    finalColor = hexToRgba(overlayColor, overlayOpacity);
                } else {
                    finalColor = colorNameToRgba(overlayColor, overlayOpacity);
                }
                
                console.log("Final color: " + finalColor);
                
                // Apply the overlay color directly to the ::before pseudo-element
                const overlayDiv = document.createElement("div");
                overlayDiv.className = "parallax-overlay";
                overlayDiv.style.cssText = "position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: " + finalColor + "; z-index: 2;";
                
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
        
        // Helper function to convert hex to rgba
        function hexToRgba(hex, opacity) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return "rgba(" + r + "," + g + "," + b + "," + opacity + ")";
        }
        
        // Helper function to convert color names to rgba
        function colorNameToRgba(colorName, opacity) {
            const colors = {
                "black": "0,0,0",
                "white": "255,255,255",
                "red": "255,0,0",
                "green": "0,128,0",
                "blue": "0,0,255",
                "yellow": "255,255,0",
                "purple": "128,0,128",
                "orange": "255,165,0",
                "pink": "255,192,203",
                "brown": "165,42,42",
                "gray": "128,128,128",
                "grey": "128,128,128"
            };
            
            const rgb = colors[colorName.toLowerCase()] || "0,0,0";
            return "rgba(" + rgb + "," + opacity + ")";
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
    </script>
ASSETS;
}
?> 