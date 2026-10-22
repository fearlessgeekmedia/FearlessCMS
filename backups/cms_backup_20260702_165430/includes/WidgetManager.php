<?php
class WidgetManager {
    private $widgetsFile;
    private $sidebarsFile;
    private $documentationNavFile;

    public function __construct() {
        $this->widgetsFile = CONFIG_DIR . '/widgets.json';
        $this->sidebarsFile = CONFIG_DIR . '/sidebars.json';
        $this->documentationNavFile = CONFIG_DIR . '/documentation-nav.json';
    }

    public function renderSidebar($sidebarName) {
        $widgets = [];
        
        if (file_exists($this->sidebarsFile)) {
            $data = json_decode(file_get_contents($this->sidebarsFile), true);
            if (isset($data[$sidebarName])) {
                $widgets = $data[$sidebarName]['widgets'] ?? [];
            }
        }
        
        if (empty($widgets) && file_exists($this->widgetsFile)) {
            $data = json_decode(file_get_contents($this->widgetsFile), true);
            if (isset($data[$sidebarName])) {
                $widgets = $data[$sidebarName]['widgets'] ?? [];
            }
        }

        if (empty($widgets)) {
            return '';
        }

        $html = '';
        foreach ($widgets as $widget) {
            $html .= $this->renderWidget($widget);
        }

        return $html;
    }

    private function renderWidget($widget) {
        $type = $widget['type'] ?? '';
        $title = htmlspecialchars($widget['title'] ?? '');
        
        // Handle documentation navigation widgets
        if ($type === 'documentation-nav') {
            return $this->renderDocumentationNavWidget($widget);
        }
        
        // For HTML widgets, don't escape the content
        if ($type === 'html') {
            $content = $widget['content'] ?? '';
        } else {
            $content = htmlspecialchars($widget['content'] ?? '');
        }

        $html = '<div class="widget widget-' . htmlspecialchars($type) . '">';
        if ($title) {
            $html .= '<h3 class="widget-title">' . $title . '</h3>';
        }
        $html .= '<div class="widget-content">' . $content . '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderDocumentationNavWidget($widget) {
        $title = htmlspecialchars($widget['title'] ?? '');
        $navKey = $widget['content'] ?? '';
        
        if (!file_exists($this->documentationNavFile)) {
            error_log("WidgetManager: documentation-nav.json not found at " . $this->documentationNavFile);
            return '';
        }
        
        $navData = json_decode(file_get_contents($this->documentationNavFile), true);
        if (!isset($navData[$navKey])) {
            error_log("WidgetManager: Nav key " . $navKey . " not found in " . $this->documentationNavFile);
            return '';
        }
        
        $html = '<div class="widget widget-documentation-nav">';
        if ($title) {
            $html .= '<h3 class="widget-title">' . $title . '</h3>';
        }
        $html .= '<div class="widget-content">';
        $html .= '<ul class="documentation-nav-list">';
        
        foreach ($navData[$navKey] as $item) {
            $label = htmlspecialchars($item['label'] ?? '');
            $url = htmlspecialchars($item['url'] ?? '#');
            $description = htmlspecialchars($item['description'] ?? '');
            
            $html .= '<li class="documentation-nav-item">';
            $html .= '<a href="' . $url . '" class="documentation-nav-link" title="' . $description . '">' . $label . '</a>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
} 