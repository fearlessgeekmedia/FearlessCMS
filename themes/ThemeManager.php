<?php
class ThemeManager {
    private $activeTheme;
    private $themesPath;

    public function __construct() {
        $this->themesPath = dirname(__DIR__) . '/themes';  // Changed to get correct themes path
        $this->loadActiveTheme();
    }

    private function loadActiveTheme() {
        $configFile = CONFIG_DIR . '/config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $this->activeTheme = $config['active_theme'] ?? 'default';
        } else {
            $this->activeTheme = 'default';
        }
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

    public function getActiveTheme() {
        return $this->activeTheme;
    }

    public function setActiveTheme($themeName) {
        if (!is_dir($this->themesPath . "/$themeName")) {
            throw new Exception("Theme '$themeName' does not exist");
        }

        $config = ['active_theme' => $themeName];
        file_put_contents(CONFIG_DIR . '/config.json', json_encode($config, JSON_PRETTY_PRINT));
        $this->activeTheme = $themeName;
    }
}
