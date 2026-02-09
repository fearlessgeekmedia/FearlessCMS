<aside class="elegant-sidebar">
    <!-- CMS Dynamic Widgets -->
    {{sidebar=main}}

    <!-- Static Theme Widgets (Optional) -->
    <div class="sidebar-widget">
        <div class="widget-header">
            <h3 class="widget-title">Navigation</h3>
            <div class="widget-decoration"></div>
        </div>
        
        <div class="widget-content">
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    {{#each menu.main}}
                        <li class="sidebar-item">
                            <a href="/{{url}}" class="sidebar-link">{{title}}</a>
                        </li>
                    {{/each}}
                </ul>
            </nav>
        </div>
    </div>
    
    <div class="sidebar-widget">
        <div class="widget-header">
            <h3 class="widget-title">Categories</h3>
            <div class="widget-decoration"></div>
        </div>
        
        <div class="widget-content">
            <div class="category-elegant">
                <a href="#" class="category-link">Lifestyle</a>
                <a href="#" class="category-link">Technology</a>
                <a href="#" class="category-link">Design</a>
                <a href="#" class="category-link">Business</a>
            </div>
        </div>
    </div>
</aside> 