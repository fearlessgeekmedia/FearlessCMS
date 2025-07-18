#!/bin/bash

# FearlessCMS Permission Fix Script
# This script fixes permission issues for FearlessCMS on nginx/Apache servers

set -e  # Exit on any error

echo "🔧 FearlessCMS Permission Fix Script"
echo "====================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root. Please run as your user account."
   exit 1
fi

# Get the current directory (should be FearlessCMS root)
CMS_ROOT="$(pwd)"
print_status "CMS Root Directory: $CMS_ROOT"

# Check if we're in the right directory
if [[ ! -f "$CMS_ROOT/index.php" ]] || [[ ! -d "$CMS_ROOT/plugins" ]]; then
    print_error "This doesn't appear to be a FearlessCMS installation. Please run this script from the CMS root directory."
    exit 1
fi

# Detect web server user
print_status "Detecting web server user..."

WEB_USER=""
WEB_GROUP=""

# Try to detect nginx user
if command -v nginx &> /dev/null; then
    NGINX_USER=$(ps aux | grep nginx | grep -v grep | head -1 | awk '{print $1}')
    if [[ -n "$NGINX_USER" ]]; then
        WEB_USER="$NGINX_USER"
        WEB_GROUP="$NGINX_USER"
        print_status "Detected nginx user: $WEB_USER"
    fi
fi

# Try to detect Apache user
if [[ -z "$WEB_USER" ]] && command -v apache2 &> /dev/null; then
    APACHE_USER=$(ps aux | grep apache2 | grep -v grep | head -1 | awk '{print $1}')
    if [[ -n "$APACHE_USER" ]]; then
        WEB_USER="$APACHE_USER"
        WEB_GROUP="$APACHE_USER"
        print_status "Detected Apache user: $WEB_USER"
    fi
fi

# Common web server users to try
if [[ -z "$WEB_USER" ]]; then
    for user in www-data nginx apache httpd; do
        if id "$user" &>/dev/null; then
            WEB_USER="$user"
            WEB_GROUP="$user"
            print_status "Found web server user: $WEB_USER"
            break
        fi
    done
fi

# If still no web user found, ask for input
if [[ -z "$WEB_USER" ]]; then
    print_warning "Could not automatically detect web server user."
    echo -n "Please enter your web server user (e.g., www-data, nginx): "
    read -r WEB_USER
    WEB_GROUP="$WEB_USER"
fi

# Verify the user exists
if ! id "$WEB_USER" &>/dev/null; then
    print_error "User '$WEB_USER' does not exist. Please check your web server configuration."
    exit 1
fi

print_success "Using web server user: $WEB_USER"

# Get current user
CURRENT_USER=$(whoami)
print_status "Current user: $CURRENT_USER"

echo ""
print_status "Starting permission fixes..."

# Create a backup of current permissions
BACKUP_FILE="$CMS_ROOT/permissions_backup_$(date +%Y%m%d_%H%M%S).txt"
print_status "Creating permissions backup: $BACKUP_FILE"

# Save current permissions
find "$CMS_ROOT" -type f -exec ls -la {} \; > "$BACKUP_FILE" 2>/dev/null || true

# Function to create directory if it doesn't exist
create_dir_if_not_exists() {
    if [[ ! -d "$1" ]]; then
        print_status "Creating directory: $1"
        sudo mkdir -p "$1"
    fi
}

# Create necessary directories
create_dir_if_not_exists "$CMS_ROOT/content/forms"
create_dir_if_not_exists "$CMS_ROOT/content/form_submissions"
create_dir_if_not_exists "$CMS_ROOT/content/_preview"

# Set ownership for web server
print_status "Setting ownership to $WEB_USER:$WEB_GROUP..."
sudo chown -R "$WEB_USER:$WEB_GROUP" "$CMS_ROOT"

# Set base permissions
print_status "Setting base permissions..."
sudo chmod -R 755 "$CMS_ROOT"

# Set specific permissions for writable directories
print_status "Setting writable permissions for content directories..."
sudo chmod -R 775 "$CMS_ROOT/content"
sudo chmod -R 775 "$CMS_ROOT/config"
sudo chmod -R 775 "$CMS_ROOT/uploads"
sudo chmod -R 775 "$CMS_ROOT/admin/uploads"

# Set specific file permissions
print_status "Setting file permissions..."
sudo chmod 664 "$CMS_ROOT/sitemap.xml" 2>/dev/null || true
sudo chmod 664 "$CMS_ROOT/robots.txt" 2>/dev/null || true
sudo chmod 664 "$CMS_ROOT/debug.log" 2>/dev/null || true
sudo chmod 664 "$CMS_ROOT/error.log" 2>/dev/null || true

# Create log files if they don't exist
touch "$CMS_ROOT/debug.log" 2>/dev/null || sudo touch "$CMS_ROOT/debug.log"
touch "$CMS_ROOT/error.log" 2>/dev/null || sudo touch "$CMS_ROOT/error.log"
sudo chown "$WEB_USER:$WEB_GROUP" "$CMS_ROOT/debug.log" "$CMS_ROOT/error.log"

# Set permissions for forms log
if [[ -f "$CMS_ROOT/content/forms/forms.log" ]]; then
    sudo chmod 664 "$CMS_ROOT/content/forms/forms.log"
    sudo chown "$WEB_USER:$WEB_GROUP" "$CMS_ROOT/content/forms/forms.log"
fi

# Add current user to web server group for easier development
print_status "Adding current user to web server group..."
sudo usermod -a -G "$WEB_GROUP" "$CURRENT_USER" 2>/dev/null || print_warning "Could not add user to group (may already be a member)"

# Set special permissions for development
print_status "Setting development-friendly permissions..."
sudo chmod -R g+w "$CMS_ROOT/content"
sudo chmod -R g+w "$CMS_ROOT/config"
sudo chmod -R g+w "$CMS_ROOT/uploads"

echo ""
print_success "Permission fixes completed!"
echo ""
print_status "Summary of changes:"
echo "  - Ownership set to: $WEB_USER:$WEB_GROUP"
echo "  - Base permissions: 755"
echo "  - Writable directories: 775"
echo "  - Log files: 664"
echo "  - Backup created: $BACKUP_FILE"
echo ""
print_warning "You may need to log out and back in for group changes to take effect."
echo ""
print_status "Testing write permissions..."

# Test write permissions
TEST_FILE="$CMS_ROOT/permission_test_$(date +%s).tmp"
if sudo -u "$WEB_USER" touch "$TEST_FILE" 2>/dev/null; then
    sudo rm -f "$TEST_FILE"
    print_success "Write permissions test passed!"
else
    print_error "Write permissions test failed. Please check your web server configuration."
fi

echo ""
print_success "🎉 Permission fix completed successfully!"
print_status "Your FearlessCMS should now work without permission errors." 