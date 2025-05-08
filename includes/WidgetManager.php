<?php
class WidgetManager {
    private $widgetsFile;

    public function __construct() {
        $this->widgetsFile = ADMIN_CONFIG_DIR . '/widgets.json';
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
        $content = htmlspecialchars($widget['content'] ?? '');

        $html = '<div class="widget widget-' . htmlspecialchars($type) . '">';
        if ($title) {
            $html .= '<h3 class="widget-title">' . $title . '</h3>';
        }
        $html .= '<div class="widget-content">' . $content . '</div>';
        $html .= '</div>';

        return $html;
    }
} 