<?php
// Simple test to verify parallax plugin functionality
echo "Testing parallax plugin...\n";

// Test the shortcode processing function directly
$test_content = '[parallax_section id="test" background_image="/test.jpg" speed="0.5" effect="scroll"]Test content[/parallax_section]';

if (function_exists('parallax_process_shortcode')) {
    echo "Function exists!\n";
    $result = parallax_process_shortcode($test_content);
    echo "Result: " . $result . "\n";
} else {
    echo "Function does not exist!\n";
}

// Test hook registration
if (function_exists('fcms_add_hook')) {
    echo "Hook system available!\n";
} else {
    echo "Hook system not available!\n";
}

echo "Test complete.\n"; 