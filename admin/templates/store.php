<?php
// Get the CMS mode manager instance
global $cmsModeManager;

$page_title = 'Store';

// Check if store access is allowed
if (!$cmsModeManager->canAccessStore()) {
    // Redirect to plugins page with a message
    header('Location: ?action=plugins&error=store_disabled');
    exit;
}

// Load store configuration
$config_file = CONFIG_DIR . '/config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$store_repo = $config['store_url'] ?? 'https://github.com/fearlessgeekmedia/FearlessCMS-Store.git';

$store_data = null;
$news = '';
$featured = '';

// Determine if this is a raw content URL or a GitHub repository URL
$base_url = $store_repo;
if (strpos($store_repo, 'github.com') !== false) {
    // Transform GitHub repository URL to raw content URL
    $base_url = str_replace(['github.com', '.git'], ['raw.githubusercontent.com', ''], $store_repo) . '/main';
} elseif (strpos($store_repo, 'raw.githubusercontent.com') !== false) {
    // If it's a raw content URL, remove the store.json part to get the base URL
    $base_url = dirname($store_repo);
}

// Fetch store data
$store_json_url = $base_url . '/store.json';
error_log("Fetching store.json from: " . $store_json_url);
$store_json = fetch_github_content($store_json_url);
if ($store_json) {
    $store_data = json_decode($store_json, true);
}

// Fetch news and featured content
$news_url = $base_url . '/news.md';
$featured_url = $base_url . '/featured.md';

error_log("Fetching news from: " . $news_url);
error_log("Fetching featured from: " . $featured_url);

$news = fetch_github_content($news_url);
$featured = fetch_github_content($featured_url);

// Parse markdown content
require_once __DIR__ . '/../../includes/Parsedown.php';
$parsedown = new Parsedown();
$news_html = $parsedown->text($news);
$featured_html = $parsedown->text($featured);
?>

