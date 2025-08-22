{ pkgs ? import <nixpkgs> {} }:

pkgs.mkShell {
  buildInputs = with pkgs; [
    php82
    nodejs_20
    php82Packages.composer
  ];

  shellHook = ''
    echo "Simple FearlessCMS Development Environment"
    echo "======================================="
    echo "PHP version: $(php --version | head -n1)"
    echo "Available modules: $(php -m | grep -E '(session|mbstring|openssl|pdo|sqlite3|curl|gd)' | tr '\n' ' ')"
    echo ""

    # Check for session support
    if php -r "if (function_exists('session_start')) { echo 'Session support: OK'; } else { echo 'Session support: MISSING'; }"; then
      echo ""
    fi

    echo "Ready to test!"
  '';
}
