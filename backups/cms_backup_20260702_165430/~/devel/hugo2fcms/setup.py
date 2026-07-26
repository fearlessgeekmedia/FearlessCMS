#!/usr/bin/env python3
"""
Setup script for Hugo to FearlessCMS Theme Converter
"""

from setuptools import setup, find_packages

with open("README.md", "r", encoding="utf-8") as fh:
    long_description = fh.read()

setup(
    name="hugo2fcms",
    version="1.0.0",
    author="Fearless Geek Media",
    author_email="info@fearlessgeek.com",
    description="Convert Hugo themes to FearlessCMS themes",
    long_description=long_description,
    long_description_content_type="text/markdown",
    url="https://github.com/fearlessgeek/hugo2fcms",
    py_modules=["hugo-to-fearlesscms-converter"],
    classifiers=[
        "Development Status :: 4 - Beta",
        "Intended Audience :: Developers",
        "License :: OSI Approved :: MIT License",
        "Operating System :: OS Independent",
        "Programming Language :: Python :: 3",
        "Programming Language :: Python :: 3.6",
        "Programming Language :: Python :: 3.7",
        "Programming Language :: Python :: 3.8",
        "Programming Language :: Python :: 3.9",
        "Programming Language :: Python :: 3.10",
        "Topic :: Software Development :: Libraries :: Python Modules",
        "Topic :: Internet :: WWW/HTTP :: Site Management",
        "Topic :: Text Processing :: Markup :: HTML",
    ],
    python_requires=">=3.6",
    install_requires=[
        # No external dependencies required
    ],
    entry_points={
        "console_scripts": [
            "hugo2fcms=hugo-to-fearlesscms-converter:main",
        ],
    },
    keywords="hugo, cms, theme, conversion, fearlesscms",
    project_urls={
        "Bug Reports": "https://github.com/fearlessgeek/hugo2fcms/issues",
        "Source": "https://github.com/fearlessgeek/hugo2fcms",
        "Documentation": "https://github.com/fearlessgeek/hugo2fcms#readme",
    },
) 