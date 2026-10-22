#!/usr/bin/env python3
"""
Test script for Hugo to FearlessCMS Theme Converter
"""

import os
import tempfile
import shutil
from pathlib import Path
from hugo_to_fearlesscms_converter import HugoToFearlessCMSConverter

def create_test_hugo_theme():
    """Create a minimal test Hugo theme for testing"""
    with tempfile.TemporaryDirectory() as temp_dir:
        hugo_theme = Path(temp_dir) / "test-hugo-theme"
        hugo_theme.mkdir()
        
        # Create Hugo theme structure
        (hugo_theme / "layouts").mkdir()
        (hugo_theme / "layouts" / "_default").mkdir()
        (hugo_theme / "layouts" / "_default" / "partials").mkdir()
        (hugo_theme / "assets").mkdir()
        (hugo_theme / "static").mkdir()
        
        # Create theme.toml
        theme_toml = """name = "Test Hugo Theme"
description = "A test theme for conversion"
version = "1.0.0"
author = "Test Author"
license = "MIT"
"""
        with open(hugo_theme / "theme.toml", "w") as f:
            f.write(theme_toml)
        
        # Create index.html template
        index_html = """<!DOCTYPE html>
<html>
<head>
    <title>{{ .Site.Title }}</title>
</head>
<body>
    <header>
        <h1>{{ .Site.Title }}</h1>
        <nav>
            {{ range .Site.Menus.main }}
                <a href="{{ .URL }}">{{ .Name }}</a>
            {{ end }}
        </nav>
    </header>
    
    <main>
        {{ .Content }}
    </main>
    
    <footer>
        &copy; {{ .Site.Title }}
    </footer>
</body>
</html>
"""
        with open(hugo_theme / "layouts" / "index.html", "w") as f:
            f.write(index_html)
        
        # Create single.html template
        single_html = """<!DOCTYPE html>
<html>
<head>
    <title>{{ .Title }} - {{ .Site.Title }}</title>
</head>
<body>
    <article>
        <h1>{{ .Title }}</h1>
        {{ .Content }}
    </article>
</body>
</html>
"""
        with open(hugo_theme / "layouts" / "single.html", "w") as f:
            f.write(single_html)
        
        # Create a partial
        header_html = """<header>
    <h1>{{ .Site.Title }}</h1>
    <p>{{ .Site.Description }}</p>
</header>
"""
        with open(hugo_theme / "layouts" / "_default" / "partials" / "header.html", "w") as f:
            f.write(header_html)
        
        # Create some assets
        style_css = """body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 20px;
}

header {
    background: #f0f0f0;
    padding: 20px;
}
"""
        with open(hugo_theme / "assets" / "style.css", "w") as f:
            f.write(style_css)
        
        # Create a SASS file
        main_scss = """$primary-color: #007bff;
$secondary-color: #6c757d;

body {
    font-family: Arial, sans-serif;
    color: $primary-color;
}

.button {
    background-color: $primary-color;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
}
"""
        with open(hugo_theme / "assets" / "main.scss", "w") as f:
            f.write(main_scss)
        
        return str(hugo_theme)

def test_conversion():
    """Test the conversion process"""
    print("🧪 Testing Hugo to FearlessCMS Converter")
    print("=" * 50)
    
    # Create test Hugo theme
    print("Creating test Hugo theme...")
    hugo_theme_path = create_test_hugo_theme()
    
    # Create output directory
    with tempfile.TemporaryDirectory() as temp_dir:
        output_path = Path(temp_dir) / "converted-theme"
        
        print(f"Hugo theme: {hugo_theme_path}")
        print(f"Output: {output_path}")
        print()
        
        # Run conversion
        try:
            converter = HugoToFearlessCMSConverter(hugo_theme_path, str(output_path))
            success = converter.convert()
            
            if success:
                print("\n✅ Conversion test PASSED!")
                
                # Check output files
                expected_files = [
                    "theme.json",
                    "templates/home.html",
                    "templates/page.html",
                    "assets/css/style.css",
                    "README.md"
                ]
                
                print("\nChecking output files:")
                for file_path in expected_files:
                    full_path = output_path / file_path
                    if full_path.exists():
                        print(f"  ✅ {file_path}")
                    else:
                        print(f"  ❌ {file_path} (missing)")
                
                # Check conversion stats
                print(f"\nConversion stats:")
                print(f"  Templates converted: {converter.conversion_stats['templates_converted']}")
                print(f"  Assets converted: {converter.conversion_stats['assets_converted']}")
                print(f"  Errors: {len(converter.conversion_stats['errors'])}")
                
                return True
            else:
                print("\n❌ Conversion test FAILED!")
                return False
                
        except Exception as e:
            print(f"\n❌ Conversion test FAILED with exception: {e}")
            return False

def test_variable_mapping():
    """Test variable mapping functionality"""
    print("\n🔧 Testing variable mapping...")
    
    converter = HugoToFearlessCMSConverter("dummy", "dummy")
    
    test_cases = [
        ("{{ .Title }}", "{{title}}"),
        ("{{ .Site.Title }}", "{{siteName}}"),
        ("{{ .Content }}", "{{content}}"),
        ("{{ range .Pages }}", "{{foreach children}}"),
    ]
    
    for hugo_var, expected_fearless_var in test_cases:
        result = converter.convert_template_syntax(hugo_var)
        if result == expected_fearless_var:
            print(f"  ✅ {hugo_var} → {result}")
        else:
            print(f"  ❌ {hugo_var} → {result} (expected: {expected_fearless_var})")

if __name__ == "__main__":
    print("🚀 Running Hugo to FearlessCMS Converter Tests")
    print("=" * 60)
    
    # Test variable mapping
    test_variable_mapping()
    
    # Test full conversion
    success = test_conversion()
    
    print("\n" + "=" * 60)
    if success:
        print("🎉 All tests PASSED!")
        print("The converter is working correctly.")
    else:
        print("❌ Some tests FAILED!")
        print("Please check the output above for issues.")
    
    print("\nTo use the converter:")
    print("python hugo-to-fearlesscms-converter.py <hugo-theme> <output-path>") 