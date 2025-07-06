<aside class="cyberpunk-sidebar">
    <div class="sidebar-widget">
        <h3 class="widget-title">Navigation</h3>
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
    
    <div class="sidebar-widget">
        <h3 class="widget-title">System Status</h3>
        <div class="status-indicators">
            <div class="status-item">
                <span class="status-dot online"></span>
                <span class="status-text">Online</span>
            </div>
            <div class="status-item">
                <span class="status-dot secure"></span>
                <span class="status-text">Secure</span>
            </div>
        </div>
    </div>
</aside> 