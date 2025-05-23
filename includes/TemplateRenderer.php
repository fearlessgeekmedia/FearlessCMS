<?php
class TemplateRenderer {
    private $theme;
    private $themeOptions;
    private $menuManager;
    private $widgetManager;

    public function __construct($theme, $themeOptions, $menuManager, $widgetManager) {
        $this->theme = $theme;
        $this->themeOptions = $themeOptions;
        $this->menuManager = $menuManager;
        $this->widgetManager = $widgetManager;
    }

    public function render($template, $data) {
        // Get template content
        $templateFile = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $template . '.html';
        if (!file_exists($templateFile)) {
            throw new Exception("Template not found: $template");
        }
        $content = file_get_contents($templateFile);

        // Normalize theme options keys
        $normalizedOptions = [];
        foreach ($this->themeOptions as $key => $value) {
            $normalizedOptions[strtolower($key)] = $value;
        }

        error_log("Theme options: " . print_r($this->themeOptions, true));
        error_log("Normalized options: " . print_r($normalizedOptions, true));

        // Prepare template data with both camelCase and snake_case versions
        $templateData = [
            'theme' => $this->theme,
            'siteName' => $data['siteName'] ?? 'FearlessCMS',
            'site_name' => $data['siteName'] ?? 'FearlessCMS',
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'logo' => $normalizedOptions['logo'] ?? null,
            'heroBanner' => $normalizedOptions['herobanner'] ?? $data['heroBanner'] ?? null,
            'hero_banner' => $normalizedOptions['herobanner'] ?? $data['heroBanner'] ?? null,
            'currentYear' => date('Y'),
            'current_year' => date('Y'),
            'mainMenu' => $this->menuManager->renderMenu('main'),
            'custom_css' => $data['custom_css'] ?? '',
            'custom_js' => $data['custom_js'] ?? ''
        ];

        // Merge with any additional data
        $templateData = array_merge($templateData, $data);

        error_log("Template data: " . print_r($templateData, true));

        // Replace template variables
        $content = $this->replaceVariables($content, $templateData);

        return $content;
    }

    private function replaceVariables($content, $data) {
        error_log("Template content before processing: " . $content);
        
        // Handle if conditions with else blocks
        $content = preg_replace_callback('/{{#if\s+([^}]+)}}(.*?)(?:{{else}}(.*?))?{{\/if}}/s', function($matches) use ($data) {
            $condition = trim($matches[1]);
            $ifContent = $matches[2];
            $elseContent = $matches[3] ?? '';
            
            error_log("Found if condition: " . $condition);
            error_log("If content: " . $ifContent);
            error_log("Else content: " . $elseContent);
            
            // Check both camelCase and snake_case versions
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $condition));
            
            // Check if the condition exists and is truthy
            $conditionMet = false;
            if (isset($data[$condition])) {
                $conditionMet = !empty($data[$condition]);
            } elseif (isset($data[$snakeCase])) {
                $conditionMet = !empty($data[$snakeCase]);
            }
            
            if ($conditionMet) {
                return $this->replaceVariables($ifContent, $data);
            } else {
                return $this->replaceVariables($elseContent, $data);
            }
        }, $content);
        
        error_log("Template content after processing: " . $content);

        // Handle sidebar syntax
        $content = preg_replace_callback('/{{sidebar=([^}]+)}}/', function($matches) {
            $sidebarName = trim($matches[1]);
            return $this->widgetManager->renderSidebar($sidebarName);
        }, $content);

        // Handle menu syntax (both {{menu=main}} and {{mainMenu}})
        $content = preg_replace_callback('/{{menu=([^}]+)}}/', function($matches) {
            $menuId = trim($matches[1]);
            return $this->menuManager->renderMenu($menuId);
        }, $content);

        // Replace simple variables
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                // Unescape forward slashes in paths
                if (in_array($key, ['logo', 'heroBanner', 'hero_banner']) && is_string($value)) {
                    $value = str_replace('\\/', '/', $value);
                }
                // Handle both camelCase and snake_case versions
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }

        // Add Tailwind CSS if not already included
        if (strpos($content, 'tailwindcss') === false) {
            $tailwindLink = '<script src="https://cdn.tailwindcss.com"></script>';
            $content = str_replace('</head>', $tailwindLink . "\n</head>", $content);
        }

        return $content;
    }
} 