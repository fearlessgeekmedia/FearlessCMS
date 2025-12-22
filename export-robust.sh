#!/usr/bin/env bash

# FearlessCMS Robust Export Script
# This script ensures all assets are properly downloaded

# Configuration
EXPORT_DIR="export"
BASE_URL="http://localhost:8000"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}FearlessCMS Robust Export Script${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Check if curl is available
if ! command -v curl &> /dev/null; then
    echo -e "${RED}Error: curl is not installed${NC}"
    exit 1
fi

# Check if the localhost site is running
echo -e "${YELLOW}Checking if localhost:8000 is accessible...${NC}"
if ! curl -s --head "$BASE_URL" > /dev/null; then
    echo -e "${RED}Error: Cannot connect to $BASE_URL${NC}"
    echo "Please make sure the FearlessCMS development server is running:"
    echo "  nix-shell -p php83 --run 'export FCMS_DEBUG=true && ./serve.sh'"
    exit 1
fi

echo -e "${GREEN}✓ Localhost site is accessible${NC}"
echo ""

# Clean export directory
if [ -d "$EXPORT_DIR" ]; then
    echo -e "${YELLOW}Cleaning existing export directory...${NC}"
    rm -rf "$EXPORT_DIR"
fi

mkdir -p "$EXPORT_DIR"
echo -e "${GREEN}✓ Export directory prepared${NC}"
echo ""

# Function to download a page and extract assets
download_page() {
    local url=$1
    local output_dir=$2
    
    echo -e "${BLUE}Downloading: $url${NC}"
    
    # Download the page
    local html_file="$output_dir/index.html"
    
    # Check if this is an RSS feed
    if [[ "$url" == *"/rss"* ]]; then
        html_file="$output_dir/rss.xml"
        echo -e "${YELLOW}Detected RSS feed, saving as rss.xml${NC}"
    fi
    
    curl -s "$url" > "$html_file"
    
    if [ ! -s "$html_file" ]; then
        echo -e "${YELLOW}Warning: Empty response from $url${NC}"
        return
    fi
    
    # Extract CSS links
    local css_links=$(grep -o 'href="[^"]*\.css"' "$html_file" | sed 's/href="//' | sed 's/"//' | sort -u)
    
    # Download CSS files
    for css in $css_links; do
        if [[ $css == /* ]]; then
            # Absolute path
            local css_url="$BASE_URL$css"
            local css_path="$EXPORT_DIR$css"
            local css_dir=$(dirname "$css_path")
            
            mkdir -p "$css_dir"
            echo -e "${YELLOW}Downloading CSS: $css${NC}"
            curl -s "$css_url" > "$css_path"
        fi
    done
    
    # Extract JS links
    local js_links=$(grep -o 'src="[^"]*\.js"' "$html_file" | sed 's/src="//' | sed 's/"//' | sort -u)
    
    # Download JS files
    for js in $js_links; do
        if [[ $js == /* ]]; then
            # Absolute path
            local js_url="$BASE_URL$js"
            local js_path="$EXPORT_DIR$js"
            local js_dir=$(dirname "$js_path")
            
            mkdir -p "$js_dir"
            echo -e "${YELLOW}Downloading JS: $js${NC}"
            curl -s "$js_url" > "$js_path"
        fi
    done
    
    # Extract image links
    local img_links=$(grep -o 'src="[^"]*\.\(png\|jpg\|jpeg\|gif\|svg\|ico\)"' "$html_file" | sed 's/src="//' | sed 's/"//' | sort -u)
    
    # Download image files
    for img in $img_links; do
        if [[ $img == /* ]]; then
            # Absolute path
            local img_url="$BASE_URL$img"
            local img_path="$EXPORT_DIR$img"
            local img_dir=$(dirname "$img_path")
            
            mkdir -p "$img_dir"
            echo -e "${YELLOW}Downloading image: $img${NC}"
            curl -s "$img_url" > "$img_path"
        fi
    done
    
    # Extract other asset links (fonts, etc.)
    local asset_links=$(grep -o 'href="[^"]*\.\(woff\|woff2\|ttf\|eot\)"' "$html_file" | sed 's/href="//' | sed 's/"//' | sort -u)
    
    # Download asset files
    for asset in $asset_links; do
        if [[ $asset == /* ]]; then
            # Absolute path
            local asset_url="$BASE_URL$asset"
            local asset_path="$EXPORT_DIR$asset"
            local asset_dir=$(dirname "$asset_path")
            
            mkdir -p "$asset_dir"
            echo -e "${YELLOW}Downloading asset: $asset${NC}"
            curl -s "$asset_url" > "$asset_path"
        fi
    done
    
    # Extract page links for recursive crawling
    local page_links=$(grep -o 'href="[^"]*"' "$html_file" | sed 's/href="//' | sed 's/"//' | grep -E '^/' | grep -v '^/#\|^/mailto:' | sort -u)
    
    # Download linked pages (basic recursion)
    for link in $page_links; do
        if [[ $link == /* ]] && [[ $link != "/" ]]; then
            local page_url="$BASE_URL$link"
            local page_dir="$EXPORT_DIR$link"
            
            # Skip if already downloaded
            if [ ! -d "$page_dir" ]; then
                mkdir -p "$page_dir"
                download_page "$page_url" "$page_dir"
            fi
        fi
    done
}

# Start the export process
echo -e "${BLUE}Starting robust export process...${NC}"
echo "Base URL: $BASE_URL"
echo "Export directory: $EXPORT_DIR"
echo ""

# Download starting from the home page
download_page "$BASE_URL" "$EXPORT_DIR"

# Ensure RSS feed is properly exported
echo -e "${BLUE}Exporting RSS feed...${NC}"
if [ -d "$EXPORT_DIR/blog" ]; then
    mkdir -p "$EXPORT_DIR/blog"
    curl -s "$BASE_URL/blog/rss" > "$EXPORT_DIR/blog/rss.xml"
    echo -e "${GREEN}✓ RSS feed exported as blog/rss.xml${NC}"
fi

# Copy theme assets directly
echo -e "${BLUE}Copying theme assets...${NC}"
if [ -d "themes/default/assets" ]; then
    mkdir -p "$EXPORT_DIR/themes/default/assets"
    cp -r themes/default/assets/* "$EXPORT_DIR/themes/default/assets/" 2>/dev/null || true
    echo -e "${GREEN}✓ Theme assets copied${NC}"
fi

# Copy uploads directory
echo -e "${BLUE}Copying uploads...${NC}"
if [ -d "uploads" ]; then
    mkdir -p "$EXPORT_DIR/uploads"
    cp -r uploads/* "$EXPORT_DIR/uploads/" 2>/dev/null || true
    echo -e "${GREEN}✓ Uploads copied${NC}"
fi

echo ""
echo -e "${GREEN}✓ Export completed successfully!${NC}"
echo ""

# Show export statistics
echo -e "${BLUE}Export Statistics:${NC}"
echo "Total files: $(find "$EXPORT_DIR" -type f | wc -l)"
echo "HTML files: $(find "$EXPORT_DIR" -name "*.html" | wc -l)"
echo "CSS files: $(find "$EXPORT_DIR" -name "*.css" | wc -l)"
echo "JS files: $(find "$EXPORT_DIR" -name "*.js" | wc -l)"
echo "Image files: $(find "$EXPORT_DIR" -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.gif" -o -name "*.svg" | wc -l)"
echo ""

# Show the export directory structure
echo -e "${BLUE}Export Directory Structure:${NC}"
find "$EXPORT_DIR" -type f | head -20

echo ""
echo -e "${GREEN}Static site is available in the '$EXPORT_DIR' directory${NC}"
echo "This robust export ensures all CSS, JS, and assets are properly downloaded." 