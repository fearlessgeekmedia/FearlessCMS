/**
 * FearlessCMS Export Script
 * 
 * This script exports the CMS content to a static HTML site.
 * 
 * IMPORTANT: This script has been updated to handle HTML content instead of Markdown.
 * All content files (.md) are expected to contain HTML content with JSON frontmatter.
 * 
 * Features:
 * - Exports HTML content directly (no Markdown conversion)
 * - Maintains all existing functionality (RSS, blog, etc.)
 * - Supports both HTML and Markdown-style links for backward compatibility
 * - Generates static site with proper relative paths
 */

const fs = require('fs-extra');
const path = require('path');
const handlebars = require('handlebars');

// Configuration
const config = JSON.parse(fs.readFileSync('config/config.json', 'utf8'));
const theme = config.active_theme;
const siteName = config.site_name;
const customCss = config.custom_css || '';

// Export directory
const exportDir = 'export';

// Content directory
const contentDir = 'content';

// Helper function to process modules
function processModules(content) {
    let modifiedContent = content;
    let match;
    const moduleRegex = /{{module=([^}]+)}}/g;
    while ((match = moduleRegex.exec(modifiedContent)) !== null) {
        const moduleFileName = match[1];
        const modulePath = path.join('themes', theme, 'templates', moduleFileName);
        let moduleContent = '';
        if (fs.existsSync(modulePath)) {
            moduleContent = fs.readFileSync(modulePath, 'utf8');
            moduleContent = processModules(moduleContent); // Recursively process nested modules
        } else if (fs.existsSync(`${modulePath}.mod`)) { // Check for .mod extension if not found
            moduleContent = fs.readFileSync(`${modulePath}.mod`, 'utf8');
            moduleContent = processModules(moduleContent); // Recursively process nested modules
        } else {
            console.warn(`Warning: Module file not found: ${moduleFileName}`);
        }
        modifiedContent = modifiedContent.replace(match[0], moduleContent);
        // Reset regex lastIndex to avoid infinite loops with global regex in while loop
        moduleRegex.lastIndex = 0;
    }
    return modifiedContent;
}

// Helper function to build meta tags
function buildMetaTags({ title, description, socialImage, siteTitle, titleSeparator, appendSiteTitle }) {
    // Build full title
    let fullTitle = title;
    if (appendSiteTitle && title && siteTitle) {
        fullTitle += ` ${titleSeparator} ${siteTitle}`;
    } else if (!title && siteTitle) {
        fullTitle = siteTitle;
    }
    
    let metaTags = '';
    
    // Basic meta tags
    if (description) {
        metaTags += `<meta name="description" content="${description}">\n`;
    }
    
    // Open Graph meta tags
    metaTags += '<meta property="og:type" content="website">\n';
    if (fullTitle) {
        metaTags += `<meta property="og:title" content="${fullTitle}">\n`;
    }
    if (description) {
        metaTags += `<meta property="og:description" content="${description}">\n`;
    }
    if (socialImage) {
        metaTags += `<meta property="og:image" content="${socialImage}">\n`;
    }
    
    // Twitter Card meta tags
    metaTags += '<meta name="twitter:card" content="summary_large_image">\n';
    if (fullTitle) {
        metaTags += `<meta name="twitter:title" content="${fullTitle}">\n`;
    }
    if (description) {
        metaTags += `<meta name="twitter:description" content="${description}">\n`;
    }
    if (socialImage) {
        metaTags += `<meta name="twitter:image" content="${socialImage}">\n`;
    }
    
    return metaTags;
}

// Helper function to convert absolute paths to relative paths
function makeRelativePath(absolutePath, currentDepth = 0) {
    if (!absolutePath.startsWith('/')) {
        return absolutePath;
    }
    
    const relativePath = '../'.repeat(currentDepth) + absolutePath.substring(1);
    return relativePath;
}

// Helper function to calculate page depth
function calculatePageDepth(exportPath) {
    const relativePath = path.relative(exportDir, exportPath);
    const depth = relativePath.split(path.sep).length - 1;
    return depth;
}

// Ensure export directory exists and is clean
if (fs.existsSync(exportDir)) {
    fs.removeSync(exportDir);
}
fs.mkdirSync(exportDir, { recursive: true });

