<header class="minimalist-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                {{#if logo}}
                    <img src="/{{logo}}" alt="{{siteName}}" class="minimalist-logo">
                {{else}}
                    <h1 class="site-title">{{siteName}}</h1>
                {{/if}}
            </div>
            
            <nav class="minimalist-nav">
                <ul class="nav-menu">
                    {{#each menu.main}}
                        <li class="nav-item">
                            <a href="/{{url}}" class="nav-link">{{title}}</a>
                        </li>
                    {{/each}}
                </ul>
            </nav>
        </div>
    </div>
</header> 