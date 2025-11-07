<?php
/**
 * Router class for handling URL routing and plugin integration
 */
class Router {
    private $requestPath;
    private $demoManager;
    private $config;

    public function __construct($demoManager, $config) {
        $this->demoManager = $demoManager;
        $this->config = $config;
        $this->parseRequestPath();
    }

    private function parseRequestPath() {
        $this->requestPath = trim($_SERVER['REQUEST_URI'], '/');

        // Remove query parameters
        if (($queryPos = strpos($this->requestPath, '?')) !== false) {
            $this->requestPath = substr($this->requestPath, 0, $queryPos);
        }

        // Handle subdomain prefix if present
        if (strpos($this->requestPath, 'fearlesscms.hstn.me/') === 0) {
            $this->requestPath = substr($this->requestPath, strlen('fearlesscms.hstn.me/'));
        }
    }

    public function getRequestPath() {
        return $this->requestPath === '' ? 'home' : $this->requestPath;
    }

    public function isPreviewRequest() {
        return strpos($this->requestPath, '_preview/') === 0;
    }

    public function handlePreviewRequest() {
        $previewPath = substr($this->requestPath, 9);
        $previewFile = CONTENT_DIR . '/_preview/' . $previewPath . '.md';

        if (!file_exists($previewFile)) {
            return false;
        }

        $contentData = file_get_contents($previewFile);
        $metadata = [];

        if (preg_match('/^<!--\\s*json\\s*(.*?)\\s*-->/s', $contentData, $matches)) {
            $metadata = json_decode($matches[1], true);
            $content = substr($contentData, strlen($matches[0]));
        } else {
            $content = $contentData;
        }

        $pageTitle = $metadata['title'] ?? 'Preview';
        $editorMode = $metadata['editor_mode'] ?? 'markdown';

        if ($editorMode === 'easy' || $editorMode === 'html') {
            $pageContentHtml = $content;
        } else {
            require_once PROJECT_ROOT . '/includes/Parsedown.php';
            $Parsedown = new Parsedown();
            $Parsedown->setMarkupEscaped(false);
            $pageContentHtml = $Parsedown->text($content);
        }

        $configFile = CONFIG_DIR . '/config.json';
        $siteName = 'FearlessCMS';
        $siteDescription = '';
        $favicon = '';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $siteName = $config['site_name'] ?? $siteName;
            $siteDescription = $config['site_description'] ?? $siteDescription;
            $favicon = $config['favicon'] ?? $favicon;
        }

        $themeOptionsFile = CONFIG_DIR . '/theme_options.json';
        $themeOptions = file_exists($themeOptionsFile) ? json_decode(file_get_contents($themeOptionsFile), true) : [];

        require_once PROJECT_ROOT . '/includes/ThemeManager.php';
        require_once PROJECT_ROOT . '/includes/MenuManager.php';
        require_once PROJECT_ROOT . '/includes/WidgetManager.php';
        require_once PROJECT_ROOT . '/includes/TemplateRenderer.php';

        $themeManager = new ThemeManager();
        $menuManager = new MenuManager();
        $widgetManager = new WidgetManager();

        $templateRenderer = new TemplateRenderer(
            $themeManager->getActiveTheme(),
            $themeOptions,
            $menuManager,
            $widgetManager
        );

        $templateData = [
            'title' => $pageTitle,
            'content' => $pageContentHtml,
            'siteName' => $siteName,
            'siteDescription' => $siteDescription,
            'favicon' => $favicon,
            'currentYear' => date('Y'),
            'logo' => $themeOptions['logo'] ?? null,
            'heroBanner' => $themeOptions['herobanner'] ?? null,
            'mainMenu' => $menuManager->renderMenu('main'),
        ];

        if (isset($metadata) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                $templateData[$key] = $value;
            }
        }

        $pageContentHtml = $templateRenderer->replaceVariables($pageContentHtml, $templateData);
        $templateData['content'] = $pageContentHtml;

        $templateName = $metadata['template'] ?? 'page-with-sidebar';
        fcms_do_hook_ref('before_render', $templateName);
        $template = $templateRenderer->render($templateName, $templateData);

        echo $template;
        exit;
    }

    public function handlePluginRoutes(&$handled, &$title, &$content, $path) {
        fcms_do_hook_ref('route', $handled, $title, $content, $path);
        return $handled;
    }

    public function getDefaultPath() {
        return $this->requestPath === '' ? 'home' : $this->requestPath;
    }

    public function getDefaultTemplate($path) {
        return $path === 'home' ? 'home' : 'page-with-sidebar';
    }
}
?>
