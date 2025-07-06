<header class="site-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="logo-section">
                {{#if logo}}
                    <img src="/{{logo}}" alt="{{siteName}}" class="logo-image">
                {{else}}
                    <h1 class="site-title" style="color: {{primaryColor}}">{{siteName}}</h1>
                {{/if}}
            </div>
            
            <!-- Navigation -->
            <nav class="main-navigation">
                {{menu=main}}
            </nav>
            
            <!-- Hero Banner -->
            {{#if heroBanner}}
                <div class="hero-banner">
                    <img src="/{{heroBanner}}" alt="Hero Banner" class="banner-image">
                </div>
            {{/if}}
        </div>
    </div>
</header> 