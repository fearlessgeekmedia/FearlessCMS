<?php

/**
 * Tests for MenuManager
 */

beforeEach(function () {
    require_once __DIR__ . '/../../includes/MenuManager.php';
    $this->menuManager = new MenuManager();
});

test('renderMenu returns HTML for existing menu', function () {
    $html = $this->menuManager->renderMenu('main');

    expect($html)->toContain('<ul class="main-nav">')
        ->and($html)->toContain('<a href="/"')
        ->and($html)->toContain('Home')
        ->and($html)->toContain('</ul>');
});

test('renderMenu returns empty string for non-existent menu', function () {
    $html = $this->menuManager->renderMenu('nonexistent');
    expect($html)->toBe('');
});

test('renderMenu renders nested children', function () {
    $menusFile = CONFIG_DIR . '/menus.json';
    $menus = [
        'test' => [
            'label' => 'Test Menu',
            'menu_class' => 'test-nav',
            'items' => [
                [
                    'label' => 'Parent',
                    'url' => '/parent',
                    'class' => '',
                    'target' => '',
                    'children' => [
                        ['label' => 'Child', 'url' => '/child', 'class' => '', 'target' => ''],
                    ],
                ],
            ],
        ],
    ];
    file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));

    $html = $this->menuManager->renderMenu('test');

    expect($html)->toContain('has-submenu')
        ->and($html)->toContain('<ul class="submenu">')
        ->and($html)->toContain('Child');
});

test('renderMenu escapes HTML in labels and URLs', function () {
    $menusFile = CONFIG_DIR . '/menus.json';
    $menus = [
        'xss' => [
            'label' => 'XSS Test',
            'menu_class' => 'nav',
            'items' => [
                [
                    'label' => '<script>alert(1)</script>',
                    'url' => '/safe',
                    'class' => '',
                    'target' => '',
                ],
            ],
        ],
    ];
    file_put_contents($menusFile, json_encode($menus, JSON_PRETTY_PRINT));

    $html = $this->menuManager->renderMenu('xss');

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

test('renderMenu returns empty string when menus file is missing', function () {
    $menusFile = CONFIG_DIR . '/menus.json';
    if (file_exists($menusFile)) {
        unlink($menusFile);
    }

    $mm = new MenuManager();
    expect($mm->renderMenu('main'))->toBe('');
});
