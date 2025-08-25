<?php
/*
Plugin Name: SEO
Description: Adds basic SEO features to FearlessCMS
Version: 1.0
Author: Claude
*/

// Define constants
define('SEO_CONFIG_FILE', ADMIN_CONFIG_DIR . '/seo_settings.json');

// Register admin section
fcms_register_admin_section('seo', [
    'label' => 'SEO',
    'menu_order' => 30,
    'parent' => 'manage_plugins',
    'render_callback' => 'seo_admin_page'
]);

// Hook into page rendering to add meta tags
fcms_add_hook('before_render', 'seo_inject_meta_tags');

/**
 * Render the SEO admin page
 */
function seo_admin_page() {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_seo_settings') {
        $settings = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'title_separator' => trim($_POST['title_separator'] ?? '-'),
            'append_site_title' => isset($_POST['append_site_title']),
            'social_image' => trim($_POST['social_image'] ?? '')
        ];
        
        file_put_contents(SEO_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
        $success_message = 'SEO settings saved successfully!';
    }
    
    // Load current settings
    $settings = seo_get_settings();
    
    // Start output buffer
    ob_start();
    
    // Display success message if any
    if (isset($success_message)) {
        echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . htmlspecialchars($success_message) . '</div>';
    }
    
    // Render form
    ?>
    <h2 class="text-2xl font-bold mb-6 fira-code">SEO Settings</h2>
    
    <form method="POST" class="space-y-6">
        <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
        <input type="hidden" name="action" value="save_seo_settings">
        
        <div>
            <label class="block font-medium mb-1">Site Title</label>
            <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" 
                   class="w-full border rounded px-3 py-2">
            <p class="text-sm text-gray-600 mt-1">The name of your website (used as default title and in meta tags)</p>
        </div>
        
        <div>
            <label class="block font-medium mb-1">Site Description</label>
            <textarea name="site_description" rows="3" 
                      class="w-full border rounded px-3 py-2"><?= htmlspecialchars($settings['site_description']) ?></textarea>
            <p class="text-sm text-gray-600 mt-1">A short description of your website (used in meta description)</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-medium mb-1">Title Separator</label>
                <input type="text" name="title_separator" value="<?= htmlspecialchars($settings['title_separator']) ?>" 
                       class="w-full border rounded px-3 py-2">
                <p class="text-sm text-gray-600 mt-1">Character used between page title and site title</p>
            </div>
            
            <div>
                <label class="block font-medium mb-1">Append Site Title</label>
                <div class="mt-2">
                    <input type="checkbox" name="append_site_title" id="append_site_title" 
                           <?= $settings['append_site_title'] ? 'checked' : '' ?>>
                    <label for="append_site_title">Add site title after page title</label>
                </div>
                <p class="text-sm text-gray-600 mt-1">Example: Page Title <?= htmlspecialchars($settings['title_separator']) ?> <?= htmlspecialchars($settings['site_title']) ?></p>
            </div>
        </div>
        
        <div>
            <label class="block font-medium mb-1">Default Social Image URL</label>
            <input type="text" name="social_image" value="<?= htmlspecialchars($settings['social_image']) ?>" 
                   class="w-full border rounded px-3 py-2">
            <p class="text-sm text-gray-600 mt-1">Image used when sharing on social media (absolute URL recommended)</p>
        </div>
        
        <div>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Save Settings
            </button>
        </div>
    </form>
    
    <div class="mt-8 p-4 bg-gray-100 rounded">
        <h3 class="text-lg font-medium mb-2">How to use SEO in your content</h3>
        <p class="mb-2">Add JSON frontmatter to your markdown files to customize SEO for each page:</p>
        <pre class="bg-gray-800 text-white p-3 rounded overflow-x-auto">
&lt;!-- json
{
    "title": "Your Page Title",
    "description": "Your page description for search engines",
    "social_image": "https://example.com/image.jpg"
}
--&gt;

# Your Page Content
        </pre>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Get SEO settings with defaults
 */
function seo_get_settings() {
    $defaults = [
        'site_title' => 'My Website',
        'site_description' => '',
        'title_separator' => '-',
        'append_site_title' => true,
        'social_image' => ''
    ];
    
    if (file_exists(SEO_CONFIG_FILE)) {
        $settings = json_decode(file_get_contents(SEO_CONFIG_FILE), true);
        if (is_array($settings)) {
            return array_merge($defaults, $settings);
        }
    }
    
    return $defaults;
}

/**
 * Extract metadata from content
 */
function seo_get_page_metadata($content) {
    $metadata = [
        'title' => null,
        'description' => null,
        'social_image' => null
    ];

    // Ensure $content is a string to avoid deprecation warnings in PHP 8.1+
    if (!is_string($content)) {
        $content = '';
    }
    
    // Extract JSON frontmatter if present
    if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
        $json = json_decode($matches[1], true);
        if (is_array($json)) {
            if (isset($json['title'])) $metadata['title'] = $json['title'];
            if (isset($json['description'])) $metadata['description'] = $json['description'];
            if (isset($json['social_image'])) $metadata['social_image'] = $json['social_image'];
        }
    }
    
    return $metadata;
}

/**
 * Inject SEO meta tags into the page
 */
function seo_inject_meta_tags(&$template) {
    global $title, $content;
    
    $settings = seo_get_settings();
    $metadata = seo_get_page_metadata($content);
    
    // Determine page title
    $page_title = $metadata['title'] ?? $title ?? '';
    
    // Build full title
    $full_title = $page_title;
    if ($settings['append_site_title'] && !empty($page_title) && !empty($settings['site_title'])) {
        $full_title .= ' ' . $settings['title_separator'] . ' ' . $settings['site_title'];
    } elseif (empty($page_title) && !empty($settings['site_title'])) {
        $full_title = $settings['site_title'];
    }
    
    // Get description
    $description = $metadata['description'] ?? $settings['site_description'] ?? '';
    
    // Get social image
    $social_image = $metadata['social_image'] ?? $settings['social_image'] ?? '';
    
    // Build meta tags
    $meta_tags = '';
    
    // Basic meta tags
    if (!empty($description)) {
        $meta_tags .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    
    // Open Graph meta tags
    $meta_tags .= '<meta property="og:type" content="website">' . "\n";
    if (!empty($full_title)) {
        $meta_tags .= '<meta property="og:title" content="' . htmlspecialchars($full_title) . '">' . "\n";
    }
    if (!empty($description)) {
        $meta_tags .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    if (!empty($social_image)) {
        $meta_tags .= '<meta property="og:image" content="' . htmlspecialchars($social_image) . '">' . "\n";
    }
    
    // Twitter Card meta tags
    $meta_tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    if (!empty($full_title)) {
        $meta_tags .= '<meta name="twitter:title" content="' . htmlspecialchars($full_title) . '">' . "\n";
    }
    if (!empty($description)) {
        $meta_tags .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    if (!empty($social_image)) {
        $meta_tags .= '<meta name="twitter:image" content="' . htmlspecialchars($social_image) . '">' . "\n";
    }
    
    // Replace title tag
    if (!empty($full_title)) {
        $template = preg_replace('/<title>.*?<\/title>/i', '<title>' . htmlspecialchars($full_title) . '</title>', $template);
    }
    
    // Insert meta tags before </head>
    $template = str_replace('</head>', $meta_tags . '</head>', $template);
}
