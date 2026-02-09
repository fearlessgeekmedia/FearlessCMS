<header class="vintage-header">
    <div class="vintage-container">
        <div class="header-content">
            <div class="logo-section">
                {{#if logo}}
                    <img src="/{{logo}}" alt="{{siteName}}" class="vintage-logo">
                {{else}}
                    <h1 class="vintage-logo-text">{{siteName}}</h1>
                {{/if}}
            </div>
            
            <nav class="vintage-nav">
                {{menu=main}}
            </nav>
        </div>
    </div>
    
    <div class="vintage-border"></div>
</header> 