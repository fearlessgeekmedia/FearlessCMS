<?php

/**
 * Tests for ThemeManager
 */

beforeEach(function () {
    // Clean themes dir and config
    $themesDir = FCMS_TEST_DIR . '/themes';
    if (is_dir($themesDir)) {
        // Leave default theme created by bootstrap
        foreach (glob($themesDir . '/*') as $theme) {
            if (is_dir($theme) && basename($theme) !== 'default') {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $item) {
                    $item->isDir() ? rmdir($item) : unlink($item);
                }
                rmdir($theme);
            }
        }
    }

    $configFile = FCMS_TEST_DIR . '/config/config.json';
    file_put_contents($configFile, json_encode(['active_theme' => 'default'], JSON_PRETTY_PRINT));

    require_once __DIR__ . '/../../includes/ThemeManager.php';
    $this->themeManager = new ThemeManager();
});

test('ThemeManager identifies active theme', function () {
    expect($this->themeManager->getActiveTheme())->toBe('default');
});

test('ThemeManager can change active theme', function () {
    // Create another valid theme
    $newThemePath = FCMS_TEST_DIR . '/themes/newtheme/templates';
    mkdir($newThemePath, 0755, true);
    file_put_contents($newThemePath . '/page.html', 'New Page');
    file_put_contents($newThemePath . '/404.html', 'New 404');
    
    $this->themeManager->setActiveTheme('newtheme');
    expect($this->themeManager->getActiveTheme())->toBe('newtheme');
    
    // Check persistence
    $config = json_decode(file_get_contents(FCMS_TEST_DIR . '/config/config.json'), true);
    expect($config['active_theme'])->toBe('newtheme');
});

test('ThemeManager falls back to default if theme is invalid', function () {
    // Corrupt config to point to non-existent theme
    $configFile = FCMS_TEST_DIR . '/config/config.json';
    file_put_contents($configFile, json_encode(['active_theme' => 'nonexistent'], JSON_PRETTY_PRINT));
    
    $manager = new ThemeManager();
    expect($manager->getActiveTheme())->toBe('default');
});

test('getTemplate returns content from active theme', function () {
    $templatesPath = FCMS_TEST_DIR . '/themes/default/templates';
    file_put_contents($templatesPath . '/test.html', 'Test Content');
    
    expect($this->themeManager->getTemplate('test'))->toBe('Test Content');
});

test('getTemplate falls back to page.html if requested not found', function () {
    $templatesPath = FCMS_TEST_DIR . '/themes/default/templates';
    file_put_contents($templatesPath . '/page.html', 'Default Page');
    
    expect($this->themeManager->getTemplate('missing', 'page'))->toBe('Default Page');
});

test('registerThemeSidebars scans templates', function () {
    $templatesPath = FCMS_TEST_DIR . '/themes/default/templates';
    file_put_contents($templatesPath . '/sidebar_test.html', '{{sidebar=my_custom_sidebar}}');
    
    $this->themeManager->setActiveTheme('default');
    
    $widgetsFile = FCMS_TEST_DIR . '/admin/config/widgets.json';
    expect(file_exists($widgetsFile))->toBeTrue();
    
    $widgets = json_decode(file_get_contents($widgetsFile), true);
    expect($widgets)->toHaveKey('my_custom_sidebar');
    expect($widgets['my_custom_sidebar']['name'])->toBe('My Custom Sidebar');
});

test('getThemes returns all available themes', function () {
    // Create another theme
    $newThemePath = FCMS_TEST_DIR . '/themes/theme2/templates';
    mkdir($newThemePath, 0755, true);
    file_put_contents($newThemePath . '/page.html', '...');
    file_put_contents($newThemePath . '/404.html', '...');
    file_put_contents(FCMS_TEST_DIR . '/themes/theme2/config.json', json_encode(['name' => 'Theme Two']));
    
    $themes = $this->themeManager->getThemes();
    expect($themes)->toHaveCount(2);
    
    $theme2 = array_filter($themes, fn($t) => $t['id'] === 'theme2');
    $theme2 = array_values($theme2)[0];
    expect($theme2['name'])->toBe('Theme Two');
});
