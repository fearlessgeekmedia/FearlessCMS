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
            // Provide safe defaults for optional placeholders used by themes
            'custom_css' => $data['custom_css'] ?? '',

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
        // Handle module includes first ({{module=filename.html}})
        $content = preg_replace_callback('/{{module=([^}]+)}}/', function($matches) use ($data) {
            $moduleFile = trim($matches[1]);
            return $this->includeModule($moduleFile, $data);
        }, $content);
        
        // Handle include syntax ({{include=filename.html}})
        $content = preg_replace_callback('/{{include=([^}]+)}}/', function($matches) use ($data) {
            $includeFile = trim($matches[1]);
            return $this->includeFile($includeFile, $data);
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
            }
        }

        // Add Tailwind CSS if not already included
        if (strpos($content, 'tailwindcss') === false && strpos($content, '/public/css/output.css') === false) {
            $tailwindLink = '<link href="/public/css/output.css" rel="stylesheet">';
            $content = str_replace('</head>', $tailwindLink . "\n</head>", $content);
        }

        // Determine global UI enhancement flags (site-level, theme-level, meta override)
        $siteConfig = [];
        $themeConfig = [];
        $siteConfigPath = CONFIG_DIR . '/config.json';
        $themeConfigPath = PROJECT_ROOT . '/themes/' . $this->theme . '/config.json';
        if (file_exists($siteConfigPath)) {
            $siteConfig = json_decode(file_get_contents($siteConfigPath), true) ?: [];
        }
        if (file_exists($themeConfigPath)) {
            $themeConfig = json_decode(file_get_contents($themeConfigPath), true) ?: [];
        }

        $metaDisablesUi = (strpos($content, 'fcms-disable-global-ui') !== false);
        $globalEnhancementsEnabled = ($siteConfig['global_ui_enhancements'] ?? true) && !($themeConfig['disableGlobalEnhancements'] ?? false) && !$metaDisablesUi;
        $enableHamburger = ($siteConfig['enable_hamburger'] ?? true) && !($themeConfig['disableHamburger'] ?? false);
        $enableThemeToggle = ($siteConfig['enable_theme_toggle'] ?? true) && !($themeConfig['disableThemeToggle'] ?? false) && ($themeConfig['supportsDarkMode'] ?? true);

        // Inject minimal nav hamburger CSS and theme toggle script globally if enabled and not already present
        if ($globalEnhancementsEnabled && strpos($content, 'fcms-nav-style') === false) {
            $enhanceStyle = '<style id="fcms-nav-style">\n'
                . '@media (max-width: 900px){header nav{display:none}header nav.is-open{display:block}.fcms-hamburger{display:inline-flex;gap:6px;flex-direction:column;justify-content:center;align-items:center;width:40px;height:36px;border:1px solid rgba(0,0,0,0.15);border-radius:10px;background:#fff;margin-left:6px} .fcms-hamburger span{display:block;width:20px;height:2px;background:#111;border-radius:2px}}\n'
                . '@media (prefers-color-scheme: dark){@media (max-width:900px){.fcms-hamburger{background:#0f172a;border-color:rgba(255,255,255,0.25)} .fcms-hamburger span{background:#e5e7eb}}}\n'
                . '@media (min-width: 901px){.fcms-hamburger{display:none !important}}\n'
                . '.fcms-theme-toggle{display:inline-flex;align-items:center;justify-content:center;width:40px;height:36px;margin-left:8px;border:1px solid rgba(0,0,0,0.15);border-radius:10px;background:#fff;color:#111;font-size:16px;line-height:1} .fcms-theme-toggle:focus{outline:2px solid #6366f1; outline-offset:2px}\n'
                . '@media (prefers-color-scheme: dark){.fcms-theme-toggle{background:#0f172a;border-color:rgba(255,255,255,0.25);color:#e5e7eb}}\n'
                . '</style>';
            $content = str_replace('</head>', $enhanceStyle . "\n</head>", $content);
        }

        if ($globalEnhancementsEnabled && strpos($content, 'fcmsThemeInit') === false) {
            $script = '<script id="fcmsThemeInit">\n'
                . '(function(){\n'
                . '  var root=document.documentElement;\n'
                . '  var KEY="fcms-theme";\n'
                . '  function apply(pref){\n'
                . '    if(pref==="system"){root.removeAttribute("data-theme");} else {root.setAttribute("data-theme", pref);}\n'
                . '  }\n'
                . '  function init(){\n'
                . '    var pref=localStorage.getItem(KEY)||"system"; apply(pref);\n'
                . '    window.fcmsSetTheme=function(p){localStorage.setItem(KEY,p);apply(p);return p;};\n'
                . '    window.fcmsCycleTheme=function(){var seq=["system","light","dark"];var cur=localStorage.getItem(KEY)||"system";var next=seq[(seq.indexOf(cur)+1)%3];localStorage.setItem(KEY,next);apply(next);return next;};\n'
                . '  }\n'
                . '  document.addEventListener("DOMContentLoaded", init);\n'
                . '})();\n'
                . '(function(){\n'
                . '  function enhance(){\n'
                . '    var header=document.querySelector("header"); if(!header) return;\n'
                . '    var nav=header.querySelector("nav"); if(!nav) return;\n'
                . '    var parent=nav.parentNode;\n'
                . '    var enableH=' . ($enableHamburger ? 'true' : 'false') . ';\n'
                . '    var enableT=' . ($enableThemeToggle ? 'true' : 'false') . ';\n'
                . '    if(enableH){\n'
                . '      var hb=document.getElementById("fcms-hamburger");\n'
                . '      if(!hb){ hb=document.createElement("button"); hb.id="fcms-hamburger"; hb.type="button"; hb.className="fcms-hamburger"; hb.setAttribute("aria-label","Toggle navigation"); hb.setAttribute("aria-expanded","false"); hb.innerHTML="<span></span><span></span><span></span>"; try{parent.insertBefore(hb, nav);}catch(e){header.appendChild(hb);} }\n'
                . '      if(hb && !hb._wired){ hb.addEventListener("click", function(){ var open=nav.classList.toggle("is-open"); hb.setAttribute("aria-expanded", open); }); hb._wired=true; }\n'
                . '    }\n'
                . '    if(enableT){\n'
                . '      var t=document.getElementById("fcms-theme-toggle");\n'
                . '      if(!t){ t=document.createElement("button"); t.id="fcms-theme-toggle"; t.type="button"; t.className="fcms-theme-toggle"; t.setAttribute("aria-label","Toggle theme (system/light/dark)"); t.title="Theme"; t.textContent="◐"; try{parent.insertBefore(t, nav);}catch(e){header.appendChild(t);} }\n'
                . '      if(t && !t._wired){ t.addEventListener("click", function(){ var next=window.fcmsCycleTheme?window.fcmsCycleTheme():null; t.setAttribute("data-mode", next); }); t._wired=true; }\n'
                . '    }\n'
                . '  }\n'
                . '  document.addEventListener("DOMContentLoaded", enhance);\n'
                . '})();\n'
                . '</script>';
            $content = str_replace('</head>', $script . "\n</head>", $content);
        }

        return $content;
    }

    /**
     * Include a module template file
     * 
     * @param string $moduleFile The module file name (e.g., "header.html" or "header.html.mod")
     * @param array $data The template data to pass to the module
     * @return string The rendered module content
     */
    private function includeModule($moduleFile, $data) {
        // Look for the module file in the current theme's templates directory
        $modulePath = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $moduleFile;
        
        // If the file doesn't have an extension, try adding .html.mod first, then .html
        if (!file_exists($modulePath)) {
            if (!pathinfo($moduleFile, PATHINFO_EXTENSION)) {
                // No extension provided, try .html.mod first, then .html
                $modulePathMod = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $moduleFile . '.html.mod';
                if (file_exists($modulePathMod)) {
                    $modulePath = $modulePathMod;
                } else {
                    $modulePath = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $moduleFile . '.html';
                }
            } elseif (pathinfo($moduleFile, PATHINFO_EXTENSION) === 'html') {
                // .html extension provided, try .html.mod version
                $modulePathMod = PROJECT_ROOT . '/themes/' . $this->theme . '/templates/' . $moduleFile . '.mod';
                if (file_exists($modulePathMod)) {
                    $modulePath = $modulePathMod;
                }
            }
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

    /**
     * Include a file from the themes directory
     * 
     * @param string $includeFile The file name to include
     * @param array $data The template data to pass to the include
     * @return string The rendered include content
     */
    private function includeFile($includeFile, $data) {
        // Look for the file in the themes directory (not theme-specific)
        $includePath = PROJECT_ROOT . '/themes/' . $includeFile;
        
        if (!file_exists($includePath)) {
            error_log("Include file not found: " . $includePath);
            return "<!-- Include file not found: $includeFile -->";
        }
        
        // Read the include content
        $includeContent = file_get_contents($includePath);
        
        // Process the include content with the same data
        return $this->replaceVariables($includeContent, $data);
    }
} 
