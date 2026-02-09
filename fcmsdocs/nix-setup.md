# Nix Development Environment for FearlessCMS

This project includes a Nix development environment that provides all the necessary tools and dependencies for developing FearlessCMS.

## Prerequisites

1. **Install Nix**: Follow the [official installation guide](https://nixos.org/download.html)
2. **Enable flakes** (optional but recommended): Add `experimental-features = nix-command flakes` to your `~/.config/nix/nix.conf`

## Quick Start

### Option 1: Using nix-shell (Recommended)

```bash
# Enter the development environment
nix-shell

# Or use the default.nix
nix-shell default.nix
```

### Option 2: Using direnv (Automatic)

1. Install direnv: `nix-env -iA nixpkgs.direnv`
2. Add `eval "$(direnv hook zsh)"` to your shell config (or bash equivalent)
3. Run `direnv allow` in the project directory
4. The environment will automatically load when you enter the directory

**Note**: The sandbox HOME functionality works best with `nix-shell`. For direnv, you may need to manually set the HOME variable or use the nix-shell approach for full sandbox functionality.

## What's Included

### Core Dependencies
- **PHP 8.1** with essential extensions:
  - session, json, mbstring, openssl
  - pdo, pdo_sqlite, sqlite3
  - zip, fileinfo, curl, gd, imagick

- **Node.js 20** with npm for export functionality

### Development Tools
- **Version Control**: git
- **Text Editors**: vim, nano
- **File Utilities**: tree, jq, ripgrep, bat, fd, fzf
- **Web Development**: httpie, curl, wget
- **Process Monitoring**: htop
- **Archive Tools**: unzip, zip
- **Image Processing**: imagemagick (for thumbnails)
- **Database Tools**: sqlite

## Available Commands

Once in the nix-shell, you'll have access to these aliases:

```bash
# PHP development server
serve                    # Start PHP server on localhost:8000

# Installation commands
install-check           # Check environment status
install-dirs            # Create required directories
install-deps            # Install Node.js dependencies

# Export functionality
export-site             # Export static site
npm install             # Install Node.js dependencies
```

## Environment Variables

The shell automatically sets:
- `FCMS_DEBUG=true` - Enables debug mode
- `FCMS_CONFIG_DIR=./config` - Points to config directory

## Sandbox Environment

The development environment includes a sandboxed HOME directory:
- **Sandbox HOME**: `./sandbox_home` (relative to project root)
- **Purpose**: Isolates npm cache, config files, and other user-specific data
- **Benefits**: Prevents conflicts with system-wide configurations and keeps project dependencies isolated

When using `nix-shell`, the HOME environment variable is automatically set to the sandbox directory. This ensures that:
- npm installs packages to the project-specific cache
- Configuration files are stored locally
- No interference with system-wide settings

## PHP Configuration

A custom `php.ini` is provided with optimized settings:
- Memory limit: 256M
- Upload max filesize: 64M
- Post max size: 64M
- Max execution time: 300s
- Error logging enabled
- Session handling configured

## Troubleshooting

### Common Issues

1. **PHP extensions missing**: The shell includes all necessary extensions. If you see missing extension errors, ensure you're using the nix-shell.

2. **Permission issues**: The environment runs with your user permissions. If you encounter permission issues, check file ownership.

3. **Node.js dependencies**: Run `npm install` after entering the shell to install project dependencies.

### Updating Dependencies

To update the Nix environment:
1. Modify `shell.nix` as needed
2. Exit and re-enter the shell: `exit` then `nix-shell`

### Customizing the Environment

Edit `shell.nix` to:
- Add more PHP extensions
- Include additional development tools
- Modify PHP configuration
- Change Node.js version

## Integration with IDEs

### VS Code
1. Install the "Nix Environment Selector" extension
2. Select the nix-shell environment when prompted
3. VS Code will use the PHP and Node.js from the nix-shell

### Other IDEs
Most IDEs can be configured to use the executables from the nix-shell:
- PHP: `$(which php)` from within nix-shell
- Node.js: `$(which node)` from within nix-shell

## Performance Notes

- First run may take time to download and build dependencies
- Subsequent runs are much faster due to Nix caching
- The environment is isolated and reproducible across different systems

## Verification

To verify the environment is working correctly:

```bash
# Test all components
nix-shell --run "php --version && node --version && npm --version"

# Check PHP extensions
nix-shell --run "php -m | grep -E '(session|mbstring|openssl|pdo|sqlite|curl|gd|zip)'"

# Test sandbox HOME
nix-shell --run "echo \$HOME"

# Install Node.js dependencies
nix-shell --run "npm install"
```

Expected output:
- PHP 8.1.33+
- Node.js 20.x
- All essential PHP extensions present
- HOME points to `./sandbox_home`
- Node dependencies install without errors 