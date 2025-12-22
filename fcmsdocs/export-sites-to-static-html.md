# Export Sites to Static HTML

FearlessCMS includes powerful export functionality that allows you to convert your dynamic PHP-based site into static HTML files for deployment to static hosting services like Netlify, Vercel, GitHub Pages, and more.

## Overview

The export system crawls your running FearlessCMS site and downloads all HTML pages, CSS stylesheets, JavaScript files, images, and other assets, creating a completely static version that can be deployed anywhere.

## Why Use Static Export?

- **Performance**: Static sites load faster and are more reliable
- **Cost**: Free hosting on services like Netlify, Vercel, GitHub Pages
- **Security**: No PHP execution, reducing attack surface
- **Scalability**: Easy to serve via CDN for global performance
- **Compatibility**: Works with any static hosting service

## Prerequisites

1. **FearlessCMS development server running** on localhost:8000
2. **wget** (recommended) or **curl** (alternative)

### Starting the Development Server

```bash
nix-shell -p php83 --run "export FCMS_DEBUG=true && ./serve.sh"
```

### Installing wget (Recommended)

**Ubuntu/Debian:**
```bash
sudo apt install wget
```

**CentOS/RHEL:**
```bash
sudo yum install wget
```

**macOS:**
```bash
brew install wget
```

**NixOS:**
```bash
nix-env -iA nixpkgs.wget
```

## Export Methods

### Option 1: Using wget (Recommended)

The `export-wget.sh` script provides comprehensive site crawling:

```bash
./export-wget.sh
```

**Features:**
- Recursive crawling to depth 3
- Automatic asset downloading
- Link conversion to relative paths
- Comprehensive error handling
- Export statistics

### Option 2: Using curl (Alternative)

For systems without wget, use the `export-curl.sh` script:

```bash
./export-curl.sh
```

**Features:**
- Basic page crawling
- Asset downloading
- Suitable for simple sites

## Configuration

You can customize the export process by editing the script variables:

```bash
# In export-wget.sh or export-curl.sh
EXPORT_DIR="export"           # Output directory
BASE_URL="http://localhost:8000"  # Source URL
DEPTH=3                       # Crawl depth
WAIT=1                        # Wait between requests (wget only)
```

## What Gets Exported

### ✅ Included
- All HTML pages (home, blog, documentation, etc.)
- CSS stylesheets and theme files
- JavaScript files and plugins
- Images (PNG, JPG, GIF, SVG)
- Fonts (WOFF, WOFF2, TTF, EOT)
- Uploaded files and media
- Plugin-generated content

### ❌ Excluded
- PHP files (server-side code)
- Dynamic content requiring PHP
- Server-side only features

## Output Structure

```
export/
├── index.html                 # Home page
├── dev-roadmap/
│   └── index.html            # Development roadmap
├── documentation/
│   └── index.html            # Documentation
├── blog/
│   ├── index.html            # Blog index
│   └── [post-slug]/
│       └── index.html        # Individual blog posts
├── themes/
│   └── default/
│       └── assets/
│           └── style.css     # Theme styles
├── uploads/                   # User uploads
└── assets/                    # Other assets
```

## Deployment

### Netlify
1. Drag and drop the `export/` folder to Netlify
2. Your site is live instantly
3. Automatic deployments on future exports

### Vercel
1. Connect your repository
2. Set build output directory to `export/`
3. Deploy automatically on commits

### GitHub Pages
1. Create a `gh-pages` branch
2. Push the export folder contents
3. Enable GitHub Pages in repository settings

### AWS S3 + CloudFront
1. Upload export folder to S3 bucket
2. Configure CloudFront distribution
3. Set S3 bucket as origin

### Traditional Hosting
1. Upload via FTP/SFTP
2. Point domain to export folder
3. Configure web server for static files

## Advanced Usage

### Custom Export Scripts

You can create custom export scripts for specific needs:

```bash
#!/bin/bash
# Custom export script
EXPORT_DIR="custom-export"
BASE_URL="http://localhost:8000"
DEPTH=5

wget \
    --recursive \
    --level=$DEPTH \
    --page-requisites \
    --adjust-extension \
    --convert-links \
    --directory-prefix="$EXPORT_DIR" \
    "$BASE_URL"
```

### Scheduled Exports

Set up automated exports using cron:

```bash
# Export every day at 2 AM
0 2 * * * cd /path/to/fearlesscms && ./export-wget.sh
```

### CI/CD Integration

Integrate with GitHub Actions or other CI/CD systems:

```yaml
# .github/workflows/export.yml
name: Export Site
on:
  push:
    branches: [main]
jobs:
  export:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Start FearlessCMS
        run: |
          # Start server and export
          ./export-wget.sh
      - name: Deploy to Netlify
        uses: nwtgck/actions-netlify@v1.2
        with:
          publish-dir: './export'
```

## Troubleshooting

### Common Issues

**"Cannot connect to localhost:8000"**
- Ensure development server is running
- Check if port 8000 is available
- Verify firewall settings

**Empty or incomplete exports**
- Increase wait time between requests
- Check server logs for errors
- Ensure all pages are accessible via browser

**Missing assets**
- Verify file permissions on server
- Check for absolute vs. relative paths
- Review browser console for 404 errors

**Large export files**
- Exclude unnecessary file types
- Reduce crawl depth
- Use selective export scripts

### Performance Optimization

- **Reduce crawl depth** for large sites
- **Increase wait time** to avoid overwhelming server
- **Use selective crawling** for specific sections
- **Exclude unnecessary file types** to reduce size

## Best Practices

1. **Test thoroughly** before deploying
2. **Use staging environment** for testing exports
3. **Monitor export size** and optimize as needed
4. **Set up automated exports** for production sites
5. **Keep backups** of original dynamic site
6. **Document custom configurations** for team members

## Migration from Old Export System

If you were using the previous Node.js export script:

1. **Stop using** the old `export.js` script
2. **Use the new scripts**: `./export-wget.sh` is recommended
3. **Update deployment process** to use new export directory
4. **Test thoroughly** to ensure all content is exported correctly

## Support

For issues with the export system:

1. Check the troubleshooting section above
2. Review server logs for errors
3. Test with a simple site first
4. Verify all prerequisites are met
5. Check the FearlessCMS community forums

## Examples

### Basic Export
```bash
# Start server
nix-shell -p php83 --run "export FCMS_DEBUG=true && ./serve.sh"

# In another terminal, export
./export-wget.sh
```

### Custom Export Directory
```bash
# Edit script to change export directory
sed -i 's/EXPORT_DIR="export"/EXPORT_DIR="my-static-site"/' export-wget.sh
./export-wget.sh
```

### Selective Export
```bash
# Export only specific sections by modifying the script
# or by running multiple targeted exports
```

The export system provides a robust, maintainable way to create static versions of your FearlessCMS sites, ensuring compatibility with all plugins and themes while maintaining the flexibility to deploy anywhere. 