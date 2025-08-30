# FearlessCMS Bash Updater

## Overview
The FearlessCMS Bash Updater is a simple, reliable alternative to the PHP-based updater that completely bypasses all CSRF token issues. It's a standalone bash script that can update your CMS installation directly from the command line.

## Why Bash Instead of PHP?
- **No CSRF token issues** - Completely bypasses PHP session/CSRF complications
- **More reliable** - Direct file system operations without web server dependencies
- **Easier debugging** - Clear command line output and error messages
- **Faster execution** - No PHP overhead or web server processing
- **Better backup control** - Direct rsync operations for reliable backups

## Features
- ✅ **Automatic backup creation** before updates
- ✅ **Dry run mode** to preview changes without making them
- ✅ **Version checking** - shows current and available versions
- ✅ **Safe updates** - preserves content, config, uploads, and sessions
- ✅ **Configurable** - customizable repository URL and branch
- ✅ **Error handling** - exits on errors with clear messages
- ✅ **Cleanup** - automatically removes temporary files

## Requirements
- Bash shell
- Git (for downloading updates)
- rsync (for backups)
- Basic Unix/Linux environment

## Usage

### Basic Update (with backup)
```bash
./update.sh
```

### Dry Run (preview only)
```bash
./update.sh --dry-run
```

### Update without backup
```bash
./update.sh --no-backup
```

### Custom repository and branch
```bash
./update.sh -r https://github.com/user/repo.git -b develop
```

### Show help
```bash
./update.sh --help
```

## What Gets Updated
The updater replaces core CMS files while preserving:
- `content/` - Your site content
- `config/` - Configuration files
- `uploads/` - User uploads
- `admin/uploads/` - Admin uploads
- `sessions/` - User sessions
- `cache/` - Cache files
- `.git/` - Git repository

## What Gets Replaced
- `admin/` - Admin interface
- `includes/` - Core PHP functions
- `themes/` - Theme files
- `plugins/` - Plugin files
- `parallax/` - Parallax plugin
- Core PHP files (index.php, base.php, etc.)
- Documentation files (*.md, *.txt)
- Configuration files (*.nix, *.json)

## Backup Location
Backups are stored in `./backups/cms_backup_YYYYMMDD_HHMMSS/` with timestamps for easy identification.

## Safety Features
1. **Environment check** - Verifies you're in a valid CMS directory
2. **Automatic backup** - Creates backup before any changes
3. **Dry run mode** - Test updates without making changes
4. **Error handling** - Exits immediately on any error
5. **Cleanup** - Removes temporary files after completion

## Example Output
```
[2025-08-30 00:23:17] Starting FearlessCMS update process
[2025-08-30 00:23:17] Repository: https://github.com/fearlessgeekmedia/FearlessCMS.git
[2025-08-30 00:23:17] Branch: main
[2025-08-30 00:23:17] Environment check passed
[2025-08-30 00:23:17] Current version: 1.0.0
[2025-08-30 00:23:17] Downloading latest version...
[SUCCESS] Latest version downloaded successfully
[2025-08-30 00:23:20] Available version: 1.1.0
[2025-08-30 00:23:20] Creating backup at ./backups/cms_backup_20250830_002320
[SUCCESS] Backup created successfully
[2025-08-30 00:23:25] Performing update...
[SUCCESS] Update completed successfully
[SUCCESS] FearlessCMS has been updated successfully!
```

## Troubleshooting

### Permission Denied
```bash
chmod +x update.sh
```

### Git Not Found
Install git on your system:
```bash
# Ubuntu/Debian
sudo apt-get install git

# CentOS/RHEL
sudo yum install git

# macOS
brew install git
```

### rsync Not Found
Install rsync on your system:
```bash
# Ubuntu/Debian
sudo apt-get install git

# CentOS/RHEL
sudo yum install git

# macOS
brew install git
```

### Wrong Directory
Make sure you're in the CMS root directory (where `index.php` and `admin/` folder exist).

## Security Notes
- The script runs with your user permissions
- It only modifies files in the current directory
- It creates backups before making changes
- It excludes sensitive directories from updates
- It's designed to be safe for production use

## Integration
You can integrate this updater into your deployment pipeline, cron jobs, or manual update processes. It's completely independent of the web interface and can be run from SSH, CI/CD systems, or any command line environment.

## Support
This updater is designed to be simple and reliable. If you encounter issues:
1. Check the error messages for specific problems
2. Verify you're in the correct directory
3. Ensure git and rsync are installed
4. Try a dry run first to identify issues
5. Check that you have write permissions in the directory 