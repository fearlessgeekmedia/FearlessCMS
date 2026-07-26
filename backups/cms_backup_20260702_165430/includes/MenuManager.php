<?php
class MenuManager {
    private $menusFile;

    public function __construct() {
        $this->menusFile = CONFIG_DIR . '/menus.json';
    }

    public function renderMenu($menuId = 'main') {
        error_log("Rendering menu: " . $menuId);
        
        if (!file_exists($this->menusFile)) {
            error_log("Menu file not found: " . $this->menusFile);
            return '';
        }

        $menus = json_decode(file_get_contents($this->menusFile), true);
        error_log("Loaded menus: " . print_r($menus, true));
        
        if (!isset($menus[$menuId]['items'])) {
            error_log("Menu not found or has no items: " . $menuId);
            return '';
        }

        $html = '<ul class="' . htmlspecialchars($menus[$menuId]['menu_class'] ?? 'main-nav') . '">';
        foreach ($menus[$menuId]['items'] as $item) {
            $html .= $this->renderMenuItem($item);
        }
        $html .= '</ul>';

        error_log("Generated menu HTML: " . $html);
        return $html;
    }

    private function renderMenuItem($item) {
        $label = htmlspecialchars($item['label']);
        $url = htmlspecialchars($item['url']);
        $class = htmlspecialchars($item['class'] ?? '');
        $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
        
        // Check if item has children
        $hasChildren = isset($item['children']) && !empty($item['children']);
        
        $html = '<li class="' . ($hasChildren ? 'has-submenu' : '') . '">';
        $html .= "<a href=\"$url\" class=\"$class\"$target>$label</a>";
        
        // Render children if they exist
        if ($hasChildren) {
            $html .= '<ul class="submenu">';
            foreach ($item['children'] as $child) {
                $html .= $this->renderMenuItem($child);
            }
            $html .= '</ul>';
        }
        
        $html .= '</li>';
        return $html;
    }
} 