// Create custom CSS and JS files
if (customCss) {
    const customCssPath = path.join(exportDir, 'assets', 'custom.css');
    fs.mkdirSync(path.dirname(customCssPath), { recursive: true });
    fs.writeFileSync(customCssPath, customCss);
}

// Generate parallax plugin assets
const parallaxCssPath = path.join(exportDir, 'assets', 'parallax.css');
const parallaxJsPath = path.join(exportDir, 'assets', 'parallax.js');
fs.mkdirSync(path.dirname(parallaxCssPath), { recursive: true });
fs.writeFileSync(parallaxCssPath, generateParallaxCSS());
fs.writeFileSync(parallaxJsPath, generateParallaxJS());
console.log('Generated parallax plugin assets: parallax.css and parallax.js');


// Copy the entire theme directory (css, js, assets, etc)
const themeDir = path.join('themes', theme);
const exportThemeDir = path.join(exportDir, 'themes', theme);
if (fs.existsSync(themeDir)) {
    fs.copySync(themeDir, exportThemeDir, { recursive: true });
    console.log(`Copied theme directory: ${themeDir} -> ${exportThemeDir}`);
}

// Copy site assets
const siteAssetsDir = 'assets';
const exportSiteAssetsDir = path.join(exportDir, 'assets');
if (fs.existsSync(siteAssetsDir)) {
    fs.copySync(siteAssetsDir, exportSiteAssetsDir, { recursive: true });
}

// Copy uploads
const uploadsDir = 'uploads';
const exportUploadsDir = path.join(exportDir, 'uploads');
if (fs.existsSync(uploadsDir)) {
    fs.copySync(uploadsDir, exportUploadsDir, { recursive: true });
}

// Copy widgets.json for static site
const widgetsFile = 'config/widgets.json';
const exportWidgetsFile = path.join(exportDir, 'config', 'widgets.json');
if (fs.existsSync(widgetsFile)) {
    fs.mkdirSync(path.dirname(exportWidgetsFile), { recursive: true });
    fs.copySync(widgetsFile, exportWidgetsFile);
    console.log('Copied widgets.json to export');
}

// Read theme options
const themeOptionsFile = path.join('config', 'theme_options.json');
let themeOptions = {};
if (fs.existsSync(themeOptionsFile)) {
    themeOptions = JSON.parse(fs.readFileSync(themeOptionsFile, 'utf8'));
}

// Read site configuration
const configFile = path.join('config', 'config.json');
let siteConfig = {};
if (fs.existsSync(configFile)) {
    siteConfig = JSON.parse(fs.readFileSync(configFile, 'utf8'));
}

// Generate main menu
function generateMenu(pageDepth = 0) {
    let menusJson;
    try {
        menusJson = JSON.parse(fs.readFileSync('config/menus.json', 'utf8'));
    } catch (error) {
        console.warn('Warning: Could not read menus.json, using empty menu');
        return '';
    }

    const mainMenu = menusJson.main;
    if (!mainMenu || !mainMenu.items || !Array.isArray(mainMenu.items)) {
        console.warn('Warning: Invalid main menu structure, using empty menu');
        return '';
    }

    let menuHtml = `<ul class="${mainMenu.menu_class || 'main-nav'}">`;
    mainMenu.items.forEach(item => {
        const label = item.label || '';
        const url = item.url ? makeRelativePath(item.url, pageDepth) : '#';
        const className = item.class || '';
        const target = item.target ? ` target="${item.target}"` : '';
        
        menuHtml += `<li class="${item.children && item.children.length > 0 ? 'has-submenu' : ''}">`;
        menuHtml += `<a href="${url}" class="${className}"${target}>${label}</a>`;
        
        // Add submenu if children exist
        if (item.children && item.children.length > 0) {
            menuHtml += '<ul class="submenu">';
            item.children.forEach(child => {
                const childLabel = child.label || '';
                const childUrl = child.url ? makeRelativePath(child.url, pageDepth) : '#';
                const childClassName = child.class || '';
                const childTarget = child.target ? ` target="${child.target}"` : '';
                
                menuHtml += `<li><a href="${childUrl}" class="${childClassName}"${childTarget}>${childLabel}</a></li>`;
            });
            menuHtml += '</ul>';
        }
        
        menuHtml += '</li>';
    });
    menuHtml += '</ul>';
    
    return menuHtml;
}

