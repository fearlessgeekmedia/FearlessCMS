<?php
error_log("Base template - Current session: " . print_r($_SESSION, true));
require_once dirname(dirname(__DIR__)) . '/version.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control - <?php echo htmlspecialchars($pageTitle ?? ''); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code&display=swap" rel="stylesheet">
    <!-- Toast UI Editor -->
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
    <style>
        .fira-code { font-family: 'Fira Code', monospace; }
        <?php if (!empty($custom_css)) echo $custom_css; ?>
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold fira-code"><a href="<?php echo BASE_URL; ?>?action=dashboard">Mission Control</a></h1>
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($username ?? ''); ?></span>
                <a href="/" target="_blank">Your site</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="<?php echo BASE_URL; ?>?action=manage_users" class="hover:text-green-200">Users</a>
                <a href="<?php echo BASE_URL; ?>?action=files" class="hover:text-green-200">Files</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_themes" class="hover:text-green-200">Themes</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_menus" class="hover:text-green-200">Menus</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_widgets" class="hover:text-green-200">Widgets</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_plugins" class="hover:text-green-200">Plugins</a>
                <?php 
                // Add admin sections to navigation
                $admin_sections = fcms_get_admin_sections();
                foreach ($admin_sections as $id => $section) {
                    echo '<a href="' . BASE_URL . '?action=' . htmlspecialchars($id) . '" class="hover:text-green-200">' . htmlspecialchars($section['label']) . '</a>';
                }
                if (!empty($plugin_nav_items)) echo $plugin_nav_items; 
                ?>
                <div class="flex items-center">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6 fira-code"><?php echo htmlspecialchars($pageTitle ?? ''); ?></h2>
            <?php echo $content ?? ''; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle AJAX form submissions
        document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Update the content area
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('.bg-white.shadow.rounded-lg');
                    if (newContent) {
                        document.querySelector('.bg-white.shadow.rounded-lg').innerHTML = newContent.innerHTML;
                    }
                    
                    // Update URL without reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('action', formData.get('action'));
                    window.history.pushState({}, '', url);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        // Handle AJAX links
        document.querySelectorAll('a[data-ajax="true"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                
                fetch(this.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('.bg-white.shadow.rounded-lg');
                    if (newContent) {
                        document.querySelector('.bg-white.shadow.rounded-lg').innerHTML = newContent.innerHTML;
                    }
                    
                    // Update URL without reload
                    window.history.pushState({}, '', this.href);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        // Handle dynamic content loading
        function loadContent(action) {
            const url = new URL(window.location.href);
            url.searchParams.set('action', action);
            
            fetch(url.toString())
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('.bg-white.shadow.rounded-lg');
                if (newContent) {
                    document.querySelector('.bg-white.shadow.rounded-lg').innerHTML = newContent.innerHTML;
                }
                
                // Update URL without reload
                window.history.pushState({}, '', url.toString());
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Add data-ajax attributes to all forms and links
        document.querySelectorAll('form').forEach(form => {
            if (!form.hasAttribute('data-ajax')) {
                form.setAttribute('data-ajax', 'true');
            }
        });

        document.querySelectorAll('a').forEach(link => {
            if (link.getAttribute('href')?.startsWith('?action=')) {
                link.setAttribute('data-ajax', 'true');
            }
        });
    });
    </script>

    <?php if (!empty($custom_js)) echo $custom_js; ?>
    
    <!-- Version Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white text-sm py-1 px-4 text-center">
        FearlessCMS v<?php echo APP_VERSION; ?>
    </div>
</body>
</html>
