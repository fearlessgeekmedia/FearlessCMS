<?php

/**
 * FearlessCMS
 *
 * Copyright (C) 2026 FearlessCMS
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * As a special exception to the GNU General Public License version 2
 * ("GPL"), the copyright holder(s) of FearlessCMS give you permission
 * to link, load, or combine this software with independent modules
 * ("Themes" and "Plugins") that are not derived from or based on this
 * software, regardless of the license terms of those Themes or
 * Plugins, including proprietary or commercial licenses, and to
 * convey the resulting combination under terms of your choice,
 * provided that you also convey the corresponding source code of the
 * FearlessCMS portions under the terms of the GPL.
 *
 * This exception does not affect any of your obligations under the
 * GPL with respect to the FearlessCMS core software itself, and it
 * does not extend to modifications of FearlessCMS core files — only
 * to independent Theme and Plugin modules that interact with
 * FearlessCMS through its documented Theme/Plugin API.
 */

/**
 * Authentication and user management functions
 */

require_once __DIR__ . '/config.php';

// Note: isLoggedIn() function is defined in auth.php

/**
 * Get the currently logged in user's data
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $usersFile = CONFIG_DIR . '/users.json';
    if (!file_exists($usersFile)) {
        return null;
    }
    
    $users = json_decode(file_get_contents($usersFile), true);
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Get all users
 * @return array Array of user data
 */
function getUsers() {
    $usersFile = CONFIG_DIR . '/users.json';
    if (!file_exists($usersFile)) {
        return [];
    }
    
    return json_decode(file_get_contents($usersFile), true);
}

/**
 * Save users data
 * @param array $users Array of user data
 * @return bool True if successful, false otherwise
 */
function saveUsers($users) {
    $usersFile = CONFIG_DIR . '/users.json';
    return file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Add a new user
 * @param string $username Username
 * @param string $password Password
 * @param string $role User role
 * @return bool True if successful, false otherwise
 */
function addUser($username, $password, $role) {
    $users = getUsers();
    
    // Check if username already exists
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return false;
        }
    }
    
    // Add new user
    $users[] = [
        'id' => uniqid(),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role
    ];
    
    return saveUsers($users);
}

/**
 * Update an existing user
 * @param string $userId User ID
 * @param array $data User data to update
 * @return bool True if successful, false otherwise
 */
function updateUser($userId, $data) {
    $users = getUsers();
    
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            if (isset($data['username'])) {
                $user['username'] = $data['username'];
            }
            if (isset($data['password'])) {
                $user['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            if (isset($data['role'])) {
                $user['role'] = $data['role'];
            }
            return saveUsers($users);
        }
    }
    
    return false;
}

/**
 * Delete a user
 * @param string $userId User ID
 * @return bool True if successful, false otherwise
 */
function deleteUser($userId) {
    $users = getUsers();
    
    foreach ($users as $key => $user) {
        if ($user['id'] === $userId) {
            unset($users[$key]);
            return saveUsers(array_values($users));
        }
    }
    
    return false;
}

/**
 * Verify user credentials
 * @param string $username Username
 * @param string $password Password
 * @return array|false User data if credentials are valid, false otherwise
 */
function verifyCredentials($username, $password) {
    $users = getUsers();
    
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    
    return false;
}

/**
 * Get all plugins
 * @return array Array of plugin information
 */
function getPlugins() {
    $plugins = [];
    $pluginsDir = PLUGINS_DIR;
    
    if (!is_dir($pluginsDir)) {
        return $plugins;
    }
    
    $pluginFiles = glob($pluginsDir . '/*/plugin.json');
    foreach ($pluginFiles as $pluginFile) {
        $pluginData = json_decode(file_get_contents($pluginFile), true);
        if ($pluginData) {
            $pluginDir = dirname($pluginFile);
            $pluginId = basename($pluginDir);
            $pluginData['id'] = $pluginId;
            $pluginData['active'] = isPluginActive($pluginId);
            $plugins[] = $pluginData;
        }
    }
    
    return $plugins;
}

/**
 * Check if a plugin is active
 * @param string $pluginId The plugin ID
 * @return bool True if the plugin is active
 */
function isPluginActive($pluginId) {
    $activePluginsFile = PLUGIN_CONFIG;
    if (!file_exists($activePluginsFile)) {
        return false;
    }
    
    $activePlugins = json_decode(file_get_contents($activePluginsFile), true);
    return in_array($pluginId, $activePlugins ?? []);
} 