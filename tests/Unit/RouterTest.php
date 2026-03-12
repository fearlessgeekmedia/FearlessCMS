<?php

/**
 * Tests for Router
 */

beforeEach(function () {
    require_once __DIR__ . '/../../includes/DemoModeManager.php';
    require_once __DIR__ . '/../../includes/Router.php';

    $this->demoManager = new DemoModeManager();
    $this->config = ['site_name' => 'TestCMS'];
});

test('Router parses basic request path', function () {
    $_SERVER['REQUEST_URI'] = '/about';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('about');
});

test('Router defaults empty path to home', function () {
    $_SERVER['REQUEST_URI'] = '/';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('home');
    
    $_SERVER['REQUEST_URI'] = '';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('home');
});

test('Router removes query parameters', function () {
    $_SERVER['REQUEST_URI'] = '/contact?name=test&email=test@example.com';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('contact');
});

test('Router handles subdirectories in URI', function () {
    $_SERVER['REQUEST_URI'] = '/blog/post-1';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('blog/post-1');
});

test('Router handles hstn.me subdomain prefix', function () {
    $_SERVER['REQUEST_URI'] = 'fearlesscms.hstn.me/some-page';
    $router = new Router($this->demoManager, $this->config);
    expect($router->getRequestPath())->toBe('some-page');
});

test('isPreviewRequest returns true for preview paths', function () {
    $_SERVER['REQUEST_URI'] = '/_preview/my-draft';
    $router = new Router($this->demoManager, $this->config);
    expect($router->isPreviewRequest())->toBeTrue();
    
    $_SERVER['REQUEST_URI'] = '/about';
    $router = new Router($this->demoManager, $this->config);
    expect($router->isPreviewRequest())->toBeFalse();
});

test('getDefaultTemplate returns home for home path', function () {
    $router = new Router($this->demoManager, $this->config);
    expect($router->getDefaultTemplate('home'))->toBe('home');
    expect($router->getDefaultTemplate('about'))->toBe('page-with-sidebar');
});
