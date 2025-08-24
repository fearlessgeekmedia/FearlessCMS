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
    // Add CSS and JavaScript to the page
    parallax_enqueue_assets();
}

// Process parallax shortcodes
function parallax_process_shortcode($content) {
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parallax plugin called with content length: " . strlen($content));
        error_log("Content preview: " . substr($content, 0, 500));
    }
    
    // Wrap feature sections in div containers for styling
    $content = wrapFeatureSections($content);
    
    // Now use a simple regex that works reliably
    $content = preg_replace_callback('/\[parallax_section(.*?)\](.*?)\[\/parallax_section\]/s', 'process_parallax_shortcode', $content);
    
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
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Parsed attributes - ID: $id, Background: $background_image, Speed: $speed, Effect: $effect, Overlay: $overlay_color, Opacity: $overlay_opacity");
    }
    
    // Validate required attributes
    if (empty($id) || empty($background_image)) {
        return '<div class="alert alert-danger">Parallax section requires both id and background_image attributes</div>';
    }
    
    // Generate unique CSS class
    $css_class = 'parallax-section-' . sanitize_id($id);
    
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
    $output = '<div id="' . htmlspecialchars($id) . '" class="' . $css_class . ' parallax-section" data-speed="' . htmlspecialchars($speed) . '" data-effect="' . htmlspecialchars($effect) . '" data-overlay-color="' . htmlspecialchars($overlay_color) . '" data-overlay-opacity="' . htmlspecialchars($overlay_opacity) . '">';
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

// Add CSS and JavaScript to the page
function parallax_enqueue_assets() {
    // Add CSS
    echo <<<'CSS'
<style>
        .parallax-section {
            position: relative;
            overflow: hidden;
            width: 100%;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Ensure sections have adequate height for content */
            min-height: 500px;
            height: auto;
            /* Ensure proper background coverage */
            background: transparent;
        }
        
        .parallax-background {
            position: absolute;
            top: 60% !important;
            left: 50%;
            width: 120%;
            height: 120%;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: scroll;
            z-index: 1;
            /* Center using transform, then adjust for parallax */
            transform: translate(-50%, -50%);
            will-change: transform;
            /* Prevent any gaps during scroll */
            background-clip: border-box;
        }
        
        .parallax-content {
            position: relative;
            z-index: 3;
            padding: 4rem 2rem;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            /* Ensure content takes full height */
            width: 100%;
            height: 100%;
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
            
            .parallax-background {
                top: 60% !important;
                width: 130%;
                height: 130%;
            }
        }
        
        @media (max-width: 480px) {
            .parallax-section {
                min-height: 350px;
            }
            
            .parallax-content {
                padding: 1.5rem 1rem;
                min-height: 250px;
            }
            
            .parallax-background {
                top: 60% !important;
                width: 140%;
                height: 140%;
            }
        }
        
        /* Ensure sections maintain proper aspect ratio */
        .parallax-section {
            box-sizing: border-box;
        }
        
        /* Fix for content overflow */
        .parallax-content > * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Ensure background images always cover the full section */
        .parallax-background {
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            /* Enhanced coverage for scrolling */
            background-clip: border-box !important;
            /* Ensure background extends beyond section boundaries during scroll */
            transform-origin: center center !important;
        }
        
        /* Additional rules for better background coverage during scroll */
        .parallax-section {
            /* Ensure section maintains proper dimensions during scroll */
            transform: translateZ(0);
            backface-visibility: hidden;
        }
        
        /* Enhanced background coverage - no pseudo-elements needed */
        
        /* Ensure background covers everything - simplified approach */
        
        /* Clean, unified background coverage rules */
        .parallax-section {
            overflow: hidden;
            position: relative;
        }
        
        /* Ensure smooth scrolling and prevent background gaps */
        .parallax-section {
            /* Hardware acceleration */
            transform: translateZ(0);
            /* Prevent background clipping */
            clip-path: none;
            /* Ensure proper stacking context */
            isolation: isolate;
        }
        
        /* Ensure background images are fully loaded before display */
        .parallax-background {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .parallax-background.loaded {
            opacity: 1;
        }
        
        /* Fallback for images that fail to load */
        .parallax-background:not([style*="background-image"]) {
            background-color: #f0f0f0;
        }
        
        /* Styling for regular content sections between parallax */
        .parallax-section + h2 {
            margin-top: 4rem;
            margin-bottom: 2rem;
        }
        
        h2 + p {
            margin-top: 1rem;
            margin-bottom: 3rem;
        }
        
        /* Professional section headers */
        h2 {
            color: #1a202c;
            font-size: 2.8rem;
            font-weight: 800;
            text-align: center;
            margin: 3rem 0 1.5rem 0;
            padding: 0;
            border: none;
            background: none;
            position: relative;
            letter-spacing: -0.025em;
        }
        
        /* Add elegant underline to section headers */
        h2 {
            border-bottom: 4px solid transparent;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6) bottom;
            background-size: 60px 4px;
            background-repeat: no-repeat;
            background-position: center bottom;
            padding-bottom: 20px;
        }
        
        /* Section descriptions */
        h2 + p {
            text-align: center;
            font-size: 1.25rem;
            color: #4a5568;
            max-width: 900px;
            margin: 0 auto 3rem auto;
            line-height: 1.7;
            padding: 0 2rem;
            font-weight: 400;
        }
        
        /* Feature Cards - 2 Column Grid Layout (temporary placement for testing) */
        .feature-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 1.5rem !important;
            margin: 0.5rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            display: inline-block !important;
            width: calc(50% - 1rem) !important;
            vertical-align: top !important;
            min-height: 120px !important;
            border-top: 4px solid transparent !important;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899) top, linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
            background-size: 100% 4px, 100% 100% !important;
            background-repeat: no-repeat !important;
            background-position: center top, center center !important;
            padding-top: 20px !important;
        }
        
        /* Style the h3 headers within feature cards */
        .feature-card h3 {
            background: none !important;
            border: none !important;
            padding: 0 0 1rem 0 !important;
            margin: 0 0 1rem 0 !important;
            box-shadow: none !important;
            font-size: 1.3rem !important;
            font-weight: 700 !important;
            line-height: 1.4 !important;
            color: #1e293b !important;
            text-align: center !important;
            min-height: auto !important;
            width: 100% !important;
            display: block !important;
        }
        
        /* Style the description text within feature cards */
        .feature-card p {
            margin: 0 !important;
            padding: 0 !important;
            color: #4a5568 !important;
            font-size: 1rem !important;
            line-height: 1.6 !important;
            text-align: center !important;
        }
        
        /* Hover effects for feature cards */
        .feature-card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border-color: #cbd5e1 !important;
        }
        
        /* Create a container for the features that will force the grid layout */
        .parallax-section + h2 + p {
            display: block !important;
            clear: both !important;
            margin-bottom: 2rem !important;
        }
        
        /* Force the first feature card to start a new line */
        .parallax-section + h2 + p + .feature-card {
            clear: both !important;
        }
        
        /* Responsive grid adjustments */
        @media (max-width: 768px) {
            .feature-card {
                width: calc(100% - 1rem) !important;
                display: block !important;
                clear: both !important;
            }
        }
