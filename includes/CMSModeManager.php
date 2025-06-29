<?php
/**
 * CMS Mode Manager
 * Handles three different modes for the CMS:
 * 1. full-featured: Full access to plugins and store
 * 2. hosting-service-plugin: Plugins available but no store access
 * 3. hosting-service-no-plugins: No plugin management, only pre-installed plugins
 */
class CMSModeManager {
    private $configFile;
    private $mode;
    private $modes = [
        'full-featured' => [
            'name' => 'Full Featured',
            'description' => 'Complete access to all features including plugin store and management',
            'can_manage_plugins' => true,
            'can_access_store' => true,
            'can_install_plugins' => true,
            'can_activate_plugins' => true,
            'can_deactivate_plugins' => true,
            'can_delete_plugins' => true
        ],
        'hosting-service-plugin' => [
            'name' => 'Hosting Service (Plugin Mode)',
            'description' => 'Plugins available but no access to the store. Users can activate/deactivate installed plugins.',
            'can_manage_plugins' => true,
            'can_access_store' => false,
            'can_install_plugins' => false,
            'can_activate_plugins' => true,
            'can_deactivate_plugins' => true,
            'can_delete_plugins' => false
        ],
        'hosting-service-no-plugins' => [
            'name' => 'Hosting Service (No Plugin Management)',
            'description' => 'No plugin management. Only pre-installed and activated plugins are available.',
            'can_manage_plugins' => false,
            'can_access_store' => false,
            'can_install_plugins' => false,
            'can_activate_plugins' => false,
            'can_deactivate_plugins' => false,
            'can_delete_plugins' => false
        ]
    ];

    public function __construct() {
        if (!defined('PROJECT_ROOT')) {
            throw new Exception("PROJECT_ROOT is not defined!");
        }
        
        $this->configFile = PROJECT_ROOT . '/config/cms_mode.json';
        $this->loadMode();
    }

    /**
     * Load the current CMS mode from configuration
     */
    private function loadMode() {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            $this->mode = $config['mode'] ?? 'full-featured';
        } else {
            // Default to full-featured mode
            $this->mode = 'full-featured';
            $this->saveMode();
        }
    }

    /**
     * Save the current CMS mode to configuration
     */
    private function saveMode() {
        $config = ['mode' => $this->mode];
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Get the current CMS mode
     */
    public function getCurrentMode() {
        return $this->mode;
    }

    /**
     * Set the CMS mode
     */
    public function setMode($mode) {
        if (!isset($this->modes[$mode])) {
            throw new Exception("Invalid CMS mode: $mode");
        }
        
        $this->mode = $mode;
        $this->saveMode();
        return true;
    }

    /**
     * Get all available modes
     */
    public function getAvailableModes() {
        return $this->modes;
    }

    /**
     * Check if a specific capability is allowed in the current mode
     */
    public function can($capability) {
        if (!isset($this->modes[$this->mode])) {
            return false;
        }
        
        return $this->modes[$this->mode][$capability] ?? false;
    }

    /**
     * Check if plugin management is allowed
     */
    public function canManagePlugins() {
        return $this->can('can_manage_plugins');
    }

    /**
     * Check if store access is allowed
     */
    public function canAccessStore() {
        return $this->can('can_access_store');
    }

    /**
     * Check if plugin installation is allowed
     */
    public function canInstallPlugins() {
        return $this->can('can_install_plugins');
    }

    /**
     * Check if plugin activation is allowed
     */
    public function canActivatePlugins() {
        return $this->can('can_activate_plugins');
    }

    /**
     * Check if plugin deactivation is allowed
     */
    public function canDeactivatePlugins() {
        return $this->can('can_deactivate_plugins');
    }

    /**
     * Check if plugin deletion is allowed
     */
    public function canDeletePlugins() {
        return $this->can('can_delete_plugins');
    }

    /**
     * Get the current mode information
     */
    public function getCurrentModeInfo() {
        return $this->modes[$this->mode] ?? null;
    }

    /**
     * Get mode name for display
     */
    public function getModeName() {
        $info = $this->getCurrentModeInfo();
        return $info['name'] ?? 'Unknown Mode';
    }

    /**
     * Get mode description for display
     */
    public function getModeDescription() {
        $info = $this->getCurrentModeInfo();
        return $info['description'] ?? '';
    }

    /**
     * Check if the system is in a restricted mode
     */
    public function isRestricted() {
        return $this->mode !== 'full-featured';
    }

    /**
     * Get a summary of current permissions
     */
    public function getPermissionsSummary() {
        $summary = [];
        foreach ($this->modes[$this->mode] as $key => $value) {
            if (strpos($key, 'can_') === 0) {
                $summary[$key] = $value;
            }
        }
        return $summary;
    }
} 