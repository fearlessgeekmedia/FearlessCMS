<meta charset="UTF-8">
<title>{{site_name}} - {{title}}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light dark">
<link rel="stylesheet" href="/themes/default/assets/style.css">
<script src="https://cdn.tailwindcss.com"></script>
{{#if parallaxAssets}}{{{parallaxAssets}}}{{/if}}
<style>
  {{#if themeOptions.accentColor}}
  :root { --color-accent: {{themeOptions.accentColor}}; }
  {{/if}}
  /* ensure global UI toggles visible if injected */
  .fcms-theme-toggle{display:inline-flex}
  {{custom_css}}
</style>