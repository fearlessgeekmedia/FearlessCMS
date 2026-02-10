#!/usr/bin/env bash

# FearlessCMS Bash Updater with Complete Theme Preservation
# Preserves ALL existing themes and only adds new ones from repository

set -e  # Exit on any error

# Set up environment - ensure we have a proper PATH
export PATH="/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/run/current-system/sw/bin:$PATH"

# Find git dynamically
GIT_CMD=$(which git 2>/dev/null || echo "git")
if [[ ! -x "$(which $GIT_CMD 2>/dev/null)" ]]; then
    echo "ERROR: Git is not available in PATH. Please ensure git is installed." >&2
    exit 1
fi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REPO_URL="https://github.com/fearlessgeekmedia/FearlessCMS.git"
BRANCH="main"
BACKUP_DIR="./backups"
UPDATE_DIR="./update_temp"
THEMES_BACKUP_DIR="./themes_backup_temp"

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if we're in the right directory
check_environment() {
    if [[ ! -f "index.php" ]]; then
        error "This doesn't appear to be a FearlessCMS installation (index.php not found)"
        exit 1
    fi
    
    if [[ ! -d "admin" ]]; then
        error "Admin directory not found. Are you in the correct CMS root directory?"
        exit 1
    fi
    
    # Debug environment information
    log "Environment check passed"
    log "Current PATH: $PATH"
    log "Git command: $GIT_CMD"
    log "Git location: $(which $GIT_CMD 2>/dev/null || echo 'not found')"
}