<div class="space-y-6">
    <!-- Store Configuration -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Store Configuration</h2>
        <form id="store-config-form" class="space-y-4">
            <div>
                <label for="store-repo" class="block text-sm font-medium text-gray-700">Store Repository URL</label>
                <input type="text" id="store-repo" name="store_repo" value="<?php echo htmlspecialchars($store_repo); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Update Store
                </button>
            </div>
        </form>
    </div>

    <!-- News and Featured -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Latest News</h2>
            <div class="prose max-w-none">
                <?php echo $news_html; ?>
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Featured</h2>
            <div class="prose max-w-none">
                <?php echo $featured_html; ?>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('plugins')" class="tab-button border-green-500 text-green-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" id="plugins-tab">
                Plugins
            </button>
            <button onclick="switchTab('themes')" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" id="themes-tab">
                Themes
            </button>
        </nav>
    </div>

    <!-- Search and Filter -->
    <div class="flex justify-between items-center">
        <div class="flex-1 max-w-lg">
            <div class="relative">
                <input type="text" id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm" placeholder="Search...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        <div class="ml-4">
            <select id="category-filter" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md">
                <option value="">All Categories</option>
                <?php
                if ($store_data && isset($store_data['categories'])) {
                    foreach ($store_data['categories'] as $category) {
                        echo '<option value="' . htmlspecialchars($category['slug']) . '">' . htmlspecialchars($category['name']) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <!-- Content -->
    <div id="plugins-content" class="tab-content">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            if ($store_data && isset($store_data['plugins'])) {
                foreach ($store_data['plugins'] as $plugin) {
                    ?>
                    <div class="bg-white overflow-hidden shadow rounded-lg" data-category="<?php echo htmlspecialchars($plugin['slug']); ?>">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <img src="<?php echo htmlspecialchars($plugin['icons']['default']); ?>" alt="<?php echo htmlspecialchars($plugin['name']); ?>" class="h-12 w-12">
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($plugin['name']); ?></h3>
                                    <p class="text-sm text-gray-500">By <a href="<?php echo htmlspecialchars($plugin['author_url']); ?>" class="text-green-600 hover:text-green-500"><?php echo htmlspecialchars($plugin['author']); ?></a></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($plugin['description']); ?></p>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-sm text-gray-500">Version <?php echo htmlspecialchars($plugin['version']); ?></span>
                                <button onclick="installPlugin('<?php echo htmlspecialchars($plugin['slug']); ?>')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Install
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>

    <div id="themes-content" class="tab-content hidden">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php
            if ($store_data && isset($store_data['themes'])) {
                foreach ($store_data['themes'] as $theme) {
                    ?>
                    <div class="bg-white overflow-hidden shadow rounded-lg" data-category="<?php echo htmlspecialchars($theme['slug']); ?>">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <img src="<?php echo htmlspecialchars($theme['icons']['default']); ?>" alt="<?php echo htmlspecialchars($theme['name']); ?>" class="h-12 w-12">
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($theme['name']); ?></h3>
                                    <p class="text-sm text-gray-500">By <a href="<?php echo htmlspecialchars($theme['author_url']); ?>" class="text-green-600 hover:text-green-500"><?php echo htmlspecialchars($theme['author']); ?></a></p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($theme['description']); ?></p>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-sm text-gray-500">Version <?php echo htmlspecialchars($theme['version']); ?></span>
                                <button onclick="installTheme('<?php echo htmlspecialchars($theme['slug']); ?>')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Install
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-green-500', 'text-green-600');
        button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
    });
    document.getElementById(`${tab}-tab`).classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
    document.getElementById(`${tab}-tab`).classList.add('border-green-500', 'text-green-600');

    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(`${tab}-content`).classList.remove('hidden');
}

// Search functionality
document.getElementById('search-input').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const currentTab = document.querySelector('.tab-button.border-green-500').id.replace('-tab', '');
    const items = document.querySelectorAll(`#${currentTab}-content .bg-white`);
    
    items.forEach(item => {
        const title = item.querySelector('h3').textContent.toLowerCase();
        const description = item.querySelector('.mt-4 p').textContent.toLowerCase();
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Category filter
document.getElementById('category-filter').addEventListener('change', function(e) {
    const category = e.target.value.toLowerCase();
    const currentTab = document.querySelector('.tab-button.border-green-500').id.replace('-tab', '');
    const items = document.querySelectorAll(`#${currentTab}-content .bg-white`);
    
    items.forEach(item => {
        if (!category || item.dataset.category === category) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Store configuration form
document.getElementById('store-config-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const storeRepo = document.getElementById('store-repo').value;
    
    // Send AJAX request to update store configuration
    fetch('?action=store&update_config=1', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `store_repo=${encodeURIComponent(storeRepo)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Response text:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Reload the page to show updated store
            window.location.reload();
        } else {
            alert('Failed to update store configuration: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating store configuration: ' + error.message);
    });
});

// Install plugin
function installPlugin(slug) {
    if (confirm('Are you sure you want to install this plugin?')) {
        fetch('?action=store&install_plugin=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `plugin_slug=${encodeURIComponent(slug)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Plugin installed successfully!');
                window.location.reload();
            } else {
                alert('Failed to install plugin: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while installing the plugin');
        });
    }
}

// Install theme
function installTheme(slug) {
    if (confirm('Are you sure you want to install this theme?')) {
        fetch('?action=store&install_theme=1', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `theme_slug=${encodeURIComponent(slug)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Theme installed successfully!');
                window.location.reload();
            } else {
                alert('Failed to install theme: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while installing the theme');
        });
    }
}

function searchPlugins() {
    const searchTerm = document.getElementById('plugin-search').value;
    const resultsContainer = document.getElementById('plugin-results');
    
    // Show loading state
    resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">Searching...</div>';
    
    // Make AJAX request
    const formData = new FormData();
    formData.append('query', searchTerm);
    
    fetch(window.location.href + '?action=store&search_plugins', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(results => {
        if (results.length === 0) {
            resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">No plugins found.</div>';
            return;
        }
        
        // Display results
        resultsContainer.innerHTML = results.map(plugin => {
            const isInstalled = isPluginInstalled(plugin.slug);
            const isActive = isPluginActive(plugin.slug);
            
            let actionButton = '';
            if (isInstalled) {
                if (isActive) {
                    actionButton = '<button class="px-3 py-1 rounded bg-gray-500 text-white">Active</button>';
                } else {
                    actionButton = '<button onclick="deletePlugin(\'' + plugin.slug + '\')" class="px-3 py-1 rounded bg-red-500 text-white hover:bg-red-600">Delete</button>';
                }
            } else {
                actionButton = '<button onclick="installPlugin(\'' + plugin.slug + '\')" class="px-3 py-1 rounded bg-green-500 text-white hover:bg-green-600">Install</button>';
            }
            
            return `
                <div class="bg-white border rounded-lg overflow-hidden">
                    <img src="${plugin.banners.low}" alt="${plugin.name}" class="w-full h-32 object-cover">
                    <div class="p-4">
                        <h4 class="font-medium">${plugin.name}</h4>
                        <p class="text-sm text-gray-600 mt-1">${plugin.description}</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-sm text-gray-500">v${plugin.version}</span>
                            <div class="flex gap-2">
                                <a href="${plugin.repository}" target="_blank" class="text-blue-500 hover:text-blue-600">Learn More</a>
                                ${actionButton}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    })
    .catch(error => {
        console.error('Error searching plugins:', error);
        resultsContainer.innerHTML = '<div class="col-span-full text-center py-4 text-red-500">Error searching plugins. Please try again.</div>';
    });
}

function searchThemes() {
    const searchTerm = document.getElementById('theme-search').value;
    const resultsContainer = document.getElementById('theme-results');
    
    // Show loading state
    resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">Searching...</div>';
    
    // Make AJAX request
    const formData = new FormData();
    formData.append('query', searchTerm);
    
    fetch(window.location.href + '?action=store&search_themes', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(results => {
        if (results.length === 0) {
            resultsContainer.innerHTML = '<div class="col-span-full text-center py-4">No themes found.</div>';
            return;
        }
        
        // Display results
        resultsContainer.innerHTML = results.map(theme => `
            <div class="bg-white border rounded-lg overflow-hidden">
                <img src="${theme.banners.low}" alt="${theme.name}" class="w-full h-32 object-cover">
                <div class="p-4">
                    <h4 class="font-medium">${theme.name}</h4>
                    <p class="text-sm text-gray-600 mt-1">${theme.description}</p>
                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-sm text-gray-500">v${theme.version}</span>
                        <a href="${theme.repository}" target="_blank" class="text-blue-500 hover:text-blue-600">Learn More</a>
                    </div>
                </div>
            </div>
        `).join('');
    })
    .catch(error => {
        console.error('Error searching themes:', error);
        resultsContainer.innerHTML = '<div class="col-span-full text-center py-4 text-red-500">Error searching themes. Please try again.</div>';
    });
}
</script> 