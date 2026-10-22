<?php
/**
 * ContentLoader class for loading and processing content files
 */
class ContentLoader {
    private $demoManager;
    private $contentDir;

    public function __construct($demoManager) {
        $this->demoManager = $demoManager;
        $this->contentDir = CONTENT_DIR;
    }

    public function getContentFile($path) {
        $isDemoUser = $this->demoManager->isDemoUser();

        if ($isDemoUser) {
            $this->contentDir = $this->demoManager->getDemoContentDir();
            if ($path === 'home' || $path === 'about' || $path === 'contact') {
                return $this->contentDir . '/pages/' . $path . '.md';
            } elseif (strpos($path, 'blog/') === 0) {
                $blogPath = substr($path, 5);
                return $this->contentDir . '/blog/' . $blogPath . '.md';
            } elseif (strpos($path, 'pages/') === 0) {
                return $this->contentDir . '/' . $path . '.md';
            } else {
                return $this->contentDir . '/pages/' . $path . '.md';
            }
        } else {
            return $this->contentDir . '/' . $path . '.md';
        }
    }

    public function findContentFile($path) {
        $contentFile = $this->getContentFile($path);

        if (file_exists($contentFile)) {
            return $contentFile;
        }

        // Try parent/child relationship
        $parts = explode('/', $path);
        if (count($parts) > 1) {
            $childPath = array_pop($parts);
            $parentPath = implode('/', $parts);
            $parentFile = $this->contentDir . '/' . $parentPath . '.md';

            if (file_exists($parentFile)) {
                $parentContent = file_get_contents($parentFile);
                $parentMetadata = [];
                if (preg_match('/^<!--\\s*json\\s*(.*?)\\s*-->/s', $parentContent, $matches)) {
                    $parentMetadata = json_decode($matches[1], true);
                }

                $childFile = $this->contentDir . '/' . $childPath . '.md';
                if (file_exists($childFile)) {
                    $childContent = file_get_contents($childFile);
                    $childMetadata = [];
                    if (preg_match('/^<!--\\s*json\\s*(.*?)\\s*-->/s', $childContent, $matches)) {
                        $childMetadata = json_decode($matches[1], true);
                    }

                    if (isset($childMetadata['parent']) && $childMetadata['parent'] === $parentPath) {
                        return $childFile;
                    }
                }
            }
        }

        return false;
    }

    public function loadContent($contentFile) {
        $fileContent = file_get_contents($contentFile);
        $pageTitle = '';
        $pageDescription = '';
        $pageContent = $fileContent;
        $metadata = [];

        // Extract JSON frontmatter
        if (preg_match('/^<!--\\s*json\\s*(.*?)\\s*-->/s', $fileContent, $matches)) {
            $metadata = json_decode($matches[1], true);
            if ($metadata) {
                $pageTitle = $metadata['title'] ?? '';
                $pageDescription = $metadata['description'] ?? '';
            } else {
                $metadata = [];
            }
            $pageContent = preg_replace('/^<!--\\s*json\\s*.*?\\s*-->\\s*/s', '', $fileContent);
        }

        // Fallback to filename as title
        if (!$pageTitle) {
            $pageTitle = ucwords(str_replace(['-', '_'], ' ', basename(str_replace('.md', '', $contentFile))));
        }

        return [
            'title' => $pageTitle,
            'description' => $pageDescription,
            'content' => $pageContent,
            'metadata' => $metadata,
            'editor_mode' => $metadata['editor_mode'] ?? 'markdown'
        ];
    }

    public function processContent($content, $editorMode) {
        if ($editorMode === 'html') {
            $pageContent = fcms_apply_filter('content', $content);
            $pageContentHtml = $pageContent;
        } else {
            $pageContent = fcms_apply_filter('content', $content);
            if (!class_exists('Parsedown')) {
                require_once PROJECT_ROOT . '/includes/Parsedown.php';
            }
            $Parsedown = new Parsedown();
            $Parsedown->setMarkupEscaped(false);
            $pageContentHtml = $Parsedown->text($pageContent);
        }

        if ($editorMode !== 'html') {
            $pageContentHtml = fcms_apply_filter('after_content', $pageContentHtml);
        }

        return $pageContentHtml;
    }
}
?>
