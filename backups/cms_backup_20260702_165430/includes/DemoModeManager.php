<?php
/**
 * Demo Mode Manager
 * Handles demo mode functionality for FearlessCMS
 */

class DemoModeManager {
    private $configFile;
    private $demoConfig;
    private $demoContentDir;
    private $demoConfigDir;
    
    public function __construct() {
        $this->configFile = CONFIG_DIR . '/demo_mode.json';
        $this->demoContentDir = PROJECT_ROOT . '/demo_content';
        $this->demoConfigDir = PROJECT_ROOT . '/demo_config';
        $this->loadConfig();
    }
    
    /**
     * Load demo mode configuration
     */
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $this->demoConfig = json_decode(file_get_contents($this->configFile), true);
        } else {
            $this->demoConfig = [
                'enabled' => false,
                'demo_user' => [
                    'username' => 'demo',
                    'password' => 'demo',
                    'role' => 'administrator'
                ],
                'session_timeout' => 3600, // 1 hour
                'cleanup_interval' => 86400, // 24 hours
                'max_demo_sessions' => 10
            ];
            $this->saveConfig();
        }
    }
    
    /**
     * Save demo mode configuration
     */
    private function saveConfig() {
        file_put_contents($this->configFile, json_encode($this->demoConfig, JSON_PRETTY_PRINT));
    }
    
    /**
     * Check if demo mode is enabled
     */
    public function isEnabled() {
        return $this->demoConfig['enabled'] ?? false;
    }
    
    /**
     * Enable demo mode
     */
    public function enable() {
        $this->demoConfig['enabled'] = true;
        $this->saveConfig();
        $this->setupDemoEnvironment();
    }
    
    /**
     * Disable demo mode
     */
    public function disable() {
        $this->demoConfig['enabled'] = false;
        $this->saveConfig();
        $this->cleanupDemoEnvironment();
    }
    
    /**
     * Check if current session is a demo session
     */
    public function isDemoSession() {
        return isset($_SESSION['demo_mode']) && $_SESSION['demo_mode'] === true;
    }
    
    /**
     * Check if current user is demo user (for testing purposes)
     */
    public function isDemoUserSession() {
        return isset($_SESSION['username']) && $_SESSION['username'] === 'demo';
    }
    
    /**
     * Enhanced demo user detection with multiple fallback methods
     */
    public function isDemoUser() {
        // Method 1: Check demo_mode session flag
        if (isset($_SESSION['demo_mode']) && $_SESSION['demo_mode'] === true) {
            return true;
        }
        
        // Method 2: Check username
        if (isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            return true;
        }
        
        // Method 3: Check demo session ID
        if (isset($_SESSION['demo_session_id']) && strpos($_SESSION['demo_session_id'], 'demo_') === 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if username matches demo user
     */
    public function isDemoUsername($username) {
        return $username === ($this->demoConfig['demo_user']['username'] ?? 'demo');
    }
    
    /**
     * Get demo user credentials
     */
    public function getDemoUser() {
        return $this->demoConfig['demo_user'];
    }
    
    /**
     * Start demo session
     */
    public function startDemoSession($username) {
        if ($this->isDemoUsername($username)) {
            $_SESSION['demo_mode'] = true;
            $_SESSION['demo_start_time'] = time();
            $_SESSION['demo_session_id'] = uniqid('demo_', true);
            
            // Create demo-specific directories
            $this->createDemoDirectories();
            
            // Copy sample content
            $this->setupDemoContent();
            
            return true;
        }
        return false;
    }
    
    /**
     * End demo session
     */
    public function endDemoSession() {
        // Check demo session detection methods
        $isDemoSession = $this->isDemoSession();
        
        // FALLBACK: Check username directly if session detection fails
        if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            error_log("DEMO: Fallback demo user detection in endDemoSession");
            $isDemoSession = true;
        }
        
        if ($isDemoSession) {
            unset($_SESSION['demo_mode']);
            unset($_SESSION['demo_start_time']);
            unset($_SESSION['demo_session_id']);
            
            // Cleanup demo content
            $this->cleanupDemoContent();
        }
    }
    
    /**
     * Check if demo session has expired
     */
    public function isDemoSessionExpired() {
        if (!$this->isDemoSession()) {
            return false;
        }
        
        $startTime = $_SESSION['demo_start_time'] ?? 0;
        $timeout = $this->demoConfig['session_timeout'] ?? 3600;
        
        return (time() - $startTime) > $timeout;
    }
    
    /**
     * Get demo content directory
     */
    public function getDemoContentDir() {
        return $this->demoContentDir;
    }
    
    /**
     * Get demo config directory
     */
    public function getDemoConfigDir() {
        return $this->demoConfigDir;
    }
    
    /**
     * Setup demo environment
     */
    private function setupDemoEnvironment() {
        // Create demo directories
        $this->createDemoDirectories();
        
        // Create demo user in users.json
        $this->createDemoUser();
        
        // Setup sample content
        $this->setupDemoContent();
    }
    
    /**
     * Create demo directories
     */
    private function createDemoDirectories() {
        $dirs = [
            $this->demoContentDir,
            $this->demoConfigDir,
            $this->demoContentDir . '/blog',
            $this->demoContentDir . '/pages',
            $this->demoConfigDir . '/demo_themes',
            $this->demoConfigDir . '/demo_uploads'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Create a new demo content file
     */
    public function createDemoContent($path, $title, $content, $metadata = []) {
        if (!$this->isDemoSession() && !$this->isDemoUserSession()) {
            return false;
        }
        
        $filePath = $this->demoContentDir . '/' . $path . '.md';
        $dir = dirname($filePath);
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $defaultMetadata = [
            'title' => $title,
            'demo_content' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $metadata = array_merge($defaultMetadata, $metadata);
        
        $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
        $fileContent = $frontmatter . "\n\n" . $content;
        
        return file_put_contents($filePath, $fileContent) !== false;
    }
    
    /**
     * Create demo user
     */
    private function createDemoUser() {
        $usersFile = CONFIG_DIR . '/users.json';
        $users = [];
        
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
        }
        
        // Check if demo user already exists
        $demoExists = false;
        foreach ($users as $user) {
            if ($user['username'] === 'demo') {
                $demoExists = true;
                break;
            }
        }
        
        if (!$demoExists) {
        $demoUser = [
            'username' => 'demo',
            'password' => password_hash('demo', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'demo_user' => true
        ];
            
            $users[] = $demoUser;
            file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Setup demo content
     */
    private function setupDemoContent() {
        // Create sample pages
        $this->createSamplePages();
        
        // Create sample blog posts
        $this->createSampleBlogPosts();
        
        // Create demo configuration
        $this->createDemoConfig();
    }
    
    /**
     * Create sample pages
     */
    private function createSamplePages() {
        $pages = [
            'home' => [
                'title' => 'Welcome to FearlessCMS Demo',
                'content' => "# Welcome to FearlessCMS Demo\n\nThis is a **demo website** created with FearlessCMS. You can explore all the features without affecting a real website.\n\n## Features You Can Try\n\n- **Content Management**: Create and edit pages\n- **Blog System**: Write and publish blog posts\n- **Theme Management**: Switch between different themes\n- **Plugin System**: Explore available plugins\n- **User Management**: Manage users and roles\n\n## Demo Limitations\n\n- This is a temporary demo session\n- Changes will be reset when the session expires\n- No real data is affected\n\nEnjoy exploring FearlessCMS!",
                'template' => 'home'
            ],
            'about' => [
                'title' => 'About This Demo',
                'content' => "# About This Demo\n\nThis is a **demonstration** of FearlessCMS capabilities.\n\n## What You Can Do\n\n- Edit this content using the admin panel\n- Create new pages and blog posts\n- Customize the theme and appearance\n- Manage users and permissions\n- Install and configure plugins\n\n## Getting Started\n\n1. Log in with username: `demo` and password: `demo`\n2. Explore the admin panel\n3. Try creating new content\n4. Experiment with different themes\n\nRemember, this is just a demo - your changes won't affect any real website!",
                'template' => 'page-with-sidebar'
            ],
            'contact' => [
                'title' => 'Contact Us',
                'content' => "# Contact Us\n\nThis is a demo contact page. In a real website, you would include:\n\n- Contact form\n- Business information\n- Office hours\n- Location details\n\n## Demo Information\n\n- **Username**: demo\n- **Password**: demo\n- **Session Timeout**: 1 hour\n- **Reset**: Every 24 hours\n\nFeel free to explore all the features of FearlessCMS!",
                'template' => 'page-with-sidebar'
            ]
        ];
        
        foreach ($pages as $slug => $page) {
            $content = "<!-- json\n" . json_encode([
                'title' => $page['title'],
                'template' => $page['template'],
                'demo_page' => true
            ], JSON_PRETTY_PRINT) . "\n-->\n\n" . $page['content'];
            
            $file = $this->demoContentDir . '/pages/' . $slug . '.md';
            file_put_contents($file, $content);
        }
    }
    
    /**
     * Create demo content with proper structure
     */
    public function createDemoContentFile($path, $title, $content, $metadata = []) {
        // Check demo session detection methods
        $isDemoSession = $this->isDemoSession() || $this->isDemoUserSession();
        
        // FALLBACK: Check username directly if session detection fails
        if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            error_log("DEMO: Fallback demo user detection in createDemoContentFile");
            $isDemoSession = true;
        }
        
        if (!$isDemoSession) {
            error_log("DEMO: createDemoContentFile rejected - not a demo user");
            return false;
        }
        
        // Generate unique session-based filename to avoid conflicts
        $sessionId = $_SESSION['demo_session_id'] ?? uniqid('demo_', true);
        $timestamp = time();
        
        // Determine if this is a blog post or page based on path
        $isBlogPost = strpos($path, 'blog/') === 0;
        
        if ($isBlogPost) {
            $blogPath = substr($path, 5); // Remove 'blog/' prefix
            $filePath = $this->demoContentDir . '/blog/' . $blogPath . '.md';
        } else {
            $filePath = $this->demoContentDir . '/pages/' . $path . '.md';
        }
        
        $dir = dirname($filePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $defaultMetadata = [
            'title' => $title,
            'demo_content' => true,
            'demo_session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'template' => $isBlogPost ? 'post' : 'page-with-sidebar'
        ];
        
        $metadata = array_merge($defaultMetadata, $metadata);
        
        $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
        $fileContent = $frontmatter . "\n\n" . $content;
        
        return file_put_contents($filePath, $fileContent) !== false;
    }
    
    /**
     * Clean up demo content for current session
     */
    public function cleanupDemoContent() {
        // Check demo session detection methods
        $isDemoSession = $this->isDemoSession() || $this->isDemoUserSession();
        
        // FALLBACK: Check username directly if session detection fails
        if (!$isDemoSession && isset($_SESSION['username']) && $_SESSION['username'] === 'demo') {
            error_log("DEMO: Fallback demo user detection in cleanupDemoContent");
            $isDemoSession = true;
        }
        
        if (!$isDemoSession) {
            error_log("DEMO: cleanupDemoContent rejected - not a demo user");
            return false;
        }
        
        $sessionId = $_SESSION['demo_session_id'] ?? null;
        if (!$sessionId) {
            // If no session ID, clean up all demo content (fallback for logout)
            error_log("DEMO: No session ID found, cleaning up all demo content");
            $sessionId = 'all'; // Use a special marker for cleanup
        }
        
        $cleanedFiles = 0;
        
        // Clean up demo content files
        $demoContentDir = $this->demoContentDir;
        if (is_dir($demoContentDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($demoContentDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'md') {
                    $content = file_get_contents($file->getPathname());
                    
                    // Check if this file belongs to current demo session or is demo content
                    $shouldDelete = false;
                    
                    if ($sessionId === 'all') {
                        // Clean up all demo content files
                        if (preg_match('/demo_content["\s]*:\s*true/', $content)) {
                            $shouldDelete = true;
                        }
                    } else {
                        // Clean up files from specific session
                        if (preg_match('/demo_session_id["\s]*:\s*["\']?' . preg_quote($sessionId, '/') . '["\']?/', $content)) {
                            $shouldDelete = true;
                        }
                    }
                    
                    if ($shouldDelete) {
                        unlink($file->getPathname());
                        $cleanedFiles++;
                    }
                }
            }
        }
        
        // Also clean up any demo posts from the main blog_posts.json file
        $this->cleanupDemoBlogPosts($sessionId);
        
        error_log("Demo content cleanup: Removed {$cleanedFiles} files for session {$sessionId}");
        return $cleanedFiles;
    }
    
    /**
     * Clean up demo blog posts from main blog_posts.json file
     */
    private function cleanupDemoBlogPosts($sessionId) {
        $blogPostsFile = CONTENT_DIR . '/blog_posts.json';
        if (!file_exists($blogPostsFile)) {
            return;
        }
        
        $posts = json_decode(file_get_contents($blogPostsFile), true);
        if (!is_array($posts)) {
            return;
        }
        
        $originalCount = count($posts);
        
        // Remove posts that appear to be demo content (based on titles/content patterns)
        $posts = array_filter($posts, function($post) {
            $title = strtolower($post['title'] ?? '');
            $content = strtolower($post['content'] ?? '');
            
            // Remove posts with demo/test indicators
            $demoPatterns = [
                'demo', 'test', 'no post', 'not a post', 'should not work', 'hopefully be deleted'
            ];
            
            foreach ($demoPatterns as $pattern) {
                if (strpos($title, $pattern) !== false || strpos($content, $pattern) !== false) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Save cleaned posts back to file
        if (count($posts) !== $originalCount) {
            file_put_contents($blogPostsFile, json_encode($posts, JSON_PRETTY_PRINT));
            error_log("Demo cleanup: Removed " . ($originalCount - count($posts)) . " demo blog posts");
        }
    }
    
    /**
     * Create sample blog posts
     */
    private function createSampleBlogPosts() {
        $posts = [
            'welcome-to-fearlesscms' => [
                'title' => 'Welcome to FearlessCMS',
                'content' => "# Welcome to FearlessCMS\n\nFearlessCMS is a modern, lightweight content management system built with simplicity and power in mind.\n\n## Key Features\n\n- **Markdown Support**: Write content in Markdown\n- **Theme System**: Easy-to-customize themes\n- **Plugin Architecture**: Extend functionality\n- **User Management**: Role-based permissions\n- **Responsive Design**: Works on all devices\n\n## Getting Started\n\n1. Create your first page\n2. Customize your theme\n3. Add plugins for additional functionality\n4. Configure your site settings\n\nThis demo allows you to explore all these features safely!",
                'date' => date('Y-m-d'),
                'author' => 'Demo User',
                'featured_image' => '',
                'tags' => ['demo', 'cms', 'getting-started']
            ],
            'customizing-your-theme' => [
                'title' => 'Customizing Your Theme',
                'content' => "# Customizing Your Theme\n\nOne of the most powerful features of FearlessCMS is its flexible theme system.\n\n## Theme Options\n\n- **Logo Upload**: Add your brand logo\n- **Hero Banner**: Create eye-catching headers\n- **Color Schemes**: Customize colors\n- **Layout Options**: Choose different layouts\n\n## Custom CSS\n\nYou can also add custom CSS to further customize your theme:\n\n```css\n.custom-header {\n    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);\n    color: white;\n    padding: 2rem;\n}\n```\n\nTry experimenting with different themes in this demo!",
                'date' => date('Y-m-d', strtotime('-1 day')),
                'author' => 'Demo User',
                'featured_image' => '',
                'tags' => ['themes', 'customization', 'css']
            ]
        ];
        
        foreach ($posts as $slug => $post) {
            $content = "<!-- json\n" . json_encode([
                'title' => $post['title'],
                'date' => $post['date'],
                'author' => $post['author'],
                'featured_image' => $post['featured_image'],
                'tags' => $post['tags'],
                'demo_post' => true
            ], JSON_PRETTY_PRINT) . "\n-->\n\n" . $post['content'];
            
            $file = $this->demoContentDir . '/blog/' . $slug . '.md';
            file_put_contents($file, $content);
        }
    }
    
    /**
     * Create demo configuration
     */
    private function createDemoConfig() {
        $demoConfig = [
            'site_name' => 'FearlessCMS Demo',
            'site_description' => 'A demonstration of FearlessCMS capabilities',
            'site_keywords' => 'demo, cms, fearlesscms, content management',
            'site_author' => 'Demo User',
            'admin_path' => 'admin',
            'demo_mode' => true
        ];
        
        $configFile = $this->demoConfigDir . '/config.json';
        file_put_contents($configFile, json_encode($demoConfig, JSON_PRETTY_PRINT));
        
        // Create demo theme options
        $themeOptions = [
            'logo' => '',
            'herobanner' => '',
            'demo_mode' => true
        ];
        
        $themeOptionsFile = $this->demoConfigDir . '/theme_options.json';
        file_put_contents($themeOptionsFile, json_encode($themeOptions, JSON_PRETTY_PRINT));
    }
    
    /**
     * Cleanup demo environment
     */
    private function cleanupDemoEnvironment() {
        // Remove demo user
        $this->removeDemoUser();
        
        // Cleanup demo directories
        $this->cleanupDemoDirectories();
    }
    
    /**
     * Remove demo user
     */
    private function removeDemoUser() {
        $usersFile = CONFIG_DIR . '/users.json';
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true) ?: [];
            $users = array_filter($users, function($user) {
                return $user['username'] !== 'demo';
            });
            file_put_contents($usersFile, json_encode(array_values($users), JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Cleanup demo directories
     */
    private function cleanupDemoDirectories() {
        if (file_exists($this->demoContentDir)) {
            $this->deleteDirectory($this->demoContentDir);
        }
        if (file_exists($this->demoConfigDir)) {
            $this->deleteDirectory($this->demoConfigDir);
        }
    }
    
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Get demo mode status for display
     */
    public function getStatus() {
        return [
            'enabled' => $this->isEnabled(),
            'demo_session' => $this->isDemoSession(),
            'session_id' => $_SESSION['demo_session_id'] ?? null,
            'start_time' => $_SESSION['demo_start_time'] ?? null,
            'timeout' => $this->demoConfig['session_timeout'] ?? 3600
        ];
    }
}