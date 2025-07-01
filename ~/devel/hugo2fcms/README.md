# Hugo to FearlessCMS Theme Converter

A Python tool that automatically converts Hugo themes to FearlessCMS themes.

## 🚀 Quick Start

```bash
# Clone or download this repository
cd ~/devel/hugo2fcms

# Convert a Hugo theme
python hugo-to-fearlesscms-converter.py /path/to/hugo/theme /path/to/output
```

## ✨ Features

- **Template Conversion**: Converts Hugo Go templates to FearlessCMS template syntax
- **Variable Mapping**: Maps Hugo variables to FearlessCMS equivalents
- **Asset Conversion**: Converts SASS/SCSS to CSS and copies assets
- **Configuration**: Generates FearlessCMS theme.json from Hugo config
- **Partial Support**: Converts Hugo partials to FearlessCMS includes
- **Error Reporting**: Provides detailed conversion logs and error reporting

## 📋 Prerequisites

- Python 3.6+
- SASS compiler (optional, for SASS/SCSS compilation)

### Installing SASS

```bash
# Using npm (recommended)
npm install -g sass

# Using Ruby
gem install sass

# Using Homebrew (macOS)
brew install sass/sass/sass
```

## 🛠️ Usage

### Basic Usage

```bash
python hugo-to-fearlesscms-converter.py <hugo-theme-path> <output-path>
```

### Examples

```bash
# Convert a Hugo theme to FearlessCMS
python hugo-to-fearlesscms-converter.py ~/hugo-themes/my-theme themes/my-converted-theme

# Convert to a specific directory
python hugo-to-fearlesscms-converter.py ~/hugo-themes/nightfall ~/fearlesscms/themes/nightfall
```

## 📁 What Gets Converted

### Templates
| Hugo Template | FearlessCMS Template |
|---------------|---------------------|
| `layouts/index.html` | `templates/home.html` |
| `layouts/single.html` | `templates/page.html` |
| `layouts/list.html` | `templates/blog.html` |
| `layouts/404.html` | `templates/404.html` |
| `layouts/_default/partials/` | `partials/` |

### Variables
| Hugo Variable | FearlessCMS Variable |
|---------------|---------------------|
| `{{ .Title }}` | `{{title}}` |
| `{{ .Content }}` | `{{content}}` |
| `{{ .Site.Title }}` | `{{siteName}}` |
| `{{ .Site.Menus.main }}` | `{{menu.main}}` |
| `{{ range .Pages }}` | `{{foreach children}}` |
| `{{ if .Title }}` | `{{if title}}` |
| `{{ partial "header.html" . }}` | `{{include "partials/header.html"}}` |

### Assets
- `assets/` → `assets/`
- `static/` → `assets/`
- SASS/SCSS files are compiled to CSS
- Images, JS, and other assets are copied

## 🔧 Conversion Process

The converter performs the following steps:

1. **Create Directory Structure**: Sets up FearlessCMS theme directories
2. **Convert Configuration**: Generates `theme.json` from Hugo config
3. **Convert Templates**: Transforms Hugo templates to FearlessCMS syntax
4. **Convert Assets**: Compiles SASS and copies assets
5. **Generate Documentation**: Creates README and conversion notes

## ⚠️ Limitations

### Automatic Conversion ✅
- Basic template syntax
- Common Hugo variables
- Range loops and conditionals
- Partial includes
- Asset compilation
- Theme configuration

### Manual Adjustment Required ❌
- Complex Hugo functions
- Custom Hugo shortcodes
- Hugo-specific taxonomies
- Advanced Hugo features
- Custom Hugo functions

## 📝 Post-Conversion Steps

After running the converter:

1. **Review Templates**: Check for any unconverted Hugo syntax
2. **Test Functionality**: Ensure all features work as expected
3. **Add Theme Options**: Consider adding configurable options
4. **Customize Styling**: Adjust CSS to match your needs
5. **Test Responsiveness**: Verify mobile compatibility

## 🎯 Example Conversion

```bash
# Convert a Hugo theme
python hugo-to-fearlesscms-converter.py ~/hugo-themes/my-blog themes/my-blog-converted

# Output:
# Converting Hugo theme: my-blog
# Output directory: themes/my-blog-converted
# ✓ Created directory structure
# ✓ Converted theme configuration
# ✓ Converted template: index.html → home.html
# ✓ Converted template: single.html → page.html
# ✓ Converted template: list.html → blog.html
# ✓ Converted template: 404.html → 404.html
# ✓ Compiled SASS: main.scss → main.css
# ✓ Copied asset: theme.js → assets/js/
# ✓ Generated README.md
# 
# CONVERSION SUMMARY
# ==================================================
# Theme: my-blog
# Output: themes/my-blog-converted
# Templates converted: 4
# Assets converted: 8
# ✓ No errors encountered
# 
# Next steps:
# 1. Review the converted theme
# 2. Test with your FearlessCMS installation
# 3. Customize as needed
# 4. Add theme options if desired
# 
# 🎉 Conversion completed successfully!
```

## 🐛 Troubleshooting

### Common Issues

#### SASS Compilation Fails
```bash
# Install SASS compiler
npm install -g sass

# Check SASS syntax in source files
sass --check assets/main.scss
```

#### Template Conversion Errors
- Check for complex Hugo functions
- Review unconverted syntax in output templates
- Manually adjust as needed

#### Missing Assets
- Verify source theme structure
- Check file permissions
- Review conversion log for errors

### Getting Help

If you encounter issues:

1. Check the conversion log for specific errors
2. Review the FearlessCMS theme documentation
3. Compare with working themes
4. Test with simpler Hugo themes first

## 🤝 Contributing

To improve the converter:

1. **Add Variable Mappings**: Extend the variable mappings dictionary
2. **Support More Functions**: Add conversion for additional Hugo functions
3. **Improve Error Handling**: Better error messages and recovery
4. **Add Tests**: Create test cases for different theme types

### Development Setup

```bash
# Clone the repository
git clone <repository-url>
cd hugo2fcms

# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies (if any)
pip install -r requirements.txt
```

## 🔮 Future Enhancements

Potential improvements:

- **GUI Interface**: Web-based conversion tool
- **Batch Processing**: Convert multiple themes at once
- **Preview Mode**: Show conversion results before applying
- **Custom Mappings**: User-defined variable mappings
- **Theme Validation**: Validate converted themes
- **Reverse Conversion**: FearlessCMS to Hugo (for comparison)

## 📚 Related Resources

- [FearlessCMS Theme Documentation](https://github.com/fearlessgeek/fearlesscms)
- [Hugo Documentation](https://gohugo.io/documentation/)
- [FearlessCMS Theme Development Guide](~/fcmsdocs/creating-themes.md)

## 📄 License

This tool is open source and available under the MIT License.

---

*This tool is designed to accelerate theme development and make FearlessCMS more accessible to developers familiar with Hugo.* 