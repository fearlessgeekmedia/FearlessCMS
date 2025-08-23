{ pkgs ? import <nixpkgs> {} }:

let
  # PHP with required extensions
  php = pkgs.php81.withExtensions ({ enabled, all }: enabled ++ (with all; [
    mbstring
    openssl
    pdo
    pdo_sqlite
    sqlite3
    zip
    fileinfo
    curl
    gd
    session
  ]));

  # Node.js with npm
  nodejs = pkgs.nodejs_20;

  # Development tools
  devTools = with pkgs; [
    # Version control
    git

    # Text editors
    vim
    nano

    # File utilities
    tree
    jq
    ripgrep

    # Web development
    httpie
    nginx

    # Process monitoring
    htop

    # Network tools
    curl
    wget

    # Archive tools
    unzip
    zip

    # Image processing (for thumbnails)
    imagemagick

    # Database tools
    sqlite

    # Shell utilities
    bat
    fd
    fzf
  ];

in pkgs.mkShell {
  name = "fearlesscms-dev";

  buildInputs = [
    php
    nodejs
    pkgs.nodePackages.npm
    pkgs.php81Packages.composer
  ] ++ devTools;

  shellHook = ''
    echo "🐺 FearlessCMS Development Environment"
    echo "======================================"
    echo "PHP version: $(php -v | head -n1)"
    echo "Node.js version: $(node --version)"
    echo "NPM version: $(npm --version)"
    echo ""
    echo "Available commands:"
    echo "  php install.php --check               # Check environment"
    echo "  php install.php --create-dirs         # Create directories"
    echo "  php install.php --install-export-deps # Install Node deps"
    echo "  node export.js                        # Export static site"
    echo "  npm install                           # Install Node dependencies"
    echo "  ./serve.sh          		  # Start PHP server"
    echo ""

    # Set up sandbox environment
    export HOME="$(pwd)/sandbox_home"
    mkdir -p "$HOME"

    # Set up environment variables
    export FCMS_DEBUG=true
    export FCMS_CONFIG_DIR="$(pwd)/config"

    # Set up PHP configuration
    export PHP_INI_SCAN_DIR="${toString ./.}/php-config"
    mkdir -p "$PHP_INI_SCAN_DIR"
    cp ${pkgs.writeText "99-custom.ini" ''
      memory_limit = 256M
      upload_max_filesize = 64M
      post_max_size = 64M
      max_execution_time = 300
      display_errors = On
      log_errors = On
      error_log = /tmp/php_errors.log
      session.save_handler = files
      session.save_path = ${toString ./.}/sessions
      session.auto_start = 0
      session.use_strict_mode = 1
    ''} "$PHP_INI_SCAN_DIR/99-custom.ini"

    # Create symlinks for common PHP commands
    if [ ! -L php ]; then
      ln -sf ${php}/bin/php php
    fi

    # Set up PHP development server alias
    alias serve="php -S localhost:8000"

    # Set up export alias
    alias export-site="node export.js"

    # Set up install aliases
    alias install-check="php install.php --check"
    alias install-dirs="php install.php --create-dirs"
    alias install-deps="php install.php --install-export-deps"

    echo "Environment ready! 🚀"
    echo "Sandbox HOME: $HOME"
  '';

  # Environment variables
  FCMS_DEBUG = "true";
  FCMS_CONFIG_DIR = toString ./. + "/config";
}
