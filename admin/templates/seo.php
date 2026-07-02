<?php
// Define SEO config file path if not already defined
if (!defined('SEO_CONFIG_FILE')) {
    define('SEO_CONFIG_FILE', ADMIN_CONFIG_DIR . '/seo_settings.json');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_seo_settings') {
    $settings = [
        'site_title' => trim($_POST['site_title'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'title_separator' => trim($_POST['title_separator'] ?? '-'),
        'append_site_title' => isset($_POST['append_site_title']),
        'social_image' => trim($_POST['social_image'] ?? ''),
        'canonical_base' => trim($_POST['canonical_base'] ?? ''),
        'default_language' => trim($_POST['default_language'] ?? 'en'),
        'additional_meta' => trim($_POST['additional_meta'] ?? ''),
        'focus_keyword' => trim($_POST['focus_keyword'] ?? ''),
        'seo_title' => trim($_POST['seo_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'social_title' => trim($_POST['social_title'] ?? ''),
        'social_description' => trim($_POST['social_description'] ?? '')
    ];
    
    file_put_contents(SEO_CONFIG_FILE, json_encode($settings, JSON_PRETTY_PRINT));
    $success_message = 'SEO settings saved successfully!';
}

// Load current settings
$settings = seo_get_settings();
?>

<div class="space-y-6">
    <!-- SEO Analysis Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">SEO Analysis</h2>
            <p class="text-sm text-gray-600">Analyze your content for SEO optimization</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">95</div>
                    <div class="text-sm text-gray-600">SEO Score</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 95%"></div>
                    </div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">88</div>
                    <div class="text-sm text-gray-600">Readability</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 88%"></div>
                    </div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">92</div>
                    <div class="text-sm text-gray-600">Social Media</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-purple-600 h-2 rounded-full" style="width: 92%"></div>
                    </div>
                </div>
            </div>
            
            <!-- SEO Checklist -->
            <div class="space-y-3">
                <h3 class="font-medium text-gray-900">SEO Checklist</h3>
                <div class="space-y-2">
                    <div class="flex items-center text-sm">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Title is optimized (55 characters)</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Meta description is set (155 characters)</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Focus keyword appears in first paragraph</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">Internal links (add 2-3 internal links)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Optimization Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Content Optimization</h2>
            <p class="text-sm text-gray-600">Optimize your content for better search rankings</p>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <?php if (function_exists('csrf_token_field')) echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="save_seo_settings">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Focus Keyword</label>
                        <input type="text" name="focus_keyword" value="<?= htmlspecialchars($settings['focus_keyword'] ?? '') ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter your main keyword">
                        <p class="text-sm text-gray-500 mt-1">The main keyword you want to rank for</p>
                    </div>
                    
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">SEO Title</label>
                        <input type="text" name="seo_title" value="<?= htmlspecialchars($settings['seo_title'] ?? '') ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="SEO optimized title">
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-gray-500">SEO title preview</span>
                            <span class="text-gray-400">0/60</span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Meta Description</label>
                    <textarea name="meta_description" rows="3" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Write a compelling meta description"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
                    <div class="flex justify-between text-sm mt-1">
                        <span class="text-gray-500">Google will show this in search results</span>
                        <span class="text-gray-400">0/155</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Social Media Title</label>
                        <input type="text" name="social_title" value="<?= htmlspecialchars($settings['social_title'] ?? '') ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Title for social media sharing">
                    </div>
    
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Social Media Description</label>
                        <textarea name="social_description" rows="2" 
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Description for social media"><?= htmlspecialchars($settings['social_description'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Social Media Image</label>
                    <input type="text" name="social_image" value="<?= htmlspecialchars($settings['social_image'] ?? '') ?>" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="https://example.com/image.jpg">
                    <p class="text-sm text-gray-500 mt-1">Image that appears when sharing on social media (1200x630px recommended)</p>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Advanced SEO Settings -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Advanced Settings</h2>
            <p class="text-sm text-gray-600">Configure advanced SEO options</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Site Title</label>
                    <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Default title for your website</p>
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Title Separator</label>
                    <input type="text" name="title_separator" value="<?= htmlspecialchars($settings['title_separator'] ?? '-') ?>" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Character between page title and site title</p>
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Canonical URL Base</label>
                    <input type="text" name="canonical_base" value="<?= htmlspecialchars($settings['canonical_base'] ?? '') ?>" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="https://yoursite.com">
                    <p class="text-sm text-gray-500 mt-1">Base URL for canonical links</p>
                </div>
                
                <div>
                    <label class="block font-medium text-gray-700 mb-2">Default Language</label>
                    <input type="text" name="default_language" value="<?= htmlspecialchars($settings['default_language'] ?? 'en') ?>" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Default language for HTML lang attribute</p>
                </div>
            </div>
            
            <div class="mt-6">
                <label class="block font-medium text-gray-700 mb-2">Additional Meta Tags</label>
                <textarea name="additional_meta" rows="3" 
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="&lt;meta name=&quot;robots&quot; content=&quot;noindex&quot;&gt;"><?= htmlspecialchars($settings['additional_meta'] ?? '') ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Custom meta tags (one per line)</p>
            </div>
            
            <div class="mt-6">
                <label class="flex items-center">
                    <input type="checkbox" name="append_site_title" <?= ($settings['append_site_title'] ?? true) ? 'checked' : '' ?> 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Append site title to page titles</span>
                </label>
            </div>
        </div>
    </div>
</form>
</div> 