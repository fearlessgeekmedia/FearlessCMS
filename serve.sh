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

# check if php is installed
if ! command -v php &> /dev/null
then
    echo "Error: PHP not found in PATH. Please install PHP."
    exit 1
fi

# check if PHP has session extension
if ! php -m | grep -q session; then
    echo "Warning: PHP session extension not found. Sessions may not work properly."
fi

# check if custom PHP config exists
if [ ! -f "php-config/99-custom.ini" ]; then
    echo "Warning: Custom PHP configuration not found (php-config/99-custom.ini)."
    php_config=""
else
    php_config="-c php-config/99-custom.ini"
    echo "Using custom PHP configuration: $php_config"
fi

echo "Starting FearlessCMS server on http://localhost:$port..."
php $php_config -S localhost:$port router.php > serve-log.tmp 2>&1 &
pid=$!

echo "Server started with PID $pid"
echo "To stop the server, run: kill $pid"
echo "To view logs, run: tail -f serve-log.tmp"
echo "🚀"

# Wait for the process to finish
wait $pid
