{ pkgs ? import <nixpkgs> {} }:

let
  # PHP with session and essential extensions
  php = pkgs.php82.withExtensions ({ all, ... }: with all; [
    session
    mbstring
    openssl
    pdo
    pdo_sqlite
    sqlite3
    fileinfo
    curl
    gd
  ]);

in pkgs.mkShell {
  name = "fearlesscms-dev";

  buildInputs = [
    php
    pkgs.nodejs_20
    pkgs.php82Packages.composer
  ];

  shellHook = ''
    echo "🐺 FearlessCMS Development Environment (Working)"
    echo "=============================================="
    echo "PHP version: $(php --version | head -n1)"
    echo ""

    # Test session support
    echo -n "Session support: "
    if php -r "if (function_exists('session_start')) { echo 'OK'; } else { echo 'MISSING'; }" 2>/dev/null; then
      echo ""
    else
      echo "ERROR"
    fi

    echo ""
    echo "PHP modules loaded:"
    php -m | grep -E '(session|mbstring|openssl|pdo|sqlite|fileinfo|curl|gd)' | sed 's/^/  - /'
    echo ""
    echo "Ready! Try: php install.php --check"
  '';
}
