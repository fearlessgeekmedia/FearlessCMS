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

// Copy theme assets
const themeAssetsDir = path.join('themes', theme, 'assets');
const exportThemeAssetsDir = path.join(exportDir, 'themes', theme, 'assets');
if (fs.existsSync(themeAssetsDir)) {
    fs.copySync(themeAssetsDir, exportThemeAssetsDir, { recursive: true });
}

// Copy theme CSS
const themeCssFile = path.join('themes', theme, 'style.css');
const exportThemeCssFile = path.join(exportDir, 'themes', theme, 'style.css');
if (fs.existsSync(themeCssFile)) {
    fs.copySync(themeCssFile, exportThemeCssFile);
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

// Generate main menu
function generateMenu(currentPage = '') {
    let menuJson;
    try {
        menuJson = JSON.parse(fs.readFileSync('config/menus.json', 'utf8'));
    } catch (error) {
        console.warn('Warning: Could not read menus.json, using default menu');
        menuJson = {
            main: {
                items: [
                    { label: 'Home', url: 'home', class: 'nav-link' },
                    { label: 'About', url: 'about', class: 'nav-link' },
                    { label: 'Contact', url: 'contact', class: 'nav-link' }
                ]
            }
        };
    }

    // Get the main menu items
    const menuItems = menuJson.main?.items || menuJson.items || [];
    
    // Ensure menuItems is an array
    if (!Array.isArray(menuItems)) {
        console.warn('Warning: Invalid menu structure, using default menu');
        menuItems = [
            { label: 'Home', url: 'home', class: 'nav-link' },
            { label: 'About', url: 'about', class: 'nav-link' },
            { label: 'Contact', url: 'contact', class: 'nav-link' }
        ];
    }

    let menuHtml = '';
    menuItems.forEach(item => {
        // Handle external URLs
        if (item.url.startsWith('http')) {
            menuHtml += `<a href="${item.url}" class="${item.class}" ${item.target ? `target="${item.target}"` : ''}>${item.label}</a>`;
            return;
        }
        
        // For internal URLs, use absolute paths from root
        let url = item.url;
        if (url.startsWith('/')) {
            url = url.substring(1); // Remove leading slash
        }
        
        // Special case for home page
        if (url === 'home' || url === '') {
            url = '/';
        } else {
            url = '/' + url + '/';
        }
        
        menuHtml += `<a href="${url}" class="${item.class}" ${item.target ? `target="${item.target}"` : ''}>${item.label}</a>`;
    });
    return menuHtml;
}

// Generate sidebar
function generateSidebar(sidebarName) {
    let sidebarJson;
    try {
        sidebarJson = JSON.parse(fs.readFileSync('config/sidebars.json', 'utf8'));
    } catch (error) {
        console.warn('Warning: Could not read sidebars.json, using empty sidebar');
        return '';
    }

    const sidebar = sidebarJson[sidebarName];
    if (!sidebar || !sidebar.widgets || !Array.isArray(sidebar.widgets)) {
        console.warn(`Warning: Invalid sidebar structure for ${sidebarName}, using empty sidebar`);
        return '';
    }

    let sidebarHtml = '';
    sidebar.widgets.forEach(widget => {
        sidebarHtml += `<div class="widget widget-${widget.type}">`;
        sidebarHtml += `<h3 class="widget-title">${widget.title}</h3>`;
        sidebarHtml += `<div class="widget-content">${widget.content}</div>`;
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
        const sidebarName = metadata.sidebar || templateSidebar;

        // Replace custom syntax with Handlebars syntax
        templateContent = templateContent.replace(/{{sidebar=([^}]+)}}/g, '{{{sidebar}}}');
        templateContent = templateContent.replace(/{{content}}/g, '{{{content}}}');
        templateContent = templateContent.replace(/{{mainMenu}}/g, '{{{mainMenu}}}');

        // Compile template
        const compiledTemplate = handlebars.compile(templateContent);

        // Generate menu and sidebar
        const mainMenu = generateMenu(basePath ? `${basePath}/${filename}` : filename);
        const sidebarContent = sidebarName ? generateSidebar(sidebarName) : '';

        // Convert markdown to HTML
        const markdownContent = content.replace(/^<!--\s*json\s*.*?-->\n/, '');
        const htmlContent = marked(markdownContent);

        // Use default logo and heroBanner if not set in metadata
        const logoValue = logo ? `uploads/theme/${logo}` : 'uploads/theme/logo.png';
        const heroBannerValue = heroBanner ? `uploads/theme/${heroBanner}` : 'uploads/theme/herobanner.jpg';

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

        // Render template
        let renderedHtml = compiledTemplate({
            title: pageTitle,
            siteName,
            theme,
            currentYear: new Date().getFullYear(),
            custom_css: customCss,
            custom_js: customJs,
            mainMenu,
            sidebar: sidebarContent,
            content: htmlContent,
            heroBanner: heroBannerValue,
            logo: logoValue,
            metaTags
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
        templateContent = templateContent.replace(/{{content}}/g, '{{{content}}}');
        templateContent = templateContent.replace(/{{mainMenu}}/g, '{{{mainMenu}}}');
        
        const compiledTemplate = handlebars.compile(templateContent);
        
        // Generate blog index content
        let blogIndexContent = '<div class="max-w-4xl mx-auto px-4 py-8">';
        blogIndexContent += '<h1 class="text-3xl font-bold mb-8">Blog Posts</h1>';
        blogIndexContent += '<div class="space-y-8">';
        
        publishedPosts.forEach(post => {
            blogIndexContent += '<article class="border-b pb-8">';
            blogIndexContent += `<h2 class="text-2xl font-bold mb-2"><a href="/blog/${post.slug}/" class="text-blue-600 hover:underline">${post.title}</a></h2>`;
            blogIndexContent += `<div class="text-gray-600 mb-4">${post.date}</div>`;
            blogIndexContent += `<div class="prose">${marked(post.content.substring(0, 300) + '...')}</div>`;
            blogIndexContent += `<a href="/blog/${post.slug}/" class="text-blue-600 hover:underline mt-4 inline-block">Read more →</a>`;
            blogIndexContent += '</article>';
        });
        
        blogIndexContent += '</div></div>';
        
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
            theme,
            currentYear: new Date().getFullYear(),
            custom_css: customCss,
            custom_js: customJs,
            mainMenu,
            sidebar: sidebarContent,
            content: blogIndexContent,
            heroBanner: 'uploads/theme/herobanner.jpg',
            logo: 'uploads/theme/logo.png',
            metaTags
        });
        
        // Write blog index
        const blogIndexPath = path.join(exportDir, 'blog', 'index.html');
        fs.mkdirSync(path.dirname(blogIndexPath), { recursive: true });
        fs.writeFileSync(blogIndexPath, renderedHtml);
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
                theme,
                currentYear: new Date().getFullYear(),
                custom_css: customCss,
                custom_js: customJs,
                mainMenu,
                sidebar: sidebarContent,
                content: `<article class="max-w-4xl mx-auto px-4 py-8">
                    <h1 class="text-3xl font-bold mb-4">${post.title}</h1>
                    <div class="text-gray-600 mb-8">${post.date}</div>
                    <div class="prose">${postContent}</div>
                </article>`,
                heroBanner: 'uploads/theme/herobanner.jpg',
                logo: 'uploads/theme/logo.png',
                metaTags: postMetaTags
            });
            
            // Write blog post
            const postPath = path.join(exportDir, 'blog', post.slug, 'index.html');
            fs.mkdirSync(path.dirname(postPath), { recursive: true });
            fs.writeFileSync(postPath, postHtml);
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