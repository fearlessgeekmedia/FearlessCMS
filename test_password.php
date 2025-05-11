<?php
$password = 'changeme123';
$hash = '$2y$12$mpPB80Y6.G79PyX.AsDGGeMny5hBxoT4pt/zh217NCQHAEg0wMbU.';

echo "Testing password verification:\n";
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "Verification result: " . (password_verify($password, $hash) ? "true" : "false") . "\n";

// Generate a new hash for comparison
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "\nNew hash generated: " . $new_hash . "\n";
echo "Verification with new hash: " . (password_verify($password, $new_hash) ? "true" : "false") . "\n"; 