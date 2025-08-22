#!/bin/bash

echo "🐺 FearlessCMS Development Environment Starter"
echo "============================================="
echo ""

# Check if we're in a Nix environment
if command -v nix-shell >/dev/null 2>&1; then
    echo "Nix detected. Starting development environment..."
    
    # Check which shell.nix file to use
    if [ -f "shell-working.nix" ]; then
        echo "Using shell-working.nix (recommended)"
        nix-shell shell-working.nix
    elif [ -f "shell-simple.nix" ]; then
        echo "Using shell-simple.nix (fallback)"
        nix-shell shell-simple.nix
    elif [ -f "shell.nix" ]; then
        echo "Using shell.nix (may have session issues)"
        nix-shell
    else
        echo "No shell.nix file found. Please create one."
        exit 1
    fi
else
    echo "Nix not detected. Please install Nix or use a different PHP environment."
    echo ""
    echo "To install Nix:"
    echo "  curl --proto '=https' --tlsv1.2 -sSf https://get.determinate.systems/nix | sh -s -- install"
    exit 1
fi 