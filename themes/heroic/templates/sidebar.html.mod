<aside class="space-y-8">
    <!-- Sidebar Widgets -->
    <div class="bg-white shadow rounded-lg p-6">
        {{sidebar=main}}
    </div>

    <!-- Additional Sidebar Navigation -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Navigation</h3>
        <nav class="space-y-2">
            {{#each menu.main}}
                <a href="/{{url}}" class="block text-gray-600 hover:text-blue-600 transition-colors">{{title}}</a>
            {{/each}}
        </nav>
    </div>
</aside>