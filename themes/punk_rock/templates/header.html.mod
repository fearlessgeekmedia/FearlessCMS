<header>
    <div class="logo">
        {{#if themeOptions.logo}}
            <img src="/{{themeOptions.logo}}" alt="{{site_name}}">
        {{else}}
            {{site_name}}
        {{/if}}
    </div>
    <div class="slogan">Loud. Broke. Unapologetically Real.</div>
    <nav class="main-menu">
        {{menu=main}}
    </nav>
</header>
