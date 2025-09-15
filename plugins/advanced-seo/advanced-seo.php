<?php
/*
Plugin Name: Advanced SEO Premium
Description: Premium SEO features for FearlessCMS including Meta Editor, Open Graph Preview, 404 Monitoring, XML sitemap, structured data, and advanced meta tags
Version: 0.0.3
Author: Fearless Geek
Premium: true
*/

// Define constants
define('ADVANCED_SEO_CONFIG_FILE', ADMIN_CONFIG_DIR . '/advanced_seo_settings.json');
define('ADVANCED_SEO_META_FILE', ADMIN_CONFIG_DIR . '/page_meta_tags.json');
define('ADVANCED_SEO_404_LOG_FILE', ADMIN_CONFIG_DIR . '/404_errors.json');
define('ADVANCED_SEO_LICENSE_FILE', ADMIN_CONFIG_DIR . '/advanced_seo_license.json');
define('SITEMAP_FILE', PROJECT_ROOT . '/sitemap.xml');
define('ROBOTS_FILE', PROJECT_ROOT . '/robots.txt');

// License verification constants
define('ADVANCED_SEO_LICENSE_SERVER', 'http://localhost:9090');
define('ADVANCED_SEO_PRODUCT_ID', 'advanced_seo_premium_v2');

// Register all Advanced SEO Premium admin sections as children of 'plugins'
fcms_register_admin_section('advanced_seo', [
    'label' => 'Advanced SEO Premium',
    'menu_order' => 2,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_admin_page'
]);

fcms_register_admin_section('advanced_seo_license', [
    'label' => 'License',
    'menu_order' => 3,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_license_page'
]);

fcms_register_admin_section('advanced_seo_meta_editor', [
    'label' => 'Meta Editor',
    'menu_order' => 4,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_meta_editor_page'
]);

fcms_register_admin_section('advanced_seo_og_preview', [
    'label' => 'Open Graph Preview',
    'menu_order' => 5,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_og_preview_page'
]);

fcms_register_admin_section('advanced_seo_404_monitor', [
    'label' => '404 Monitor',
    'menu_order' => 6,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_404_monitor_page'
]);

// Register hooks
fcms_add_hook('before_render', 'advanced_seo_inject_meta_tags');
fcms_add_hook('init', 'advanced_seo_generate_sitemap');
fcms_add_hook('404_error', 'advanced_seo_log_404_error');
fcms_add_hook('init', 'advanced_seo_check_redirects');

/**
 * Get advanced SEO settings with defaults
 */
function advanced_seo_get_settings() {
    $defaults = [
        'site_title' => 'My Website',
        'site_description' => '',
        'title_separator' => '-',
        'append_site_title' => true,
        'social_image' => '',
        'google_analytics_id' => '',
        'google_tag_manager_id' => '',
        'facebook_pixel_id' => '',
        'twitter_handle' => '',
        'schema_type' => 'WebSite',
        'schema_name' => '',
        'schema_description' => '',
        'schema_logo' => '',
        'schema_social_profiles' => [],
        'sitemap_enabled' => true,
        'sitemap_priority' => 0.5,
        'sitemap_changefreq' => 'weekly',
        'robots_txt' => "User-agent: *\nAllow: /\nSitemap: /sitemap.xml",
        'meta_robots' => 'index,follow',
        'canonical_url' => '',
        'hreflang_tags' => [],
        '404_redirects' => []
    ];
    
    if (file_exists(ADVANCED_SEO_CONFIG_FILE)) {
        $settings = json_decode(file_get_contents(ADVANCED_SEO_CONFIG_FILE), true);
        if (is_array($settings)) {
            return array_merge($defaults, $settings);
        }
    }
    
    return $defaults;
}

/**
 * Generate XML sitemap
 */
function advanced_seo_generate_sitemap() {
    $settings = advanced_seo_get_settings();
    if (!$settings['sitemap_enabled']) return;

    // Check if required constants are defined
    if (!defined('CONTENT_DIR')) {
        error_log('Advanced SEO: CONTENT_DIR constant is not defined');
        return;
    }

    $pages = [];
    $contentDir = CONTENT_DIR;
    
    // Check if content directory exists
    if (!is_dir($contentDir)) {
        error_log('Advanced SEO: Content directory does not exist: ' . $contentDir);
        return;
    }
    
    // Get all markdown files recursively
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contentDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'md') {
                $relativePath = str_replace($contentDir . '/', '', $file->getPathname());
                $relativePath = str_replace('.md', '', $relativePath);
                
                // Skip preview files
                if (strpos($relativePath, '_preview/') === 0) continue;
                
                $url = '/' . $relativePath;
                $lastmod = date('Y-m-d', $file->getMTime());
                
                $pages[] = [
                    'url' => $url,
                    'lastmod' => $lastmod,
                    'priority' => $settings['sitemap_priority'],
                    'changefreq' => $settings['sitemap_changefreq']
                ];
            }
        }
    } catch (Exception $e) {
        error_log('Advanced SEO: Error generating sitemap: ' . $e->getMessage());
        return;
    }
    
    // Generate sitemap XML using string concatenation
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($pages as $page) {
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>https://' . htmlspecialchars($_SERVER['HTTP_HOST']) . htmlspecialchars($page['url']) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . htmlspecialchars($page['lastmod']) . '</lastmod>' . "\n";
        $xml .= '    <priority>' . htmlspecialchars($page['priority']) . '</priority>' . "\n";
        $xml .= '    <changefreq>' . htmlspecialchars($page['changefreq']) . '</changefreq>' . "\n";
        $xml .= '  </url>' . "\n";
    }
    
    $xml .= '</urlset>';
    
    // Save sitemap
    if (defined('SITEMAP_FILE')) {
        file_put_contents(SITEMAP_FILE, $xml);
    } else {
        error_log('Advanced SEO: SITEMAP_FILE constant is not defined');
    }
    
    // Generate robots.txt
    if (defined('ROBOTS_FILE')) {
        file_put_contents(ROBOTS_FILE, $settings['robots_txt']);
    } else {
        error_log('Advanced SEO: ROBOTS_FILE constant is not defined');
    }
}

/**
 * Generate structured data (JSON-LD)
 */
function advanced_seo_generate_structured_data($pageData) {
    $settings = advanced_seo_get_settings();
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $settings['schema_type'],
        'name' => $settings['schema_name'] ?: $pageData['title'],
        'description' => $settings['schema_description'] ?: $pageData['description'],
        'url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ];
    
    if ($settings['schema_logo']) {
        $schema['logo'] = $settings['schema_logo'];
    }
    
    if (!empty($settings['schema_social_profiles'])) {
        $schema['sameAs'] = $settings['schema_social_profiles'];
    }
    
    return json_encode($schema);
}

/**
 * Inject advanced SEO meta tags
 */
