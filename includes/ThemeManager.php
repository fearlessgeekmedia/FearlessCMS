<?php
class ThemeManager {
    private $activeTheme;
    private $themesPath;
    private $configPath;

    public function __construct() {
        if (!defined('PROJECT_ROOT')) {
            throw new Exception("PROJECT_ROOT is not defined! Please define('PROJECT_ROOT', ...) in your entry script before including ThemeManager.php.");
        }
        $root = PROJECT_ROOT;
        $this->themesPath = $root . '/themes';
        $this->configPath = $root . '/config/config.json';
        $this->loadActiveTheme();
        
        // Validate active theme, fallback to default if missing/invalid
        if (!$this->validateTheme($this->activeTheme)) {
            // Try fallback to default
            $this->activeTheme = 'default';
            if (!$this->validateTheme('default')) {
                throw new Exception("No valid theme found: both '{$this->activeTheme}' and 'default' are missing or invalid.");
            }
        }
    }

    private function loadActiveTheme() {
        if (file_exists($this->configPath)) {
            $config = json_decode(file_get_contents($this->configPath), true);
            $this->activeTheme = $config['active_theme'] ?? 'default';
        } else {
            $this->activeTheme = 'default';
        }
    }

    private function validateTheme($themeName) {
        $themePath = $this->themesPath . "/$themeName";
        $templatesPath = $themePath . '/templates';
        
        if (!is_dir($themePath)) {
            return false;
        }
        
        if (!is_dir($templatesPath)) {
            return false;
        }
        
        // Check for required templates
        $requiredTemplates = ['page.html', '404.html'];
        foreach ($requiredTemplates as $template) {
            if (!file_exists($templatesPath . '/' . $template)) {
                return false;
            }
        }
        return true;
    }

    public function getActiveTheme() {
        return $this->activeTheme;
    }

    public function setActiveTheme($themeName) {
        // Validate theme before setting it as active
        $this->validateTheme($themeName);

        $config = ['active_theme' => $themeName];
        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT));
        $this->activeTheme = $themeName;

        // Register theme's sidebars and menus
        $this->registerThemeSidebars($themeName);
        $this->registerThemeMenus($themeName);

        return true;
    }

    private function registerThemeSidebars($themeName) {
        $sidebars = [];
        $templatesPath = $this->themesPath . "/$themeName/templates";
        
        // Scan all template files
        $templateFiles = glob($templatesPath . '/*.html');
        foreach ($templateFiles as $templateFile) {
            $content = file_get_contents($templateFile);
            
            // Find all sidebar declarations
            preg_match_all('/{{sidebar=([^}]+)}}/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $sidebarName) {
                    $sidebarName = trim($sidebarName);
                    if (!isset($sidebars[$sidebarName])) {
                        $sidebars[$sidebarName] = [
                            'name' => ucwords(str_replace('_', ' ', $sidebarName)),
                            'description' => "Sidebar for $sidebarName",
                            'widgets' => []
                        ];
                    }
                }
            }
        }

        // Save sidebars to widgets.json
        if (!empty($sidebars)) {
            $widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
            $existingWidgets = file_exists($widgetsFile) ? json_decode(file_get_contents($widgetsFile), true) : [];
            
            // Merge new sidebars with existing ones, preserving existing widgets
            foreach ($sidebars as $name => $sidebar) {
                if (!isset($existingWidgets[$name])) {
                    $existingWidgets[$name] = $sidebar;
                }
            }
            
            file_put_contents($widgetsFile, json_encode($existingWidgets, JSON_PRETTY_PRINT));
        }
    }

    private function registerThemeMenus($themeName) {
        $menus = [];
        $templatesPath = $this->themesPath . "/$themeName/templates";
        
        // Scan all template files
        $templateFiles = glob($templatesPath . '/*.html');
        foreach ($templateFiles as $templateFile) {
            $content = file_get_contents($templateFile);
            
            // Find all menu declarations
            preg_match_all('/{{menu=([^}]+)}}/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $menuName) {
                    $menuName = trim($menuName);
                    if (!isset($menus[$menuName])) {
                        $menus[$menuName] = [
                            'name' => ucwords(str_replace('_', ' ', $menuName)),
                            'description' => "Menu for $menuName",
                            'items' => []
                        ];
                    }
                }
            }
        }

        // Save menus to menus.json
        if (!empty($menus)) {
            $menusFile = ADMIN_CONFIG_DIR . '/menus.json';
            $existingMenus = file_exists($menusFile) ? json_decode(file_get_contents($menusFile), true) : [];
            
            // Merge new menus with existing ones, preserving existing items
            foreach ($menus as $name => $menu) {
                if (!isset($existingMenus[$name])) {
                    $existingMenus[$name] = $menu;
                }
            }
            
            file_put_contents($menusFile, json_encode($existingMenus, JSON_PRETTY_PRINT));
        }
    }

    public function getThemes() {
        $themes = [];
        $themeFolders = array_filter(glob($this->themesPath . '/*'), 'is_dir');
        
        foreach ($themeFolders as $themeFolder) {
            $themeId = basename($themeFolder);
            $themeConfigFile = $themeFolder . '/config.json';
            
            if (file_exists($themeConfigFile)) {
                $themeConfig = json_decode(file_get_contents($themeConfigFile), true);
                $themes[] = [
                    'id' => $themeId,
                    'name' => $themeConfig['name'] ?? ucfirst($themeId) . ' Theme',
                    'description' => $themeConfig['description'] ?? 'A theme for FearlessCMS',
                    'version' => $themeConfig['version'] ?? '1.0',
                    'author' => $themeConfig['author'] ?? 'Unknown',
                    'active' => ($themeId === $this->activeTheme)
                ];
            } else {
                // Fallback for themes without config
                $themes[] = [
                    'id' => $themeId,
                    'name' => ucfirst($themeId) . ' Theme',
                    'description' => 'A theme for FearlessCMS',
                    'version' => '1.0',
                    'author' => 'Unknown',
                    'active' => ($themeId === $this->activeTheme)
                ];
            }
        }
        
        return $themes;
    }

    public function getTemplate($templateName, $fallbackTemplate = 'page') {
        // First try the requested template in active theme
        $templatePath = $this->themesPath . "/{$this->activeTheme}/templates/$templateName.html";
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }

        // If not found, try the fallback template in active theme
        $fallbackPath = $this->themesPath . "/{$this->activeTheme}/templates/$fallbackTemplate.html";
        if (file_exists($fallbackPath)) {
            return file_get_contents($fallbackPath);
        }

        // Finally, try the default theme's fallback template
        $defaultPath = $this->themesPath . "/default/templates/$fallbackTemplate.html";
        if (file_exists($defaultPath)) {
            return file_get_contents($defaultPath);
        }

        throw new Exception("Template '$templateName' and fallback '$fallbackTemplate' not found");
    }
}
