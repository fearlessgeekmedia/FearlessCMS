<?php
class WidgetManager {
    private $widgetsFile;
    private $documentationNavFile;

    public function __construct() {
        $this->widgetsFile = CONFIG_DIR . '/widgets.json';
        $this->documentationNavFile = CONFIG_DIR . '/documentation-nav.json';
    }

    public function renderSidebar($sidebarName) {
        if (!file_exists($this->widgetsFile)) {
            return '';
        }

        $widgets = json_decode(file_get_contents($this->widgetsFile), true);
        if (!isset($widgets[$sidebarName])) {
            return '';
        }

        $html = '';
        foreach ($widgets[$sidebarName]['widgets'] as $widget) {
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
            return '';
        }
        
        $navData = json_decode(file_get_contents($this->documentationNavFile), true);
        if (!isset($navData[$navKey])) {
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