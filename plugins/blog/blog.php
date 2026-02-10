<?php
if (getenv('FCMS_DEBUG') === 'true') {
    error_log("Blog plugin - POST request received");
    error_log("Blog plugin - POST data: " . print_r($_POST, true));
}
?>
<?php
/*
Plugin Name: Blog
Description: Adds a blog with admin management to FearlessCMS.
Version: 0.0.3
Author: Fearless Geek
*/

define('BLOG_POSTS_FILE', CONTENT_DIR . '/blog_posts.json');

function blog_load_posts() {
    // Check if we're in demo mode and should load demo posts
    if (class_exists('DemoModeManager')) {
        $demoManager = new DemoModeManager();
        if ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) {
            return blog_load_demo_posts();
        }
    }
    
    if (!file_exists(BLOG_POSTS_FILE)) return [];
    $posts = json_decode(file_get_contents(BLOG_POSTS_FILE), true);
    return is_array($posts) ? $posts : [];
}

function blog_load_demo_posts() {
    $demoManager = new DemoModeManager();
    $demoContentDir = $demoManager->getDemoContentDir();
    $demoBlogDir = $demoContentDir . '/blog';
    
    if (!is_dir($demoBlogDir)) return [];
    
    $posts = [];
    $files = glob($demoBlogDir . '/*.md');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $slug = basename($file, '.md');
        
        // Parse frontmatter
        if (preg_match('/<!-- json\s*(.*?)\s*-->/s', $content, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                $posts[] = [
                    'id' => crc32($slug), // Generate consistent ID
                    'title' => $metadata['title'] ?? $slug,
                    'slug' => $slug,
                    'date' => $metadata['date'] ?? date('Y-m-d'),
                    'content' => trim(str_replace($matches[0], '', $content)),
                    'status' => 'published',
                    'featured_image' => $metadata['featured_image'] ?? '',
                    'demo_post' => true
                ];
            }
        }
    }
    
    return $posts;
}

function blog_save_posts($posts) {
    // Check if we're in demo mode and should save to demo content
    if (class_exists('DemoModeManager')) {
        $demoManager = new DemoModeManager();
        if ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) {
            return blog_save_demo_posts($posts);
        }
    }
    
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Blog plugin - blog_save_posts called");
        error_log("Blog plugin - Saving to file: " . BLOG_POSTS_FILE);
        error_log("Blog plugin - Posts to save: " . print_r($posts, true));
    }

    $json = json_encode($posts, JSON_PRETTY_PRINT);
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Blog plugin - JSON to write: " . $json);
    }

    $result = file_put_contents(BLOG_POSTS_FILE, $json);
    if (getenv('FCMS_DEBUG') === 'true') {
        error_log("Blog plugin - Save result: " . ($result !== false ? "success" : "failed"));
    }

    return $result;
}

function blog_save_demo_posts($posts) {
    $demoManager = new DemoModeManager();
    $demoContentDir = $demoManager->getDemoContentDir();
    $demoBlogDir = $demoContentDir . '/blog';
    
    // Ensure demo blog directory exists
    if (!is_dir($demoBlogDir)) {
        mkdir($demoBlogDir, 0755, true);
    }
    
    // Save each post as a markdown file in demo content
    foreach ($posts as $post) {
        if (isset($post['demo_post']) && $post['demo_post']) {
            $slug = $post['slug'];
            $filePath = $demoBlogDir . '/' . $slug . '.md';
            
            $metadata = [
                'title' => $post['title'],
                'date' => $post['date'],
                'author' => 'Demo User',
                'featured_image' => $post['featured_image'] ?? '',
                'demo_post' => true,
                'demo_session_id' => $_SESSION['demo_session_id'] ?? uniqid('demo_', true)
            ];
            
            $frontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
            $fileContent = $frontmatter . "\n\n" . $post['content'];
            
            file_put_contents($filePath, $fileContent);
        }
    }
    
    return true;
}

// Helper function to create URL-friendly slugs
function blog_create_slug($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    return $text;
}

