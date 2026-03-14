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
update_test=false
restore_backup=false

# Simple argument parsing
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --port) port="$2"; shift ;;
        --update-test) update_test=true ;;
        --restore-backup) restore_backup=true ;;
    esac
    shift
done

if [ "$update_test" = true ]; then
    export FCMS_UPDATE_BRANCH="update-test"
    export FCMS_FORCE_BACKUP="true"
    echo "Mode: Update Test (--update-test active, backups forced)"
fi

if [ "$restore_backup" = true ]; then
    echo "Restoring latest backup..."
    latest_backup=$(ls -dt backups/cms_backup_* 2>/dev/null | head -n 1)
    if [ -n "$latest_backup" ]; then
        echo "Found latest backup: $latest_backup"
        ./update.sh --restore "$latest_backup"
        exit 0
    else
        echo "Error: No backups found in backups/ directory."
        exit 1
    fi
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
    php_config="-d include_path=. $(sed -E '/^\s*(;|$)/d' php-config/99-custom.ini | while IFS='=' read -r key val; do printf -- '-d %s=%s ' "$(echo "$key" | xargs)" "$(echo "$val" | xargs)"; done)"
    echo "Using custom PHP configuration from php-config/99-custom.ini"
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
