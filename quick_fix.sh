#!/bin/bash

# Quick Permission Fix for FearlessCMS
# Run this on your web server with sudo

echo "Quick permission fix for FearlessCMS..."

# Set the correct path (adjust if needed)
CMS_DIR="/home/fearlessgeek/fearlesscms"

# Common web server user (change if different)
WEB_USER="www-data"

echo "Setting ownership to $WEB_USER..."
sudo chown -R $WEB_USER:$WEB_USER $CMS_DIR

echo "Setting permissions..."
sudo chmod -R 755 $CMS_DIR
sudo chmod -R 775 $CMS_DIR/content
sudo chmod -R 775 $CMS_DIR/config
sudo chmod -R 775 $CMS_DIR/uploads

echo "Creating log files..."
sudo touch $CMS_DIR/debug.log
sudo touch $CMS_DIR/error.log
sudo chown $WEB_USER:$WEB_USER $CMS_DIR/debug.log $CMS_DIR/error.log
sudo chmod 664 $CMS_DIR/debug.log $CMS_DIR/error.log

echo "Setting file permissions..."
sudo chmod 664 $CMS_DIR/sitemap.xml
sudo chmod 664 $CMS_DIR/robots.txt

echo "Creating forms directory..."
sudo mkdir -p $CMS_DIR/content/forms
sudo chown -R $WEB_USER:$WEB_USER $CMS_DIR/content/forms

echo "Done! Your CMS should now work without permission errors." 