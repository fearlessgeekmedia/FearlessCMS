# FearlessCMS Export System

This directory contains the export system for converting your FearlessCMS site to static HTML for deployment to static hosting services.

## Why Use This Export System?

The new streamlined export approach is much better than the previous Node.js script because:

- **No plugin compatibility issues** - whatever works in the browser will work in the export
- **Always up-to-date** - automatically includes any new plugins, themes, or features
- **Simpler maintenance** - no need to update the export script when the CMS changes
- **Full functionality** - includes any JavaScript-rendered content, dynamic features, etc.
- **Complete asset handling** - automatically downloads CSS, JS, images, and all dependencies

## Prerequisites

1. **FearlessCMS development server running** on localhost:8000
2. **curl** - Used for downloading pages and assets

### Starting the Development Server

```bash
nix-shell -p php81 --run "export FCMS_DEBUG=true && ./serve.sh"
```

### Installing curl

**Ubuntu/Debian:**
```bash
sudo apt install curl
```

**CentOS/RHEL:**
```bash
sudo yum install curl
```

**macOS:**
```bash
brew install curl
```

**NixOS:**
```bash
nix-env -iA nixpkgs.curl
```

## Usage

### Using the Robust Export Script

```bash
./export-robust.sh
```

This script will:
- Check if localhost:8000 is accessible
- Clean the export directory
- Download the main page and extract all asset links
- Recursively crawl linked pages
- Download all CSS, JS, images, and other assets
- Copy theme assets directly for reliability
- Provide comprehensive export statistics
- Handle all asset types automatically

## Configuration

You can modify the script to change:

- **Export directory**: Change `EXPORT_DIR="export"` 
- **Base URL**: Change `BASE_URL="http://localhost:8000"`
- **Asset handling**: The script automatically handles all asset types
- **Recursion**: Built-in intelligent page crawling

## What Gets Exported

The scripts will download:

- ✅ All HTML pages
- ✅ CSS stylesheets
- ✅ JavaScript files
- ✅ Images (PNG, JPG, GIF, SVG)
- ✅ Fonts (WOFF, WOFF2, TTF, EOT)
- ✅ Other assets referenced in pages

## What Gets Excluded

- ❌ PHP files (not downloaded)
- ❌ Server-side only content
- ❌ Dynamic content that requires PHP

## Output Structure

```
export/
├── index.html                 # Home page
├── dev-roadmap/
│   └── index.html            # Dev roadmap page
├── documentation/
│   └── index.html            # Documentation page
├── blog/
│   ├── index.html            # Blog index
│   └── [post-slug]/
│       └── index.html        # Individual blog posts
├── themes/
│   └── default/
│       └── assets/
│           └── style.css     # Theme styles
├── uploads/                   # Uploaded files
└── assets/                    # Other assets
```

## Deployment

Once exported, you can deploy the `export/` directory to any static hosting service:

- **Netlify**: Drag and drop the export folder
- **Vercel**: Connect your repository and set build output to export/
- **GitHub Pages**: Push the export folder to a gh-pages branch
- **AWS S3 + CloudFront**: Upload to S3 and serve via CloudFront
- **Traditional hosting**: Upload via FTP/SFTP

## Troubleshooting

### "Cannot connect to localhost:8000"

Make sure your development server is running:
```bash
nix-shell -p php81 --run "export FCMS_DEBUG=true && ./serve.sh"
```

### "curl is not installed"

Install curl using your package manager (see Prerequisites above)

### Empty or incomplete exports

- Check if the development server is fully loaded
- Increase the `WAIT` value in the script
- Check server logs for errors
- Ensure all pages are accessible via browser

### Missing assets

- Check if assets are referenced with absolute paths
- Verify file permissions on the development server
- Check browser console for 404 errors

## Advantages Over Node.js Export

| Aspect | Node.js Script | Robust Export Script |
|--------|----------------|----------------------|
| **Plugin Support** | Manual updates required | Automatic |
| **Template Changes** | Manual updates required | Automatic |
| **Asset Handling** | Complex logic | Automatic |
| **Maintenance** | High | Zero |
| **Reliability** | Depends on script logic | Depends on browser rendering |
| **Performance** | Fast | Optimized for reliability |
| **Compatibility** | Limited | Full browser compatibility |
| **Asset Coverage** | Partial | Complete |

## Migration from Node.js Script

If you were using the old `export.js` script:

1. **Stop using it**: The Node.js approach has compatibility issues
2. **Use the new script**: `./export-robust.sh` is the recommended approach
3. **Update your deployment process**: Point to the new export directory
4. **Test thoroughly**: Ensure all content and assets are exported correctly

## Contributing

To improve the export system:

1. Test with different FearlessCMS configurations
2. Add support for more file types if needed
3. Improve error handling and reporting
4. Add configuration options for different deployment scenarios
5. Enhance asset discovery and download reliability

## License

The export system is part of FearlessCMS and follows the same license terms. 