// Generate sidebar
function generateSidebar(sidebarName) {
    let widgetsJson;
    try {
        widgetsJson = JSON.parse(fs.readFileSync('config/widgets.json', 'utf8'));
    } catch (error) {
        console.warn('Warning: Could not read widgets.json, using empty sidebar');
        return '';
    }

    const sidebar = widgetsJson[sidebarName];
    if (!sidebar || !sidebar.widgets || !Array.isArray(sidebar.widgets)) {
        console.warn(`Warning: Invalid sidebar structure for ${sidebarName}, using empty sidebar`);
        return '';
    }

    let sidebarHtml = '';
    sidebar.widgets.forEach(widget => {
        const type = widget.type || 'text';
        const title = widget.title || '';
        let content = widget.content || '';
        
        // For HTML widgets, don't escape the content
        if (type !== 'html') {
            content = content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        
        sidebarHtml += `<div class="widget widget-${type}">`;
        if (title) {
            sidebarHtml += `<h3 class="widget-title">${title}</h3>`;
        }
        sidebarHtml += `<div class="widget-content">${content}</div>`;
        sidebarHtml += '</div>';
    });
    return sidebarHtml;
}

// Generate RSS feed
function generateRssFeed(posts) {
    let rss = '<?xml version="1.0" encoding="UTF-8"?>\n';
    rss += '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">\n';
    rss += '  <channel>\n';
    rss += `    <title>${siteName} - Blog</title>\n`;
    rss += `    <link>https://fearlesscms.com/blog</link>\n`;
    rss += `    <description>${siteConfig.site_description || ''}</description>\n`;
    rss += '    <language>en-us</language>\n';
    rss += `    <lastBuildDate>${new Date().toUTCString()}</lastBuildDate>\n`;
    rss += `    <atom:link href="https://fearlesscms.com/blog/rss.xml" rel="self" type="application/rss+xml" />\n`;

    posts.forEach(post => {
        const postUrl = `https://fearlesscms.com/blog/${post.slug}`;
        const pubDate = new Date(post.date).toUTCString();

        rss += '    <item>\n';
        rss += `      <title>${post.title}</title>\n`;
        rss += `      <link>${postUrl}</link>\n`;
        rss += `      <guid>${postUrl}</guid>\n`;
        rss += `      <pubDate>${pubDate}</pubDate>\n`;
        rss += `      <description><![CDATA[${post.content.substring(0, 300) + '...'}]]></description>\n`;
        rss += '    </item>\n';
    });

    rss += '  </channel>\n';
    rss += '</rss>\n';

    return rss;
}

// Generate HTML version of RSS feed
function generateRssHtml(posts) {
    let html = '<div class="max-w-4xl mx-auto px-4 py-8">';
    html += '<h1 class="text-3xl font-bold mb-8">RSS Feed</h1>';
    html += '<div class="space-y-8">';
    posts.forEach(post => {
        const postUrl = `blog/${post.slug}/`; // Relative path for static site
        html += `<article class="border-b pb-8"><h2 class="text-2xl font-bold mb-2"><a href="${postUrl}" class="text-blue-600 hover:underline">${post.title}</a></h2><div class="text-gray-600 mb-4">${post.date}</div><div class="prose">${post.content.substring(0, 300) + '...'}</div><a href="${postUrl}" class="text-blue-600 hover:underline mt-4 inline-block">Read more →</a></article>`;
    });
    html += '</div></div>';
    return html;
}

// Helper function to process parallax shortcodes
function processParallaxShortcodes(content) {
    // Process parallax shortcodes similar to the PHP plugin
    const parallaxRegex = /\[parallax_section([^\]]*)\](.*?)\[\/parallax_section\]/gs;
    
    return content.replace(parallaxRegex, (match, attributes, innerContent) => {
        // Parse attributes
        const id = extractAttribute(attributes, 'id') || 'parallax-' + Math.random().toString(36).substr(2, 9);
        const backgroundImage = extractAttribute(attributes, 'background_image') || '';
        const speed = extractAttribute(attributes, 'speed') || '0.5';
        const effect = extractAttribute(attributes, 'effect') || 'scroll';
        const overlayColor = extractAttribute(attributes, 'overlay_color') || 'rgba(0,0,0,0.3)';
        const overlayOpacity = extractAttribute(attributes, 'overlay_opacity') || '0.3';
        
        if (!id || !backgroundImage) {
            return '<div class="alert alert-danger">Parallax section requires both id and background_image attributes</div>';
        }
        
        // Clean up inner content
        let cleanContent = innerContent.trim();
        
        // Generate CSS class
        const cssClass = 'parallax-section-' + id.replace(/[^a-zA-Z0-9_-]/g, '');
        
        // Build parallax HTML
        let output = `<div id="${id}" class="${cssClass} parallax-section" data-speed="${speed}" data-effect="${effect}" data-overlay-color="${overlayColor}" data-overlay-opacity="${overlayOpacity}">`;
        output += `<div class="parallax-background" style="background-image: url('${backgroundImage}');"></div>`;
        output += `<div class="parallax-overlay" style="background-color: ${overlayColor}; opacity: ${overlayOpacity};"></div>`;
        output += `<div class="parallax-content">`;
        output += cleanContent;
        output += `</div>`;
        output += `</div>`;
        
        return output;
    });
}

