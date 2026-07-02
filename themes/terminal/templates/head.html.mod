<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light dark">
<title>{{siteName}} — {{title}}</title>

{{#if favicon}}
<link rel="icon" type="image/x-icon" href="{{favicon}}">
<link rel="apple-touch-icon" href="{{favicon}}">
{{else}}
<link rel="icon" type="image/x-icon" href="/favicon.ico">
{{/if}}

<link rel="stylesheet" href="/themes/terminal/assets/style.css">
<link href="/public/css/output.css" rel="stylesheet">

<style>
  :root {
    --terminal-accent: {{themeOptions.accentColor}};
    --terminal-glow: 0 0 10px var(--terminal-accent), 0 0 40px var(--terminal-accent);
    --terminal-bg: #0a0a0a;
    --terminal-surface: #111111;
    --terminal-text: #c0c0c0;
    --terminal-text-dim: #666666;
    --terminal-border: #333333;
    --terminal-font: "Courier New", Courier, monospace;
  }
  body {
    background: var(--terminal-bg);
    color: var(--terminal-text);
    font-family: var(--terminal-font);
  }
</style>
