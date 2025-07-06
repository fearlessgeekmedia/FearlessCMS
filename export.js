const fs = require('fs-extra');
const path = require('path');
const { marked } = require('marked');
const handlebars = require('handlebars');

// Configuration
const config = JSON.parse(fs.readFileSync('config/config.json', 'utf8'));
const theme = config.active_theme;
const siteName = config.site_name;
const customCss = config.custom_css || '';
const customJs = config.custom_js || '';

// Export directory
const exportDir = 'export';

// Content directory
const contentDir = 'content';

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

if (customJs) {
    const customJsPath = path.join(exportDir, 'assets', 'custom.js');
    fs.mkdirSync(path.dirname(customJsPath), { recursive: true });
    fs.writeFileSync(customJsPath, customJs);
}

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
function generateMenu(currentPage = '') {
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
        const url = item.url || '#';
        const className = item.class || '';
        const target = item.target ? ` target="${item.target}"` : '';
        
        menuHtml += `<li class="${item.children && item.children.length > 0 ? 'has-submenu' : ''}">`;
        menuHtml += `<a href="${url}" class="${className}"${target}>${label}</a>`;
        
        // Add submenu if children exist
        if (item.children && item.children.length > 0) {
            menuHtml += '<ul class="submenu">';
            item.children.forEach(child => {
                const childLabel = child.label || '';
                const childUrl = child.url || '#';
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

// Process markdown files recursively
function processContentDirectory(dir, basePath = '') {
    // First, read all markdown files to build the page hierarchy
    const pages = {};
    fs.readdirSync(dir).forEach(file => {
        if (path.extname(file) === '.md') {
            const fullPath = path.join(dir, file);
            const content = fs.readFileSync(fullPath, 'utf8');
            const metadataMatch = content.match(/^<!--\s*json\s*({[\s\S]*?})\s*-->/);
            const metadata = metadataMatch ? JSON.parse(metadataMatch[1]) : {};
            const filename = path.basename(file, '.md');
            
            console.log(`DEBUG: Processing file ${file}`);
            console.log(`DEBUG: Metadata match: ${metadataMatch ? metadataMatch[1] : 'none'}`);
            console.log(`DEBUG: Parsed metadata:`, metadata);
            
            // Extract links from content to determine parent-child relationships
            const links = content.match(/\[([^\]]+)\]\(([^)]+)\)/g) || [];
            const childPages = links.map(link => {
                const match = link.match(/\[([^\]]+)\]\(([^)]+)\)/);
                return match ? match[2].replace(/^\//, '') : null;
            }).filter(Boolean);
            
            pages[filename] = {
                metadata,
                childPages,
                content,
                fullPath
            };
        }
    });
    
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

        // Extract sidebar name from template
        const sidebarMatch = templateContent.match(/{{sidebar=([^}]+)}}/);
        const templateSidebar = sidebarMatch ? sidebarMatch[1] : null;
        // Use template sidebar if metadata explicitly requests one, or if template is designed for sidebars
        const sidebarName = metadata.sidebar || (template === 'sidebar-only' ? templateSidebar : null);

        // Replace custom syntax with Handlebars syntax
        templateContent = templateContent.replace(/{{sidebar=([^}]+)}}/g, '{{{sidebar}}}');
        templateContent = templateContent.replace(/{{menu=([^}]+)}}/g, '{{{mainMenu}}}');
        templateContent = templateContent.replace(/{{content}}/g, '{{{content}}}');

        // Compile template
        const compiledTemplate = handlebars.compile(templateContent);

        // Generate menu and sidebar
        const mainMenu = generateMenu(basePath ? `${basePath}/${filename}` : filename);
        const sidebarContent = sidebarName ? generateSidebar(sidebarName) : '';
        const hasSidebar = sidebarContent && sidebarContent.trim() !== '';

        // Convert markdown to HTML
        const markdownContent = content.replace(/^<!--\s*json\s*.*?-->\n/, '');
        const htmlContent = marked(markdownContent);

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
        
        // Render template
        let renderedHtml = compiledTemplate({
            title: pageTitle,
            siteName,
            siteDescription: siteConfig.site_description || '',
            theme,
            currentYear: new Date().getFullYear(),
            custom_css: customCss,
            custom_js: customJs,
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

// Process blog posts
const blogPostsFile = path.join(contentDir, 'blog_posts.json');
if (fs.existsSync(blogPostsFile)) {
    const posts = JSON.parse(fs.readFileSync(blogPostsFile, 'utf8'));
    const publishedPosts = posts.filter(post => post.status === 'published');
    
    // Sort posts by date (newest first)
    publishedPosts.sort((a, b) => new Date(b.date) - new Date(a.date));
    
    // Create blog index page
    const blogTemplatePath = path.join('themes', theme, 'templates', 'blog.html');
    if (fs.existsSync(blogTemplatePath)) {
        let templateContent = fs.readFileSync(blogTemplatePath, 'utf8');
        
        // Replace custom syntax with Handlebars syntax
        templateContent = templateContent.replace(/{{sidebar=([^}]+)}}/g, '{{{sidebar}}}');
        templateContent = templateContent.replace(/{{menu=([^}]+)}}/g, '{{{mainMenu}}}');
        templateContent = templateContent.replace(/{{content}}/g, '{{{content}}}');

        const compiledTemplate = handlebars.compile(templateContent);
        
        // Generate blog index content - let the theme template handle styling
        let blogIndexContent = '';
        publishedPosts.forEach(post => {
            blogIndexContent += `<article><h2><a href="/blog/${post.slug}/">${post.title}</a></h2>`;
            blogIndexContent += `<div>${post.date}</div>`;
            blogIndexContent += `<div>${marked(post.content.substring(0, 300) + '...')}</div>`;
            blogIndexContent += `<a href="/blog/${post.slug}/">Read more →</a></article>`;
        });
        
        // Generate menu and sidebar
        const mainMenu = generateMenu('blog');
        const sidebarContent = generateSidebar('blog') || '';
        
        // Get SEO settings
        const seoSettingsFile = path.join('config', 'seo_settings.json');
        let seoSettings = {};
        if (fs.existsSync(seoSettingsFile)) {
            seoSettings = JSON.parse(fs.readFileSync(seoSettingsFile, 'utf8'));
        }
        
        // Build meta tags for blog index
        const metaTags = buildMetaTags({
            title: 'Blog',
            description: seoSettings.site_description || 'Blog posts',
            socialImage: seoSettings.social_image || '',
            siteTitle: seoSettings.site_title || siteName,
            titleSeparator: seoSettings.title_separator || '-',
            appendSiteTitle: seoSettings.append_site_title !== false
        });
        
        // Render blog index template
        const renderedHtml = compiledTemplate({
            title: 'Blog',
            siteName,
            siteDescription: siteConfig.site_description || '',
            theme,
            currentYear: new Date().getFullYear(),
            custom_css: customCss,
            custom_js: customJs,
            mainMenu,
            sidebar: sidebarContent,
            content: blogIndexContent,
            heroBanner: themeOptions.herobanner || '',
            logo: themeOptions.logo || '',
            metaTags
        });

        // Convert absolute paths to relative paths for blog index
        const blogIndexDepth = 1; // blog/index.html is one level deep
        let processedBlogHtml = renderedHtml;
        processedBlogHtml = processedBlogHtml.replace(/href="\/themes\/([^"]+)"/g, `href="${makeRelativePath('/themes/$1', blogIndexDepth)}"`);
        processedBlogHtml = processedBlogHtml.replace(/src="\/themes\/([^"]+)"/g, `src="${makeRelativePath('/themes/$1', blogIndexDepth)}"`);
        processedBlogHtml = processedBlogHtml.replace(/href="\/uploads\/([^"]+)"/g, `href="${makeRelativePath('/uploads/$1', blogIndexDepth)}"`);
        processedBlogHtml = processedBlogHtml.replace(/src="\/uploads\/([^"]+)"/g, `src="${makeRelativePath('/uploads/$1', blogIndexDepth)}"`);
        processedBlogHtml = processedBlogHtml.replace(/href="\/assets\/([^"]+)"/g, `href="${makeRelativePath('/assets/$1', blogIndexDepth)}"`);
        processedBlogHtml = processedBlogHtml.replace(/src="\/assets\/([^"]+)"/g, `src="${makeRelativePath('/assets/$1', blogIndexDepth)}"`);
        
        // Write blog index
        const blogIndexPath = path.join(exportDir, 'blog', 'index.html');
        fs.mkdirSync(path.dirname(blogIndexPath), { recursive: true });
        fs.writeFileSync(blogIndexPath, processedBlogHtml);
        console.log('Created blog index page');
        
        // Create individual blog post pages
        publishedPosts.forEach(post => {
            const postContent = marked(post.content);
            
            // Build meta tags for blog post
            const postMetaTags = buildMetaTags({
                title: post.title,
                description: post.content.substring(0, 160).replace(/[^\w\s]/g, ''), // Clean description
                socialImage: seoSettings.social_image || '',
                siteTitle: seoSettings.site_title || siteName,
                titleSeparator: seoSettings.title_separator || '-',
                appendSiteTitle: seoSettings.append_site_title !== false
            });
            
            // Render blog post template
            const postHtml = compiledTemplate({
                title: post.title,
                siteName,
                siteDescription: siteConfig.site_description || '',
                theme,
                currentYear: new Date().getFullYear(),
                custom_css: customCss,
                custom_js: customJs,
                mainMenu,
                sidebar: sidebarContent,
                content: postContent,
                heroBanner: themeOptions.herobanner || '',
                logo: themeOptions.logo || '',
                metaTags: postMetaTags
            });
            
            // Convert absolute paths to relative paths for blog posts
            const blogPostDepth = 2; // blog/post-slug/index.html is two levels deep
            let processedPostHtml = postHtml;
            processedPostHtml = processedPostHtml.replace(/href="\/themes\/([^"]+)"/g, `href="${makeRelativePath('/themes/$1', blogPostDepth)}"`);
            processedPostHtml = processedPostHtml.replace(/src="\/themes\/([^"]+)"/g, `src="${makeRelativePath('/themes/$1', blogPostDepth)}"`);
            processedPostHtml = processedPostHtml.replace(/href="\/uploads\/([^"]+)"/g, `href="${makeRelativePath('/uploads/$1', blogPostDepth)}"`);
            processedPostHtml = processedPostHtml.replace(/src="\/uploads\/([^"]+)"/g, `src="${makeRelativePath('/uploads/$1', blogPostDepth)}"`);
            processedPostHtml = processedPostHtml.replace(/href="\/assets\/([^"]+)"/g, `href="${makeRelativePath('/assets/$1', blogPostDepth)}"`);
            processedPostHtml = processedPostHtml.replace(/src="\/assets\/([^"]+)"/g, `src="${makeRelativePath('/assets/$1', blogPostDepth)}"`);
            
            // Write blog post
            const postPath = path.join(exportDir, 'blog', post.slug, 'index.html');
            fs.mkdirSync(path.dirname(postPath), { recursive: true });
            fs.writeFileSync(postPath, processedPostHtml);
            console.log(`Created blog post: ${post.slug}`);
        });
    } else {
        console.warn('Warning: blog.html template not found, skipping blog export');
    }
}

console.log('Export completed successfully!');
console.log(`Static site is available in the '${exportDir}' directory`);

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