<?php

/**
 * Tests for TemplateRenderer
 */

require_once __DIR__ . '/../../includes/MenuManager.php';
require_once __DIR__ . '/../../includes/WidgetManager.php';
require_once __DIR__ . '/../../includes/TemplateRenderer.php';

class MockMenuManager extends MenuManager {
    public function __construct() {}
    public function renderMenu($menuId = 'main') { return "<ul>Menu $menuId</ul>"; }
}

class MockWidgetManager extends WidgetManager {
    public function __construct() {}
    public function renderSidebar($name) { return "<aside>Sidebar $name</aside>"; }
}

beforeEach(function () {
    $this->menuManager = new MockMenuManager();
    $this->widgetManager = new MockWidgetManager();
    
    $this->theme = 'default';
    $this->themeOptions = ['primary_color' => '#ff0000', 'show_sidebar' => true];
    
    $this->renderer = new TemplateRenderer(
        $this->theme,
        $this->themeOptions,
        $this->menuManager,
        $this->widgetManager
    );
    
    // Create default theme template dir
    $this->templateDir = FCMS_TEST_DIR . '/themes/default/templates';
    if (!is_dir($this->templateDir)) {
        mkdir($this->templateDir, 0755, true);
    }
});

test('replaceVariables replaces simple placeholders', function () {
    $content = '<h1>{{title}}</h1><div>{{content}}</div>';
    $data = ['title' => 'My Title', 'content' => 'My Content'];
    
    $result = $this->renderer->replaceVariables($content, $data);
    
    expect($result)->toBe('<h1>My Title</h1><div>My Content</div>');
});

test('replaceVariables handles themeOptions', function () {
    $content = '<div style="color: {{themeOptions.primary_color}}">Text</div>';
    $data = ['themeOptions' => $this->themeOptions];
    
    $result = $this->renderer->replaceVariables($content, $data);
    
    expect($result)->toBe('<div style="color: #ff0000">Text</div>');
});

test('replaceVariables handles #if condition (true)', function () {
    $content = '{{#if show_sidebar}}Sidebar is on{{/if}}';
    $data = ['show_sidebar' => true];
    
    $result = $this->renderer->replaceVariables($content, $data);
    expect($result)->toBe('Sidebar is on');
});

test('replaceVariables handles #if condition (false) with else', function () {
    $content = '{{#if show_sidebar}}Sidebar is on{{else}}Sidebar is off{{/if}}';
    $data = ['show_sidebar' => false];
    
    $result = $this->renderer->replaceVariables($content, $data);
    expect($result)->toBe('Sidebar is off');
});

test('replaceVariables handles #each loop', function () {
    $content = '<ul>{{#each items}}<li>{{name}}</li>{{/each}}</ul>';
    $data = ['items' => [
        ['name' => 'Item 1'],
        ['name' => 'Item 2']
    ]];
    
    $result = $this->renderer->replaceVariables($content, $data);
    expect($result)->toBe('<ul><li>Item 1</li><li>Item 2</li></ul>');
});

test('replaceVariables renders sidebar via widgetManager', function () {
    $content = '<aside>{{sidebar=main_sidebar}}</aside>';
    $result = $this->renderer->replaceVariables($content, []);
    
    expect($result)->toBe('<aside><aside>Sidebar main_sidebar</aside></aside>');
});

test('render loads template file and replaces variables', function () {
    file_put_contents($this->templateDir . '/test_page.html', '<h1>{{title}}</h1>');
    
    $result = $this->renderer->render('test_page', ['title' => 'Hello World']);
    
    expect($result)->toContain('<h1>Hello World</h1>');
});

test('render injects tailwind if missing', function () {
    file_put_contents($this->templateDir . '/no_tailwind.html', '<html><head></head><body></body></html>');
    
    $result = $this->renderer->render('no_tailwind', []);
    
    expect($result)->toContain('link href="/public/css/output.css"');
});

test('includeModule includes and processes another file', function () {
    file_put_contents($this->templateDir . '/header.html', '<header>{{siteName}}</header>');
    
    $content = '<div>{{module=header.html}}</div>';
    $data = ['siteName' => 'Test Site'];
    
    $result = $this->renderer->replaceVariables($content, $data);
    expect($result)->toBe('<div><header>Test Site</header></div>');
});
