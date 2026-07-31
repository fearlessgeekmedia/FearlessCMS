# Home Page Configuration

FearlessCMS allows you to designate any content page as your site's home page directly from the admin content listing.

## Overview

By default, the site serves `content/home.md` (or `content/home.html`) as the home page when visitors access the root URL. With home page configuration, you can select any existing page to serve as the home page instead.

## Setting a Home Page

1. Navigate to **Mission Control** → **Content Management**
2. Find the page you want to set as the home page in the content list
3. Click the **Set as Home** button in the **Home Page** column
4. The page will be marked with a yellow **Home Page** badge

The setting is stored in `config/config.json` under the `home_page` key as the page's URL path (slug).

## Unsetting a Home Page

To revert to the default behavior:

1. Navigate to **Mission Control** → **Content Management**
2. Find the page currently marked as the home page (it will have a yellow **Home Page** badge)
3. Click the **Unset** button next to the badge

This removes the `home_page` setting from `config/config.json`, and the site will fall back to serving `content/home.md` or `content/home.html` as the default home page.

## Automatic Cleanup

- If you **delete** a page that is currently set as the home page, the `home_page` setting is automatically cleared.
- If you **bulk delete** pages and one of them is the home page, the setting is also automatically cleared.

## Configuration Reference

The home page setting is stored in `config/config.json`:

```json
{
    "site_name": "My Site",
    "home_page": "welcome"
}
```

In this example, `content/welcome.md` (or `content/welcome.html`) would be served as the home page.

To remove the setting entirely, delete the `home_page` key from the JSON file.

## Routing Behavior

- When a visitor accesses the root URL (`/`), FearlessCMS loads the page specified by `home_page`.
- If `home_page` is not set, it falls back to `home`.
- The configured page still uses its own template and content; only the URL mapping changes.

## Template Selection

The configured home page still uses its own `template` metadata value. The router only changes which content file is loaded for the root URL. The default template selection logic remains the same: if the resolved path is the home page path, the `home` template is used by default.
