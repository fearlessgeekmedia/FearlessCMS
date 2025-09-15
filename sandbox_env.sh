#!/usr/bin/env bash
# Environment setup for sandbox

# Set HOME to sandbox directory
export HOME="/home/fearlessgeek/devel/FearlessCMS/sandbox_home"

# Create the sandbox home directory if it doesn't exist
mkdir -p "$HOME"

# Set other environment variables
export FCMS_DEBUG=true
export FCMS_CONFIG_DIR="/home/fearlessgeek/devel/FearlessCMS/config"

echo "🏠 Sandbox environment activated!"
echo "HOME: $HOME"
echo "To use this environment, run: source sandbox_env.sh"