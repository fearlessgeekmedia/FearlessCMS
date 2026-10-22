<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

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