// Function to generate RSS feed
function blog_generate_rss() {
    $posts = blog_load_posts();
    $published = array_filter($posts, fn($p) => $p['status'] === 'published');
    usort($published, fn($a, $b) => strcmp($b['date'], $a['date']));

    // Get site configuration
    $configFile = CONFIG_DIR . '/config.json';
    $siteName = 'FearlessCMS';
    $siteDescription = '';
    $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        $siteName = $config['site_name'] ?? $siteName;
        $siteDescription = $config['site_description'] ?? $siteDescription;
    }

    // Generate RSS XML
    $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
    $rss .= '  <channel>' . "\n";
    $rss .= '    <title>' . htmlspecialchars($siteName) . ' - Blog</title>' . "\n";
    $rss .= '    <link>' . htmlspecialchars($siteUrl) . '/blog</link>' . "\n";
    $rss .= '    <description>' . htmlspecialchars($siteDescription) . '</description>' . "\n";
    $rss .= '    <language>en-us</language>' . "\n";
    $rss .= '    <lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . "\n";
    $rss .= '    <atom:link href="' . htmlspecialchars($siteUrl) . '/blog/rss" rel="self" type="application/rss+xml" />' . "\n";

    foreach ($published as $post) {
        $postUrl = $siteUrl . '/blog/' . urlencode($post['slug']);
        $pubDate = date(DATE_RSS, strtotime($post['date']));

        // Convert markdown to plain text for better RSS readability
        $content = $post['content'];

        // Remove markdown headers
        $content = preg_replace('/^#{1,6}\s+/m', '', $content);

        // Remove markdown links but keep the text
        $content = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $content);

        // Remove markdown formatting
        $content = preg_replace('/\*\*([^*]+)\*\*/', '$1', $content);
        $content = preg_replace('/\*([^*]+)\*/', '$1', $content);
        $content = preg_replace('/`([^`]+)`/', '$1', $content);

        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $content);

        // Clean up whitespace
        $content = preg_replace('/\n\s*\n/', "\n\n", $content);
        $content = trim($content);

        // Create description (first 300 characters)
        $description = substr($content, 0, 300);
        if (strlen($content) > 300) {
            $description .= '...';
        }

        $rss .= '    <item>' . "\n";
        $rss .= '      <title>' . htmlspecialchars($post['title']) . '</title>' . "\n";
        $rss .= '      <link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
        $rss .= '      <guid>' . htmlspecialchars($postUrl) . '</guid>' . "\n";
        $rss .= '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
        $rss .= '      <description>' . htmlspecialchars($description) . '</description>' . "\n";
        $rss .= '    </item>' . "\n";
    }

    $rss .= '  </channel>' . "\n";
    $rss .= '</rss>';

    return $rss;
}

