<?php
// Include accessibility utilities
require_once PROJECT_ROOT . '/includes/accessibility.php';

// Get current accessibility preferences
$preferences = fcms_get_accessibility_preferences();
?>

<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6 text-gray-900">Accessibility Settings</h2>
    
    <div class="mb-8">
        <p class="text-gray-600 mb-4">
            Customize your experience to make FearlessCMS more accessible for your needs. 
            These settings will be saved in your browser and applied to all pages.
        </p>
    </div>

    <form method="POST" action="" id="accessibility-form">
        <input type="hidden" name="action" value="save_accessibility_settings" />
        
        <fieldset class="mb-8">
            <legend class="text-lg font-semibold text-gray-900 mb-4">Visual Preferences</legend>
            
            <div class="space-y-4">
                <!-- High Contrast Mode -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="high-contrast" class="text-sm font-medium text-gray-700">
                            High Contrast Mode
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Increases contrast between text and background for better readability
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="high-contrast" 
                            name="high_contrast" 
                            value="1"
                            <?php echo isset($preferences['high_contrast']) && $preferences['high_contrast'] ? 'checked' : ''; ?>
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="high-contrast-help"
                        >
                        <div id="high-contrast-help" class="sr-only">Toggle high contrast mode on or off</div>
                    </div>
                </div>

                <!-- Large Text Mode -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="large-text" class="text-sm font-medium text-gray-700">
                            Large Text Mode
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Increases font size throughout the interface for better readability
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="large-text" 
                            name="large_text" 
                            value="1"
                            <?php echo isset($preferences['large_text']) && $preferences['large_text'] ? 'checked' : ''; ?>
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="large-text-help"
                        >
                        <div id="large-text-help" class="sr-only">Toggle large text mode on or off</div>
                    </div>
                </div>

                <!-- Reduced Motion -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="reduced-motion" class="text-sm font-medium text-gray-700">
                            Reduced Motion
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Reduces or eliminates animations and transitions that may cause discomfort
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="reduced-motion" 
                            name="reduced_motion" 
                            value="1"
                            <?php echo isset($preferences['reduced_motion']) && $preferences['reduced_motion'] ? 'checked' : ''; ?>
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="reduced-motion-help"
                        >
                        <div id="reduced-motion-help" class="sr-only">Toggle reduced motion mode on or off</div>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-8">
            <legend class="text-lg font-semibold text-gray-900 mb-4">Navigation Preferences</legend>
            
            <div class="space-y-4">
                <!-- Skip Links -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="skip-links" class="text-sm font-medium text-gray-700">
                            Enhanced Skip Links
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Always show skip navigation links for keyboard users
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="skip-links" 
                            name="skip_links" 
                            value="1"
                            checked
                            disabled
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="skip-links-help"
                        >
                        <div id="skip-links-help" class="sr-only">Skip links are always enabled for accessibility</div>
                    </div>
                </div>

                <!-- Focus Indicators -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="focus-indicators" class="text-sm font-medium text-gray-700">
                            Enhanced Focus Indicators
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Always show clear focus indicators for keyboard navigation
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="focus-indicators" 
                            name="focus_indicators" 
                            value="1"
                            checked
                            disabled
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="focus-indicators-help"
                        >
                        <div id="focus-indicators-help" class="sr-only">Focus indicators are always enabled for accessibility</div>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-8">
            <legend class="text-lg font-semibold text-gray-900 mb-4">Screen Reader Support</legend>
            
            <div class="space-y-4">
                <!-- Live Regions -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="live-regions" class="text-sm font-medium text-gray-700">
                            Enhanced Screen Reader Announcements
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Provides additional context and status updates for screen reader users
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="live-regions" 
                            name="live_regions" 
                            value="1"
                            checked
                            disabled
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="live-regions-help"
                        >
                        <div id="live-regions-help" class="sr-only">Screen reader announcements are always enabled for accessibility</div>
                    </div>
                </div>

                <!-- ARIA Labels -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <label for="aria-labels" class="text-sm font-medium text-gray-700">
                            Enhanced ARIA Labels
                        </label>
                        <p class="text-sm text-gray-500 mt-1">
                            Provides additional descriptive information for screen readers
                        </p>
                    </div>
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="aria-labels" 
                            name="aria_labels" 
                            value="1"
                            checked
                            disabled
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            aria-describedby="aria-labels-help"
                        >
                        <div id="aria-labels-help" class="sr-only">ARIA labels are always enabled for accessibility</div>
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <span id="save-status" aria-live="polite"></span>
            </div>
            <div class="flex space-x-3">
                <button 
                    type="submit" 
                    class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                    aria-describedby="save-help"
                >
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Settings
                    </span>
                </button>
                <button 
                    type="button" 
                    onclick="resetAccessibilitySettings()"
                    class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded border border-gray-300 hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                    aria-describedby="reset-help"
                >
                    Reset to Defaults
                </button>
            </div>
        </div>
        
        <div id="save-help" class="sr-only">Click to save your accessibility settings</div>
        <div id="reset-help" class="sr-only">Click to reset all accessibility settings to default values</div>
    </form>

    <!-- Accessibility Information -->
    <div class="mt-12 p-6 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Accessibility Features</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
                <h4 class="font-medium mb-2">Keyboard Navigation</h4>
                <ul class="space-y-1">
                    <li>• Tab to navigate between elements</li>
                    <li>• Enter/Space to activate buttons</li>
                    <li>• Escape to close modals</li>
                    <li>• Ctrl+S to save content</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-2">Screen Reader Support</h4>
                <ul class="space-y-1">
                    <li>• ARIA labels and descriptions</li>
                    <li>• Live region announcements</li>
                    <li>• Semantic HTML structure</li>
                    <li>• Focus management</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('accessibility-form');
    const saveStatus = document.getElementById('save-status');
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const preferences = {};
        
        // Collect checkbox values
        ['high_contrast', 'large_text', 'reduced_motion'].forEach(key => {
            preferences[key] = formData.get(key) === '1';
        });
        
        // Save preferences to cookies
        Object.entries(preferences).forEach(([key, value]) => {
            if (value) {
                document.cookie = `fcms-${key}=true; path=/; max-age=31536000`; // 1 year
            } else {
                document.cookie = `fcms-${key}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
            }
        });
        
        // Apply preferences immediately
        applyAccessibilityPreferences(preferences);
        
        // Show success message
        announceSaveStatus('Accessibility settings saved successfully');
        
        // Announce to screen reader if available
        if (typeof announceToScreenReader === 'function') {
            announceToScreenReader('Accessibility settings saved and applied');
        }
    });
    
    // Apply preferences when checkboxes change
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const preferences = {
                high_contrast: document.getElementById('high-contrast').checked,
                large_text: document.getElementById('large-text').checked,
                reduced_motion: document.getElementById('reduced-motion').checked
            };
            
            applyAccessibilityPreferences(preferences);
        });
    });
    
    // Apply current preferences on page load
    const currentPreferences = {
        high_contrast: document.getElementById('high-contrast').checked,
        large_text: document.getElementById('large-text').checked,
        reduced_motion: document.getElementById('reduced-motion').checked
    };
    
    applyAccessibilityPreferences(currentPreferences);
    
    // Announce page load to screen readers
    if (typeof announceToScreenReader === 'function') {
        announceToScreenReader('Accessibility settings page loaded. Use Tab to navigate between options.');
    }
});

// Apply accessibility preferences
function applyAccessibilityPreferences(preferences) {
    let css = '';
    
    if (preferences.high_contrast) {
        css += 'body { --color-text: #000000 !important; --color-bg: #ffffff !important; --color-border: #000000 !important; }';
        css += 'button:focus, input:focus, select:focus, textarea:focus, a:focus { outline: 3px solid #000000 !important; }';
    }
    
    if (preferences.large_text) {
        css += 'body { font-size: 18px !important; } h1, h2, h3, h4, h5, h6 { font-size: 1.2em !important; }';
    }
    
    if (preferences.reduced_motion) {
        css += '* { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }';
    }
    
    // Apply CSS
    let styleElement = document.getElementById('accessibility-preferences');
    if (!styleElement) {
        styleElement = document.createElement('style');
        styleElement.id = 'accessibility-preferences';
        document.head.appendChild(styleElement);
    }
    styleElement.textContent = css;
}

// Reset accessibility settings
function resetAccessibilitySettings() {
    // Uncheck all checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Clear cookies
    ['high_contrast', 'large_text', 'reduced_motion'].forEach(key => {
        document.cookie = `fcms-${key}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
    });
    
    // Apply default preferences
    applyAccessibilityPreferences({
        high_contrast: false,
        large_text: false,
        reduced_motion: false
    });
    
    // Show reset message
    announceSaveStatus('Accessibility settings reset to defaults');
    
    // Announce to screen reader if available
    if (typeof announceToScreenReader === 'function') {
        announceToScreenReader('Accessibility settings reset to default values');
    }
}

// Announce status to screen readers
function announceSaveStatus(message) {
    const saveStatus = document.getElementById('save-status');
    if (saveStatus) {
        saveStatus.textContent = message;
        
        // Clear message after 3 seconds
        setTimeout(() => {
            saveStatus.textContent = '';
        }, 3000);
    }
}
</script> 