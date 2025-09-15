#!/usr/bin/env bash
# Working sandbox HOME wrapper

# Set HOME to sandbox directory
export HOME="/home/fearlessgeek/devel/FearlessCMS/sandbox_home"

# Create the sandbox home directory if it doesn't exist
mkdir -p "$HOME"

# Start a new bash shell with the modified environment
exec bash