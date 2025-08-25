<?php
// Session debugging removed for security
require_once dirname(dirname(__DIR__)) . '/version.php';

// Get CMS mode manager instance
global $cmsModeManager;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control - <?php echo htmlspecialchars($pageTitle ?? ''); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code&display=swap" rel="stylesheet">
    <!-- Quill.js Editor (replaces Toast UI) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        .fira-code { font-family: 'Fira Code', monospace; }
        
        /* Improved submenu navigation */
        .submenu {
            margin-top: 0 !important;
            border-top: 2px solid transparent;
        }
        
        /* Ensure no gap between parent and submenu */
        .relative.group {
            padding-bottom: 2px;
        }
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
                <?php 
                $admin_sections = fcms_get_admin_sections();
                foreach ($admin_sections as $id => $section) {
                    $hasChildren = isset($section['children']) && !empty($section['children']);
                    
                    if ($hasChildren) {
                        echo '<div class="relative group" onmouseenter="showSubmenu(this)" onmouseleave="hideSubmenu(this)">';
                        echo '<a href="' . BASE_URL . '?action=' . htmlspecialchars($id) . '" class="hover:text-green-200">' . htmlspecialchars($section['label']) . '</a>';
                        echo '<div class="absolute hidden bg-green-700 text-white shadow-lg rounded-md mt-0 py-2 w-48 z-10 submenu">';
                        foreach ($section['children'] as $child_id => $child_section) {
                            echo '<a href="' . BASE_URL . '?action=' . htmlspecialchars($child_id) . '" class="block px-4 py-2 hover:bg-green-800">' . htmlspecialchars($child_section['label']) . '</a>';
                        }
                        echo '</div>';
                        echo '</div>';
                    } else if (!isset($section['parent'])) { // Only display top-level items that are not children of other sections
                        echo '<a href="' . BASE_URL . '?action=' . htmlspecialchars($id) . '" class="hover:text-green-200">' . htmlspecialchars($section['label']) . '</a>';
                    }
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
            <?php
            if (isset($templateFile) && $templateFile && file_exists($templateFile)) {
                include $templateFile;
            } else {
                echo $content ?? '';
            }
            ?>
        </div>
    </div>

    <script>
    // Submenu functions for better dropdown navigation
    function showSubmenu(element) {
        const submenu = element.querySelector('.submenu');
        if (submenu) {
            submenu.classList.remove('hidden');
        }
    }
    
    function hideSubmenu(element) {
        const submenu = element.querySelector('.submenu');
        if (submenu) {
            // Add a small delay to allow moving mouse to submenu
            setTimeout(() => {
                if (!element.matches(':hover') && !submenu.matches(':hover')) {
                    submenu.classList.add('hidden');
                }
            }, 100);
        }
    }
    
    // Add mouse events to submenus to keep them visible
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.submenu').forEach(submenu => {
            submenu.addEventListener('mouseenter', function() {
                this.classList.remove('hidden');
            });
            
            submenu.addEventListener('mouseleave', function() {
                this.classList.add('hidden');
            });
        });
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        // Handle AJAX form submissions (but skip delete forms)
        document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Skip AJAX for delete forms - let them submit normally
                const action = new FormData(this).get('action');
                if (action && ['delete_content', 'delete_page'].includes(action)) {
                    return; // Don't prevent default, let form submit normally
                }
                
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

        // TEMPORARILY DISABLED: Add data-ajax attributes to all forms and links EXCEPT forms marked with data-no-ajax
        // document.querySelectorAll('form').forEach(form => {
        //     if (!form.hasAttribute('data-ajax') && !form.hasAttribute('data-no-ajax')) {
        //         form.setAttribute('data-ajax', 'true');
        //     }
        // });

        document.querySelectorAll('a').forEach(link => {
            if (link.getAttribute('href')?.startsWith('?action=')) {
                link.setAttribute('data-ajax', 'true');
            }
        });
    });
    </script>
    
    <!-- Version Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white text-sm py-1 px-4 text-center flex justify-between items-center">
        <span>FearlessCMS v<?php echo APP_VERSION; ?></span>
        <a href="https://ko-fi.com/fearlessgeekmedia" target="_blank" class="text-blue-300 hover:text-blue-100">Support FearlessCMS on Ko-fi!</a>
    </div>
</body>
</html>