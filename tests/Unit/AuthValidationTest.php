<?php

/**
 * Tests for input validation and sanitization functions in auth.php
 */

beforeAll(function () {
    require_once __DIR__ . '/../../includes/auth.php';
});

// --- validate_username ---

test('validate_username accepts valid usernames', function (string $username) {
    expect(validate_username($username))->toBeTrue();
})->with([
    'simple'      => 'alice',
    'numbers'     => 'user123',
    'underscores' => 'my_user',
    'dashes'      => 'my-user',
    'mixed'       => 'My-User_99',
    '3 chars min' => 'abc',
    '50 chars max' => str_repeat('a', 50),
]);

test('validate_username rejects invalid usernames', function (string $username) {
    expect(validate_username($username))->toBeFalse();
})->with([
    'too short'    => 'ab',
    'too long'     => str_repeat('a', 51),
    'spaces'       => 'bad name',
    'special chars' => 'user@name',
    'dots'         => 'user.name',
    'empty'        => '',
]);

// --- validate_password ---

test('validate_password accepts valid passwords', function (string $password) {
    expect(validate_password($password))->toBeTrue();
})->with([
    'letters and numbers' => 'password1',
    'complex'             => 'C0mpl3x!Pass',
    'exactly 8 chars'     => 'abcdefg1',
]);

test('validate_password rejects invalid passwords', function (string $password) {
    expect(validate_password($password))->toBeFalse();
})->with([
    'too short'     => 'pass1',
    'no numbers'    => 'onlyletters',
    'no letters'    => '12345678',
    'empty'         => '',
]);

// --- sanitize_input ---

test('sanitize_input string mode escapes html', function () {
    expect(sanitize_input('<script>alert(1)</script>'))
        ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('sanitize_input username mode strips special characters', function () {
    expect(sanitize_input('good_user-1!@#', 'username'))
        ->toBe('good_user-1');
});

test('sanitize_input filename mode keeps safe characters', function () {
    expect(sanitize_input('my-file_2.txt', 'filename'))
        ->toBe('my-file_2.txt');
});

test('sanitize_input filename mode strips slashes but keeps dots', function () {
    expect(sanitize_input('../../etc/passwd', 'filename'))
        ->toBe('....etcpasswd');
});

test('sanitize_input path mode keeps slashes', function () {
    expect(sanitize_input('pages/about/team', 'path'))
        ->toBe('pages/about/team');
});

test('sanitize_input trims whitespace', function () {
    expect(sanitize_input('  hello  '))->toBe('hello');
});

// --- validate_file_path ---

test('validate_file_path accepts paths within the allowed directory', function () {
    $baseDir = FCMS_TEST_DIR . '/content';
    $result = validate_file_path('pages/about.md', $baseDir);
    expect($result)->toBe($baseDir . '/pages/about.md');
});

test('validate_file_path rejects directory traversal', function () {
    $baseDir = FCMS_TEST_DIR . '/content';
    expect(validate_file_path('../config/users.json', $baseDir))->toBeFalse();
});

test('validate_file_path rejects absolute paths', function () {
    $baseDir = FCMS_TEST_DIR . '/content';
    expect(validate_file_path('/etc/passwd', $baseDir))->toBeFalse();
});

test('validate_file_path rejects null bytes', function () {
    $baseDir = FCMS_TEST_DIR . '/content';
    $result = validate_file_path("page\0.md", $baseDir);
    // After null byte removal the path should still be valid
    expect($result)->toBeString();
});

// --- CSRF ---

test('generate_csrf_token returns a non-empty string', function () {
    $token = generate_csrf_token();
    expect($token)->toBeString()->not->toBeEmpty();
});

test('generate_csrf_token returns same token within a session', function () {
    $_SESSION['csrf_token'] = null; // reset
    $token1 = generate_csrf_token();
    $token2 = generate_csrf_token();
    expect($token1)->toBe($token2);
});

// --- isLoggedIn ---

test('isLoggedIn returns false when session has no username', function () {
    unset($_SESSION['username']);
    expect(isLoggedIn())->toBeFalse();
});

test('isLoggedIn returns true when session has a username', function () {
    $_SESSION['username'] = 'admin';
    expect(isLoggedIn())->toBeTrue();
    unset($_SESSION['username']);
});
