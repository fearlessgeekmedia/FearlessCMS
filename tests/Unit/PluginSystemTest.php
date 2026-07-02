<?php

/**
 * Tests for the plugin and hook system in plugins.php
 */

try {
    require_once __DIR__ . '/../../includes/plugins.php';
} catch (Throwable $e) {
    echo "CRITICAL ERROR during plugins.php include: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(255);
}

beforeEach(function () {
    // Reset globals
    $GLOBALS['fcms_hooks'] = [
        'init' => [],
        'test_hook' => [],
        'filter_hook' => []
    ];
    $GLOBALS['fcms_admin_sections'] = [];
});

test('fcms_add_hook adds a callback', function () {
    $called = false;
    fcms_add_hook('test_hook', function() use (&$called) {
        $called = true;
    });
    
    fcms_do_hook('test_hook');
    expect($called)->toBeTrue();
});

test('fcms_do_hook passes arguments', function () {
    $passedArg = null;
    fcms_add_hook('test_hook', function($arg) use (&$passedArg) {
        $passedArg = $arg;
    });
    
    fcms_do_hook('test_hook', 'hello');
    expect($passedArg)->toBe('hello');
});

test('fcms_do_hook stops on first non-null return', function () {
    fcms_add_hook('test_hook', function() {
        return 'first';
    });
    fcms_add_hook('test_hook', function() {
        return 'second';
    });
    
    $result = fcms_do_hook('test_hook');
    expect($result)->toBe('first');
});

test('fcms_apply_filter modifies value through chain', function () {
    fcms_add_hook('filter_hook', function($val) {
        return $val . ' branch 1';
    });
    fcms_add_hook('filter_hook', function($val) {
        return $val . ' branch 2';
    });
    
    $result = fcms_apply_filter('filter_hook', 'root');
    expect($result)->toBe('root branch 1 branch 2');
});

test('fcms_do_hook_ref modifies by reference', function () {
    fcms_add_hook('ref_hook', function(&$val) {
        $val = 'modified';
    });
    
    $myVar = 'original';
    fcms_do_hook_ref('ref_hook', $myVar);
    expect($myVar)->toBe('modified');
});

test('fcms_register_admin_section adds to global sections', function () {
    fcms_register_admin_section('my_section', [
        'label' => 'My Section',
        'menu_order' => 50
    ]);
    
    expect($GLOBALS['fcms_admin_sections'])->toHaveKey('my_section')
        ->and($GLOBALS['fcms_admin_sections']['my_section']['label'])->toBe('My Section');
});
