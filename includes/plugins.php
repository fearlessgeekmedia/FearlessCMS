<?php
define('PLUGIN_DIR', PROJECT_ROOT . '/plugins');
define('PLUGIN_CONFIG', ADMIN_CONFIG_DIR . '/plugins.json');

// --- Hook system ---
$GLOBALS['fcms_hooks'] = [
    'init' => [],
    'before_content' => [],
    'after_content' => [],
    'before_render' => [],
    'after_render' => [],
    'route' => [],
    // ...add more as needed
];

// Register a hook
function fcms_add_hook($hook, $callback) {
    if (!isset($GLOBALS['fcms_hooks'][$hook])) $GLOBALS['fcms_hooks'][$hook] = [];
    $GLOBALS['fcms_hooks'][$hook][] = $callback;
}

// Run all callbacks for a hook
function fcms_do_hook($hook, ...$args) {
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

// --- Admin section registration ---
$GLOBALS['fcms_admin_sections'] = [];

/**
 * Register an admin section.
 * @param string $id Unique section id (e.g. 'blog')
 * @param array $opts [
 *   'label' => 'Blog',
 *   'menu_order' => 50, // lower = earlier in menu
 *   'render_callback' => function() { ... },
 * ]
 */
function fcms_register_admin_section($id, $opts) {
    $GLOBALS['fcms_admin_sections'][$id] = $opts;
}

/**
 * Get all registered admin sections, sorted by menu_order.
 * @return array
 */
function fcms_get_admin_sections() {
    $sections = $GLOBALS['fcms_admin_sections'];
    uasort($sections, function($a, $b) {
        return ($a['menu_order'] ?? 100) <=> ($b['menu_order'] ?? 100);
    });
    return $sections;
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
