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
            echo '<form method="POST" class="space-y-4">';
            echo '<input type="hidden" name="action" value="save_post">';
            echo '<input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '">';
            echo '<div><label>Title:</label><input name="title" value="' . htmlspecialchars($edit['title']) . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Slug:</label><input name="slug" value="' . htmlspecialchars($edit['slug']) . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Date:</label><input name="date" value="' . htmlspecialchars($edit['date']) . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published"' . ($edit['status'] === 'published' ? ' selected' : '') . '>Published</option><option value="draft"' . ($edit['status'] === 'draft' ? ' selected' : '') . '>Draft</option></select></div>';
            echo '<div><label>Content:</label><textarea name="content" rows="10" class="border rounded px-2 py-1 w-full">' . htmlspecialchars($edit['content']) . '</textarea></div>';
            echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
            echo '</form>';
            echo '<form method="POST" class="mt-4"><input type="hidden" name="action" value="delete_post"><input type="hidden" name="id" value="' . htmlspecialchars($edit['id']) . '"><button type="submit" onclick="return confirm(\'Delete this post?\')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button></form>';
            echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
        } elseif (isset($_GET['new'])) {
            echo '<form method="POST" class="space-y-4">';
            echo '<input type="hidden" name="action" value="save_post">';
            echo '<div><label>Title:</label><input name="title" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Slug:</label><input name="slug" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Date:</label><input name="date" value="' . date('Y-m-d') . '" class="border rounded px-2 py-1 w-full"></div>';
            echo '<div><label>Status:</label><select name="status" class="border rounded px-2 py-1 w-full"><option value="published">Published</option><option value="draft">Draft</option></select></div>';
            echo '<div><label>Content:</label><textarea name="content" rows="10" class="border rounded px-2 py-1 w-full"></textarea></div>';
            echo '<button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>';
            echo '</form>';
            echo '<a href="?action=blog" class="inline-block mt-4 text-blue-600 hover:underline">Back to list</a>';
        } else {
            echo '<table class="w-full border-collapse"><tr><th class="border-b py-2">Title</th><th class="border-b py-2">Slug</th><th class="border-b py-2">Date</th><th class="border-b py-2">Status</th><th class="border-b py-2">Actions</th></tr>';
            foreach ($posts as $p) {
                echo '<tr>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['title']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['slug']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['date']) . '</td>';
                echo '<td class="py-2 border-b">' . htmlspecialchars($p['status']) . '</td>';
                echo '<td class="py-2 border-b"><a href="?action=blog&edit=' . $p['id'] . '" class="text-blue-600 hover:underline">Edit</a></td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        return ob_get_clean();
    }
]);

// Public route: /blog and /blog/{slug}
fcms_add_hook('route', function (&$handled, &$title, &$content, $pageSlug) {
    if (preg_match('#^blog(?:/([a-zA-Z0-9_-]+))?$#', $pageSlug, $m)) {
        $posts = blog_load_posts();
        if (!empty($m[1])) {
            foreach ($posts as $post) {
                if ($post['slug'] === $m[1] && $post['status'] === 'published') {
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
            $content = "<h2>Blog Posts</h2><ul>";
            foreach ($published as $post) {
                $content .= '<li><a href="/blog/' . htmlspecialchars($post['slug']) . '">' . htmlspecialchars($post['title']) . '</a> <span style="color:#888;font-size:smaller;">' . htmlspecialchars($post['date']) . '</span></li>';
            }
            $content .= "</ul>";
            $handled = true;
        }
    }
});
