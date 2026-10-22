<?php

/**
 * Tests for CMSModeManager
 */

beforeEach(function () {
    // Ensure a clean config for every test
    $configFile = FCMS_TEST_DIR . '/config/cms_mode.json';
    if (file_exists($configFile)) {
        unlink($configFile);
    }

    require_once __DIR__ . '/../../includes/CMSModeManager.php';
    $this->cmsModeManager = new CMSModeManager();
});

test('CMSModeManager defaults to full-featured mode', function () {
    expect($this->cmsModeManager->getCurrentMode())->toBe('full-featured');
    expect($this->cmsModeManager->isRestricted())->toBeFalse();
});

test('CMSModeManager can set and get mode', function () {
    $this->cmsModeManager->setMode('hosting-service-plugins');
    expect($this->cmsModeManager->getCurrentMode())->toBe('hosting-service-plugins');
    expect($this->cmsModeManager->isRestricted())->toBeTrue();
    
    $this->cmsModeManager->setMode('hosting-service-no-plugins');
    expect($this->cmsModeManager->getCurrentMode())->toBe('hosting-service-no-plugins');
});

test('CMSModeManager throws exception on invalid mode', function () {
    $this->cmsModeManager->setMode('invalid-mode');
})->throws(Exception::class, "Invalid CMS mode: invalid-mode");

test('CMSModeManager capabilities for full-featured mode', function () {
    $this->cmsModeManager->setMode('full-featured');
    
    expect($this->cmsModeManager->canManagePlugins())->toBeTrue();
    expect($this->cmsModeManager->canAccessStore())->toBeTrue();
    expect($this->cmsModeManager->canInstallPlugins())->toBeTrue();
    expect($this->cmsModeManager->canActivatePlugins())->toBeTrue();
    expect($this->cmsModeManager->canDeactivatePlugins())->toBeTrue();
    expect($this->cmsModeManager->canDeletePlugins())->toBeTrue();
    expect($this->cmsModeManager->canManageFiles())->toBeTrue();
    expect($this->cmsModeManager->canUploadFiles())->toBeTrue();
    expect($this->cmsModeManager->canUploadContentImages())->toBeTrue();
});

test('CMSModeManager capabilities for hosting-service-plugins mode', function () {
    $this->cmsModeManager->setMode('hosting-service-plugins');
    
    expect($this->cmsModeManager->canManagePlugins())->toBeTrue();
    expect($this->cmsModeManager->canAccessStore())->toBeFalse();
    expect($this->cmsModeManager->canInstallPlugins())->toBeFalse();
    expect($this->cmsModeManager->canActivatePlugins())->toBeTrue();
    expect($this->cmsModeManager->canDeactivatePlugins())->toBeTrue();
    expect($this->cmsModeManager->canDeletePlugins())->toBeFalse();
    expect($this->cmsModeManager->canManageFiles())->toBeTrue();
    expect($this->cmsModeManager->canUploadFiles())->toBeTrue();
    expect($this->cmsModeManager->canUploadContentImages())->toBeTrue();
});

test('CMSModeManager capabilities for hosting-service-no-plugins mode', function () {
    $this->cmsModeManager->setMode('hosting-service-no-plugins');
    
    expect($this->cmsModeManager->canManagePlugins())->toBeFalse();
    expect($this->cmsModeManager->canAccessStore())->toBeFalse();
    expect($this->cmsModeManager->canInstallPlugins())->toBeFalse();
    expect($this->cmsModeManager->canActivatePlugins())->toBeFalse();
    expect($this->cmsModeManager->canDeactivatePlugins())->toBeFalse();
    expect($this->cmsModeManager->canDeletePlugins())->toBeFalse();
    expect($this->cmsModeManager->canManageFiles())->toBeFalse();
    expect($this->cmsModeManager->canUploadFiles())->toBeFalse();
    expect($this->cmsModeManager->canUploadContentImages())->toBeFalse();
});

test('CMSModeManager returns correct mode name and description', function () {
    $this->cmsModeManager->setMode('full-featured');
    expect($this->cmsModeManager->getModeName())->toBe('Full Featured');
    expect($this->cmsModeManager->getModeDescription())->toContain('Complete access');
    
    $this->cmsModeManager->setMode('hosting-service-plugins');
    expect($this->cmsModeManager->getModeName())->toBe('Hosting Service (Plugin Mode)');
});

test('CMSModeManager persists mode to file', function () {
    $this->cmsModeManager->setMode('hosting-service-plugins');
    
    // Create a new instance to check persistence
    $newManager = new CMSModeManager();
    expect($newManager->getCurrentMode())->toBe('hosting-service-plugins');
});

test('getPermissionsSummary returns only capability flags', function () {
    $summary = $this->cmsModeManager->getPermissionsSummary();
    
    expect($summary)->toBeArray();
    foreach (array_keys($summary) as $key) {
        expect($key)->toStartWith('can_');
    }
});
