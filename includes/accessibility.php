<?php
/**
 * Accessibility Utilities for FearlessCMS
 * 
 * This file provides helper functions and utilities to improve accessibility
 * across the CMS, including ARIA attributes, focus management, and screen reader support.
 */

/**
 * Generate proper ARIA labels for form fields
 */
function fcms_generate_aria_label($field_name, $required = false, $help_text = '') {
    $label = ucfirst(str_replace('_', ' ', $field_name));
    $aria_attrs = [
        'aria-label' => $label,
        'aria-required' => $required ? 'true' : 'false'
    ];
    
    if ($help_text) {
        $help_id = 'help-' . $field_name;
        $aria_attrs['aria-describedby'] = $help_id;
    }
    
    return $aria_attrs;
}

/**
 * Generate accessible form field HTML
 */
function fcms_form_field($type, $name, $label, $value = '', $required = false, $help_text = '', $options = []) {
    $field_id = 'field-' . $name;
    $help_id = 'help-' . $name;
    
    $html = '<div class="mb-4">';
    
    // Label
    $required_mark = $required ? ' <span class="text-red-500" aria-label="required">*</span>' : '';
    $html .= '<label for="' . $field_id . '" class="block mb-2 text-sm font-medium text-gray-700">';
    $html .= htmlspecialchars($label) . $required_mark;
    $html .= '</label>';
    
    // Input field
    $html .= '<' . $type;
    $html .= ' id="' . $field_id . '"';
    $html .= ' name="' . htmlspecialchars($name) . '"';
    $html .= ' value="' . htmlspecialchars($value) . '"';
    
    if ($required) {
        $html .= ' required aria-required="true"';
    }
    
    if ($help_text) {
        $html .= ' aria-describedby="' . $help_id . '"';
    }
    
    // Add additional attributes
    foreach ($options as $attr => $val) {
        $html .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
    }
    
    $html .= ' class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">';
    
    // Close tag for self-closing elements
    if ($type === 'input') {
        $html .= ' />';
    } else {
        $html .= '</' . $type . '>';
    }
    
    // Help text
    if ($help_text) {
        $html .= '<div id="' . $help_id . '" class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($help_text);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate accessible select field
 */
function fcms_select_field($name, $label, $options, $selected = '', $required = false, $help_text = '', $attributes = []) {
    $field_id = 'field-' . $name;
    $help_id = 'help-' . $name;
    
    $html = '<div class="mb-4">';
    
    // Label
    $required_mark = $required ? ' <span class="text-red-500" aria-label="required">*</span>' : '';
    $html .= '<label for="' . $field_id . '" class="block mb-2 text-sm font-medium text-gray-700">';
    $html .= htmlspecialchars($label) . $required_mark;
    $html .= '</label>';
    
    // Select field
    $html .= '<select id="' . $field_id . '" name="' . htmlspecialchars($name) . '"';
    
    if ($required) {
        $html .= ' required aria-required="true"';
    }
    
    if ($help_text) {
        $html .= ' aria-describedby="' . $help_id . '"';
    }
    
    // Add additional attributes
    foreach ($attributes as $attr => $val) {
        $html .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
    }
    
    $html .= ' class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">';
    
    // Options
    foreach ($options as $value => $label) {
        $html .= '<option value="' . htmlspecialchars($value) . '"';
        if ($value == $selected) {
            $html .= ' selected';
        }
        $html .= '>' . htmlspecialchars($label) . '</option>';
    }
    
    $html .= '</select>';
    
    // Help text
    if ($help_text) {
        $html .= '<div id="' . $help_id . '" class="mt-1 text-sm text-gray-500">';
        $html .= htmlspecialchars($help_text);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate accessible button
 */
function fcms_button($type, $text, $attributes = [], $icon = '') {
    $html = '<button type="' . $type . '"';
    
    // Add default classes
    $default_classes = 'px-4 py-2 rounded font-medium transition-colors focus:ring-2 focus:ring-offset-2';
    
    if ($type === 'submit') {
        $default_classes .= ' bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-500';
    } elseif ($type === 'button') {
        $default_classes .= ' bg-gray-500 text-white hover:bg-gray-600 focus:ring-gray-500';
    }
    
    $classes = isset($attributes['class']) ? $default_classes . ' ' . $attributes['class'] : $default_classes;
    $attributes['class'] = $classes;
    
    // Add additional attributes
    foreach ($attributes as $attr => $val) {
        if ($attr !== 'class') {
            $html .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
        }
    }
    
    $html .= ' class="' . $classes . '">';
    
    if ($icon) {
        $html .= '<span class="flex items-center">';
        $html .= $icon;
        $html .= '<span class="ml-2">' . htmlspecialchars($text) . '</span>';
        $html .= '</span>';
    } else {
        $html .= htmlspecialchars($text);
    }
    
    $html .= '</button>';
    
    return $html;
}

/**
 * Generate accessible modal HTML
 */
function fcms_modal($id, $title, $content, $actions = [], $attributes = []) {
    $html = '<div id="' . $id . '" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50"';
    $html .= ' role="dialog" aria-modal="true" aria-labelledby="' . $id . '-title"';
    
    if (isset($attributes['aria-describedby'])) {
        $html .= ' aria-describedby="' . $attributes['aria-describedby'] . '"';
    }
    
    $html .= '>';
    
    // Backdrop
    $html .= '<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>';
    
    // Modal content
    $html .= '<div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full mx-4 relative">';
    
    // Title
    $html .= '<h3 id="' . $id . '-title" class="text-lg font-medium mb-4">' . htmlspecialchars($title) . '</h3>';
    
    // Content
    $html .= '<div class="mb-4">' . $content . '</div>';
    
    // Actions
    if (!empty($actions)) {
        $html .= '<div class="flex justify-end gap-4">';
        foreach ($actions as $action) {
            $html .= $action;
        }
        $html .= '</div>';
    }
    
    $html .= '</div>'; // Modal content
    $html .= '</div>'; // Modal container
    
    return $html;
}

/**
 * Generate skip links HTML
 */
function fcms_skip_links($links = []) {
    if (empty($links)) {
        $links = [
            'main-navigation' => 'Skip to navigation',
            'main-content' => 'Skip to main content'
        ];
    }
    
    $html = '';
    foreach ($links as $target => $text) {
        $html .= '<a href="#' . $target . '" class="skip-link">' . htmlspecialchars($text) . '</a>';
    }
    
    return $html;
}

/**
 * Generate accessible breadcrumbs
 */
function fcms_breadcrumbs($items, $separator = '>') {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb" aria-label="Breadcrumb">';
    
    foreach ($items as $index => $item) {
        if ($index > 0) {
            $html .= ' <span class="separator" aria-hidden="true">' . htmlspecialchars($separator) . '</span> ';
        }
        
        if (isset($item['url']) && $index < count($items) - 1) {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="text-blue-600 hover:text-blue-800">';
            $html .= htmlspecialchars($item['title']);
            $html .= '</a>';
        } else {
            $html .= '<span class="current-page text-gray-600" aria-current="page">';
            $html .= htmlspecialchars($item['title']);
            $html .= '</span>';
        }
    }
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate accessible alert/notification
 */
function fcms_alert($message, $type = 'info', $dismissible = false) {
    $type_classes = [
        'info' => 'bg-blue-100 border-blue-400 text-blue-700',
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
        'error' => 'bg-red-100 border-red-400 text-red-700'
    ];
    
    $aria_live = $type === 'error' ? 'assertive' : 'polite';
    
    $html = '<div class="border rounded px-4 py-3 relative ' . $type_classes[$type] . '"';
    $html .= ' role="alert" aria-live="' . $aria_live . '">';
    
    if ($dismissible) {
        $html .= '<button type="button" class="absolute top-0 right-0 p-2" onclick="this.parentElement.remove()" aria-label="Dismiss alert">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
        $html .= '</svg>';
        $html .= '</button>';
    }
    
    $html .= htmlspecialchars($message);
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate accessible table
 */
function fcms_table($headers, $rows, $caption = '', $attributes = []) {
    $html = '<div class="overflow-x-auto">';
    $html .= '<table class="min-w-full divide-y divide-gray-200"';
    
    // Add additional attributes
    foreach ($attributes as $attr => $val) {
        $html .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
    }
    
    $html .= '>';
    
    if ($caption) {
        $html .= '<caption class="sr-only">' . htmlspecialchars($caption) . '</caption>';
    }
    
    // Headers
    if (!empty($headers)) {
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">';
            $html .= htmlspecialchars($header);
            $html .= '</th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
    }
    
    // Rows
    if (!empty($rows)) {
        $html .= '<tbody class="bg-white divide-y divide-gray-200">';
        foreach ($rows as $row_index => $row) {
            $html .= '<tr>';
            foreach ($row as $cell_index => $cell) {
                if ($cell_index === 0) {
                    $html .= '<th scope="row" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">';
                } else {
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
                }
                $html .= htmlspecialchars($cell);
                $html .= $cell_index === 0 ? '</th>' : '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
    }
    
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate accessible pagination
 */
function fcms_pagination($current_page, $total_pages, $base_url, $attributes = []) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="flex items-center justify-between" aria-label="Pagination">';
    
    // Previous button
    $prev_disabled = $current_page <= 1;
    $prev_url = $prev_disabled ? '#' : $base_url . '?page=' . ($current_page - 1);
    
    $html .= '<a href="' . $prev_url . '"';
    if ($prev_disabled) {
        $html .= ' aria-disabled="true" tabindex="-1" class="opacity-50 cursor-not-allowed"';
    } else {
        $html .= ' class="text-blue-600 hover:text-blue-800"';
    }
    $html .= ' aria-label="Go to previous page">';
    $html .= '&larr; Previous';
    $html .= '</a>';
    
    // Page numbers
    $html .= '<div class="flex space-x-2" role="group" aria-label="Page navigation">';
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="px-3 py-2 bg-blue-500 text-white rounded" aria-current="page">';
            $html .= $i;
            $html .= '</span>';
        } else {
            $html .= '<a href="' . $base_url . '?page=' . $i . '" class="px-3 py-2 text-blue-600 hover:text-blue-800 rounded">';
            $html .= $i;
            $html .= '</a>';
        }
    }
    
    $html .= '</div>';
    
    // Next button
    $next_disabled = $current_page >= $total_pages;
    $next_url = $next_disabled ? '#' : $base_url . '?page=' . ($current_page + 1);
    
    $html .= '<a href="' . $next_url . '"';
    if ($next_disabled) {
        $html .= ' aria-disabled="true" tabindex="-1" class="opacity-50 cursor-not-allowed"';
    } else {
        $html .= ' class="text-blue-600 hover:text-blue-800"';
    }
    $html .= ' aria-label="Go to next page">';
    $html .= 'Next &rarr;';
    $html .= '</a>';
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate accessibility meta tags
 */
function fcms_accessibility_meta_tags() {
    $html = '';
    
    // Color scheme support
    $html .= '<meta name="color-scheme" content="light dark">';
    
    // Viewport for mobile accessibility
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    
    // Theme color for mobile browsers
    $html .= '<meta name="theme-color" content="#6c63ff">';
    
    // MS theme color for IE
    $html .= '<meta name="msapplication-TileColor" content="#6c63ff">';
    
    return $html;
}

/**
 * Generate accessibility CSS variables
 */
function fcms_accessibility_css_variables() {
    $css = ':root {';
    $css .= '--color-focus: #2563eb;';
    $css .= '--color-focus-offset: 2px;';
    $css .= '--color-focus-ring: 3px;';
    $css .= '--color-skip-link-bg: #000000;';
    $css .= '--color-skip-link-text: #ffffff;';
    $css .= '--color-high-contrast-text: #000000;';
    $css .= '--color-high-contrast-bg: #ffffff;';
    $css .= '}';
    
    return $css;
}

/**
 * Generate accessibility JavaScript utilities
 */
function fcms_accessibility_js_utilities() {
    $js = '<script>';
    $js .= '// Accessibility utilities for FearlessCMS';
    $js .= 'window.FCMSAccessibility = {';
    
    // Announce to screen reader
    $js .= 'announce: function(message, type = "polite") {';
    $js .= 'const liveRegion = document.getElementById("fcms-live-region") || this.createLiveRegion();';
    $js .= 'liveRegion.setAttribute("aria-live", type);';
    $js .= 'liveRegion.textContent = message;';
    $js .= 'setTimeout(() => liveRegion.textContent = "", 1000);';
    $js .= '},';
    
    // Create live region
    $js .= 'createLiveRegion: function() {';
    $js .= 'const liveRegion = document.createElement("div");';
    $js .= 'liveRegion.id = "fcms-live-region";';
    $js .= 'liveRegion.setAttribute("aria-live", "polite");';
    $js .= 'liveRegion.setAttribute("aria-atomic", "true");';
    $js .= 'liveRegion.className = "sr-only";';
    $js .= 'document.body.appendChild(liveRegion);';
    $js .= 'return liveRegion;';
    $js .= '},';
    
    // Trap focus in modal
    $js .= 'trapFocus: function(modal) {';
    $js .= 'const focusableElements = modal.querySelectorAll("button, [href], input, select, textarea, [tabindex]:not([tabindex=\\"-1\\"])");';
    $js .= 'if (focusableElements.length === 0) return;';
    $js .= 'const firstElement = focusableElements[0];';
    $js .= 'const lastElement = focusableElements[focusableElements.length - 1];';
    $js .= 'modal.addEventListener("keydown", function(e) {';
    $js .= 'if (e.key === "Tab") {';
    $js .= 'if (e.shiftKey && document.activeElement === firstElement) {';
    $js .= 'e.preventDefault(); lastElement.focus();';
    $js .= '} else if (!e.shiftKey && document.activeElement === lastElement) {';
    $js .= 'e.preventDefault(); firstElement.focus();';
    $js .= '}';
    $js .= '}';
    $js .= '});';
    $js .= '}';
    
    $js .= '};';
    $js .= '</script>';
    
    return $js;
}

/**
 * Check if current user has accessibility preferences
 */
function fcms_get_accessibility_preferences() {
    $preferences = [];
    
    // Check for high contrast preference
    if (isset($_COOKIE['fcms-high-contrast']) && $_COOKIE['fcms-high-contrast'] === 'true') {
        $preferences['high_contrast'] = true;
    }
    
    // Check for reduced motion preference
    if (isset($_COOKIE['fcms-reduced-motion']) && $_COOKIE['fcms-reduced-motion'] === 'true') {
        $preferences['reduced_motion'] = true;
    }
    
    // Check for large text preference
    if (isset($_COOKIE['fcms-large-text']) && $_COOKIE['fcms-large-text'] === 'true') {
        $preferences['large_text'] = true;
    }
    
    return $preferences;
}

/**
 * Apply accessibility preferences to current page
 */
function fcms_apply_accessibility_preferences($preferences) {
    $css = '';
    
    if (isset($preferences['high_contrast']) && $preferences['high_contrast']) {
        $css .= 'body { --color-text: #000000; --color-bg: #ffffff; --color-border: #000000; }';
    }
    
    if (isset($preferences['large_text']) && $preferences['large_text']) {
        $css .= 'body { font-size: 18px; } h1, h2, h3, h4, h5, h6 { font-size: 1.2em; }';
    }
    
    if (isset($preferences['reduced_motion']) && $preferences['reduced_motion']) {
        $css .= '* { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }';
    }
    
    if ($css) {
        return '<style>' . $css . '</style>';
    }
    
    return '';
} 