// Helper function to extract attributes from shortcode
function extractAttribute(attributeString, attributeName) {
    const regex = new RegExp(`${attributeName}=["']([^"']+)["']`);
    const match = attributeString.match(regex);
    return match ? match[1] : null;
}

// Generate parallax CSS
function generateParallaxCSS() {
    return `
/* Parallax Plugin Styles */
.parallax-section {
    position: relative;
    overflow: hidden;
    width: 100%;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 500px;
    height: auto;
    background: transparent;
}

.parallax-background {
    position: absolute;
    top: 60% !important;
    left: 50%;
    width: 120%;
    height: 120%;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    background-attachment: scroll;
    z-index: 1;
    transform: translate(-50%, -50%);
    will-change: transform;
    background-clip: border-box;
}

.parallax-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    pointer-events: none;
}

.parallax-content {
    position: relative;
    z-index: 3;
    padding: 4rem 2rem;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 100%;
    height: 100%;
}

.parallax-content h1,
.parallax-content h2,
.parallax-content h3,
.parallax-content h4,
.parallax-content h5,
.parallax-content h6 {
    text-shadow: 3px 3px 6px rgba(0,0,0,0.9);
    color: white !important;
}

.parallax-content p {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
    color: white !important;
}

.parallax-content a {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
    color: white !important;
}

.parallax-content span,
.parallax-content strong,
.parallax-content em,
.parallax-content code,
.parallax-content mark {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.9);
    color: white !important;
}

.parallax-content > *:first-child {
    margin-top: 0;
}

.parallax-content > *:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .parallax-section {
        min-height: 400px;
    }
    
    .parallax-content {
        padding: 2rem 1rem;
        min-height: 300px;
    }
    
    .parallax-background {
        background-attachment: scroll;
        top: 60% !important;
        width: 130%;
        height: 130%;
    }
}

@media (max-width: 480px) {
    .parallax-section {
        min-height: 350px;
    }
    
    .parallax-content {
        padding: 1.5rem 1rem;
        min-height: 250px;
    }
    
    .parallax-background {
        top: 60% !important;
        width: 140%;
        height: 140%;
    }
}
`;
}

// Generate parallax JavaScript
function generateParallaxJS() {
    return `
/* Parallax Plugin JavaScript */
(function() {
    'use strict';
    
    function initParallax() {
        const parallaxSections = document.querySelectorAll('.parallax-section');
        
        if (parallaxSections.length === 0) return;
        
        // Check if user prefers reduced motion
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (prefersReducedMotion) {
            console.log('Parallax disabled due to user preference for reduced motion');
            return;
        }
        
        function updateParallax() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            parallaxSections.forEach(section => {
                const rect = section.getBoundingClientRect();
                const speed = parseFloat(section.dataset.speed) || 0.5;
                const effect = section.dataset.effect || 'scroll';
                
                // Process all sections, not just those in viewport for initial positioning
                const background = section.querySelector('.parallax-background');
                if (background) {
                    let yPos;
                    
                    if (effect === 'scroll') {
                        yPos = (scrollTop - section.offsetTop) * speed;
                    } else if (effect === 'fixed') {
                        yPos = 0;
                    } else {
                        yPos = (scrollTop - section.offsetTop) * speed;
                    }
                    
                    // Use transform3d for better performance and ensure proper initial positioning
                    background.style.transform = \`translate3d(-50%, calc(-50% + \${yPos}px), 0)\`;
                    
                    // Ensure background covers the entire section
                    background.style.minWidth = '140%';
                    background.style.minHeight = '140%';
                }
            });
        }
        
        // Throttle scroll events for performance
        let ticking = false;
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateParallax);
                ticking = true;
                setTimeout(() => { ticking = false; }, 16); // ~60fps
            }
        }
        
        // Initialize backgrounds immediately
        parallaxSections.forEach(section => {
            const background = section.querySelector('.parallax-background');
            if (background) {
                // Ensure immediate proper sizing and positioning
                background.style.transform = 'translate3d(-50%, -50%, 0)';
                background.style.minWidth = '150%';
                background.style.minHeight = '150%';
            }
        });
        
        // Initial call
        updateParallax();
        
        // Add scroll listener
        window.addEventListener('scroll', requestTick, { passive: true });
        window.addEventListener('resize', updateParallax, { passive: true });
        
        console.log(\`Initialized parallax for \${parallaxSections.length} sections\`);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initParallax);
    } else {
        initParallax();
    }
})();
`;
}

