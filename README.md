<p style="width: 100%; text-align:center">
<img src="https://3ofrpz7mhw.ufs.sh/f/9h8vN5CCYibJCfOnkrzL5RTenxN0PakwUA41YgmtJo8ZrK7C" alt="FearlessCMS Logo" style="width:200px"></img>
</p>

# Note: Funding Required for FearlessCMS Development to Continue 
More at <a href="https://ko-fi.com/post/Funding-Required-For-FearelessCMS-Development-To-C-D1D31IVNKP" target="_blank">Ko-Fi</a>.

Welcome to FearlessCMS, a new content management system centered around simplicity and respect for the open source community. The project is currently in beta testing stages, so while it is becoming more robust, please report any issues you find.

## ✍️ HTML Editing with Markdown Support

FearlessCMS features **HTML content creation** with **Markdown content compatibility** to accommodate different user preferences:

- **HTML Editor (Default)** - Rich WYSIWYG editor with code view toggle for complex layouts
- **Markdown Support** - Can read and display Markdown files created in external editors
- **Backwards Compatibility** - All existing Markdown content continues to work perfectly
- **Flexible Workflow** - Create HTML in CMS or upload Markdown from external editors

This approach gives you the best of both worlds: the power and precision of HTML editing when you need it, and the flexibility to use external Markdown editors when you prefer it.

View documentation at <a href="https://fearlesscms.online/documentation/" target="_blank">https://fearlesscms.online/documentation/</a> to get started.

# FearlessCMS Manifesto

For years, the World Wide Web has been dominated by content management systems that have become bloated and clunky over time. **FearlessCMS** strives to be different.

Open-source projects must respect both the users of the software and the developers who contribute to it. FearlessCMS will adhere to these principles by fostering an inclusive, transparent, and collaborative environment for all contributors.

## Fearless Geek Media declares the following in the making of FearlessCMS:

1. **FearlessCMS will respect the culture and spirit of open-source software.**
   - Contributors are encouraged to improve, distribute, and use the code for commercial or non-commercial purposes, as long as proper attribution is given to developers and contributors.
   - All trademarks associated with the original project can be used freely by others, without restriction.
   - Input from designers, developers, and users is welcome and integral to the platform's success. This is an open-source project for the community and by the community.

2. **Templates should be templates. Plugins should be plugins.**
   - In many modern platforms, themes and templates have taken on functionality that should be provided by plugins, introducing unnecessary complexity.
   - In FearlessCMS, themes and templates are not to include plugin functionality, and plugins should be clearly declared dependencies, well-documented, and disclosed upfront.
   - Themes and templates will consist only of **HTML, CSS, and JavaScript** (or languages that compile to JavaScript like Coffeescript or Hyperscript), with minimal JavaScript to limit potential attack vectors.

3. **FearlessCMS will evolve with technology.**
   - Technology is constantly advancing. FearlessCMS commits to embracing new, secure, faster, and more efficient technologies to improve the platform.
   - Every two years after the initial launch, the codebase will be reviewed for efficiency and overhauled if necessary to ensure continued evolution and relevance.

4. **Security and privacy are non-negotiable.**
   - FearlessCMS will prioritize the protection of user data and safeguard the platform from security vulnerabilities.
   - Regular security audits and prompt patching of vulnerabilities are essential parts of the development cycle.

5. **The platform will remain lightweight and performance-driven.**
   - Bloat is the enemy. The CMS will be designed to be as lightweight as possible without compromising functionality. This ensures a fast, responsive experience for both developers and end-users.

6. **Accessibility and inclusivity are core values.**
   - FearlessCMS will follow web standards to ensure the platform is accessible to users with disabilities, including adherence to **WCAG** guidelines.
   - An inclusive community where everyone, regardless of skill level or background, is encouraged to participate, share knowledge, and grow together is central to the project's ethos.

7. **Ethical development and use.**
   - Developers contributing to FearlessCMS will uphold ethical programming standards, prioritizing user safety, privacy, and the open web.
   - FearlessCMS will not tolerate contributions that introduce unethical practices, such as data mining, surveillance, or exploitation of vulnerabilities.

8. **Sustainability in software development.**
   - The project recognizes the environmental impact of large-scale web services. FearlessCMS will strive to use efficient code and consider its carbon footprint, aiming to reduce unnecessary resource usage.

---

*The ideas in section 2 were contributed by James Potts.*

---

### Conclusion:

With these policies in place, FearlessCMS seeks to create a platform that is lightweight, secure, and accessible, but most importantly, it is a CMS **for the people**. Whether you are a developer, a designer, or a casual user, FearlessCMS will respect your needs and provide a toolset for creating websites that empower the open web and its diverse community.

## Quick Start

* Note: At this time, this is only being tested on Linux, Unix-like systems, and MacOS. 
Windows is not supported, but may be in the future. But this will likely work with Windows Subsystem for Linux. *

### Installation

- Web installer: visit `install.php` in your browser and follow the prompts to verify directories, initialize defaults, and optionally install export tool dependencies.
- CLI installer:

```bash
php install.php --check               # show environment and directory status
php install.php --create-dirs         # create required directories and default configs
php install.php --install-export-deps # install Node deps for export.js (fs-extra, handlebars, marked)
php install.php --create-admin=<username> --password=<pwd>
# or use a file for the password:
php install.php --create-admin=<username> --password-file=/path/to/secret
```

### Static Site Export

FearlessCMS includes powerful export functionality that converts your dynamic PHP site to static HTML for deployment on any static hosting service.

#### Export via Dashboard (Recommended)

The easiest way to export your site is directly through the Mission Control dashboard:
1. Log in to your admin panel.
2. On the **Dashboard**, look for the blue **Export Site** button next to the page title.
3. Click the button to generate a full static export of your site in the `export/` directory.

**Features:**
- ✅ **One-click export** - no command line required
- ✅ **Zero maintenance** - automatically works with all plugins and themes
- ✅ **Full compatibility** - internal rendering ensures what you see is what you get
- ✅ **Deploy anywhere** - Netlify, Vercel, GitHub Pages, AWS S3, etc.
- ✅ **Complete assets** - CSS, JS, images, and all dependencies included

For advanced users, legacy shell scripts (`export-robust.sh`) are still available for automated CI/CD workflows.

For complete export documentation, visit: [Export to Static HTML Guide](https://fearlesscms.online/documentation/export-sites-to-static-html/)
