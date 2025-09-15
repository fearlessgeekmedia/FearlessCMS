#!/usr/bin/env bash
# Simple script to fix HOME variable

echo "Setting HOME to sandbox directory..."
export HOME="/home/fearlessgeek/devel/FearlessCMS/sandbox_home"
echo "HOME is now: $HOME"

# Start a new bash shell with the modified environment
exec bash