fcms_register_admin_section('blog', [
    'label' => 'Blog',
    'menu_order' => 40,
    'parent' => 'manage_plugins',
    'render_callback' => function() {
        ob_start();
        $posts = blog_load_posts();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token first
            if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
                error_log("Blog plugin - CSRF token validation failed");
                echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Invalid security token. Please refresh the page and try again.</div>';
            } else {
                if (isset($_POST['action']) && $_POST['action'] === 'save_post') {
                    if (getenv('FCMS_DEBUG') === 'true') {
                        error_log("Blog plugin - Starting save_post action");
                        error_log("Blog plugin - POST data: " . print_r($_POST, true));
                    }

                    $id = $_POST['id'] ?? null;
                    $title = trim($_POST['title'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');

                    if (getenv('FCMS_DEBUG') === 'true') {
                        error_log("Blog plugin - ID: " . $id);
                        error_log("Blog plugin - Title: " . $title);
                        error_log("Blog plugin - Slug: " . $slug);
                    }

                    // Auto-generate slug if empty
                    if (empty($slug) && !empty($title)) {
                        $slug = blog_create_slug($title);
                        if (getenv('FCMS_DEBUG') === 'true') {
                            error_log("Blog plugin - Generated slug: " . $slug);
                        }
                    } else {
                        // Ensure slug is URL-friendly
                        $slug = blog_create_slug($slug);
                        if (getenv('FCMS_DEBUG') === 'true') {
                            error_log("Blog plugin - URL-friendly slug: " . $slug);
                        }
                    }

                    $date = trim($_POST['date'] ?? date('Y-m-d'));
                    $content = $_POST['content'] ?? '';
                    $status = $_POST['status'] ?? 'draft';

                    if (getenv('FCMS_DEBUG') === 'true') {
                        error_log("Blog plugin - Date: " . $date);
                        error_log("Blog plugin - Status: " . $status);
                        error_log("Blog plugin - Content length: " . strlen($content));
                    }

                    if ($title && $slug) {
                        if ($id) {
                            foreach ($posts as &$post) {
                                if ($post['id'] == $id) {
                                    $post['title'] = $title;
                                    $post['slug'] = $slug;
                                    $post['date'] = $date;
                                    $post['content'] = $content;
                                    $post['status'] = $status;
                                    $post['featured_image'] = $_POST['featured_image'] ?? '';
                                }
                            }
                        } else {
                            $newPost = [
                                'id' => time(),
                                'title' => $title,
                                'slug' => $slug,
                                'date' => $date,
                                'content' => $content,
                                'status' => $status,
                                'featured_image' => $_POST['featured_image'] ?? ''
                            ];
                            
                            // Mark as demo post if in demo mode
                            if (class_exists('DemoModeManager')) {
                                $demoManager = new DemoModeManager();
                                if ($demoManager->isDemoSession() || $demoManager->isDemoUserSession()) {
                                    $newPost['demo_post'] = true;
                                }
                            }
                            
                            $posts[] = $newPost;
                        }
                        blog_save_posts($posts);
                        // Set success message in session and let admin system handle the flow
                        if (getenv('FCMS_DEBUG') === 'true') {
                            error_log("Blog plugin - Post saved successfully");
                        }
                        // Set session message for the admin system to display
                        if (function_exists('session_start') && session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION['blog_success'] = 'Blog post saved successfully!';
                        // Redirect back to blog page to show success message
                        echo '<script>window.location.href = "?action=blog";</script>';
                        return ob_get_clean();
                    } else {
                        if (getenv('FCMS_DEBUG') === 'true') {
                            error_log("Blog plugin - Invalid title or slug, skipping save");
                        }
                    }
                } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_post' && isset($_POST['id'])) {
                    $posts = array_filter($posts, fn($p) => $p['id'] != $_POST['id']);
                    blog_save_posts($posts);
                    // Set success message in session and let admin system handle the flow
                    if (getenv('FCMS_DEBUG') === 'true') {
                        error_log("Blog plugin - Post deleted successfully");
                    }
                    // Set session message for the admin system to display
                    if (function_exists('session_start') && session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['blog_deleted'] = 'Blog post deleted successfully!';
                    // Redirect back to blog page to show success message
                    echo '<script>window.location.href = "?action=blog";</script>';
                    return ob_get_clean();
                }
            }
        }
        echo '<h2 class="text-2xl font-bold mb-6 fira-code">Blog Posts</h2>';
        
        // Display success messages
        if (isset($_SESSION['blog_success'])) {
            echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . htmlspecialchars($_SESSION['blog_success']) . '</div>';
            unset($_SESSION['blog_success']);
        }
        if (isset($_SESSION['blog_deleted'])) {
            echo '<div class="bg-blue-100 text-blue-700 p-4 rounded mb-4">' . htmlspecialchars($_SESSION['blog_deleted']) . '</div>';
            unset($_SESSION['blog_deleted']);
        }
        
        echo '<a href="?action=blog&new=1" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">New Post</a><br><br>';

        // Add direct upload functionality for featured images
        echo '<script>
        function uploadFeaturedImage() {
            const input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.click();
            
            input.onchange = function() {
                const file = input.files[0];
                if (file) {
                    // Show loading state
                    const uploadBtn = document.querySelector("#featured-image-upload-btn");
                    const originalText = uploadBtn.textContent;
                    uploadBtn.textContent = "Uploading...";
                    uploadBtn.disabled = true;
                    
                    const formData = new FormData();
                    formData.append("action", "upload_featured_image");
                    formData.append("image", file);
                    
                    fetch("?action=upload_featured_image", {
                        method: "POST",
                        body: formData
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            document.querySelector("input[name=\'featured_image\']").value = data.url;
                            // Update preview
                            const previewContainer = document.querySelector("#featured-image-preview");
                            if (previewContainer) {
                                previewContainer.innerHTML = `<img src="${data.url}" alt="Featured image preview" class="max-w-xs h-auto rounded">`;
                            }
                        } else {
                            alert("Upload failed: " + (data.error || "Unknown error"));
                        }
                    })
                    .catch(function(error) {
                        alert("Upload failed: " + error);
                    })
                    .finally(function() {
                        uploadBtn.textContent = originalText;
                        uploadBtn.disabled = false;
                    });
                }
            };
        }
        </script>';

        // Add Quill.js styling
        echo '<style>
        .ql-editor {
            min-height: 400px;
            font-size: 16px;
            line-height: 1.6;
        }
        .ql-toolbar {
            border: 1px solid #ccc;
            border-radius: 4px 4px 0 0;
            background: #f8f9fa;
        }
        .ql-container {
            border: 1px solid #ccc;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }
        #blog-toast-editor {
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        </style>';
        
        if (isset($_GET['edit'])) {
            $edit = null;
            foreach ($posts as $p) if ($p['id'] == $_GET['edit']) $edit = $p;
            if (!$edit) {
                echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Post not found</div>';
                echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
            } else {
                echo '<form method="POST" class="space-y-4" id="blog-post-form" data-ajax="false">';
                echo '<input type="hidden" name="action" value="save_post">';
                echo '<input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '">';
                if (function_exists('csrf_token_field')) echo csrf_token_field();
                echo '<div><label>Title:</label><input name="title" value="' . htmlspecialchars($edit['title']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div><label>Slug:</label><input name="slug" value="' . htmlspecialchars($edit['slug']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div class="text-sm text-gray-500">The slug should be URL-friendly (lowercase, no spaces). Example: my-blog-post</div>';
                echo '<div><label>Date:</label><input name="date" value="' . htmlspecialchars($edit['date']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published"' . ($edit['status'] === 'published' ? ' selected' : '') . '>Published</option><option value="draft"' . ($edit['status'] === 'draft' ? ' selected' : '') . '>Draft</option></select></div>';
                echo '<div><label>Featured Image:</label>';
                echo '<div class="flex items-center space-x-4">';
                echo '<input type="text" name="featured_image" value="' . htmlspecialchars($edit['featured_image'] ?? '') . '" class="border rounded px-2 py-1 flex-grow" placeholder="Image URL or upload using button">';
                echo '<button type="button" id="featured-image-upload-btn" onclick="uploadFeaturedImage()" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Upload Image</button>';
                echo '</div>';
                echo '<div id="featured-image-preview" class="mt-2">';
                if (!empty($edit['featured_image'])) {
                    echo '<img src="' . htmlspecialchars($edit['featured_image']) . '" alt="Featured image preview" class="max-w-xs h-auto rounded">';
                }
                echo '</div>';
                echo '</div>';
                echo '<div><label>Content:</label></div>';
                echo '<div id="blog-toast-editor"></div>';
                echo '<input type="hidden" name="content" id="blog-editor-content">';
                echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
                echo '</form>';
                echo '<form method="POST" class="mt-4"><input type="hidden" name="action" value="delete_post"><input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '">';
                if (function_exists('csrf_token_field')) echo csrf_token_field();
                echo '<button type="submit" onclick="return confirm(\'Delete this post?\')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button></form>';
                echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';

                // Quill.js Editor initialization
                echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var initialContent = ' . json_encode($edit['content']) . ';
                    // Create a simple toolbar without problematic modules
                    var toolbarOptions = [
                        [{ "header": [1, 2, 3, false] }],
                        ["bold", "italic", "underline", "strike"],
                        [{ "list": "ordered"}, { "list": "bullet" }],
                        [{ "color": [] }, { "background": [] }],
                        [{ "align": [] }],
                        ["link"],
                        ["clean"]
                    ];

                    var editor = new Quill("#blog-toast-editor", {
                        theme: "snow",
                        modules: {
                            toolbar: toolbarOptions,
                            clipboard: {
                                matchVisual: false
                            }
                        },
                        placeholder: "Start writing your blog post here...",
                        height: "500px"
                    });

                    // Add custom image handling after Quill is initialized
                    var imageButton = document.createElement("button");
                    imageButton.innerHTML = \'<svg viewBox="0 0 18 18"><rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect><circle class="ql-fill" cx="6" cy="6" r="1"></circle><polyline class="ql-even ql-fill" points="5 8,9 4,13 8,13 14,5 14"></polyline></svg>\';
                    imageButton.type = "button";
                    imageButton.className = "ql-image";
                    imageButton.setAttribute("aria-label", "Insert image");
                    
                    // Add click handler for image upload
                    imageButton.addEventListener("click", function() {
                        var input = document.createElement("input");
                        input.setAttribute("type", "file");
                        input.setAttribute("accept", "image/*");
                        input.click();
                        
                        input.onchange = function() {
                            var file = input.files[0];
                            if (file) {
                                var formData = new FormData();
                                formData.append("action", "upload_image");
                                formData.append("image", file);
                                
                                fetch("?action=upload_image", {
                                    method: "POST",
                                    body: formData
                                })
                                .then(function(response) { return response.json(); })
                                .then(function(data) {
                                    if (data.success) {
                                        var range = editor.getSelection();
                                        editor.insertEmbed(range.index, "image", data.url);
                                    } else {
                                        alert("Upload failed: " + (data.error || "Unknown error"));
                                    }
                                })
                                .catch(function(error) {
                                    alert("Upload failed: " + error);
                                });
                            }
                        };
                    });
                    
                    // Add the custom image button to the toolbar
                    var toolbar = editor.getModule("toolbar");
                    toolbar.addHandler("image", function() {
                        imageButton.click();
                    });
                    
                    // Insert the image button into the toolbar
                    var toolbarContainer = document.querySelector(".ql-toolbar");
                    if (toolbarContainer) {
                        toolbarContainer.appendChild(imageButton);
                    }

                    // Set initial content if editing
                    if (initialContent) {
                        editor.root.innerHTML = initialContent;
                    }

                    document.getElementById("blog-post-form").addEventListener("submit", function(e) {
                        var content = editor.root.innerHTML;
                        console.log("Editor content before save:", content);
                        document.getElementById("blog-editor-content").value = content;
                        console.log("Form content after setting:", document.getElementById("blog-editor-content").value);
                    });
                });
                </script>';
            }
        } elseif (isset($_GET['new'])) {
            echo '<form method="POST" class="space-y-4" id="blog-post-form" data-ajax="false">';
            echo '<input type="hidden" name="action" value="save_post">';
            if (function_exists('csrf_token_field')) echo csrf_token_field();
            echo '<div><label>Title:</label><input name="title" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Slug:</label><input name="slug" class="border rounded px-2 py-1 w-full" placeholder="auto-generated-if-empty"></div>';
            echo '<div class="text-sm text-gray-500">The slug should be URL-friendly (lowercase, no spaces). Example: my-blog-post</div>';
            echo '<div><label>Date:</label><input name="date" value="' . date('Y-m-d') . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published">Published</option><option value="draft">Draft</option></select></div>';
            echo '<div><label>Featured Image:</label>';
            echo '<div class="flex items-center space-x-4">';
            echo '<input type="text" name="featured_image" class="border rounded px-2 py-1 flex-grow" placeholder="Image URL or upload using button">';
            echo '<button type="button" id="featured-image-upload-btn" onclick="uploadFeaturedImage()" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Upload Image</button>';
            echo '</div>';
            echo '<div id="featured-image-preview" class="mt-2"></div>';
            echo '</div>';
            echo '<div><label>Content:</label></div>';
            echo '<div id="blog-toast-editor"></div>';
            echo '<input type="hidden" name="content" id="blog-editor-content">';
            echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
            echo '</form>';
            echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';

            // Quill.js Editor initialization for new post
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Create a simple toolbar without problematic modules
                var toolbarOptions = [
                    [{ "header": [1, 2, 3, false] }],
                    ["bold", "italic", "underline", "strike"],
                    [{ "list": "ordered"}, { "list": "bullet" }],
                    [{ "color": [] }, { "background": [] }],
                    [{ "align": [] }],
                    ["link"],
                    ["clean"]
                ];

                var editor = new Quill("#blog-toast-editor", {
                    theme: "snow",
                    modules: {
                        toolbar: toolbarOptions,
                        clipboard: {
                            matchVisual: false
                        }
                    },
                    placeholder: "Start writing your blog post here...",
                    height: "500px"
                });

                // Add custom image handling after Quill is initialized
                var imageButton = document.createElement("button");
                imageButton.innerHTML = \'<svg viewBox="0 0 18 18"><rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect><circle class="ql-fill" cx="6" cy="6" r="1"></circle><polyline class="ql-even ql-fill" points="5 8,9 4,13 8,13 14,5 14"></polyline></svg>\';
                imageButton.type = "button";
                imageButton.className = "ql-image";
                imageButton.setAttribute("aria-label", "Insert image");
                
                // Add click handler for image upload
                imageButton.addEventListener("click", function() {
                    var input = document.createElement("input");
                    input.setAttribute("type", "file");
                    input.setAttribute("accept", "image/*");
                    input.click();
                    
                    input.onchange = function() {
                        var file = input.files[0];
                        if (file) {
                            var formData = new FormData();
                            formData.append("action", "upload_image");
                            formData.append("image", file);
                            
                            fetch("?action=upload_image", {
                                method: "POST",
                                body: formData
                            })
                            .then(function(response) { return response.json(); })
                            .then(function(data) {
                                if (data.success) {
                                    var range = editor.getSelection();
                                    editor.insertEmbed(range.index, "image", data.url);
                                } else {
                                    alert("Upload failed: " + (data.error || "Unknown error"));
                                }
                            })
                            .catch(function(error) {
                                alert("Upload failed: " + error);
                            });
                        }
                    };
                });
                
                // Add the custom image button to the toolbar
                var toolbar = editor.getModule("toolbar");
                toolbar.addHandler("image", function() {
                    imageButton.click();
                });
                
                // Insert the image button into the toolbar
                var toolbarContainer = document.querySelector(".ql-toolbar");
                if (toolbarContainer) {
                    toolbarContainer.appendChild(imageButton);
                }

                document.getElementById("blog-post-form").addEventListener("submit", function(e) {
                    var content = editor.root.innerHTML;
                    console.log("Editor content before save:", content);
                    document.getElementById("blog-editor-content").value = content;
                    console.log("Form content after setting:", document.getElementById("blog-editor-content").value);
                });
            });
            </script>';
        } else {
            echo '<table class="w-full border-collapse"><tr><th class="border-b py-2">Title</th><th class="border-b py-2">Slug</th><th class="border-b py-2">Date</th><th class="border-b py-2">Status</th><th class="border-b py-2">Actions</th></tr>';
            foreach ($posts as $p) {
                echo '<tr>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['title']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['slug']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['date']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['status']) . '</td>';
                echo '<td class="py-2 border-b">
                    <a href="?action=blog&edit=' . $p['id'] . '" class="text-blue-600 hover:underline mr-2">Edit</a>
                    <a href="/blog/' . urlencode($p['slug']) . '" target="_blank" class="text-green-600 hover:underline mr-2">View</a>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="delete_post">
                        <input type="hidden" name="id" value="' . htmlspecialchars($p['id']) . '">';
                        if (function_exists('csrf_token_field')) echo csrf_token_field();
                        echo '<button type="submit" onclick="return confirm(\'Are you sure you want to delete this post?\')" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        return ob_get_clean();
    }
]);

// Public route: /blog and /blog/{slug}
fcms_add_hook('route', function (&$handled, &$title, &$content, $path) {
    // Handle RSS feed route
    if ($path === 'blog/rss') {
        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo blog_generate_rss();
        $handled = true;
        exit; // Exit immediately to prevent any additional processing
    }

    if (preg_match('#^blog(?:/([^/]+))?$#', $path, $m)) {
        $posts = blog_load_posts();

        if (!empty($m[1])) {
            $slug = urldecode($m[1]);

            foreach ($posts as $post) {
                if ($post['slug'] === $slug && $post['status'] === 'published') {
                    $title = $post['title'];
                    if (!class_exists('Parsedown')) require_once PROJECT_ROOT . '/includes/Parsedown.php';
                    $Parsedown = new Parsedown();
                    $content = '<article class="max-w-4xl mx-auto px-4 py-8">';
                    if (!empty($post['featured_image'])) {
                        $content .= '<div class="mb-8"><img src="' . htmlspecialchars($post['featured_image']) . '" alt="' . htmlspecialchars($post['title']) . '" class="w-full h-96 object-cover rounded-lg shadow-lg"></div>';
                    }
                    $content .= '<h1 class="text-3xl font-bold mb-4" style="line-height: 3.25rem;">' . htmlspecialchars($post['title']) . '</h1>';
                    $content .= '<div class="text-gray-600 mb-8">' . htmlspecialchars($post['date']) . '</div>';
                    $content .= '<div class="prose max-w-none">' . $Parsedown->text($post['content']) . '</div>';
                    $content .= '</article>';
                    $handled = true;
                    return;
                }
            }

            $title = 'Post Not Found';
            $content = '<p>Sorry, that blog post does not exist.</p>';
            $handled = true;
        } else {
            $published = array_filter($posts, fn($p) => $p['status'] === 'published');
            usort($published, fn($a, $b) => strcmp($b['date'], $a['date']));
            $title = 'Blog';
            $content = '<div class="max-w-4xl mx-auto px-4 py-8">';
            $content .= '<h1 class="text-3xl font-bold mb-8" style="line-height: 3.25rem;">Blog Posts</h1>';
            // Add RSS feed link
            $content .= '<div class="mb-4"><a href="/blog/rss" class="text-blue-600 hover:underline">📡 RSS Feed</a></div>';
            $content .= '<div class="space-y-8">';
            foreach ($published as $post) {
                $content .= '<article class="border-b pb-8">';
                if (!empty($post['featured_image'])) {
                    $content .= '<div class="mb-4"><img src="' . htmlspecialchars($post['featured_image']) . '" alt="' . htmlspecialchars($post['title']) . '" class="w-full h-64 object-cover rounded"></div>';
                }
                $content .= '<h2 class="text-2xl font-bold mb-2"><a href="/blog/' . urlencode($post['slug']) . '" class="text-blue-600 hover:underline">' . htmlspecialchars($post['title']) . '</a></h2>';
                $content .= '<div class="text-gray-600 mb-4">' . htmlspecialchars($post['date']) . '</div>';
                if (!class_exists('Parsedown')) require_once PROJECT_ROOT . '/includes/Parsedown.php';
                $Parsedown = new Parsedown();
                $content .= '<div class="prose">' . $Parsedown->text(substr($post['content'], 0, 300) . '...') . '</div>';
                $content .= '<a href="/blog/' . urlencode($post['slug']) . '" class="text-blue-600 hover:underline mt-4 inline-block">Read more →</a>';
                $content .= '</article>';
            }
            $content .= '</div></div>';
            $handled = true;
        }
    }
});

// Add template selection for blog posts
fcms_add_hook('before_render', function(&$template, $path = null) {
    // If path is not provided, check if we're in a blog route
    if ($path === null) {
        $currentPath = trim($_SERVER['REQUEST_URI'], '/');
        if (preg_match('#^blog(?:/([^/]+))?$#', $currentPath)) {
            $template = 'blog';
        }
    } else if (preg_match('#^blog(?:/([^/]+))?$#', $path)) {
        $template = 'blog';
    }

    // Don't use template for RSS feed
    if ($path === 'blog/rss' || (isset($_SERVER['REQUEST_URI']) && trim($_SERVER['REQUEST_URI'], '/') === 'blog/rss')) {
        $template = null;
    }
});
