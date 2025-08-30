<aside class="sidebar">
    {{#if menu.sidebar}}
        <nav class="sidebar-nav">
            <h3>Navigation</h3>
            <ul>
                {{#each menu.sidebar}}
                    <li><a href="/{{url}}">{{title}}</a></li>
                {{/each}}
            </ul>
        </nav>
    {{/if}}
    
    <!-- Render the right-sidebar widgets -->
    {{sidebar=right-sidebar}}
    
    <!-- Render the main sidebar widgets -->
    {{sidebar=main}}
    
    {{#if children}}
        <div class="sidebar-pages">
            <h3>Related Pages</h3>
            <ul>
                {{#each children}}
                    <li><a href="/{{url}}">{{title}}</a></li>
                {{/each}}
            </ul>
        </div>
    {{/if}}
</aside> 