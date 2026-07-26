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