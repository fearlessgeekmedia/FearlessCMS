#!/usr/bin/env python3
import os
import re

def fix_over_braces(filepath):
    with open(filepath, 'r') as f:
        content = f.read()
    
    original = content
    
    # Fix specific patterns with over-braces
    # Fix {{{{{#if ...}}}} to {{#if ...}}
    content = re.sub(r'\{5}#if\s+([^}]+)\}{5}', r'{{#if \1}}', content)
    content = re.sub(r'\{5}/if\}{5}', r'{{/if}}', content)
    content = re.sub(r'\{5}#each\s+([^}]+)\}{5}', r'{{#each \1}}', content)
    content = re.sub(r'\{5}/each\}{5}', r'{{/each}}', content)
    content = re.sub(r'\{5}else\}{5}', r'{{else}}', content)
    
    # Fix {{{{{themeOptions...}}}} to {{themeOptions...}}
    content = re.sub(r'\{5}(themeOptions\.[^}]+)\}{5}', r'{{\1}}', content)
    
    # Fix {{{{{#if ...}}}} to {{#if ...}} (remaining cases)
    content = re.sub(r'\{5}#if\s+([^}]+)\}{5}', r'{{#if \1}}', content)
    
    # Fix {{{title}}} to {{title}}
    content = re.sub(r'\{3}([^}]+)\}{3}', r'{{\1}}', content)
    
    # Fix any remaining quadruple braces
    content = re.sub(r'\{4}([^}]+)\}{4}', r'{{\1}}', content)
    
    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        return True
    return False

# Process all new themes
themes = [d for d in os.listdir('themes') if os.path.isdir(f'themes/{d}') and d not in ['starterscores', 'salt-lake', 'punk_rock']]

fixed_count = 0
for theme in themes:
    templates_dir = f'themes/{theme}/templates'
    if os.path.exists(templates_dir):
        for file in os.listdir(templates_dir):
            if file.endswith('.html'):
                filepath = f'{templates_dir}/{file}'
                if fix_over_braces(filepath):
                    print(f'Fixed: {filepath}')
                    fixed_count += 1

print(f'\nTotal files fixed: {fixed_count}') 