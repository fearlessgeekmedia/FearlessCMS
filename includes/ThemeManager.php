<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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
        $this->configPath = CONFIG_DIR . '/config.json';
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

            // Check for thumbnail files
            $thumbnail = null;
            $thumbnailExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
            foreach ($thumbnailExtensions as $ext) {
                $thumbnailPath = $themeFolder . "/thumbnail.$ext";
                if (file_exists($thumbnailPath)) {
                    $thumbnail = "themes/$themeId/thumbnail.$ext";
                    break;
                }
                // Also check for screenshot
                $screenshotPath = $themeFolder . "/screenshot.$ext";
                if (file_exists($screenshotPath)) {
                    $thumbnail = "themes/$themeId/screenshot.$ext";
                    break;
                }
            }

            if (file_exists($themeConfigFile)) {
                $themeConfig = json_decode(file_get_contents($themeConfigFile), true);
                $themes[] = [
                    'id' => $themeId,
                    'name' => $themeConfig['name'] ?? ucfirst($themeId) . ' Theme',
                    'description' => $themeConfig['description'] ?? 'A theme for FearlessCMS',
                    'version' => $themeConfig['version'] ?? '0.0.3',
                    'author' => $themeConfig['author'] ?? 'Unknown',
                    'thumbnail' => $thumbnail,
                    'active' => ($themeId === $this->activeTheme)
                ];
            } else {
                // Fallback for themes without config
                $themes[] = [
                    'id' => $themeId,
                    'name' => ucfirst($themeId) . ' Theme',
                    'description' => 'A theme for FearlessCMS',
                    'version' => '0.0.3',
                    'author' => 'Unknown',
                    'thumbnail' => $thumbnail,
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
