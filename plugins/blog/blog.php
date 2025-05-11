<?php
/*
Plugin Name: Blog
Description: Adds a blog with admin management to FearlessCMS.
Version: 2.0
Author: Fearless Geek
*/

define('BLOG_POSTS_FILE', CONTENT_DIR . '/blog_posts.json');

function blog_load_posts() {
    if (!file_exists(BLOG_POSTS_FILE)) return [];
    $posts = json_decode(file_get_contents(BLOG_POSTS_FILE), true);
    return is_array($posts) ? $posts : [];
}

function blog_save_posts($posts) {
    file_put_contents(BLOG_POSTS_FILE, json_encode($posts, JSON_PRETTY_PRINT));
}

// Helper function to create URL-friendly slugs
function blog_create_slug($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
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

fcms_register_admin_section('blog', [
    'label' => 'Blog',
    'menu_order' => 40,
    'render_callback' => function() {
        ob_start();
        $posts = blog_load_posts();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'save_post') {
                $id = $_POST['id'] ?? null;
                $title = trim($_POST['title'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                
                // Auto-generate slug if empty
                if (empty($slug) && !empty($title)) {
                    $slug = blog_create_slug($title);
                } else {
                    // Ensure slug is URL-friendly
                    $slug = blog_create_slug($slug);
                }
                
                $date = trim($_POST['date'] ?? date('Y-m-d'));
                $content = $_POST['content'] ?? '';
                $status = $_POST['status'] ?? 'draft';
                
                if ($title && $slug) {
                    if ($id) {
                        foreach ($posts as &$post) {
                            if ($post['id'] == $id) {
                                $post['title'] = $title;
                                $post['slug'] = $slug;
                                $post['date'] = $date;
                                $post['content'] = $content;
                                $post['status'] = $status;
                            }
                        }
                    } else {
                        $posts[] = [
                            'id' => time(),
                            'title' => $title,
                            'slug' => $slug,
                            'date' => $date,
                            'content' => $content,
                            'status' => $status
                        ];
                    }
                    blog_save_posts($posts);
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_post' && isset($_POST['id'])) {
                $posts = array_filter($posts, fn($p) => $p['id'] != $_POST['id']);
                blog_save_posts($posts);
            }
        }
        echo '<h2 class="text-2xl font-bold mb-6 fira-code">Blog Posts</h2>';
        echo '<a href="?action=blog&new=1" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">New Post</a><br><br>';
        if (isset($_GET['edit'])) {
            $edit = null;
            foreach ($posts as $p) if ($p['id'] == $_GET['edit']) $edit = $p;
            if (!$edit) {
                echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Post not found</div>';
                echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
            } else {
                echo '<form method="POST" class="space-y-4" id="blog-post-form">';
                echo '<input type="hidden" name="action" value="save_post">';
                echo '<input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '">';
                echo '<div><label>Title:</label><input name="title" value="' . htmlspecialchars($edit['title']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div><label>Slug:</label><input name="slug" value="' . htmlspecialchars($edit['slug']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div class="text-sm text-gray-500">The slug should be URL-friendly (lowercase, no spaces). Example: my-blog-post</div>';
                echo '<div><label>Date:</label><input name="date" value="' . htmlspecialchars($edit['date']) . '" class="border rounded px-2 py-1 w-full"></div>';
                echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published"' . ($edit['status'] === 'published' ? ' selected' : '') . '>Published</option><option value="draft"' . ($edit['status'] === 'draft' ? ' selected' : '') . '>Draft</option></select></div>';
                echo '<div><label>Content:</label></div>';
                echo '<div id="blog-toast-editor"></div>';
                echo '<input type="hidden" name="content" id="blog-editor-content">';
                echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
                echo '</form>';
                echo '<form method="POST" class="mt-4"><input type="hidden" name="action" value="delete_post"><input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '"><button type="submit" onclick="return confirm(\'Delete this post?\')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button></form>';
                echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
                
                // Toast UI Editor initialization
                echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var initialContent = ' . json_encode($edit['content']) . ';
                    var editor = new toastui.Editor({
                        el: document.querySelector("#blog-toast-editor"),
                        height: "500px",
                        initialEditType: "markdown",
                        previewStyle: "vertical",
                        initialValue: initialContent,
                        usageStatistics: false
                    });
                    
                    document.getElementById("blog-post-form").addEventListener("submit", function(e) {
                        document.getElementById("blog-editor-content").value = editor.getMarkdown();
                    });
                });
                </script>';
            }
        } elseif (isset($_GET['new'])) {
            echo '<form method="POST" class="space-y-4" id="blog-post-form">';
            echo '<input type="hidden" name="action" value="save_post">';
            echo '<div><label>Title:</label><input name="title" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Slug:</label><input name="slug" class="border rounded px-2 py-1 w-full" placeholder="auto-generated-if-empty"></div>';
            echo '<div class="text-sm text-gray-500">The slug should be URL-friendly (lowercase, no spaces). Example: my-blog-post</div>';
            echo '<div><label>Date:</label><input name="date" value="' . date('Y-m-d') . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published">Published</option><option value="draft">Draft</option></select></div>';
            echo '<div><label>Content:</label></div>';
            echo '<div id="blog-toast-editor"></div>';
            echo '<input type="hidden" name="content" id="blog-editor-content">';
            echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
            echo '</form>';
            echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
            
            // Toast UI Editor initialization for new post
            echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var editor = new toastui.Editor({
                    el: document.querySelector("#blog-toast-editor"),
                    height: "500px",
                    initialEditType: "markdown",
                    previewStyle: "vertical",
                    initialValue: "# New Blog Post\n\nStart writing your content here...",
                    usageStatistics: false
                });
                
                document.getElementById("blog-post-form").addEventListener("submit", function(e) {
                    document.getElementById("blog-editor-content").value = editor.getMarkdown();
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
                    <a href="/blog/' . urlencode($p['slug']) . '" target="_blank" class="text-green-600 hover:underline">View</a>
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
    if (preg_match('#^blog(?:/([^/]+))?$#', $path, $m)) {
        $posts = blog_load_posts();
        
        if (!empty($m[1])) {
            $slug = urldecode($m[1]);
            
            foreach ($posts as $post) {
                if ($post['slug'] === $slug && $post['status'] === 'published') {
                    $title = $post['title'];
                    if (!class_exists('Parsedown')) require_once PROJECT_ROOT . '/includes/Parsedown.php';
                    $Parsedown = new Parsedown();
                    $content = $Parsedown->text($post['content']);
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
            $content .= '<h1 class="text-3xl font-bold mb-8">Blog Posts</h1>';
            $content .= '<div class="space-y-8">';
            foreach ($published as $post) {
                $content .= '<article class="border-b pb-8">';
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
});
