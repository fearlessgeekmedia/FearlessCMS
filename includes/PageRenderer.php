<?php
/**
 * PageRenderer class for template rendering and output
 */
class PageRenderer {
    private $themeManager;
    private $menuManager;
    private $widgetManager;
    private $templateRenderer;
    private $cmsModeManager;
    private $demoManager;

    public function __construct($themeManager, $menuManager, $widgetManager, $cmsModeManager, $demoManager) {
        $this->themeManager = $themeManager;
        $this->menuManager = $menuManager;
        $this->widgetManager = $widgetManager;
        $this->cmsModeManager = $cmsModeManager; // may be null
        $this->demoManager = $demoManager;

        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

        $activeTheme = $this->themeManager->getActiveTheme();

        require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';
        $this->templateRenderer = new TemplateRenderer(
            $activeTheme,
            $themeOptions,
            $this->menuManager,
            $this->widgetManager
        );
    }

    public function renderPluginContent($title, $content, $path = '', $metadata = []) {
        $siteData = $this->getSiteData();

        $template = 'page';
        fcms_do_hook_ref('before_render', $template, $path);

        $templateData = array_merge($siteData, [
            'title' => $title,
            'content' => $content,
        ]);

        if (is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $templateData[$key] = $value;
            }
        }

        return $this->templateRenderer->render($template, $templateData);
    }

    public function render404() {
        fcms_flush_output();
        http_response_code(404);
        fcms_do_hook('404_error', $_SERVER['REQUEST_URI']);

        $contentFile = CONTENT_DIR . '/404.md';
        if (file_exists($contentFile)) {
            $contentLoader = new ContentLoader($this->demoManager);
            $contentData = $contentLoader->loadContent($contentFile);
            $pageContentHtml = $contentLoader->processContent($contentData['content'], $contentData['editor_mode']);
            return $this->renderPage($contentData, $pageContentHtml, '404');
        }

        $pageTitle = 'Page Not Found';
        $pageContent = '<p>The page you requested could not be found.</p>';

        $siteData = $this->getSiteData();
        $templateData = array_merge($siteData, [
            'title' => $pageTitle,
            'content' => $pageContent,
        ]);

        $templateRenderer = new TemplateRenderer(
            $this->themeManager->getActiveTheme(),
            $this->getThemeOptions(),
            $this->menuManager,
            $this->widgetManager
        );

        return $templateRenderer->render('404', $templateData);
    }

    public function renderPage($contentData, $pageContentHtml, $path) {
        $siteData = $this->getSiteData();
        $themeOptions = $this->getThemeOptions();

        $templateData = array_merge($siteData, [
            'title' => $contentData['title'],
            'content' => $pageContentHtml,
            'cmsMode' => $this->cmsModeManager ? $this->cmsModeManager->getCurrentMode() : 'full-featured',
            'isHostingServiceMode' => $this->cmsModeManager ? $this->cmsModeManager->isRestricted() : false,
            'cmsModeName' => $this->cmsModeManager ? $this->cmsModeManager->getModeName() : 'Full Featured',
        ]);

        // Add custom variables from metadata
        if (isset($contentData['metadata']) && is_array($contentData['metadata'])) {
            foreach ($contentData['metadata'] as $key => $value) {
                $templateData[$key] = $value;
            }
        }

        $templateName = $contentData['metadata']['template'] ?? 'page-with-sidebar';
        fcms_do_hook_ref('before_render', $templateName);

        $template = $this->templateRenderer->render($templateName, $templateData);

        return $template;
    }

    private function getSiteData() {
        $configFile = CONFIG_DIR . '/config.json';
        $siteName = 'FearlessCMS';
        $siteDescription = '';
        $favicon = '';

        // Use demo config if in demo mode
        if ($this->demoManager->isDemoSession() || $this->demoManager->isDemoUserSession()) {
            $demoConfigFile = $this->demoManager->getDemoConfigDir() . '/config.json';
            if (file_exists($demoConfigFile)) {
                $configFile = $demoConfigFile;
            }
        }

        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $siteName = $config['site_name'] ?? $siteName;
            $siteDescription = $config['site_description'] ?? $siteDescription;
            $favicon = $config['favicon'] ?? $favicon;
        }

        return [
            'siteName' => $siteName,
            'siteDescription' => $siteDescription,
            'favicon' => $favicon,
            'currentYear' => date('Y'),
        ];
    }

    private function getThemeOptions() {
        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        return file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];
    }
}
?>
