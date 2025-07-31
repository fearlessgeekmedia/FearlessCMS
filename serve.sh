#!/usr/bin/env bash

# Change to the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR" || {
    echo "Error: Could not change to script directory: $SCRIPT_DIR"
    exit 1
}

# clear previous logs if any
rm -f serve-log.tmp

# check if php is installed
if ! command -v php &> /dev/null
then
    echo "php could not be found"
    exit
fi

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

# run server and log output
php -c php.ini -S localhost:$port router.php > serve-log.tmp 2>&1 &
pid=$!
echo "Server started on port $port with PID $pid"
echo "To stop the server, run: kill $pid"
echo "To view logs, run: tail -f serve-log.tmp"
echo "To access the server, open http://localhost:$port in your browser"
echo ""
# retain session of the process
wait $pid
