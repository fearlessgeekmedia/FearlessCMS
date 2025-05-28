<?php
/*
Plugin Name: WordPress Import
Description: Import content from WordPress XML export files into FearlessCMS
Version: 1.0
Author: Fearless Geek
*/

// Register admin section
fcms_register_admin_section('wordpress_import', [
    'label' => 'WordPress Import',
    'menu_order' => 1,
    'parent' => 'plugins',
    'render_callback' => 'wordpress_import_admin_page'
]);

/**
 * Handle WordPress import process
 */
function wordpress_import_process($file) {
    if (!file_exists($file)) {
        return ['success' => false, 'error' => 'File not found'];
    }

    // Load and parse WordPress XML
    $xml = simplexml_load_file($file);
    if (!$xml) {
        return ['success' => false, 'error' => 'Invalid WordPress XML file'];
    }

    $imported = [
        'posts' => 0,
        'pages' => 0,
        'categories' => 0,
        'tags' => 0,
        'media' => 0
    ];

    // Process categories first
    foreach ($xml->channel->category as $category) {
        $cat_name = (string)$category->cat_name;
        $cat_slug = (string)$category->category_nicename;
        
        // Create category directory if it doesn't exist
        $cat_dir = CONTENT_DIR . '/' . $cat_slug;
        if (!is_dir($cat_dir)) {
            mkdir($cat_dir, 0755, true);
        }
        
        $imported['categories']++;
    }

    // Process posts and pages
    foreach ($xml->channel->item as $item) {
        $type = (string)$item->children('wp', true)->post_type;
        $status = (string)$item->children('wp', true)->status;
        
        // Skip if not published
        if ($status !== 'publish') continue;

        // Get post data
        $title = (string)$item->title;
        $content = (string)$item->children('content', true);
        $excerpt = (string)$item->children('excerpt', true);
        $date = (string)$item->pubDate;
        $slug = (string)$item->children('wp', true)->post_name;

        // Get categories and tags
        $categories = [];
        $tags = [];
        foreach ($item->category as $cat) {
            $domain = (string)$cat['domain'];
            if ($domain === 'category') {
                $categories[] = (string)$cat;
            } elseif ($domain === 'post_tag') {
                $tags[] = (string)$cat;
            }
        }

        // Create metadata
        $metadata = [
            'title' => $title,
            'date' => date('Y-m-d', strtotime($date)),
            'template' => $type === 'page' ? 'page' : 'post',
            'categories' => $categories,
            'tags' => $tags,
            'excerpt' => $excerpt
        ];

        // Determine content path
        if ($type === 'page') {
            $content_path = CONTENT_DIR . '/' . $slug . '.md';
        } else {
            // For posts, use first category or 'uncategorized'
            $category = !empty($categories) ? $categories[0] : 'uncategorized';
            $content_path = CONTENT_DIR . '/' . strtolower($category) . '/' . $slug . '.md';
        }

        // Format content with metadata
        $content_with_metadata = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->' . "\n\n" . $content;

        // Save content
        if (file_put_contents($content_path, $content_with_metadata)) {
            $imported[$type === 'page' ? 'pages' : 'posts']++;
        }

        // Process attachments
        if ($type === 'attachment') {
            $imported['media']++;
        }
    }

    return [
        'success' => true,
        'imported' => $imported
    ];
}

/**
 * Render the WordPress import admin page
 */
function wordpress_import_admin_page() {
    $message = '';
    $error = '';

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['wordpress_xml'])) {
        $file = $_FILES['wordpress_xml'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $result = wordpress_import_process($file['tmp_name']);
            
            if ($result['success']) {
                $message = sprintf(
                    'Import completed successfully! Imported %d posts, %d pages, %d categories, %d tags, and %d media files.',
                    $result['imported']['posts'],
                    $result['imported']['pages'],
                    $result['imported']['categories'],
                    $result['imported']['tags'],
                    $result['imported']['media']
                );
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'Error uploading file';
        }
    }

    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Import WordPress Content</h3>
            
            <div class="space-y-4">
                <p class="text-gray-600">
                    Upload your WordPress XML export file to import content into FearlessCMS.
                    The import process will:
                </p>
                
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>Convert posts and pages to Markdown format</li>
                    <li>Preserve categories and tags</li>
                    <li>Maintain post dates and metadata</li>
                    <li>Create appropriate directory structure</li>
                </ul>
                
                <form method="POST" enctype="multipart/form-data" class="mt-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-1">WordPress XML File</label>
                            <input type="file" name="wordpress_xml" accept=".xml" required
                                   class="w-full border rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                Start Import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
} 