# Backup ALL existing themes
backup_all_themes() {
    log "Backing up ALL existing themes..."
    
    if [[ ! -d "themes" ]]; then
        log "No themes directory found, skipping theme backup"
        return 0
    fi
    
    # Create temporary backup directory
    rm -rf "${THEMES_BACKUP_DIR}"
    mkdir -p "${THEMES_BACKUP_DIR}"
    
    # Simple approach: copy entire themes directory
    log "Copying all themes to backup directory"
    cp -r themes/* "${THEMES_BACKUP_DIR}/" 2>/dev/null || {
        log "No themes found to backup"
        return 0
    }
    
    local theme_count=$(ls -1 "${THEMES_BACKUP_DIR}" | wc -l)
    if [[ $theme_count -gt 0 ]]; then
        success "Backed up $theme_count themes to ${THEMES_BACKUP_DIR}"
    else
        log "No themes found to backup"
    fi
}

# Restore ALL themes and add new ones from repository
restore_and_merge_themes() {
    log "Restoring themes and merging with new ones from repository..."
    
    # First, restore all backed up themes
    if [[ -d "${THEMES_BACKUP_DIR}" ]] && [[ "$(ls -A "${THEMES_BACKUP_DIR}" 2>/dev/null)" ]]; then
        log "Restoring all backed up themes"
        cp -r "${THEMES_BACKUP_DIR}"/* themes/
        local restored_count=$(ls -1 "${THEMES_BACKUP_DIR}" | wc -l)
        success "Restored $restored_count existing themes"
    else
        log "No themes to restore"
    fi
    
    # Now add any new themes from the repository that don't exist locally
    if [[ -d "${UPDATE_DIR}/themes" ]]; then
        local new_themes_added=0
        
        for repo_theme in "${UPDATE_DIR}/themes"/*; do
            if [[ -d "$repo_theme" ]]; then
                repo_theme_name=$(basename "$repo_theme")
                
                # Check if this theme already exists locally
                if [[ ! -d "themes/$repo_theme_name" ]]; then
                    log "Adding new theme from repository: $repo_theme_name"
                    cp -r "$repo_theme" themes/
                    ((new_themes_added++))
                else
                    log "Theme '$repo_theme_name' already exists locally, preserving local version"
                fi
            fi
        done
        
        if [[ $new_themes_added -gt 0 ]]; then
            success "Added $new_themes_added new themes from repository"
        fi
    fi
    
    # Clean up temporary backup
    rm -rf "${THEMES_BACKUP_DIR}"
}

# Create backup
create_backup() {
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_path="${BACKUP_DIR}/cms_backup_${timestamp}"
    
    log "Creating backup at ${backup_path}"
    
    mkdir -p "${backup_path}"
    
    # Backup core files (exclude content, config, uploads, etc.)
    rsync -av --exclude='content/' \
              --exclude='config/' \
              --exclude='uploads/' \
              --exclude='admin/uploads/' \
              --exclude='sessions/' \
              --exclude='cache/' \
              --exclude='.git/' \
              --exclude='backups/' \
              --exclude='update_temp/' \
              --exclude='themes_backup_temp/' \
              --exclude='*.log' \
              --exclude='*.tmp' \
              ./ "${backup_path}/" > /dev/null 2>&1 || {
        error "Failed to create backup"
        exit 1
    }
    
    success "Backup created successfully at ${backup_path}"
    echo "${backup_path}"
}

# Download latest version
download_update() {
    log "Downloading latest version from ${REPO_URL} (branch: ${BRANCH})"
    
    # Clean up any existing update directory
    rm -rf "${UPDATE_DIR}"
    mkdir -p "${UPDATE_DIR}"
    
    # Clone the repository with better error handling and SSL verification bypass
    log "Running: ${GIT_CMD} -c http.sslVerify=false clone --depth 1 --branch ${BRANCH} ${REPO_URL} ${UPDATE_DIR}"
    
    if "${GIT_CMD}" -c http.sslVerify=false clone --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${UPDATE_DIR}"; then
        success "Latest version downloaded successfully"
    else
        error "Failed to download latest version"
        error "Git clone command failed. Please check:"
        error "1. Internet connection"
        error "2. Repository URL: ${REPO_URL}"
        error "3. Branch name: ${BRANCH}"
        error "4. Git is properly installed"
        exit 1
    fi
}

# Perform the update with complete theme preservation
perform_update() {
    log "Performing update..."
    
    # Backup ALL existing themes before removing themes directory
    backup_all_themes
    
    # Remove old core files (keeping content, config, uploads, etc.)
    rm -rf admin/ includes/ themes/ plugins/ parallax/
    rm -f index.php base.php router.php store.php
    rm -f *.md *.txt *.nix *.json package*
    
    # Copy new files
    cp -r "${UPDATE_DIR}/admin/" ./admin/
    cp -r "${UPDATE_DIR}/includes/" ./includes/
    cp -r "${UPDATE_DIR}/plugins/" ./plugins/
    cp -r "${UPDATE_DIR}/parallax/" ./parallax/
    cp -r "${UPDATE_DIR}/public/" ./public/ 2>/dev/null || true
    cp "${UPDATE_DIR}/index.php" ./index.php
    cp "${UPDATE_DIR}/base.php" ./base.php
    cp "${UPDATE_DIR}/router.php" ./router.php
    cp "${UPDATE_DIR}/store.php" ./store.php 2>/dev/null || true
    cp "${UPDATE_DIR}/version.php" ./version.php 2>/dev/null || true
    cp "${UPDATE_DIR}/"*.md ./ 2>/dev/null || true
    cp "${UPDATE_DIR}/"*.txt ./ 2>/dev/null || true
    cp "${UPDATE_DIR}/"*.nix ./ 2>/dev/null || true
    cp "${UPDATE_DIR}/"*.json ./ 2>/dev/null || true
    cp "${UPDATE_DIR}/package*" ./ 2>/dev/null || true
    
    # Create themes directory
    mkdir -p themes/
    
    # Restore all themes and merge with new ones
    restore_and_merge_themes
    
    # Set proper permissions
    chmod 644 *.php *.md *.txt *.nix *.json package* 2>/dev/null || true
    chmod 755 admin/ includes/ themes/ plugins/ parallax/ 2>/dev/null || true
    chmod 755 admin/*.php includes/*.php 2>/dev/null || true
    
    success "Update completed successfully"
}

# Cleanup
cleanup() {
    log "Cleaning up temporary files"
    rm -rf "${UPDATE_DIR}"
    rm -rf "${THEMES_BACKUP_DIR}"
    success "Cleanup completed"
}

# Show current version
show_current_version() {
    if [[ -f "version.php" ]]; then
        local version=$(grep "APP_VERSION" version.php | sed "s/.*APP_VERSION', '\([^']*\)'.*/\1/")
        if [[ -n "${version}" ]] && [[ "${version}" != "APP_VERSION" ]]; then
            log "Current version: ${version}"
        else
            log "Current version: Unknown"
        fi
    else
        log "Current version: Not available"
    fi
}

