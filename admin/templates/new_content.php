<?php
// Minimal test - just basic PHP output
echo "PHP is working!";
echo "<br>";
echo "Time: " . date('Y-m-d H:i:s');
echo "<br>";
echo "PHP version: " . phpversion();
echo "<br>";
echo "Memory usage: " . memory_get_usage() . " bytes";
echo "<br>";
echo "Max memory: " . ini_get('memory_limit');
echo "<br>";
echo "Error reporting: " . error_reporting();
echo "<br>";
echo "Display errors: " . ini_get('display_errors');
echo "<br>";

// Test basic HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>HTML is working!</h1>
    <p>If you see this, both PHP and HTML are working.</p>
    
    <form method="POST" action="?action=create_page">
        <input type="hidden" name="action" value="create_page">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
        
        <div style="margin: 20px 0; padding: 10px; border: 2px solid red; background: #ffe6e6;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Title:</label>
            <input type="text" name="page_title" required style="display: block !important; visibility: visible !important; width: 100%; padding: 8px; border: 2px solid blue; background: white; color: black;">
        </div>
        
        <div style="margin: 20px 0; padding: 10px; border: 2px solid red; background: #ffe6e6;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">URL Slug:</label>
            <input type="text" name="new_page_filename" required style="display: block !important; visibility: visible !important; width: 100%; padding: 8px; border: 2px solid blue; background: white; color: black;">
        </div>
        
        <div style="margin: 20px 0; padding: 10px; border: 2px solid red; background: #ffe6e6;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Template:</label>
            <select name="template" style="display: block !important; visibility: visible !important; width: 100%; padding: 8px; border: 2px solid blue; background: white; color: black;">
                <option value="page">Page</option>
                <option value="home">Home</option>
                <option value="blog">Blog</option>
            </select>
        </div>
        
        <div style="margin: 20px 0; padding: 10px; border: 2px solid red; background: #ffe6e6;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Parent Page:</label>
            <select name="parent_page" style="display: block !important; visibility: visible !important; width: 100%; padding: 8px; border: 2px solid blue; background: white; color: black;">
                <option value="">None (Top Level)</option>
                <option value="home">Home</option>
                <option value="documentation">Documentation</option>
            </select>
        </div>
        
        <div style="margin: 20px 0; padding: 10px; border: 2px solid red; background: #ffe6e6;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Content:</label>
            <textarea name="new_page_content" rows="10" cols="50" style="display: block !important; visibility: visible !important; width: 100%; padding: 8px; border: 2px solid blue; background: white; color: black; font-family: monospace;"></textarea>
        </div>
        
        <button type="submit" style="display: block !important; visibility: visible !important; padding: 10px 20px; background: green; color: white; border: none; margin: 20px 0;">Create Page</button>
    </form>
    
    <div style="margin: 20px 0; padding: 10px; border: 2px solid orange; background: #fff3cd;">
        <h3>Form Test Instructions:</h3>
        <p>1. Fill in the Title field (e.g., "Test Page")</p>
        <p>2. Fill in the URL Slug field (e.g., "test-page")</p>
        <p>3. Add some content to the Content field</p>
        <p>4. Select a Template and Parent Page</p>
        <p>5. Click "Create Page" to test if the form submission works</p>
    </div>
</body>
</html>
