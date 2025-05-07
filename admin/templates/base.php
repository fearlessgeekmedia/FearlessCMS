<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control - <?php echo htmlspecialchars($pageTitle ?? ''); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code&display=swap" rel="stylesheet">
    <style>
        .fira-code { font-family: 'Fira Code', monospace; }
        <?php if (!empty($custom_css)) echo $custom_css; ?>
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold fira-code"><a href="admin">Mission Control</a></h1>
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($username ?? ''); ?></span>
                <a href="/" target="_blank">Your site</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="?action=manage_users" class="hover:text-green-200">Users</a>
                <a href="?action=files" class="hover:text-green-200">Files</a>
                <a href="?action=manage_themes" class="hover:text-green-200">Themes</a>
                <a href="?action=manage_menus" class="hover:text-green-200">Menus</a>
                <a href="?action=manage_widgets" class="hover:text-green-200">Widgets</a>
                <a href="?action=manage_plugins" class="hover:text-green-200">Plugins</a>
                <?php if (!empty($plugin_nav_items)) echo $plugin_nav_items; ?>
                <a href="?action=logout" class="hover:text-green-200">Logout</a>
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
</body>
</html>
