<?php

/**
 * Tests for user management functions in functions.php
 */

beforeEach(function () {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';

    // Start with an empty users file
    $usersFile = CONFIG_DIR . '/users.json';
    file_put_contents($usersFile, json_encode([], JSON_PRETTY_PRINT));
});

test('addUser creates a new user', function () {
    $result = addUser('testuser', 'Password1', 'editor');
    expect($result)->toBeTrue();

    $users = getUsers();
    expect($users)->toHaveCount(1)
        ->and($users[0]['username'])->toBe('testuser')
        ->and($users[0]['role'])->toBe('editor');
});

test('addUser hashes the password', function () {
    addUser('hashtest', 'MyPassword1', 'editor');

    $users = getUsers();
    expect($users[0]['password'])->not->toBe('MyPassword1')
        ->and(password_verify('MyPassword1', $users[0]['password']))->toBeTrue();
});

test('addUser rejects duplicate username', function () {
    addUser('dupe', 'Password1', 'editor');
    $result = addUser('dupe', 'Password2', 'admin');
    expect($result)->toBeFalse();

    expect(getUsers())->toHaveCount(1);
});

test('getUsers returns empty array when no users file', function () {
    unlink(CONFIG_DIR . '/users.json');
    expect(getUsers())->toBe([]);
});

test('deleteUser removes a user', function () {
    addUser('toremove', 'Password1', 'editor');
    $users = getUsers();
    $userId = $users[0]['id'];

    $result = deleteUser($userId);
    expect($result)->toBeTrue()
        ->and(getUsers())->toHaveCount(0);
});

test('deleteUser returns false for non-existent user', function () {
    expect(deleteUser('nonexistent-id'))->toBeFalse();
});

test('updateUser changes username', function () {
    addUser('original', 'Password1', 'editor');
    $users = getUsers();
    $userId = $users[0]['id'];

    updateUser($userId, ['username' => 'renamed']);

    $updated = getUsers();
    expect($updated[0]['username'])->toBe('renamed');
});

test('updateUser changes password', function () {
    addUser('pwuser', 'OldPassword1', 'editor');
    $users = getUsers();
    $userId = $users[0]['id'];

    updateUser($userId, ['password' => 'NewPassword2']);

    $updated = getUsers();
    expect(password_verify('NewPassword2', $updated[0]['password']))->toBeTrue();
});

test('verifyCredentials returns user on correct login', function () {
    addUser('loginuser', 'CorrectPass1', 'administrator');

    $result = verifyCredentials('loginuser', 'CorrectPass1');
    expect($result)->toBeArray()
        ->and($result['username'])->toBe('loginuser');
});

test('verifyCredentials returns false on wrong password', function () {
    addUser('loginuser2', 'CorrectPass1', 'administrator');

    $result = verifyCredentials('loginuser2', 'WrongPass1');
    expect($result)->toBeFalse();
});

test('verifyCredentials returns false for non-existent user', function () {
    expect(verifyCredentials('ghost', 'Password1'))->toBeFalse();
});