// Process HTML content files recursively
function processContentDirectory(dir, basePath = '') {
    // First, read all HTML content files to build the page hierarchy
    const pages = {};
    fs.readdirSync(dir).forEach(file => {
        if (path.extname(file) === '.md') { // Still support .md files for backward compatibility
            const fullPath = path.join(dir, file);
            const content = fs.readFileSync(fullPath, 'utf8');
            const metadataMatch = content.match(/^<!--\s*json\s*({[\s\S]*?})\s*-->/);
            const metadata = metadataMatch ? JSON.parse(metadataMatch[1]) : {};
            const filename = path.basename(file, '.md');
            
            console.log(`DEBUG: Processing HTML file ${file} (converted from Markdown)`);
            console.log(`DEBUG: Metadata match: ${metadataMatch ? metadataMatch[1] : 'none'}`);
            console.log(`DEBUG: Parsed metadata:`, metadata);
            
            // Extract links from content to determine parent-child relationships
            // For HTML content, look for both href attributes and Markdown-style links
            const hrefLinks = content.match(/href="([^"]+)"/g) || [];
            const markdownLinks = content.match(/\[([^\]]+)\]\(([^)]+)\)/g) || [];
            
            const childPages = [
                ...hrefLinks.map(link => {
                    const match = link.match(/href="([^"]+)"/);
                    return match ? match[1].replace(/^\//, '') : null;
                }),
                ...markdownLinks.map(link => {
                    const match = link.match(/\[([^\]]+)\]\(([^)]+)\)/);
                    return match ? match[2].replace(/^\//, '') : null;
                })
            ].filter(Boolean);
            
            pages[filename] = {
                metadata,
                childPages,
                content,
                fullPath
            };
        }
    });

    // Process blog posts
    const blogPostsFile = path.join(contentDir, 'blog_posts.json');
    if (fs.existsSync(blogPostsFile)) {
        const posts = JSON.parse(fs.readFileSync(blogPostsFile, 'utf8'));
        const publishedPosts = posts.filter(post => post.status === 'published');
        
        // Sort posts by date (newest first)
        publishedPosts.sort((a, b) => new Date(b.date) - new Date(a.date));

        // Create blog directory if it doesn't exist
        const blogDir = path.join(exportDir, 'blog');
        if (!fs.existsSync(blogDir)) {
            fs.mkdirSync(blogDir, { recursive: true });
        }

        // Generate RSS feed
        const rssFeed = generateRssFeed(publishedPosts);
        fs.writeFileSync(path.join(blogDir, 'rss.xml'), rssFeed);

        // Generate and save RSS HTML page
        const rssHtmlContent = generateRssHtml(publishedPosts);
        const rssHtmlExportPath = path.join(blogDir, 'rss.html');
        fs.writeFileSync(rssHtmlExportPath, rssHtmlContent);

        // Add blog index page
        let blogIndexContent = '<div class="max-w-4xl mx-auto px-4 py-8">';
        blogIndexContent += '<h1 class="text-3xl font-bold mb-8">Blog Posts</h1>';
        blogIndexContent += '<div class="mb-4"><a href="rss.xml" class="text-blue-600 hover:underline">📡 RSS Feed</a> | <a href="rss.html" class="text-blue-600 hover:underline">HTML RSS Feed</a></div>';
        blogIndexContent += '<div class="space-y-8">';
        publishedPosts.forEach(post => {
            // For blog posts, we expect HTML content, so no need for Markdown conversion
            const postExcerpt = post.content ? post.content.substring(0, 300) + '...' : '';
            blogIndexContent += `<article class="border-b pb-8"><h2 class="text-2xl font-bold mb-2"><a href="${post.slug}/" class="text-blue-600 hover:underline">${post.title}</a></h2><div class="text-gray-600 mb-4">${post.date}</div><div class="prose">${postExcerpt}</div><a href="${post.slug}/" class="text-blue-600 hover:underline mt-4 inline-block">Read more →</a></article>`;
        });
        blogIndexContent += '</div></div>';

        pages['blog'] = {
            metadata: { title: 'Blog', template: 'page' },
            childPages: publishedPosts.map(p => p.slug),
            content: blogIndexContent,
            fullPath: path.join(contentDir, 'blog.html')
        };

        // Add individual blog posts
        publishedPosts.forEach(post => {
            let postContent = '<article class="max-w-4xl mx-auto px-4 py-8">';
            if (post.featured_image) {
                postContent += `<div class="mb-8"><img src="../${post.featured_image}" alt="${post.title}" class="w-full h-96 object-cover rounded-lg shadow-lg"></div>`;
            }
            postContent += `<div class="text-gray-600 mb-8">${post.date}</div>`;
            // Blog post content is already HTML, so no conversion needed
            postContent += `<div class="prose max-w-none">${post.content}</div>`;
            postContent += '</article>';

            pages[`blog/${post.slug}`] = {
                metadata: { title: post.title, template: 'post' },
                childPages: [],
                content: postContent,
                fullPath: path.join(contentDir, 'blog', `${post.slug}.html`)
            };
        });
    }
    
    // Now process each page
    Object.entries(pages).forEach(([filename, page]) => {
        const { metadata, content, fullPath } = page;
        const template = metadata.template || 'page';
        const heroBanner = metadata.heroBanner || metadata.hero_banner || '';
        const logo = metadata.logo || '';
        const sidebar = metadata.sidebar || '';
        const pageTitle = metadata.title || filename;

        // Get template
        const templatePath = path.join('themes', theme, 'templates', `${template}.html`);
        let templateContent = fs.readFileSync(templatePath, 'utf8');

        // Process modules
        templateContent = processModules(templateContent);

        // Extract sidebar name from template
        const sidebarMatch = templateContent.match(/{{sidebar=([^}]+)}}/);
        const templateSidebar = sidebarMatch ? sidebarMatch[1] : null;
        // Use template sidebar if metadata explicitly requests one, or if template is designed for sidebars
        const sidebarName = metadata.sidebar || (template === 'sidebar-only' ? templateSidebar : null);

        // Replace custom syntax with Handlebars syntax
        templateContent = templateContent.replace(/{{sidebar=([^}]+)}}/g, '{{{sidebar}}}');
        templateContent = templateContent.replace(/{{menu=([^}]+)}}/g, '{{{mainMenu}}}');
        templateContent = templateContent.replace(/{{content}}/g, '{{{content}}}');
        templateContent = templateContent.replace(/{{include=([^}]+)}}/g, '{{> $1}}');

        // Compile template
        const compiledTemplate = handlebars.compile(templateContent);

        // Register partials if they exist
        const partialsDir = path.join('themes', theme, 'templates');
        if (fs.existsSync(partialsDir)) {
            fs.readdirSync(partialsDir).forEach(file => {
                if ((file.endsWith('.html') || file.endsWith('.mod')) && file !== `${template}.html`) {
                    const partialName = path.basename(file, path.extname(file));
                    const partialContent = fs.readFileSync(path.join(partialsDir, file), 'utf8');
                    handlebars.registerPartial(partialName, partialContent);
                }
            });
        }

        // Handle missing partials gracefully
        handlebars.registerPartial('ad-area.html', '<!-- Ad area placeholder -->');
        handlebars.registerPartial('ad-area', '<!-- Ad area placeholder -->');

        // Determine export path based on parent-child relationships
        let exportFilePath;
        if (filename === 'home' && basePath === '') {
            // For the home page, create it in the root export directory
            exportFilePath = path.join(exportDir, 'index.html');
        } else {
            // Check if this page is a child of another page
            const parentPage = Object.entries(pages).find(([_, p]) => p.childPages.includes(filename));
            if (parentPage) {
                // Create the page under its parent's directory
                exportFilePath = path.join(exportDir, parentPage[0], filename, 'index.html');
            } else {
                // Create the page in its own directory
                exportFilePath = path.join(exportDir, filename, 'index.html');
            }
        }

        // Calculate page depth for relative paths
        const pageDepth = calculatePageDepth(exportFilePath);

        // Generate menu and sidebar
        const mainMenu = generateMenu(pageDepth);
        const sidebarContent = sidebarName ? generateSidebar(sidebarName) : '';
        const hasSidebar = sidebarContent && sidebarContent.trim() !== '';

        // Process content (now HTML instead of Markdown)
        let htmlContent = content.replace(/^<!--\s*json\s*.*?-->\n/, '');
        
        // Process parallax shortcodes
        htmlContent = processParallaxShortcodes(htmlContent);
        
        // Content is already HTML, so no conversion needed

        // Use logo and heroBanner from metadata or theme options
        const logoValue = logo ? `uploads/theme/${logo}` : themeOptions.logo || '';
        const heroBannerValue = heroBanner ? `uploads/theme/${heroBanner}` : themeOptions.herobanner || '';

        // Debug output for logo and heroBanner
        console.log(`DEBUG: For page '${filename}': logo='${logoValue}', heroBanner='${heroBannerValue}'`);

        // Get SEO settings
        const seoSettingsFile = path.join('config', 'seo_settings.json');
        let seoSettings = {};
        if (fs.existsSync(seoSettingsFile)) {
            seoSettings = JSON.parse(fs.readFileSync(seoSettingsFile, 'utf8'));
        }

        // Build meta tags
        const metaTags = buildMetaTags({
            title: pageTitle,
            description: metadata.description || seoSettings.site_description || '',
            socialImage: metadata.social_image || seoSettings.social_image || '',
            siteTitle: seoSettings.site_title || siteName,
            titleSeparator: seoSettings.title_separator || '-',
            appendSiteTitle: seoSettings.append_site_title !== false
        });
        
        // Check if content contains parallax sections
        const hasParallax = htmlContent.includes('parallax-section');
        let parallaxAssets = '';
        if (hasParallax) {
            const cssPath = makeRelativePath('/assets/parallax.css', pageDepth);
            const jsPath = makeRelativePath('/assets/parallax.js', pageDepth);
            parallaxAssets = `<link rel="stylesheet" href="${cssPath}">`;
            parallaxAssets += `<script src="${jsPath}"></script>`;
        }
        
        // Render template
        let renderedHtml = compiledTemplate({
            title: pageTitle,
            siteName,
            siteDescription: siteConfig.site_description || '',
            theme,
            currentYear: new Date().getFullYear(),
            custom_css: customCss,
            parallaxAssets: parallaxAssets,
            
            mainMenu,
            sidebar: hasSidebar ? sidebarContent : false,
            content: htmlContent,
            heroBanner: heroBannerValue,
            logo: logoValue,
            metaTags
        });

        // Convert absolute paths to relative paths
        renderedHtml = renderedHtml.replace(/href="\/themes\/([^"]+)"/g, `href="${makeRelativePath('/themes/$1', pageDepth)}"`);
        renderedHtml = renderedHtml.replace(/src="\/themes\/([^"]+)"/g, `src="${makeRelativePath('/themes/$1', pageDepth)}"`);
        renderedHtml = renderedHtml.replace(/href="\/uploads\/([^"]+)"/g, `href="${makeRelativePath('/uploads/$1', pageDepth)}"`);
        renderedHtml = renderedHtml.replace(/src="\/uploads\/([^"]+)"/g, `src="${makeRelativePath('/uploads/$1', pageDepth)}"`);
        renderedHtml = renderedHtml.replace(/href="\/assets\/([^"]+)"/g, `href="${makeRelativePath('/assets/$1', pageDepth)}"`);
        renderedHtml = renderedHtml.replace(/src="\/assets\/([^"]+)"/g, `src="${makeRelativePath('/assets/$1', pageDepth)}"`);

        fs.mkdirSync(path.dirname(exportFilePath), { recursive: true });
        fs.writeFileSync(exportFilePath, renderedHtml);
        console.log(`Created page: ${filename}`);
    });
}

// Start processing content directory
processContentDirectory(contentDir);

console.log('Export completed successfully!');
console.log(`Static site is available in the '${exportDir}' directory`);