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

class PageHierarchy {
    private $contentDir;
    
    public function __construct($contentDir) {
        $this->contentDir = $contentDir;
    }
    
    public function createPage($path, $content) {
        $path = trim($path, '/');
        $parts = explode('/', $path);
        $filename = array_pop($parts);
        
        // Create directories if needed
        $currentPath = $this->contentDir;
        foreach ($parts as $part) {
            $currentPath .= '/' . $part;
            if (!is_dir($currentPath)) {
                mkdir($currentPath, 0755, true);
            }
        }
        
        // Save the file
        return file_put_contents($currentPath . '/' . $filename . '.md', $content);
    }
    
    public function getAllPages() {
        $pages = [];
        $this->scanDirectory($this->contentDir, '', $pages);
        return $pages;
    }
    
    private function scanDirectory($dir, $path, &$pages) {
        foreach (glob($dir . '/*') as $item) {
            if (is_dir($item)) {
                $dirName = basename($item);
                $this->scanDirectory($item, $path . '/' . $dirName, $pages);
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'md') {
                $filename = basename($item);
                $slug = basename($filename, '.md');
                $fullPath = $path ? $path . '/' . $slug : $slug;
                
                $content = file_get_contents($item);
                $title = $slug;
                
                if (preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches)) {
                    $metadata = json_decode($matches[1], true);
                    if ($metadata && isset($metadata['title'])) {
                        $title = $metadata['title'];
                    }
                }
                
                $pages[$fullPath] = [
                    'title' => $title,
                    'path' => $fullPath,
                    'file' => $item,
                    'parent' => $path ?: null
                ];
            }
        }
    }
}

