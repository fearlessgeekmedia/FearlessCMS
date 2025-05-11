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
            $label = htmlspecialchars($item['label']);
            $url = htmlspecialchars($item['url']);
            $class = htmlspecialchars($item['class'] ?? '');
            $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $html .= "<li><a href=\"$url\" class=\"$class\"$target>$label</a></li>";
        }
        $html .= '</ul>';

        error_log("Generated menu HTML: " . $html);
        return $html;
    }
} 