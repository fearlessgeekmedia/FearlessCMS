<?php
// Session debugging removed for security
require_once dirname(dirname(__DIR__)) . '/version.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Control - <?php echo htmlspecialchars($pageTitle ?? ''); ?></title>
    <link href="/public/css/output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code&display=swap" rel="stylesheet">
    <!-- Toast UI Editor -->
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
    <style>
        .fira-code { font-family: 'Fira Code', monospace; }
        
        /* Skip links for accessibility */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1000;
            font-weight: 500;
            transition: top 0.2s ease;
        }
        
        .skip-link:focus {
            top: 6px;
            outline: 3px solid #2563eb;
            outline-offset: 2px;
        }
        
        /* Enhanced focus indicators */
        button:focus,
        input:focus,
        select:focus,
        textarea:focus,
        a:focus {
            outline: 3px solid #2563eb;
            outline-offset: 2px;
            border-color: #2563eb;
        }
        
        /* Screen reader only class */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Improved color contrast for better accessibility */
        .text-gray-300 {
            color: #4b5563 !important; /* Darker gray for better contrast */
        }
        
        .hover\:text-white:hover {
            color: #ffffff !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Skip links for accessibility -->
    <a href="#main-navigation" class="skip-link">Skip to navigation</a>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav id="main-navigation" class="bg-green-600 text-white p-4" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-bold fira-code"><a href="<?php echo BASE_URL; ?>?action=dashboard" aria-label="Go to Mission Control dashboard">Mission Control</a></h1>
                <span class="text-sm">Welcome, <span aria-label="Current user"><?php echo htmlspecialchars($username ?? ''); ?></span></span>
                <a href="/" target="_blank" aria-label="View your website (opens in new tab)">Your site</a>
            </div>
            <div class="flex items-center space-x-4" role="menubar">
                <a href="<?php echo BASE_URL; ?>?action=manage_users" class="hover:text-green-200" role="menuitem">Users</a>
                <a href="<?php echo BASE_URL; ?>?action=files" class="hover:text-green-200" role="menuitem">Files</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_themes" class="hover:text-green-200" role="menuitem">Themes</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_menus" class="hover:text-green-200" role="menuitem">Menus</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_widgets" class="hover:text-green-200" role="menuitem">Widgets</a>
                <a href="<?php echo BASE_URL; ?>?action=manage_plugins" class="hover:text-green-200" role="menuitem">Plugins</a>
                <?php 
                // Add admin sections to navigation
                $admin_sections = fcms_get_admin_sections();
                foreach ($admin_sections as $id => $section) {
                    echo '<a href="' . BASE_URL . '?action=' . htmlspecialchars($id) . '" class="hover:text-green-200" role="menuitem">' . htmlspecialchars($section['label']) . '</a>';
                }
                if (!empty($plugin_nav_items)) echo $plugin_nav_items; 
                ?>
                <div class="flex items-center">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium" aria-label="Logout from system">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Status messages for screen readers -->
    <div id="status-messages" aria-live="polite" aria-atomic="true" class="sr-only"></div>

    <div class="max-w-7xl mx-auto px-4">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert" aria-live="assertive">
                <span class="sr-only">Error: </span><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert" aria-live="polite">
                <span class="sr-only">Success: </span><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
    </div>

    <main id="main-content" class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6 fira-code"><?php echo htmlspecialchars($pageTitle ?? ''); ?></h2>
            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <script>
    // Accessibility enhancement: Announce messages to screen readers
    function announceToScreenReader(message, type = 'polite') {
        const liveRegion = document.getElementById('status-messages');
        if (liveRegion) {
            liveRegion.setAttribute('aria-live', type);
            liveRegion.textContent = message;
            
            // Clear message after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    }
    
    // Enhanced focus management
    function trapFocus(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Announce page load to screen readers
        const pageTitle = document.querySelector('h2');
        if (pageTitle) {
            announceToScreenReader(`Loaded ${pageTitle.textContent} page`);
        }
        
        // Improve keyboard navigation
        const focusableElements = document.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        // Add keyboard shortcuts for common actions
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save (if save button exists)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const saveButton = document.querySelector('button[type="submit"]');
                if (saveButton && saveButton.textContent.toLowerCase().includes('save')) {
                    saveButton.click();
                    announceToScreenReader('Saving content...');
                }
            }
            
            // Escape key to close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('[role="dialog"]');
                modals.forEach(modal => {
                    if (modal.classList.contains('flex') || !modal.classList.contains('hidden')) {
                        const closeButton = modal.querySelector('button[onclick*="close"], button[onclick*="cancel"]');
                        if (closeButton) {
                            closeButton.click();
                            announceToScreenReader('Modal closed');
                        }
                    }
                });
            }
        });
        
        // Handle AJAX form submissions
        document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                // Announce form submission to screen readers
                announceToScreenReader('Submitting form...');
                
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
                        announceToScreenReader('Content updated successfully');
                    }
                    
                    // Update URL without reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('action', formData.get('action'));
                    window.history.pushState({}, '', url);
                })
                .catch(error => {
                    console.error('Error:', error);
                    announceToScreenReader('Error occurred while submitting form', 'assertive');
                });
            });
        });
        
        // Enhance modal accessibility
        document.querySelectorAll('[role="dialog"]').forEach(modal => {
            trapFocus(modal);
        });
    });
    </script>
    
    <!-- Version Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-gray-800 text-white text-sm py-1 px-4 text-center">
        FearlessCMS v<?php echo APP_VERSION; ?>
    </div>
</body>
</html>

