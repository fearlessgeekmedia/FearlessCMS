#!/usr/bin/env bash

echo "=== FearlessCMS Session Extension Fix ===\n"

# Check if we're in a Nix environment
if command -v nix-shell >/dev/null 2>&1; then
    echo "Nix detected. Checking if we should use nix-shell..."
    
    if [ -f "shell.nix" ]; then
        echo "Found shell.nix file. You should run this in a nix-shell environment."
        echo ""
        echo "To fix the session extension issue:"
        echo "1. Exit this shell"
        echo "2. Run: nix-shell"
        echo "3. Then run: php test_session_extension.php"
        echo ""
        echo "Or run directly: nix-shell --run 'php test_session_extension.php'"
        echo ""
    fi
fi

# Check if PHP is available
if ! command -v php >/dev/null 2>&1; then
    echo "❌ PHP is not available in PATH"
    echo ""
    echo "If you're using NixOS/Nix:"
    echo "  nix-shell --run 'php test_session_extension.php'"
    echo ""
    echo "If you're using a different system:"
    echo "  Install PHP with session extension enabled"
    exit 1
fi

echo "PHP found: $(php -v | head -n1)"
echo ""

# Run the session extension test
echo "Running session extension test..."
php test_session_extension.php

echo ""
echo "=== Additional Debugging ===\n"

# Check PHP modules
echo "Loaded PHP extensions:"
php -m | grep -E "(session|mbstring|openssl|pdo|sqlite)" | sed 's/^/  - /'

echo ""
echo "PHP configuration:"
php --ini | head -n5

echo ""
echo "If session extension is not loaded, you may need to:"
echo "1. Install PHP with session support"
echo "2. Enable session extension in php.ini"
echo "3. Restart your web server"
echo ""
echo "For NixOS/Nix users:"
echo "  nix-shell --run 'php -m | grep session'" 