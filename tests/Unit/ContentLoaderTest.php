<?php

/**
 * Tests for ContentLoader
 */

beforeEach(function () {
    // Clean content directory
    $contentDir = FCMS_TEST_DIR . '/content';
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($contentDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item) : unlink($item);
    }

    // Load dependencies
    require_once __DIR__ . '/../../includes/Parsedown.php';

    // Provide minimal hook stubs so ContentLoader works without the full plugin chain
    if (!isset($GLOBALS['fcms_hooks'])) {
        $GLOBALS['fcms_hooks'] = [];
    }
    if (!function_exists('fcms_apply_filter')) {
        function fcms_apply_filter($hook, $value, ...$args) { return $value; }
    }
    if (!function_exists('fcms_do_hook_ref')) {
        function fcms_do_hook_ref($hook, &...$args) {}
    }

    require_once __DIR__ . '/../../includes/DemoModeManager.php';
    require_once __DIR__ . '/../../includes/ContentLoader.php';

    $this->demoManager = new DemoModeManager();
    $this->loader = new ContentLoader($this->demoManager);
});

test('loadContent extracts JSON frontmatter', function () {
    $file = createTestContent('home.md', 'Hello world', [
        'title' => 'Home Page',
        'description' => 'Welcome',
    ]);

    $result = $this->loader->loadContent($file);

    expect($result['title'])->toBe('Home Page')
        ->and($result['description'])->toBe('Welcome')
        ->and($result['content'])->toContain('Hello world');
});

test('loadContent uses filename as title when no frontmatter', function () {
    $file = FCMS_TEST_DIR . '/content/about-us.md';
    file_put_contents($file, 'Some content here');

    $result = $this->loader->loadContent($file);

    expect($result['title'])->toBe('About Us');
});

test('loadContent defaults editor_mode to markdown', function () {
    $file = createTestContent('test.md', 'content');

    $result = $this->loader->loadContent($file);
    expect($result['editor_mode'])->toBe('markdown');
});

test('loadContent respects editor_mode from frontmatter', function () {
    $file = createTestContent('test.md', '<p>html content</p>', [
        'title' => 'HTML Page',
        'editor_mode' => 'html',
    ]);

    $result = $this->loader->loadContent($file);
    expect($result['editor_mode'])->toBe('html');
});

test('processContent renders markdown to HTML', function () {
    $html = $this->loader->processContent("# Heading\n\nParagraph", 'markdown');

    expect($html)->toContain('<h1>Heading</h1>')
        ->and($html)->toContain('<p>Paragraph</p>');
});

test('processContent passes through HTML content', function () {
    $raw = '<div class="custom"><p>Already HTML</p></div>';
    $html = $this->loader->processContent($raw, 'html');

    expect($html)->toContain('<div class="custom">');
});

test('findContentFile returns false for non-existent page', function () {
    $result = $this->loader->findContentFile('does-not-exist');
    expect($result)->toBeFalse();
});

test('findContentFile finds an existing content file', function () {
    createTestContent('about.md', 'About page content', ['title' => 'About']);

    $result = $this->loader->findContentFile('about');
    expect($result)->toBeString()
        ->and(file_exists($result))->toBeTrue();
});
