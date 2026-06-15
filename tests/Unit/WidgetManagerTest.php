<?php

/**
 * Tests for WidgetManager
 */

beforeEach(function () {
    // Ensure clean state
    $widgetsFile = FCMS_TEST_DIR . '/config/widgets.json';
    if (file_exists($widgetsFile)) {
        unlink($widgetsFile);
    }
    
    $sidebarsFile = FCMS_TEST_DIR . '/config/sidebars.json';
    if (file_exists($sidebarsFile)) {
        unlink($sidebarsFile);
    }

    require_once __DIR__ . '/../../includes/WidgetManager.php';
    $this->widgetManager = new WidgetManager();
});

test('renderSidebar returns empty string when no widgets found', function () {
    expect($this->widgetManager->renderSidebar('nonexistent'))->toBe('');
});

test('renderSidebar renders HTML widget', function () {
    $widgetsData = [
        'main_sidebar' => [
            'widgets' => [
                [
                    'type' => 'html',
                    'title' => 'Test Widget',
                    'content' => '<p>Hello World</p>'
                ]
            ]
        ]
    ];
    file_put_contents(FCMS_TEST_DIR . '/config/widgets.json', json_encode($widgetsData));
    
    $html = $this->widgetManager->renderSidebar('main_sidebar');
    
    expect($html)->toContain('widget widget-html')
        ->and($html)->toContain('Test Widget')
        ->and($html)->toContain('<p>Hello World</p>');
});

test('renderSidebar renders regular text widget with escaping', function () {
    $widgetsData = [
        'main_sidebar' => [
            'widgets' => [
                [
                    'type' => 'text',
                    'title' => 'Text Widget',
                    'content' => '<b>Bold Text</b>'
                ]
            ]
        ]
    ];
    file_put_contents(FCMS_TEST_DIR . '/config/widgets.json', json_encode($widgetsData));
    
    $html = $this->widgetManager->renderSidebar('main_sidebar');
    
    expect($html)->toContain('&lt;b&gt;Bold Text&lt;/b&gt;')
        ->and($html)->not->toContain('<b>Bold Text</b>');
});

test('renderSidebar uses sidebars.json if it exists', function () {
    $sidebarsData = [
        'custom_sidebar' => [
            'widgets' => [
                [
                    'type' => 'html',
                    'title' => 'From Sidebars JSON',
                    'content' => 'Content'
                ]
            ]
        ]
    ];
    file_put_contents(FCMS_TEST_DIR . '/config/sidebars.json', json_encode($sidebarsData));
    
    $html = $this->widgetManager->renderSidebar('custom_sidebar');
    expect($html)->toContain('From Sidebars JSON');
});

test('renderDocumentationNavWidget renders list from documentation-nav.json', function () {
    $navData = [
        'main-nav' => [
            ['label' => 'Doc 1', 'url' => '/doc1', 'description' => 'Desc 1'],
            ['label' => 'Doc 2', 'url' => '/doc2', 'description' => 'Desc 2']
        ]
    ];
    file_put_contents(FCMS_TEST_DIR . '/config/documentation-nav.json', json_encode($navData));
    
    $widgetsData = [
        'sidebar' => [
            'widgets' => [
                [
                    'type' => 'documentation-nav',
                    'title' => 'Docs',
                    'content' => 'main-nav'
                ]
            ]
        ]
    ];
    file_put_contents(FCMS_TEST_DIR . '/config/widgets.json', json_encode($widgetsData));
    
    $html = $this->widgetManager->renderSidebar('sidebar');
    
    expect($html)->toContain('widget widget-documentation-nav')
        ->and($html)->toContain('Doc 1')
        ->and($html)->toContain('href="/doc1"')
        ->and($html)->toContain('Doc 2')
        ->and($html)->toContain('href="/doc2"');
});
