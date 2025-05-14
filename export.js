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

// Ensure export directory exists and is clean
if (fs.existsSync(exportDir)) {
    fs.removeSync(exportDir);
}
fs.mkdirSync(exportDir, { recursive: true });

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
            items: [
                { label: 'Home', url: 'home', class: 'nav-link' },
                { label: 'About', url: 'about', class: 'nav-link' },
                { label: 'Contact', url: 'contact', class: 'nav-link' }
            ]
        };
    }

    // Ensure menuJson.items exists
    if (!menuJson.items || !Array.isArray(menuJson.items)) {
        console.warn('Warning: Invalid menu structure, using default menu');
        menuJson.items = [
            { label: 'Home', url: 'home', class: 'nav-link' },
            { label: 'About', url: 'about', class: 'nav-link' },
            { label: 'Contact', url: 'contact', class: 'nav-link' }
        ];
    }

    let menuHtml = '';
    menuJson.items.forEach(item => {
        // Convert absolute URLs to relative URLs
        let url = item.url;
        if (url.startsWith('/')) {
            url = url.substring(1); // Remove leading slash
        }
        
        // If we're in a subdirectory (like /home/), we need to go up one level
        if (currentPage === 'home') {
            url = url === 'home' ? './' : '../' + url;
        } else {
            url = url === 'home' ? '../home/' : '../' + url + '/';
        }
        
        menuHtml += `<a href="${url}" class="${item.class}">${item.label}</a>`;
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

// Process markdown files
const contentDir = 'content';
fs.readdirSync(contentDir).forEach(file => {
    if (path.extname(file) === '.md') {
        const filename = path.basename(file, '.md');
        const filePath = path.join(contentDir, file);
        const content = fs.readFileSync(filePath, 'utf8');

        // Extract metadata
        const metadataMatch = content.match(/^<!--\s*json\s*({.*?})\s*-->/);
        const metadata = metadataMatch ? JSON.parse(metadataMatch[1]) : {};
        const title = metadata.title || filename;
        const template = metadata.template || 'page';
        const heroBanner = metadata.heroBanner || metadata.hero_banner || '';
        const logo = metadata.logo || '';
        const sidebar = metadata.sidebar || '';

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
        const mainMenu = generateMenu(filename);
        const sidebarContent = sidebarName ? generateSidebar(sidebarName) : '';

        // Convert markdown to HTML
        const markdownContent = content.replace(/^<!--\s*json\s*.*?-->\n/, '');
        const htmlContent = marked(markdownContent);

        // Use default logo and heroBanner if not set in metadata
        const logoValue = logo ? `uploads/theme/${logo}` : 'uploads/theme/logo.png';
        const heroBannerValue = heroBanner ? `uploads/theme/${heroBanner}` : 'uploads/theme/herobanner.jpg';

        // Debug output for logo and heroBanner
        console.log(`DEBUG: For page '${filename}': logo='${logoValue}', heroBanner='${heroBannerValue}'`);

        // Render template
        let renderedHtml = compiledTemplate({
            title,
            siteName,
            theme,
            currentYear: new Date().getFullYear(),
            customCss,
            customJs,
            mainMenu,
            sidebar: sidebarContent,
            content: htmlContent,
            heroBanner: heroBannerValue,
            logo: logoValue
        });

        // Write to export directory
        const exportFilePath = path.join(exportDir, filename, 'index.html');
        fs.mkdirSync(path.dirname(exportFilePath), { recursive: true });
        fs.writeFileSync(exportFilePath, renderedHtml);
        console.log(`Created page: ${filename}`);
    }
});

// Create index.html in root
if (fs.existsSync(path.join(exportDir, 'index', 'index.html'))) {
    fs.copyFileSync(path.join(exportDir, 'index', 'index.html'), path.join(exportDir, 'index.html'));
    console.log('Created root index.html');
} else {
    const redirectHtml = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=home/index.html">
    <title>Redirecting...</title>
</head>
<body>
    <p>If you are not redirected automatically, <a href="home/index.html">click here</a>.</p>
</body>
</html>`;
    fs.writeFileSync(path.join(exportDir, 'index.html'), redirectHtml);
    console.log('Created redirect index.html');
}

console.log('Export completed successfully!');
console.log(`Static site is available in the '${exportDir}' directory`);