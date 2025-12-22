#!/usr/bin/env bash

# Change to the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR" || {
    echo "Error: Could not change to script directory: $SCRIPT_DIR"
    exit 1
}

# clear previous logs if any
rm -f serve-log.tmp

# set default port or use ENV variable
port=${PORT:-8000}

if [ "$1" == "--port" ]; then
    port=$2
fi

# check if port is in use
if lsof -i:$port > /dev/null
then
    echo "Port $port is already in use"
    exit
fi

# check if php is installed and use nix-shell if needed
if ! command -v php &> /dev/null
then
    echo "PHP not found in PATH, using nix-shell with php83..."
    # Use nix-shell to get PHP with all required extensions
    nix-shell -p php83 --pure --run "php -c php-config/99-custom.ini -S localhost:$port router.php" > serve-log.tmp 2>&1 &
    pid=$!
    echo "Server started on port $port with PID $pid using nix-shell PHP"
    echo "To stop the server, run: kill $pid"
    echo "To view logs, run: tail -f serve-log.tmp"
    echo "To access the server, open http://localhost:$port in your browser"
    echo "🚀"
    wait $pid
    exit
fi

# check if PHP has session extension
if ! php -m | grep -q session; then
    echo "Warning: PHP session extension not found. Using nix-shell with php83 for proper session support..."
    # Use nix-shell to get PHP with session extension
    nix-shell -p php83 --pure --run "php -c php-config/99-custom.ini -S localhost:$port router.php" > serve-log.tmp 2>&1 &
    pid=$!
    echo "Server started on port $port with PID $pid using nix-shell PHP"
    echo "To stop the server, run: kill $pid"
    echo "To view logs, run: tail -f serve-log.tmp"
    echo "To access the server, open http://localhost:$port in your browser"
    echo "🚀"
    wait $pid
    exit
fi

# check if custom PHP config exists
if [ ! -f "php-config/99-custom.ini" ]; then
    echo "Warning: Custom PHP configuration not found. Sessions may not work properly."
    echo "Consider creating php-config/99-custom.ini with proper session settings."
    php_config=""
else
    php_config="-c php-config/99-custom.ini"
    echo "Using custom PHP configuration for proper session handling"
fi

# Always use nix-shell with custom config for consistent session handling
echo "Using nix-shell with php83 and custom configuration for proper session support..."
nix-shell -p php83 --pure --run "php $php_config -S localhost:$port router.php" > serve-log.tmp 2>&1 &
pid=$!
echo "Server started on port $port with PID $pid"
echo "To stop the server, run: kill $pid"
echo "To view logs, run: tail -f serve-log.tmp"
echo "To access the server, open http://localhost:$port in your browser"
echo "🚀"
# retain session of the process
wait $pid