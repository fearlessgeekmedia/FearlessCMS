<?php
// includes/plugins.php

// Make sure PROJECT_ROOT is defined
if (!defined('PROJECT_ROOT')) {
    throw new Exception("PROJECT_ROOT is not defined! Please define('PROJECT_ROOT', ...) in your entry script before including plugins.php.");
}

// Define all necessary constants
if (!defined('ADMIN_CONFIG_DIR')) {
    define('ADMIN_CONFIG_DIR', PROJECT_ROOT . '/admin/config');
}

// Define PLUGIN_DIR if not already defined
if (!defined('PLUGIN_DIR')) {
    define('PLUGIN_DIR', PROJECT_ROOT . '/plugins');
}

// Define PLUGIN_CONFIG if not already defined
if (!defined('PLUGIN_CONFIG')) {
    define('PLUGIN_CONFIG', ADMIN_CONFIG_DIR . '/plugins.json');
}

// --- Hook system ---
$GLOBALS['fcms_hooks'] = [
    'init' => [],
    'before_content' => [],
    'after_content' => [],
    'before_render' => [],
    'after_render' => [],
    'route' => [],
    'check_permission' => [],
    'filter_admin_sections' => [],
    'content' => [], // Add content filter
    // ...add more as needed
];

// Register a hook
function fcms_add_hook($hook, $callback) {
    if (!isset($GLOBALS['fcms_hooks'][$hook])) $GLOBALS['fcms_hooks'][$hook] = [];
    $GLOBALS['fcms_hooks'][$hook][] = $callback;
}

// Run all callbacks for a hook
function fcms_do_hook($hook, &...$args) {
    if (!empty($GLOBALS['fcms_hooks'][$hook])) {
        foreach ($GLOBALS['fcms_hooks'][$hook] as $cb) {
            call_user_func_array($cb, $args);
        }
    }
}

// Run all callbacks for a filter and return the final value
function fcms_apply_filter($hook, $value, ...$args) {
    if (!empty($GLOBALS['fcms_hooks'][$hook])) {
        foreach ($GLOBALS['fcms_hooks'][$hook] as $cb) {
            $value = call_user_func_array($cb, array_merge([$value], $args));
        }
    }
    return $value;
}

// Add a filter
function add_filter($hook, $callback) {
    fcms_add_hook($hook, $callback);
}

// Add permission check function
function fcms_check_plugin_permission($username, $capability) {
    $result = false;
    if (!empty($GLOBALS['fcms_hooks']['check_permission'])) {
        foreach ($GLOBALS['fcms_hooks']['check_permission'] as $callback) {
            $result = call_user_func($callback, $username, $capability, []);
            if ($result === true) {
                break;
            }
        }
    }
    return $result;
}

// Add permission hook registration function
function fcms_register_permission_hook($hook, $callback) {
    if (!isset($GLOBALS['fcms_hooks'][$hook])) {
        $GLOBALS['fcms_hooks'][$hook] = [];
    }
    $GLOBALS['fcms_hooks'][$hook][] = $callback;
}

// --- Admin section registration ---
$GLOBALS['fcms_admin_sections'] = [];

// Register the Plugins section
fcms_register_admin_section('plugins', [
    'label' => 'Plugins',
    'menu_order' => 45,
    'render_callback' => function() {
        include PROJECT_ROOT . '/admin/templates/plugins.php';
    }
]);

/**
 * Register an admin section.
 * @param string $id Unique section id (e.g. 'blog')
 * @param array $opts [
 *   'label' => 'Blog',
 *   'menu_order' => 50, // lower = earlier in menu
 *   'render_callback' => function() { ... },
 *   'parent' => 'plugins', // optional parent menu id
 * ]
 */
function fcms_register_admin_section($id, $opts) {
    error_log("Registering admin section: " . $id . " with options: " . print_r($opts, true));
    $GLOBALS['fcms_admin_sections'][$id] = $opts;
}

/**
 * Get all registered admin sections, sorted by menu_order.
 * @return array
 */
function fcms_get_admin_sections() {
    $sections = $GLOBALS['fcms_admin_sections'];
    
    error_log("Admin sections before sorting: " . print_r($sections, true));
    
    // First, sort by menu_order
    uasort($sections, function($a, $b) {
        return ($a['menu_order'] ?? 100) <=> ($b['menu_order'] ?? 100);
    });
    
    error_log("Admin sections after sorting: " . print_r($sections, true));
    
    // Then, organize into parent/child structure
    $organized = [];
    foreach ($sections as $id => $section) {
        if (isset($section['parent'])) {
            $parent_id = $section['parent'];
            if (!isset($organized[$parent_id])) {
                // Find the parent section
                $parent_section = null;
                foreach ($sections as $sid => $s) {
                    if ($sid === $parent_id) {
                        $parent_section = $s;
                        break;
                    }
                }
                if ($parent_section) {
                    $organized[$parent_id] = array_merge($parent_section, [
                        'id' => $parent_id,
                        'children' => []
                    ]);
                }
            }
            if (isset($organized[$parent_id])) {
                $organized[$parent_id]['children'][$id] = array_merge($section, ['id' => $id]);
            }
        } else {
            // Only add as a top-level section if it's not already added as a parent
            if (!isset($organized[$id])) {
                $organized[$id] = array_merge($section, ['id' => $id]);
            }
        }
    }
    
    error_log("Admin sections after organization: " . print_r($organized, true));
    
    return $organized;
}

// --- Plugin loader: only load active plugins ---
function fcms_load_plugins() {
    $pluginDir = PLUGIN_DIR;
    $configFile = PLUGIN_CONFIG;
    $active = [];

    if (file_exists($configFile)) {
        $active = json_decode(file_get_contents($configFile), true);
        if (!is_array($active)) $active = [];
    }

    if (!is_dir($pluginDir)) return;
    foreach (glob($pluginDir . '/*', GLOB_ONLYDIR) as $pluginFolder) {
        $pluginName = basename($pluginFolder);
        if (!in_array($pluginName, $active)) continue;
        $mainFile = $pluginFolder . '/' . $pluginName . '.php';
        if (file_exists($mainFile)) {
            include_once $mainFile;
        }
    }
}
fcms_load_plugins();
fcms_do_hook('init');
?>
