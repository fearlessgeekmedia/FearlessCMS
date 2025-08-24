<?php
/**
 * Markdown to HTML Conversion Script
 * Converts all .md files in the content directory to HTML format
 * 
 * Usage: php convert_markdown_to_html.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$contentDir = __DIR__ . '/content';
$backupDir = __DIR__ . '/content_backup_' . date('Y-m-d_H-i-s');

// Check if content directory exists
if (!is_dir($contentDir)) {
    die("Content directory not found: $contentDir\n");
}

// Create backup directory
if (!mkdir($backupDir, 0755, true)) {
    die("Failed to create backup directory: $backupDir\n");
}

echo "Starting Markdown to HTML conversion...\n";
echo "Content directory: $contentDir\n";
echo "Backup directory: $backupDir\n\n";

// Function to convert Markdown to HTML
function markdownToHtml($markdown) {
    // Basic Markdown to HTML conversion
    $html = $markdown;
    
    // Headers
    $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
    
    // Bold and italic
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
    
    // Code blocks
    $html = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $html);
    $html = preg_replace('/`(.*?)`/s', '<code>$1</code>', $html);
    
    // Blockquotes
    $html = preg_replace('/^> (.*$)/m', '<blockquote>$1</blockquote>', $html);
    
    // Lists
    $html = preg_replace('/^\- (.*$)/m', '<li>$1</li>', $html);
    $html = preg_replace('/^(\d+)\. (.*$)/m', '<li>$2</li>', $html);
    
    // Wrap lists in ul/ol tags (simplified)
    $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
    
    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
    
    // Images
    $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);
    
    // Line breaks
    $html = preg_replace('/\n\n/', '</p><p>', $html);
    
    // Wrap in paragraphs
    $html = '<p>' . $html . '</p>';
    
    // Clean up empty paragraphs
    $html = str_replace('<p></p>', '', $html);
    $html = str_replace('<p><p>', '<p>', $html);
    $html = str_replace('</p></p>', '</p>', $html);
    
    return $html;
}

// Function to process a single file
function processFile($filePath, $backupDir) {
    $relativePath = str_replace(__DIR__ . '/content/', '', $filePath);
    $backupPath = $backupDir . '/' . $relativePath;
    
    echo "Processing: $relativePath\n";
    
    // Create backup
    $backupDirPath = dirname($backupPath);
    if (!is_dir($backupDirPath)) {
        mkdir($backupDirPath, 0755, true);
    }
    copy($filePath, $backupPath);
    echo "  ✓ Backed up to: $backupPath\n";
    
    // Read content
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "  ✗ Failed to read file\n";
        return false;
    }
    
    // Check if content has JSON frontmatter
    $hasFrontmatter = preg_match('/^<!--\s*json\s*(.*?)\s*-->/s', $content, $matches);
    $metadata = [];
    $markdownContent = $content;
    
    if ($hasFrontmatter) {
        $metadata = json_decode($matches[1], true) ?: [];
        // Remove frontmatter from content
        $markdownContent = preg_replace('/^<!--\s*json\s*.*?\s*-->\s*/s', '', $content);
        echo "  ✓ Found frontmatter with keys: " . implode(', ', array_keys($metadata)) . "\n";
    }
    
    // Convert Markdown to HTML
    $htmlContent = markdownToHtml($markdownContent);
    
    // Update metadata
    $metadata['editor_mode'] = 'html';
    $metadata['converted_from_markdown'] = true;
    $metadata['conversion_date'] = date('Y-m-d H:i:s');
    
    // Create new frontmatter
    $newFrontmatter = '<!-- json ' . json_encode($metadata, JSON_PRETTY_PRINT) . ' -->';
    
    // Combine frontmatter and HTML content
    $newContent = $newFrontmatter . "\n\n" . $htmlContent;
    
    // Write back to file
    if (file_put_contents($filePath, $newContent) !== false) {
        echo "  ✓ Converted to HTML successfully\n";
        return true;
    } else {
        echo "  ✗ Failed to write converted content\n";
        return false;
    }
}

// Function to find all Markdown files
function findMarkdownFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'md') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Find all Markdown files
$markdownFiles = findMarkdownFiles($contentDir);
echo "Found " . count($markdownFiles) . " Markdown files to convert.\n\n";

if (empty($markdownFiles)) {
    echo "No Markdown files found to convert.\n";
    exit(0);
}

// Process each file
$successCount = 0;
$errorCount = 0;

foreach ($markdownFiles as $filePath) {
    if (processFile($filePath, $backupDir)) {
        $successCount++;
    } else {
        $errorCount++;
    }
    echo "\n";
}

// Summary
echo "=== CONVERSION SUMMARY ===\n";
echo "Total files: " . count($markdownFiles) . "\n";
echo "Successful conversions: $successCount\n";
echo "Failed conversions: $errorCount\n";
echo "Backup location: $backupDir\n";
echo "\n";

if ($errorCount > 0) {
    echo "⚠️  Some files failed to convert. Check the backup directory for original files.\n";
    exit(1);
} else {
    echo "✅ All files converted successfully!\n";
    echo "Your Markdown files have been converted to HTML and backed up.\n";
    echo "You can now edit content using the HTML editor.\n";
    exit(0);
} 