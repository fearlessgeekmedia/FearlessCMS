<?php
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

