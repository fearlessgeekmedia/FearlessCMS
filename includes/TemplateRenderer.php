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
        $templateFile = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $template;
        if (!file_exists($templateFile)) {
            $templateFile .= '.html';
        }
        if (!file_exists($templateFile)) {
            // Fallback to page.html
            $templateFile = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/page.html';
            if (!file_exists($templateFile)) {
                throw new Exception("Template not found: $template, and fallback to page.html also failed.");
            }
        }
        $content = file_get_contents($templateFile);

        // Normalize theme options keys
        $normalizedOptions = [];
        foreach ($this->themeOptions as $key => $value) {
            $normalizedOptions[strtolower($key)] = $value;
        }

        // error_log("Theme options: " . print_r($this->themeOptions, true));
        // error_log("Normalized options: " . print_r($normalizedOptions, true));

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
            'custom_js' => $data['custom_js'] ?? '',
            'themeOptions' => $this->themeOptions,
            'theme_options' => $this->themeOptions
        ];

        // Add theme options as both original and lowercased keys
        foreach ($this->themeOptions as $key => $value) {
            $templateData[$key] = $value;
            $templateData[strtolower($key)] = $value;
        }

        // Merge with any additional data
        $templateData = array_merge($templateData, $data);

        // Handle sidebar variable
        if (isset($data['sidebar'])) {
            $templateData['sidebar'] = (bool)$data['sidebar'];
        }

        // Replace template variables
        $content = $this->replaceVariables($content, $templateData);

        return $content;
    }

    public function replaceVariables($content, $data) {
        // error_log("Template content before processing: " . $content);
        
        // Handle module includes first ({{module=filename.html}})
        $content = preg_replace_callback('/{{module=([^}]+)}}/', function($matches) use ($data) {
            $moduleFile = trim($matches[1]);
            return $this->includeModule($moduleFile, $data);
        }, $content);
        
        // Handle special tags first
        // Handle sidebar syntax (only {{sidebar=name}} form)
        $content = preg_replace_callback('/{{sidebar=([^}]+)}}/', function($matches) {
            $sidebarName = trim($matches[1]);
            return $this->widgetManager->renderSidebar($sidebarName);
        }, $content);

        // Handle menu syntax (both {{menu=main}} and {{mainMenu}})
        $content = preg_replace_callback('/{{menu=([^}]+)}}/', function($matches) {
            $menuId = trim($matches[1]);
            return $this->menuManager->renderMenu($menuId);
        }, $content);

        // Handle themeOptions access ({{themeOptions.key}})
        $content = preg_replace_callback('/{{themeOptions\.([^}]+)}}/', function($matches) use ($data) {
            $key = trim($matches[1]);
            return $data['themeOptions'][$key] ?? '';
        }, $content);

        // Handle foreach loops for arrays ({{#each array}}...{{/each}})
        $content = preg_replace_callback('/{{#each\s+([^}]+)}}(.*?){{\/each}}/s', function($matches) use ($data) {
            $arrayKey = trim($matches[1]);
            $loopContent = $matches[2];
            
            // Check for themeOptions.array format
            if (preg_match('/^themeOptions\.(.+)$/', $arrayKey, $themeMatches)) {
                $key = $themeMatches[1];
                $array = $data['themeOptions'][$key] ?? [];
            } else {
                $array = $data[$arrayKey] ?? [];
            }
            
            if (!is_array($array)) {
                return '';
            }
            
            $result = '';
            foreach ($array as $item) {
                $itemContent = $loopContent;
                // Replace {{key}} with item values
                foreach ($item as $itemKey => $itemValue) {
                    $itemContent = str_replace('{{' . $itemKey . '}}', $itemValue, $itemContent);
                }
                $result .= $itemContent;
            }
            
            return $result;
        }, $content);

        // Handle if conditions with else blocks (run multiple times for nested blocks)
        for ($i = 0; $i < 5; $i++) {
            $newContent = preg_replace_callback(
                '/{{#if\s+([^}]+)}}(.*?)(?:{{else}}(.*?))?{{\/if}}/s',
                function($matches) use ($data) {
                    $condition = trim($matches[1]);
                    $ifContent = $matches[2];
                    $elseContent = $matches[3] ?? '';
                    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $condition));
                    $conditionMet = false;
                    
                    // Check for themeOptions.key format
                    if (preg_match('/^themeOptions\.(.+)$/', $condition, $themeMatches)) {
                        $key = $themeMatches[1];
                        $conditionMet = !empty($data['themeOptions'][$key]);
                    } elseif (isset($data[$condition])) {
                        $conditionMet = !empty($data[$condition]);
                    } elseif (isset($data[$snakeCase])) {
                        $conditionMet = !empty($data[$snakeCase]);
                    }
                    
                    if ($conditionMet) {
                        return $this->replaceVariables($ifContent, $data);
                    } else {
                        return $this->replaceVariables($elseContent, $data);
                    }
                },
                $content
            );
            if ($newContent === $content) break;
            $content = $newContent;
        }
        
        // Remove any stray {{/if}} tags
        $content = str_replace('{{/if}}', '', $content);
        
        // error_log("Template content after processing: " . $content);

        // Then handle simple variables (but not special tags)
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                // Skip if this is a special tag
                if (in_array($key, ['sidebar', 'menu'])) {
                    continue;
                }
                // Unescape forward slashes in paths
                if (in_array($key, ['logo', 'heroBanner', 'hero_banner']) && is_string($value)) {
                    $value = str_replace('\\/', '/', $value);
                }
                // Convert boolean to string
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                // Handle both camelCase and snake_case versions
                $content = str_replace('{{' . $key . '}}', $value, $content);
                $content = str_replace('{{{' . $key . '}}}', $value, $content);
                
                // Debug: Check if content variable is being processed
                if ($key === 'content') {
                    error_log("PROCESSING CONTENT VARIABLE: " . substr($value, 0, 100));
                    error_log("TEMPLATE BEFORE: " . substr($content, 0, 200));
                }
            }
        }

        // Add Tailwind CSS if not already included
        if (strpos($content, 'tailwindcss') === false) {
            $tailwindLink = '<script src="https://cdn.tailwindcss.com"></script>';
            $content = str_replace('</head>', $tailwindLink . "\n</head>", $content);
        }

        return $content;
    }

    /**
     * Include a module template file
     * 
     * @param string $moduleFile The module file name (e.g., "header.html")
     * @param array $data The template data to pass to the module
     * @return string The rendered module content
     */
    private function includeModule($moduleFile, $data) {
        // Look for the module file in the current theme's templates directory
        $modulePath = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $moduleFile;
        
        // If the file doesn't have an extension, try adding .html
        if (!file_exists($modulePath) && !pathinfo($moduleFile, PATHINFO_EXTENSION)) {
            $modulePath .= '.html';
        }
        
        if (!file_exists($modulePath)) {
            error_log("Module file not found: " . $modulePath);
            return "<!-- Module not found: $moduleFile -->";
        }
        
        // Read the module content
        $moduleContent = file_get_contents($modulePath);
        
        // Process the module content with the same data
        return $this->replaceVariables($moduleContent, $data);
    }
} 
