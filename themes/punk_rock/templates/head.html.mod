<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{#if title}}{{title}} - {{/if}}{{site_name}}</title>
<meta name="description" content="{{#if meta_description}}{{meta_description}}{{else}}{{site_description}}{{/if}}">
<meta name="keywords" content="{{meta_keywords}}">
<meta name="author" content="{{site_name}}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="{{canonical_url}}">
<meta property="og:title" content="{{#if title}}{{title}} - {{/if}}{{site_name}}">
<meta property="og:description" content="{{#if meta_description}}{{meta_description}}{{else}}{{site_description}}{{/if}}">
<meta property="og:image" content="{{#if featured_image}}{{featured_image}}{{else}}/assets/default-og-image.jpg{{/if}}">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{canonical_url}}">
<meta property="twitter:title" content="{{#if title}}{{title}} - {{/if}}{{site_name}}">
<meta property="twitter:description" content="{{#if meta_description}}{{meta_description}}{{else}}{{site_description}}{{/if}}">
<meta property="twitter:image" content="{{#if featured_image}}{{featured_image}}{{else}}/assets/default-og-image.jpg{{/if}}">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

<!-- Theme CSS -->
<link rel="stylesheet" href="/themes/punk_rock/css/style.css">

<!-- Punk Rock Theme Meta -->
<meta name="theme-color" content="#ff0040">
<meta name="msapplication-TileColor" content="#000000">

<!-- Preconnect for performance -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Canonical URL -->
<link rel="canonical" href="{{canonical_url}}">

<!-- RSS Feed -->
{{#if rss_enabled}}
<link rel="alternate" type="application/rss+xml" title="{{site_name}} RSS Feed" href="/rss.xml">
{{/if}}
