#!/bin/bash

# FearlessCMS Server Permission Fix Script
# Run this on your web server where FearlessCMS is installed

echo "🔧 FearlessCMS Server Permission Fix"
echo "===================================="

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

# Get CMS directory
CMS_DIR="/home/fearlessgeek/fearlesscms"

# Check if directory exists
if [[ ! -d "$CMS_DIR" ]]; then
    echo -e "${RED}Error: CMS directory not found at $CMS_DIR${NC}"
    exit 1
fi

echo -e "${BLUE}CMS Directory: $CMS_DIR${NC}"

# Common web server users to try
WEB_USERS=("www-data" "nginx" "apache" "httpd")

WEB_USER=""
for user in "${WEB_USERS[@]}"; do
    if id "$user" &>/dev/null; then
        WEB_USER="$user"
        echo -e "${BLUE}Found web server user: $WEB_USER${NC}"
        break
    fi
done

if [[ -z "$WEB_USER" ]]; then
    echo -e "${RED}Could not find web server user. Please run:${NC}"
    echo "ps aux | grep nginx"
    echo "Then manually set the user below."
    echo -n "Enter web server user: "
    read -r WEB_USER
fi

echo -e "${BLUE}Using web server user: $WEB_USER${NC}"

# Create backup
BACKUP_FILE="$CMS_DIR/permissions_backup_$(date +%Y%m%d_%H%M%S).txt"
echo -e "${BLUE}Creating backup: $BACKUP_FILE${NC}"
find "$CMS_DIR" -type f -exec ls -la {} \; > "$BACKUP_FILE" 2>/dev/null || true

# Create necessary directories
echo -e "${BLUE}Creating necessary directories...${NC}"
mkdir -p "$CMS_DIR/content/forms"
mkdir -p "$CMS_DIR/content/form_submissions"
mkdir -p "$CMS_DIR/content/_preview"

# Set ownership
echo -e "${BLUE}Setting ownership to $WEB_USER...${NC}"
chown -R "$WEB_USER:$WEB_USER" "$CMS_DIR"

# Set permissions
echo -e "${BLUE}Setting permissions...${NC}"
chmod -R 755 "$CMS_DIR"
chmod -R 775 "$CMS_DIR/content"
chmod -R 775 "$CMS_DIR/config"
chmod -R 775 "$CMS_DIR/uploads"
chmod -R 775 "$CMS_DIR/admin/uploads"

# Set specific file permissions
chmod 664 "$CMS_DIR/sitemap.xml" 2>/dev/null || true
chmod 664 "$CMS_DIR/robots.txt" 2>/dev/null || true
chmod 664 "$CMS_DIR/debug.log" 2>/dev/null || true
chmod 664 "$CMS_DIR/error.log" 2>/dev/null || true

# Create log files if they don't exist
touch "$CMS_DIR/debug.log" 2>/dev/null || true
touch "$CMS_DIR/error.log" 2>/dev/null || true
chown "$WEB_USER:$WEB_USER" "$CMS_DIR/debug.log" "$CMS_DIR/error.log"

# Set permissions for forms log
if [[ -f "$CMS_DIR/content/forms/forms.log" ]]; then
    chmod 664 "$CMS_DIR/content/forms/forms.log"
    chown "$WEB_USER:$WEB_USER" "$CMS_DIR/content/forms/forms.log"
fi

# Test write permissions
echo -e "${BLUE}Testing write permissions...${NC}"
TEST_FILE="$CMS_DIR/permission_test_$(date +%s).tmp"
if sudo -u "$WEB_USER" touch "$TEST_FILE" 2>/dev/null; then
    rm -f "$TEST_FILE"
    echo -e "${GREEN}✅ Write permissions test passed!${NC}"
else
    echo -e "${RED}❌ Write permissions test failed${NC}"
    echo "You may need to run this script with sudo"
fi

echo ""
echo -e "${GREEN}🎉 Permission fix completed!${NC}"
echo -e "${BLUE}Backup saved to: $BACKUP_FILE${NC}"
echo ""
echo "Your FearlessCMS should now work without permission errors." 