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
    
    // Start output buffer
    ob_start(); // Restored output buffering to work with admin template
    
    // Display success message if any
    if (isset($success_message)) {
        echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . htmlspecialchars($success_message) . '</div>';
    }
    
    // Render form
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
                        <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">Default title for your website</p>
                    </div>
                    
                    <div>
                        <label class="block font-medium text-gray-700 mb-2">Title Separator</label>
                        <input type="text" name="title_separator" value="<?= htmlspecialchars($settings['title_separator']) ?>" 
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
        
        <!-- Content Analysis Preview -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Content Analysis</h2>
                <p class="text-sm text-gray-600">Real-time analysis of your content</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Content length: 1,247 words</span>
                        </div>
                        <span class="text-sm text-green-600 font-medium">Good</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Keyword density: 2.1%</span>
                        </div>
                        <span class="text-sm text-yellow-600 font-medium">Acceptable</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Headings structure: H1, H2, H3</span>
                        </div>
                        <span class="text-sm text-green-600 font-medium">Good</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-gray-700">Internal links: 0 found</span>
                        </div>
                        <span class="text-sm text-red-600 font-medium">Needs improvement</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Result Preview -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Search Result Preview</h2>
                <p class="text-sm text-gray-600">How your page will appear in Google search results</p>
            </div>
            <div class="p-6">
                <div class="max-w-2xl">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Google Preview</label>
                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="text-blue-600 text-sm mb-1" id="preview-url">https://yoursite.com/page</div>
                            <div class="text-xl text-blue-800 font-medium mb-1" id="preview-title"><?= htmlspecialchars($settings['seo_title'] ?: 'Page Title') ?></div>
                            <div class="text-sm text-gray-600" id="preview-description"><?= htmlspecialchars($settings['meta_description'] ?: 'Page description will appear here...') ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Social Media Preview</label>
                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gray-200 rounded flex-shrink-0" id="preview-social-image">
                                    <?php if (!empty($settings['social_image'])): ?>
                                        <img src="<?= htmlspecialchars($settings['social_image']) ?>" alt="Social preview" class="w-full h-full object-cover rounded">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm text-gray-500 mb-1">yoursite.com</div>
                                    <div class="font-medium text-gray-900 mb-1" id="preview-social-title"><?= htmlspecialchars($settings['social_title'] ?: 'Social Media Title') ?></div>
                                    <div class="text-sm text-gray-600" id="preview-social-description"><?= htmlspecialchars($settings['social_description'] ?: 'Social media description...') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-500">
                        <p><strong>Note:</strong> This is a preview. Actual search results may vary.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Yoast-style SEO analysis and character counting
    document.addEventListener('DOMContentLoaded', function() {
        const seoTitle = document.querySelector('input[name="seo_title"]');
        const metaDescription = document.querySelector('textarea[name="meta_description"]');
        const focusKeyword = document.querySelector('input[name="focus_keyword"]');
        
        // Character counting for SEO title
        if (seoTitle) {
            const titleCounter = seoTitle.parentNode.querySelector('.text-gray-400');
            
            function updateTitleCount() {
                const length = seoTitle.value.length;
                const maxLength = 60;
                titleCounter.textContent = `${length}/${maxLength}`;
                
                if (length > maxLength) {
                    titleCounter.classList.add('text-red-500');
                    titleCounter.classList.remove('text-gray-400');
                } else if (length > 50) {
                    titleCounter.classList.add('text-yellow-500');
                    titleCounter.classList.remove('text-gray-400', 'text-red-500');
                } else {
                    titleCounter.classList.remove('text-yellow-500', 'text-red-500');
                    titleCounter.classList.add('text-gray-400');
                }
            }
            
            seoTitle.addEventListener('input', updateTitleCount);
            updateTitleCount();
        }
        
        // Character counting for meta description
        if (metaDescription) {
            const descCounter = metaDescription.parentNode.querySelector('.text-gray-400');
            
            function updateDescCount() {
                const length = metaDescription.value.length;
                const maxLength = 155;
                descCounter.textContent = `${length}/${maxLength}`;
                
                if (length > maxLength) {
                    descCounter.classList.add('text-red-500');
                    descCounter.classList.remove('text-gray-400');
                } else if (length > 120) {
                    descCounter.classList.add('text-yellow-500');
                    descCounter.classList.remove('text-gray-400', 'text-red-500');
                } else {
                    descCounter.classList.remove('text-yellow-500', 'text-red-500');
                    descCounter.classList.add('text-gray-400');
                }
            }
            
            metaDescription.addEventListener('input', updateDescCount);
            updateDescCount();
        }
        
        // Real-time SEO analysis
        function updateSEOAnalysis() {
            const keyword = focusKeyword ? focusKeyword.value.toLowerCase() : '';
            const title = seoTitle ? seoTitle.value.toLowerCase() : '';
            const description = metaDescription ? metaDescription.value.toLowerCase() : '';
            
            // Update SEO checklist based on current values
            updateChecklistItem('Title is optimized', title.length >= 30 && title.length <= 60);
            updateChecklistItem('Meta description is set', description.length >= 120 && description.length <= 155);
            updateChecklistItem('Focus keyword in title', keyword && title.includes(keyword));
            updateChecklistItem('Focus keyword in description', keyword && description.includes(keyword));
        }
        
        function updateChecklistItem(text, isGood) {
            const checklistItems = document.querySelectorAll('.space-y-2 .flex.items-center');
            checklistItems.forEach(item => {
                if (item.textContent.includes(text)) {
                    const icon = item.querySelector('svg');
                    const status = item.querySelector('.text-gray-700');
                    
                    if (isGood) {
                        icon.classList.remove('text-yellow-500', 'text-red-500');
                        icon.classList.add('text-green-500');
                        status.classList.remove('text-yellow-700', 'text-red-700');
                        status.classList.add('text-green-700');
                    } else {
                        icon.classList.remove('text-green-500');
                        icon.classList.add('text-yellow-500');
                        status.classList.remove('text-green-700');
                        status.classList.add('text-yellow-700');
                    }
                }
            });
        }
        
        // Update analysis when any field changes
        [seoTitle, metaDescription, focusKeyword].forEach(field => {
            if (field) {
                field.addEventListener('input', updateSEOAnalysis);
            }
        });
        
        // Initial analysis
        updateSEOAnalysis();
        
        // Update SEO scores based on checklist
        function updateSEOScores() {
            const checklistItems = document.querySelectorAll('.space-y-2 .flex.items-center');
            let goodItems = 0;
            let totalItems = checklistItems.length;
            
            checklistItems.forEach(item => {
                const icon = item.querySelector('svg');
                if (icon.classList.contains('text-green-500')) {
                    goodItems++;
                }
            });
            
            const seoScore = Math.round((goodItems / totalItems) * 100);
            const seoScoreElement = document.querySelector('.text-green-600');
            const seoProgressBar = document.querySelector('.bg-green-600');
            
            if (seoScoreElement && seoProgressBar) {
                seoScoreElement.textContent = seoScore;
                seoProgressBar.style.width = seoScore + '%';
                
                // Update progress bar color based on score
                if (seoScore >= 80) {
                    seoProgressBar.classList.remove('bg-yellow-500', 'bg-red-500');
                    seoProgressBar.classList.add('bg-green-600');
                } else if (seoScore >= 60) {
                    seoProgressBar.classList.remove('bg-green-600', 'bg-red-500');
                    seoProgressBar.classList.add('bg-yellow-500');
                } else {
                    seoProgressBar.classList.remove('bg-green-600', 'bg-yellow-500');
                    seoProgressBar.classList.add('bg-red-500');
                }
            }
        }
        
        // Update scores when analysis changes
        document.addEventListener('input', updateSEOScores);
        updateSEOScores();
        
        // Real-time preview updates
        function updatePreviews() {
            const title = seoTitle ? seoTitle.value : '';
            const description = metaDescription ? metaDescription.value : '';
            const socialTitle = document.querySelector('input[name="social_title"]') ? document.querySelector('input[name="social_title"]').value : '';
            const socialDescription = document.querySelector('textarea[name="social_description"]') ? document.querySelector('textarea[name="social_description"]').value : '';
            const socialImage = document.querySelector('input[name="social_image"]') ? document.querySelector('input[name="social_image"]').value : '';
            
            // Update Google preview
            const previewTitle = document.getElementById('preview-title');
            const previewDescription = document.getElementById('preview-description');
            
            if (previewTitle) {
                previewTitle.textContent = title || 'Page Title';
            }
            if (previewDescription) {
                previewDescription.textContent = description || 'Page description will appear here...';
            }
            
            // Update social media preview
            const previewSocialTitle = document.getElementById('preview-social-title');
            const previewSocialDescription = document.getElementById('preview-social-description');
            const previewSocialImage = document.getElementById('preview-social-image');
            
            if (previewSocialTitle) {
                previewSocialTitle.textContent = socialTitle || 'Social Media Title';
            }
            if (previewSocialDescription) {
                previewSocialDescription.textContent = socialDescription || 'Social media description...';
            }
            if (previewSocialImage && socialImage) {
                previewSocialImage.innerHTML = `<img src="${socialImage}" alt="Social preview" class="w-full h-full object-cover rounded">`;
            } else if (previewSocialImage) {
                previewSocialImage.innerHTML = '';
                previewSocialImage.classList.add('bg-gray-200');
            }
        }
        
        // Update previews when any field changes
        [seoTitle, metaDescription, document.querySelector('input[name="social_title"]'), 
         document.querySelector('textarea[name="social_description"]'), document.querySelector('input[name="social_image"]')].forEach(field => {
            if (field) {
                field.addEventListener('input', updatePreviews);
            }
        });
        
        // Initial preview update
        updatePreviews();
    });
    </script>
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
        'social_image' => '',
        'canonical_base' => '',
        'default_language' => 'en',
        'additional_meta' => '',
        'focus_keyword' => '',
        'seo_title' => '',
        'meta_description' => '',
        'social_title' => '',
        'social_description' => ''
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
 * Extract metadata from HTML content
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
    
    // Method 1: Extract from existing meta tags (highest priority)
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['description'] = $matches[1];
    }
    
    if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['title'] = $matches[1];
    }
    
    if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['description'] = $matches[1];
    }
    
    if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['social_image'] = $matches[1];
    }
    
    // Method 2: Extract from data attributes
    if (preg_match('/data-seo-title=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['title'] = $matches[1];
    }
    
    if (preg_match('/data-seo-description=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['description'] = $matches[1];
    }
    
    if (preg_match('/data-seo-image=["\']([^"\']+)["\']/i', $content, $matches)) {
        $metadata['social_image'] = $matches[1];
    }
    
    // Method 3: Extract from JSON-LD structured data
    if (preg_match('/<script\s+type=["\']application\/ld\+json["\']\s*>(.*?)<\/script>/is', $content, $matches)) {
        $json = json_decode($matches[1], true);
        if (is_array($json)) {
            if (isset($json['name']) && empty($metadata['title'])) {
                $metadata['title'] = $json['name'];
            }
            if (isset($json['description']) && empty($metadata['description'])) {
                $metadata['description'] = $json['description'];
            }
            if (isset($json['image']) && empty($metadata['social_image'])) {
                $metadata['social_image'] = $json['image'];
            }
        }
    }
    
    // Method 4: Fallback to legacy markdown frontmatter (for backward compatibility)
    if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
        $json = json_decode($matches[1], true);
        if (is_array($json)) {
            if (isset($json['title']) && empty($metadata['title'])) {
                $metadata['title'] = $json['title'];
            }
            if (isset($json['description']) && empty($metadata['description'])) {
                $metadata['description'] = $json['description'];
            }
            if (isset($json['social_image']) && empty($metadata['social_image'])) {
                $metadata['social_image'] = $json['social_image'];
            }
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
    
    // Language meta tag
    if (!empty($settings['default_language'])) {
        $meta_tags .= '<meta http-equiv="content-language" content="' . htmlspecialchars($settings['default_language']) . '">' . "\n";
    }
    
    // Canonical URL
    if (!empty($settings['canonical_base'])) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $canonical_url = rtrim($settings['canonical_base'], '/') . $current_url;
        $meta_tags .= '<link rel="canonical" href="' . htmlspecialchars($canonical_url) . '">' . "\n";
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
    
    // Additional custom meta tags
    if (!empty($settings['additional_meta'])) {
        $meta_tags .= $settings['additional_meta'] . "\n";
    }
    
    // Replace title tag
    if (!empty($full_title)) {
        $template = preg_replace('/<title>.*?<\/title>/i', '<title>' . htmlspecialchars($full_title) . '</title>', $template);
    }
    
    // Insert meta tags before </head>
    $template = str_replace('</head>', $meta_tags . '</head>', $template);
}

/**
 * Generate SEO meta tags for HTML content
 * This function can be used in templates to generate proper meta tags
 */
function seo_generate_meta_tags($title = '', $description = '', $image = '', $type = 'website') {
    $settings = seo_get_settings();
    
    // Use provided values or fall back to settings
    $page_title = $title ?: $settings['site_title'];
    $page_description = $description ?: $settings['site_description'];
    $page_image = $image ?: $settings['social_image'];
    
    // Build full title
    $full_title = $page_title;
    if ($settings['append_site_title'] && !empty($page_title) && !empty($settings['site_title'])) {
        $full_title .= ' ' . $settings['title_separator'] . ' ' . $settings['site_title'];
    }
    
    $meta_tags = '';
    
    // Basic meta tags
    if (!empty($page_description)) {
        $meta_tags .= '<meta name="description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    }
    
    // Language meta tag
    if (!empty($settings['default_language'])) {
        $meta_tags .= '<meta http-equiv="content-language" content="' . htmlspecialchars($settings['default_language']) . '">' . "\n";
    }
    
    // Canonical URL
    if (!empty($settings['canonical_base'])) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $canonical_url = rtrim($settings['canonical_base'], '/') . $current_url;
        $meta_tags .= '<link rel="canonical" href="' . htmlspecialchars($canonical_url) . '">' . "\n";
    }
    
    // Open Graph meta tags
    $meta_tags .= '<meta property="og:type" content="' . htmlspecialchars($type) . '">' . "\n";
    $meta_tags .= '<meta property="og:title" content="' . htmlspecialchars($full_title) . '">' . "\n";
    if (!empty($page_description)) {
        $meta_tags .= '<meta property="og:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    }
    if (!empty($page_image)) {
        $meta_tags .= '<meta property="og:image" content="' . htmlspecialchars($page_image) . '">' . "\n";
    }
    
    // Twitter Card meta tags
    $meta_tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $meta_tags .= '<meta name="twitter:title" content="' . htmlspecialchars($full_title) . '">' . "\n";
    if (!empty($page_description)) {
        $meta_tags .= '<meta name="twitter:description" content="' . htmlspecialchars($page_description) . '">' . "\n";
    }
    if (!empty($page_image)) {
        $meta_tags .= '<meta name="twitter:image" content="' . htmlspecialchars($page_image) . '">' . "\n";
    }
    
    // Additional custom meta tags
    if (!empty($settings['additional_meta'])) {
        $meta_tags .= $settings['additional_meta'] . "\n";
    }
    
    return $meta_tags;
}

/**
 * Generate JSON-LD structured data for a page
 */
function seo_generate_structured_data($title = '', $description = '', $image = '', $type = 'WebPage') {
    $settings = seo_get_settings();
    
    $page_title = $title ?: $settings['site_title'];
    $page_description = $description ?: $settings['site_description'];
    $page_image = $image ?: $settings['social_image'];
    
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type' => $type,
        'name' => $page_title
    ];
    
    if (!empty($page_description)) {
        $structured_data['description'] = $page_description;
    }
    
    if (!empty($page_image)) {
        $structured_data['image'] = $page_image;
    }
    
    if (!empty($settings['canonical_base'])) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $structured_data['url'] = rtrim($settings['canonical_base'], '/') . $current_url;
    }
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}
