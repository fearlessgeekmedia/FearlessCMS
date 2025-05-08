<?php
class MenuManager {
    private $menusFile;

    public function __construct() {
        $this->menusFile = CONFIG_DIR . '/menus.json';
    }

    public function renderMenu($menuId = 'main') {
        if (!file_exists($this->menusFile)) {
            return '';
        }

        $menus = json_decode(file_get_contents($this->menusFile), true);
        if (!isset($menus[$menuId]['items'])) {
            return '';
        }

        $html = '<ul class="' . htmlspecialchars($menus[$menuId]['menu_class'] ?? 'main-nav') . '">';
        foreach ($menus[$menuId]['items'] as $item) {
            $label = htmlspecialchars($item['label']);
            $url = htmlspecialchars($item['url']);
            $class = htmlspecialchars($item['item_class'] ?? '');
            $target = $item['target'] ? ' target="' . htmlspecialchars($item['target']) . '"' : '';
            $html .= "<li><a href=\"$url\" class=\"$class\"$target>$label</a></li>";
        }
        $html .= '</ul>';

        return $html;
    }
} 