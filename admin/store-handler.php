<?php
// Register store admin section
fcms_register_admin_section('store', [
    'label' => 'Store',
    'menu_order' => 40,
    'render_callback' => 'store_admin_page'
]);

/**
 * Simple markdown parser if Parsedown is not available
 */
class SimpleMarkdown {
    public function text($text) {
        // Convert headers
        $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
        $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
        
        // Convert bold and italic
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        
        // Convert links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2">$1</a>', $text);
        
        // Convert paragraphs
        $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
        
        return $text;
    }
}

/**
 * Fetch content from GitHub repository
 */
function fetch_github_content($path) {
    $storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/fearlesscms-store';
    $rawUrl = str_replace('github.com', 'raw.githubusercontent.com', $storeUrl) . '/main/' . $path;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $rawUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($ch);
    curl_close($ch);
    
    return $content;
}

/**
 * Get markdown parser instance
 */
function get_markdown_parser() {
    // Try to use Parsedown if available
    if (class_exists('Parsedown')) {
        return new Parsedown();
    }
    // Fall back to simple markdown parser
    return new SimpleMarkdown();
}

/**
 * Render the store admin page
 */
function store_admin_page() {
    // Load store configuration
    require_once dirname(__DIR__) . '/includes/config/store.php';
    
    // Get current store URL
    $storeUrl = defined('STORE_URL') ? STORE_URL : 'https://github.com/fearlessgeekmedia/fearlesscms-store';
    
    // Fetch news and featured content
    $newsContent = fetch_github_content('news.md');
    $featuredContent = fetch_github_content('featured.md');
    
    // Get markdown parser
    $parser = get_markdown_parser();
    
    // Start output buffer
    ob_start();
    ?>
    <div class="space-y-8">
        <!-- Store Configuration -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Store Configuration</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_store_settings">
                <div>
                    <label class="block mb-1">Store URL</label>
                    <input type="text" 
                           name="store_url" 
                           value="<?php echo htmlspecialchars($storeUrl); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Update Store URL</button>
            </form>
        </div>

        <!-- Featured Items -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Featured Items</h3>
            <div class="prose max-w-none">
                <?php 
                if ($featuredContent) {
                    echo $parser->text($featuredContent);
                } else {
                    echo '<p class="text-gray-500">Unable to load featured items.</p>';
                }
                ?>
            </div>
        </div>

        <!-- News -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Latest News</h3>
            <div class="prose max-w-none">
                <?php 
                if ($newsContent) {
                    echo $parser->text($newsContent);
                } else {
                    echo '<p class="text-gray-500">Unable to load news.</p>';
                }
                ?>
            </div>
        </div>

        <!-- Plugin Search -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Search Plugins</h3>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <input type="text" 
                           id="plugin-search" 
                           placeholder="Search plugins..." 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded">
                    <button onclick="searchPlugins()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Search
                    </button>
                </div>
                <div id="plugin-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Plugin results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Theme Search -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Search Themes</h3>
            <div class="space-y-4">
                <div class="flex gap-4">
                    <input type="text" 
                           id="theme-search" 
                           placeholder="Search themes..." 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded">
                    <button onclick="searchThemes()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Search
                    </button>
                </div>
                <div id="theme-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Theme results will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Store Statistics -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Store Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Total Plugins</div>
                    <div class="text-2xl font-bold">0</div>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <div class="text-sm text-gray-500">Total Themes</div>
                    <div class="text-2xl font-bold">0</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function searchPlugins() {
        const searchTerm = document.getElementById('plugin-search').value;
        // TODO: Implement plugin search
        console.log('Searching plugins:', searchTerm);
    }

    function searchThemes() {
        const searchTerm = document.getElementById('theme-search').value;
        // TODO: Implement theme search
        console.log('Searching themes:', searchTerm);
    }
    </script>
    <?php
    return ob_get_clean();
} 