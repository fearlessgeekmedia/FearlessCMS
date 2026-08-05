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
public=false
open=false
http=false
hostname=""
update_test=false
restore_backup=false

# Simple argument parsing
while [[ "$#" -gt 0 ]]; do
    case $1 in
        --port|-p) port="$2"; shift ;;
        --public) public=true ;;
        --open) open=true ;;
        --http) http=true ;;
        --hostname) hostname="$2"; shift ;;
        --update-test) update_test=true ;;
        --restore-backup) restore_backup=true ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  -p, --port PORT        Port number to serve on (default: 8000)"
            echo "      --public           Bind to 0.0.0.0 instead of localhost (shows detected IP)"
            echo "      --open             Open the default browser on port 80 (requires sudo or doas)"
            echo "      --http             Run on port 80 (requires sudo or doas, no port in URL)"
            echo "      --hostname HOST    Use a specific domain name in the URL (requires hosts file edit)"
            echo "      --update-test      Enable update test mode"
            echo "      --restore-backup   Restore the latest backup"
            echo "  -h, --help             Show this help message"
            exit 0
            ;;
        *) echo "Unknown option: $1"; exit 1 ;;
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
    php_config="-d include_path=$SCRIPT_DIR $(sed -E '/^\s*(;|$)/d' php-config/99-custom.ini | while IFS='=' read -r key val; do printf -- '-d %s=%s ' "$(echo "$key" | xargs)" "$(echo "$val" | xargs)"; done)"
    echo "Using custom PHP configuration from php-config/99-custom.ini"
fi

if [ "$http" = true ] || [ "$open" = true ]; then
    port=80
    if command -v sudo &> /dev/null; then
        escalate_cmd="sudo"
    elif command -v doas &> /dev/null; then
        escalate_cmd="doas"
    else
        echo "Error: --open requires sudo or doas for port 80, but neither was found."
        exit 1
    fi
fi

# check if port is in use (after port is finalized)
port_in_use=false
if command -v lsof &> /dev/null; then
    lsof -i:$port > /dev/null && port_in_use=true
elif command -v ss &> /dev/null; then
    ss -tulpn 2>/dev/null | grep -q ":$port " && port_in_use=true
elif command -v fuser &> /dev/null; then
    fuser $port/tcp &> /dev/null && port_in_use=true
else
    echo "Warning: Neither lsof, ss, nor fuser found. Skipping port-in-use check."
fi

if [ "$port_in_use" = true ]; then
    echo "Port $port is already in use"
    exit
fi

if [ "$public" = true ]; then
    address="0.0.0.0"
    if [ -n "$hostname" ]; then
        display_address="$hostname"
    else
        display_address=$(ip addr show 2>/dev/null | awk '/inet /{split($2,a,"/"); print a[1]}' | grep -v '^127\.' | grep '^192\.168\.' | head -n 1)
        if [ -z "$display_address" ]; then
            display_address=$(ip addr show 2>/dev/null | awk '/inet /{split($2,a,"/"); print a[1]}' | grep -v '^127\.' | head -n 1)
        fi
        if [ -z "$display_address" ]; then
            display_address="0.0.0.0"
        fi
    fi
else
    address="localhost"
    if [ -n "$hostname" ]; then
        display_address="$hostname"
    else
        display_address="localhost"
    fi
fi

if [ "$http" = true ] || [ "$open" = true ]; then
    echo "Starting FearlessCMS server on http://$display_address/ (using $escalate_cmd)"
else
    echo "Starting FearlessCMS server on http://$display_address:$port..."
fi
if [ "$http" = true ] || [ "$open" = true ]; then
    $escalate_cmd php $php_config -S $address:$port "$SCRIPT_DIR/router.php" > serve-log.tmp 2>&1 &
else
    php $php_config -S $address:$port "$SCRIPT_DIR/router.php" > serve-log.tmp 2>&1 &
fi
pid=$!

if [ "$http" = true ] || [ "$open" = true ]; then
    echo "Server started with PID $pid (running with $escalate_cmd)"
else
    echo "Server started with PID $pid"
fi
echo "To stop the server, run: kill $pid"
echo "To view logs, run: tail -f serve-log.tmp"
echo "🚀"

if [ "$open" = true ]; then
    if [ "$port" -eq 80 ]; then
        browser_url="http://$display_address"
    else
        browser_url="http://$display_address:$port"
    fi
    
    if [ -n "$SSH_CONNECTION" ] || [ -n "$SSH_CLIENT" ] || [ -z "$DISPLAY" ]; then
        echo "Remote/headless session detected. Open $browser_url in your browser."
    elif command -v xdg-open &> /dev/null; then
        xdg-open "$browser_url" &
    elif command -v open &> /dev/null; then
        open "$browser_url"
    elif command -v start &> /dev/null; then
        start "$browser_url"
    else
        echo "Could not open browser automatically. Open $browser_url manually."
    fi
fi

# Wait for the process to finish
wait $pid