</style>
CSS;
    
    // Add JavaScript
    echo <<<'JS'
<script>
        function initParallax() {
            const parallaxSections = document.querySelectorAll(".parallax-section");
            
            if (parallaxSections.length === 0) {
                console.log("No parallax sections found");
                return;
            }
            
            console.log("Found " + parallaxSections.length + " parallax sections");
            
            // Set up scroll event listener for parallax effect
            window.addEventListener("scroll", function() {
                parallaxSections.forEach(function(section) {
                    const rect = section.getBoundingClientRect();
                    const speed = parseFloat(section.dataset.speed) || 0.5;
                    const effect = section.dataset.effect || "scroll";
                    
                    // Apply parallax to visible sections
                    if (rect.top < window.innerHeight && rect.bottom > 0) {
                        const background = section.querySelector(".parallax-background");
                        if (background) {
                            const scrolled = window.pageYOffset;
                            const sectionTop = section.offsetTop;
                            
                            // Simple parallax calculation relative to section position
                            const relativeScroll = scrolled - sectionTop;
                            const rate = relativeScroll * speed * 0.5;
                            
                            // Apply transform while maintaining centering
                            background.style.transform = "translate(-50%, calc(-50% + " + rate + "px))";
                        }
                    }
                });
            });
        }
        
        // Function to set overlay colors and opacity
        function setOverlayStyles() {
            console.log("Setting overlay styles...");
            const parallaxSections = document.querySelectorAll(".parallax-section");
            console.log("Found " + parallaxSections.length + " parallax sections");
            
            parallaxSections.forEach(function(section, index) {
                const overlayColor = section.dataset.overlayColor || "rgba(0,0,0,0.4)";
                const overlayOpacity = section.dataset.overlayOpacity || "0.4";
                
                console.log("Section " + index + " - Color: " + overlayColor + ", Opacity: " + overlayOpacity);
                
                // Convert color names to hex if needed
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
                overlayDiv.style.cssText = "position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: " + finalColor + "; z-index: 1;";
                
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
JS;
}
?> 