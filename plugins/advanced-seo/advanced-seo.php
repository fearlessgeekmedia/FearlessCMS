<?php
/*
Plugin Name: Advanced SEO
Description: Enhanced SEO features for FearlessCMS including XML sitemap, structured data, and advanced meta tags
Version: 1.0
Author: Fearless Geek
*/

// Define constants
define('ADVANCED_SEO_CONFIG_FILE', ADMIN_CONFIG_DIR . '/advanced_seo_settings.json');
define('SITEMAP_FILE', PROJECT_ROOT . '/sitemap.xml');
define('ROBOTS_FILE', PROJECT_ROOT . '/robots.txt');

// Register admin section
fcms_register_admin_section('advanced_seo', [
    'label' => 'Advanced SEO',
    'menu_order' => 2,
    'parent' => 'plugins',
    'render_callback' => 'advanced_seo_admin_page'
]);

// Register hooks
fcms_add_hook('before_render', 'advanced_seo_inject_meta_tags');
fcms_add_hook('init', 'advanced_seo_generate_sitemap');

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
        'hreflang_tags' => []
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
        'description' => $settings['schema_description'] ?: $pageData['meta_description'],
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
    
    // Basic meta tags
    $page_title = $metadata['title'] ?? $title ?? '';
    $full_title = $page_title;
    if ($settings['append_site_title'] && !empty($page_title) && !empty($settings['site_title'])) {
        $full_title .= ' ' . $settings['title_separator'] . ' ' . $settings['site_title'];
    }
    
    // Enhanced meta tags
    $templateData['meta_tags'] = [
        'title' => $full_title,
        'description' => $metadata['description'] ?? $settings['site_description'] ?? '',
        'robots' => $settings['meta_robots'],
        'canonical' => $settings['canonical_url'] ?: 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'social_image' => $metadata['social_image'] ?? $settings['social_image'] ?? '',
        'twitter_card' => 'summary_large_image',
        'twitter_site' => $settings['twitter_handle'] ? '@' . $settings['twitter_handle'] : '',
        'og_title' => $full_title,
        'og_description' => $metadata['description'] ?? $settings['site_description'] ?? '',
        'og_image' => $metadata['social_image'] ?? $settings['social_image'] ?? '',
        'og_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        'og_type' => 'website'
    ];
    
    // Add structured data
    $templateData['structured_data'] = advanced_seo_generate_structured_data($templateData['meta_tags']);
    
    // Add tracking codes
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
            'hreflang_tags' => array_filter(array_map('trim', explode("\n", $_POST['hreflang_tags'] ?? '')))
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
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
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