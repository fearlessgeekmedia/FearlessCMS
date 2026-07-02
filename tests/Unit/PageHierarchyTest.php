<?php

/**
 * Tests for PageHierarchy
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

    require_once __DIR__ . '/../../includes/PageHierarchy.php';
    $this->hierarchy = new PageHierarchy(FCMS_TEST_DIR . '/content');
});

test('createPage writes a markdown file', function () {
    $this->hierarchy->createPage('about', '# About');

    $file = FCMS_TEST_DIR . '/content/about.md';
    expect(file_exists($file))->toBeTrue()
        ->and(file_get_contents($file))->toBe('# About');
});

test('createPage creates nested directories', function () {
    $this->hierarchy->createPage('docs/guides/getting-started', '# Guide');

    $file = FCMS_TEST_DIR . '/content/docs/guides/getting-started.md';
    expect(file_exists($file))->toBeTrue();
});

test('getAllPages returns all pages', function () {
    createTestContent('home.md', 'Home', ['title' => 'Home']);
    createTestContent('about.md', 'About', ['title' => 'About Us']);

    $pages = $this->hierarchy->getAllPages();

    expect($pages)->toHaveCount(2)
        ->and(array_keys($pages))->toContain('home')
        ->and(array_keys($pages))->toContain('about');
});

test('getAllPages extracts title from frontmatter', function () {
    createTestContent('contact.md', 'Content', ['title' => 'Contact Us']);

    $pages = $this->hierarchy->getAllPages();

    expect($pages['contact']['title'])->toBe('Contact Us');
});

test('getAllPages uses slug as title when no frontmatter', function () {
    file_put_contents(FCMS_TEST_DIR . '/content/plain-page.md', 'No frontmatter');

    $pages = $this->hierarchy->getAllPages();

    expect($pages['plain-page']['title'])->toBe('plain-page');
});

test('getAllPages finds nested pages', function () {
    createTestContent('docs/intro.md', 'Intro', ['title' => 'Introduction']);

    $pages = $this->hierarchy->getAllPages();

    expect($pages)->toHaveKey('/docs/intro')
        ->and($pages['/docs/intro']['parent'])->toBe('/docs');
});
