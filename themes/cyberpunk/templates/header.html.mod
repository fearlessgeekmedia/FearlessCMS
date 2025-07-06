<header class="cyberpunk-header">
    <div class="header-container">
        <div class="logo-section">
            {{#if logo}}
                <img src="/{{logo}}" alt="{{siteName}}" class="cyberpunk-logo">
            {{else}}
                <h1 class="cyberpunk-logo-text">{{siteName}}</h1>
            {{/if}}
        </div>
        
        <nav class="cyberpunk-nav">
            <ul class="nav-list">
                {{#each menu.main}}
                    <li class="nav-item">
                        <a href="/{{url}}" class="nav-link">{{title}}</a>
                    </li>
                {{/each}}
            </ul>
        </nav>
        
        <div class="header-controls">
            <button class="cyberpunk-toggle" id="theme-toggle">
                <span class="toggle-icon">⚡</span>
            </button>
        </div>
    </div>
    
    <div class="cyberpunk-glitch"></div>
</header> 