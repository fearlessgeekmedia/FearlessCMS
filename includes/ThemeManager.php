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
    }

    private function loadActiveTheme() {
        if (file_exists($this->configPath)) {
            $config = json_decode(file_get_contents($this->configPath), true);
            $this->activeTheme = $config['active_theme'] ?? 'default';
        } else {
            $this->activeTheme = 'default';
        }
    }

    public function getActiveTheme() {
        return $this->activeTheme;
    }

    public function setActiveTheme($themeName) {
        if (!is_dir($this->themesPath . "/$themeName")) {
            throw new Exception("Theme '$themeName' does not exist");
        }

        $config = ['active_theme' => $themeName];
        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT));
        $this->activeTheme = $themeName;
        return true;
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