function advanced_seo_inject_meta_tags(&$templateName) {
    global $title, $content, $templateData;
    
    $settings = advanced_seo_get_settings();
    $metadata = seo_get_page_metadata($content);
    
    // Check if plugin is licensed for premium features
    $is_licensed = advanced_seo_is_licensed();
    
    // Get current page path for page-specific meta tags (premium feature)
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    $current_path = trim($current_path, '/');
    if (empty($current_path)) $current_path = 'home';
    
    // Get page-specific meta tags (only if licensed)
    $page_meta_tags = [];
    if ($is_licensed) {
        $page_meta_tags = advanced_seo_get_page_meta_tags($current_path);
    }
    
    // Basic meta tags - use page-specific if licensed, otherwise fall back to defaults
    $page_title = '';
    if ($is_licensed && !empty($page_meta_tags['title'])) {
        $page_title = $page_meta_tags['title'];
    } else {
        $page_title = $metadata['title'] ?? $title ?? '';
    }
    
    $full_title = $page_title;
    if ($settings['append_site_title'] && !empty($page_title) && !empty($settings['site_title'])) {
        $full_title .= ' ' . $settings['title_separator'] . ' ' . $settings['site_title'];
    }
    
    // Enhanced meta tags - premium features only if licensed
    $templateData['meta_tags'] = [
        'title' => $full_title,
        'description' => $is_licensed && !empty($page_meta_tags['description']) 
            ? $page_meta_tags['description'] 
            : ($metadata['description'] ?? $settings['site_description'] ?? ''),
        'keywords' => $is_licensed ? ($page_meta_tags['keywords'] ?? '') : '',
        'robots' => $is_licensed && !empty($page_meta_tags['robots']) 
            ? $page_meta_tags['robots'] 
            : $settings['meta_robots'],
        'canonical' => $is_licensed && !empty($page_meta_tags['canonical_url']) 
            ? $page_meta_tags['canonical_url'] 
            : ($settings['canonical_url'] ?: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']),
        'social_image' => $is_licensed && !empty($page_meta_tags['og_image']) 
            ? $page_meta_tags['og_image'] 
            : ($metadata['social_image'] ?? $settings['social_image'] ?? ''),
        'twitter_card' => $is_licensed ? ($page_meta_tags['twitter_card'] ?? 'summary_large_image') : 'summary_large_image',
        'twitter_site' => $settings['twitter_handle'] ? '@' . $settings['twitter_handle'] : '',
        'twitter_title' => $is_licensed && !empty($page_meta_tags['twitter_title']) 
            ? $page_meta_tags['twitter_title'] 
            : $full_title,
        'twitter_description' => $is_licensed && !empty($page_meta_tags['twitter_description']) 
            ? $page_meta_tags['twitter_description'] 
            : ($page_meta_tags['description'] ?? $metadata['description'] ?? $settings['site_description'] ?? ''),
        'twitter_image' => $is_licensed && !empty($page_meta_tags['twitter_image']) 
            ? $page_meta_tags['twitter_image'] 
            : ($page_meta_tags['og_image'] ?? $metadata['social_image'] ?? $settings['social_image'] ?? ''),
        'og_title' => $is_licensed && !empty($page_meta_tags['og_title']) 
            ? $page_meta_tags['og_title'] 
            : $full_title,
        'og_description' => $is_licensed && !empty($page_meta_tags['og_description']) 
            ? $page_meta_tags['og_description'] 
            : ($page_meta_tags['description'] ?? $metadata['description'] ?? $settings['site_description'] ?? ''),
        'og_image' => $is_licensed && !empty($page_meta_tags['og_image']) 
            ? $page_meta_tags['og_image'] 
            : ($metadata['social_image'] ?? $settings['social_image'] ?? ''),
        'og_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'og_type' => $is_licensed ? ($page_meta_tags['og_type'] ?? 'website') : 'website'
    ];
    
    // Add structured data (basic version for non-licensed users)
    $templateData['structured_data'] = advanced_seo_generate_structured_data($templateData['meta_tags']);
    
    // Add tracking codes (always available)
    if ($settings['google_analytics_id']) {
        $templateData['google_analytics'] = $settings['google_analytics_id'];
    }
    if ($settings['google_tag_manager_id']) {
        $templateData['google_tag_manager'] = $settings['google_tag_manager_id'];
    }
    if ($settings['facebook_pixel_id']) {
        $templateData['facebook_pixel'] = $settings['facebook_pixel_id'];
    }
}

/**
 * Render the advanced SEO admin page
 */
function advanced_seo_admin_page() {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_advanced_seo_settings') {
        $settings = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'title_separator' => trim($_POST['title_separator'] ?? '-'),
            'append_site_title' => isset($_POST['append_site_title']),
            'social_image' => trim($_POST['social_image'] ?? ''),
            'google_analytics_id' => trim($_POST['google_analytics_id'] ?? ''),
            'google_tag_manager_id' => trim($_POST['google_tag_manager_id'] ?? ''),
            'facebook_pixel_id' => trim($_POST['facebook_pixel_id'] ?? ''),
            'twitter_handle' => trim($_POST['twitter_handle'] ?? ''),
            'schema_type' => trim($_POST['schema_type'] ?? 'WebSite'),
            'schema_name' => trim($_POST['schema_name'] ?? ''),
            'schema_description' => trim($_POST['schema_description'] ?? ''),
            'schema_logo' => trim($_POST['schema_logo'] ?? ''),
            'schema_social_profiles' => array_filter(array_map('trim', explode("\n", $_POST['schema_social_profiles'] ?? ''))),
            'sitemap_enabled' => isset($_POST['sitemap_enabled']),
            'sitemap_priority' => floatval($_POST['sitemap_priority'] ?? 0.5),
            'sitemap_changefreq' => trim($_POST['sitemap_changefreq'] ?? 'weekly'),
            'robots_txt' => trim($_POST['robots_txt'] ?? ''),
            'meta_robots' => trim($_POST['meta_robots'] ?? 'index,follow'),
            'canonical_url' => trim($_POST['canonical_url'] ?? ''),
            'hreflang_tags' => array_filter(array_map('trim', explode("\n", $_POST['hreflang_tags'] ?? ''))),
            '404_redirects' => []
        ];
        
        file_put_contents(ADVANCED_SEO_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
        $success_message = 'Advanced SEO settings saved successfully!';
        
        // Regenerate sitemap
        advanced_seo_generate_sitemap();
    }
    
    // Load current settings
    $settings = advanced_seo_get_settings();
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <!-- Premium Notice -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">🚀 Advanced SEO Premium</h2>
                    <p class="text-purple-100">Enhanced SEO features including Meta Editor, Open Graph Preview, and 404 Monitoring</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">$29.99</div>
                    <div class="text-purple-100 text-sm">One-time purchase</div>
                </div>
            </div>
        </div>
        
        <!-- License Status -->
        <?php
        $license_status = advanced_seo_get_license_status();
        $is_licensed = advanced_seo_is_licensed();
        ?>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">License Status</h3>
            <?php if ($is_licensed): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800">License Active</h4>
                            <p class="text-sm text-green-700 mt-1">All premium features are available</p>
                            <?php if (!empty($license_status['expires'])): ?>
                                <p class="text-sm text-green-600 mt-1">Expires: <?= htmlspecialchars($license_status['expires']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-yellow-800">License Required</h4>
                            <p class="text-sm text-yellow-700 mt-1"><?= htmlspecialchars($license_status['message']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="?action=advanced_seo_license" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Activate License
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Feature Status -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Feature Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Always Available Features -->
                <div>
                    <h4 class="font-medium text-green-700 mb-2">✓ Always Available</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Basic SEO settings</li>
                        <li>• XML sitemap generation</li>
                        <li>• Robots.txt management</li>
                        <li>• Analytics integration</li>
                        <li>• Basic structured data</li>
                        <li>• Social media settings</li>
                    </ul>
                </div>
                
                <!-- Premium Features -->
                <div>
                    <h4 class="font-medium <?= $is_licensed ? 'text-green-700' : 'text-red-700' ?> mb-2">
                        <?= $is_licensed ? '✓' : '✗' ?> Premium Features
                    </h4>
                    <ul class="text-sm <?= $is_licensed ? 'text-gray-600' : 'text-gray-500' ?> space-y-1">
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• Page-specific meta tags</li>
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• Meta Editor interface</li>
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• Open Graph Preview tool</li>
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• 404 error monitoring</li>
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• 404 redirect management</li>
                        <li class="<?= $is_licensed ? '' : 'line-through' ?>">• Advanced schema markup</li>
                    </ul>
                    <?php if (!$is_licensed): ?>
                        <p class="text-xs text-red-600 mt-2">Activate license to unlock premium features</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Feature Comparison -->
        <?php if (!$is_licensed): ?>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Free vs Premium Features</h3>
                <?php $features = advanced_seo_get_feature_comparison(); ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Free</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Premium</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $all_features = array_unique(array_merge(array_keys($features['free']), array_keys($features['premium'])));
                            foreach ($all_features as $feature):
                                $free_available = $features['free'][$feature] ?? false;
                                $premium_available = $features['premium'][$feature] ?? false;
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($feature) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($free_available): ?>
                                        <svg class="h-5 w-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($premium_available): ?>
                                        <svg class="h-5 w-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="?action=advanced_seo_license" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium">
                        Upgrade to Premium - $29.99
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="save_advanced_seo_settings">
            
            <!-- Basic SEO Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Basic SEO Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Site Title</label>
                        <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Site Description</label>
                        <textarea name="site_description" rows="3" 
                                  class="w-full border rounded px-3 py-2"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Title Separator</label>
                            <input type="text" name="title_separator" value="<?= htmlspecialchars($settings['title_separator']) ?>" 
                                   class="w-full border rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Append Site Title</label>
                            <div class="mt-2">
                                <input type="checkbox" name="append_site_title" id="append_site_title" 
                                       <?= $settings['append_site_title'] ? 'checked' : '' ?>>
                                <label for="append_site_title">Add site title after page title</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Social Media Settings</h3>
                <?php if (!$is_licensed): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <p class="text-sm text-blue-700">
                            <strong>Premium Feature:</strong> Page-specific social media tags are available with a license.
                        </p>
                    </div>
                <?php endif; ?>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Default Social Image URL</label>
                        <input type="text" name="social_image" value="<?= htmlspecialchars($settings['social_image']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Twitter Handle</label>
                        <input type="text" name="twitter_handle" value="<?= htmlspecialchars($settings['twitter_handle']) ?>" 
                               class="w-full border rounded px-3 py-2" placeholder="@username">
                    </div>
                </div>
            </div>
            
            <!-- Analytics Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Analytics Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Google Analytics ID</label>
                        <input type="text" name="google_analytics_id" value="<?= htmlspecialchars($settings['google_analytics_id']) ?>" 
                               class="w-full border rounded px-3 py-2" placeholder="UA-XXXXXXXXX-X or G-XXXXXXXXXX">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Google Tag Manager ID</label>
                        <input type="text" name="google_tag_manager_id" value="<?= htmlspecialchars($settings['google_tag_manager_id']) ?>" 
                               class="w-full border rounded px-3 py-2" placeholder="GTM-XXXXXX">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Facebook Pixel ID</label>
                        <input type="text" name="facebook_pixel_id" value="<?= htmlspecialchars($settings['facebook_pixel_id']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                </div>
            </div>
            
            <!-- Schema.org Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Schema.org Settings</h3>
                <?php if (!$is_licensed): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <p class="text-sm text-blue-700">
                            <strong>Premium Feature:</strong> Page-specific schema markup is available with a license.
                        </p>
                    </div>
                <?php endif; ?>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Schema Type</label>
                        <select name="schema_type" class="w-full border rounded px-3 py-2">
                            <option value="WebSite" <?= $settings['schema_type'] === 'WebSite' ? 'selected' : '' ?>>Website</option>
                            <option value="Organization" <?= $settings['schema_type'] === 'Organization' ? 'selected' : '' ?>>Organization</option>
                            <option value="Person" <?= $settings['schema_type'] === 'Person' ? 'selected' : '' ?>>Person</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Schema Name</label>
                        <input type="text" name="schema_name" value="<?= htmlspecialchars($settings['schema_name']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Schema Description</label>
                        <textarea name="schema_description" rows="3" 
                                  class="w-full border rounded px-3 py-2"><?= htmlspecialchars($settings['schema_description']) ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Schema Logo URL</label>
                        <input type="text" name="schema_logo" value="<?= htmlspecialchars($settings['schema_logo']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Social Profile URLs (one per line)</label>
                        <textarea name="schema_social_profiles" rows="3" 
                                  class="w-full border rounded px-3 py-2"><?= htmlspecialchars(implode("\n", $settings['schema_social_profiles'])) ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Sitemap Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Sitemap Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Enable Sitemap</label>
                        <div class="mt-2">
                            <input type="checkbox" name="sitemap_enabled" id="sitemap_enabled" 
                                   <?= $settings['sitemap_enabled'] ? 'checked' : '' ?>>
                            <label for="sitemap_enabled">Generate sitemap.xml</label>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Default Priority</label>
                            <input type="number" name="sitemap_priority" value="<?= htmlspecialchars($settings['sitemap_priority']) ?>" 
                                   class="w-full border rounded px-3 py-2" step="0.1" min="0" max="1">
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Change Frequency</label>
                            <select name="sitemap_changefreq" class="w-full border rounded px-3 py-2">
                                <option value="always" <?= $settings['sitemap_changefreq'] === 'always' ? 'selected' : '' ?>>Always</option>
                                <option value="hourly" <?= $settings['sitemap_changefreq'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                <option value="daily" <?= $settings['sitemap_changefreq'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= $settings['sitemap_changefreq'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= $settings['sitemap_changefreq'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                <option value="yearly" <?= $settings['sitemap_changefreq'] === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                <option value="never" <?= $settings['sitemap_changefreq'] === 'never' ? 'selected' : '' ?>>Never</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Robots.txt Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Robots.txt Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Robots.txt Content</label>
                        <textarea name="robots_txt" rows="5" 
                                  class="w-full border rounded px-3 py-2 font-mono text-sm"><?= htmlspecialchars($settings['robots_txt']) ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Meta Robots Tag</label>
                        <input type="text" name="meta_robots" value="<?= htmlspecialchars($settings['meta_robots']) ?>" 
                               class="w-full border rounded px-3 py-2" placeholder="index,follow">
                    </div>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Advanced Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">Canonical URL</label>
                        <input type="text" name="canonical_url" value="<?= htmlspecialchars($settings['canonical_url']) ?>" 
                               class="w-full border rounded px-3 py-2" placeholder="https://example.com">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Hreflang Tags (one per line)</label>
                        <textarea name="hreflang_tags" rows="3" 
                                  class="w-full border rounded px-3 py-2"><?= htmlspecialchars(implode("\n", $settings['hreflang_tags'])) ?></textarea>
                        <p class="text-sm text-gray-600 mt-1">Format: &lt;link rel="alternate" hreflang="x" href="y" /&gt;</p>
                    </div>
                </div>
            </div>
            
            <div>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Get page-specific meta tags
 */
function advanced_seo_get_page_meta_tags($page_path) {
    if (!file_exists(ADVANCED_SEO_META_FILE)) {
        return [];
    }
    
    $meta_tags = json_decode(file_get_contents(ADVANCED_SEO_META_FILE), true);
    return $meta_tags[$page_path] ?? [];
}

/**
 * Save page-specific meta tags
 */
function advanced_seo_save_page_meta_tags($page_path, $meta_tags) {
    $all_meta_tags = [];
    if (file_exists(ADVANCED_SEO_META_FILE)) {
        $all_meta_tags = json_decode(file_get_contents(ADVANCED_SEO_META_FILE), true);
    }
    
    $all_meta_tags[$page_path] = $meta_tags;
    file_put_contents(ADVANCED_SEO_META_FILE, json_encode($all_meta_tags, JSON_PRETTY_PRINT));
}

/**
 * Log 404 errors
 */
function advanced_seo_log_404_error($url) {
    // Only log 404 errors if plugin is licensed
    if (!advanced_seo_is_licensed()) {
        return;
    }
    
    $errors = [];
    if (file_exists(ADVANCED_SEO_404_LOG_FILE)) {
        $errors = json_decode(file_get_contents(ADVANCED_SEO_404_LOG_FILE), true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $errors[] = [
        'url' => $url,
        'timestamp' => $timestamp,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Keep only last 1000 errors
    if (count($errors) > 1000) {
        $errors = array_slice($errors, -1000);
    }
    
    file_put_contents(ADVANCED_SEO_404_LOG_FILE, json_encode($errors, JSON_PRETTY_PRINT));
}

/**
 * Get 404 error statistics
 */
function advanced_seo_get_404_stats() {
    if (!file_exists(ADVANCED_SEO_404_LOG_FILE)) {
        return [
            'total_errors' => 0,
            'recent_errors' => [],
            'error_counts' => []
        ];
    }
    
    $errors = json_decode(file_get_contents(ADVANCED_SEO_404_LOG_FILE), true);
    $recent_errors = array_slice($errors, -50); // Last 50 errors
    
    // Count errors by URL
    $error_counts = [];
    foreach ($errors as $error) {
        $url = $error['url'];
        $error_counts[$url] = ($error_counts[$url] ?? 0) + 1;
    }
    
    // Sort by count
    arsort($error_counts);
    
    return [
        'total_errors' => count($errors),
        'recent_errors' => $recent_errors,
        'error_counts' => array_slice($error_counts, 0, 20) // Top 20
    ];
}

/**
 * Generate Open Graph preview HTML
 */
function advanced_seo_generate_og_preview($title, $description, $image, $url) {
    $preview_html = '
    <div class="og-preview">
        <div class="og-card">
            <div class="og-image">
                <img src="' . htmlspecialchars($image ?: '/uploads/default-og-image.jpg') . '" alt="' . htmlspecialchars($title) . '">
            </div>
            <div class="og-content">
                <div class="og-url">' . htmlspecialchars(parse_url($url, PHP_URL_HOST) ?: 'example.com') . '</div>
                <div class="og-title">' . htmlspecialchars($title) . '</div>
                <div class="og-description">' . htmlspecialchars($description) . '</div>
            </div>
        </div>
    </div>';
    
    return $preview_html;
}

/**
 * Render the Meta Editor page
 */
function advanced_seo_meta_editor_page() {
    // Check for premium access
    $access_check = advanced_seo_check_premium_access();
    if ($access_check) {
        return $access_check;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_meta_tags') {
        $page_path = trim($_POST['page_path'] ?? '');
        $meta_tags = [
            'title' => trim($_POST['meta_title'] ?? ''),
            'description' => trim($_POST['meta_description'] ?? ''),
            'keywords' => trim($_POST['meta_keywords'] ?? ''),
            'og_title' => trim($_POST['og_title'] ?? ''),
            'og_description' => trim($_POST['og_description'] ?? ''),
            'og_image' => trim($_POST['og_image'] ?? ''),
            'og_type' => trim($_POST['og_type'] ?? 'website'),
            'twitter_card' => trim($_POST['twitter_card'] ?? 'summary_large_image'),
            'twitter_title' => trim($_POST['twitter_title'] ?? ''),
            'twitter_description' => trim($_POST['twitter_description'] ?? ''),
            'twitter_image' => trim($_POST['twitter_image'] ?? ''),
            'canonical_url' => trim($_POST['canonical_url'] ?? ''),
            'robots' => trim($_POST['robots'] ?? 'index,follow'),
            'schema_type' => trim($_POST['schema_type'] ?? 'WebPage'),
            'schema_title' => trim($_POST['schema_title'] ?? ''),
            'schema_description' => trim($_POST['schema_description'] ?? '')
        ];
        
        advanced_seo_save_page_meta_tags($page_path, $meta_tags);
        $success_message = 'Meta tags saved successfully for: ' . htmlspecialchars($page_path);
    }
    
    // Get all pages for dropdown
    $pages = [];
    if (defined('CONTENT_DIR') && is_dir(CONTENT_DIR)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(CONTENT_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'md') {
                $relativePath = str_replace(CONTENT_DIR . '/', '', $file->getPathname());
                $relativePath = str_replace('.md', '', $relativePath);
                
                // Skip preview files
                if (strpos($relativePath, '_preview/') === 0) continue;
                
                $pages[] = $relativePath;
            }
        }
    }
    
    // Get selected page meta tags
    $selected_page = $_GET['page'] ?? '';
    $page_meta_tags = [];
    if ($selected_page) {
        $page_meta_tags = advanced_seo_get_page_meta_tags($selected_page);
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <!-- Page Selection -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Select Page</h3>
            <form method="GET" class="flex gap-4">
                <select name="page" class="flex-1 border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="">Select a page...</option>
                    <?php foreach ($pages as $page): ?>
                        <option value="<?= htmlspecialchars($page) ?>" <?= $selected_page === $page ? 'selected' : '' ?>>
                            <?= htmlspecialchars($page) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Load Page
                </button>
            </form>
        </div>
        
        <?php if ($selected_page): ?>
            <form method="POST" class="space-y-6">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="save_meta_tags">
                <input type="hidden" name="page_path" value="<?= htmlspecialchars($selected_page) ?>">
                
                <!-- Basic Meta Tags -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">Basic Meta Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-1">Meta Title</label>
                            <input type="text" name="meta_title" value="<?= htmlspecialchars($page_meta_tags['title'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2" maxlength="60">
                            <p class="text-sm text-gray-600 mt-1">Recommended: 50-60 characters</p>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Meta Description</label>
                            <textarea name="meta_description" rows="3" 
                                      class="w-full border rounded px-3 py-2" maxlength="160"><?= htmlspecialchars($page_meta_tags['description'] ?? '') ?></textarea>
                            <p class="text-sm text-gray-600 mt-1">Recommended: 150-160 characters</p>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="<?= htmlspecialchars($page_meta_tags['keywords'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2">
                            <p class="text-sm text-gray-600 mt-1">Comma-separated keywords</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium mb-1">Canonical URL</label>
                                <input type="text" name="canonical_url" value="<?= htmlspecialchars($page_meta_tags['canonical_url'] ?? '') ?>" 
                                       class="w-full border rounded px-3 py-2">
                            </div>
                            
                            <div>
                                <label class="block font-medium mb-1">Robots</label>
                                <select name="robots" class="w-full border rounded px-3 py-2">
                                    <option value="index,follow" <?= ($page_meta_tags['robots'] ?? '') === 'index,follow' ? 'selected' : '' ?>>Index, Follow</option>
                                    <option value="noindex,follow" <?= ($page_meta_tags['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>No Index, Follow</option>
                                    <option value="index,nofollow" <?= ($page_meta_tags['robots'] ?? '') === 'index,nofollow' ? 'selected' : '' ?>>Index, No Follow</option>
                                    <option value="noindex,nofollow" <?= ($page_meta_tags['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>No Index, No Follow</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Open Graph Tags -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">Open Graph Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-1">OG Title</label>
                            <input type="text" name="og_title" value="<?= htmlspecialchars($page_meta_tags['og_title'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2" maxlength="60">
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">OG Description</label>
                            <textarea name="og_description" rows="3" 
                                      class="w-full border rounded px-3 py-2" maxlength="200"><?= htmlspecialchars($page_meta_tags['og_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium mb-1">OG Image URL</label>
                                <input type="text" name="og_image" value="<?= htmlspecialchars($page_meta_tags['og_image'] ?? '') ?>" 
                                       class="w-full border rounded px-3 py-2">
                            </div>
                            
                            <div>
                                <label class="block font-medium mb-1">OG Type</label>
                                <select name="og_type" class="w-full border rounded px-3 py-2">
                                    <option value="website" <?= ($page_meta_tags['og_type'] ?? '') === 'website' ? 'selected' : '' ?>>Website</option>
                                    <option value="article" <?= ($page_meta_tags['og_type'] ?? '') === 'article' ? 'selected' : '' ?>>Article</option>
                                    <option value="product" <?= ($page_meta_tags['og_type'] ?? '') === 'product' ? 'selected' : '' ?>>Product</option>
                                    <option value="profile" <?= ($page_meta_tags['og_type'] ?? '') === 'profile' ? 'selected' : '' ?>>Profile</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Twitter Card Tags -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">Twitter Card Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-1">Twitter Card Type</label>
                            <select name="twitter_card" class="w-full border rounded px-3 py-2">
                                <option value="summary" <?= ($page_meta_tags['twitter_card'] ?? '') === 'summary' ? 'selected' : '' ?>>Summary</option>
                                <option value="summary_large_image" <?= ($page_meta_tags['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : '' ?>>Summary Large Image</option>
                                <option value="app" <?= ($page_meta_tags['twitter_card'] ?? '') === 'app' ? 'selected' : '' ?>>App</option>
                                <option value="player" <?= ($page_meta_tags['twitter_card'] ?? '') === 'player' ? 'selected' : '' ?>>Player</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Twitter Title</label>
                            <input type="text" name="twitter_title" value="<?= htmlspecialchars($page_meta_tags['twitter_title'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2" maxlength="60">
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Twitter Description</label>
                            <textarea name="twitter_description" rows="3" 
                                      class="w-full border rounded px-3 py-2" maxlength="200"><?= htmlspecialchars($page_meta_tags['twitter_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Twitter Image URL</label>
                            <input type="text" name="twitter_image" value="<?= htmlspecialchars($page_meta_tags['twitter_image'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                </div>
                
                <!-- Schema.org Tags -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">Schema.org Tags</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium mb-1">Schema Type</label>
                            <select name="schema_type" class="w-full border rounded px-3 py-2">
                                <option value="WebPage" <?= ($page_meta_tags['schema_type'] ?? '') === 'WebPage' ? 'selected' : '' ?>>WebPage</option>
                                <option value="Article" <?= ($page_meta_tags['schema_type'] ?? '') === 'Article' ? 'selected' : '' ?>>Article</option>
                                <option value="Product" <?= ($page_meta_tags['schema_type'] ?? '') === 'Product' ? 'selected' : '' ?>>Product</option>
                                <option value="Person" <?= ($page_meta_tags['schema_type'] ?? '') === 'Person' ? 'selected' : '' ?>>Person</option>
                                <option value="Organization" <?= ($page_meta_tags['schema_type'] ?? '') === 'Organization' ? 'selected' : '' ?>>Organization</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Schema Title</label>
                            <input type="text" name="schema_title" value="<?= htmlspecialchars($page_meta_tags['schema_title'] ?? '') ?>" 
                                   class="w-full border rounded px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block font-medium mb-1">Schema Description</label>
                            <textarea name="schema_description" rows="3" 
                                      class="w-full border rounded px-3 py-2"><?= htmlspecialchars($page_meta_tags['schema_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        Save Meta Tags
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Render the Open Graph Preview page
 */
function advanced_seo_og_preview_page() {
    // Check for premium access
    $access_check = advanced_seo_check_premium_access();
    if ($access_check) {
        return $access_check;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview_og') {
        $preview_data = [
            'title' => trim($_POST['og_title'] ?? ''),
            'description' => trim($_POST['og_description'] ?? ''),
            'image' => trim($_POST['og_image'] ?? ''),
            'url' => trim($_POST['og_url'] ?? ''),
            'type' => trim($_POST['og_type'] ?? 'website')
        ];
    } else {
        $preview_data = [
            'title' => 'Sample Page Title',
            'description' => 'This is a sample description that shows how your content will appear when shared on social media platforms like Facebook, Twitter, and LinkedIn.',
            'image' => '/uploads/default-og-image.jpg',
            'url' => 'https://example.com/sample-page',
            'type' => 'website'
        ];
    }
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <!-- Preview Form -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Open Graph Preview Generator</h3>
            <form method="POST" class="space-y-4">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="preview_og">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">OG Title</label>
                        <input type="text" name="og_title" value="<?= htmlspecialchars($preview_data['title']) ?>" 
                               class="w-full border rounded px-3 py-2" maxlength="60">
                        <p class="text-sm text-gray-600 mt-1">Recommended: 50-60 characters</p>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">OG Type</label>
                        <select name="og_type" class="w-full border rounded px-3 py-2">
                            <option value="website" <?= $preview_data['type'] === 'website' ? 'selected' : '' ?>>Website</option>
                            <option value="article" <?= $preview_data['type'] === 'article' ? 'selected' : '' ?>>Article</option>
                            <option value="product" <?= $preview_data['type'] === 'product' ? 'selected' : '' ?>>Product</option>
                            <option value="profile" <?= $preview_data['type'] === 'profile' ? 'selected' : '' ?>>Profile</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block font-medium mb-1">OG Description</label>
                    <textarea name="og_description" rows="3" 
                              class="w-full border rounded px-3 py-2" maxlength="200"><?= htmlspecialchars($preview_data['description']) ?></textarea>
                    <p class="text-sm text-gray-600 mt-1">Recommended: 150-200 characters</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">OG Image URL</label>
                        <input type="text" name="og_image" value="<?= htmlspecialchars($preview_data['image']) ?>" 
                               class="w-full border rounded px-3 py-2">
                        <p class="text-sm text-gray-600 mt-1">Recommended: 1200x630 pixels</p>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">Page URL</label>
                        <input type="text" name="og_url" value="<?= htmlspecialchars($preview_data['url']) ?>" 
                               class="w-full border rounded px-3 py-2">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Generate Preview
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Preview Display -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Social Media Preview</h3>
            
            <!-- Facebook Preview -->
            <div class="mb-8">
                <h4 class="font-medium mb-3 text-blue-600">Facebook Preview</h4>
                <?= advanced_seo_generate_og_preview($preview_data['title'], $preview_data['description'], $preview_data['image'], $preview_data['url']) ?>
            </div>
            
            <!-- Twitter Preview -->
            <div class="mb-8">
                <h4 class="font-medium mb-3 text-blue-400">Twitter Preview</h4>
                <div class="og-preview">
                    <div class="og-card twitter-style">
                        <div class="og-image">
                            <img src="<?= htmlspecialchars($preview_data['image'] ?: '/uploads/default-og-image.jpg') ?>" 
                                 alt="<?= htmlspecialchars($preview_data['title']) ?>">
                        </div>
                        <div class="og-content">
                            <div class="og-url"><?= htmlspecialchars(parse_url($preview_data['url'], PHP_URL_HOST) ?: 'example.com') ?></div>
                            <div class="og-title"><?= htmlspecialchars($preview_data['title']) ?></div>
                            <div class="og-description"><?= htmlspecialchars($preview_data['description']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- LinkedIn Preview -->
            <div>
                <h4 class="font-medium mb-3 text-blue-700">LinkedIn Preview</h4>
                <div class="og-preview">
                    <div class="og-card linkedin-style">
                        <div class="og-image">
                            <img src="<?= htmlspecialchars($preview_data['image'] ?: '/uploads/default-og-image.jpg') ?>" 
                                 alt="<?= htmlspecialchars($preview_data['title']) ?>">
                        </div>
                        <div class="og-content">
                            <div class="og-url"><?= htmlspecialchars(parse_url($preview_data['url'], PHP_URL_HOST) ?: 'example.com') ?></div>
                            <div class="og-title"><?= htmlspecialchars($preview_data['title']) ?></div>
                            <div class="og-description"><?= htmlspecialchars($preview_data['description']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Generated Meta Tags -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Generated Meta Tags</h3>
            <div class="bg-gray-100 p-4 rounded font-mono text-sm">
                <pre><?= htmlspecialchars('<!-- Open Graph Meta Tags -->
<meta property="og:title" content="' . $preview_data['title'] . '" />
<meta property="og:description" content="' . $preview_data['description'] . '" />
<meta property="og:image" content="' . $preview_data['image'] . '" />
<meta property="og:url" content="' . $preview_data['url'] . '" />
<meta property="og:type" content="' . $preview_data['type'] . '" />

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="' . $preview_data['title'] . '" />
<meta name="twitter:description" content="' . $preview_data['description'] . '" />
<meta name="twitter:image" content="' . $preview_data['image'] . '" />') ?></pre>
            </div>
        </div>
    </div>
    
    <style>
    .og-preview {
        max-width: 500px;
        margin: 0 auto;
    }
    
    .og-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .og-image img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .og-content {
        padding: 12px;
    }
    
    .og-url {
        color: #666;
        font-size: 12px;
        margin-bottom: 4px;
    }
    
    .og-title {
        font-weight: bold;
        font-size: 16px;
        margin-bottom: 4px;
        color: #1a1a1a;
    }
    
    .og-description {
        font-size: 14px;
        color: #666;
        line-height: 1.4;
    }
    
    .twitter-style {
        border-radius: 12px;
    }
    
    .linkedin-style {
        border-radius: 4px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}

/**
 * Render the 404 Monitor page
 */
function advanced_seo_404_monitor_page() {
    // Check for premium access
    $access_check = advanced_seo_check_premium_access();
    if ($access_check) {
        return $access_check;
    }
    
    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'clear_404_logs') {
            file_put_contents(ADVANCED_SEO_404_LOG_FILE, json_encode([]));
            $success_message = '404 error logs cleared successfully!';
        } elseif ($_POST['action'] === 'redirect_404') {
            $old_url = trim($_POST['old_url'] ?? '');
            $new_url = trim($_POST['new_url'] ?? '');
            
            if ($old_url && $new_url) {
                // Save redirect rule
                $redirects = [];
                if (file_exists(ADVANCED_SEO_CONFIG_FILE)) {
                    $settings = json_decode(file_get_contents(ADVANCED_SEO_CONFIG_FILE), true);
                    $redirects = $settings['404_redirects'] ?? [];
                }
                
                $redirects[$old_url] = $new_url;
                
                // Update settings
                $settings = advanced_seo_get_settings();
                $settings['404_redirects'] = $redirects;
                file_put_contents(ADVANCED_SEO_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
                
                $success_message = 'Redirect rule added successfully!';
            }
        }
    }
    
    // Get 404 statistics
    $stats = advanced_seo_get_404_stats();
    $settings = advanced_seo_get_settings();
    $redirects = $settings['404_redirects'] ?? [];
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-2">Total 404 Errors</h3>
                <p class="text-3xl font-bold text-red-600"><?= number_format($stats['total_errors']) ?></p>
                <p class="text-sm text-gray-600 mt-1">All time</p>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-2">Recent Errors</h3>
                <p class="text-3xl font-bold text-orange-600"><?= count($stats['recent_errors']) ?></p>
                <p class="text-sm text-gray-600 mt-1">Last 50 errors</p>
            </div>
            
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-2">Active Redirects</h3>
                <p class="text-3xl font-bold text-green-600"><?= count($redirects) ?></p>
                <p class="text-sm text-gray-600 mt-1">Configured</p>
            </div>
        </div>
        
        <!-- Add Redirect Rule -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Add Redirect Rule</h3>
            <form method="POST" class="space-y-4">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="redirect_404">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">Old URL (404 URL)</label>
                        <input type="text" name="old_url" class="w-full border rounded px-3 py-2" 
                               placeholder="/old-page-url">
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-1">New URL (Redirect to)</label>
                        <input type="text" name="new_url" class="w-full border rounded px-3 py-2" 
                               placeholder="/new-page-url">
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Add Redirect Rule
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Most Common 404 Errors -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Most Common 404 Errors</h3>
            <?php if (empty($stats['error_counts'])): ?>
                <p class="text-gray-600">No 404 errors recorded yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Seen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($stats['error_counts'] as $url => $count): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($url) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                                            <?= $count ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php
                                        $last_error = null;
                                        foreach ($stats['recent_errors'] as $error) {
                                            if ($error['url'] === $url) {
                                                $last_error = $error;
                                                break;
                                            }
                                        }
                                        echo $last_error ? htmlspecialchars($last_error['timestamp']) : 'Unknown';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="setRedirectUrl('<?= htmlspecialchars($url) ?>')" 
                                                class="text-blue-600 hover:text-blue-900">Add Redirect</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent 404 Errors -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Recent 404 Errors</h3>
            <?php if (empty($stats['recent_errors'])): ?>
                <p class="text-gray-600">No recent 404 errors.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Agent</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach (array_reverse($stats['recent_errors']) as $error): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($error['url']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($error['timestamp']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($error['ip']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="max-w-xs truncate" title="<?= htmlspecialchars($error['user_agent']) ?>">
                                            <?= htmlspecialchars($error['user_agent']) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Active Redirects -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Active Redirect Rules</h3>
            <?php if (empty($redirects)): ?>
                <p class="text-gray-600">No redirect rules configured.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($redirects as $from => $to): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($from) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="text-green-600">→</span> <?= htmlspecialchars($to) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="removeRedirect('<?= htmlspecialchars($from) ?>')" 
                                                class="text-red-600 hover:text-red-900">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Clear Logs -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Maintenance</h3>
            <form method="POST" class="space-y-4">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="clear_404_logs">
                <p class="text-gray-600">Clear all 404 error logs. This action cannot be undone.</p>
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded" 
                        onclick="return confirm('Are you sure you want to clear all 404 logs?')">
                    Clear All 404 Logs
                </button>
            </form>
        </div>
    </div>
    
    <script>
    function setRedirectUrl(url) {
        document.querySelector('input[name="old_url"]').value = url;
        document.querySelector('input[name="old_url"]').focus();
    }
    
    function removeRedirect(fromUrl) {
        if (confirm('Are you sure you want to remove this redirect rule?')) {
            // This would need to be implemented with AJAX or a separate form
            alert('Redirect removal functionality needs to be implemented');
        }
    }
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * Check for and handle 404 redirects
 */
function advanced_seo_check_redirects() {
    // Only check redirects if plugin is licensed
    if (!advanced_seo_is_licensed()) {
        return;
    }
    
    $settings = advanced_seo_get_settings();
    $redirects = $settings['404_redirects'] ?? [];
    
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    
    // Check if current URL matches any redirect rules
    if (isset($redirects[$current_url])) {
        $redirect_url = $redirects[$current_url];
        
        // Perform 301 redirect
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $redirect_url);
        exit;
    }
}

/**
 * Get license information
 */
function advanced_seo_get_license() {
    if (!file_exists(ADVANCED_SEO_LICENSE_FILE)) {
        return [
            'status' => 'not_licensed',
            'key' => '',
            'domain' => '',
            'expires' => '',
            'last_check' => '',
            'message' => 'No license found'
        ];
    }
    
    $license = json_decode(file_get_contents(ADVANCED_SEO_LICENSE_FILE), true);
    if (!is_array($license)) {
        return [
            'status' => 'invalid',
            'key' => '',
            'domain' => '',
            'expires' => '',
            'last_check' => '',
            'message' => 'Invalid license file'
        ];
    }
    
    return $license;
}

/**
 * Save license information
 */
function advanced_seo_save_license($license_data) {
    file_put_contents(ADVANCED_SEO_LICENSE_FILE, json_encode($license_data, JSON_PRETTY_PRINT));
}

/**
 * Verify license key with server
 */
function advanced_seo_verify_license($license_key) {
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $site_url = 'https://' . $domain;
    
    $data = [
        'action' => 'verify',
        'license_key' => $license_key,
        'domain' => $domain,
        'product_id' => ADVANCED_SEO_PRODUCT_ID
    ];
    
    $response = advanced_seo_make_api_request('/license-server-simulator.php', $data);
    
    if (!$response) {
        return [
            'success' => false,
            'status' => 'error',
            'message' => 'Failed to connect to license server'
        ];
    }
    
    if (!$response['success']) {
        return [
            'success' => false,
            'status' => $response['status'] ?? 'error',
            'message' => $response['message'] ?? 'License verification failed'
        ];
    }
    
    // License is valid, save the license data
    $license_data = [
        'license_key' => $license_key,
        'status' => 'active',
        'verified_at' => date('Y-m-d H:i:s'),
        'domain' => $domain,
        'expires_at' => $response['license']['expires_at'] ?? null,
        'email' => $response['license']['email'] ?? null,
        'activations' => $response['license']['activations'] ?? 0,
        'max_activations' => $response['license']['max_activations'] ?? 1
    ];
    
    advanced_seo_save_license($license_data);
    
    return [
        'success' => true,
        'status' => 'valid',
        'message' => 'License activated successfully',
        'license' => $license_data
    ];
}

/**
 * Make API request to license server
 */
function advanced_seo_make_api_request($endpoint, $data) {
    $url = ADVANCED_SEO_LICENSE_SERVER . $endpoint;
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result === false) {
        return false;
    }
    
    return json_decode($result, true);
}

/**
 * Check if plugin is licensed
 */
function advanced_seo_is_licensed() {
    $license = advanced_seo_get_license();
    
    // If not licensed, return false
    if ($license['status'] !== 'valid') {
        return false;
    }
    
    // Check if license has expired
    if (!empty($license['expires'])) {
        $expires = strtotime($license['expires']);
        if ($expires && $expires < time()) {
            return false;
        }
    }
    
    // Check if we need to re-verify (every 7 days)
    $last_check = strtotime($license['last_check']);
    if ($last_check && (time() - $last_check) > (7 * 24 * 60 * 60)) {
        // Re-verify license
        $new_license = advanced_seo_verify_license($license['key']);
        return $new_license['status'] === 'valid';
    }
    
    return true;
}

/**
 * Get license status message
 */
function advanced_seo_get_license_status() {
    $license = advanced_seo_get_license();
    
    switch ($license['status']) {
        case 'valid':
            return [
                'status' => 'valid',
                'message' => 'License is valid and active',
                'expires' => $license['expires'],
                'domain' => $license['domain']
            ];
        case 'not_licensed':
            return [
                'status' => 'not_licensed',
                'message' => 'Plugin is not licensed. Please purchase a license to use premium features.',
                'expires' => '',
                'domain' => ''
            ];
        case 'invalid':
            return [
                'status' => 'invalid',
                'message' => $license['message'] ?: 'License key is invalid',
                'expires' => '',
                'domain' => $license['domain']
            ];
        case 'error':
            return [
                'status' => 'error',
                'message' => $license['message'] ?: 'Unable to verify license',
                'expires' => '',
                'domain' => $license['domain']
            ];
        default:
            return [
                'status' => 'unknown',
                'message' => 'Unknown license status',
                'expires' => '',
                'domain' => ''
            ];
    }
}

/**
 * Render the License page
 */
function advanced_seo_license_page() {
    // Handle license activation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'activate_license') {
            $license_key = trim($_POST['license_key'] ?? '');
            if (!empty($license_key)) {
                $result = advanced_seo_verify_license($license_key);
                if ($result['status'] === 'valid') {
                    $success_message = 'License activated successfully!';
                } else {
                    $error_message = $result['message'];
                }
            } else {
                $error_message = 'Please enter a license key.';
            }
        } elseif ($_POST['action'] === 'deactivate_license') {
            // Clear license data
            if (file_exists(ADVANCED_SEO_LICENSE_FILE)) {
                unlink(ADVANCED_SEO_LICENSE_FILE);
            }
            $success_message = 'License deactivated successfully.';
        }
    }
    
    $license_status = advanced_seo_get_license_status();
    $is_licensed = advanced_seo_is_licensed();
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <!-- License Status -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">License Status</h3>
            
            <?php if ($is_licensed): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800">License Active</h4>
                            <p class="text-sm text-green-700 mt-1"><?= htmlspecialchars($license_status['message']) ?></p>
                            <?php if (!empty($license_status['expires'])): ?>
                                <p class="text-sm text-green-600 mt-1">Expires: <?= htmlspecialchars($license_status['expires']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($license_status['domain'])): ?>
                                <p class="text-sm text-green-600">Domain: <?= htmlspecialchars($license_status['domain']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="mt-4">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    <input type="hidden" name="action" value="deactivate_license">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded" 
                            onclick="return confirm('Are you sure you want to deactivate the license?')">
                        Deactivate License
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-yellow-800">License Required</h4>
                            <p class="text-sm text-yellow-700 mt-1"><?= htmlspecialchars($license_status['message']) ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="?action=advanced_seo_license" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Activate License
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- License Activation -->
        <?php if (!$is_licensed): ?>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Activate License</h3>
                <form method="POST" class="space-y-4">
                    <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                    <input type="hidden" name="action" value="activate_license">
                    
                    <div>
                        <label class="block font-medium mb-1">License Key</label>
                        <input type="text" name="license_key" class="w-full border rounded px-3 py-2" 
                               placeholder="Enter your license key" required>
                        <p class="text-sm text-gray-600 mt-1">Enter the license key you received after purchase.</p>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Activate License
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Purchase Information -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold mb-2">Purchase Advanced SEO Premium</h3>
                    <p class="text-purple-100">Get access to Meta Editor, Open Graph Preview, and 404 Monitoring</p>
                    <ul class="text-purple-100 mt-2 space-y-1">
                        <li>• Page-specific meta tag management</li>
                        <li>• Live social media previews</li>
                        <li>• 404 error monitoring and redirects</li>
                        <li>• Advanced SEO analytics</li>
                    </ul>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">$29.99</div>
                    <div class="text-purple-100 text-sm">One-time purchase</div>
                    <a href="https://fearlessgeek.com/plugins/advanced-seo-premium" 
                       target="_blank" 
                       class="inline-block bg-white text-purple-600 px-4 py-2 rounded mt-2 font-medium hover:bg-gray-100">
                        Purchase Now
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Support Information -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Support</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium mb-2">Need Help?</h4>
                    <p class="text-gray-600 text-sm mb-2">If you need assistance with your license or the plugin:</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Email: support@fearlessgeek.com</li>
                        <li>• Website: fearlessgeek.com</li>
                        <li>• Documentation: docs.fearlessgeek.com</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">License Information</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• One license per domain</li>
                        <li>• Lifetime updates</li>
                        <li>• 30-day money-back guarantee</li>
                        <li>• Premium support included</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Check if user has access to premium features
 */
function advanced_seo_check_premium_access() {
    if (!advanced_seo_is_licensed()) {
        $license_status = advanced_seo_get_license_status();
        ob_start();
        ?>
        <div class="space-y-8">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">Premium Feature</h4>
                        <p class="text-sm text-yellow-700 mt-1"><?= htmlspecialchars($license_status['message']) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold mb-2">Upgrade to Premium</h3>
                        <p class="text-purple-100">Get access to all premium features including Meta Editor, Open Graph Preview, and 404 Monitoring</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold">$29.99</div>
                        <div class="text-purple-100 text-sm">One-time purchase</div>
                        <a href="?action=advanced_seo_license" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Activate License
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    return false;
}

/**
 * Get feature comparison between free and premium versions
 */
function advanced_seo_get_feature_comparison() {
    return [
        'free' => [
            'Basic SEO Settings' => true,
            'XML Sitemap Generation' => true,
            'Robots.txt Management' => true,
            'Analytics Integration' => true,
            'Basic Structured Data' => true,
            'Social Media Settings' => true,
            'Meta Tags (Global)' => true,
            'Open Graph Tags (Global)' => true,
            'Twitter Card Tags (Global)' => true
        ],
        'premium' => [
            // All free features included
            'Basic SEO Settings' => true,
            'XML Sitemap Generation' => true,
            'Robots.txt Management' => true,
            'Analytics Integration' => true,
            'Basic Structured Data' => true,
            'Social Media Settings' => true,
            'Meta Tags (Global)' => true,
            'Open Graph Tags (Global)' => true,
            'Twitter Card Tags (Global)' => true,
            // Additional premium features
            'Page-specific Meta Tags' => true,
            'Meta Editor Interface' => true,
            'Open Graph Preview Tool' => true,
            '404 Error Monitoring' => true,
            '404 Redirect Management' => true,
            'Advanced Schema Markup' => true,
            'Page-specific Social Tags' => true,
            'Page-specific Schema Data' => true,
            'Premium Support' => true
        ]
    ];
}

/**
 * Display feature comparison chart
 */
function advanced_seo_display_comparison_chart() {
    $comparison = advanced_seo_get_feature_comparison();
    $all_features = array_unique(array_merge(array_keys($comparison['free']), array_keys($comparison['premium'])));
    
    ob_start();
    ?>
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium mb-6">Free vs Premium Features</h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Free</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Premium</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($all_features as $feature): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($feature) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if (isset($comparison['free'][$feature]) && $comparison['free'][$feature]): ?>
                                    <svg class="h-5 w-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if (isset($comparison['premium'][$feature]) && $comparison['premium'][$feature]): ?>
                                    <svg class="h-5 w-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>Note:</strong> Premium includes all free features plus additional advanced features for enhanced SEO management.
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
} 