# Show available version
show_available_version() {
    if [[ -d "${UPDATE_DIR}" ]] && [[ -f "${UPDATE_DIR}/version.php" ]]; then
        local version=$(grep "APP_VERSION" "${UPDATE_DIR}/version.php" | sed "s/.*APP_VERSION', '\([^']*\)'.*/\1/")
        if [[ -n "${version}" ]] && [[ "${version}" != "APP_VERSION" ]]; then
            log "Available version: ${version}"
        else
            log "Available version: Unknown"
        fi
    fi
}

# Main update function
update_cms() {
    local dry_run=${1:-false}
    local create_backup=${2:-true}
    
    log "Starting FearlessCMS update process with complete theme preservation"
    log "Repository: ${REPO_URL}"
    log "Branch: ${BRANCH}"
    log "Dry run: ${dry_run}"
    log "Create backup: ${create_backup}"
    
    # Check environment
    check_environment
    
    # Show current version
    show_current_version
    
    # Download latest version
    download_update
    
    # Show available version
    show_available_version
    
    if [[ "${dry_run}" == "true" ]]; then
        warning "DRY RUN MODE - No files will be changed"
        backup_all_themes
        log "Would preserve the following themes:"
        if [[ -d "${THEMES_BACKUP_DIR}" ]] && [[ "$(ls -A "${THEMES_BACKUP_DIR}" 2>/dev/null)" ]]; then
            for theme in "${THEMES_BACKUP_DIR}"/*; do
                if [[ -d "$theme" ]]; then
                    theme_name=$(basename "$theme")
                    log "  - $theme_name (existing theme)"
                fi
            done
        fi
        
        # Check for new themes in repository
        if [[ -d "${UPDATE_DIR}/themes" ]]; then
            log "New themes available from repository:"
            for repo_theme in "${UPDATE_DIR}/themes"/*; do
                if [[ -d "$repo_theme" ]]; then
                    repo_theme_name=$(basename "$repo_theme")
                    if [[ ! -d "themes/$repo_theme_name" ]]; then
                        log "  - $repo_theme_name (would be added)"
                    fi
                fi
            done
        fi
        
        log "Update simulation completed successfully"
        cleanup
        return 0
    fi
    
    # Create backup if requested
    local backup_path=""
    if [[ "${create_backup}" == "true" ]]; then
        backup_path=$(create_backup)
    fi
    
    # Perform the update
    perform_update
    
    # Cleanup
    cleanup
    
    success "FearlessCMS has been updated successfully with ALL themes preserved!"
    if [[ -n "${backup_path}" ]]; then
        log "Backup is available at: ${backup_path}"
    fi
    
    log "Please test your site to ensure everything is working correctly"
}

# Show usage
show_usage() {
    echo "FearlessCMS Bash Updater with Complete Theme Preservation"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help          Show this help message"
    echo "  -d, --dry-run       Simulate update without making changes"
    echo "  -n, --no-backup     Skip creating backup before update"
    echo "  -r, --repo URL      Set repository URL (default: ${REPO_URL})"
    echo "  -b, --branch BRANCH Set branch (default: ${BRANCH})"
    echo ""
    echo "Features:"
    echo "  - Preserves ALL existing themes (custom and modified defaults)"
    echo "  - Adds new themes from repository that don't exist locally"
    echo "  - Never overwrites existing theme files"
    echo "  - Creates backups before updating"
    echo ""
    echo "Examples:"
    echo "  $0                    # Normal update with complete theme preservation"
    echo "  $0 --dry-run         # Simulate update and show which themes would be preserved/added"
    echo "  $0 --no-backup       # Update without backup but preserve all themes"
    echo "  $0 -r https://github.com/user/repo.git -b develop"
    echo ""
}

# Parse command line arguments
parse_args() {
    local dry_run=false
    local create_backup=true
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -d|--dry-run)
                dry_run=true
                shift
                ;;
            -n|--no-backup)
                create_backup=false
                shift
                ;;
            -r|--repo)
                REPO_URL="$2"
                shift 2
                ;;
            -b|--branch)
                BRANCH="$2"
                shift 2
                ;;
            *)
                error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    update_cms "${dry_run}" "${create_backup}"
}

# Main execution
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    parse_args "$@"
fi
