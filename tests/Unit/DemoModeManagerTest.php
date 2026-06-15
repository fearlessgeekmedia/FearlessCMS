<?php

/**
 * Tests for DemoModeManager
 */

beforeEach(function () {
    // Ensure clean state
    $configFile = FCMS_TEST_DIR . '/config/demo_mode.json';
    if (file_exists($configFile)) {
        unlink($configFile);
    }

    $usersFile = FCMS_TEST_DIR . '/config/users.json';
    if (file_exists($usersFile)) {
        unlink($usersFile);
    }

    // Reset session
    $_SESSION = [];

    require_once __DIR__ . '/../../includes/DemoModeManager.php';
    $this->demoModeManager = new DemoModeManager();
});

test('DemoModeManager defaults to disabled', function () {
    expect($this->demoModeManager->isEnabled())->toBeFalse();
});

test('DemoModeManager can enable demo mode', function () {
    $this->demoModeManager->enable();
    expect($this->demoModeManager->isEnabled())->toBeTrue();
    
    // Enabling demo mode should create a demo user in users.json
    $usersFile = FCMS_TEST_DIR . '/config/users.json';
    expect(file_exists($usersFile))->toBeTrue();
    
    $users = json_decode(file_get_contents($usersFile), true);
    $demoUser = array_filter($users, fn($u) => $u['username'] === 'demo');
    expect($demoUser)->not->toBeEmpty();
});

test('DemoModeManager can disable demo mode', function () {
    $this->demoModeManager->enable();
    $this->demoModeManager->disable();
    expect($this->demoModeManager->isEnabled())->toBeFalse();
    
    // Disabling demo mode should remove the demo user
    $usersFile = FCMS_TEST_DIR . '/config/users.json';
    $users = json_decode(file_get_contents($usersFile), true);
    $demoUser = array_filter($users, fn($u) => $u['username'] === 'demo');
    expect($demoUser)->toBeEmpty();
});

test('isDemoUser returns true for demo session', function () {
    expect($this->demoModeManager->isDemoUser())->toBeFalse();
    
    $_SESSION['demo_mode'] = true;
    expect($this->demoModeManager->isDemoUser())->toBeTrue();
    
    unset($_SESSION['demo_mode']);
    $_SESSION['username'] = 'demo';
    expect($this->demoModeManager->isDemoUser())->toBeTrue();
});

test('startDemoSession sets session flags', function () {
    $this->demoModeManager->startDemoSession('demo');
    
    expect($_SESSION['demo_mode'])->toBeTrue();
    expect($_SESSION['demo_session_id'])->toStartWith('demo_');
    expect($_SESSION['demo_start_time'])->toBeGreaterThan(0);
});

test('startDemoSession fails for non-demo username', function () {
    $result = $this->demoModeManager->startDemoSession('other');
    expect($result)->toBeFalse();
    expect($_SESSION)->toBeEmpty();
});

test('createDemoContentFile creates files in demo content dir', function () {
    $this->demoModeManager->startDemoSession('demo');
    
    $result = $this->demoModeManager->createDemoContentFile('test-page', 'Test Title', 'Test Content');
    expect($result)->toBeTrue();
    
    $expectedPath = FCMS_TEST_DIR . '/demo_content/pages/test-page.md';
    expect(file_exists($expectedPath))->toBeTrue();
    
    $content = file_get_contents($expectedPath);
    expect($content)->toContain('Test Title')
        ->and($content)->toContain('Test Content')
        ->and($content)->toContain('demo_content": true');
});

test('cleanupDemoContent removes only session files', function () {
    $this->demoModeManager->startDemoSession('demo');
    $sessionId = $_SESSION['demo_session_id'];
    
    $this->demoModeManager->createDemoContentFile('page1', 'Title 1', 'Content 1');
    
    // Simulate another session
    $otherSessionId = 'demo_other123';
    $otherFile = FCMS_TEST_DIR . '/demo_content/pages/page2.md';
    $otherContent = "<!-- json " . json_encode(['demo_content' => true, 'demo_session_id' => $otherSessionId]) . " -->Content 2";
    file_put_contents($otherFile, $otherContent);
    
    expect(file_exists(FCMS_TEST_DIR . '/demo_content/pages/page1.md'))->toBeTrue();
    expect(file_exists($otherFile))->toBeTrue();
    
    $this->demoModeManager->cleanupDemoContent();
    
    // page1 should be gone, page2 should remain
    expect(file_exists(FCMS_TEST_DIR . '/demo_content/pages/page1.md'))->toBeFalse();
    expect(file_exists($otherFile))->toBeTrue();
});

test('isDemoSessionExpired checks timeout', function () {
    $_SESSION['demo_mode'] = true;
    $_SESSION['demo_start_time'] = time() - 4000; // Over default 3600
    
    expect($this->demoModeManager->isDemoSessionExpired())->toBeTrue();
    
    $_SESSION['demo_start_time'] = time() - 100;
    expect($this->demoModeManager->isDemoSessionExpired())->toBeFalse();
});
