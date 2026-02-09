<meta charset="UTF-8">
<title>{{site_name}} - {{title}}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light dark">

<!-- Favicon -->
{{#if favicon}}
<link rel="icon" type="image/x-icon" href="{{favicon}}">
<link rel="apple-touch-icon" href="{{favicon}}">
{{else}}
<link rel="icon" type="image/x-icon" href="/favicon.ico">
{{/if}}

<link rel="stylesheet" href="/themes/default/assets/style.css">
<link href="/public/css/output.css" rel="stylesheet">
<style>
  {{#if themeOptions.accentColor}}
  :root { --color-accent: {{themeOptions.accentColor}}; }
  {{/if}}
  /* ensure global UI toggles visible if injected */
  .fcms-theme-toggle{display:inline-flex}
</style>
