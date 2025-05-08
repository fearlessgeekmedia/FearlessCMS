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

        // Prepare template data
        $templateData = array_merge([
            'theme' => $this->theme,
            'siteName' => $data['siteName'] ?? 'FearlessCMS',
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'logo' => $this->themeOptions['logo'] ?? null,
            'heroBanner' => $this->themeOptions['herobanner'] ?? null,
            'currentYear' => date('Y'),
            'mainMenu' => $this->menuManager->renderMenu('main')
        ], $data);

        // Replace template variables
        $content = $this->replaceVariables($content, $templateData);

        return $content;
    }

    private function replaceVariables($content, $data) {
        // Handle if conditions with else blocks
        $content = preg_replace_callback('/{{#if\s+([^}]+)}}(.*?)(?:{{else}}(.*?))?{{\/if}}/s', function($matches) use ($data) {
            $condition = trim($matches[1]);
            $ifContent = $matches[2];
            $elseContent = $matches[3] ?? '';
            
            // Check both camelCase and snake_case versions
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $condition));
            if ((isset($data[$condition]) && $data[$condition]) || (isset($data[$snakeCase]) && $data[$snakeCase])) {
                return $this->replaceVariables($ifContent, $data);
            } else {
                return $this->replaceVariables($elseContent, $data);
            }
        }, $content);

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