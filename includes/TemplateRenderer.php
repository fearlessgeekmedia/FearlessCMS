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

        // Prepare template data
        $templateData = array_merge([
            'theme' => $this->theme,
            'siteName' => $data['siteName'] ?? 'FearlessCMS',
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'logo' => $normalizedOptions['logo'] ?? null,
            'heroBanner' => $normalizedOptions['herobanner'] ?? $data['heroBanner'] ?? null,
            'currentYear' => date('Y'),
            'mainMenu' => $this->menuManager->renderMenu('main')
        ], $data);

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
            error_log("Data for condition: " . print_r($data, true));
            
            // Check both camelCase and snake_case versions
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $condition));
            error_log("Checking condition: " . $condition . " and snake_case: " . $snakeCase);
            error_log("Data[$condition] = " . (isset($data[$condition]) ? $data[$condition] : 'not set'));
            error_log("Data[$snakeCase] = " . (isset($data[$snakeCase]) ? $data[$snakeCase] : 'not set'));
            
            if ((isset($data[$condition]) && $data[$condition]) || (isset($data[$snakeCase]) && $data[$snakeCase])) {
                error_log("Condition satisfied for: " . $condition);
                return $this->replaceVariables($ifContent, $data);
            } else {
                error_log("Condition not satisfied for: " . $condition);
                return $this->replaceVariables($elseContent, $data);
            }
        }, $content);
        
        error_log("Template content after processing: " . $content);

        // Handle sidebar syntax
        $content = preg_replace_callback('/{{sidebar=([^}]+)}}/', function($matches) {
            $sidebarName = trim($matches[1]);
            return $this->widgetManager->renderSidebar($sidebarName);
        }, $content);

        // Handle menu syntax
        $content = preg_replace_callback('/{{menu=([^}]+)}}/', function($matches) {
            $menuId = trim($matches[1]);
            return $this->menuManager->renderMenu($menuId);
        }, $content);

        // Replace simple variables last
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                // Unescape forward slashes in paths
                if (in_array($key, ['logo', 'heroBanner']) && is_string($value)) {
                    $value = str_replace('\\/', '/', $value);
                }
                // Handle both camelCase and snake_case versions
                $content = str_replace('{{' . $key . '}}', $value, $content);
                // Convert camelCase to snake_case for alternative format
                $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
                $content = str_replace('{{' . $snakeCase . '}}', $value, $content);
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